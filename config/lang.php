<?php
// 1. Détection ou Changement de langue
if (isset($_GET['lang'])) {
    // Si l'utilisateur clique sur un drapeau, on change la session
    $_SESSION['lang'] = $_GET['lang'];
}

// 2. Définir la langue actuelle (Anglais par défaut si rien en session)
$current_lang = $_SESSION['lang'] ?? 'en';

// 3. Charger le fichier de traduction correspondant
$lang_file = ROOT_PATH . "/lang/$current_lang.php";

// Fallback : si le fichier n'existe pas (ex: es.php), on charge l'anglais
if (!file_exists($lang_file)) {
    $lang_file = ROOT_PATH . "/lang/en.php";
}

// On charge le tableau dans une variable globale
$GLOBALS['translations'] = require $lang_file;

/**
 * Fonction helper pour récupérer une traduction
 * Utilisation : <?= __('menu_library') ?>
 */
function __($key) {
    global $translations;
    return $translations[$key] ?? $key; // Retourne la clé si pas de traduction trouvée
}
?>