<?php
// action/switch_language.php
session_start();
require_once '../includes/url_helpers.php';

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    // Only allow supported languages
    if (in_array($lang, ['en', 'ceb'])) {
        $_SESSION['lang'] = $lang;
    }
}

// Redirect back to the previous page
$fallback = dfps_helper_url();
$referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
header("Location: " . $referer);
exit;
