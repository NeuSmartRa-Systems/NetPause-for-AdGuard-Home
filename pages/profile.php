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

$user = getCurrentUser();
$username = $user['username'];



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($old_password, $users[$username]['password_hash'])) {
        $notification = [
            'message' => 'Altes Passwort ist falsch.',
            'type' => 'error'
        ];
    } elseif ($new_password !== $confirm_password) {
        $notification = [
            'message' => 'Neue Passwörter stimmen nicht überein.',
            'type' => 'warning'
        ];
    } elseif (strlen($new_password) < 8) {
        $notification = [
            'message' => 'Neues Passwort muss mindestens 8 Zeichen lang sein.',
            'type' => 'warning'
        ];
    } else {
        $users[$username]['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
        if (saveUsers($users)) {
            $success = 'Passwort erfolgreich geändert.';
        } else {
            $notification = [
                'message' => 'Fehler beim Speichern.',
                'type' => 'error'
            ];
        }
    }
}
?>

<h2>Profil von <?= htmlspecialchars($username) ?></h2>


<form method="post" class="up-form">
    <h3>Passwort ändern</h3>
    <input type="password" name="old_password" placeholder="Altes Passwort" required>
    <input type="password" name="new_password" placeholder="Neues Passwort (min. 8 Zeichen)" required>
    <input type="password" name="confirm_password" placeholder="Neues Passwort wiederholen" required>
    <input type="submit" name="change_password" value="Passwort ändern">
</form>