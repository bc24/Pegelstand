<?php

declare(strict_types=1);

// Testserver: php fake-smtp.php PORT PROTOKOLLDATEI [auth] [ablehnen]
[, $port, $datei, $auth, $ablehnen] = $argv + [null, '0', '', '', '0'];
$server = stream_socket_server('tcp://127.0.0.1:' . $port, $nr, $text);
if ($server === false) {
    exit(1);
}
$log = static fn(string $zeile) => file_put_contents($datei, $zeile . "\n", FILE_APPEND);
// Verbindungen nur zum Bereitschaftstest werden sofort wieder geschlossen und zählen nicht.
while (($c = @stream_socket_accept($server, 30)) !== false) {
    stream_set_timeout($c, 5);
    fwrite($c, "220 fake ESMTP\r\n");
    $daten = false;
    $gelesen = 0;
    while (($zeile = fgets($c)) !== false) {
        ++$gelesen;
        $zeile = rtrim($zeile, "\r\n");
        if ($daten) {
            $log('D> ' . $zeile);
            if ($zeile === '.') {
                $daten = false;
                fwrite($c, $ablehnen === '1' ? "554 abgelehnt\r\n" : "250 ok queued\r\n");
            }
            continue;
        }
        $log('C> ' . $zeile);
        $cmd = strtoupper(substr($zeile, 0, 4));
        if ($cmd === 'EHLO') {
            fwrite($c, "250-fake\r\n" . ($auth !== '' ? "250-AUTH PLAIN LOGIN\r\n" : '') . "250 8BITMIME\r\n");
        } elseif ($cmd === 'AUTH') {
            [$user, $pw] = explode(':', $auth, 2) + ['', ''];
            $teile = explode(' ', $zeile);
            $ok = ($teile[1] ?? '') === 'PLAIN' && base64_decode($teile[2] ?? '') === "\0$user\0$pw";
            fwrite($c, $ok ? "235 ok\r\n" : "535 falsch\r\n");
        } elseif ($cmd === 'DATA') {
            $daten = true;
            fwrite($c, "354 los\r\n");
        } elseif ($cmd === 'QUIT') {
            fwrite($c, "221 tschuess\r\n");
            break;
        } else {
            fwrite($c, "250 ok\r\n");
        }
    }
    fclose($c);
    if ($gelesen > 0) {
        break;
    }
}
