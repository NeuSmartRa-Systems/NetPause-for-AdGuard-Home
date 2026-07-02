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



if (!empty($users)) {
    // Es gibt bereits Benutzer -> weiterleiten zum Login
    header('Location: ?page=login');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($username) || empty($password)) {
        $notification = [
            'message' => 'Bitte Benutzername und Passwort eingeben.',
            'type' => 'info'
        ];
    } elseif ($password !== $password_confirm) {
        $notification = [
            'message' => 'Passwörter stimmen nicht überein.',
            'type' => 'error'
        ];
    } elseif (strlen($password) < 8) {
        $notification = [
            'message' => 'Passwort muss mindestens 8 Zeichen lang sein.',
            'type' => 'warning'
        ];
    } else {
        $users = [
            $username => [
                'id' => generateUserId(),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        if (saveUsers($users)) {
            $notification = [
                'message' => 'Admin-Benutzer wurde angelegt. Sie können sich jetzt anmelden.',
                'type' => 'success'
            ];
            // Weiterleitung zum Login nach 2 Sekunden
            header('Refresh: 2');
        } else {
            $notification = [
                'message' => 'Fehler beim Speichern der Benutzerdaten. Prüfen Sie die Schreibrechte in /var/www/keys.',
                'type' => 'error'
            ];
        }
    }
}
?>

<h2>Ersteinrichtung</h2>
<p>Willkommen bei AdGuard Parental. Bitte legen Sie den ersten Admin-Benutzer an.</p>

<form method="post" class="up-form">
    <input type="text" name="username" placeholder="Admin-Benutzername" required>
    <input type="password" name="password" placeholder="Passwort (mind. 8 Zeichen)" required>
    <input type="password" name="password_confirm" placeholder="Passwort wiederholen" required>
    <input type="submit" name="install" value="Admin anlegen">
</form>