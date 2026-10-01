<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/**
 * Erkennt Browser, Betriebssystem und Gerätetyp aus dem User-Agent. Bewusst grob und ohne Versionen:
 * Das ist für die Auswertung genug und gibt dem Gerät weniger Merkmale preis.
 */
final class UserAgentParser
{
    public function parse(string $ua): UserAgentInfo
    {
        return new UserAgentInfo($this->browser($ua), $this->os($ua), $this->device($ua));
    }

    private function browser(string $ua): ?string
    {
        $regeln = [
            'Edge' => '/\b(Edg|EdgA|EdgiOS|Edge)\//',
            'Opera' => '/\b(OPR|Opera|OPT)\//',
            'Samsung Internet' => '/SamsungBrowser\//',
            'Vivaldi' => '/Vivaldi\//',
            'Firefox' => '/\b(Firefox|FxiOS)\//',
            'Chrome' => '/\b(Chrome|CriOS|Chromium)\//',
            'Safari' => '/Version\/[\d.]+.*Safari\//',
        ];
        foreach ($regeln as $name => $muster) {
            if (preg_match($muster, $ua) === 1) {
                return $name;
            }
        }

        return null;
    }

    private function os(string $ua): ?string
    {
        $regeln = [
            'iOS' => '/\b(iPhone|iPad|iPod)\b/',
            'Android' => '/Android/',
            'Windows' => '/Windows/',
            'ChromeOS' => '/CrOS/',
            'macOS' => '/Macintosh|Mac OS X/',
            'Linux' => '/Linux|X11/',
        ];
        foreach ($regeln as $name => $muster) {
            if (preg_match($muster, $ua) === 1) {
                return $name;
            }
        }

        return null;
    }

    private function device(string $ua): int
    {
        if ($ua === '') {
            return UserAgentInfo::DEVICE_UNKNOWN;
        }
        if (preg_match('/iPad|Tablet|Kindle|Silk\/|PlayBook/i', $ua) === 1
            || (str_contains($ua, 'Android') && !str_contains($ua, 'Mobile'))) {
            return UserAgentInfo::DEVICE_TABLET;
        }
        if (preg_match('/Mobi|iPhone|iPod|Android|Windows Phone|BlackBerry/i', $ua) === 1) {
            return UserAgentInfo::DEVICE_MOBILE;
        }

        return UserAgentInfo::DEVICE_DESKTOP;
    }
}
