# NetPause for AdGuardHome

NetPause for AdGuardHome ist eine Erweiterung für AdGuard Home, die eine zeitgesteuerte Kindersicherung ermöglicht.
Mit dieser App können Sie für einzelne Geräte (Clients) Sperrzeiten festlegen und trotzdem bestimmte Webseiten erlauben – alles vollautomatisch über einen Cron-Job.

## Das Problem

AdGuard Home filtert Domains zuverlässig, aber immer nur dauerhaft. Wer die Internetnutzung seiner Kinder etwa nur während der Hausaufgaben oder nachts einschränken möchte, muss Filterregeln bisher manuell umstellen. Das ist umständlich und führt oft zu Problemen.

## Die Lösung – einfach & automatisch

- **Individuelle Sperrzeiten:** Legen Sie für jedes Gerät (Tablet, Smartphone, Laptop) fest, **wann das Internet gesperrt** sein soll – z.B. täglich von 21:00 bis 07:00 Uhr.
- **Erlaubte Ausnahmen:** Auch während der Sperrzeiten können Sie bestimmte Webseiten freigeben – etwa Lernplattformen, Schul-Clouds oder Bibliothekskataloge. Jedes Gerät erhält eine eigene Whitelist.
- **Vollautomatisch im Hintergrund:** Ein automatischer Dienst (Cron-Job) prüft minütlich Ihre Einstellungen und passt die Regeln in AdGuard Home an. Sie müssen sich um nichts weiter kümmern.

## So nutzen Sie NetPause for AdGuardHome

1. **Einmalig anmelden:** Hinterlegen Sie Ihre AdGuard Home-Zugangsdaten (werden verschlüsselt gespeichert).
2. **Geräte konfigurieren:** Wählen Sie die gewünschten Geräte aus, legen Sie erlaubte Webseiten fest und definieren Sie Sperrzeiten (dazu müssen in AdGuard Home die Client-Profile eingerichtet sein).
3. **Fertig:** Die App übernimmt ab sofort die komplette Steuerung. Sie können sich zurücklehnen.

**Beachten Sie,** dass NetPause for AdGuardHome die **'Benutzerdefinierte Filterregeln'**  in AdGuard Home **überschreiben** wird.

**Für wen ist das gedacht?**  
Für alle, die klare, automatisierte Regeln für die Internetnutzung in Ihrem lokalen Netzwerk durchsetzen möchten – ohne manuelle Eingriffe und ohne Probleme. NetPause for AdGuardHome läuft auf Ihrem eigenen Webserver, speichert alle Daten lokal und ist damit besonders datenschutzfreundlich.

# Installation
## VORAUSSETZUNGEN

- Betriebssystem: Linux (Debian/Ubuntu empfohlen) mit sudo-Rechten
- Webserver: Apache2 (wird automatisch installiert)
- PHP: Version 7.4 oder höher (wird mitinstalliert)
- PHP-Erweiterungen: curl, openssl (werden automatisch installiert)
- Cron: Muss aktiv sein (meistens standardmäßig vorhanden)
- AdGuard Home: Muss bereits laufen und erreichbar sein


## INSTALLATION


Legen Sie die heruntergeladene Zip-Datei auf Ihrem Server ab und entpacken Sie sie:
'''
unzip AdGuardParental.zip -d AdGuardParental
cd AdGuardParental
'''

2. Führen Sie das Installationsskript aus

Das Skript setup.sh erledigt alle notwendigen Schritte automatisch:

- Installiert Apache2, PHP und benötigte Module
- Erstellt die Verzeichnisse /var/www/html/AGP und /var/www/keys
- Generiert einen zufälligen Verschlüsselungsschlüssel in /var/www/keys/secret.php
- Kopiert alle Anwendungsdateien ins Webverzeichnis
- Setzt die richtigen Berechtigungen
- Legt eine .htaccess für saubere URLs an
- Richtet einen Cron-Job ein, der jede Minute die Zeitschaltung prüft

Starten Sie das Skript mit:

'''
chmod +x setup.sh
sudo ./setup.sh
'''

Während der Ausführung werden Sie ggf. nach Ihrem Passwort gefragt (für sudo).

Hinweis: Das Skript kopiert die Dateien automatisch aus dem aktuellen Verzeichnis nach /var/www/html/AGP.
Stellen Sie daher sicher, dass Sie sich im entpackten Ordner befinden, bevor Sie setup.sh ausführen.

3. Nach der Installation

- Stellen Sie die richtige Zeitzone auf ihrem Server ein:
  '''
    timedatectl
    timedatectl list-timezones | grep -i WUNSCHORT
    sudo timedatectl set-timezone ANGEZEIGTER-TREFFER-WUNSCHORT
  '''
- Die Webanwendung ist nun unter http://IHRE-SERVER-IP:8083 erreichbar.
- Da noch kein Benutzer existiert, werden Sie automatisch zur Ersteinrichtung weitergeleitet.
- Legen Sie dort den ersten Admin-Benutzer an (Benutzername und Passwort).
- Melden Sie sich an und gehen Sie zu Einstellungen, um Ihre AdGuard Home-Zugangsdaten zu hinterlegen.
- Unter Steuerung können Sie nun Geräte konfigurieren, Whitelists festlegen und Sperrzeiten definieren.

4. Aufräumen

Nach erfolgreicher Installation können Sie den entpackten Ordner (z.B. AdGuardParental) löschen – die Anwendung liegt sicher im Webverzeichnis.

'''
cd ..
rm -rf AdGuardParental
'''


# FUNKTIONSWEISE

- Ein Cron-Job führt jede Minute die Datei cron.php aus.
- Diese vergleicht die aktuellen Zeitintervalle mit der Systemzeit und setzt bei Bedarf die entsprechenden Filterregeln in AdGuard Home.
- Alle Regeln werden in den Benutzerdefinierten Filterregeln von AdGuard Home gespeichert – vorhandene Regeln werden dabei überschrieben.


## WICHTIGE SICHERHEITSHINWEISE

- Die Datei /var/www/keys/secret.php enthält Ihren individuellen Verschlüsselungsschlüssel. Sichern Sie diese Datei unbedingt – ohne sie können die gespeicherten AdGuard-Zugangsdaten und Benutzerkonten nicht wiederhergestellt werden.
- Das Verzeichnis /var/www/keys ist nur für den Benutzer www-data lesbar und liegt außerhalb des Webroots – es ist nicht über das Internet erreichbar.


# RECHTLICHE HINWEISE



## Hinweis zur Nutzung:
Diese App ist ausschließlich für die Überwachung eigener Geräte und die Kindersicherung im eigenen Haushalt bestimmt. Der Nutzer ist verpflichtet, die geltenden Datenschutzgesetze einzuhalten und die App nur in Übereinstimmung mit den nationalen Rechtsvorschriften zu verwenden. Eine unbefugte Überwachung Dritter ist nicht gestattet.

## Haftungsausschluss:
Der Entwickler übernimmt keine Haftung für direkte oder indirekte Schäden, die durch die Nutzung dieser Software entstehen könnten. Die App wurde sorgfältig entwickelt, dennoch können Fehler nicht vollständig ausgeschlossen werden. Bei Problemen nehmen wir gerne Hinweise entgegen.

## Markenrecht:
AdGuard ist eine eingetragene Marke der AdGuard Software Limited. Diese App ist eine unabhängige Drittanbieter-Erweiterung und steht in keiner offiziellen Verbindung mit AdGuard Software Limited. Die Verwendung des Namens dient ausschließlich der Kennzeichnung der Kompatibilität mit AdGuard-Produkten.
