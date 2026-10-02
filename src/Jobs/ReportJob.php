<?php

declare(strict_types=1);

namespace Pegelstand\Jobs;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Mail\Mailer;
use Pegelstand\Reports\ReportBuilder;
use Pegelstand\Reports\ReportSchedule;
use Pegelstand\Reports\ReportSubscriptions;
use Pegelstand\Settings\SettingsStore;
use Pegelstand\Settings\SiteRepository;
use Throwable;

/** Verschickt fällige E-Mail-Berichte. Fehlgeschlagene Sendungen werden beim nächsten Lauf (stündlich) erneut versucht. */
final class ReportJob implements Job
{
    public function __construct(
        private readonly ReportSubscriptions $subscriptions,
        private readonly SiteRepository $sites,
        private readonly ReportBuilder $builder,
        private readonly Mailer $mailer,
        private readonly SettingsStore $settings,
    ) {}

    public function name(): string
    {
        return 'reports';
    }

    public function interval(): int
    {
        return 3600;
    }

    public function timeout(): int
    {
        return 900;
    }

    public function run(DateTimeImmutable $now): string
    {
        if (!$this->mailer->isConfigured()) {
            return 'E-Mail-Versand nicht eingerichtet';
        }
        $websites = [];
        foreach ($this->sites->all() as $s) {
            $websites[$s['id']] = $s;
        }
        $gesendet = 0;
        $fehler = 0;
        foreach ($this->subscriptions->all() as $abo) {
            $site = $websites[$abo['site_id']] ?? null;
            if ($site === null) {
                continue;
            }
            $zone = new DateTimeZone($site['timezone']);
            $termin = ReportSchedule::latest($abo['frequency'], $now, $zone);
            $zuletzt = $abo['last_sent_at'] === null ? null : new DateTimeImmutable($abo['last_sent_at'], new DateTimeZone('UTC'));
            if ($zuletzt !== null && $zuletzt >= $termin['faellig']) {
                continue;
            }
            $basis = $this->settings->get('mail.base_url');
            $nachricht = $this->builder->build($site, $abo['frequency'], $termin['von'], $termin['bis'], $abo['email'], $basis, $now);
            try {
                if ($nachricht !== null) {
                    $this->mailer->send($nachricht);
                    ++$gesendet;
                }
                $this->subscriptions->markSent($abo['id'], $now);
            } catch (Throwable) {
                ++$fehler;
            }
        }

        return $gesendet . ' Berichte gesendet' . ($fehler > 0 ? ', ' . $fehler . ' fehlgeschlagen' : '');
    }
}
