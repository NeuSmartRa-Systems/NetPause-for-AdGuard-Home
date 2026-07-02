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

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    header('Location: ?page=profile');
    exit;
}

$users = loadUsers();


// Neuen Benutzer anlegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_username = trim($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $new_role = $_POST['new_role'] ?? 'user';

    if (empty($new_username) || empty($new_password)) {
        $notification = [
            'message' => 'Bitte Benutzername und Passwort eingeben.',
            'type' => 'info'
        ];
    } elseif (isset($users[$new_username])) {
        $notification = [
            'message' => 'Benutzername existiert bereits.',
            'type' => 'error'
        ];
    } elseif (strlen($new_password) < 8) {
        $notification = [
            'message' => 'Passwort muss mindestens 8 Zeichen lang sein.',
            'type' => 'warning'
        ];
    } else {
        $users[$new_username] = [
            'id' => generateUserId(),
            'password_hash' => password_hash($new_password, PASSWORD_DEFAULT),
            'role' => $new_role,
            'created_at' => date('Y-m-d H:i:s')
        ];
        if (saveUsers($users)) {
            $notification = [
                'message' => 'Benutzer ' . $new_username . ' wurde angelegt.',
                'type' => 'success'
            ];
        } else {
            $notification = [
                'message' => 'Fehler beim Speichern.',
                'type' => 'error'
            ];
        }
    }
}

// Benutzer löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $delete_username = $_POST['target_username'];

    if ($delete_username !== $currentUser['username'] && isset($users[$delete_username])) {
        unset($users[$delete_username]);


        if (saveUsers($users)) {
            $notification = [
                'message' => 'Benutzer ' . $delete_username . ' wurde gelöscht.',
                'type' => 'success'
            ];
        } else {
            $notification = [
                'message' => 'Fehler beim Löschen.',
                'type' => 'error'
            ];
        }
    } else {
        $notification = [
            'message' => 'Sie können sich nicht selbst löschen.',
            'type' => 'error'
        ];
    }
}

// Passwort zurücksetzen (Admin kann Passwort ändern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $target_username = $_POST['target_username'];
    $new_password = $_POST['reset_new_password'];

    if (!isset($users[$target_username])) {
        $notification = [
            'message' => 'Benutzer nicht gefunden.',
            'type' => 'error'
        ];
    } elseif (strlen($new_password) < 8) {
        $notification = [
            'message' => 'Passwort muss mindestens 8 Zeichen lang sein.',
            'type' => 'warning'
        ];
    } else {
        $users[$target_username]['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
        if (saveUsers($users)) {
            $notification = [
                'message' => 'Passwort für ' . $target_username . ' wurde zurückgesetzt.',
                'type' => 'success'
            ];
        } else {
            $notification = [
                'message' => 'Fehler beim Speichern.',
                'type' => 'error'
            ];
        }
    }
}
?>
<h2>Benutzerverwaltung</h2>


<h3>Neuen Benutzer anlegen</h3>
<form method="post" class="up-form">
    <input type="text" name="new_username" placeholder="Benutzername" required>
    <input type="password" name="new_password" placeholder="Passwort (min. 8 Zeichen)" required>
    <select name="new_role">
        <option value="user">Benutzer</option>
        <option value="admin">Admin</option>
    </select>
    <input type="submit" name="add_user" value="Benutzer anlegen">
</form>

<h3>Vorhandene Benutzer</h3>
<table style="width:100%; border-collapse: collapse;">
    <tr>
        <th>Benutzername</th>
        <th>Rolle</th>
        <th>Aktionen</th>
    </tr>
    <?php foreach ($users as $username => $data): ?>
        <tr>
            <td><?= htmlspecialchars($username) ?></td>
            <td><?= htmlspecialchars($data['role'] ?? 'user') ?></td>
            <td>
                <?php if ($username !== $currentUser['username']): ?>
                    <form method="post">
                        <input type="hidden" name="target_username" value="<?= htmlspecialchars($username) ?>">
                        <input type="password" name="reset_new_password" placeholder="Neues Passwort">
            <td>
                <button type="submit" name="reset_password" class="input">Passwort zurücksetzen</button>
            </td>
            </form>
            <td>
                <form method="post">
                    <input type="hidden" name="target_username" value="<?= htmlspecialchars($username) ?>">
                    <button type="submit" name="delete" class="input" style="background:#8b3e3e;">Löschen</button>
                </form>
            </td>
        <?php else: ?>
            <div><em> (Sie selbst)</em></div>

        <?php endif; ?>
        </td>
        </tr>
    <?php endforeach; ?>
</table>