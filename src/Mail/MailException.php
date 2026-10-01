<?php

declare(strict_types=1);

namespace Pegelstand\Mail;

use RuntimeException;

/** Versand fehlgeschlagen. Die Meldung ist für Administratoren gedacht und enthält nie Zugangsdaten. */
final class MailException extends RuntimeException {}
