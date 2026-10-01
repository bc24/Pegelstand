<?php

declare(strict_types=1);

namespace Pegelstand\Mail;

/** Eine Nachricht aus reinem Text, optional mit HTML-Fassung. */
final class Message
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $text,
        public readonly ?string $html = null,
    ) {}
}
