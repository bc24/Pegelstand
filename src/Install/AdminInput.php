<?php

declare(strict_types=1);

namespace Pegelstand\Install;

/**
 * Angaben zum ersten Administrator samt Validierung.
 */
final class AdminInput
{
    public const MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $passwordRepeat,
    ) {}

    /**
     * @param array<mixed> $post
     */
    public static function fromPost(array $post): self
    {
        $text = static fn(string $key): string => is_string($post[$key] ?? null) ? $post[$key] : '';

        return new self(trim($text('name')), trim($text('email')), $text('password'), $text('password_repeat'));
    }

    /**
     * @return array<string, string> Feld zu Schlüssel der Fehlermeldung
     */
    public function validate(): array
    {
        $fehler = [];
        if ($this->name === '' || mb_strlen($this->name) > 120) {
            $fehler['name'] = 'install.fehler.feld.name';
        }
        if (mb_strlen($this->email) > 190 || filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $fehler['email'] = 'install.fehler.feld.email';
        }
        if (mb_strlen($this->password) < self::MIN_PASSWORD_LENGTH) {
            $fehler['password'] = 'install.fehler.feld.passwort_kurz';
        } elseif (mb_strlen($this->password) > 200) {
            $fehler['password'] = 'install.fehler.feld.passwort_lang';
        }
        if (!isset($fehler['password']) && !hash_equals($this->password, $this->passwordRepeat)) {
            $fehler['password_repeat'] = 'install.fehler.feld.passwort_gleich';
        }

        return $fehler;
    }
}
