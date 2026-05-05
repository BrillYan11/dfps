<?php

declare(strict_types=1);

/**
 * Polyfill for str_starts_with (PHP < 8.0 compatibility)
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

/**
 * Polyfill for str_ends_with (PHP < 8.0 compatibility)
 */
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle !== '' && substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('dfps_helper_normalize_root_path')) {
    function dfps_helper_normalize_root_path(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/.');

        if ($normalized === '' || $normalized === 'index.php') {
            return '';
        }

        if (str_ends_with($normalized, '/index.php')) {
            $normalized = substr($normalized, 0, -10);
        }

        return $normalized;
    }
}

if (!function_exists('dfps_helper_app_root')) {
    function dfps_helper_app_root(): string
    {
        if (isset($_SERVER['DFPS_APP_ROOT'])) {
            $normalizedRoot = dfps_helper_normalize_root_path((string)$_SERVER['DFPS_APP_ROOT']);
            return $normalizedRoot === '' ? '' : '/' . $normalizedRoot;
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');

        if (preg_match('#^(.*?)(?:/action/|/buyer/|/farmer/|/da/|/profile/|/index\.php)#', $scriptName, $matches)) {
            $root = dfps_helper_normalize_root_path($matches[1]);
            return $root === '' ? '' : '/' . $root;
        }

        $root = dfps_helper_normalize_root_path(dirname($scriptName));
        return $root === '' ? '' : '/' . $root;
    }
}

if (!function_exists('dfps_helper_url')) {
    function dfps_helper_url(string $path = ''): string
    {
        $root = rtrim(dfps_helper_app_root(), '/');
        $rawPath = str_replace('\\', '/', $path);

        // If it's an external URL, return as is
        if (preg_match('#^(https?:)?//#', $rawPath)) {
            return $rawPath;
        }

        $wantsTrailingSlash = $rawPath !== '' && str_ends_with($rawPath, '/');
        $normalized = trim($rawPath, '/');

        if ($normalized === '') {
            $url = $root === '' ? '/' : $root;
            if ($wantsTrailingSlash) {
                $url = rtrim($url, '/') . '/';
            }
            return $url;
        }

        $url = ($root === '' ? '' : $root) . '/' . $normalized;
        return $wantsTrailingSlash ? $url . '/' : $url;
    }
}

if (!function_exists('dfps_helper_asset')) {
    function dfps_helper_asset(string $path): string
    {
        return dfps_helper_url($path);
    }
}

if (!function_exists('dfps_helper_redirect')) {
    function dfps_helper_redirect(string $path, int $statusCode = 302)
    {
        header('Location: ' . dfps_helper_url($path), true, $statusCode);
        exit;
    }
}
