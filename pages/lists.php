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


requireLogin();

// Pfad für Listen
$listDir = __DIR__ . '/../lists/';
if (!is_dir($listDir)) {
    mkdir($listDir, 0755, true);
}

// Typ ermitteln (black/white)
$type = $_GET['type'] ?? 'black';
if (!in_array($type, ['black', 'white'])) {
    $type = 'black';
}
$typeLabel = ($type === 'black') ? 'Sperrliste' : 'Erlaubnisliste';

// Alle vorhandenen Dateien dieses Typs auflisten
$pattern = $type . '_*.txt';
$files = glob($listDir . $pattern);
$listNames = [];
foreach ($files as $f) {
    $base = basename($f);
    // Name ohne Präfix und Erweiterung: z.B. black_meine.txt -> meine
    $name = preg_replace('/^' . $type . '_(.*)\.txt$/', '$1', $base);
    $listNames[$base] = $name;
}
asort($listNames); // alphabetisch sortieren

// Aktuelle Datei aus GET oder erstes Element
$currentFile = $_GET['file'] ?? '';
if ($currentFile === '' && !empty($listNames)) {
    // erste Datei als Standard
    $currentFile = array_key_first($listNames);
}
// Prüfen, ob die Datei existiert (und zum Typ passt)
if ($currentFile !== '' && !in_array($currentFile, array_keys($listNames))) {
    $currentFile = ''; // ungültig
}

// Basis-URL für Listen
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // z.B. /AGP oder leer
$listBaseUrl = $protocol . '://' . $host . $basePath . '/lists/';

/**
 * Formatiert eine neue Regel entsprechend dem Listentyp.
 * Wenn die Regel bereits eine gültige AdGuard-Regel ist (beginnt mit ||, @@||, IP, Regex, Kommentar), wird sie unverändert übernommen.
 * Ansonsten wird sie als Domain interpretiert und mit ||...^ (black) oder @@||...^ (white) formatiert.
 */
function formatRule($rule, $type)
{
    $trimmed = trim($rule);
    if ($trimmed === '') return '';
    // Bereits formatierte AdGuard-Regel (beginnt mit || oder @@||)
    if (strpos($trimmed, '||') === 0 || strpos($trimmed, '@@||') === 0) {
        return $trimmed;
    }
    // IP-basierte Hosts-Datei (beginnt mit einer IP-Adresse)
    if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|0\.0\.0\.0|127\.0\.0\.1)\s+/', $trimmed)) {
        return $trimmed;
    }
    // Regex (beginnt und endet mit /)
    if (preg_match('/^\/.+\/$/', $trimmed)) {
        return $trimmed;
    }
    // Kommentar (beginnt mit ! oder #)
    if (strpos($trimmed, '!') === 0 || strpos($trimmed, '#') === 0) {
        return $trimmed;
    }
    // Ansonsten als Domain behandeln
    if ($type === 'black') {
        return '||' . $trimmed . '^';
    } else {
        return '@@||' . $trimmed . '^';
    }
}

/**
 * Bereitet eine Regel für die Anzeige vor (entfernt je nach Typ das Präfix und Suffix)
 */
function displayRule($rule, $type)
{
    $trimmed = trim($rule);
    // Wenn es eine typische AdGuard-Regel ist
    if ($type === 'black' && strpos($trimmed, '||') === 0 && substr($trimmed, -1) === '^') {
        // Entferne || am Anfang und ^ am Ende
        return substr($trimmed, 2, -1);
    }
    if ($type === 'white' && strpos($trimmed, '@@||') === 0 && substr($trimmed, -1) === '^') {
        // Entferne @@|| am Anfang und ^ am Ende
        return substr($trimmed, 4, -1);
    }
    // Ansonsten unverändert (IP, Regex, Kommentare, etc.)
    return $trimmed;
}

// Hilfsfunktion zum Lesen der Liste
function readList($file)
{
    $headers = [
        'Title' => 'AdGuard Parental Liste',
        'Description' => 'Benutzerdefinierte Liste',
        'Version' => '1.0',
        'Homepage' => '',
        'Expires' => '',
    ];
    $otherComments = []; // Alle !-Zeilen, die nicht bekannte Header sind (außer Last modified)
    $rules = [];

    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $inHeaders = true;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($inHeaders && strpos($trimmed, '!') === 0) {
                // Kopfzeile
                if (preg_match('/^!\s*([^:]+):\s*(.*)$/', $trimmed, $matches)) {
                    $key = trim($matches[1]);
                    $value = trim($matches[2]);
                    // Bekannte Header speichern, Last modified ignorieren (wird beim Speichern neu gesetzt)
                    if ($key === 'Title' || $key === 'Description' || $key === 'Version' || $key === 'Homepage' || $key === 'Expires') {
                        $headers[$key] = $value;
                    } elseif ($key !== 'Last modified') {
                        // Andere bekannte Header (wie 'Compiled by') als Kommentare speichern
                        $otherComments[] = $trimmed;
                    }
                } else {
                    // Kommentar ohne Doppelpunkt
                    $otherComments[] = $trimmed;
                }
            } else {
                $inHeaders = false;
                if ($trimmed !== '') {
                    $rules[] = $trimmed;
                }
            }
        }
    }
    return [$headers, $otherComments, $rules];
}

// Standard-Header für neue Liste
function defaultHeaders($type)
{
    return [
        'Title' => 'AdGuard Parental ' . ($type === 'black' ? 'Sperrliste' : 'Erlaubnisliste'),
        'Description' => 'Benutzerdefinierte ' . ($type === 'black' ? 'Sperrliste' : 'Erlaubnisliste'),
        'Version' => '1.0',
        'Homepage' => '',
        'Expires' => '',
    ];
}



// Flag, ob wir nach einem add/delete bereits aktuelle Daten haben (dann nicht neu aus Datei lesen)
$dataLoaded = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Neue Liste anlegen
    if ($action === 'new_list') {
        $newName = trim($_POST['new_list_name'] ?? '');
        if ($newName === '') {
            $notification = [
                'message' => 'Bitte einen Namen eingeben.',
                'type' => 'error'
            ];
        } else {
            // Dateiname sicher machen: nur alphanumerisch, Bindestrich, Unterstrich
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $newName);
            if ($safeName === '') $safeName = 'liste';
            $filename = $type . '_' . $safeName . '.txt';
            $filePath = $listDir . $filename;
            if (file_exists($filePath)) {
                $notification = [
                    'message' => 'Eine Liste mit diesem Namen existiert bereits.',
                    'type' => 'warning'
                ];
            } else {
                // Neue Datei mit Standard-Header anlegen
                $headers = defaultHeaders($type);
                $content = "! Title: " . $headers['Title'] . "\n";
                $content .= "! Description: " . $headers['Description'] . "\n";
                $content .= "! Version: " . $headers['Version'] . "\n";
                $content .= "! Last modified: " . gmdate('Y-m-d\TH:i:s\Z') . "\n\n";
                if (file_put_contents($filePath, $content)) {
                    $notification = [
                        'message' => 'Neue Liste wurde angelegt.',
                        'type' => 'success'
                    ];
                    // Weiterleitung zur neuen Liste
                    header('Location: ?page=lists&type=' . $type . '&file=' . urlencode($filename));
                    exit;
                } else {
                    $notification = [
                        'message' => 'Fehler beim Anlegen der Datei.',
                        'type' => 'error'
                    ];
                }
            }
        }
    }

    // 2. Liste löschen
    elseif ($action === 'delete_list') {
        $fileToDelete = $_POST['current_file'] ?? '';
        if ($fileToDelete !== '' && in_array($fileToDelete, array_keys($listNames))) {
            if (unlink($listDir . $fileToDelete)) {
                // Auch Backup löschen, falls vorhanden
                @unlink($listDir . $fileToDelete . '.backup');
                $notification = [
                    'message' => 'Liste wurde gelöscht.',
                    'type' => 'success'
                ];
                // Zurück zur Übersicht ohne Dateiauswahl
                header('Location: ?page=lists&type=' . $type);
                exit;
            } else {
                $notification = [
                    'message' => 'Fehler beim Löschen der Datei.',
                    'type' => 'error'
                ];
            }
        } else {
            $notification = [
                'message' => 'Ungültige Datei.',
                'type' => 'error'
            ];
        }
    }

    // 3. Bearbeitung der aktuellen Liste (add, delete, save, restore)
    elseif (in_array($action, ['add', 'delete', 'save', 'restore'])) {
        $currentFile = $_POST['current_file'] ?? '';
        if ($currentFile === '' || !in_array($currentFile, array_keys($listNames))) {
            $notification = [
                'message' => 'Keine gültige Liste ausgewählt.',
                'type' => 'error'
            ];
        } else {
            $filePath = $listDir . $currentFile;
            $backupPath = $filePath . '.backup';
            list($headers, $otherComments, $rules) = readList($filePath);
            $rulesJson = $_POST['rules_json'] ?? json_encode($rules);
            $currentRules = json_decode($rulesJson, true) ?? [];

            if ($action === 'add' && !empty($_POST['new_rule'])) {
                $newRule = formatRule($_POST['new_rule'], $type);
                if ($newRule !== '') {
                    $currentRules[] = $newRule;
                }
                // Nach add: Wir haben aktuelle Daten, setzen Flag
                $dataLoaded = true;
            } elseif ($action === 'delete' && isset($_POST['selected_rules'])) {
                $selected = $_POST['selected_rules'];
                $currentRules = array_values(array_diff_key($currentRules, array_flip($selected)));
                $dataLoaded = true;
            } elseif ($action === 'save') {
                $headers['Title'] = trim($_POST['title']);
                $headers['Description'] = trim($_POST['description']);
                $headers['Version'] = trim($_POST['version']);
                $headers['Homepage'] = trim($_POST['homepage']);
                $headers['Expires'] = trim($_POST['expires']);
                $otherComments = explode("\n", trim($_POST['other_comments']));
                $otherComments = array_filter(array_map('trim', $otherComments));
                $rules = $currentRules; // Regeln aus dem versteckten Feld

                // Backup erstellen
                if (file_exists($filePath)) {
                    copy($filePath, $backupPath);
                }

                // Datei schreiben
                $content = '';
                $content .= "! Title: " . $headers['Title'] . "\n";
                $content .= "! Description: " . $headers['Description'] . "\n";
                $content .= "! Version: " . $headers['Version'] . "\n";
                if (!empty($headers['Homepage'])) {
                    $content .= "! Homepage: " . $headers['Homepage'] . "\n";
                }
                // Nur einen aktuellen Last modified-Eintrag
                $content .= "! Last modified: " . gmdate('Y-m-d\TH:i:s\Z') . "\n";
                if (!empty($headers['Expires'])) {
                    $content .= "! Expires: " . $headers['Expires'] . "\n";
                }
                // Andere Kommentare (außer Last modified) wieder einfügen
                foreach ($otherComments as $comment) {
                    if (!empty($comment)) {
                        // Sicherstellen, dass es mit ! beginnt
                        $content .= (strpos($comment, '!') === 0 ? $comment : '! ' . $comment) . "\n";
                    }
                }
                $content .= "\n"; // Leerzeile nach Kopf
                foreach ($rules as $rule) {
                    $content .= $rule . "\n";
                }

                if (file_put_contents($filePath, $content) === false) {
                    $notification = [
                        'message' => 'Fehler beim Schreiben der Datei.',
                        'type' => 'error'
                    ];
                } else {
                    $notification = [
                        'message' => 'Liste gespeichert.',
                        'type' => 'success'
                    ];
                    // Nach save: Daten neu aus Datei lesen (für aktualisiertes Last modified etc.)
                    list($headers, $otherComments, $rules) = readList($filePath);
                    $currentRules = $rules;
                }
            } elseif ($action === 'restore') {
                if (file_exists($backupPath)) {
                    if (copy($backupPath, $filePath)) {
                        $notification = [
                            'message' => 'Backup wiederhergestellt.',
                            'type' => 'success'
                        ];
                        list($headers, $otherComments, $rules) = readList($filePath);
                        $currentRules = $rules;
                    } else {
                        $notification = [
                            'message' => 'Fehler beim Wiederherstellen.',
                            'type' => 'error'
                        ];
                    }
                } else {
                    $notification = [
                        'message' => 'Kein Backup vorhanden.',
                        'type' => 'error'
                    ];
                }
            }

            // Nach add/delete: $currentRules in $rules übernehmen
            if ($action === 'add' || $action === 'delete') {
                $rules = $currentRules;
            }
            // Für die Anzeige bereiten wir $rulesJson vor
            $rulesJson = json_encode($rules);
        }
    }
}

// Wenn nach POST die Seite neu geladen wird, müssen $listNames etc. aktualisiert werden
// (z.B. nach dem Anlegen einer neuen Liste wird vor dem Redirect neu geladen)
// Daher holen wir die Daten nochmal frisch
$files = glob($listDir . $pattern);
$listNames = [];
foreach ($files as $f) {
    $base = basename($f);
    $name = preg_replace('/^' . $type . '_(.*)\.txt$/', '$1', $base);
    $listNames[$base] = $name;
}
asort($listNames);

// Wenn aktuell eine Datei ausgewählt ist, lesen wir sie für die Anzeige,
// aber nur, wenn wir nicht bereits aktuelle Daten aus add/delete haben.
if ($currentFile !== '' && isset($listNames[$currentFile])) {
    if (!$dataLoaded) {
        list($headers, $otherComments, $rules) = readList($listDir . $currentFile);
        $rulesJson = json_encode($rules);
    }
    // Sonst verwenden wir die bereits gesetzten $headers, $otherComments, $rules und $rulesJson aus dem POST-Block
} else {
    // Keine Liste ausgewählt – Standardwerte für leere Ansicht
    $headers = defaultHeaders($type);
    $otherComments = [];
    $rules = [];
    $rulesJson = '[]';
}

// Berechne die URL der aktuellen Liste (für den Kopier-Link)
$currentListUrl = '';
if ($currentFile !== '') {
    $currentListUrl = $listBaseUrl . urlencode($currentFile);
}
?>

<h2>Listenverwaltung</h2>

<!-- Tabs für Sperrlisten / Erlaubnislisten -->
<a href="?page=lists&type=black" class="sub-nav-link <?= $type === 'black' ? 'active' : '' ?>">Sperrlisten</a>
<a href="?page=lists&type=white" class="sub-nav-link <?= $type === 'white' ? 'active' : '' ?>">Erlaubnislisten</a>



<!-- Auswahlbereich für vorhandene Listen -->
<h3><?= $typeLabel ?> – Liste auswählen</h3>
<form method="get" action="" style="display: flex; gap: 10px; align-items: center;">
    <input type="hidden" name="page" value="lists">
    <input type="hidden" name="type" value="<?= $type ?>">
    <select name="file" style="flex: 1;">
        <option value="">-- Bitte wählen --</option>
        <?php foreach ($listNames as $filename => $displayName): ?>
            <option value="<?= htmlspecialchars($filename) ?>" <?= ($filename === $currentFile) ? 'selected' : '' ?>>
                <?= htmlspecialchars($displayName) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="button1">Auswählen</button>
</form>

<!-- Neue Liste anlegen -->
<form method="post" action="" style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
    <input type="hidden" name="action" value="new_list">
    <input type="hidden" name="type" value="<?= $type ?>">
    <input type="text" name="new_list_name" placeholder="Name der neuen Liste" style="flex: 1;">
    <button type="submit" class="button1">Neue Liste anlegen</button>
</form>

<?php if ($currentFile !== ''): ?>
    <!-- Löschen der aktuellen Liste -->
    <form method="post" action="" style="margin-top: 15px;" onsubmit="return confirm('Wirklich die gesamte Liste löschen? Diese Aktion kann nicht rückgängig gemacht werden.');">
        <input type="hidden" name="action" value="delete_list">
        <input type="hidden" name="current_file" value="<?= htmlspecialchars($currentFile) ?>">
        <button type="submit" class="button1" style="background:#8b3e3e;">Liste löschen</button>
    </form>
<?php endif; ?>

<?php if ($currentFile !== ''): ?>
    <!-- Listen-URL zum Kopieren für AdGuard -->
    <h3>Listen-URL für AdGuard</h3>
    <p>Fügen Sie diese URL in AdGuard Home unter <strong>Filter → Benutzerdefinierte Filter</strong> ein, um die Liste zu abonnieren:</p>
    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" id="list-url" value="<?= htmlspecialchars($currentListUrl) ?>" readonly style="flex: 1; font-family: monospace;">
        <button type="button" class="button1" onclick="copyUrl()">Kopieren</button>
    </div>

    <!-- Bearbeitungsbereich für die ausgewählte Liste -->
    <h3>Bearbeite: <?= htmlspecialchars($listNames[$currentFile]) ?></h3>

    <form method="post" id="list-form">
        <input type="hidden" name="action" id="action" value="">
        <input type="hidden" name="current_file" value="<?= htmlspecialchars($currentFile) ?>">
        <input type="hidden" name="rules_json" id="rules_json" value="<?= htmlspecialchars($rulesJson) ?>">

        <label>Titel:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($headers['Title']) ?>" style="width:100%;">

        <label>Beschreibung:</label>
        <input type="text" name="description" value="<?= htmlspecialchars($headers['Description']) ?>" style="width:100%;">

        <label>Version:</label>
        <input type="text" name="version" value="<?= htmlspecialchars($headers['Version']) ?>" style="width:100%;">

        <label>Homepage:</label>
        <input type="url" name="homepage" value="<?= htmlspecialchars($headers['Homepage']) ?>" style="width:100%;">

        <label>Ablauf (Tage, z.B. 7):</label>
        <input type="text" name="expires" value="<?= htmlspecialchars($headers['Expires']) ?>" style="width:100%;">

        <label>Weitere Kommentare (eine Zeile pro Kommentar, mit ! beginnend):</label>
        <textarea name="other_comments" rows="4" style="width:100%;"><?= htmlspecialchars(implode("\n", $otherComments)) ?></textarea>

        <hr>

        <h3>Regeln</h3>
        <div style="margin-bottom:10px;">
            <button type="button" class="button1" onclick="selectAll()">Alle auswählen</button>
            <button type="button" class="button1" onclick="deselectAll()">Alle abwählen</button>
        </div>

        <!-- Regeln als Tabelle mit bereinigter Anzeige -->
        <table style="width:100%; border-collapse: collapse;">
            <?php foreach ($rules as $index => $rule): ?>
                <tr>
                    <td style="width: 30px; vertical-align: top; padding: 2px 0;">
                        <input type="checkbox" name="selected_rules[]" value="<?= $index ?>" style="margin: 0;">
                    </td>
                    <td style="font-family: monospace; white-space: pre-wrap; word-break: break-all; padding-left: 5px;">
                        <?= htmlspecialchars(displayRule($rule, $type)) ?>
                        <!-- Tooltip mit vollständiger Regel (optional) -->
                        <span style="display: none;" class="full-rule"><?= htmlspecialchars($rule) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div style="margin-top: 20px;">
            <label>Neue Regel:</label>
            <input type="text" name="new_rule" id="new_rule" style="width:70%;" placeholder="z.B. example.com">
            <button type="button" class="button1" onclick="submitAction('add')">Hinzufügen</button>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="button" class="button1" onclick="submitAction('delete')">Ausgewählte löschen</button>
            <button type="button" class="button1" onclick="submitAction('save')">Speichern</button>
            <button type="button" class="button1" onclick="submitAction('restore')">Backup wiederherstellen</button>
        </div>
    </form>

    <script>
        function copyUrl() {
            var copyText = document.getElementById("list-url");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("URL wurde kopiert: " + copyText.value);
        }
    </script>
<?php endif; ?>

<script>
    function submitAction(action) {
        document.getElementById('action').value = action;
        document.getElementById('list-form').submit();
    }

    function selectAll() {
        document.querySelectorAll('input[name="selected_rules[]"]').forEach(cb => cb.checked = true);
    }

    function deselectAll() {
        document.querySelectorAll('input[name="selected_rules[]"]').forEach(cb => cb.checked = false);
    }
</script>

<style>
    .up-container label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    .up-container input,
    .up-container textarea {
        margin-bottom: 10px;
    }

    /* Optional: Hover-Effekt für Tabellenzeilen */
    #rules-list tr:hover {
        background-color: var(--card-bg-hover, #333);
    }
</style>