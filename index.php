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


// --- FEHLERREPORT --- NUR ZUM DEBUGGING ---
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

session_start();
require_once 'logics/functions/functions.php';


$Version = '1.1';
$currentUser = getCurrentUser();
$pagesdirection = './pages/';


// Zugriffskontrolle
$currentPage = $_GET['page'] ?? 'about';
$publicPages = ['login', 'install', 'home', 'about', 'help'];

if (!in_array($currentPage, $publicPages)) {
    requireLogin();
}

// Prüfe, ob eine Installation nötig ist
$users = loadUsers();
if (empty($users) && $currentPage !== 'home' && $currentPage !== 'install') {
    header('Location: ?page=install');
    exit;
}



// Benachrichtigung aus Session holen
$notification = $_SESSION['notification'] ?? null;
unset($_SESSION['notification']);


?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetPause – Zeitschaltung für AdGuard Home</title>
    <link rel="icon" type="image/png" href="Logo.png">
    <link rel="stylesheet" href="./styles/init.css">
    <style>
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .notification {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-bottom: 15px;
            max-width: 400px;
            padding: 16px 20px;
            position: relative;
            opacity: 0;
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            transform: translateX(50px);
            display: flex;
            align-items: center;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .notification.success::before {
            background-color: #4CAF50;
        }

        .notification.error::before {
            background-color: #f44336;
        }

        .notification.warning::before {
            background-color: #ff9800;
        }

        .notification.info::before {
            background-color: #2196F3;
        }

        .notification-icon {
            margin-right: 15px;
            font-size: 24px;
            flex-shrink: 0;
        }

        .notification-message {
            flex-grow: 1;
            font-size: 15px;
            line-height: 1.5;
            margin-right: 20px;
        }

        .notification .close-btn {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 22px;
            line-height: 1;
            padding: 0;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            transition: color 0.2s;
        }

        .notification .close-btn:hover {
            color: #333;
        }

        @media (max-width: 480px) {
            .notification {
                max-width: 90%;
                margin-right: 5%;
            }
        }
    </style>
</head>

<body>
    <header>
        <a href="?page=home">
            <!-- Banner mit 8vh Höhe – passt sich dem 10vh Header an -->
            <img src="Banner.png" height="80vh" alt="NetPause Logo">
        </a>
        <h1 class="header-text">NetPause</h1>
    </header>
    <main>
        <mainmenu>
            <div class="sub-nav">
                <!-- Home -->
                <a href="?page=home" class="sub-nav-link <?= ($currentPage === 'home') ? 'active' : '' ?>">Home</a>

                <!-- Steuerung -->
                <a href="?page=data" class="sub-nav-link <?= ($currentPage === 'data') ? 'active' : '' ?>">Steuerung</a>

                <?php if (isLoggedIn()): ?>
                    <a href="?page=lists&type=black" class="sub-nav-link <?= ($currentPage === 'lists') ? 'active' : '' ?>">Listen</a>
                <?php endif; ?>

                <!-- Listen – jetzt direkt als zwei Links (nur wenn eingeloggt)
                <?php if (isLoggedIn()): ?>
                    <a href="?page=lists&type=black" class="sub-nav-link <?= ($currentPage === 'lists' && isset($_GET['type']) && $_GET['type'] === 'black') ? 'active' : '' ?>">Sperrlisten</a>
                    <a href="?page=lists&type=white" class="sub-nav-link <?= ($currentPage === 'lists' && isset($_GET['type']) && $_GET['type'] === 'white') ? 'active' : '' ?>">Erlaubnislisten</a>
                <?php endif; ?> -->

                <!-- Einstellungen -->
                <a href="?page=settings" class="sub-nav-link <?= ($currentPage === 'settings') ? 'active' : '' ?>">Einstellungen</a>

                <!-- Benutzerbereich – ohne Dropdown -->
                <?php if (isLoggedIn()): ?>
                    <a href="?page=profile" class="sub-nav-link <?= ($currentPage === 'profile') ? 'active' : '' ?>">Profil</a>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="?page=users" class="sub-nav-link <?= ($currentPage === 'users') ? 'active' : '' ?>">Benutzerverwaltung</a>
                    <?php endif; ?>
                    <a href="?page=logout" class="sub-nav-link <?= ($currentPage === 'logout') ? 'active' : '' ?>">Abmelden</a>
                <?php else: ?>
                    <!-- Anmelden für nicht eingeloggte -->
                    <a href="?page=login" class="sub-nav-link <?= ($currentPage === 'login') ? 'active' : '' ?>">Anmelden</a>
                <?php endif; ?>
            </div>
        </mainmenu>

        <?php $pageFile = $pagesdirection . $currentPage . '.php';
        if (file_exists($pageFile)) {
            include $pageFile;
        } else {
            // 404-Seite anzeigen
            include $pagesdirection . '404.xml';
        } ?>
    </main>

    <footer class="footer">
        <table>
            <tr>
                <td>Version <?php echo htmlspecialchars($Version); ?></td>
                <td>&copy; <?php echo date("Y"); ?> Neusmartra | Systems</td>
                <td>
                    <a href="?page=about" class="sub-nav-link">Über das Projekt</a> •
                    <a href="https://github.com/NeuSmartRa-Systems/NetPause-for-AdGuard-Home" class="sub-nav-link" target="_blank">GitHub</a> •
                    <a href="?page=help" class="sub-nav-link">Hilfe</a>
                </td>
            </tr>
        </table>
    </footer>

    <div id="notification-container"></div>

    <script>
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;

            let icon = '';
            switch (type) {
                case 'success':
                    icon = '✔️';
                    break;
                case 'error':
                    icon = '❌';
                    break;
                case 'warning':
                    icon = '⚠️';
                    break;
                case 'info':
                    icon = 'ℹ️';
                    break;
            }

            notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${icon}</span>
            <span class="notification-message">${message}</span>
        </div>
        <button class="close-btn" aria-label="Schließen">×</button>
    `;

            container.appendChild(notification);

            // Fade-in und Slide-in Effekt
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);

            const closeBtn = notification.querySelector('.close-btn');
            closeBtn.addEventListener('click', () => closeNotification(notification));

            // Automatisches Entfernen nach 5 Sekunden
            setTimeout(() => closeNotification(notification), 5000);
        }

        function closeNotification(notification) {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    </script>

    <script>
        <?php
        if ($notification !== null) {
            echo "document.addEventListener('DOMContentLoaded', function() {";
            echo "    showNotification('" . addslashes($notification['message']) . "', '" . $notification['type'] . "');";
            echo "});";
        }
        ?>
    </script>






</body>

</html>
