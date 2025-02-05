<?php
// Activer l'affichage des erreurs pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Vérifier la configuration
if (file_exists('config.php')) {
    $config = include('config.php');
} else {
    respondWithError('Missing config.php');
}

// Définition des paramètres IA
$IA_USED = $config['ia_used'] ?? 'openai';
$API_KEY = $IA_USED === 'gemini' ? ($config['api_key_gemini'] ?? null) : ($config['api_key_open_ai'] ?? null);
$MODEL = $IA_USED === 'gemini' ? ($config['gemini_model'] ?? 'gemini-1.5-pro-latest') : 'gpt-4o-mini';
$buildsDir = __DIR__ . '/builds';

// Vérifier la clé API
if (empty($API_KEY)) {
    respondWithError('API key is missing.');
}

// Lire et vérifier les données JSON reçues
$data = json_decode(file_get_contents('php://input'), true);
$description = $data['description'] ?? '';

if (empty($description)) {
    respondWithError('No description provided.');
}

// Numéro de build unique
$buildNumber = incrementBuildCount('build_count.txt');

// Construire le prompt
$prompt = buildPrompt($description);

// Appel de l'API pour générer le code
$generatedFiles = getGeneratedFiles($prompt, $API_KEY, $MODEL, $IA_USED);

if (!$generatedFiles || !isset($generatedFiles['files'])) {
    respondWithError('Invalid response format from AI API.', $generatedFiles);
}

// Vérifier et créer le dossier builds
if (!is_dir($buildsDir)) {
    mkdir($buildsDir, 0775, true);
}

// Sauvegarde des fichiers générés
$fileLinks = saveGeneratedFiles($generatedFiles, $buildNumber, $buildsDir);

if (empty($fileLinks)) {
    respondWithError('Failed to save generated files.');
}

// Retourne la réponse JSON correcte
echo json_encode(['links' => $fileLinks]);
exit;

/**
 * Fonction pour appeler l'API d'IA et récupérer les fichiers générés.
 */
function getGeneratedFiles($prompt, $API_KEY, $MODEL, $IA_USED) {
    $apiUrl = ($IA_USED === 'gemini') ?
        "https://generativelanguage.googleapis.com/v1beta/models/$MODEL:generateContent?key=$API_KEY" :
        "https://api.openai.com/v1/chat/completions";

    $postData = json_encode([
        'model' => $MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 10000
    ]);

    $headers = ['Content-Type: application/json'];
    if ($IA_USED === 'openai') {
        $headers[] = 'Authorization: Bearer ' . $API_KEY;
    }

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        logError('cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    // Vérification du JSON
    $responseData = json_decode($response, true);
    if (!$responseData) {
        logError('Invalid JSON response', $response);
        return null;
    }

    return $responseData;
}

/**
 * Fonction pour sauvegarder les fichiers générés.
 */
function saveGeneratedFiles($generatedFiles, $buildNumber, $buildsDir) {
    $fileLinks = [];

    foreach ($generatedFiles['files'] as $file) {
        $filename = "$buildsDir/build_$buildNumber" . '_' . $file['file_title'];
        $result = file_put_contents($filename, $file['content']);

        if ($result === false) {
            logError('Failed to write file', ['filename' => $filename]);
            return [];
        }

        $fileLinks[] = "/builds/build_$buildNumber" . '_' . $file['file_title'];
    }
    return $fileLinks;
}

/**
 * Fonction pour incrémenter le numéro de build.
 */
function incrementBuildCount($filePath) {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, 0);
    }
    $buildNumber = intval(file_get_contents($filePath)) + 1;
    file_put_contents($filePath, $buildNumber);
    return $buildNumber;
}

/**
 * Fonction pour construire le prompt à envoyer à l'IA.
 */
function buildPrompt($description) {
    return "You are a professional web developer. Generate a complete and valid HTML document based on: " . $description . ".\n\n" .
           "Return JSON in this format:\n" .
           "{\n" .
           '  "files": [\n' .
           '    {\n' .
           '      "file_title": "index.html",\n' .
           '      "content": "<HTML content>"\n' .
           "    }\n" .
           "  ]\n" .
           "}\n";
}

/**
 * Fonction pour répondre avec une erreur JSON.
 */
function respondWithError($message, $context = []) {
    http_response_code(400);
    echo json_encode(['error' => $message, 'details' => $context]);
    exit;
}

/**
 * Fonction pour enregistrer les erreurs dans un fichier.
 */
function logError($message, $context = []) {
    $logEntry = [
        'error' => $message,
        'context' => $context,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents('error_log.txt', json_encode($logEntry) . "\n", FILE_APPEND);
}
?>
