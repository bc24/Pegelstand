<?php
declare(strict_types=1);

/** Minimaler HTTP-Client (cURL, sonst PHP-Streams) für Server-zu-Server-Abrufe mit Zeitlimit. */
final class Http
{
    public const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    /** @return array{status:int,body:string,error:string} */
    public static function get(string $url, int $timeout = 15, array $headers = []): array
    {
        if (!preg_match('~^https://~i', $url)) {
            return ['status' => 0, 'body' => '', 'error' => 'Nur HTTPS erlaubt.'];
        }
        $headers = array_merge(['User-Agent: ' . self::UA, 'Accept-Language: de-DE,de;q=0.9,en;q=0.7'], $headers);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4,
                CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '', CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            ]);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            return ['status' => $status, 'body' => is_string($body) ? $body : '', 'error' => $err];
        }
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => $timeout, 'header' => implode("\r\n", $headers), 'follow_location' => 1, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $h, $m)) {
                $status = (int)$m[1];
            }
        }
        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'error' => $body === false ? 'Abruf fehlgeschlagen (allow_url_fopen/cURL prüfen).' : ''];
    }
}
