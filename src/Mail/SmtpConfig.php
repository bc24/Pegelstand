<?php

declare(strict_types=1);

namespace Pegelstand\Mail;

/** Zugangsdaten zum Postausgangsserver. `security`: "tls" (SSL von Beginn an, meist Port 465), "starttls" (meist 587) oder "none". */
final class SmtpConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $security,
        public readonly string $user,
        public readonly string $password,
        public readonly string $fromAddress,
        public readonly string $fromName,
        public readonly int $timeout = 15,
    ) {}
}
