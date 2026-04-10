<?php
// Affiche les erreurs pour le debug
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// 1. LECTURE DU FICHIER .env
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die("Erreur : Le fichier .env est introuvable à la racine du projet.\n");
}

$envVariables = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
        $key = trim($parts[0]);
        $value = trim(trim($parts[1]), "\"'");
        $envVariables[$key] = $value;
    }
}

// 2. SÉCURITÉ WEBCRON
$secretToken = $envVariables['CRON_TOKEN'] ?? 'MotDePasseDeSecours123';
if (!isset($_GET['token']) || $_GET['token'] !== $secretToken) {
    http_response_code(403);
    die("Accès refusé. Token invalide ou manquant.");
}

ob_start();
echo "--- Début de l'importation WebCron du " . date('Y-m-d H:i:s') . " ---\n";

// 3. CONNEXION BDD
$host = $envVariables['DB_HOST'] ?? 'localhost';
$db_name = $envVariables['DB_NAME'] ?? '';
$username = $envVariables['DB_USER'] ?? '';
$password = $envVariables['DB_PASS'] ?? '';

try {
    $db = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    die("Erreur de connexion BDD : " . $exception->getMessage() . "\n");
}

$usd_to_eur_rate = 0.92; 

// Fonction de nettoyage
function normalizeString($string) {
    if (empty($string)) return '';
    $string = strtolower(trim($string));
    
    $unwanted_array = array('š'=>'s', 'ž'=>'z', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
    'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
    'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
    $string = strtr($string, $unwanted_array);

    $string = str_replace(['playstation 4', 'playstation 5'], ['ps4', 'ps5'], $string);
    $string = str_replace(['nintendo switch'], ['switch'], $string);
    $string = preg_replace('/[^a-z0-9]/', '', $string);
    return $string;
}

// Nouvelle détection des éditions spéciales
function isSpecialEdition($title) {
    $t = strtolower($title);
    
    // Si PriceCharting met des crochets, c'est une édition/variante
    if (strpos($t, '[') !== false) return true;
    
    // Mots clés pour vérifier la BDD
    $keywords = ['collector', 'limited', 'special', 'deluxe', 'ultimate', 'premium', 'steelbook', 'edition', 'collection'];
    foreach ($keywords as $word) {
        if (strpos($t, $word) !== false) {
            return true;
        }
    }
    return false;
}

$csvFile = __DIR__ . '/prices.csv'; 
if (!file_exists($csvFile)) {
    die("Erreur : Le fichier $csvFile est introuvable.\n");
}

$updatedCount = 0;
$notFoundCount = 0;

$stmt = $db->prepare("SELECT id, title, platform FROM games WHERE format = 'physical'");
$stmt->execute();
$dbGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ";", "\"", "\\")) !== FALSE) {
        if (count($data) < 3) continue;

        $csvTitle = $data[0]; 
        $csvPlatform = $data[1];
        
        $rawPrice = isset($data[2]) ? $data[2] : '0';
        $csvPriceUsd = floatval(preg_replace('/[^0-9.]/', '', $rawPrice)); 
        $csvPriceEur = round($csvPriceUsd * $usd_to_eur_rate, 2);

        // --- 1. DÉTECTION DES RÉGIONS (Libres dans le texte) ---
        $region = 'usa'; // Par défaut
        // On combine le titre et la plateforme pour chercher partout
        $combinedText = strtolower($csvTitle . ' ' . $csvPlatform);
        
        // On cherche les mots isolés (grâce à \b)
        if (preg_match('/\b(pal|eu|europe)\b/', $combinedText)) {
            $region = 'pal';
        } elseif (preg_match('/\b(jp|japan|ntsc-j)\b/', $combinedText)) {
            $region = 'jp';
        }

        // --- 2. NETTOYAGE POUR LA COMPARAISON ---
        // On retire le contenu des crochets et parenthèses
        $cleanCsvTitle = preg_replace('/\[.*?\]/', '', $csvTitle);
        $cleanCsvTitle = preg_replace('/\(.*?\)/', '', $cleanCsvTitle);
        
        // On retire les mots de région pour la comparaison (ex: "Sonic JP" devient "Sonic")
        $cleanCsvTitle = preg_replace('/\b(pal|eu|europe|jp|japan|usa|ntsc|ntsc-u|ntsc-j)\b/i', '', $cleanCsvTitle);
        $cleanCsvPlatform = preg_replace('/\b(pal|eu|europe|jp|japan|usa|ntsc|ntsc-u|ntsc-j)\b/i', '', $csvPlatform);

        $normalizedCsvTitle = normalizeString($cleanCsvTitle);
        $normalizedCsvPlatform = normalizeString($cleanCsvPlatform);

        $matchFound = false;

        foreach ($dbGames as $dbGame) {
            $normalizedDbTitle = normalizeString($dbGame['title']);
            $normalizedDbPlatform = normalizeString($dbGame['platform']);

            if ($normalizedCsvTitle === $normalizedDbTitle && $normalizedCsvPlatform === $normalizedDbPlatform) {
                
                // Vérification stricte des Éditions (Standard vs Collector/Crochets)
                $csvIsSpecial = isSpecialEdition($csvTitle);
                $dbIsSpecial = isSpecialEdition($dbGame['title']);

                // Si l'un est spécial (présence de crochets ou de mots-clés) et pas l'autre, on bloque !
                if ($csvIsSpecial !== $dbIsSpecial) {
                    continue; 
                }

                // --- 3. MISE À JOUR EN BASE DE DONNÉES ---
                $colName = 'price_' . $region;
                
                $sql = "UPDATE games SET {$colName} = :price";
                // Si c'est la région PAL, on met aussi à jour le prix estimé global
                if ($region === 'pal') {
                    $sql .= ", estimated_price = :price";
                }
                $sql .= " WHERE id = :id";

                $updateStmt = $db->prepare($sql);
                $updateStmt->execute([
                    ':price' => $csvPriceEur,
                    ':id' => $dbGame['id']
                ]);
                
                $updatedCount++;
                $matchFound = true;
                break; 
            }
        }

        if (!$matchFound) {
            $notFoundCount++;
        }
    }
    fclose($handle);
}

echo "Bilan : $updatedCount prix régionaux mis à jour.\n";
echo "Bilan : $notFoundCount lignes CSV ignorées ou éditions bloquées.\n";
echo "--- Fin de l'importation ---\n\n";

$logOutput = ob_get_clean();
file_put_contents(__DIR__ . '/import_prices.log', $logOutput, FILE_APPEND);
echo nl2br($logOutput);
?>