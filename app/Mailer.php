<?php
declare(strict_types=1);

/** Mail-Versand über PHP mail() oder einen SMTP-Server (STARTTLS/SSL, AUTH LOGIN). */
final class Mailer
{
    public static string $lastError = '';

    public static function from(): string
    {
        $from = trim(setting('mail_from'));
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }
        $host = preg_replace('/^www\./', '', (string)parse_url(abs_url('/'), PHP_URL_HOST)) ?: 'localhost';
        return 'no-reply@' . $host;
    }

    public static function send(string $to, string $subject, string $text, ?string $replyTo = null): bool
    {
        self::$lastError = '';
        $recipients = array_values(array_filter(array_map('trim', explode(',', $to)), static fn($a) => filter_var($a, FILTER_VALIDATE_EMAIL)));
        if (!$recipients) {
            self::$lastError = 'Keine gültige Empfängeradresse.';
            return false;
        }
        $subject = preg_replace('/[\r\n]+/', ' ', $subject) ?? $subject;
        $fromName = setting('site_name', 'Frank Panzer');
        $from = self::from();
        $headers = [
            'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'X-Mailer: FrankPanzer-CMS',
        ];
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $body = chunk_split(base64_encode($text));

        try {
            if (setting('mail_transport', 'mail') === 'smtp') {
                return self::smtp($recipients, $from, self::encodeHeader($subject), $headers, $body);
            }
            $ok = @mail(implode(',', $recipients), self::encodeHeader($subject), $body, implode("\r\n", $headers), '-f' . $from);
            if (!$ok) {
                self::$lastError = 'mail() wurde vom Server abgelehnt.';
            }
            return $ok;
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();
            return false;
        }
    }

    private static function encodeHeader(string $s): string
    {
        return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    private static function smtp(array $to, string $from, string $subject, array $headers, string $body): bool
    {
        $host = setting('smtp_host');
        $port = (int)setting('smtp_port', '587');
        $secure = setting('smtp_secure', 'tls');
        if ($host === '') {
            self::$lastError = 'SMTP-Host fehlt.';
            return false;
        }
        $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 15);
        if (!$fp) {
            self::$lastError = "Verbindung fehlgeschlagen: $errstr";
            return false;
        }
        stream_set_timeout($fp, 15);
        $read = static function () use ($fp): string {
            $out = '';
            while (($line = fgets($fp, 1024)) !== false) {
                $out .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $out;
        };
        $cmd = static function (string $c, array $expect) use ($fp, $read): string {
            fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!in_array((int)substr($r, 0, 3), $expect, true)) {
                throw new RuntimeException('SMTP: ' . trim($r));
            }
            return $r;
        };
        try {
            $r = $read();
            if ((int)substr($r, 0, 3) !== 220) {
                throw new RuntimeException('SMTP-Begrüßung: ' . trim($r));
            }
            $ehlo = 'EHLO ' . (parse_url(abs_url('/'), PHP_URL_HOST) ?: 'localhost');
            $cmd($ehlo, [250]);
            if ($secure === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS fehlgeschlagen.');
                }
                $cmd($ehlo, [250]);
            }
            $user = setting('smtp_user');
            if ($user !== '') {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode($user), [334]);
                $cmd(base64_encode(setting('smtp_pass')), [235]);
            }
            $cmd('MAIL FROM:<' . $from . '>', [250]);
            foreach ($to as $rcpt) {
                $cmd('RCPT TO:<' . $rcpt . '>', [250, 251]);
            }
            $cmd('DATA', [354]);
            $msg = 'Date: ' . date('r') . "\r\n"
                 . 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . (parse_url(abs_url('/'), PHP_URL_HOST) ?: 'localhost') . ">\r\n"
                 . 'To: ' . implode(', ', $to) . "\r\n"
                 . 'Subject: ' . $subject . "\r\n"
                 . implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $msg = preg_replace('/^\./m', '..', $msg) ?? $msg;
            $cmd($msg . "\r\n.", [250]);
            $cmd('QUIT', [221]);
            fclose($fp);
            return true;
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();
            @fclose($fp);
            return false;
        }
    }
}
