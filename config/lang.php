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

function translate_genres($genresString) {
    if (empty($genresString)) return '';
    
    // Découpe la chaine (ex: "Adventure, Indie")
    $parts = explode(',', $genresString);
    
    // Traduit chaque morceau
    $translatedParts = array_map(function($genre) {
        $genre = trim($genre);
        $key = 'genre_' . $genre;
        
        // Tente de traduire via la fonction __() existante
        // Si la traduction n'existe pas, on garde le mot anglais par sécurité
        $translated = __($key);
        return ($translated !== $key) ? $translated : $genre;
    }, $parts);
    
    // Recompose la chaine (ex: "Aventure, Indépendant")
    return implode(', ', $translatedParts);
}
?>