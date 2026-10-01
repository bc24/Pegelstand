<?php

declare(strict_types=1);

namespace Pegelstand\Mail;

/**
 * Schlanker SMTP-Client ohne Abhängigkeit: SSL/STARTTLS, Anmeldung per PLAIN oder LOGIN, UTF-8-Nachrichten.
 * Prüft Zertifikate. Zugangsdaten tauchen weder in Meldungen noch in Protokollen auf.
 */
final class SmtpClient
{
    /** @var resource|null */
    private $socket;

    public function __construct(private readonly SmtpConfig $config) {}

    /**
     * @throws MailException
     */
    public function send(Message $message): void
    {
        if (preg_match('/^[^\s@<>",;]+@[^\s@<>",;]+$/', $message->to) !== 1 || preg_match('/[\r\n]/', $message->to . $message->subject) === 1) {
            throw new MailException('Die Empfängeradresse oder der Betreff ist ungültig.');
        }
        try {
            $this->verbinden();
            $this->befehl(null, [220]);
            $domain = $this->ehloName();
            $antwort = $this->befehl('EHLO ' . $domain, [250]);
            if ($this->config->security === 'starttls') {
                $this->befehl('STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($this->socket(), true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new MailException('Die verschlüsselte Verbindung (STARTTLS) konnte nicht aufgebaut werden.');
                }
                $antwort = $this->befehl('EHLO ' . $domain, [250]);
            }
            if ($this->config->user !== '') {
                $this->anmelden($antwort);
            }
            $this->befehl('MAIL FROM:<' . $this->config->fromAddress . '>', [250]);
            $this->befehl('RCPT TO:<' . $message->to . '>', [250, 251]);
            $this->befehl('DATA', [354]);
            $this->roh($this->aufbauen($message) . "\r\n.\r\n");
            $this->lies([250]);
            $this->befehl('QUIT', [221], false);
        } finally {
            if (is_resource($this->socket)) {
                @fclose($this->socket);
            }
            $this->socket = null;
        }
    }

    private function verbinden(): void
    {
        $c = $this->config;
        $ziel = ($c->security === 'tls' ? 'ssl://' : 'tcp://') . $c->host . ':' . $c->port;
        $fehlerNr = 0;
        $fehlerText = '';
        $kontext = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $c->host]]);
        $socket = @stream_socket_client($ziel, $fehlerNr, $fehlerText, $c->timeout, STREAM_CLIENT_CONNECT, $kontext);
        if ($socket === false) {
            throw new MailException(sprintf('Keine Verbindung zu %s:%d (%s).', $c->host, $c->port, $fehlerText !== '' ? $fehlerText : 'Fehler ' . $fehlerNr));
        }
        stream_set_timeout($socket, $c->timeout);
        $this->socket = $socket;
    }

    /**
     * @return resource
     */
    private function socket()
    {
        if (!is_resource($this->socket)) {
            throw new MailException('Die Verbindung zum Mailserver ist nicht offen.');
        }

        return $this->socket;
    }

    private function ehloName(): string
    {
        $name = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) gethostname());

        return $name === '' || $name === null ? 'localhost' : $name;
    }

    private function anmelden(string $ehlo): void
    {
        $c = $this->config;
        if (preg_match('/AUTH[ =].*\bPLAIN\b/i', $ehlo) === 1) {
            $this->befehl('AUTH PLAIN ' . base64_encode("\0" . $c->user . "\0" . $c->password), [235], true, 'Die Anmeldung am Mailserver wurde abgelehnt. Prüfe Benutzername und Passwort.');

            return;
        }
        $this->befehl('AUTH LOGIN', [334]);
        $this->befehl(base64_encode($c->user), [334]);
        $this->befehl(base64_encode($c->password), [235], true, 'Die Anmeldung am Mailserver wurde abgelehnt. Prüfe Benutzername und Passwort.');
    }

    /**
     * @param list<int> $erwartet
     */
    private function befehl(?string $zeile, array $erwartet, bool $lesen = true, ?string $fehlertext = null): string
    {
        if ($zeile !== null) {
            $this->roh($zeile . "\r\n");
        }

        return $lesen ? $this->lies($erwartet, $fehlertext) : '';
    }

    private function roh(string $daten): void
    {
        $rest = $daten;
        while ($rest !== '') {
            $n = @fwrite($this->socket(), $rest);
            if ($n === false || $n === 0) {
                throw new MailException('Das Senden an den Mailserver ist abgebrochen.');
            }
            $rest = substr($rest, $n);
        }
    }

    /**
     * @param list<int> $erwartet
     */
    private function lies(array $erwartet, ?string $fehlertext = null): string
    {
        $antwort = '';
        do {
            $zeile = @fgets($this->socket(), 2048);
            if ($zeile === false) {
                throw new MailException('Der Mailserver antwortet nicht (Zeitüberschreitung).');
            }
            $antwort .= $zeile;
        } while (strlen($zeile) >= 4 && $zeile[3] === '-');

        $code = (int) substr($antwort, 0, 3);
        if (!in_array($code, $erwartet, true)) {
            throw new MailException($fehlertext ?? 'Der Mailserver hat abgelehnt: ' . trim(substr($antwort, 0, 200)));
        }

        return $antwort;
    }

    private function aufbauen(Message $m): string
    {
        $c = $this->config;
        $kopf = [
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'From: ' . self::kopfWert($c->fromName) . ' <' . $c->fromAddress . '>',
            'To: <' . $m->to . '>',
            'Subject: ' . self::kopfWert($m->subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (substr(strrchr($c->fromAddress, '@') ?: '@localhost', 1)) . '>',
            'MIME-Version: 1.0',
            'Auto-Submitted: auto-generated',
        ];
        if ($m->html === null) {
            $kopf[] = 'Content-Type: text/plain; charset=UTF-8';
            $kopf[] = 'Content-Transfer-Encoding: quoted-printable';
            $koerper = self::qp($m->text);
        } else {
            $grenze = 'pegelstand-' . bin2hex(random_bytes(8));
            $kopf[] = 'Content-Type: multipart/alternative; boundary="' . $grenze . '"';
            $koerper = '--' . $grenze . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n" . self::qp($m->text)
                . "\r\n--" . $grenze . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n" . self::qp($m->html)
                . "\r\n--" . $grenze . '--';
        }

        // Punkte am Zeilenanfang verdoppeln (SMTP-Transparenz).
        return (string) preg_replace('/^\./m', '..', implode("\r\n", $kopf) . "\r\n\r\n" . $koerper);
    }

    public static function kopfWert(string $text): string
    {
        $text = (string) preg_replace('/[\r\n]+/', ' ', $text);
        if (preg_match('/^[\x20-\x7E]*$/', $text) === 1) {
            return $text;
        }

        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    private static function qp(string $text): string
    {
        return quoted_printable_encode(str_replace("\n", "\r\n", str_replace(["\r\n", "\r"], "\n", $text)));
    }
}
