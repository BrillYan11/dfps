<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    http_response_code(500);
    echo 'Flight dependencies are missing. Run "composer install" in the project root.';
    exit;
}

require $autoloadPath;

// Handle static files for the PHP built-in server
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file) && !str_ends_with($file, '.php')) {
        $mimetypes = [
            'css' => 'text/css',
            'js'  => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg'=> 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        ];
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (isset($mimetypes[$extension])) {
            header("Content-Type: " . $mimetypes[$extension]);
        }
        readfile($file);
        return true;
    }
}

require_once __DIR__ . '/includes/url_helpers.php';

$registerRoutes = require __DIR__ . '/config/routes.php';
$registerRoutes();

Flight::start();
