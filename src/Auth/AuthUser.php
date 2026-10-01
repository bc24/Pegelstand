<?php

declare(strict_types=1);

namespace Pegelstand\Auth;

/** Der angemeldete Benutzer. */
final class AuthUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
    ) {}

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
