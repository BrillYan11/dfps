<?php

declare(strict_types=1);

$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    http_response_code(500);
    echo 'Flight dependencies are missing. Run "composer install" in the project root.';
    exit;
}

require $autoloadPath;

require_once __DIR__ . '/includes/url_helpers.php';

$_SERVER['DFPS_APP_ROOT'] = dfps_helper_normalize_root_path(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')));

$registerRoutes = require __DIR__ . '/config/routes.php';
$registerRoutes();

Flight::start();
