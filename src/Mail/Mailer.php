<?php

declare(strict_types=1);

namespace Pegelstand\Mail;

use Pegelstand\Settings\SettingsStore;

/** Verschickt Nachrichten mit den gespeicherten E-Mail-Einstellungen. */
final class Mailer
{
    public function __construct(private readonly SettingsStore $settings) {}

    public function isConfigured(): bool
    {
        return $this->config() !== null;
    }

    public function config(): ?SmtpConfig
    {
        $host = $this->settings->get('mail.host');
        $von = $this->settings->get('mail.from_address');
        if ($host === '' || $von === '') {
            return null;
        }

        return new SmtpConfig(
            $host,
            (int) $this->settings->get('mail.port', '587'),
            $this->settings->get('mail.security', 'starttls'),
            $this->settings->get('mail.user'),
            $this->settings->getSecret('mail.password'),
            $von,
            $this->settings->get('mail.from_name', 'Pegelstand'),
        );
    }

    /**
     * @throws MailException
     */
    public function send(Message $message): void
    {
        $config = $this->config();
        if ($config === null) {
            throw new MailException('Der E-Mail-Versand ist noch nicht eingerichtet.');
        }
        (new SmtpClient($config))->send($message);
    }
}
