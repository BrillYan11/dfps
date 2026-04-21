<?php

declare(strict_types=1);

if (!function_exists('dfps_helper_normalize_root_path')) {
    function dfps_helper_normalize_root_path(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/.');

        if ($normalized === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $normalized), static fn ($segment) => $segment !== ''));
        $deduped = [];

        foreach ($segments as $segment) {
            if ($segment === end($deduped)) {
                continue;
            }

            $deduped[] = $segment;
        }

        return implode('/', $deduped);
    }
}

if (!function_exists('dfps_helper_app_root')) {
    function dfps_helper_app_root(): string
    {
        $explicitRoot = $_SERVER['DFPS_APP_ROOT'] ?? null;
        if (is_string($explicitRoot) && $explicitRoot !== '') {
            $normalizedRoot = dfps_helper_normalize_root_path($explicitRoot);
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

        if ($rawPath !== '' && str_starts_with($rawPath, '/')) {
            return $rawPath;
        }

        $wantsTrailingSlash = $rawPath !== '' && str_ends_with($rawPath, '/');
        $normalized = trim($rawPath, '/');

        if ($normalized === '') {
            return $root === '' ? '/' : $root;
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
    function dfps_helper_redirect(string $path, int $statusCode = 302): never
    {
        header('Location: ' . dfps_helper_url($path), true, $statusCode);
        exit;
    }
}
