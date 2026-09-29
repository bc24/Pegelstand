<?php
declare(strict_types=1);

/** Sichere Uploads: MIME-Prüfung per fileinfo, Neu-Kodierung (entfernt EXIF/GPS), Vorschaubilder. */
final class Media
{
    public const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'application/pdf' => 'pdf',
    ];
    public const MAX_DIM = 2560;
    public const THUMB = 520;

    /** @return array{ok:bool,error?:string,id?:int,path?:string,thumb?:string} */
    public static function store(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadError((int)($file['error'] ?? 0))];
        }
        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp) && !(defined('FP_TESTING') && is_file($tmp))) {
            return ['ok' => false, 'error' => 'Ungültiger Upload.'];
        }
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset(self::ALLOWED[$mime])) {
            return ['ok' => false, 'error' => 'Dateityp nicht erlaubt (' . $mime . '). Erlaubt: JPG, PNG, WebP, GIF, PDF.'];
        }
        $ext = self::ALLOWED[$mime];
        $dir = 'uploads/' . date('Y') . '/' . date('m');
        $abs = FP_ROOT . '/' . $dir;
        if (!is_dir($abs) && !@mkdir($abs, 0755, true) && !is_dir($abs)) {
            return ['ok' => false, 'error' => 'Upload-Ordner ist nicht beschreibbar.'];
        }
        $name = bin2hex(random_bytes(8));
        $rel = $dir . '/' . $name . '.' . $ext;
        $target = FP_ROOT . '/' . $rel;
        $w = $h = 0;
        $thumbRel = '';

        if ($mime === 'application/pdf') {
            if (!move_uploaded_file($tmp, $target) && !@rename($tmp, $target)) {
                return ['ok' => false, 'error' => 'Datei konnte nicht gespeichert werden.'];
            }
        } else {
            $info = @getimagesize($tmp);
            if (!$info) {
                return ['ok' => false, 'error' => 'Die Datei ist kein gültiges Bild.'];
            }
            [$w, $h] = $info;
            if ($w * $h > 60_000_000) {
                return ['ok' => false, 'error' => 'Bild ist zu groß (Pixelanzahl).'];
            }
            $processed = false;
            if (extension_loaded('gd') && $mime !== 'gif') {
                $processed = self::reencode($tmp, $target, $mime, $w, $h);
                if ($processed) {
                    [$w, $h] = array_slice((array)getimagesize($target), 0, 2);
                }
            }
            if (!$processed && !move_uploaded_file($tmp, $target) && !@rename($tmp, $target)) {
                return ['ok' => false, 'error' => 'Datei konnte nicht gespeichert werden.'];
            }
            $thumbRel = self::thumb($target, $dir, $name, $mime);
        }
        @chmod($target, 0644);
        $id = Db::insert('media', [
            'path' => $rel, 'thumb' => $thumbRel,
            'original_name' => mb_substr(preg_replace('/[^\p{L}\p{N}._ -]+/u', '', basename((string)($file['name'] ?? $name))) ?? $name, 0, 200),
            'mime' => $mime, 'size' => (int)filesize($target), 'width' => (int)$w, 'height' => (int)$h,
            'alt' => '', 'created_at' => now(),
        ]);
        return ['ok' => true, 'id' => $id, 'path' => $rel, 'thumb' => $thumbRel ?: $rel];
    }

    private static function reencode(string $src, string $dst, string $mime, int $w, int $h): bool
    {
        $im = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png'  => @imagecreatefrompng($src),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
            default => false,
        };
        if (!$im) {
            return false;
        }
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($src);
            $rot = [3 => 180, 6 => -90, 8 => 90][(int)($exif['Orientation'] ?? 1)] ?? 0;
            if ($rot) {
                $im = imagerotate($im, $rot, 0) ?: $im;
                $w = imagesx($im);
                $h = imagesy($im);
            }
        }
        if (max($w, $h) > self::MAX_DIM) {
            $scale = self::MAX_DIM / max($w, $h);
            $resized = imagescale($im, (int)round($w * $scale), (int)round($h * $scale), IMG_BICUBIC);
            if ($resized) {
                $im = $resized;
            }
        }
        if ($mime !== 'image/jpeg') {
            imagealphablending($im, false);
            imagesavealpha($im, true);
        }
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($im, $dst, 86),
            'image/png'  => imagepng($im, $dst, 6),
            'image/webp' => imagewebp($im, $dst, 86),
            default => false,
        };
        return (bool)$ok;
    }

    private static function thumb(string $abs, string $dir, string $name, string $mime): string
    {
        if (!extension_loaded('gd') || $mime === 'image/gif') {
            return '';
        }
        $im = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($abs),
            'image/png'  => @imagecreatefrompng($abs),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($abs) : false,
            default => false,
        };
        if (!$im) {
            return '';
        }
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w > self::THUMB) {
            $im = imagescale($im, self::THUMB, (int)round($h * self::THUMB / $w), IMG_BICUBIC) ?: $im;
        }
        $rel = $dir . '/' . $name . '-thumb.jpg';
        // Transparenz auf dunklem Grund flachrechnen (Vorschau im Admin)
        $flat = imagecreatetruecolor(imagesx($im), imagesy($im));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 22, 28, 38));
        imagecopy($flat, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
        imagejpeg($flat, FP_ROOT . '/' . $rel, 82);
        return $rel;
    }

    public static function delete(int $id): void
    {
        $m = Db::one('SELECT path, thumb FROM media WHERE id = ?', [$id]);
        if (!$m) {
            return;
        }
        foreach ([$m['path'], $m['thumb']] as $rel) {
            if ($rel && str_starts_with($rel, 'uploads/') && !str_contains($rel, '..')) {
                @unlink(FP_ROOT . '/' . $rel);
            }
        }
        Db::delete('media', $id);
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        return max(1, (int)round($bytes / 1024)) . ' KB';
    }

    private static function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Datei ist größer als das erlaubte Limit (upload_max_filesize ' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_PARTIAL => 'Upload wurde unterbrochen.',
            UPLOAD_ERR_NO_FILE => 'Keine Datei ausgewählt.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Der Server kann die Datei nicht zwischenspeichern.',
            default => 'Upload fehlgeschlagen.',
        };
    }
}
