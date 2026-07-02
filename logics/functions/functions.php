<?php

/*
╔══════════════════════════════════════════════════════════╗
║                   NetPause -                             ║
║           Zeitschaltung für AdGuard Home                 ║
║                 developed by Neusmartra                  ║
║                      © 2026                              ║
║  Dieses Programm ist freie Software. Sie können es unter ║
║  den Bedingungen der GNU General Public License, wie     ║
║  von der Free Software Foundation veröffentlicht,        ║
║  Version 3 der Lizenz, weitergeben und/oder modifizieren.║
║  Siehe LICENSE-Datei für vollständige Bedingungen.       ║
╚══════════════════════════════════════════════════════════╝
 */



// Sicheren Pfad zur secret.php einbinden (außerhalb des Webroots)
define('KEYS_DIR', '/var/www/keys/');
require_once KEYS_DIR . 'secret.php';

define('CONFIG_FILE', KEYS_DIR . 'config.enc');
define('USERS_FILE', KEYS_DIR . 'users.enc');


// Prüfe, ob benötigte Erweiterungen geladen sind
if (!extension_loaded('curl')) {
    die('Fehler: PHP-cURL ist nicht installiert. Bitte aktivieren.');
}
if (!extension_loaded('openssl')) {
    die('Fehler: PHP-OpenSSL ist nicht installiert.');
}

/**
 * Stellt sicher, dass das Schlüsselverzeichnis existiert und beschreibbar ist.
 * @return bool
 */
function ensureKeysDirectory()
{
    if (!is_dir(KEYS_DIR)) {
        if (!mkdir(KEYS_DIR, 0750, true)) {
            error_log("Konnte Verzeichnis " . KEYS_DIR . " nicht erstellen.");
            return false;
        }
    }
    if (!is_writable(KEYS_DIR)) {
        error_log("Verzeichnis " . KEYS_DIR . " ist nicht beschreibbar.");
        return false;
    }
    return true;
}

/**
 * Verschlüsselt Daten mit AES-256-CBC
 * @param mixed $data
 * @return string|false
 */
function encryptData($data)
{
    $key = base64_decode(ENCRYPTION_KEY);
    $iv = random_bytes(16);
    $cipher = openssl_encrypt(json_encode($data), 'AES-256-CBC', $key, 0, $iv);
    if ($cipher === false) {
        error_log("openssl_encrypt fehlgeschlagen");
        return false;
    }
    return base64_encode($iv . $cipher);
}

/**
 * Entschlüsselt Daten
 * @param string $encrypted
 * @return mixed|false
 */
function decryptData($encrypted)
{
    $key = base64_decode(ENCRYPTION_KEY);
    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 16);
    $cipher = substr($data, 16);
    $decrypted = openssl_decrypt($cipher, 'AES-256-CBC', $key, 0, $iv);
    if ($decrypted === false) {
        error_log("openssl_decrypt fehlgeschlagen");
        return false;
    }
    return json_decode($decrypted, true);
}

/**
 * Lädt die verschlüsselte AdGuard-Konfiguration
 * @return array|null
 */
function loadAdGuardConfig()
{
    if (!file_exists(CONFIG_FILE)) return null;
    $content = file_get_contents(CONFIG_FILE);
    if ($content === false) return null;
    return decryptData($content);
}

/**
 * Speichert die AdGuard-Konfiguration verschlüsselt
 * @param array $config
 * @return bool
 */
function saveAdGuardConfig($config)
{
    if (!ensureKeysDirectory()) return false;
    $encrypted = encryptData($config);
    if ($encrypted === false) return false;
    if (file_put_contents(CONFIG_FILE, $encrypted) === false) {
        error_log("Konnte " . CONFIG_FILE . " nicht schreiben.");
        return false;
    }
    chmod(CONFIG_FILE, 0640);
    return true;
}

/**
 * Führt einen Request an die AdGuard-API aus
 * @param string $endpoint
 * @param string $method
 * @param mixed $data
 * @param array|null $auth
 * @param string|null $baseUrl
 * @return array
 */
function adguardRequest($endpoint, $method = 'GET', $data = null, $auth = null, $baseUrl = null)
{
    if (!$baseUrl && $auth && isset($auth['url'])) {
        $baseUrl = $auth['url'];
    }
    if (!$baseUrl) return ['code' => 0, 'response' => 'Keine Basis-URL'];

    $baseUrl = rtrim($baseUrl, '/') . '/control';
    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);
    if ($ch === false) {
        return ['code' => 0, 'response' => 'curl_init fehlgeschlagen'];
    }

    $headers = ['Content-Type: application/json'];

    if ($auth && isset($auth['user'], $auth['password'])) {
        $headers[] = 'Authorization: Basic ' . base64_encode($auth['user'] . ':' . $auth['password']);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['code' => 0, 'response' => 'cURL-Fehler: ' . $error];
    }
    curl_close($ch);
    return ['code' => $httpCode, 'response' => $response];
}

/**
 * Testet die AdGuard-Anmeldung (Status-Endpunkt)
 * @param string $url
 * @param string $user
 * @param string $pass
 * @return array
 */
function testAdGuardLogin($url, $user, $pass)
{
    $fullUrl = rtrim($url, '/') . '/control/status';
    $auth = base64_encode("$user:$pass");

    $ch = curl_init($fullUrl);
    if ($ch === false) {
        return ['success' => false, 'error' => 'curl_init fehlgeschlagen'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $auth]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_error($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => 'cURL-Fehler: ' . $error];
    }
    curl_close($ch);

    if ($httpCode === 200) {
        return ['success' => true, 'data' => json_decode($response, true)];
    } else {
        return ['success' => false, 'error' => "HTTP $httpCode"];
    }
}

/**
 * Lädt die Gerätekonfiguration (Liste von Geräten mit eigenen Domains und Intervallen)
 * @return array
 */
function loadDeviceConfig()
{
    $file = __DIR__ . '/../../device.json';
    if (!file_exists($file)) {
        return ['devices' => []];
    }
    $content = file_get_contents($file);
    if ($content === false) {
        return ['devices' => []];
    }
    $data = json_decode($content, true);
    if (!is_array($data)) {
        return ['devices' => []];
    }

    // Migration von altem Format (einzelnes Gerät mit gemeinsamen Domains/Intervallen)
    if (isset($data['clients']) && isset($data['allowed_domains'])) {
        $newDevices = [];
        foreach ($data['clients'] as $client) {
            $newDevices[] = [
                'name' => $client,
                'domains' => $data['allowed_domains'],
                'intervals' => $data['intervals'] ?? []
            ];
        }
        return ['devices' => $newDevices];
    }

    // Sicherstellen, dass devices existiert und korrekt ist
    if (!isset($data['devices']) || !is_array($data['devices'])) {
        $data['devices'] = [];
    }

    // Jedes Gerät sollte name, domains (Array) und intervals (Array) haben
    foreach ($data['devices'] as &$dev) {
        if (!isset($dev['name'])) $dev['name'] = 'Unbekannt';
        if (!isset($dev['domains']) || !is_array($dev['domains'])) $dev['domains'] = [];
        if (!isset($dev['intervals']) || !is_array($dev['intervals'])) $dev['intervals'] = [];
    }
    return $data;
}

/**
 * Speichert die Gerätekonfiguration
 * @param array $config
 * @return bool
 */
function saveDeviceConfig($config)
{
    $file = __DIR__ . '/../../device.json';
    // Sicherstellen, dass devices existiert und Array ist
    if (!isset($config['devices']) || !is_array($config['devices'])) {
        $config['devices'] = [];
    }
    // Daten bereinigen
    foreach ($config['devices'] as &$dev) {
        if (!is_array($dev)) continue;
        $dev['name'] = trim($dev['name'] ?? '');
        $dev['domains'] = array_values(array_filter(array_map('trim', $dev['domains'] ?? [])));
        $dev['intervals'] = array_values(array_filter($dev['intervals'] ?? [], function ($i) {
            return isset($i['start'], $i['end']) && !empty($i['start']) && !empty($i['end']);
        }));
    }
    $json = json_encode($config, JSON_PRETTY_PRINT);
    if ($json === false) return false;
    return file_put_contents($file, $json) !== false;
}
/**
 * Schreibt eine Log-Nachricht
 * @param string $msg
 */
function logMessage($msg)
{
    $logFile = __DIR__ . '/../../kinderschutz.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL, FILE_APPEND);
}







/**
 * Lädt alle Benutzer aus der verschlüsselten Datei
 * @return array
 */
function loadUsers()
{
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    $encrypted = file_get_contents(USERS_FILE);
    if ($encrypted === false) return [];
    $decrypted = decryptData($encrypted);
    return is_array($decrypted) ? $decrypted : [];
}

/**
 * Speichert Benutzer verschlüsselt
 * @param array $users
 * @return bool
 */
function saveUsers($users)
{
    $encrypted = encryptData($users);
    if ($encrypted === false) return false;
    if (file_put_contents(USERS_FILE, $encrypted) === false) {
        error_log("Konnte Benutzerdatei nicht schreiben: " . USERS_FILE);
        return false;
    }
    chmod(USERS_FILE, 0640);
    return true;
}

/**
 * Prüft, ob ein Benutzer eingeloggt ist
 */
function isLoggedIn()
{
    // Prüfe, ob eine User-ID in der Session existiert
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    // Lade alle Benutzer
    $users = loadUsers();
    foreach ($users as $username => $data) {
        if (isset($data['id']) && $data['id'] === $_SESSION['user_id']) {
            return true; // Gefunden => eingeloggt
        }
    }
    return false; // ID existiert in Session, aber kein passender Benutzer mehr?
}

/**
 * Erzwingt Login – leitet zur Login-Seite weiter, wenn nicht eingeloggt
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ?page=login'); // oder nur '?page=login', wenn die aktuelle Datei bereits index.php ist
        exit;
    }
}
/**
 * Gibt den aktuell eingeloggten Benutzer zurück (oder null)
 */
function getCurrentUser()
{
    if (!isLoggedIn()) return null;
    $users = loadUsers();
    foreach ($users as $username => $data) {
        if (isset($data['id']) && $data['id'] === $_SESSION['user_id']) {
            return [
                'username' => $username,
                'role' => $data['role'] ?? 'user'
            ];
        }
    }
    return null;
}

/**
 * Generiert eine eindeutige ID für Benutzer
 */
function generateUserId()
{
    return bin2hex(random_bytes(16));
}



/**
 * Liest eine AdGuard-Liste und gibt alle Regeln (ohne Kommentare) zurück.
 * @param string $file Pfad zur Liste
 * @return array
 */
function readListRules($file) {
    $rules = [];
    if (!file_exists($file)) return $rules;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        // Kommentarzeilen ignorieren (beginnen mit ! oder #)
        if (strpos($trimmed, '!') === 0 || strpos($trimmed, '#') === 0) {
            continue;
        }
        if ($trimmed !== '') {
            $rules[] = $trimmed;
        }
    }
    return $rules;
}

/**
 * Hängt den Client-Parameter an eine Regel an.
 * @param string $rule
 * @param string $client
 * @return string
 */
function addClientToRule($rule, $client) {
    // Wenn die Regel bereits ein $ enthält (Modifikatoren)
    if (strpos($rule, '$') !== false) {
        // Prüfen, ob bereits ein client= vorhanden ist (sollte nicht)
        if (preg_match('/\$.*\bclient=/', $rule)) {
            // bereits vorhanden – dann lassen wir die Regel unverändert
            return $rule;
        }
        // Ansonsten hängen wir ,client=... an das Ende des Modifikatorblocks an
        return $rule . ',client=' . $client;
    } else {
        // Keine Modifikatoren – einfach $client= anhängen
        return $rule . '$client=' . $client;
    }
}