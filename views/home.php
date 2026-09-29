<?php
/** @var array $sections */
require __DIR__ . '/sections/hero.php';
foreach ($sections as $sec) {
    $file = __DIR__ . '/sections/' . $sec['skey'] . '.php';
    if (is_file($file)) {
        include $file;
    }
}
