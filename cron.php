#!/usr/bin/php
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


putenv('PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin');
require_once __DIR__ . '/logics/functions/functions.php';

// Logs jeden Sonntag leeren
if ((int)date('w') === 0) {
    file_put_contents(__DIR__ . '/kinderschutz.log', '');
    file_put_contents(__DIR__ . '/cron-error.log', '');
}

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/cron-error.log');

$adguardConfig = loadAdGuardConfig();
if (!$adguardConfig) {
    logMessage("Cron: Keine AdGuard-Konfiguration gefunden.");
    exit;
}

$deviceConfig = loadDeviceConfig();
if (empty($deviceConfig['devices'])) {
    logMessage("Cron: Keine Geräte konfiguriert.");
    exit;
}

$auth = [
    'user' => $adguardConfig['user'],
    'password' => $adguardConfig['password'],
    'url' => $adguardConfig['url']
];

$now = date('H:i');
$expectedRules = [];

// Pfad zu den Listen
$listDir = __DIR__ . '/lists/';

// Für jedes Gerät prüfen, ob es aktuell in einem seiner Intervalle ist
foreach ($deviceConfig['devices'] as $device) {
    $client = trim($device['name']);
    if ($client === '') continue;

    // In cron.php: Ersetze die Intervall-Prüfung
    $inInterval = false;
    foreach ($device['intervals'] as $interval) {
        if (isset($interval['start'], $interval['end'])) {
            $start = $interval['start'];
            $end = $interval['end'];
            // Prüfen, ob Intervall über Mitternacht geht (Ende < Start)
            if ($end < $start) {
                // Über Mitternacht: aktiv wenn jetzt >= Start ODER jetzt <= Ende
                if ($now >= $start || $now <= $end) {
                    $inInterval = true;
                    break;
                }
            } else {
                // Normales Intervall am selben Tag
                if ($now >= $start && $now <= $end) {
                    $inInterval = true;
                    break;
                }
            }
        }
    }

    if ($inInterval) {
        // Blockierregel für dieses Gerät
        $expectedRules[] = "||*^\$client=$client";

        // 1. Geräte-eigene Whitelist (Domains)
        foreach ($device['domains'] as $domain) {
            $domain = trim($domain);
            if ($domain === '') continue;
            $expectedRules[] = "@@||$domain^\$client=$client";
        }

        // 2. Zusätzliche Whitelists aus der Auswahl
        if (!empty($device['whitelists']) && is_array($device['whitelists'])) {
            foreach ($device['whitelists'] as $listFile) {
                $listPath = $listDir . $listFile;
                if (!file_exists($listPath)) {
                    logMessage("Cron: Whitelist-Datei nicht gefunden: $listFile");
                    continue;
                }
                $listRules = readListRules($listPath);   // extrahiert alle nicht-Kommentar-Zeilen
                foreach ($listRules as $rule) {
                    $expectedRules[] = addClientToRule($rule, $client);
                }
            }
        }
    }
}

// Aktuelle Regeln von AdGuard abrufen
$currentRules = [];
$result = adguardRequest('/filtering/status', 'GET', null, $auth);
if ($result['code'] === 200) {
    $data = json_decode($result['response'], true);
    $currentRules = $data['user_rules'] ?? [];
} else {
    logMessage("Cron: Fehler beim Abrufen der aktuellen Regeln (HTTP {$result['code']})");
    exit;
}

sort($expectedRules);
sort($currentRules);

if ($expectedRules != $currentRules) {
    $result = adguardRequest('/filtering/set_rules', 'POST', ['rules' => $expectedRules], $auth);
    if ($result['code'] === 200) {
        logMessage("Cron: Regeln aktualisiert (" . count($expectedRules) . " Regeln)");
    } else {
        logMessage("Cron: Fehler beim Setzen der Regeln (HTTP {$result['code']})");
    }
} else {
    logMessage("Cron: Regeln bereits korrekt, keine Änderung.");
}
