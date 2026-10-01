<?php

declare(strict_types=1);

defined('PEGELSTAND_ROOT') || exit;

// Teilstrings (klein geschrieben), die einen User-Agent als Automat kennzeichnen. Gern erweitern.
return [
    'bot', 'crawler', 'spider', 'slurp', 'scrapy', 'archiver', 'fetcher', 'headless', 'phantomjs', 'puppeteer',
    'playwright', 'selenium', 'webdriver', 'lighthouse', 'pagespeed', 'gtmetrix', 'pingdom', 'uptime',
    'monitor', 'checker', 'validator', 'preview', 'facebookexternalhit', 'whatsapp', 'telegram', 'discord',
    'curl/', 'wget/', 'libwww', 'python-', 'aiohttp', 'httpx', 'go-http-client', 'java/', 'okhttp-bot',
    'apache-httpclient', 'node-fetch', 'axios/', 'postman', 'insomnia', 'feedfetcher', 'feedly', 'newsblur',
    'semrush', 'ahrefs', 'mj12', 'dotbot', 'petalbot', 'bytespider', 'gptbot', 'claudebot', 'ccbot',
    'yandex', 'baidu', 'sogou', 'duckduckgo-favicons', 'google-read-aloud', 'google-site-verification',
    'adsbot', 'mediapartners', 'chrome-lighthouse',
];
