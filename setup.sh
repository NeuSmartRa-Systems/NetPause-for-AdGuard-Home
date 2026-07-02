#!/bin/bash
# Installationsskript für NetPause for AdGuardHome

#╔══════════════════════════════════════════════════════════╗
#║           NetPause for AdGuardHome -                     ║
#║           Zeitschaltung für AdGuard Home                 ║
#║                 developed by Neusmartra                  ║
#║                      © 2026                              ║
#╚══════════════════════════════════════════════════════════╝

echo
echo
echo ╔══════════════════════════════════════════════════════════╗
echo               NetPause for AdGuardHome -                      
echo             Zeitschaltung für AdGuard Home                  
echo                   developed by Neusmartra                   
echo                        © 2026                               
echo ╚══════════════════════════════════════════════════════════╝
echo
echo

echo "Dieses Skript wird NetPause for AdGuardHome auf Ihrem System installieren."
echo "Es werden Apache2, PHP und benötigte Pakete installiert."
echo "Sie sollten dieses Setup als Administrator / Root-User durchführen."
echo
read -p "Sind Sie sicher, dass Sie NetPause for AdGuardHome installieren möchten? (j/N): " confirm
if [[ ! "$confirm" =~ ^[jJyY] ]]; then
    echo "Installation abgebrochen."
    exit 0
fi


set -e  # Skript abbrechen bei Fehlern

# === Konfiguration ===
WEBROOT_BASE="/var/www/html"
APP_DIR="AGP"
WEBROOT_APP="$WEBROOT_BASE/$APP_DIR"
KEYDIR="/var/www/keys"
WEBUSER="www-data"

echo "Dieses Skript bereitet das System für NetPause for AdGuardHome vor."
echo "Benötigte Abhängigkeiten werden installiert, der Webserver konfiguriert."
echo "Die Web-Dateien kommen nach: $WEBROOT_APP"
echo "Die verschlüsselten Schlüssel und Benutzerdaten nach: $KEYDIR"
echo

# 1. Systempakete installieren
echo
echo "1. Systempakete installieren"
sudo apt update
sudo apt install -y apache2 php php-curl libapache2-mod-php php-mysql
sudo systemctl enable apache2
sudo systemctl restart apache2

# 2. Verzeichnisse erstellen
echo
echo "2. Verzeichnisse erstellen"
sudo mkdir -p "$WEBROOT_APP"
sudo mkdir -p "$KEYDIR"

# 3. secret.php mit zufälligem Verschlüsselungsschlüssel generieren
echo
echo "3. Generiere secret.php mit zufälligem Schlüssel"
if [ ! -f "$KEYDIR/secret.php" ]; then
    sudo php -r "
        \$key = base64_encode(random_bytes(32));
        file_put_contents('$KEYDIR/secret.php', '<?php define(\'ENCRYPTION_KEY\', \'' . \$key . '\'); ?>');
    "
    echo "   secret.php wurde erstellt."
else
    echo "   secret.php existiert bereits – wird nicht überschrieben."
fi

# 4. Web-Dateien kopieren (aktivieren, wenn das Skript aus dem Quellverzeichnis gestartet wird)
echo
echo "4. Web-Dateien kopieren"
# Hinweis: Voraussetzung: Das Skript liegt im entpackten Quellcode-Ordner
# und dieser Befehl kopiert alle Dateien ins WEBROOT_APP.
sudo cp -r . "$WEBROOT_APP"

# 5. Berechtigungen setzen
echo
echo "5. Berechtigungen setzen"
sudo chown -R "$WEBUSER":"$WEBUSER" "$WEBROOT_APP"
sudo chown -R "$WEBUSER":"$WEBUSER" "$KEYDIR"
sudo chmod 750 "$KEYDIR"
sudo chmod 640 "$KEYDIR/secret.php"   # Nur Besitzer und Gruppe lesen
# Log-Dateien anlegen (falls noch nicht vorhanden)
sudo touch "$WEBROOT_APP/kinderschutz.log" "$WEBROOT_APP/cron-error.log"
sudo chown "$WEBUSER":"$WEBUSER" "$WEBROOT_APP"/*.log
sudo chmod 664 "$WEBROOT_APP"/*.log

# 6. Cron-Job einrichten (jede Minute) – systemweit über /etc/cron.d/
echo
echo "6. Cron-Job einrichten (jede Minute)"
PHP_BIN=$(which php)  # Ermittelt den korrekten Pfad zu PHP (z.B. /usr/bin/php)
CRON_FILE="/etc/cron.d/adguardparental"
CRON_LINE="* * * * * $WEBUSER $PHP_BIN $WEBROOT_APP/cron.php >/dev/null 2>&1"

# Schreibe die Cron-Datei (überschreibt eine eventuell vorhandene)
echo "$CRON_LINE" | sudo tee "$CRON_FILE" >/dev/null
sudo chmod 644 "$CRON_FILE"
echo "   Cron-Job in $CRON_FILE angelegt."

# 7. VirtualHost für Port 8083 einrichten
echo
echo "7. Konfiguriere Apache für Port 8083 (VirtualHost)"
if ! grep -q "Listen 8083" /etc/apache2/ports.conf; then
    echo "Listen 8083" | sudo tee -a /etc/apache2/ports.conf
fi
VHOST_CONF="/etc/apache2/sites-available/agp-8083.conf"
sudo tee "$VHOST_CONF" >/dev/null <<EOF
<VirtualHost *:8083>
    DocumentRoot $WEBROOT_APP
    <Directory $WEBROOT_APP>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF
sudo a2ensite agp-8083.conf
sudo systemctl reload apache2

echo
echo
echo "Installation abgeschlossen. Bitte führen Sie jetzt die manuellen Schritte durch:"
echo
echo "  - Stellen Sie die richtige Zeitzone auf ihrem Server ein:
                timedatectl
                timedatectl list-timezones | grep -i WUNSCHORT
                sudo timedatectl set-timezone ANGEZEIGTER-TREFFER-WUNSCHORT"
echo "  - Rufen Sie die Webseite im Browser auf: http://IHRE-SERVER-IP:8083/"
echo "  - Legen Sie den ersten Admin-Benutzer an."
echo "  - Hinterlegen Sie Ihre AdGuard Home-Zugangsdaten."
echo "  - Entfernen Sie das heruntergeladene Archiv in Ihrem aktuellen Verzeichnis (optional)."
echo
echo "Wichtig: Die Datei $KEYDIR/secret.php enthält Ihren individuellen Verschlüsselungsschlüssel."
echo "          Sichern Sie diese Datei unbedingt, da ohne sie die gespeicherten Zugangsdaten nicht wiederhergestellt werden können."
echo
echo
echo "Wichtig: Die Datei $KEYDIR/secret.php enthält Ihren individuellen Verschlüsselungsschlüssel."
echo "          Sichern Sie diese Datei unbedingt, da ohne sie die gespeicherten Zugangsdaten nicht wiederhergestellt werden können."

echo
echo
echo ╔══════════════════════════════════════════════════════════╗
echo                     NetPause for AdGuardHome -                     
echo             Zeitschaltung für AdGuard Home                 
echo                   developed by Neusmartra                  
echo                        © 2026                              
echo ╚══════════════════════════════════════════════════════════╝
echo
echo
