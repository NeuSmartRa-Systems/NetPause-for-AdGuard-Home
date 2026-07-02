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

$adguardConfig = loadAdGuardConfig();
if (!$adguardConfig) {
    $_SESSION['notification'] = [
        'message' => 'Bitte zuerst in den Einstellungen an Ihrer AdGuard-Home-Instanz anmelden.',
        'type' => 'warning'
    ];
    header('Location: ?page=settings');
    exit;
}

$deviceConfig = loadDeviceConfig();

// Clients von AdGuard abrufen (für die Auswahl)
$auth = [
    'user' => $adguardConfig['user'],
    'password' => $adguardConfig['password'],
    'url' => $adguardConfig['url']
];
$availableClients = [];
$clientResult = adguardRequest('/clients', 'GET', null, $auth);
if ($clientResult['code'] === 200) {
    $data = json_decode($clientResult['response'], true);
    $availableClients = $data['clients'] ?? [];
} else {
    $_SESSION['notification'] = [
        'message' => 'Fehler beim Abrufen der Geräte von AdGuard.',
        'type' => 'error'
    ];
    header('Location: ?page=settings');
    exit;
}

// Verfügbare Whitelists (Erlaubnislisten) ermitteln
$listDir = __DIR__ . '/../lists/';
$whitelistFiles = glob($listDir . 'white_*.txt');
$availableWhitelists = [];
foreach ($whitelistFiles as $file) {
    $base = basename($file);
    $display = preg_replace('/^white_(.*)\.txt$/', '$1', $base);
    $availableWhitelists[$base] = $display;
}
asort($availableWhitelists);

// Formular speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_devices'])) {
    $devices = [];

    if (isset($_POST['devices']) && is_array($_POST['devices'])) {
        foreach ($_POST['devices'] as $index => $devData) {
            $name = trim($devData['name'] ?? '');
            if ($name === '') continue;

            // Domains aus Textarea
            $domainsRaw = trim($devData['domains'] ?? '');
            $domains = [];
            if ($domainsRaw !== '') {
                $lines = explode("\n", $domainsRaw);
                foreach ($lines as $line) {
                    $domain = trim($line);
                    if ($domain !== '') $domains[] = $domain;
                }
            }

            // Intervalle
            $intervals = [];
            $starts = $devData['start'] ?? [];
            $ends = $devData['end'] ?? [];
            for ($i = 0; $i < count($starts); $i++) {
                if (!empty($starts[$i]) && !empty($ends[$i])) {
                    $intervals[] = ['start' => $starts[$i], 'end' => $ends[$i]];
                }
            }

            // Ausgewählte Whitelist-Dateien
            $whitelists = isset($devData['whitelists']) && is_array($devData['whitelists'])
                ? array_values($devData['whitelists'])
                : [];

            $devices[] = [
                'name' => $name,
                'domains' => $domains,
                'intervals' => $intervals,
                'whitelists' => $whitelists   // jetzt: Whitelists, nicht Blacklists
            ];
        }
    }

    saveDeviceConfig(['devices' => $devices]);
    $notification = [
        'message' => 'Geräte-Konfiguration gespeichert.',
        'type' => 'success'
    ];
    $deviceConfig = loadDeviceConfig(); // neu laden
}

$configuredDevices = $deviceConfig['devices'] ?? [];
?>
<h2>Steuerung</h2>

<form method="POST" action="">
    <div id="devices-container">
        <?php if (empty($configuredDevices)): ?>
            <!-- Neues Gerät (leere Konfiguration) -->
            <div class="device-card" style="background: var(--card-bg); padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                <h3>Neues Gerät</h3>
                <label>Gerätename auswählen:</label>
                <select name="devices[0][name]" required>
                    <option value="">-- Bitte wählen --</option>
                    <?php foreach ($availableClients as $client):
                        $name = $client['name'] ?? $client['ip'] ?? 'Unbekannt';
                    ?>
                        <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Whitelist (eine Domain pro Zeile):</label>
                <textarea name="devices[0][domains]" rows="3" placeholder="z.B. example.com&#10;example.org" style="width:100%; margin-bottom:10px;"></textarea>

                <!-- Auswahl zusätzlicher Whitelists -->
                <label>Zusätzliche Erlaubnislisten (Whitelists):</label>
                <small>Strg/Cmd + Klick für Mehrfachauswahl</small>

                <select name="devices[0][whitelists][]" multiple size="4" style="width:100%; margin-bottom:10px;">
                    <?php foreach ($availableWhitelists as $filename => $display): ?>
                        <option value="<?= htmlspecialchars($filename) ?>"><?= htmlspecialchars($display) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Zeitintervalle:</label>
                <div class="intervals-container">
                    <div class="interval-row">
                        <input type="time" name="devices[0][start][]">
                        <input type="time" name="devices[0][end][]">
                        <button type="button" class="button1" style="background:#8b3e3e;" onclick="this.parentElement.remove()">Entfernen</button>
                    </div>
                </div>
                <button type="button" class="button1 add-interval-btn" data-device="0">+ Intervall hinzufügen</button>
            </div>
        <?php else: ?>
            <?php foreach ($configuredDevices as $idx => $device): ?>
                <div class="device-card" style="background: var(--card-bg); padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                    <h3>Gerät: <?= htmlspecialchars($device['name']) ?></h3>
                    <label>Gerätename (aus AdGuard Home):</label>
                    <input type="text" name="devices[<?= $idx ?>][name]" value="<?= htmlspecialchars($device['name']) ?>" readonly>

                    <label>Whitelist (eine Domain pro Zeile):</label>
                    <textarea name="devices[<?= $idx ?>][domains]" rows="3" style="width:100%; margin-bottom:10px;"><?= htmlspecialchars(implode("\n", $device['domains'])) ?></textarea>

                    <!-- Auswahl zusätzlicher Whitelists mit Vorauswahl -->
                    <label>Zusätzliche Erlaubnislisten (Whitelists):</label>
                    <small>Strg/Cmd + Klick für Mehrfachauswahl</small>

                    <select name="devices[<?= $idx ?>][whitelists][]" multiple size="4" style="width:100%; margin-bottom:10px;">
                        <?php foreach ($availableWhitelists as $filename => $display): ?>
                            <?php $selected = in_array($filename, $device['whitelists'] ?? []) ? 'selected' : ''; ?>
                            <option value="<?= htmlspecialchars($filename) ?>" <?= $selected ?>><?= htmlspecialchars($display) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Zeitintervalle:</label>
                    <div class="intervals-container" id="intervals-<?= $idx ?>">
                        <?php if (!empty($device['intervals'])): ?>
                            <?php foreach ($device['intervals'] as $i): ?>
                                <div class="interval-row">
                                    <input type="time" name="devices[<?= $idx ?>][start][]" value="<?= htmlspecialchars($i['start']) ?>">
                                    <input type="time" name="devices[<?= $idx ?>][end][]" value="<?= htmlspecialchars($i['end']) ?>">
                                    <button type="button" class="button1" style="background:#8b3e3e;" onclick="this.parentElement.remove()">Entfernen</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="interval-row">
                                <input type="time" name="devices[<?= $idx ?>][start][]">
                                <input type="time" name="devices[<?= $idx ?>][end][]">
                                <button type="button" class="button1" style="background:#8b3e3e;" onclick="this.parentElement.remove()">Entfernen</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button1 add-interval-btn" data-device="<?= $idx ?>">+ Intervall hinzufügen</button>
                    <hr style="margin-top:15px;">
                    <button type="button" class="button1 remove-device-btn" style="background:#8b3e3e;">Gerät entfernen</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <button type="button" id="add-device" class="button1">+ Neues Gerät hinzufügen</button>
    <div class="notification-field info">
        <div class="notification-content">
            <span class="notification-icon">ℹ️</span>
            <span class="notification-message">
                Behalten Sie ein Gerät ohne erlaubten Domains mit der Uhrzeit 00:00 - 00:00, um alle Regeln aus den 'Benutzerdefinierten Filterregeln' in AdGuardHome zu entfernen. Ansonsten bleibt der letzte Konfigurationsstand in AdGuard Home bestehen.
            </span>
        </div>
    </div>
    <input type="submit" name="save_devices" value="Alle Geräte speichern">
</form>

<script>
    let deviceCounter = <?= count($configuredDevices) ?: 1 ?>;

    // Verfügbare Whitelists als JSON für JavaScript
    const availableWhitelists = <?= json_encode($availableWhitelists) ?>;

    document.getElementById('add-device')?.addEventListener('click', function() {
        const container = document.getElementById('devices-container');
        const newIndex = deviceCounter++;

        const clientOptions = `<?php
                                $opts = '';
                                foreach ($availableClients as $client) {
                                    $name = htmlspecialchars($client['name'] ?? $client['ip'] ?? 'Unbekannt', ENT_QUOTES);
                                    $opts .= "<option value=\\\"$name\\\">$name</option>";
                                }
                                echo $opts;
                                ?>`;

        // Whitelist-Optionen aus JSON erzeugen
        let whitelistOptions = '';
        for (const [filename, display] of Object.entries(availableWhitelists)) {
            whitelistOptions += `<option value="${filename}">${display}</option>`;
        }

        const html = `
        <div class="device-card" style="background: var(--card-bg); padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <h3>Neues Gerät</h3>
            <label>Gerätename auswählen:</label>
            <select name="devices[${newIndex}][name]" required>
                <option value="">-- Bitte wählen --</option>
                ${clientOptions}
            </select>

            <label>Whitelist (eine Domain pro Zeile):</label>
            <textarea name="devices[${newIndex}][domains]" rows="3" placeholder="z.B. example.com&#10;example.org" style="width:100%; margin-bottom:10px;"></textarea>

            <label>Zusätzliche Erlaubnislisten (Whitelists):</label>
                        <small>Strg/Cmd + Klick für Mehrfachauswahl</small>

            <select name="devices[${newIndex}][whitelists][]" multiple size="4" style="width:100%; margin-bottom:10px;">
                ${whitelistOptions}
            </select>

            <label>Zeitintervalle:</label>
            <div class="intervals-container">
                <div class="interval-row">
                    <input type="time" name="devices[${newIndex}][start][]">
                    <input type="time" name="devices[${newIndex}][end][]">
                    <button type="button" class="button1" style="background:#8b3e3e;" onclick="this.parentElement.remove()">Entfernen</button>
                </div>
            </div>
            <button type="button" class="button1 add-interval-btn" data-device="${newIndex}">+ Intervall hinzufügen</button>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
    });

    // Restlicher JS-Code (Intervall hinzufügen/entfernen) bleibt identisch
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-interval-btn')) {
            const deviceIdx = e.target.getAttribute('data-device');
            const card = e.target.closest('.device-card');
            if (card) {
                const container = card.querySelector('.intervals-container');
                if (container) {
                    const row = document.createElement('div');
                    row.className = 'interval-row';
                    row.innerHTML = `
                    <input type="time" name="devices[${deviceIdx}][start][]">
                    <input type="time" name="devices[${deviceIdx}][end][]">
                    <button type="button" class="button1" style="background:#8b3e3e;" onclick="this.parentElement.remove()">Entfernen</button>
                `;
                    container.appendChild(row);
                }
            }
        }

        if (e.target.classList.contains('remove-device-btn')) {
            if (confirm('Dieses Gerät wirklich entfernen?')) {
                const card = e.target.closest('.device-card');
                if (card) card.remove();
            }
        }
    });
</script>

<style>
    .interval-row {
        display: flex;
        gap: 10px;
        margin-bottom: 8px;
        align-items: center;
    }

    .interval-row input[type="time"] {
        flex: 1;
    }
</style>