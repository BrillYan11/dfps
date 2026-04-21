<?php
// includes/lang_loader.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = $_SESSION['lang'] ?? 'en';
$lang_file = __DIR__ . "/lang/{$lang}.php";

if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/lang/en.php";
}

$translations = include $lang_file;

/**
 * Translates a key into the current language.
 * Usage: __t('dashboard')
 */
function __t($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
