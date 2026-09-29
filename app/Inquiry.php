<?php
declare(strict_types=1);

/** Projektanfrage (Panzer IT) und Booking-Anfrage (DJ-Frankus): Auswahllisten, Prüfung, Speicherung, Benachrichtigung. */
final class Inquiry
{
    public const KINDS = ['project' => '/anfrage/', 'booking' => '/booking/'];

    /** Zeilen einer Auswahlliste (Einstellungen) in der angegebenen Sprache. */
    public static function options(string $key, ?string $lang = null): array
    {
        $lang ??= Lang::$code;
        $v = Settings::get($key . '_' . $lang, '');
        if (trim($v) === '') {
            $v = Settings::get($key . '_de', '');
        }
        return lines($v);
    }

    public static function path(string $kind): string
    {
        return self::KINDS[$kind] ?? '/anfrage/';
    }

    /** Auswahl per Index prüfen; gespeichert wird die deutsche Bezeichnung (Admin liest Deutsch). */
    private static function choice(string $key, mixed $raw): ?string
    {
        if (!is_string($raw) || !ctype_digit($raw)) {
            return null;
        }
        $i = (int)$raw;
        $mine = self::options($key);
        if (!isset($mine[$i])) {
            return null;
        }
        return self::options($key, 'de')[$i] ?? $mine[$i];
    }

    public static function submit(string $kind): never
    {
        $kind = $kind === 'booking' ? 'booking' : 'project';
        $path = self::path($kind);
        $ok = u($path) . '?sent=1';
        $success = s($kind === 'booking' ? 'book_success' : 'inq_success');
        $fail = static fn(string $key) => Front::reply(false, tr($key), u($path) . '?err=' . $key, ['code' => $key]);
        $line = static fn(string $k): string => trim(preg_replace('/[\x00-\x1f\x7f]+/', ' ', (string)($_POST[$k] ?? '')) ?? '');

        // Honeypot: Bots bekommen eine "Erfolgs"-Antwort
        if (trim((string)($_POST['homepage'] ?? '')) !== '') {
            Front::reply(true, $success, $ok);
        }
        if (!FormToken::verify((string)($_POST['_t'] ?? ''), 'inquiry-' . $kind)) {
            $fail('err_spam');
        }

        $name = $line('name');
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = $line('phone');
        $message = trim(str_replace("\0", '', (string)($_POST['message'] ?? '')));
        if ($name === '' || $email === '') {
            $fail('err_required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $fail('err_email');
        }
        if (mb_strlen($name) > 120 || mb_strlen($phone) > 40 || mb_strlen($message) > 5000
            || ($phone !== '' && !preg_match('/^[0-9+()\/.\s-]{4,40}$/', $phone))) {
            $fail('err_length');
        }

        $details = [];
        if ($phone !== '') {
            $details['Telefon'] = $phone;
        }

        if ($kind === 'project') {
            $company = $line('company');
            $site = $line('current_site');
            if (mb_strlen($company) > 120 || mb_strlen($site) > 190) {
                $fail('err_length');
            }
            if ($site !== '') {
                $probe = preg_match('~^https?://~i', $site) ? $site : 'https://' . $site;
                if (!filter_var($probe, FILTER_VALIDATE_URL)) {
                    $fail('err_length');
                }
                $site = $probe;
            }
            $type = self::choice('inq_types', $_POST['type'] ?? null);
            $time = self::choice('inq_timeframes', $_POST['timeframe'] ?? null);
            $budget = self::choice('inq_budgets', $_POST['budget'] ?? null);
            if ($type === null || $time === null || $budget === null) {
                $fail('err_choice');
            }
            if ($message === '') {
                $fail('err_required');
            }
            if (mb_strlen($message) < 10) {
                $fail('err_length');
            }
            $details += ['Projektart' => $type, 'Zeitrahmen' => $time, 'Budget' => $budget];
            if ($company !== '') {
                $details['Firma'] = $company;
            }
            if ($site !== '') {
                $details['Bestehende Website'] = $site;
            }
            $subject = 'Projektanfrage: ' . $type;
        } else {
            $date = $line('date');
            $location = $line('location');
            $type = self::choice('book_types', $_POST['type'] ?? null);
            $guests = $line('guests');
            $span = $line('timespan');
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $valid = $dt !== false && $dt->format('Y-m-d') === $date;
            if ($location === '' || $date === '') {
                $fail('err_required');
            }
            if (!$valid || $dt < new DateTimeImmutable('today') || $dt > new DateTimeImmutable('+3 years')) {
                $fail('err_date');
            }
            if ($type === null) {
                $fail('err_choice');
            }
            if (mb_strlen($location) > 160 || mb_strlen($span) > 80 || ($guests !== '' && (!ctype_digit($guests) || (int)$guests < 1 || (int)$guests > 100000))) {
                $fail('err_length');
            }
            $details += ['Datum' => $dt->format('d.m.Y'), 'Ort' => $location, 'Anlass' => $type];
            if ($guests !== '') {
                $details['Gäste (ca.)'] = $guests;
            }
            if ($span !== '') {
                $details['Zeitraum / Spieldauer'] = $span;
            }
            $subject = 'Booking-Anfrage: ' . $dt->format('d.m.Y') . ', ' . $location;
        }
        $subject = mb_substr($subject, 0, 200);

        if (!RateLimit::hit('inq:' . substr(ip_hash('inquiry'), 0, 32), 5, 3600) || !RateLimit::hit('inq:all', 60, 3600)) {
            $fail('err_rate');
        }

        $id = Db::insert('messages', [
            'name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message,
            'kind' => $kind, 'details' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'new', 'lang' => Lang::$code, 'created_at' => now(),
        ]);

        $body = ($kind === 'booking' ? 'Neue Booking-Anfrage' : 'Neue Projektanfrage') . ' über ' . page_url('/') . "\n\n"
            . "Von:     $name <$email>\nSprache: " . strtoupper(Lang::$code) . "\n";
        foreach ($details as $label => $value) {
            $body .= str_pad($label . ':', 22) . $value . "\n";
        }
        if ($message !== '') {
            $body .= "\n" . $message . "\n";
        }
        $body .= "\n—\nIm Admin-Bereich ansehen: " . abs_url('/admin/?p=messages&id=' . $id) . "\n";
        $notify = setting('contact_notify') ?: setting('contact_email');
        Mailer::send($notify, '[' . setting('site_name') . '] ' . $subject, $body, $email);

        Front::reply(true, $success, $ok);
    }

    /** Lesbare Zusammenfassung für die Nachrichtenliste im Admin. */
    public static function summary(array $row): string
    {
        $d = json_decode((string)($row['details'] ?? ''), true);
        if (!is_array($d) || !$d) {
            return '';
        }
        $keys = ($row['kind'] ?? '') === 'booking' ? ['Datum', 'Ort', 'Anlass'] : ['Projektart', 'Zeitrahmen', 'Budget'];
        $out = [];
        foreach ($keys as $k) {
            if (!empty($d[$k])) {
                $out[] = $d[$k];
            }
        }
        return implode(' · ', $out);
    }
}
