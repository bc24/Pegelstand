<?php
declare(strict_types=1);

/**
 * Definition aller im Admin bearbeitbaren Inhaltstypen.
 * Bilinguale Felder ("bilingual" => true) werden als <name>_de / <name>_en gespeichert.
 * Feldtypen: text, textarea, richtext, richtext_mini, url, email, number, select, bool, icon, image, datetime, slug, readonly
 */

$lvl = [1 => 'Befriedigend', 2 => 'Gut', 3 => 'Sehr gut'];

return [

    'sections' => [
        'title' => 'Bereiche & Reihenfolge', 'singular' => 'Bereich', 'icon' => 'layout-dashboard', 'group' => 'content',
        'table' => 'sections', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'can_add' => false, 'can_delete' => false,
        'help' => 'Hier legst du fest, welche Bereiche auf der Startseite erscheinen, in welcher Reihenfolge (per Drag & Drop) und wie ihre Überschriften und Menüpunkte lauten.',
        'columns' => ['label_de' => 'Bereich', 'skey' => 'Schlüssel', 'in_nav' => 'Im Menü'],
        'fields' => [
            ['name' => 'skey', 'label' => 'Schlüssel', 'type' => 'readonly'],
            ['name' => 'anchor', 'label' => 'Anker (URL-Teil nach #)', 'type' => 'text', 'required' => true, 'help' => 'Nur Kleinbuchstaben, Zahlen und Bindestriche.'],
            ['name' => 'label', 'label' => 'Kleine Überschrift (Eyebrow)', 'type' => 'text', 'bilingual' => true],
            ['name' => 'title', 'label' => 'Große Überschrift', 'type' => 'text', 'bilingual' => true],
            ['name' => 'intro', 'label' => 'Einleitung', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3],
            ['name' => 'nav', 'label' => 'Menü-Beschriftung', 'type' => 'text', 'bilingual' => true],
            ['name' => 'in_nav', 'label' => 'Im Hauptmenü anzeigen', 'type' => 'bool'],
            ['name' => 'visible', 'label' => 'Auf der Startseite anzeigen', 'type' => 'bool'],
        ],
    ],

    'stats' => [
        'title' => 'Zahlen', 'singular' => 'Zahl', 'icon' => 'chart-column', 'group' => 'content',
        'table' => 'stats', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'help' => 'Die Kennzahlen unter dem Startbereich. Sie zählen beim Scrollen hoch.',
        'columns' => ['label_de' => 'Beschriftung', 'count_to' => 'Wert', 'suffix_de' => 'Zusatz'],
        'fields' => [
            ['name' => 'count_to', 'label' => 'Zielwert', 'type' => 'number', 'step' => '0.1', 'required' => true],
            ['name' => 'auto_years', 'label' => 'Wert automatisch als „Jahre seit Startjahr“ berechnen', 'type' => 'bool', 'help' => 'Startjahr: Einstellungen → Allgemein → „Online seit“.'],
            ['name' => 'decimals', 'label' => 'Nachkommastellen', 'type' => 'number', 'min' => 0, 'max' => 3, 'default' => 0],
            ['name' => 'prefix', 'label' => 'Präfix', 'type' => 'text', 'help' => 'z. B. „~“'],
            ['name' => 'suffix', 'label' => 'Suffix', 'type' => 'text', 'bilingual' => true, 'help' => 'z. B. „+“ oder „ Mio.“'],
            ['name' => 'label', 'label' => 'Beschriftung', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'about_badges' => [
        'title' => 'Über mich: Badges', 'singular' => 'Badge', 'icon' => 'badge-check', 'group' => 'content',
        'table' => 'about_badges', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'help' => 'Die kleinen Kurz-Infos unter dem Über-mich-Text. Texte und Foto bearbeitest du unter Einstellungen → Über mich.',
        'columns' => ['icon' => 'Icon', 'text_de' => 'Text'],
        'fields' => [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'text', 'label' => 'Text', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'interests' => [
        'title' => 'Interessen', 'singular' => 'Interesse', 'icon' => 'heart', 'group' => 'content',
        'table' => 'interests', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['icon' => 'Icon', 'title_de' => 'Titel', 'text_de' => 'Text'],
        'fields' => [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'bilingual' => true, 'rows' => 4],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'facts' => [
        'title' => 'Fun Facts', 'singular' => 'Fun Fact', 'icon' => 'party-popper', 'group' => 'content',
        'table' => 'facts', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['icon' => 'Icon', 'title_de' => 'Titel', 'text_de' => 'Text'],
        'fields' => [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'timeline' => [
        'title' => 'Zeitstrahl', 'singular' => 'Meilenstein', 'icon' => 'milestone', 'group' => 'content',
        'table' => 'timeline', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['year' => 'Jahr', 'title_de' => 'Titel', 'text_de' => 'Text'],
        'fields' => [
            ['name' => 'year', 'label' => 'Jahr', 'type' => 'text', 'required' => true],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3],
            ['name' => 'accent', 'label' => 'Hervorheben (Akzentfarbe)', 'type' => 'bool'],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'projects' => [
        'title' => 'Projekte', 'singular' => 'Projekt', 'icon' => 'folders', 'group' => 'content',
        'table' => 'projects', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible', 'toggle2' => 'featured',
        'help' => 'Projekte mit „Auf Startseite“ erscheinen im Projekt-Raster der Startseite; alle sichtbaren Projekte stehen auf der Seite /projekte/.',
        'columns' => ['image' => 'Bild', 'title' => 'Projekt', 'category' => 'Kategorie', 'status' => 'Status'],
        'search' => ['title', 'tagline_de', 'url'],
        'fields' => [
            ['name' => 'title', 'label' => 'Titel / Domain', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'URL-Name', 'type' => 'slug', 'from' => 'title', 'help' => 'Adresse der Detailseite: /projekt/<name>/'],
            ['name' => 'url', 'label' => 'Link zum Projekt', 'type' => 'url'],
            ['name' => 'image', 'label' => 'Bild / Screenshot', 'type' => 'image', 'help' => 'Ohne Bild wird automatisch ein Cover mit Icon erzeugt.'],
            ['name' => 'category', 'label' => 'Kategorie', 'type' => 'select', 'options' => ['web' => 'Websites & Plattformen', 'shop' => 'Shops & Deals', 'game' => 'Spiele', 'tool' => 'Tools & Apps'], 'default' => 'web'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['live' => 'Online', 'beta' => 'Beta', 'archiv' => 'Archiv'], 'default' => 'live'],
            ['name' => 'badge_icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'badge', 'label' => 'Badge-Text', 'type' => 'text', 'bilingual' => true],
            ['name' => 'tagline', 'label' => 'Kurzbeschreibung', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3, 'required_de' => true],
            ['name' => 'meta', 'label' => 'Zusatzinfo (z. B. Kennzahl)', 'type' => 'text', 'bilingual' => true],
            ['name' => 'description', 'label' => 'Ausführliche Beschreibung (Detailseite)', 'type' => 'richtext_mini', 'bilingual' => true],
            ['name' => 'tech', 'label' => 'Technik (Komma-getrennt)', 'type' => 'text', 'help' => 'z. B. PHP, MySQL, JavaScript'],
            ['name' => 'year_from', 'label' => 'Seit (Jahr)', 'type' => 'text'],
            ['name' => 'featured', 'label' => 'Auf der Startseite zeigen', 'type' => 'bool'],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
        'before_save' => static function (array $data, ?array $old): array {
            if ($old === null) {
                $data['created_at'] = now();
            }
            return $data;
        },
    ],

    'former_sites' => [
        'title' => 'Ehemalige Webseiten', 'singular' => 'Webseite', 'icon' => 'history', 'group' => 'content',
        'table' => 'former_sites', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['year' => 'Jahr', 'domain' => 'Domain', 'text_de' => 'Beschreibung'],
        'fields' => [
            ['name' => 'year', 'label' => 'Jahr', 'type' => 'text'],
            ['name' => 'domain', 'label' => 'Domain', 'type' => 'text', 'required' => true],
            ['name' => 'text', 'label' => 'Beschreibung', 'type' => 'text', 'bilingual' => true],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'maker_projects' => [
        'title' => 'Maker-Projekte', 'singular' => 'Maker-Projekt', 'icon' => 'hammer', 'group' => 'content',
        'table' => 'maker_projects', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['icon' => 'Icon', 'title_de' => 'Titel', 'status' => 'Status'],
        'fields' => [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['done' => 'Fertig', 'planned' => 'In Planung'], 'default' => 'done'],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'shop_items' => [
        'title' => 'Shop-Kategorien', 'singular' => 'Kategorie', 'icon' => 'shopping-bag', 'group' => 'content',
        'table' => 'shop_items', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'help' => 'Link und Button-Texte des Shops: Einstellungen → Shop.',
        'columns' => ['icon' => 'Icon', 'title_de' => 'Titel', 'text_de' => 'Text'],
        'fields' => [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'skill_groups' => [
        'title' => 'Skill-Gruppen', 'singular' => 'Gruppe', 'icon' => 'layers', 'group' => 'content',
        'table' => 'skill_groups', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['icon' => 'Icon', 'title_de' => 'Titel'],
        'links' => [['skills', 'Skills verwalten', 'code']],
        'fields' => [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'skills' => [
        'title' => 'Skills', 'singular' => 'Skill', 'icon' => 'code', 'group' => 'content',
        'table' => 'skills', 'order' => 'group_id ASC, sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'filter' => 'group_id',
        'columns' => ['icon' => 'Icon', 'name' => 'Skill', 'group_id' => 'Gruppe', 'level' => 'Level'],
        'search' => ['name'],
        'fields' => [
            ['name' => 'group_id', 'label' => 'Gruppe', 'type' => 'select', 'options_from' => ['skill_groups', 'id', 'title_de'], 'required' => true],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'level', 'label' => 'Level', 'type' => 'select', 'options' => $lvl, 'default' => 2],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'cv_entries' => [
        'title' => 'Lebenslauf', 'singular' => 'Eintrag', 'icon' => 'graduation-cap', 'group' => 'content',
        'table' => 'cv_entries', 'order' => "kind ASC, sort ASC, id ASC", 'sortable' => true, 'toggle' => 'visible',
        'filter' => 'kind',
        'columns' => ['kind' => 'Spalte', 'period' => 'Zeitraum', 'title_de' => 'Titel'],
        'fields' => [
            ['name' => 'kind', 'label' => 'Spalte', 'type' => 'select', 'options' => ['education' => 'Ausbildung & Qualifikationen', 'experience' => 'Berufserfahrung & Projekte'], 'default' => 'experience'],
            ['name' => 'period', 'label' => 'Zeitraum', 'type' => 'text', 'help' => 'z. B. 2019–heute'],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'text', 'label' => 'Beschreibung', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'tools' => [
        'title' => 'Setup / Tools', 'singular' => 'Tool', 'icon' => 'wrench', 'group' => 'content',
        'table' => 'tools', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['icon' => 'Icon', 'name' => 'Name', 'url' => 'Link'],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'url', 'label' => 'Link (optional)', 'type' => 'url'],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'music_genres' => [
        'title' => 'Musik: Genres', 'singular' => 'Genre', 'icon' => 'audio-lines', 'group' => 'content',
        'table' => 'music_genres', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'links' => [['tracks', 'Tracks verwalten', 'music']],
        'columns' => ['icon' => 'Icon', 'name' => 'Genre'],
        'fields' => [
            ['name' => 'name', 'label' => 'Genre', 'type' => 'text', 'required' => true],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'tracks' => [
        'title' => 'Musik: Tracks', 'singular' => 'Track', 'icon' => 'music', 'group' => 'content',
        'table' => 'tracks', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'help' => 'Die Songs erscheinen im Musik-Bereich. „Von TikTok aktualisieren“ lädt die neuesten Videos des Profils (Einstellungen → Musik) samt Cover. Eigene Links (TikTok-Video-Link einfügen) werden automatisch mit Titel und Cover ergänzt.',
        'buttons' => [['sync_tiktok', 'Von TikTok aktualisieren', 'refresh-cw']],
        'handlers' => ['sync_tiktok' => static function (): void {
            $user = trim(setting('tiktok_user', 'dj.frankus'));
            $res = TikTok::sync($user, max(1, (int)setting('tiktok_limit', '10')));
            Auth::log('sync', 'tracks', '', "@$user: +{$res['added']} ~{$res['updated']} -{$res['removed']}");
            if ($res['error'] !== '') {
                Admin::flash('err', 'TikTok-Abgleich fehlgeschlagen: ' . $res['error']);
            } else {
                Admin::flash('ok', "TikTok-Abgleich fertig: {$res['added']} neu, {$res['updated']} aktualisiert, {$res['removed']} entfernt.");
            }
        }],
        'columns' => ['image' => 'Cover', 'title' => 'Titel', 'platform' => 'Plattform', 'plays' => 'Aufrufe', 'published_at' => 'Datum'],
        'fields' => [
            ['name' => 'url', 'label' => 'Link zum Song / TikTok-Video', 'type' => 'url', 'help' => 'Bei TikTok-Video-Links werden Titel, Cover und Player automatisch ergänzt.'],
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'help' => 'Leer lassen, um ihn bei TikTok-Links automatisch zu übernehmen.'],
            ['name' => 'caption', 'label' => 'Beschreibung', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'genre', 'label' => 'Genre / Tags', 'type' => 'text'],
            ['name' => 'platform', 'label' => 'Plattform', 'type' => 'text', 'help' => 'z. B. TikTok, YouTube, SoundCloud, Spotify'],
            ['name' => 'image', 'label' => 'Cover', 'type' => 'image'],
            ['name' => 'video_id', 'label' => 'TikTok-Video-ID (automatisch)', 'type' => 'text', 'max' => 32],
            ['name' => 'published_at', 'label' => 'Veröffentlicht', 'type' => 'datetime'],
            ['name' => 'plays', 'label' => 'Aufrufe', 'type' => 'number', 'min' => 0, 'default' => 0],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
        'before_save' => static function (array $data, ?array $old): array {
            $url = (string)($data['url'] ?? '');
            $vid = TikTok::videoId($url);
            if ($vid !== '') {
                $data['video_id'] = $vid;
                $data['platform'] = ($data['platform'] ?? '') !== '' ? $data['platform'] : 'TikTok';
                if (empty($data['published_at'])) {
                    $data['published_at'] = TikTok::dateFromId($vid);
                }
                if (($data['title'] ?? '') === '' || ($data['image'] ?? '') === '') {
                    $oe = TikTok::oembed($url);
                    if ($oe) {
                        if (($data['title'] ?? '') === '') {
                            $data['title'] = TikTok::titleFromCaption($oe['title']);
                            $data['caption'] = ($data['caption'] ?? '') !== '' ? $data['caption'] : $oe['title'];
                        }
                        if (($data['image'] ?? '') === '') {
                            $data['image'] = TikTok::saveCover($vid, $oe['thumbnail_url']);
                        }
                    }
                }
            } else {
                $data['video_id'] = '';
            }
            if (($data['title'] ?? '') === '') {
                $data['title'] = 'Song';
            }
            if ($old === null) {
                $data['source'] = 'manual';
            }
            return $data;
        },
    ],

    'faq' => [
        'title' => 'FAQ', 'singular' => 'Frage', 'icon' => 'circle-help', 'group' => 'content',
        'table' => 'faq', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'columns' => ['q_de' => 'Frage', 'a_de' => 'Antwort'],
        'fields' => [
            ['name' => 'q', 'label' => 'Frage', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'a', 'label' => 'Antwort', 'type' => 'richtext_mini', 'bilingual' => true],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'partner_steps' => [
        'title' => 'Partner: Schritte', 'singular' => 'Schritt', 'icon' => 'handshake', 'group' => 'content',
        'table' => 'partner_steps', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'help' => 'Texte des Partner-Programms: Einstellungen → Partner-Programm.',
        'columns' => ['title_de' => 'Titel', 'text_de' => 'Text'],
        'fields' => [
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required' => true],
            ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'social_links' => [
        'title' => 'Social Links', 'singular' => 'Link', 'icon' => 'share-2', 'group' => 'communication',
        'table' => 'social_links', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible', 'toggle2' => 'in_hero',
        'help' => 'Erscheinen im Footer, im Kontaktbereich und (wenn „Im Startbereich“ aktiv) unter dem Hero-Text. Auch als sameAs im Schema.org-Profil.',
        'columns' => ['icon' => 'Icon', 'label' => 'Name', 'handle' => 'Profil', 'in_hero' => 'Im Startbereich'],
        'fields' => [
            ['name' => 'label', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'handle', 'label' => 'Profilname', 'type' => 'text'],
            ['name' => 'url', 'label' => 'Link', 'type' => 'url', 'required' => true],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'default' => 'link'],
            ['name' => 'in_hero', 'label' => 'Im Startbereich anzeigen', 'type' => 'bool'],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
    ],

    'posts' => [
        'title' => 'Blog-Beiträge', 'singular' => 'Beitrag', 'icon' => 'newspaper', 'group' => 'blog',
        'table' => 'posts', 'order' => 'published_at DESC, id DESC', 'sortable' => false,
        'columns' => ['title_de' => 'Titel', 'status' => 'Status', 'published_at' => 'Datum', 'likes' => 'Likes', 'views' => 'Aufrufe'],
        'search' => ['title_de', 'title_en', 'excerpt_de'],
        'view_url' => '/blog/{slug}/',
        'fields' => [
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required_de' => true],
            ['name' => 'slug', 'label' => 'URL-Name', 'type' => 'slug', 'from' => 'title_de', 'help' => 'Adresse: /blog/<name>/'],
            ['name' => 'excerpt', 'label' => 'Kurztext (Teaser & Meta-Beschreibung)', 'type' => 'textarea', 'bilingual' => true, 'rows' => 3],
            ['name' => 'content', 'label' => 'Inhalt', 'type' => 'richtext', 'bilingual' => true, 'help' => 'Englisch leer lassen = englische Besucher sehen den deutschen Text mit Hinweis.'],
            ['name' => 'image', 'label' => 'Titelbild', 'type' => 'image'],
            ['name' => 'category', 'label' => 'Kategorie', 'type' => 'text', 'bilingual' => true],
            ['name' => 'icon', 'label' => 'Icon (für Cover ohne Bild)', 'type' => 'icon'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Entwurf (nicht öffentlich)', 'published' => 'Veröffentlicht (bzw. geplant, siehe Datum)'], 'default' => 'draft'],
            ['name' => 'published_at', 'label' => 'Veröffentlichung', 'type' => 'datetime', 'help' => 'Leer = sofort bei Status „Veröffentlicht“. Liegt das Datum in der Zukunft, erscheint der Beitrag zu diesem Zeitpunkt automatisch (Blog, Feed, Sitemap). Entwürfe und geplante Beiträge kannst du im Admin über das Auge-Symbol als Vorschau ansehen.'],
            ['name' => 'allow_comments', 'label' => 'Kommentare erlauben', 'type' => 'bool', 'default' => 1],
        ],
        'before_save' => static function (array $data, ?array $old): array {
            $data['updated_at'] = now();
            if ($old === null) {
                $data['created_at'] = now();
            }
            if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }
            if (empty($data['published_at'])) {
                $data['published_at'] = null;
            }
            return $data;
        },
    ],

    'pages' => [
        'title' => 'Rechtstexte & Seiten', 'singular' => 'Seite', 'icon' => 'file-text', 'group' => 'blog',
        'table' => 'pages', 'order' => 'sort ASC, id ASC', 'sortable' => true, 'toggle' => 'visible',
        'help' => 'Impressum und Datenschutz sind rechtlich relevant — bitte prüfe die Texte vor dem Livegang (ggf. mit Rechtsberatung).',
        'columns' => ['title_de' => 'Titel', 'slug' => 'Adresse', 'in_footer' => 'Im Footer'],
        'view_url' => '/{slug}/',
        'fields' => [
            ['name' => 'title', 'label' => 'Titel', 'type' => 'text', 'bilingual' => true, 'required_de' => true],
            ['name' => 'slug', 'label' => 'URL-Name', 'type' => 'slug', 'from' => 'title_de'],
            ['name' => 'subtitle', 'label' => 'Untertitel', 'type' => 'text', 'bilingual' => true],
            ['name' => 'content', 'label' => 'Inhalt', 'type' => 'richtext', 'bilingual' => true, 'help' => 'Englisch leer lassen = englische Besucher sehen den deutschen Text mit Hinweis.'],
            ['name' => 'in_footer', 'label' => 'Im Footer verlinken', 'type' => 'bool', 'default' => 1],
            ['name' => 'visible', 'label' => 'Sichtbar', 'type' => 'bool', 'default' => 1],
        ],
        'before_save' => static function (array $data, ?array $old): array {
            $data['updated_at'] = now();
            return $data;
        },
        'reserved_slugs' => ['blog', 'projekte', 'projekt', 'api', 'admin', 'install', 'assets', 'uploads', 'en', 'sitemap', 'feed', 'robots'],
    ],
];
