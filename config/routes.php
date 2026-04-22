<?php

declare(strict_types=1);

$dfpsRouteAliases = [
    '' => 'home.php',
    'home' => 'home.php',
    'index.php' => 'home.php',
    'index' => 'home.php',
    'login' => 'login.php',
    'login.php' => 'login.php',
    'register' => 'register.php',
    'register.php' => 'register.php',
    'forgot-password' => 'forgot_password.php',
    'forgot_password' => 'forgot_password.php',
    'forgot_password.php' => 'forgot_password.php',
    'reset-password' => 'reset_password.php',
    'reset_password' => 'reset_password.php',
    'reset_password.php' => 'reset_password.php',
    'logout' => 'logout.php',
    'logout.php' => 'logout.php',
    'check_users' => 'check_users.php',
    'check_users.php' => 'check_users.php',
    'update_db' => 'update_db.php',
    'update_db.php' => 'update_db.php',
    'initialize_database' => 'initialize_database.php',
    'initialize_database.php' => 'initialize_database.php',
    'debug_db' => 'debug_db.php',
    'debug_db.php' => 'debug_db.php',
    'buyer' => 'buyer/index.php',
    'buyer/index' => 'buyer/index.php',
    'buyer/index.php' => 'buyer/index.php',
    'buyer/announcements' => 'buyer/announcements.php',
    'buyer/announcements.php' => 'buyer/announcements.php',
    'buyer/get_posts' => 'buyer/get_posts.php',
    'buyer/get_posts.php' => 'buyer/get_posts.php',
    'buyer/message' => 'buyer/message.php',
    'buyer/message.php' => 'buyer/message.php',
    'buyer/notification' => 'buyer/notification.php',
    'buyer/notification.php' => 'buyer/notification.php',
    'buyer/view_post' => 'buyer/view_post.php',
    'buyer/view_post.php' => 'buyer/view_post.php',
    'farmer' => 'farmer/index.php',
    'farmer/index' => 'farmer/index.php',
    'farmer/index.php' => 'farmer/index.php',
    'farmer/add_post' => 'farmer/add_post.php',
    'farmer/add_post.php' => 'farmer/add_post.php',
    'farmer/announcements' => 'farmer/announcements.php',
    'farmer/announcements.php' => 'farmer/announcements.php',
    'farmer/edit_post' => 'farmer/edit_post.php',
    'farmer/edit_post.php' => 'farmer/edit_post.php',
    'farmer/get_posts' => 'farmer/get_posts.php',
    'farmer/get_posts.php' => 'farmer/get_posts.php',
    'farmer/message' => 'farmer/message.php',
    'farmer/message.php' => 'farmer/message.php',
    'farmer/notification' => 'farmer/notification.php',
    'farmer/notification.php' => 'farmer/notification.php',
    'farmer/view_interests' => 'farmer/view_interests.php',
    'farmer/view_interests.php' => 'farmer/view_interests.php',
    'da' => 'da/index.php',
    'da/index' => 'da/index.php',
    'da/index.php' => 'da/index.php',
    'da/announcements' => 'da/announcements.php',
    'da/announcements.php' => 'da/announcements.php',
    'da/backup' => 'da/backup.php',
    'da/backup.php' => 'da/backup.php',
    'da/create_da' => 'da/create_da.php',
    'da/create_da.php' => 'da/create_da.php',
    'da/get_users' => 'da/get_users.php',
    'da/get_users.php' => 'da/get_users.php',
    'da/listings' => 'da/listings.php',
    'da/listings.php' => 'da/listings.php',
    'da/message' => 'da/message.php',
    'da/message.php' => 'da/message.php',
    'da/notification' => 'da/notification.php',
    'da/notification.php' => 'da/notification.php',
    'da/produce' => 'da/produce.php',
    'da/produce.php' => 'da/produce.php',
    'da/reports' => 'da/reports.php',
    'da/reports.php' => 'da/reports.php',
    'da/send_notification' => 'da/send_notification.php',
    'da/send_notification.php' => 'da/send_notification.php',
    'da/users' => 'da/users.php',
    'da/users.php' => 'da/users.php',
    'profile' => 'profile/index.php',
    'profile/index' => 'profile/index.php',
    'profile/index.php' => 'profile/index.php',
];

$dfpsAllowedRootScripts = [
    'check_users.php',
    'forgot_password.php',
    'home.php',
    'login.php',
    'logout.php',
    'register.php',
    'reset_password.php',
    'update_db.php',
    'initialize_database.php',
    'debug_db.php',
];

$dfpsAllowedDirectories = [
    'action',
    'buyer',
    'da',
    'farmer',
    'profile',
];

$dfpsExplicitScripts = [
    'includes/locations_api.php',
];

function dfps_application_base_path(): string
{
    if (isset($_SERVER['DFPS_APP_ROOT'])) {
        $basePath = function_exists('dfps_helper_normalize_root_path')
            ? dfps_helper_normalize_root_path((string) $_SERVER['DFPS_APP_ROOT'])
            : trim((string) $_SERVER['DFPS_APP_ROOT'], '/.');

        return $basePath === '' ? '' : $basePath;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $basePath = function_exists('dfps_helper_normalize_root_path')
        ? dfps_helper_normalize_root_path(dirname($scriptName))
        : trim(dirname($scriptName), '/.');

    return $basePath === '' ? '' : $basePath;
}

function dfps_app_root(): string
{
    $basePath = dfps_application_base_path();

    return '/' . ($basePath !== '' ? $basePath : '');
}

function dfps_url(string $path = ''): string
{
    $rawPath = str_replace('\\', '/', $path);
    $wantsTrailingSlash = $rawPath !== '' && str_ends_with($rawPath, '/');
    $normalizedPath = dfps_normalize_route_path($rawPath);
    $root = rtrim(dfps_app_root(), '/');

    if ($normalizedPath === '') {
        return $root === '' ? '/' : $root;
    }

    $url = ($root === '' ? '' : $root) . '/' . $normalizedPath;

    if ($wantsTrailingSlash) {
        $url .= '/';
    }

    return $url;
}

function dfps_asset(string $path): string
{
    return dfps_url($path);
}

function dfps_request_path(): string
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestPath = parse_url($requestUri, PHP_URL_PATH);

    if (!is_string($requestPath) || $requestPath === '') {
        return '';
    }

    return dfps_normalize_route_path($requestPath);
}

function dfps_request_query_string(): string
{
    $queryString = $_SERVER['QUERY_STRING'] ?? '';

    return $queryString === '' ? '' : '?' . $queryString;
}

function dfps_normalize_route_path(string $path): string
{
    $path = trim($path);
    $path = str_replace('\\', '/', $path);
    $path = trim($path, '/');

    if ($path === '') {
        return '';
    }

    $segments = array_values(array_filter(explode('/', $path), static fn ($segment) => $segment !== ''));

    return implode('/', $segments);
}

function dfps_is_allowed_route_path(string $path, array $allowedRootScripts, array $allowedDirectories, array $explicitScripts): bool
{
    if ($path === '' || str_contains($path, '..')) {
        return false;
    }

    if (!preg_match('/^[A-Za-z0-9_\\-\\/]+\\.php$/', $path)) {
        return false;
    }

    if (in_array($path, $explicitScripts, true)) {
        return true;
    }

    if (!str_contains($path, '/')) {
        return in_array($path, $allowedRootScripts, true);
    }

    $topLevelDirectory = strtok($path, '/');

    return in_array($topLevelDirectory, $allowedDirectories, true);
}

function dfps_resolve_route_target(string $requestedPath, array $aliases, array $allowedRootScripts, array $allowedDirectories, array $explicitScripts): ?string
{
    $normalizedPath = dfps_normalize_route_path($requestedPath);
    $basePath = dfps_application_base_path();

    if ($basePath !== '') {
        $basePrefix = $basePath . '/';

        while ($normalizedPath === $basePath || str_starts_with($normalizedPath, $basePrefix)) {
            if ($normalizedPath === $basePath) {
                $normalizedPath = '';
                break;
            }

            $normalizedPath = substr($normalizedPath, strlen($basePrefix));
        }
    }

    if (array_key_exists($normalizedPath, $aliases)) {
        return $aliases[$normalizedPath];
    }

    if ($normalizedPath !== '' && !str_ends_with($normalizedPath, '.php')) {
        $phpCandidate = $normalizedPath . '.php';

        if (array_key_exists($phpCandidate, $aliases)) {
            return $aliases[$phpCandidate];
        }

        if (dfps_is_allowed_route_path($phpCandidate, $allowedRootScripts, $allowedDirectories, $explicitScripts)) {
            return $phpCandidate;
        }
    }

    if (!dfps_is_allowed_route_path($normalizedPath, $allowedRootScripts, $allowedDirectories, $explicitScripts)) {
        return null;
    }

    return $normalizedPath;
}

function dfps_canonical_route_path(string $scriptPath): string
{
    $normalizedPath = dfps_normalize_route_path($scriptPath);

    if ($normalizedPath === 'home.php') {
        return '';
    }

    if ($normalizedPath === 'index.php') {
        return '';
    }

    if (str_ends_with($normalizedPath, '/index.php')) {
        return substr($normalizedPath, 0, -10) . '/';
    }

    if (str_ends_with($normalizedPath, '.php')) {
        return substr($normalizedPath, 0, -4);
    }

    return $normalizedPath;
}

function dfps_is_canonical_redirect_candidate(string $requestedPath): bool
{
    if ($requestedPath === '' || !str_ends_with($requestedPath, '.php')) {
        return false;
    }

    return !str_starts_with($requestedPath, 'action/');
}

function dfps_redirect_to_canonical_path(string $targetScriptPath): void
{
    $canonicalPath = dfps_canonical_route_path($targetScriptPath);
    $basePath = dfps_application_base_path();
    $location = '/' . ltrim(($basePath !== '' ? $basePath . '/' : '') . ltrim($canonicalPath, '/'), '/');

    if ($canonicalPath === '') {
        $location = '/' . ($basePath !== '' ? $basePath : '');
    }

    Flight::redirect($location . dfps_request_query_string(), 301);
}

function dfps_dispatch_script(string $relativePath): void
{
    $normalizedPath = dfps_normalize_route_path($relativePath);
    $absolutePath = __DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);

    if (!is_file($absolutePath)) {
        Flight::halt(404, 'Route target not found.');
    }

    $basePath = dfps_application_base_path();
    $scriptName = '/' . ltrim(($basePath !== '' ? $basePath . '/' : '') . $normalizedPath, '/');

    $_SERVER['DFPS_TARGET_SCRIPT'] = $scriptName;
    $_SERVER['SCRIPT_NAME'] = $scriptName;
    $_SERVER['PHP_SELF'] = $scriptName;
    $_SERVER['SCRIPT_FILENAME'] = $absolutePath;
    $_SERVER['DOCUMENT_URI'] = $scriptName;

    $originalWorkingDirectory = getcwd();
    chdir(dirname($absolutePath));

    require basename($absolutePath);

    if ($originalWorkingDirectory !== false) {
        chdir($originalWorkingDirectory);
    }
}

return static function () use ($dfpsRouteAliases, $dfpsAllowedRootScripts, $dfpsAllowedDirectories, $dfpsExplicitScripts): void {
    Flight::route('*', static function () use ($dfpsRouteAliases, $dfpsAllowedRootScripts, $dfpsAllowedDirectories, $dfpsExplicitScripts): void {
        $path = dfps_request_path();
        $target = dfps_resolve_route_target($path, $dfpsRouteAliases, $dfpsAllowedRootScripts, $dfpsAllowedDirectories, $dfpsExplicitScripts);

        if ($target === null) {
            Flight::halt(404, 'Page not found.');
        }

        if (dfps_is_canonical_redirect_candidate(dfps_normalize_route_path($path))) {
            dfps_redirect_to_canonical_path($target);
            return;
        }

        dfps_dispatch_script($target);
    });
};
