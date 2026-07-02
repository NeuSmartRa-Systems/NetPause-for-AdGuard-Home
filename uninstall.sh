#!/bin/bash
# Uninstall-Skript für AdGuard Parental

#╔══════════════════════════════════════════════════════════╗
#║                   AdGuard Parental -                     ║
#║           Zeitschaltung für AdGuard Home                 ║
#║                 developed by Neusmartra                  ║
#║                      © 2026                              ║
#║  Diese Datei ist Teil des AdGuard Parental-Projekts.     ║
#║  Alle Rechte vorbehalten. Weitergabe / Veränderung       ║
#║  nur mit ausdrücklicher Genehmigung des Autors.          ║
#╚══════════════════════════════════════════════════════════╝

echo
echo
echo ╔══════════════════════════════════════════════════════════╗
echo                     AdGuard Parental -                      
echo             Zeitschaltung für AdGuard Home                  
echo                   developed by Neusmartra                   
echo                        © 2026                               
echo    Alle Rechte vorbehalten. Weitergabe / Veränderung        
echo    nur mit ausdrücklicher Genehmigung des Autors.           
echo ╚══════════════════════════════════════════════════════════╝
echo
echo

echo "Dieses Skript wird AdGuard Parental vollständig von Ihrem System entfernen."
echo "Folgende Komponenten werden gelöscht:"
echo "  - Webverzeichnis /var/www/html/AGP"
echo "  - Schlüsselverzeichnis /var/www/keys (mit allen Benutzerdaten und Zugangsdaten!)"
echo "  - Cron-Job /etc/cron.d/adguardparental"
echo "  - Apache VirtualHost für Port 8083 (agp-8083.conf)"
echo "  - Port 8083 aus /etc/apache2/ports.conf (falls nur von uns hinzugefügt)"
echo
echo "Die Apache- und PHP-Pakete bleiben erhalten, da sie auch von anderen Anwendungen genutzt werden könnten."
echo
read -p "Sind Sie sicher, dass Sie AdGuard Parental deinstallieren möchten? (j/N): " confirm
if [[ ! "$confirm" =~ ^[jJyY] ]]; then
    echo "Deinstallation abgebrochen."
    exit 0
fi

set -e  # Skript abbrechen bei Fehlern (optional – für wichtige Schritte)

# === Konfiguration (identisch zum Installer) ===
WEBROOT_BASE="/var/www/html"
APP_DIR="AGP"
WEBROOT_APP="$WEBROOT_BASE/$APP_DIR"
KEYDIR="/var/www/keys"
WEBUSER="www-data"
CRON_FILE="/etc/cron.d/adguardparental"
VHOST_CONF="/etc/apache2/sites-available/agp-8083.conf"
VHOST_ENABLED="/etc/apache2/sites-enabled/agp-8083.conf"
PORTS_CONF="/etc/apache2/ports.conf"

echo
echo "1. Cron-Job entfernen"
if [ -f "$CRON_FILE" ]; then
    sudo rm -f "$CRON_FILE"
    echo "   $CRON_FILE gelöscht."
else
    echo "   Cron-Job nicht vorhanden."
fi

echo
echo "2. VirtualHost deaktivieren und löschen"
if [ -f "$VHOST_ENABLED" ] || [ -f "$VHOST_CONF" ]; then
    sudo a2dissite agp-8083.conf 2>/dev/null || true
    sudo rm -f "$VHOST_CONF"
    echo "   VirtualHost agp-8083.conf entfernt."
else
    echo "   VirtualHost nicht vorhanden."
fi

echo
echo "3. Port 8083 aus ports.conf entfernen (falls vorhanden)"
if [ -f "$PORTS_CONF" ]; then
    # Erstelle ein Backup, falls jemand manuell etwas geändert hat
    sudo cp "$PORTS_CONF" "$PORTS_CONF.bak"
    # Entferne die Zeile "Listen 8083" (genaue Übereinstimmung)
    sudo sed -i '/^Listen 8083$/d' "$PORTS_CONF"
    echo "   Port 8083 aus $PORTS_CONF entfernt (Backup: $PORTS_CONF.bak)."
else
    echo "   $PORTS_CONF nicht gefunden."
fi

echo
echo "4. Webverzeichnis löschen"
if [ -d "$WEBROOT_APP" ]; then
    sudo rm -rf "$WEBROOT_APP"
    echo "   $WEBROOT_APP gelöscht."
else
    echo "   Webverzeichnis nicht vorhanden."
fi

echo
echo "5. Schlüsselverzeichnis löschen (ACHTUNG: unwiderruflich!)"
if [ -d "$KEYDIR" ]; then
    echo "   Folgende Dateien werden gelöscht:"
    sudo ls -la "$KEYDIR"
    read -p "   Wirklich alle Schlüssel und Benutzerdaten unwiderruflich löschen? (j/N): " del_keys
    if [[ "$del_keys" =~ ^[jJyY] ]]; then
        sudo rm -rf "$KEYDIR"
        echo "   $KEYDIR gelöscht."
    else
        echo "   Schlüsselverzeichnis wurde behalten."
    fi
else
    echo "   Schlüsselverzeichnis nicht vorhanden."
fi

echo
echo "6. Apache-Konfiguration neu laden"
sudo systemctl reload apache2 || sudo systemctl restart apache2

echo
echo
echo "Deinstallation abgeschlossen."
echo "Apache2 und PHP wurden nicht entfernt. Falls Sie diese ebenfalls deinstallieren möchten, führen Sie aus:"
echo "  sudo apt purge apache2 php-*  (Vorsicht: könnten andere Websites beeinträchtigen)"
echo
echo ╔══════════════════════════════════════════════════════════╗
echo                     AdGuard Parental -                      
echo             Zeitschaltung für AdGuard Home                  
echo                   developed by Neusmartra                   
echo                        © 2026                               
echo    Alle Rechte vorbehalten. Weitergabe / Veränderung       
echo    nur mit ausdrücklicher Genehmigung des Autors.          
echo ╚══════════════════════════════════════════════════════════╝
echo