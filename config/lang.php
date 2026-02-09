<?php
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang'])) {
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);

    if ($browser_lang === 'fr') {
        $_SESSION['lang'] = 'fr';
    }
}

$current_lang = $_SESSION['lang'] ?? 'en';

$lang_file = ROOT_PATH . "/lang/$current_lang.php";

if (!file_exists($lang_file)) {
    $lang_file = ROOT_PATH . "/lang/en.php";
}

$GLOBALS['translations'] = require $lang_file;

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
?>