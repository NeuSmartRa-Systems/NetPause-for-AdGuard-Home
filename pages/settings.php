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

// Verarbeitung des Login-Formulars
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $adguardUrl = trim($_POST['adguard_url']);

    $test = testAdGuardLogin($adguardUrl, $username, $password);

    if ($test['success']) {
        // Versuche, die Konfiguration zu speichern
        $saved = saveAdGuardConfig([
            'url' => $adguardUrl,
            'user' => $username,
            'password' => $password
        ]);
        if ($saved) {
            $_SESSION['notification'] = [
                'message' => 'Login erfolgreich. Verbindung zu AdGuard hergestellt.',
                'type' => 'success'
            ];
        } else {
            $_SESSION['notification'] = [
                'message' => 'Fehler beim Speichern der Konfiguration (Schreibrechte?).',
                'type' => 'error'
            ];
        }
    } else {
        $_SESSION['notification'] = [
            'message' => 'Login fehlgeschlagen: ' . ($test['error'] ?? 'Unbekannter Fehler'),
            'type' => 'error'
        ];
    }
    // Weiterleitung, um POST-Daten loszuwerden
    // header('Location: ?page=settings');
    // exit;
}

// Bestehende Konfiguration laden
$config = loadAdGuardConfig();
$notification = $_SESSION['notification'] ?? null;
unset($_SESSION['notification']);

if ($config) {
    $notification = [
        'message' => 'AdGuard-Zugangsdaten sind bereits gespeichert. Bei Änderung einfach neu anmelden.',
        'type' => 'success'
    ];
}
?>
<h2>AdGuardHome Anmeldung</h2>


<form class="up-form" method="POST" action="index.php?page=settings">
    <div class="notification-field info">
        <div class="notification-content">
            <span class="notification-icon">ℹ️</span>
            <span class="notification-message">
                Melden Sie sich in AdGuardHome an. Die Zugangsdaten werden verschlüsselt gespeichert.
            </span>
        </div>
    </div>

    <input type="text" name="username" placeholder="Benutzername" required>
    <input type="password" name="password" placeholder="Passwort" required>
    <input type="text" name="adguard_url" placeholder="AdGuard URL (z.B. http://200.3.208.28:80)" required>

    <input type="submit" name="login" value="Anmelden">
</form>