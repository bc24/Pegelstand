<?php
declare(strict_types=1);

/**
 * TikTok-Anbindung ohne API-Schlüssel: liest die offizielle Profil-Einbettung
 * (https://www.tiktok.com/embed/@name) und speichert die letzten Videos mit lokalem Cover.
 * Die Besucher der Website verbinden sich erst nach einem Klick auf einen Song mit TikTok.
 */
final class TikTok
{
    public static function validUser(string $user): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9._]{2,24}$/', $user);
    }

    public static function videoId(string $url): string
    {
        return preg_match('~tiktok\.com/@[^/]+/video/(\d{10,25})~i', $url, $m) ? $m[1] : '';
    }

    public static function videoUrl(string $user, string $id): string
    {
        return 'https://www.tiktok.com/@' . $user . '/video/' . $id;
    }

    /** Veröffentlichungszeit aus der zeitbasierten Video-ID (obere 32 Bit = Unix-Zeit). */
    public static function dateFromId(string $id): string
    {
        return date('Y-m-d H:i:s', ((int)$id) >> 32);
    }

    /** Kurztitel: "DJ-Frankus – <Songname>" aus der Beschreibung, sonst die ersten Sätze ohne Hashtags. */
    public static function titleFromCaption(string $caption): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', preg_replace('/#[\p{L}\p{N}_]+/u', '', $caption) ?? '') ?? '');
        if (preg_match('/DJ-Frankus\s*[–—-]\s*([^\p{So}\p{Sk}.!?#]{3,60})/u', $t, $m)) {
            $name = trim($m[1]);
            if (mb_strlen($name) >= 3) {
                return $name;
            }
        }
        $out = '';
        foreach (preg_split('/(?<=[.!?…])\s+/u', $t) ?: [] as $sentence) {
            if ($out !== '' && mb_strlen($out . ' ' . $sentence) > 64) {
                break;
            }
            $out = trim($out . ' ' . $sentence);
            if (mb_strlen($out) >= 40) {
                break;
            }
        }
        if (mb_strlen($out) > 80) {
            $out = rtrim(mb_substr($out, 0, mb_strrpos(mb_substr($out, 0, 78), ' ') ?: 78), " ,;:-") . '…';
        }
        $out = trim(preg_replace('/[\p{So}\p{Sk}\x{FE0F}\x{200D}\s]+$/u', '', $out) ?? $out);
        return $out !== '' ? $out : 'TikTok';
    }

    public static function hashtags(string $caption, int $max = 3): string
    {
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $caption, $m);
        $tags = array_filter($m[1] ?? [], static fn($t) => !preg_match('/^dj_?frankus$/i', $t));
        return implode(', ', array_slice(array_unique($tags), 0, $max));
    }

    /** @return array{items:array<int,array>,error:string} neueste zuerst */
    public static function fetchProfile(string $user, int $limit = 10): array
    {
        if (!self::validUser($user)) {
            return ['items' => [], 'error' => 'Ungültiger TikTok-Benutzername.'];
        }
        $r = Http::get('https://www.tiktok.com/embed/@' . $user, 20);
        if ($r['status'] !== 200 || $r['body'] === '') {
            return ['items' => [], 'error' => 'TikTok nicht erreichbar (HTTP ' . $r['status'] . ($r['error'] ? ', ' . $r['error'] : '') . ').'];
        }
        if (!preg_match('~<script id="__FRONTITY_CONNECT_STATE__"[^>]*>(.*?)</script>~s', $r['body'], $m)) {
            return ['items' => [], 'error' => 'TikTok hat das Format der Profil-Einbettung geändert. Bitte Songs manuell hinzufügen.'];
        }
        $state = json_decode($m[1], true);
        $items = [];
        $walk = static function ($node) use (&$walk, &$items): void {
            if (!is_array($node)) {
                return;
            }
            if (isset($node['id'], $node['coverUrl'], $node['desc']) && !is_array($node['id'])) {
                $items[(string)$node['id']] = [
                    'id' => (string)$node['id'], 'caption' => (string)$node['desc'], 'plays' => (int)($node['playCount'] ?? 0),
                    'cover_url' => (string)$node['coverUrl'],
                ];
                return;
            }
            foreach ($node as $v) {
                $walk($v);
            }
        };
        $walk($state);
        if (!$items) {
            return ['items' => [], 'error' => 'Keine Videos gefunden (Profil privat oder ohne Videos?).'];
        }
        krsort($items, SORT_NUMERIC);
        return ['items' => array_slice(array_values($items), 0, max(1, $limit)), 'error' => ''];
    }

    /** Titel/Cover eines einzelnen Videos über die offizielle oEmbed-Schnittstelle. */
    public static function oembed(string $videoUrl): ?array
    {
        $r = Http::get('https://www.tiktok.com/oembed?url=' . rawurlencode($videoUrl), 12);
        $j = $r['status'] === 200 ? json_decode($r['body'], true) : null;
        return is_array($j) && !empty($j['thumbnail_url']) ? ['title' => (string)($j['title'] ?? ''), 'thumbnail_url' => (string)$j['thumbnail_url']] : null;
    }

    /** Cover herunterladen (nur TikTok-CDN), neu kodieren und lokal ablegen. @return string relativer Pfad oder '' */
    public static function saveCover(string $id, string $url): string
    {
        $host = (string)parse_url($url, PHP_URL_HOST);
        if (!preg_match('~^https://~', $url) || !preg_match('~(^|\.)(tiktokcdn(-[a-z]+)?\.com|tiktokv\.(com|us|eu)|tiktokcdn\.com)$~i', $host)) {
            return '';
        }
        $r = Http::get($url, 20, ['Referer: https://www.tiktok.com/']);
        if ($r['status'] !== 200 || strlen($r['body']) < 500) {
            return '';
        }
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->buffer($r['body']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return '';
        }
        $dir = FP_ROOT . '/uploads/tiktok';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return '';
        }
        $rel = 'uploads/tiktok/' . preg_replace('/\D/', '', $id);
        if (extension_loaded('gd') && ($im = @imagecreatefromstring($r['body']))) {
            $w = imagesx($im);
            if ($w > 720) {
                $im = imagescale($im, 720, (int)round(imagesy($im) * 720 / $w), IMG_BICUBIC) ?: $im;
            }
            if (imagejpeg($im, FP_ROOT . '/' . $rel . '.jpg', 82)) {
                return $rel . '.jpg';
            }
            return '';
        }
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
        return file_put_contents(FP_ROOT . '/' . $rel . '.' . $ext, $r['body']) !== false ? $rel . '.' . $ext : '';
    }

    /**
     * Letzte Videos abgleichen: neue anlegen, vorhandene aktualisieren, ältere automatisch importierte entfernen.
     * @return array{added:int,updated:int,removed:int,error:string}
     */
    public static function sync(string $user, int $limit = 10): array
    {
        $res = ['added' => 0, 'updated' => 0, 'removed' => 0, 'error' => ''];
        $fetch = self::fetchProfile($user, $limit);
        if ($fetch['error'] !== '') {
            $res['error'] = $fetch['error'];
            return $res;
        }
        $keep = [];
        $minSort = (int)Db::val('SELECT COALESCE(MIN(sort), 0) FROM tracks');
        // Älteste zuerst anlegen, damit das neueste Video die kleinste Sortierzahl (= ganz vorn) bekommt
        foreach (array_reverse($fetch['items']) as $it) {
            $keep[] = $it['id'];
            $row = Db::one('SELECT * FROM tracks WHERE video_id = ?', [$it['id']]);
            if ($row) {
                // Bereits vorhandene Songs: nur Aufrufe (und fehlendes Cover) aktualisieren – Titel/Reihenfolge bleiben wie im Admin gepflegt
                $upd = ['plays' => $it['plays']];
                if ($row['image'] === '' || !is_file(FP_ROOT . '/' . $row['image'])) {
                    $cover = self::saveCover($it['id'], $it['cover_url']);
                    if ($cover !== '') {
                        $upd['image'] = $cover;
                    }
                }
                Db::update('tracks', (int)$row['id'], $upd);
                $res['updated']++;
                continue;
            }
            $minSort -= 10;
            Db::insert('tracks', [
                'title' => self::titleFromCaption($it['caption']), 'caption' => $it['caption'], 'genre' => self::hashtags($it['caption']),
                'platform' => 'TikTok', 'url' => self::videoUrl($user, $it['id']), 'video_id' => $it['id'],
                'image' => self::saveCover($it['id'], $it['cover_url']), 'plays' => $it['plays'],
                'published_at' => self::dateFromId($it['id']), 'source' => 'tiktok', 'sort' => $minSort, 'visible' => 1,
            ]);
            $res['added']++;
        }
        // Automatisch importierte, nicht mehr zu den neuesten gehörende Videos entfernen (manuelle bleiben)
        $marks = implode(',', array_fill(0, count($keep), '?'));
        foreach (Db::all("SELECT id, image FROM tracks WHERE source = 'tiktok' AND video_id NOT IN ($marks)", $keep) as $old) {
            if ($old['image'] && str_starts_with($old['image'], 'uploads/tiktok/') && !str_contains($old['image'], '..')) {
                @unlink(FP_ROOT . '/' . $old['image']);
            }
            Db::delete('tracks', (int)$old['id']);
            $res['removed']++;
        }
        Settings::set('tiktok_last_sync', now());
        return $res;
    }
}
