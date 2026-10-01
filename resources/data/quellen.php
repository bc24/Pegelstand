<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

// Ordnet Referrer-Domains einer Gruppe zu ("suche", "sozial") und fasst Domains desselben Dienstes zusammen.
// "muster" ist ein regulärer Ausdruck auf den Hostnamen ohne "www.". Domains ohne Treffer bleiben einzelne Referrer.
return [
    ['id' => 'google', 'name' => 'Google', 'gruppe' => 'suche', 'muster' => '/(^|\.)google\.[a-z.]{2,6}$/'],
    ['id' => 'bing', 'name' => 'Bing', 'gruppe' => 'suche', 'muster' => '/(^|\.)bing\.com$/'],
    ['id' => 'duckduckgo', 'name' => 'DuckDuckGo', 'gruppe' => 'suche', 'muster' => '/(^|\.)duckduckgo\.com$/'],
    ['id' => 'ecosia', 'name' => 'Ecosia', 'gruppe' => 'suche', 'muster' => '/(^|\.)ecosia\.org$/'],
    ['id' => 'startpage', 'name' => 'Startpage', 'gruppe' => 'suche', 'muster' => '/(^|\.)startpage\.com$/'],
    ['id' => 'brave', 'name' => 'Brave Search', 'gruppe' => 'suche', 'muster' => '/(^|\.)search\.brave\.com$/'],
    ['id' => 'yahoo', 'name' => 'Yahoo', 'gruppe' => 'suche', 'muster' => '/(^|\.)yahoo\.[a-z.]{2,6}$/'],
    ['id' => 'yandex', 'name' => 'Yandex', 'gruppe' => 'suche', 'muster' => '/(^|\.)yandex\.[a-z.]{2,6}$/'],
    ['id' => 'baidu', 'name' => 'Baidu', 'gruppe' => 'suche', 'muster' => '/(^|\.)baidu\.com$/'],
    ['id' => 'qwant', 'name' => 'Qwant', 'gruppe' => 'suche', 'muster' => '/(^|\.)qwant\.com$/'],
    ['id' => 'facebook', 'name' => 'Facebook', 'gruppe' => 'sozial', 'muster' => '/(^|\.)(facebook\.com|fb\.com|fb\.me)$/'],
    ['id' => 'instagram', 'name' => 'Instagram', 'gruppe' => 'sozial', 'muster' => '/(^|\.)instagram\.com$/'],
    ['id' => 'linkedin', 'name' => 'LinkedIn', 'gruppe' => 'sozial', 'muster' => '/(^|\.)(linkedin\.com|lnkd\.in)$/'],
    ['id' => 'youtube', 'name' => 'YouTube', 'gruppe' => 'sozial', 'muster' => '/(^|\.)(youtube\.com|youtu\.be)$/'],
    ['id' => 'xing', 'name' => 'XING', 'gruppe' => 'sozial', 'muster' => '/(^|\.)xing\.com$/'],
    ['id' => 'x', 'name' => 'X (Twitter)', 'gruppe' => 'sozial', 'muster' => '/(^|\.)(twitter\.com|x\.com|t\.co)$/'],
    ['id' => 'whatsapp', 'name' => 'WhatsApp', 'gruppe' => 'sozial', 'muster' => '/(^|\.)whatsapp\.com$/'],
    ['id' => 'mastodon', 'name' => 'Mastodon', 'gruppe' => 'sozial', 'muster' => '/(^|\.)(mastodon\.[a-z.]{2,12}|mstdn\.[a-z.]{2,12})$/'],
    ['id' => 'reddit', 'name' => 'Reddit', 'gruppe' => 'sozial', 'muster' => '/(^|\.)reddit\.com$/'],
    ['id' => 'pinterest', 'name' => 'Pinterest', 'gruppe' => 'sozial', 'muster' => '/(^|\.)pinterest\.[a-z.]{2,6}$/'],
    ['id' => 'tiktok', 'name' => 'TikTok', 'gruppe' => 'sozial', 'muster' => '/(^|\.)tiktok\.com$/'],
    ['id' => 'telegram', 'name' => 'Telegram', 'gruppe' => 'sozial', 'muster' => '/(^|\.)(t\.me|telegram\.org)$/'],
];
