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


// requireLogin(); 

// Manuellen Cron-Job ausführen, wenn run_cron gesetzt ist
if (isset($_GET['run_cron']) && $_GET['run_cron'] === '1') {
    $currentUser = getCurrentUser();
    if ($currentUser && $currentUser['role'] === 'admin') {
        $cmd = 'php ' . __DIR__ . '/../cron.php > /dev/null 2>&1 &';
        exec($cmd);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    header('Location: ?page=install');
    exit;
}

$users = loadUsers();
$installiert = !empty($users);

$adguardConfig = loadAdGuardConfig();
$deviceConfig = loadDeviceConfig();
$currentUser = getCurrentUser();
$isAdmin = ($currentUser && $currentUser['role'] === 'admin');

// Hilfsfunktion: letzte N Zeilen einer Datei lesen
function tailFile($file, $lines = 20)
{
    if (!file_exists($file)) return [];
    $data = file($file);
    return array_slice($data, -$lines);
}

// Prüfe, ob Cron-Job kürzlich gelaufen ist (anhand der Log-Datei kinderschutz.log)
function getCronStatus()
{
    $logFile = __DIR__ . '/../kinderschutz.log';
    if (!file_exists($logFile)) return ['status' => 'unbekannt', 'last' => 'Nie', 'diff' => null];
    $lastModified = filemtime($logFile);
    $now = time();
    $diff = $now - $lastModified;
    if ($diff < 120) { // weniger als 2 Minuten her
        $status = 'aktiv';
    } elseif ($diff < 3600) {
        $status = 'verzögert';
    } else {
        $status = 'inaktiv';
    }
    return [
        'status' => $status,
        'last' => date('d.m.Y H:i:s', $lastModified),
        'diff' => $diff
    ];
}

// Teste AdGuard-Verbindung
function checkAdGuardConnection($config)
{
    if (!$config) return ['connected' => false, 'error' => 'Keine Konfiguration'];
    $auth = [
        'user' => $config['user'],
        'password' => $config['password'],
        'url' => $config['url']
    ];
    $result = adguardRequest('/status', 'GET', null, $auth);
    if ($result['code'] === 200) {
        $data = json_decode($result['response'], true);
        return ['connected' => true, 'version' => $data['version'] ?? '?'];
    } else {
        return ['connected' => false, 'error' => "HTTP {$result['code']}"];
    }
}

$cronStatus = getCronStatus();
$adguardStatus = checkAdGuardConnection($adguardConfig);

// Log-Dateien lesen
$logKinderschutz = tailFile(__DIR__ . '/../kinderschutz.log', 20);
$logCronError = tailFile(__DIR__ . '/../cron-error.log', 20);
?>

<h2>Systemstatus</h2>

<!-- Kompakte Statusübersicht -->
<?php if (!$installiert): ?>
    <form method="post" class="up-form">
        <input type="submit" name="install" value="Ersteinrichtung">
    </form>
<?php endif; ?>

<div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between;">
    <!-- Cron-Status -->
    <div style="flex: 1; min-width: 200px;">
        <h4 style="margin-bottom: 10px;">⏱️ Cron-Job</h4>
        <p>
            <?php if ($cronStatus['status'] === 'aktiv'): ?>
                <span style="color: #4CAF50;">✅ läuft (alle 1 Minute)</span>
            <?php elseif ($cronStatus['status'] === 'verzögert'): ?>
                <span style="color: #FF9800;">⚠️ letzte Ausführung vor >2 Min.</span>
            <?php else: ?>
                <span style="color: #f44336;">❌ inaktiv (letzte Ausführung vor >1 Std.)</span>
            <?php endif; ?>
        </p>
        <p style="font-size: 0.9rem;">Letzte: <?= htmlspecialchars($cronStatus['last']) ?></p>
    </div>

    <!-- AdGuard-Status -->
    <div style="flex: 1; min-width: 200px;">
        <h4 style="margin-bottom: 10px;">🌐 AdGuard Home</h4>
        <p>
            <?php if ($adguardStatus['connected']): ?>
                <span style="color: #4CAF50;">✅ verbunden</span>
            <?php else: ?>
                <span style="color: #f44336;">❌ nicht verbunden (<?= htmlspecialchars($adguardStatus['error']) ?>)</span>
            <?php endif; ?>
        </p>
        <?php if ($adguardStatus['connected']): ?>
            <p style="font-size: 0.9rem;">Version: <?= htmlspecialchars($adguardStatus['version']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Manueller Cron-Button (nur Admin) -->
<?php if ($isAdmin): ?>
    <hr style="margin: 20px 0;">
    <div style="display: flex; justify-content: center;">
        <a href="?page=home&run_cron=1" class="button1">🔄 Jetzt aktualisieren (Cron manuell ausführen)</a>
    </div>
<?php endif; ?>

<!-- Log-Bereich: kinderschutz.log -->
<details>
    <summary style="cursor: pointer; font-weight: bold;">📋 kinderschutz.log (letzte 20 Zeilen)</summary>
    <div style="background: var(--card-bg); padding: 10px; border-radius: 5px; margin-top: 10px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
        <?php if (empty($logKinderschutz)): ?>
            <p>Keine Einträge vorhanden.</p>
        <?php else: ?>
            <?php foreach ($logKinderschutz as $line): ?>
                <?= htmlspecialchars($line) ?><br>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</details>

<!-- Log-Bereich: cron-error.log -->
<details>
    <summary style="cursor: pointer; font-weight: bold;">⚠️ cron-error.log (letzte 20 Zeilen)</summary>
    <div style="background: var(--card-bg); padding: 10px; border-radius: 5px; margin-top: 10px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
        <?php if (empty($logCronError)): ?>
            <p>Keine Einträge vorhanden.</p>
        <?php else: ?>
            <?php foreach ($logCronError as $line): ?>
                <?= htmlspecialchars($line) ?><br>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</details>