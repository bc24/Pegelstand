<?php

declare(strict_types=1);

namespace Pegelstand\Core;

/**
 * Schutz vor Cross-Site-Request-Forgery: Jedes Formular enthält den Token der Sitzung.
 */
final class Csrf
{
    private const KEY = '_csrf';

    public function __construct(private readonly Session $session) {}

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function verify(string $eingereicht): bool
    {
        $token = $this->session->get(self::KEY);

        return is_string($token) && $token !== '' && hash_equals($token, $eingereicht);
    }
}
