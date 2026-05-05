<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/lib/helpers.php';
require_once dirname(__DIR__) . '/lib/upload.php';
require_once dirname(__DIR__) . '/lib/auth.php';

seed_default_pages();
seed_default_products();
seed_default_admin();
