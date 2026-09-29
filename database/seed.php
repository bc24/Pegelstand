<?php
declare(strict_types=1);

/**
 * Startinhalte – übernommen von frank-panzer.de, panzerit.de und den verlinkten Profilen.
 * Alles hier ist nach der Installation im Admin-Bereich änderbar.
 */

$body = static function (string $slug): string {
    $html = (string)file_get_contents(__DIR__ . '/seed/post-' . $slug . '.html');
    return trim(preg_replace('/[ \t]*\n[ \t]*/', "\n", $html) ?? $html);
};
$intro = static fn(string $slug): string => trim((string)file_get_contents(__DIR__ . '/seed/post-' . $slug . '.intro.txt'));

return [

    'sections' => [
        ['skey' => 'stats',     'anchor' => 'zahlen',      'label_de' => 'Zahlen', 'label_en' => 'Numbers'],
        ['skey' => 'about',     'anchor' => 'ueber-mich',  'label_de' => 'Über mich', 'label_en' => 'About me',
            'nav_de' => 'Über mich', 'nav_en' => 'About', 'in_nav' => 1],
        ['skey' => 'motto',     'anchor' => 'motto',       'label_de' => 'Motto', 'label_en' => 'Motto'],
        ['skey' => 'interests', 'anchor' => 'interessen',  'label_de' => 'Kreativität & Interessen', 'label_en' => 'Creativity & Interests',
            'title_de' => 'Mehr als nur Code', 'title_en' => 'More than just code'],
        ['skey' => 'funfacts',  'anchor' => 'funfacts',    'label_de' => 'Fun Facts', 'label_en' => 'Fun Facts',
            'title_de' => 'Wusstest du das?', 'title_en' => 'Did you know?'],
        ['skey' => 'timeline',  'anchor' => 'der-weg',     'label_de' => 'Der Weg', 'label_en' => 'The Journey',
            'title_de' => 'Von Bremen in die digitale Welt', 'title_en' => 'From Bremen to the digital world'],
        ['skey' => 'projects',  'anchor' => 'projekte',    'label_de' => 'Web-Projekte', 'label_en' => 'Web Projects',
            'title_de' => 'Was ich gebaut habe', 'title_en' => "What I've built",
            'intro_de' => 'Ein Auszug aus über 20 Web-Projekten — vom sozialen Netzwerk bis zum Browser-Spiel.',
            'intro_en' => 'A selection from over 20 web projects — from social network to browser game.',
            'nav_de' => 'Projekte', 'nav_en' => 'Projects', 'in_nav' => 1],
        ['skey' => 'former',    'anchor' => 'archiv',      'label_de' => 'Ehemalige Webseiten', 'label_en' => 'Former Websites',
            'title_de' => 'Projekte, die ich betrieben habe', 'title_en' => "Projects I've run"],
        ['skey' => 'maker',     'anchor' => 'maker',       'label_de' => 'Maker-Projekte', 'label_en' => 'Maker Projects',
            'title_de' => 'Nicht nur digital', 'title_en' => 'Not just digital',
            'intro_de' => 'Alle Projekte habe ich selbst geplant und umgesetzt.', 'intro_en' => 'All projects planned and built by me.'],
        ['skey' => 'shop',      'anchor' => 'shop',        'label_de' => 'Shop & E-Commerce', 'label_en' => 'Shop & E-Commerce',
            'title_de' => 'Großmarkt für alle', 'title_en' => 'Wholesale for everyone',
            'intro_de' => 'Ich verkaufe Produkte aus dem Großmarkt — so gelangen auch Privatpersonen ohne Gewerbeschein an günstige Großpackungen. Große Auswahl auf hb3d.de und bc24.org.',
            'intro_en' => 'I sell wholesale goods so private individuals without a business license can access bulk deals. Wide selection on hb3d.de and bc24.org.'],
        ['skey' => 'skills',    'anchor' => 'skills',      'label_de' => 'IT-Kenntnisse', 'label_en' => 'IT Skills',
            'title_de' => 'Was ich kann', 'title_en' => 'What I know',
            'nav_de' => 'Skills', 'nav_en' => 'Skills', 'in_nav' => 1],
        ['skey' => 'cv',        'anchor' => 'lebenslauf',  'label_de' => 'Lebenslauf', 'label_en' => 'CV',
            'title_de' => 'Mein Werdegang', 'title_en' => 'My Career Path',
            'nav_de' => 'Lebenslauf', 'nav_en' => 'CV', 'in_nav' => 1],
        ['skey' => 'music',     'anchor' => 'musik',       'label_de' => 'Musik & Kreativität', 'label_en' => 'Music & Creativity',
            'title_de' => 'DJ-Frankus', 'title_en' => 'DJ-Frankus'],
        ['skey' => 'setup',     'anchor' => 'setup',       'label_de' => 'Mein Setup', 'label_en' => 'My Setup',
            'title_de' => 'Womit ich arbeite', 'title_en' => 'What I work with',
            'intro_de' => 'Nur Software — alles, was ich täglich nutze, um meine Projekte zu betreiben.',
            'intro_en' => 'Software only — everything I use daily to run my projects.'],
        ['skey' => 'blog',      'anchor' => 'blog',        'label_de' => 'Blog', 'label_en' => 'Blog',
            'title_de' => 'Gedanken & Updates', 'title_en' => 'Thoughts & Updates',
            'nav_de' => 'Blog', 'nav_en' => 'Blog', 'in_nav' => 1],
        ['skey' => 'faq',       'anchor' => 'faq',         'label_de' => 'FAQ', 'label_en' => 'FAQ',
            'title_de' => 'Häufige Fragen', 'title_en' => 'Frequently Asked Questions'],
        ['skey' => 'partner',   'anchor' => 'partner',     'label_de' => 'Partner & Provisionen', 'label_en' => 'Partner & Commissions',
            'title_de' => 'Verdiene Geld mit mir', 'title_en' => 'Earn money with me'],
        ['skey' => 'contact',   'anchor' => 'kontakt',     'label_de' => 'Kontakt', 'label_en' => 'Contact',
            'title_de' => 'Schreib mir', 'title_en' => 'Get in touch',
            'intro_de' => 'Fragen, Ideen, Projekte, Partner-Anfragen — ich freue mich über jede Nachricht.',
            'intro_en' => 'Questions, ideas, projects, partnership inquiries — I welcome every message.',
            'nav_de' => 'Kontakt', 'nav_en' => 'Contact', 'in_nav' => 1],
    ],

    'stats' => [
        ['auto_years' => 1, 'count_to' => 25, 'label_de' => 'Jahre online (seit 2001)', 'label_en' => 'years online (since 2001)'],
        ['count_to' => 20, 'suffix_de' => '+', 'suffix_en' => '+', 'label_de' => 'Web-Projekte', 'label_en' => 'web projects'],
        ['count_to' => 12000, 'suffix_de' => '+', 'suffix_en' => '+', 'label_de' => 'Nutzer auf foto18.de', 'label_en' => 'users on foto18.de'],
        ['count_to' => 2.8, 'decimals' => 1, 'suffix_de' => ' Mio.', 'suffix_en' => ' M', 'label_de' => 'Fotos hochgeladen', 'label_en' => 'photos uploaded'],
        ['count_to' => 7, 'label_de' => 'aktive Domains', 'label_en' => 'active domains'],
    ],

    'about_badges' => [
        ['icon' => 'calendar', 'text_de' => 'Jahrgang 1982', 'text_en' => 'Born 1982'],
        ['icon' => 'map-pin', 'text_de' => 'Bremen-Blumenthal', 'text_en' => 'Bremen-Blumenthal'],
        ['icon' => 'laptop', 'text_de' => 'Seit 2001 online', 'text_en' => 'Online since 2001'],
        ['icon' => 'heart', 'text_de' => 'Verheiratet seit 02.02.2022', 'text_en' => 'Married since 02.02.2022'],
        ['icon' => 'music', 'text_de' => 'DJ-Frankus', 'text_en' => 'DJ-Frankus'],
        ['icon' => 'rocket', 'text_de' => '9+ aktive Projekte', 'text_en' => '9+ active projects'],
    ],

    'interests' => [
        ['icon' => 'music', 'title_de' => 'DJ-Frankus', 'title_en' => 'DJ-Frankus',
            'text_de' => 'Ich produziere eigene Musik unter dem Namen DJ-Frankus — vorrangig Rap, Hardstyle und Techno. Musik ist Leidenschaft, kein Business.',
            'text_en' => 'I produce my own music as DJ-Frankus — mainly Rap, Hardstyle, and Techno. Music is passion, not business.'],
        ['icon' => 'shopping-cart', 'title_de' => 'E-Commerce', 'title_en' => 'E-Commerce',
            'text_de' => 'Aktiv auf eBay und Etsy. Ich importiere Waren aus dem Großmarkt — damit gelangen auch Privatpersonen ohne Gewerbeschein an günstige Großpackungen. Angebote auf hb3d.de und bc24.org.',
            'text_en' => 'Active on eBay and Etsy. I import goods from wholesale markets so private individuals without a business license can access bulk deals. Offers on hb3d.de and bc24.org.'],
        ['icon' => 'bot', 'title_de' => 'Automatisierung', 'title_en' => 'Automation',
            'text_de' => 'Ich baue Automatisierungen mit n8n und programmiere Telegram-Bots — z. B. Bots, die Produkte aus hb3d.de bewerben, Guten-Morgen-Nachrichten senden oder Ruhezeiten einhalten.',
            'text_en' => 'I build automations with n8n and program Telegram bots — e.g. bots that promote products from hb3d.de, send good-morning messages, or respect quiet hours.'],
        ['icon' => 'sparkles', 'title_de' => 'Claude Code & KI', 'title_en' => 'Claude Code & AI',
            'text_de' => 'Seit ich Claude Code entdeckt habe, bin ich total begeistert. KI-gestützte Entwicklung verändert alles — ich nutze es täglich für Projekte, Automatisierungen und Code.',
            'text_en' => "Since discovering Claude Code, I'm totally hooked. AI-assisted development changes everything — I use it daily for projects, automations, and code."],
    ],

    'facts' => [
        ['icon' => 'globe', 'title_de' => 'Erste Website: 2001', 'title_en' => 'First website: 2001',
            'text_de' => 'Lange vor jeder IT-Ausbildung — einfach selbst rausgefunden.', 'text_en' => 'Long before any IT training — just figured it out myself.'],
        ['icon' => 'calendar-heart', 'title_de' => 'Palindrom-Hochzeitstag', 'title_en' => 'Palindrome wedding date',
            'text_de' => 'Geheiratet am 02.02.2022 — ein Datum, das vorwärts wie rückwärts gleich ist.', 'text_en' => 'Married on 02.02.2022 — a date that reads the same backwards and forwards.'],
        ['icon' => 'forklift', 'title_de' => 'Staplerschein-Inhaber', 'title_en' => 'Certified forklift operator',
            'text_de' => 'Nicht nur Software — ich fahre auch Stapler.', 'text_en' => 'Not just software — I also operate forklifts.'],
        ['icon' => 'audio-lines', 'title_de' => 'DJ-Frankus', 'title_en' => 'DJ-Frankus',
            'text_de' => 'Rap, Hardstyle, Techno — drei Genres, eine Leidenschaft. Alles selbst produziert.', 'text_en' => 'Rap, Hardstyle, Techno — three genres, one passion. All self-produced.'],
        ['icon' => 'rabbit', 'title_de' => 'Kaninchengehege in Planung', 'title_en' => 'Rabbit enclosure in planning',
            'text_de' => 'Nicht nur digitale Projekte — auch analoge Häschen-Projekte warten.', 'text_en' => 'Not just digital projects — analog bunny projects are waiting too.'],
        ['icon' => 'layers', 'title_de' => '7 Websites gleichzeitig', 'title_en' => '7 websites simultaneously',
            'text_de' => 'Alles alleine — kein Team, kein Büro, nur Kaffee und Tastatur.', 'text_en' => 'All by himself — no team, no office, just coffee and keyboard.'],
        ['icon' => 'terminal', 'title_de' => 'Claude Code Enthusiast', 'title_en' => 'Claude Code enthusiast',
            'text_de' => 'Seit der Entdeckung von Claude Code kaum noch aufgehört. Buchstäblich.', 'text_en' => 'Barely stopped since discovering Claude Code. Literally.'],
        ['icon' => 'camera', 'title_de' => '2,8 Millionen Fotos', 'title_en' => '2.8 million photos',
            'text_de' => 'Auf foto18.de haben 12.000+ Nutzer insgesamt 2,8 Mio. Fotos hochgeladen.', 'text_en' => 'On foto18.de, 12,000+ users have uploaded a total of 2.8M photos.'],
    ],

    'timeline' => [
        ['year' => '1982', 'title_de' => 'Frank Panzer wird geboren', 'title_en' => 'Frank Panzer is born',
            'text_de' => 'Im selben Jahr erscheint der Commodore 64 — der Startschuss für den Heimcomputer.', 'text_en' => 'The same year the Commodore 64 launches — the start of the home computer era.'],
        ['year' => '2001', 'title_de' => 'Erste Website: web-pood.de', 'title_en' => 'First website: web-pood.de',
            'text_de' => 'Einfach angefangen. Kein Studium, kein Kurs — nur Neugier.', 'text_en' => 'Just started. No degree, no course — just curiosity.'],
        ['year' => '2004', 'title_de' => 'Frank-Panzer.de', 'title_en' => 'Frank-Panzer.de',
            'text_de' => 'Erster persönlicher Blog online.', 'text_en' => 'First personal blog launched.'],
        ['year' => '2009', 'title_de' => 'Bremer Community gegründet', 'title_en' => 'Bremer Community founded',
            'text_de' => 'Soziales Netzwerk für Bremen & Umgebung — bis heute aktiv.', 'text_en' => 'Social network for Bremen — still active today.'],
        ['year' => '2013', 'title_de' => 'Selbststudium IT', 'title_en' => 'Self-study IT',
            'text_de' => 'Programmiersprachen in Eigeninitiative, Online-Kurse, neue Projekte.', 'text_en' => 'Self-taught programming, online courses, new projects.'],
        ['year' => '2014', 'title_de' => 'BC24-Ökosystem', 'title_en' => 'BC24 ecosystem',
            'text_de' => 'bc24.org + Chat + Upload + Hosting — ein ganzes Ökosystem selbst gebaut.', 'text_en' => 'bc24.org + chat + upload + hosting — a whole ecosystem built from scratch.'],
        ['year' => '2015', 'title_de' => 'Offizielle Umschulung', 'title_en' => 'Formal retraining',
            'text_de' => 'Fachinformatiker Systemintegration — IBB Bremen.', 'text_en' => 'IT Specialist System Integration — IBB Bremen.'],
        ['year' => '2017', 'title_de' => 'Fachinformatiker Anwendungsentwicklung', 'title_en' => 'IT Specialist Application Development',
            'text_de' => 'Abschluss bei der cbm GmbH Bremen.', 'text_en' => 'Graduated from cbm GmbH Bremen.'],
        ['year' => '2019', 'title_de' => 'Selbstständig', 'title_en' => 'Freelance',
            'text_de' => 'Eigene Projekte, eigene Kunden, eigene Regeln.', 'text_en' => 'Own projects, own clients, own rules.'],
        ['year' => '2026', 'title_de' => 'frank-panzer.de — alles auf einem Platz', 'title_en' => 'frank-panzer.de — everything in one place',
            'text_de' => 'Diese Seite. Die Geschichte geht weiter.', 'text_en' => 'This website. The story continues.', 'accent' => 1],
    ],

    'projects' => [
        ['slug' => 'panzerit-de', 'title' => 'panzerit.de', 'category' => 'web', 'badge_icon' => 'briefcase', 'badge_de' => 'Web-Agentur', 'badge_en' => 'Web agency',
            'tagline_de' => 'Professionelle Webentwicklung aus Bremen. Websites, die für Sie arbeiten.', 'tagline_en' => 'Professional web development from Bremen. Websites that work for you.',
            'description_de' => '<p>Panzer IT bietet professionelle Webentwicklung im Abo-Modell mit monatlichen Festpreisen: Unternehmens-Websites, Onlineshops und Landingpages — inklusive Hosting, SSL-Zertifikat, Backups und Updates.</p>',
            'description_en' => '<p>Panzer IT offers professional web development on a subscription basis with fixed monthly prices: company websites, online shops and landing pages — including hosting, SSL certificate, backups and updates.</p>',
            'url' => 'https://panzerit.de', 'image' => 'assets/screenshots/panzerit.png', 'year_from' => '2019', 'featured' => 1],
        ['slug' => 'bremer-community-de', 'title' => 'bremer-community.de', 'category' => 'web', 'badge_icon' => 'users', 'badge_de' => 'Community', 'badge_en' => 'Community',
            'tagline_de' => 'Soziales Netzwerk für Bremen & Umgebung. Seit 2009.', 'tagline_en' => 'Social network for Bremen & surroundings. Since 2009.',
            'description_de' => '<p>Die Bremer Community ist mein Herzensprojekt: ein soziales Netzwerk für Bremen und Umgebung — seit 2009 online und mehrfach von Grund auf neu gebaut.</p>',
            'description_en' => '<p>The Bremer Community is my passion project: a social network for Bremen and the surrounding area — online since 2009 and rebuilt from scratch several times.</p>',
            'url' => 'https://bremer-community.de', 'image' => 'assets/screenshots/bremer-community.png', 'year_from' => '2009', 'featured' => 1],
        ['slug' => 'bc24-org', 'title' => 'bc24.org', 'category' => 'web', 'badge_icon' => 'book-open', 'badge_de' => 'Affiliate & DIY', 'badge_en' => 'Affiliate & DIY',
            'tagline_de' => 'Plattform für Wachstum & Erfolg. Große Auswahl an Produkten und Anleitungen.', 'tagline_en' => 'Platform for growth & success. Wide range of products and guides.',
            'url' => 'https://bc24.org', 'image' => 'assets/screenshots/bc24.png', 'year_from' => '2014', 'featured' => 1],
        ['slug' => 'foto18-de', 'title' => 'foto18.de', 'category' => 'web', 'badge_icon' => 'camera', 'badge_de' => 'Foto-Hosting', 'badge_en' => 'Photo hosting',
            'tagline_de' => 'Professionelle Foto-Sharing-Plattform. 12.000+ Nutzer, 2,8 Mio. Fotos.', 'tagline_en' => 'Professional photo sharing platform. 12,000+ users, 2.8M photos.',
            'url' => 'https://foto18.de', 'image' => 'assets/screenshots/foto18.png', 'year_from' => '2015', 'meta_de' => '12.000+ Nutzer · 2,8 Mio. Fotos', 'meta_en' => '12,000+ users · 2.8M photos', 'featured' => 1],
        ['slug' => 'fotohimmel-de', 'title' => 'fotohimmel.de', 'category' => 'web', 'badge_icon' => 'cloud', 'badge_de' => 'Cloud-Speicher', 'badge_en' => 'Cloud storage',
            'tagline_de' => 'Automatische Foto & Video Cloud. Pläne ab 6,99 €/Monat.', 'tagline_en' => 'Automatic photo & video cloud. Plans from €6.99/month.',
            'url' => 'https://fotohimmel.de', 'image' => '', 'featured' => 1],
        ['slug' => 'hb3d-de', 'title' => 'hb3d.de', 'category' => 'shop', 'badge_icon' => 'shopping-cart', 'badge_de' => 'Online-Shop', 'badge_en' => 'Online shop',
            'tagline_de' => 'Online-Shop mit diversen Produkten — darunter Großmarkt-Waren für Privatpersonen.', 'tagline_en' => 'Online shop with various products — including wholesale goods for private customers.',
            'url' => 'https://hb3d.de', 'image' => 'assets/screenshots/hb3d.png', 'featured' => 1],
        ['slug' => 'okurs-de', 'title' => 'okurs.de', 'category' => 'web', 'badge_icon' => 'graduation-cap', 'badge_de' => 'Online-Kurs', 'badge_en' => 'Online course',
            'tagline_de' => 'Online-Mathe-Kurs — Mathematik endlich verstehen.', 'tagline_en' => 'Online math course — finally understand mathematics.',
            'description_de' => '<p>Ein Online-Mathe-Kurs, den ich selbst als Schüler gebraucht hätte.</p>', 'description_en' => '<p>An online math course I would have needed myself as a student.</p>',
            'url' => 'https://okurs.de', 'image' => 'assets/screenshots/okurs.png', 'meta_de' => '4,8★ · 2.400+ Teilnehmer', 'meta_en' => '4.8★ · 2,400+ students', 'featured' => 1],
        ['slug' => 'spiel', 'title' => 'Spiel', 'category' => 'game', 'badge_icon' => 'gamepad-2', 'badge_de' => 'Browser-Spiel', 'badge_en' => 'Browser game',
            'tagline_de' => 'Ein selbst entwickeltes Browser-Spiel — direkt im Browser spielbar, ohne Download.', 'tagline_en' => 'A self-developed browser game — playable directly in the browser, no download required.',
            'url' => 'https://panzerit.de/spiel', 'image' => '', 'meta_de' => 'panzerit.de/spiel', 'meta_en' => 'panzerit.de/spiel', 'featured' => 1],
        ['slug' => 'bremer-miner', 'title' => 'Bremer Miner', 'category' => 'game', 'badge_icon' => 'pickaxe', 'badge_de' => 'Clicker-Spiel', 'badge_en' => 'Clicker game',
            'tagline_de' => 'Clicker-Spiel mit Bremer Flair. Grabe, sammle, werde reich.', 'tagline_en' => 'A clicker game with a Bremen feel. Dig, collect, get rich.',
            'url' => 'https://panzerit.de/bremer-miner', 'image' => '', 'meta_de' => 'panzerit.de/bremer-miner', 'meta_en' => 'panzerit.de/bremer-miner', 'featured' => 1],
        ['slug' => 'franks-kaesekaestchen', 'title' => 'Käsekästchen', 'category' => 'game', 'badge_icon' => 'dices', 'badge_de' => 'Multiplayer-Spiel', 'badge_en' => 'Multiplayer game',
            'tagline_de' => 'Das bunte Multiplayer-Punktspiel — spiele gegen andere Spieler weltweit!', 'tagline_en' => 'The colourful multiplayer dots & boxes game — play against players worldwide!',
            'url' => 'https://panzerit.de/franks-kaesekaestchen', 'image' => '', 'meta_de' => 'panzerit.de/franks-kaesekaestchen', 'meta_en' => 'panzerit.de/franks-kaesekaestchen', 'featured' => 1],
        ['slug' => 'tarif-finder', 'title' => 'Tarif-Finder', 'category' => 'tool', 'badge_icon' => 'search', 'badge_de' => 'Vergleichsportal', 'badge_en' => 'Comparison portal',
            'tagline_de' => 'Versicherungen & Tarife kostenlos vergleichen — Kfz, Kranken, Energie, Kredite & mehr.', 'tagline_en' => 'Compare insurance & tariffs for free — car, health, energy, loans & more.',
            'url' => 'https://panzerit.de/tarif-finder', 'image' => '', 'meta_de' => 'panzerit.de/tarif-finder', 'meta_en' => 'panzerit.de/tarif-finder', 'featured' => 1],
        ['slug' => 'lohnabrechnung', 'title' => 'Lohnabrechnung', 'category' => 'tool', 'badge_icon' => 'wallet', 'badge_de' => 'Einkommens-Tracker', 'badge_en' => 'Income tracker',
            'tagline_de' => 'Brutto & Netto Monat für Monat tracken — immer den Überblick über dein Einkommen.', 'tagline_en' => 'Track gross & net income month by month — always keep an eye on your earnings.',
            'url' => 'https://panzerit.de/lohnabrechnung/', 'image' => '', 'meta_de' => 'panzerit.de/lohnabrechnung', 'meta_en' => 'panzerit.de/lohnabrechnung', 'featured' => 1],
        ['slug' => 'unendliche-geschichte', 'title' => 'Unendliche Geschichte', 'category' => 'tool', 'badge_icon' => 'pen-line', 'badge_de' => 'Kollaboratives Schreiben', 'badge_en' => 'Collaborative writing',
            'tagline_de' => 'Eine Geschichte ohne Ende — schreibe gemeinsam mit anderen an einer nie endenden Story. Rangliste, Statistiken, kostenlos.',
            'tagline_en' => 'A story without end — write together with others on a never-ending story. Leaderboard, stats, free.',
            'url' => 'https://panzerit.de/unendliche_geschichte', 'image' => '', 'meta_de' => 'panzerit.de/unendliche_geschichte', 'meta_en' => 'panzerit.de/unendliche_geschichte', 'featured' => 1],

        // Weitere Apps aus frank-panzer.de/projekte/ (nur auf der Projektseite, nicht auf der Startseite)
        ['slug' => 'bewerbungspilot', 'title' => 'BewerbungsPilot', 'category' => 'tool', 'badge_icon' => 'briefcase', 'badge_de' => 'Bewerbungs-App', 'badge_en' => 'Job application app',
            'tagline_de' => 'Lebenslauf hochladen, Wunschberuf angeben — passende Stellen finden und für jede Anzeige Lebenslauf und Anschreiben erstellen.',
            'tagline_en' => 'Upload your CV, name your dream job — find matching vacancies and get a tailored CV and cover letter for every ad.',
            'description_de' => '<p>BewerbungsPilot holt Stellenangebote von der Bundesagentur für Arbeit, LinkedIn, StepStone und weiteren Jobbörsen zu deinem Wunschberuf und erstellt für jede Anzeige einen passgenauen Lebenslauf und ein Anschreiben. Registrierung kostenlos, bezahlt wird nur pro Bewerbung.</p>',
            'description_en' => '<p>BewerbungsPilot pulls vacancies from the German Federal Employment Agency, LinkedIn, StepStone and other job boards for your desired profession and creates a tailored CV and cover letter for each ad. Free registration, you only pay per application.</p>',
            'url' => 'https://frank-panzer.de/projekte/bewerbungen/', 'meta_de' => 'Bezahlung nur pro Bewerbung', 'meta_en' => 'Pay per application only', 'featured' => 0],
        ['slug' => 'techdeals24', 'title' => 'TechDeals24', 'category' => 'shop', 'badge_icon' => 'tag', 'badge_de' => 'Deal-Portal', 'badge_en' => 'Deal portal',
            'tagline_de' => '61.000+ MediaMarkt-Angebote täglich aktualisiert — TVs, Smartphones, Laptops, Gaming und mehr.',
            'tagline_en' => '61,000+ MediaMarkt deals updated daily — TVs, smartphones, laptops, gaming and more.',
            'url' => 'https://frank-panzer.de/projekte/tech-deals-24/', 'meta_de' => '61.000+ Produkte', 'meta_en' => '61,000+ products', 'featured' => 0],
        ['slug' => 'trockenheld', 'title' => 'Trockenheld', 'category' => 'tool', 'badge_icon' => 'trophy', 'badge_de' => 'Challenge-App', 'badge_en' => 'Challenge app',
            'tagline_de' => 'Die Web-App für deine Challenge ohne Alkohol. Täglich einchecken, Streaks sammeln, Meilensteine feiern.',
            'tagline_en' => 'The web app for your alcohol-free challenge. Check in daily, collect streaks, celebrate milestones.',
            'description_de' => '<p>Trockenheld ist dein tägliches Challenge-Game: Ein Klick bestätigt, dass du nichts Alkoholisches getrunken hast — dafür gibt es Punkte, die mit jedem Tag deiner Serie steigen (Combo-Bonus), Level-Aufstiege und Boni bei Woche, Monat, Halbjahr und Jahr.</p>',
            'description_en' => '<p>Trockenheld is your daily challenge game: one click confirms you did not drink anything alcoholic — earn points that grow with every day of your streak (combo bonus), level-ups and bonuses for a week, month, half year and year.</p>',
            'url' => 'https://frank-panzer.de/projekte/trockenheld/', 'featured' => 0],
        ['slug' => 'weltenentdecker', 'title' => 'Weltenentdecker', 'category' => 'game', 'badge_icon' => 'compass', 'badge_de' => 'GPS-Entdecker-Spiel', 'badge_en' => 'GPS explorer game',
            'tagline_de' => 'Erkunde die echte Welt, sammle digitale Stempel und steige in der Bestenliste auf.',
            'tagline_en' => 'Explore the real world, collect digital stamps and climb the leaderboard.',
            'description_de' => '<p>Geh zu einem Ort, beantworte die Frage und checke per GPS ein. Richtig beantwortet gibt es Punkte und Stempel für deinen Reisepass.</p>',
            'description_en' => '<p>Go to a place, answer the question and check in via GPS. Correct answers earn points and stamps for your passport.</p>',
            'url' => 'https://frank-panzer.de/projekte/weltenentdecker/', 'featured' => 0],
        ['slug' => 'spielearena', 'title' => 'Spielearena', 'category' => 'game', 'badge_icon' => 'swords', 'badge_de' => 'Multiplayer-Lobby', 'badge_en' => 'Multiplayer lobby',
            'tagline_de' => 'Schach, Dame, Poker, Mau-Mau und mehr — in Echtzeit gegen echte Gegner, mit Chat, Statistiken und Bestenliste.',
            'tagline_en' => 'Chess, checkers, poker, Mau-Mau and more — in real time against real opponents, with chat, stats and leaderboard.',
            'url' => 'https://frank-panzer.de/projekte/spiele/', 'meta_de' => '8 Spiele', 'meta_en' => '8 games', 'featured' => 0],
        ['slug' => 'poesiealbum', 'title' => 'Franks Poesiealbum', 'category' => 'tool', 'badge_icon' => 'book-open', 'badge_de' => 'Poesiealbum', 'badge_en' => 'Autograph book',
            'tagline_de' => 'Hier sammeln sich alle Einträge von Freunden und Familie. Trag dich auch ein!',
            'tagline_en' => 'All entries from friends and family collect here. Add yours too!',
            'url' => 'https://frank-panzer.de/projekte/poesiealbum/', 'featured' => 0],
        // Private Login-Tools: standardmäßig unsichtbar – im Admin bei Bedarf sichtbar schalten
        ['slug' => 'panzer-it-cockpit', 'title' => 'Panzer IT Cockpit', 'category' => 'tool', 'badge_icon' => 'layout-dashboard', 'badge_de' => 'Internes Tool', 'badge_en' => 'Internal tool',
            'tagline_de' => 'Deine Firma an einem Ort: Tagesplan, Kunden, Briefe, Ziel, Fahrtenbuch, Steuern und Coach.',
            'tagline_en' => 'Your company in one place: daily plan, clients, letters, goals, logbook, taxes and coach.',
            'url' => 'https://frank-panzer.de/projekte/cockpit/', 'featured' => 0, 'visible' => 0],
        ['slug' => 'social-media-manager', 'title' => 'Social Media Manager', 'category' => 'tool', 'badge_icon' => 'share-2', 'badge_de' => 'Internes Tool', 'badge_en' => 'Internal tool',
            'tagline_de' => 'Social-Media-Verwaltung von Panzer IT (Login erforderlich).', 'tagline_en' => 'Social media management by Panzer IT (login required).',
            'url' => 'https://frank-panzer.de/projekte/social-media/', 'featured' => 0, 'visible' => 0],
    ],

    'former_sites' => [
        ['year' => '2001', 'domain' => 'web-pood.de', 'text_de' => 'Community → Chat → Portal', 'text_en' => 'Community → Chat → Portal'],
        ['year' => '2004', 'domain' => 'Frank-Panzer.de', 'text_de' => 'Erster persönlicher Blog', 'text_en' => 'First personal blog'],
        ['year' => '2009', 'domain' => 'bremer-community.de', 'text_de' => 'Community für Bremen (mehrfach überarbeitet)', 'text_en' => 'Community for Bremen (revamped multiple times)'],
        ['year' => '2013', 'domain' => 'untergrund-musik.de', 'text_de' => 'Portal für Untergrund-Musiker', 'text_en' => 'Portal for underground musicians'],
        ['year' => '2014', 'domain' => 'hochladen.bc24.org', 'text_de' => 'Kostenloser Datei-Upload (100 MB)', 'text_en' => 'Free file upload (100 MB)'],
        ['year' => '2014', 'domain' => 'chat.bc24.org', 'text_de' => 'Chat-Plattform', 'text_en' => 'Chat platform'],
        ['year' => '2014', 'domain' => 'bc-hosting.de', 'text_de' => 'Webhosting-Angebote', 'text_en' => 'Web hosting offers'],
        ['year' => '2015', 'domain' => 'derbe.sexy', 'text_de' => 'Foto-Sharing-Plattform', 'text_en' => 'Photo sharing platform'],
        ['year' => '2015', 'domain' => 'generatortechnik.com', 'text_de' => 'Firmenseite (Praktikumsbetrieb)', 'text_en' => 'Company website (internship)'],
        ['year' => '2015', 'domain' => 'be-friend.de', 'text_de' => 'Freundschafts- & Single-Netzwerk', 'text_en' => 'Friendship & singles network'],
        ['year' => '2016', 'domain' => 'forum.bc24.org', 'text_de' => 'Community-Forum', 'text_en' => 'Community forum'],
        ['year' => '2016', 'domain' => 'ibb-fisi.de', 'text_de' => 'Lernmaterial für Fachinformatiker', 'text_en' => 'Learning material for IT specialists'],
        ['year' => '2016', 'domain' => 'leicht.kaufen', 'text_de' => 'Shop für Templates & Scripte', 'text_en' => 'Shop for templates & scripts'],
        ['year' => '2016', 'domain' => 'ibb-forum.com', 'text_de' => 'Forum für IBB-Standorte', 'text_en' => 'Forum for IBB locations'],
        ['year' => '', 'domain' => 'nacht-mutter.de', 'text_de' => 'Eltern-Ratgeber', 'text_en' => 'Parenting guide'],
        ['year' => '', 'domain' => 'nacht-vater.de', 'text_de' => 'Eltern-Ratgeber', 'text_en' => 'Parenting guide'],
        ['year' => '', 'domain' => 'kostenlose-seite.de', 'text_de' => 'Kostenlose Websites für Vereine & Kleinbetriebe', 'text_en' => 'Free websites for clubs & small businesses'],
        ['year' => '', 'domain' => 'hbhost.de', 'text_de' => 'Webhosting aus Bremen', 'text_en' => 'Web hosting from Bremen'],
    ],

    'maker_projects' => [
        ['icon' => 'server', 'title_de' => 'Raspberry Pi Medienserver', 'title_en' => 'Raspberry Pi media server', 'status' => 'done'],
        ['icon' => 'gamepad-2', 'title_de' => 'Wii gemoddet + Festplatte', 'title_en' => 'Modded Wii + hard drive', 'status' => 'done'],
        ['icon' => 'martini', 'title_de' => 'Cocktailbar selbst gebaut', 'title_en' => 'Self-built cocktail bar', 'status' => 'done'],
        ['icon' => 'shirt', 'title_de' => 'Begehbarer Kleiderschrank', 'title_en' => 'Walk-in wardrobe', 'status' => 'done'],
        ['icon' => 'hard-drive', 'title_de' => '2× NAS-Server Eigenbau', 'title_en' => '2× self-built NAS server', 'status' => 'done'],
        ['icon' => 'phone', 'title_de' => 'VoIP-Anlage mehrere Standorte', 'title_en' => 'VoIP system, multiple locations', 'status' => 'done'],
        ['icon' => 'bell-ring', 'title_de' => 'Türklingel mit Mailbox & Telefon', 'title_en' => 'Doorbell with mailbox & phone', 'status' => 'done'],
        ['icon' => 'radio', 'title_de' => 'Webradio-Studio', 'title_en' => 'Web radio studio', 'status' => 'planned'],
        ['icon' => 'rabbit', 'title_de' => 'Großes Kaninchengehege', 'title_en' => 'Large rabbit enclosure', 'status' => 'planned'],
    ],

    'shop_items' => [
        ['icon' => 'spray-can', 'title_de' => 'Reinigung & Haushalt', 'title_en' => 'Cleaning & household',
            'text_de' => 'Großpackungen für Haushalt & Büro zu Großmarktpreisen.', 'text_en' => 'Bulk packs for home & office at wholesale prices.'],
        ['icon' => 'utensils', 'title_de' => 'Lebensmittel & Snacks', 'title_en' => 'Food & snacks',
            'text_de' => 'Beliebte Produkte in Großpackungen — günstiger als im Supermarkt.', 'text_en' => 'Popular products in bulk — cheaper than the supermarket.'],
        ['icon' => 'wrench', 'title_de' => 'Werkzeug & Zubehör', 'title_en' => 'Tools & accessories',
            'text_de' => 'Nützliche Alltagsprodukte, Werkzeug und Zubehör aus dem Großmarkt.', 'text_en' => 'Useful everyday products, tools and accessories from the wholesale market.'],
        ['icon' => 'package', 'title_de' => 'Sonderposten & Angebote', 'title_en' => 'Special offers',
            'text_de' => 'Immer wechselnde Sonderangebote — schau regelmäßig vorbei.', 'text_en' => 'Ever-changing special offers — check back regularly.'],
    ],

    // level: 3 = sehr gut, 2 = gut, 1 = befriedigend
    'skill_groups' => [
        ['icon' => 'code', 'title_de' => 'Web & Programmierung', 'title_en' => 'Web & Programming', 'skills' => [
            ['HTML', 'brand-html5', 3], ['CSS', 'brand-css', 3], ['JavaScript', 'brand-javascript', 3], ['PHP', 'brand-php', 2], ['jQuery', 'brand-jquery', 2],
            ['SQL', 'database', 2], ['Node.js', 'brand-nodedotjs', 1], ['AngularJS', 'brand-angular', 1], ['C#', 'code', 1],
            ['WordPress', 'brand-wordpress', 3], ['Drupal', 'brand-drupal', 2], ['Joomla', 'brand-joomla', 2], ['Shopware', 'brand-shopware', 2],
            ['TYPO3', 'brand-typo3', 1], ['Magento', 'store', 1], ['Contao', 'layers', 1],
        ]],
        ['icon' => 'server', 'title_de' => 'Infrastruktur & Netzwerk', 'title_en' => 'Infrastructure & Network', 'skills' => [
            ['Linux', 'brand-linux', 2], ['Apache 2', 'brand-apache', 2], ['Nginx', 'brand-nginx', 1], ['MySQL', 'brand-mysql', 2], ['VMware', 'brand-vmware', 2],
            ['Hyper-V', 'server', 1], ['Windows Server', 'monitor', 2], ['DNS', 'globe', 2], ['VoIP', 'phone', 2], ['Subnetting', 'network', 1],
            ['Active Directory', 'users', 1], ['Plesk', 'brand-plesk', 2],
        ]],
        ['icon' => 'wrench', 'title_de' => 'Tools & Sonstiges', 'title_en' => 'Tools & Other', 'skills' => [
            ['Photoshop', 'palette', 2], ['GIMP', 'brand-gimp', 2], ['TeamViewer', 'brand-teamviewer', 3], ['Raspberry Pi', 'brand-raspberrypi', 2],
            ['n8n', 'brand-n8n', 2], ['Telegram-Bots', 'brand-telegram', 3], ['Claude Code', 'brand-claude', 3], ['Englisch', 'languages', 2],
            ['Führerschein B+BE', 'car', 3], ['Staplerschein', 'forklift', 3],
        ]],
    ],

    'cv_entries' => [
        ['kind' => 'education', 'period' => '2017', 'title_de' => 'Fachinformatiker Anwendungsentwicklung', 'title_en' => 'IT Specialist Application Development',
            'text_de' => 'cbm GmbH, Bremen — Abschluss IHK', 'text_en' => 'cbm GmbH, Bremen — IHK graduation'],
        ['kind' => 'education', 'period' => '2015–2016', 'title_de' => 'Fachinformatiker Systemintegration', 'title_en' => 'IT Specialist System Integration',
            'text_de' => 'IBB Institut für Berufliche Bildung, Bremen — Umschulung', 'text_en' => 'IBB Institute for Vocational Training, Bremen — retraining'],
        ['kind' => 'education', 'period' => '2001–heute', 'title_de' => 'Autodidakt & Selbststudium', 'title_en' => 'Self-taught & continuous learning',
            'text_de' => 'Webentwicklung, PHP, JavaScript, MySQL, Linux, Server-Administration, Netzwerk — alles aus eigener Initiative',
            'text_en' => 'Web development, PHP, JavaScript, MySQL, Linux, server administration, networking — all self-initiated'],
        ['kind' => 'education', 'period' => '', 'title_de' => 'Lizenzen & Sprachen', 'title_en' => 'Licenses & languages',
            'text_de' => 'Führerschein Klasse B + BE · Staplerschein · Englisch (gut in Wort und Schrift)',
            'text_en' => 'Driving license B + BE · forklift license · English (good, written and spoken)'],
        ['kind' => 'experience', 'period' => '2019–heute', 'title_de' => 'Selbstständig — Panzer IT', 'title_en' => 'Freelance — Panzer IT',
            'text_de' => 'Webdesign, Webentwicklung, IT-Dienstleistungen · Kunden aus ganz Deutschland · Umsatzsteuer-ID: DE 369836826',
            'text_en' => 'Web design, web development, IT services · clients across Germany · VAT ID: DE 369836826'],
        ['kind' => 'experience', 'period' => '2018–heute', 'title_de' => 'E-Commerce — eBay & Etsy', 'title_en' => 'E-Commerce — eBay & Etsy',
            'text_de' => 'Import & Verkauf von Großmarkt-Waren · hb3d.de · bc24.org · Eigenständige Marktanalyse',
            'text_en' => 'Import & sale of wholesale goods · hb3d.de · bc24.org · independent market analysis'],
        ['kind' => 'experience', 'period' => '2015–heute', 'title_de' => 'foto18.de — Gründer & Betreiber', 'title_en' => 'foto18.de — Founder & operator',
            'text_de' => 'Foto-Sharing-Plattform mit 12.000+ Nutzern und 2,8 Mio. hochgeladenen Fotos',
            'text_en' => 'Photo sharing platform with 12,000+ users and 2.8M uploaded photos'],
        ['kind' => 'experience', 'period' => '2014–heute', 'title_de' => 'BC24-Ökosystem — Gründer & Betreiber', 'title_en' => 'BC24 ecosystem — Founder & operator',
            'text_de' => 'bc24.org · Chat · Upload · Hosting · Affiliate · Telegram-Bots · n8n-Automatisierungen — alles selbst entwickelt',
            'text_en' => 'bc24.org · chat · upload · hosting · affiliate · Telegram bots · n8n automations — all self-developed'],
        ['kind' => 'experience', 'period' => '2009–heute', 'title_de' => 'Bremer Community — Gründer & Betreiber', 'title_en' => 'Bremer Community — Founder & operator',
            'text_de' => 'Soziales Netzwerk für Bremen & Umgebung · seit 2009 aktiv · mehrfach überarbeitet',
            'text_en' => 'Social network for Bremen & surroundings · active since 2009 · revamped multiple times'],
        ['kind' => 'experience', 'period' => '2001–2015', 'title_de' => 'Lagerlogistik & frühe Webprojekte', 'title_en' => 'Warehouse logistics & early web projects',
            'text_de' => 'Parallel zur Lagerarbeit: web-pood.de (2001), Frank-Panzer.de Blog (2004), untergrund-musik.de (2013) und weitere',
            'text_en' => 'Alongside warehouse work: web-pood.de (2001), Frank-Panzer.de blog (2004), untergrund-musik.de (2013) and more'],
    ],

    'tools' => [
        ['name' => 'Claude Code', 'icon' => 'brand-claude', 'url' => 'https://claude.com/claude-code'],
        ['name' => 'n8n', 'icon' => 'brand-n8n', 'url' => 'https://n8n.io'],
        ['name' => 'Telegram', 'icon' => 'brand-telegram', 'url' => 'https://telegram.org'],
        ['name' => 'WordPress', 'icon' => 'brand-wordpress', 'url' => 'https://wordpress.org'],
        ['name' => 'VS Code', 'icon' => 'code', 'url' => 'https://code.visualstudio.com'],
        ['name' => 'Plesk', 'icon' => 'brand-plesk', 'url' => 'https://www.plesk.com'],
        ['name' => 'TeamViewer', 'icon' => 'brand-teamviewer', 'url' => 'https://www.teamviewer.com'],
        ['name' => 'Photoshop / GIMP', 'icon' => 'brand-gimp', 'url' => 'https://www.gimp.org'],
        ['name' => 'phpMyAdmin', 'icon' => 'brand-phpmyadmin', 'url' => 'https://www.phpmyadmin.net'],
        ['name' => 'WooCommerce', 'icon' => 'brand-woocommerce', 'url' => 'https://woocommerce.com'],
    ],

    'music_genres' => [
        ['icon' => 'mic', 'name' => 'Rap'],
        ['icon' => 'zap', 'name' => 'Hardstyle'],
        ['icon' => 'audio-lines', 'name' => 'Techno'],
    ],

    'faq' => [
        ['q_de' => 'Wie bist du Webentwickler geworden?', 'q_en' => 'How did you become a web developer?',
            'a_de' => 'Ich habe 2001 einfach angefangen — ohne Studium, ohne Kurs. Neugier war der einzige Antrieb. Später habe ich zwei offizielle IT-Ausbildungen abgeschlossen (Fachinformatiker Systemintegration und Anwendungsentwicklung), aber das Grundwissen kam alles durch Selbststudium.',
            'a_en' => 'I simply started in 2001 — no degree, no course. Curiosity was the only driver. Later I completed two official IT apprenticeships, but the foundational knowledge all came through self-study.'],
        ['q_de' => 'Machst du alles wirklich alleine?', 'q_en' => 'Do you really do everything alone?',
            'a_de' => 'Ja — alle Websites, Bots, Automatisierungen und Projekte plane und baue ich selbst. Kein Team, kein Büro. Nur ich, ein Rechner und Claude Code als KI-Unterstützung.',
            'a_en' => 'Yes — I plan and build all websites, bots, automations, and projects myself. No team, no office. Just me, a computer, and Claude Code as AI support.'],
        ['q_de' => 'Wie kann ich dein Partner werden?', 'q_en' => 'How can I become your partner?',
            'a_de' => 'Schreib mir einfach über das Kontaktformular auf dieser Seite. Wähle als Betreff „Partner-Anfrage“ und beschreib kurz, wie du dir eine Zusammenarbeit vorstellst. Ich melde mich dann bei dir.',
            'a_en' => 'Just write to me via the contact form on this page. Choose “Partner inquiry” as the subject and briefly describe how you envision a collaboration. I’ll get back to you.'],
        ['q_de' => 'Bietest du Webdesign-Dienstleistungen an?', 'q_en' => 'Do you offer web design services?',
            'a_de' => 'Ja! Über meine Agentur Panzer IT biete ich professionelle Webentwicklung an — WordPress-Seiten, individuelle Lösungen, Wartung und Support. Mehr Infos auf <a href="https://panzerit.de" target="_blank" rel="noopener">panzerit.de</a>.',
            'a_en' => 'Yes! Through my agency Panzer IT I offer professional web development — WordPress sites, custom solutions, maintenance and support. More info at <a href="https://panzerit.de" target="_blank" rel="noopener">panzerit.de</a>.'],
        ['q_de' => 'Was ist Claude Code und warum bist du so begeistert?', 'q_en' => 'What is Claude Code and why are you so excited?',
            'a_de' => 'Claude Code ist ein KI-Assistent von Anthropic, der direkt im Terminal läuft und beim Programmieren hilft. Er kann Code schreiben, Bugs finden, Dateien bearbeiten und komplexe Aufgaben erledigen — fast wie ein zweiter Entwickler. Seitdem ich es nutze, hat sich meine Produktivität vervielfacht.',
            'a_en' => 'Claude Code is an AI assistant from Anthropic that runs directly in the terminal and helps with programming. It can write code, find bugs, edit files, and complete complex tasks — almost like a second developer. Since using it, my productivity has multiplied.'],
        ['q_de' => 'Wie kann ich dich am besten erreichen?', 'q_en' => 'How can I best reach you?',
            'a_de' => 'Am schnellsten über das Kontaktformular auf dieser Seite oder direkt per E-Mail an <a href="mailto:frank@panzerit.de">frank@panzerit.de</a>. Ich antworte in der Regel innerhalb eines Werktages.',
            'a_en' => 'Fastest via the contact form on this page or directly by email to <a href="mailto:frank@panzerit.de">frank@panzerit.de</a>. I usually respond within one working day.'],
    ],

    'partner_steps' => [
        ['title_de' => 'Kontakt aufnehmen', 'title_en' => 'Get in touch',
            'text_de' => 'Schreib mir über das Kontaktformular unten.', 'text_en' => 'Write to me via the contact form below.'],
        ['title_de' => 'Produkte empfehlen', 'title_en' => 'Refer products',
            'text_de' => 'Du empfiehlst mit deinem persönlichen Partner-Link.', 'text_en' => 'You refer with your personal partner link.'],
        ['title_de' => 'Provision erhalten', 'title_en' => 'Earn commission',
            'text_de' => 'Bei jedem Abschluss gibt es eine Provision für dich.', 'text_en' => 'For every successful deal you earn a commission.'],
    ],

    'social_links' => [
        ['icon' => 'brand-instagram', 'label' => 'Instagram', 'handle' => '@frankpanzer82', 'url' => 'https://www.instagram.com/frankpanzer82', 'in_hero' => 1],
        ['icon' => 'brand-github', 'label' => 'GitHub', 'handle' => 'bc24', 'url' => 'https://github.com/bc24', 'in_hero' => 1],
        ['icon' => 'brand-youtube', 'label' => 'YouTube · Panzer IT', 'handle' => '@panzerit', 'url' => 'https://www.youtube.com/@panzerit', 'in_hero' => 1],
        ['icon' => 'brand-tiktok', 'label' => 'TikTok · DJ-Frankus', 'handle' => '@dj.frankus', 'url' => 'https://www.tiktok.com/@dj.frankus', 'in_hero' => 1],
        ['icon' => 'brand-facebook', 'label' => 'Facebook · Bremer Community', 'handle' => 'bremercom', 'url' => 'https://www.facebook.com/bremercom', 'in_hero' => 1],
        ['icon' => 'brand-x', 'label' => 'X · Bremer Community', 'handle' => '@bremercommunity', 'url' => 'https://x.com/bremercommunity', 'in_hero' => 0],
        ['icon' => 'brand-youtube', 'label' => 'YouTube · Bremer Community', 'handle' => '@bremercom', 'url' => 'https://www.youtube.com/@bremercom', 'in_hero' => 0],
        ['icon' => 'users', 'label' => 'Bremer Community · Profil', 'handle' => '/Frank', 'url' => 'https://bremer-community.de/Frank', 'in_hero' => 0],
    ],

    'posts' => [
        ['slug' => 'frank-panzer-de-neu-gebaut', 'category_de' => 'Web & Projekte', 'category_en' => 'Web & Projects', 'icon' => 'code',
            'title_de' => 'frank-panzer.de — komplett neu gebaut', 'title_en' => 'frank-panzer.de — completely rebuilt',
            'excerpt_de' => 'Warum ich meine persönliche Website von Grund auf neu geschrieben habe — ohne Framework, ohne Build-Tool, nur pures Handwerk.',
            'excerpt_en' => 'Why I rebuilt my personal website from scratch — no framework, no build tool, pure craft.',
            'content_de' => $body('frank-panzer-de-neu-gebaut'), 'published_at' => '2026-05-15 10:00:00', 'likes' => 0],
        ['slug' => '25-jahre-online', 'category_de' => 'Persönlich', 'category_en' => 'Personal', 'icon' => 'history',
            'title_de' => '25 Jahre online — was ich gelernt habe', 'title_en' => '25 years online — what I’ve learned',
            'excerpt_de' => $intro('25-jahre-online'),
            'excerpt_en' => 'I started my first website in 2001. What 25 years of internet taught me — about the web, about myself, and about persistence.',
            'content_de' => $body('25-jahre-online'), 'published_at' => '2026-05-20 09:00:00', 'likes' => 0],
        ['slug' => 'solo-developer-tools', 'category_de' => 'Web & Projekte', 'category_en' => 'Web & Projects', 'icon' => 'wrench',
            'title_de' => 'Meine besten Tools als Solo-Entwickler', 'title_en' => 'My best tools as a solo developer',
            'excerpt_de' => $intro('solo-developer-tools'),
            'excerpt_en' => 'Claude Code, n8n, Telegram bots, Raspberry Pi — these tools make me more productive daily and replace a whole team.',
            'content_de' => $body('solo-developer-tools'), 'published_at' => '2026-05-20 12:00:00', 'likes' => 0],
    ],

    'pages' => [
        ['slug' => 'impressum', 'title_de' => 'Impressum', 'title_en' => 'Legal notice', 'subtitle_de' => 'Anbieterkennzeichnung gemäß § 5 Digitale-Dienste-Gesetz (DDG)',
            'subtitle_en' => 'Provider identification pursuant to § 5 DDG (Germany)', 'in_footer' => 1, 'sort' => 10,
            'content_de' => <<<'HTML'
<h2>Angaben gemäß § 5 DDG</h2>
<p><strong>Frank Panzer</strong><br>Panzer IT — Webdesign &amp; IT-Dienstleistungen<br>Kreinsloger 103<br>28777 Bremen</p>
<h2>Kontakt</h2>
<p>E-Mail: <a href="mailto:frank@panzerit.de">frank@panzerit.de</a><br>Website: frank-panzer.de</p>
<h2>Umsatzsteuer-ID</h2>
<p>Umsatzsteuer-Identifikationsnummer gemäß § 27 a UStG:<br>DE 369836826</p>
<h2>Berufsbezeichnung</h2>
<p>Webentwickler, IT-Dienstleister (selbstständig in Deutschland)</p>
<h2>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
<p>Frank Panzer<br>Kreinsloger 103<br>28777 Bremen</p>
<h2>Haftung für Inhalte</h2>
<p>Als Diensteanbieter sind wir gemäß § 7 Abs. 1 DDG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach den §§ 8 bis 10 DDG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.</p>
<p>Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Bei Bekanntwerden von entsprechenden Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.</p>
<h2>Haftung für Links</h2>
<p>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links umgehend entfernen.</p>
<h2>Urheberrecht</h2>
<p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Downloads und Kopien dieser Seite sind nur für den privaten, nicht kommerziellen Gebrauch gestattet.</p>
HTML,
            'content_en' => ''],
        ['slug' => 'datenschutz', 'title_de' => 'Datenschutz', 'title_en' => 'Privacy policy', 'subtitle_de' => 'Datenschutzerklärung gemäß DSGVO (EU) 2016/679 · Stand: September 2026',
            'subtitle_en' => 'Privacy policy according to GDPR (EU) 2016/679 · As of September 2026', 'in_footer' => 1, 'sort' => 20,
            'content_de' => <<<'HTML'
<h2>1. Verantwortliche Stelle</h2>
<p>Verantwortlicher für die Verarbeitung personenbezogener Daten auf dieser Website im Sinne der DSGVO ist:</p>
<p>Frank Panzer / Panzer IT<br>Kreinsloger 103<br>28777 Bremen<br>E-Mail: <a href="mailto:frank@panzerit.de">frank@panzerit.de</a></p>
<h2>2. Was erfassen wir?</h2>
<p>Beim Besuch dieser Website werden folgende Daten verarbeitet:</p>
<ul>
<li><strong>Technische Zugriffsdaten (Server-Logs):</strong> IP-Adresse, Browsertyp, Betriebssystem, aufgerufene Seiten, Datum und Uhrzeit — automatisch durch den Webhoster erfasst</li>
<li><strong>Kontaktformular-Daten:</strong> Name, E-Mail-Adresse, Betreff, Nachricht — nur wenn Sie das Formular ausfüllen</li>
<li><strong>Kommentare:</strong> Name und Kommentartext — nur wenn Sie einen Blog-Beitrag kommentieren</li>
<li><strong>Design- und Cookie-Einstellungen:</strong> Ihre Auswahl (heller/dunkler Modus, Hinweis-Banner) wird ausschließlich lokal in Ihrem Browser gespeichert (localStorage, kein Server-Zugriff)</li>
</ul>
<p>Wir verwenden kein Google Analytics, kein Facebook Pixel und keine sonstigen Tracking-Tools. Es werden keine externen Schriftarten, Skripte oder Inhalte von Drittanbietern nachgeladen.</p>
<h2>3. Server-Logs &amp; Hosting</h2>
<p>Diese Website wird auf einem eigenen Server gehostet. Der Server erfasst automatisch technische Zugriffsdaten. Diese Daten sind technisch notwendig für den Betrieb.</p>
<p>Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO. Speicherdauer: 7–30 Tage.</p>
<h2>4. Kontaktformular</h2>
<p>Wenn Sie das Kontaktformular nutzen, werden Ihre Angaben (Name, E-Mail-Adresse, Betreff, Nachricht) zur Bearbeitung Ihrer Anfrage in der Datenbank dieser Website gespeichert und per E-Mail an den Verantwortlichen weitergeleitet. Eine Weitergabe an Dritte erfolgt nicht. Zum Schutz vor Missbrauch (Spam) wird Ihre IP-Adresse nur in gehashter Form und nur kurzzeitig (maximal 24 Stunden) für eine Häufigkeitsbegrenzung verwendet.</p>
<p>Rechtsgrundlage: Art. 6 Abs. 1 lit. b/f DSGVO. Speicherdauer: bis zur abgeschlossenen Bearbeitung Ihrer Anfrage.</p>
<h2>5. Kommentare und „Gefällt mir“</h2>
<p>Bei Blog-Kommentaren speichern wir Name, Kommentartext und Zeitpunkt. Kommentare werden vor der Veröffentlichung geprüft. Für „Gefällt mir“-Angaben und zur Missbrauchsabwehr wird die IP-Adresse nur in gehashter Form und kurzzeitig (maximal 24 Stunden) verarbeitet.</p>
<p>Rechtsgrundlage: Art. 6 Abs. 1 lit. a/f DSGVO.</p>
<h2>6. Anonymer Besuchszähler</h2>
<p>Zur Auswertung der Reichweite werden die Seitenaufrufe pro Tag und Seite gezählt. Dabei werden weder IP-Adressen noch Cookies noch Kennungen gespeichert; eine Wiedererkennung von Besuchern ist nicht möglich.</p>
<h2>7. Schriftarten</h2>
<p>Alle Schriftarten (Space Grotesk, Inter, JetBrains Mono) werden lokal von diesem Server ausgeliefert. Es findet keine Verbindung zu Google-Servern statt.</p>
<h2>8. Cookies &amp; lokaler Speicher</h2>
<p>Diese Website setzt für Besucher ausschließlich technisch notwendige lokale Speicherung ein:</p>
<ul>
<li><strong>fp-consent (localStorage):</strong> Speichert Ihre Entscheidung zum Hinweis-Banner. Kein Server-Zugriff.</li>
<li><strong>fp-theme (localStorage):</strong> Speichert Ihre Design-Präferenz (hell/dunkel). Kein Server-Zugriff.</li>
<li><strong>fp-liked (localStorage):</strong> Merkt sich, welche Beiträge Sie mit „Gefällt mir“ markiert haben. Kein Server-Zugriff.</li>
</ul>
<p>Wir verwenden keine Analyse-, Marketing- oder Tracking-Cookies. Für den geschützten Verwaltungsbereich wird nach dem Login ein Sitzungs-Cookie gesetzt; dieser betrifft nur Administratoren.</p>
<h2>9. SSL-Verschlüsselung</h2>
<p>Diese Website nutzt SSL/TLS-Verschlüsselung (erkennbar am „https://“ in der Adresszeile).</p>
<h2>10. Ihre Rechte</h2>
<p>Sie haben folgende Rechte bezüglich Ihrer personenbezogenen Daten:</p>
<ul>
<li>Auskunftsrecht (Art. 15 DSGVO)</li>
<li>Recht auf Berichtigung (Art. 16 DSGVO)</li>
<li>Recht auf Löschung (Art. 17 DSGVO)</li>
<li>Recht auf Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
<li>Recht auf Datenübertragbarkeit (Art. 20 DSGVO)</li>
<li>Widerspruchsrecht (Art. 21 DSGVO)</li>
<li>Beschwerderecht bei der Aufsichtsbehörde (Art. 77 DSGVO)</li>
</ul>
<p>Zur Ausübung Ihrer Rechte wenden Sie sich bitte an: <a href="mailto:frank@panzerit.de">frank@panzerit.de</a></p>
<h2>11. Aufsichtsbehörde</h2>
<p>Die Landesbeauftragte für Datenschutz und Informationsfreiheit Bremen<br>Arndtstraße 1 · 27570 Bremerhaven<br>Telefon: 0421 / 361-2010<br>E-Mail: <a href="mailto:office@datenschutz.bremen.de">office@datenschutz.bremen.de</a><br><a href="https://www.datenschutz.bremen.de" target="_blank" rel="noopener">www.datenschutz.bremen.de</a></p>
<h2>12. Aktualität</h2>
<p>Diese Datenschutzerklärung hat den Stand September 2026. Änderungen werden auf dieser Seite veröffentlicht.</p>
HTML,
            'content_en' => ''],
    ],
];
