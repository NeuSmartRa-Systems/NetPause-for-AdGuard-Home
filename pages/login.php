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



// Wenn bereits eingeloggt, zur Startseite weiterleiten
if (isLoggedIn()) {
    header('Location: ?page=home');
    exit;
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $users = loadUsers();

    if (isset($users[$username]) && password_verify($password, $users[$username]['password_hash'])) {
        // Login erfolgreich
        session_regenerate_id(true);
        $_SESSION['user_id'] = $users[$username]['id'];

        // Weiterleitung zu ursprünglich aufgerufener Seite
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        header("Location: $redirect");
        exit;
    } else {
        $notification = [
            'message' => 'Benutzername oder Passwort falsch.',
            'type' => 'error'
        ];
    }
}
?>

<h2>Anmeldung</h2>
<form method="post" class="up-form">
    <input type="text" name="username" placeholder="Benutzername" required>
    <input type="password" name="password" placeholder="Passwort" required>
    <input type="submit" name="login" value="Anmelden">
</form>