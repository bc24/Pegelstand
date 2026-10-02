<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

/** @var Pegelstand\Core\View $this */
/** @var string $titel */
/** @var string $bootstrap JSON mit Sites und Zeitangaben */
/** @var string $benutzer */
/** @var string $csrf */
/** @var string $version */
/** @var bool $oeffentlich Schreibgeschützte Ansicht ohne Anmeldung */
/** @var string $apiBasis Pfad der Datenschnittstelle */
$kontoLabel = $oeffentlich ? $this->translate('layout.darstellung') : $this->translate('login.konto', ['name' => $benutzer]);
?><!DOCTYPE html>
<html lang="de" data-ps-assets="<?= $this->assetsBasis() ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" href="data:,">
  <title><?= $this->e($titel) ?> – <?= $this->t('app.name') ?></title>
  <script src="<?= $this->asset('js/theme-init.js') ?>"></script>
  <link rel="preload" href="<?= $this->asset('fonts/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= $this->asset('css/pegelstand.css') ?>">
  <link rel="stylesheet" href="<?= $this->asset('css/dashboard.css') ?>">
</head>
<body>
  <div class="db-fortschritt" aria-hidden="true"></div>
  <a class="ps-skip" href="#inhalt">Zum Inhalt springen</a>

  <header class="db-header">
    <a class="ps-wordmark" href="<?= $this->e($this->url('/')) ?>"><?= $this->t('app.name') ?></a>
    <div class="ps-menu-wrap" data-ps-menu id="site-wahl">
      <button type="button" class="ps-btn ps-btn--secondary db-sitewahl" aria-haspopup="menu" aria-expanded="false" aria-controls="menue-site">
        <svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-globe"/></svg><span id="site-name">beispiel.de</span><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-chevron-down"/></svg>
      </button>
      <div class="ps-menu" id="menue-site" role="menu" aria-label="Site wechseln" hidden></div>
    </div>
    <span class="db-header__abstand"></span>
    <button type="button" class="ps-btn ps-btn--secondary db-suche" data-palette-open aria-keyshortcuts="Control+K">
      <svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-search"/></svg><span class="db-nur-gross">Suchen oder springen</span><span class="ps-visually-hidden db-nur-klein">Befehlspalette</span><kbd class="db-nur-gross">Strg K</kbd>
    </button>
    <div class="ps-menu-wrap" data-ps-menu>
      <button type="button" class="ps-btn ps-btn--ghost ps-btn--icon" aria-haspopup="menu" aria-expanded="false" aria-controls="menue-darstellung" aria-label="<?= $this->e($kontoLabel) ?>" data-ps-tooltip="<?= $this->e($kontoLabel) ?>"><?= $this->icon($oeffentlich ? 'sun' : 'user') ?></button>
      <div class="ps-menu ps-menu--end" id="menue-darstellung" role="menu" aria-label="<?= $this->e($kontoLabel) ?>" hidden>
        <button type="button" class="ps-menu__item" role="menuitemradio" aria-checked="false" data-ps-theme="light" data-ps-gruppe="theme"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-sun"/></svg> Hell <span class="ps-menu__check"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-check"/></svg></span></button>
        <button type="button" class="ps-menu__item" role="menuitemradio" aria-checked="false" data-ps-theme="dark" data-ps-gruppe="theme"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-moon"/></svg> Dunkel <span class="ps-menu__check"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-check"/></svg></span></button>
        <button type="button" class="ps-menu__item" role="menuitemradio" aria-checked="true" data-ps-theme="system" data-ps-gruppe="theme"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-monitor"/></svg> System <span class="ps-menu__check"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-check"/></svg></span></button>
        <?php if (!$oeffentlich) : ?>
        <div class="ps-menu__separator" role="separator"></div>
        <a class="ps-menu__item" role="menuitem" href="<?= $this->e($this->url('/einstellungen')) ?>"><?= $this->icon('settings', 's') ?> <?= $this->t('login.einstellungen') ?></a>
        <form method="post" action="<?= $this->e($this->url('/logout')) ?>" class="db-menue-form" role="none">
          <input type="hidden" name="_csrf" value="<?= $this->e($csrf) ?>">
          <button type="submit" class="ps-menu__item" role="menuitem"><?= $this->icon('log-out', 's') ?> <?= $this->t('login.abmelden') ?></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <button type="button" class="ps-btn ps-btn--ghost ps-btn--icon db-nur-gross" data-ps-modal-open="kuerzel-dialog" aria-label="Tastaturkürzel" data-ps-tooltip="Tastaturkürzel (?)"><svg class="ps-icon" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-keyboard"/></svg></button>
  </header>

  <?php if ($this->isDemo()) : ?>
  <p class="se-demo" role="note"><?= $this->t('layout.demo') ?></p>
  <?php endif; ?>
  <main class="db-main" id="inhalt" data-quelle="live" data-bootstrap="<?= $this->e($bootstrap) ?>" data-basis="<?= $this->e($this->url('/')) ?>" data-api="<?= $this->e($apiBasis) ?>">
    <div class="db-toolbar">
      <div class="db-toolbar__titel">
        <h1 id="titel" tabindex="-1">beispiel.de</h1>
        <span class="db-live" id="live" data-ps-tooltip="Besucher mit mindestens einem Aufruf in den letzten 5 Minuten."><span class="db-live__punkt" aria-hidden="true"></span><span id="live-text">&nbsp;</span></span>
      </div>
      <div class="db-toolbar__werkzeuge">
        <div class="ps-menu-wrap" data-ps-menu id="zeitraum-wahl">
          <button type="button" class="ps-btn ps-btn--secondary" aria-haspopup="menu" aria-expanded="false" aria-controls="menue-zeitraum">
            <svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-calendar"/></svg><span class="ps-visually-hidden">Zeitraum:</span><span id="zeitraum-name">30 Tage</span><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-chevron-down"/></svg>
          </button>
          <div class="ps-menu ps-menu--end" id="menue-zeitraum" role="menu" aria-label="Zeitraum" hidden></div>
        </div>
        <?php if (!$oeffentlich) : ?>
        <div class="ps-menu-wrap" data-ps-menu>
          <button type="button" class="ps-btn ps-btn--secondary" aria-haspopup="menu" aria-expanded="false" aria-controls="menue-export"><?= $this->icon('download', 's') ?><?= $this->t('export.titel') ?><?= $this->icon('chevron-down', 's') ?></button>
          <div class="ps-menu ps-menu--end" id="menue-export" role="menu" aria-label="<?= $this->t('export.label') ?>" hidden>
            <?php foreach (['zeitverlauf', 'seiten', 'einstiegsseiten', 'ausstiegsseiten', 'referrer', 'suchmaschinen', 'soziale-netzwerke', 'kampagnen', 'laender', 'geraete', 'browser', 'betriebssysteme', 'ziele', 'ereignisse'] as $tabelle) : ?>
            <a class="ps-menu__item" role="menuitem" href="<?= $this->e($this->url('/export/' . $tabelle)) ?>" data-export="<?= $this->e($tabelle) ?>"><?= $this->t('export.' . str_replace('-', '_', $tabelle)) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <label class="ps-switch"><input type="checkbox" role="switch" id="vergleich" checked><span>Mit Vorperiode vergleichen</span></label>
      </div>
    </div>
    <p class="db-zeitinfo" id="zeitinfo">&nbsp;</p>
    <div class="db-filter" id="filter" role="group" aria-label="Aktive Filter" hidden></div>
    <div class="ps-visually-hidden" id="status" role="status" aria-live="polite"></div>

    <div id="ansicht-normal" aria-busy="true">
      <section class="db-kpis" id="kpis" aria-label="Kennzahlen"><div class="ps-kpi" aria-hidden="true"><span class="ps-skeleton db-skel-label"></span><span class="ps-skeleton db-skel-wert"></span><span class="ps-skeleton db-skel-delta"></span></div><div class="ps-kpi" aria-hidden="true"><span class="ps-skeleton db-skel-label"></span><span class="ps-skeleton db-skel-wert"></span><span class="ps-skeleton db-skel-delta"></span></div><div class="ps-kpi" aria-hidden="true"><span class="ps-skeleton db-skel-label"></span><span class="ps-skeleton db-skel-wert"></span><span class="ps-skeleton db-skel-delta"></span></div><div class="ps-kpi" aria-hidden="true"><span class="ps-skeleton db-skel-label"></span><span class="ps-skeleton db-skel-wert"></span><span class="ps-skeleton db-skel-delta"></span></div><div class="ps-kpi" aria-hidden="true"><span class="ps-skeleton db-skel-label"></span><span class="ps-skeleton db-skel-wert"></span><span class="ps-skeleton db-skel-delta"></span></div></section>

      <section class="ps-card db-diagramm" aria-labelledby="diagramm-titel">
        <div class="ps-card__header">
          <h2 class="ps-card__title" id="diagramm-titel">Besucher im Zeitverlauf</h2>
          <div class="db-diagramm__werkzeuge">
            <ul class="db-legende">
              <li><i class="db-legende__linie" aria-hidden="true"></i>Aktueller Zeitraum</li>
              <li id="legende-vorher"><i class="db-legende__linie db-legende__linie--gestrichelt" aria-hidden="true"></i>Vorperiode</li>
            </ul>
            <button type="button" class="ps-btn ps-btn--ghost ps-btn--s" id="tabellen-knopf" aria-pressed="false" aria-controls="diagramm-tabelle"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-menu"/></svg><span id="tabellen-knopf-text">Als Tabelle</span></button>
          </div>
        </div>
        <div class="ps-card__body">
          <div class="db-diagramm__flaeche" id="diagramm" tabindex="0" role="group" aria-label="Liniendiagramm"><div class="ps-skeleton ps-skeleton--block" role="status"><span class="ps-visually-hidden">Diagramm wird geladen</span></div></div>
          <div class="db-diagramm__tabelle" id="diagramm-tabelle" hidden tabindex="0" role="region" aria-label="Diagramm als Tabelle"></div>
          <div class="ps-visually-hidden" id="diagramm-ansage" aria-live="polite"></div>
        </div>
      </section>

      <div class="db-grid">
        <section class="ps-card db-karte" aria-labelledby="seiten-titel"><div class="ps-card__header"><h2 class="ps-card__title" id="seiten-titel">Seiten</h2></div><div class="db-karte__tabs"><div class="ps-tabs" role="tablist" aria-label="Seiten"><button type="button" class="ps-tab" role="tab" id="tab-seiten-top" aria-selected="true" aria-controls="panel-seiten-top">Top-Seiten</button><button type="button" class="ps-tab" role="tab" id="tab-seiten-einstieg" aria-selected="false" aria-controls="panel-seiten-einstieg" tabindex="-1">Einstiegsseiten</button><button type="button" class="ps-tab" role="tab" id="tab-seiten-ausstieg" aria-selected="false" aria-controls="panel-seiten-ausstieg" tabindex="-1">Ausstiegsseiten</button></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-seiten-top" aria-labelledby="tab-seiten-top" data-bereich="seiten.top"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-seiten-einstieg" aria-labelledby="tab-seiten-einstieg" hidden data-bereich="seiten.einstieg"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-seiten-ausstieg" aria-labelledby="tab-seiten-ausstieg" hidden data-bereich="seiten.ausstieg"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div></section>
        <section class="ps-card db-karte" aria-labelledby="quellen-titel"><div class="ps-card__header"><h2 class="ps-card__title" id="quellen-titel">Quellen</h2></div><div class="db-karte__tabs"><div class="ps-tabs" role="tablist" aria-label="Quellen"><button type="button" class="ps-tab" role="tab" id="tab-quellen-referrer" aria-selected="true" aria-controls="panel-quellen-referrer">Referrer</button><button type="button" class="ps-tab" role="tab" id="tab-quellen-suche" aria-selected="false" aria-controls="panel-quellen-suche" tabindex="-1">Suchmaschinen</button><button type="button" class="ps-tab" role="tab" id="tab-quellen-sozial" aria-selected="false" aria-controls="panel-quellen-sozial" tabindex="-1">Soziale Netzwerke</button><button type="button" class="ps-tab" role="tab" id="tab-quellen-kampagnen" aria-selected="false" aria-controls="panel-quellen-kampagnen" tabindex="-1">Kampagnen</button></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-quellen-referrer" aria-labelledby="tab-quellen-referrer" data-bereich="quellen.referrer"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-quellen-suche" aria-labelledby="tab-quellen-suche" hidden data-bereich="quellen.suche"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-quellen-sozial" aria-labelledby="tab-quellen-sozial" hidden data-bereich="quellen.sozial"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-quellen-kampagnen" aria-labelledby="tab-quellen-kampagnen" hidden data-bereich="quellen.kampagnen"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div></section>
        <section class="ps-card db-karte" aria-labelledby="laender-titel"><div class="ps-card__header"><h2 class="ps-card__title" id="laender-titel">Länder</h2></div><div class="db-karte__koerper" data-bereich="laender"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div></section>
        <section class="ps-card db-karte" aria-labelledby="geraete-titel"><div class="ps-card__header"><h2 class="ps-card__title" id="geraete-titel">Geräte</h2></div><div class="db-karte__tabs"><div class="ps-tabs" role="tablist" aria-label="Geräte"><button type="button" class="ps-tab" role="tab" id="tab-geraete-geraete" aria-selected="true" aria-controls="panel-geraete-geraete">Geräte</button><button type="button" class="ps-tab" role="tab" id="tab-geraete-browser" aria-selected="false" aria-controls="panel-geraete-browser" tabindex="-1">Browser</button><button type="button" class="ps-tab" role="tab" id="tab-geraete-os" aria-selected="false" aria-controls="panel-geraete-os" tabindex="-1">Betriebssysteme</button></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-geraete-geraete" aria-labelledby="tab-geraete-geraete" data-bereich="geraete.geraete"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-geraete-browser" aria-labelledby="tab-geraete-browser" hidden data-bereich="geraete.browser"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-geraete-os" aria-labelledby="tab-geraete-os" hidden data-bereich="geraete.os"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div></section>
        <section class="ps-card db-karte db-karte--breit" aria-labelledby="zielereignisse-titel"><div class="ps-card__header"><h2 class="ps-card__title" id="zielereignisse-titel">Ziele und Ereignisse</h2></div><div class="db-karte__tabs"><div class="ps-tabs" role="tablist" aria-label="Ziele und Ereignisse"><button type="button" class="ps-tab" role="tab" id="tab-zielereignisse-ziele" aria-selected="true" aria-controls="panel-zielereignisse-ziele">Ziele</button><button type="button" class="ps-tab" role="tab" id="tab-zielereignisse-ereignisse" aria-selected="false" aria-controls="panel-zielereignisse-ereignisse" tabindex="-1">Ereignisse</button></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-zielereignisse-ziele" aria-labelledby="tab-zielereignisse-ziele" data-bereich="ziele"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div><div class="db-karte__koerper ps-tabpanel" role="tabpanel" id="panel-zielereignisse-ereignisse" aria-labelledby="tab-zielereignisse-ereignisse" hidden data-bereich="ereignisse"><div class="db-skel" role="status"><span class="ps-visually-hidden">Wird geladen</span><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div><div class="db-skel-zeile" aria-hidden="true"><span class="ps-skeleton"></span><span class="ps-skeleton"></span></div></div></div></section>
      </div>
    </div>
    <div id="ansicht-sonder" hidden></div>
  </main>

  <footer class="db-fusszeile">© 2026 <a href="https://frank-panzer.de">Frank Panzer</a> · <?= $this->t('layout.entwickelt_von') ?> <a href="https://panzerit.de">Panzer IT</a> · <?= $this->e($version) ?></footer>

  <dialog class="ps-modal db-palette" id="palette" aria-label="Befehlspalette">
    <div class="db-palette__eingabe">
      <svg class="ps-icon" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-search"/></svg>
      <input type="text" role="combobox" aria-expanded="false" aria-controls="palette-liste" aria-autocomplete="list" aria-label="Befehl suchen" placeholder="Befehl suchen …" autocomplete="off" spellcheck="false">
    </div>
    <div class="db-palette__liste" id="palette-liste" role="listbox" aria-label="Befehle"></div>
    <p class="db-palette__leer" hidden>Keine passenden Befehle. Versuche einen anderen Suchbegriff.</p>
  </dialog>

  <dialog class="ps-modal" id="kuerzel-dialog" aria-labelledby="kuerzel-titel">
    <div class="ps-modal__header"><h2 id="kuerzel-titel">Tastaturkürzel</h2><button type="button" class="ps-btn ps-btn--ghost ps-btn--s ps-btn--icon" data-ps-modal-close aria-label="Schließen"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-x"/></svg></button></div>
    <div class="ps-modal__body">
      <dl class="db-kuerzel">
        <dt><kbd>Strg</kbd><kbd>K</kbd></dt><dd>Befehlspalette öffnen</dd>
        <dt><kbd>?</kbd></dt><dd>Diese Übersicht anzeigen</dd>
        <dt><kbd>1</kbd> bis <kbd>7</kbd></dt><dd>Zeitraum wählen (Heute bis Dieses Jahr)</dd>
        <dt><kbd>V</kbd></dt><dd>Vergleich mit Vorperiode umschalten</dd>
        <dt><kbd>F</kbd></dt><dd>Alle Filter entfernen</dd>
        <dt><kbd>Pfeiltasten</kbd></dt><dd>Im Diagramm durch die Werte gehen</dd>
        <dt><kbd>Esc</kbd></dt><dd>Fenster oder Menü schließen</dd>
      </dl>
    </div>
    <div class="ps-modal__footer"><button type="button" class="ps-btn ps-btn--primary" data-ps-modal-close>Schließen</button></div>
  </dialog>

  <dialog class="ps-modal" id="bereich-dialog" aria-labelledby="bereich-titel">
    <form id="bereich-formular" novalidate>
      <div class="ps-modal__header"><h2 id="bereich-titel">Zeitraum wählen</h2><button type="button" class="ps-btn ps-btn--ghost ps-btn--s ps-btn--icon" data-ps-modal-close aria-label="Schließen"><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-x"/></svg></button></div>
      <div class="ps-modal__body">
        <div class="db-bereichsfelder">
          <div class="ps-field"><label class="ps-label" for="bereich-von">Von</label><input class="ps-input" type="date" id="bereich-von"></div>
          <div class="ps-field"><label class="ps-label" for="bereich-bis">Bis</label><input class="ps-input" type="date" id="bereich-bis"></div>
        </div>
        <p class="ps-error" id="bereich-fehler" role="alert" hidden><svg class="ps-icon ps-icon--s" aria-hidden="true" focusable="false"><use href="<?= $this->asset('icons.svg') ?>#ps-icon-circle-alert"/></svg><span id="bereich-fehler-text"></span></p>
      </div>
      <div class="ps-modal__footer"><button type="button" class="ps-btn ps-btn--secondary" data-ps-modal-close>Abbrechen</button><button type="submit" class="ps-btn ps-btn--primary">Übernehmen</button></div>
    </form>
  </dialog>

  <script type="module" src="<?= $this->asset('js/dashboard.js') ?>"></script>
</body>
</html>
