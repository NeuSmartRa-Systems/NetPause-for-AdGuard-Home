# ⏰ NetPause for AdGuardHome

**Zeitgesteuerte Kindersicherung für AdGuard Home – automatisch, individuell und datenschutzfreundlich.**

![GitHub stars](https://img.shields.io/badge/stars-★_coming_soon-lightgrey)
![GitHub forks](https://img.shields.io/badge/forks-★_coming_soon-lightgrey)
![GitHub issues](https://img.shields.io/badge/issues-★_coming_soon-lightgrey)
![License](https://img.shields.io/github/license/yourusername/netpause-adguardhome)

---

## 📸 Screenshots

> *(Screenshots folgen in Kürze)*

---

## ✨ Features auf einen Blick

| Bereich | Funktion |
|---------|----------|
| **Individuelle Sperrzeiten** | Pro Gerät (Tablet, Smartphone, Laptop) festlegen, wann das Internet gesperrt sein soll – z.B. täglich 21:00–07:00 Uhr |
| **Erlaubte Ausnahmen (Whitelist)** | Auch während der Sperrzeit bestimmte Webseiten freigeben (Lernplattformen, Schul‑Clouds, Bibliotheken) – je Gerät eigene Whitelist |
| **Vollautomatisch im Hintergrund** | Cron‑Job prüft minütlich die Einstellungen und passt die AdGuard‑Filterregeln an – kein manueller Eingriff nötig |
| **Datenschutzfreundlich** | Läuft auf Ihrem eigenen Webserver, alle Daten bleiben lokal – keine externen Dienste |
| **Einfache Einrichtung** | Web‑Interface zur Konfiguration von Geräten, Whitelists und Sperrzeiten |

---

## 🎯 Das Problem

AdGuard Home filtert Domains zuverlässig, aber **immer dauerhaft**. Wer die Internetnutzung von Kindern nur zu bestimmten Zeiten (z.B. während der Hausaufgaben oder nachts) einschränken möchte, muss Filterregeln bisher manuell umstellen – umständlich und fehleranfällig.

---

## 💡 Die Lösung – einfach & automatisch

Mit **NetPause for AdGuardHome** legen Sie für jedes Gerät fest:

- **Wann** das Internet gesperrt wird (Zeitintervall),
- **welche** Seiten trotzdem erreichbar bleiben (individuelle Whitelist).

Ein Cron‑Job übernimmt die komplette Steuerung – Sie müssen sich um nichts kümmern.

---

## 🖥️ So nutzen Sie NetPause

1. **Einmalig anmelden:** AdGuard‑Zugangsdaten hinterlegen (werden verschlüsselt gespeichert).
2. **Geräte konfigurieren:** Wählen Sie die Clients aus, legen Sie Whitelists und Sperrzeiten fest. *(Hinweis: In AdGuard Home müssen die Client‑Profile bereits eingerichtet sein.)*
3. **Fertig:** Die App steuert ab sofort automatisch die Filterregeln.

> **Wichtig:** NetPause überschreibt die **'Benutzerdefinierten Filterregeln'** in AdGuard Home.

---

## 👥 Für wen ist das gedacht?

Für alle, die klare, automatisierte Regeln für die Internetnutzung im eigenen Netzwerk durchsetzen möchten – ohne manuelle Eingriffe und ohne Probleme. Ideal für Familien, Wohngemeinschaften oder kleine Büros.

---

# Installation

## 📋 Voraussetzungen

| Komponente | Anforderung |
|------------|-------------|
| **Betriebssystem** | Linux (Debian/Ubuntu empfohlen) mit `sudo`‑Rechten |
| **Webserver** | Apache2 (wird automatisch installiert) |
| **PHP** | Version 7.4 oder höher (wird mitinstalliert) |
| **PHP‑Erweiterungen** | `curl`, `openssl` (werden automatisch installiert) |
| **Cron** | Muss aktiv sein (meistens standardmäßig vorhanden) |
| **AdGuard Home** | Muss bereits laufen und erreichbar sein |

---

## 🚀 Installation

1. **Zip‑Datei entpacken**

```bash
git clone https://github.com/NeuSmartRa-Systems/NetPause-for-AdGuard-Home.git
unzip NetPause-for-AdGuard-Home.zip -d NetPause
cd NetPause
```


2. **Installationsskript ausführen**

Das Skript `setup.sh` erledigt alle notwendigen Schritte automatisch:

- Installiert Apache2, PHP und benötigte Module
- Erstellt die Verzeichnisse `/var/www/html/AGP` und `/var/www/keys`
- Generiert einen zufälligen Verschlüsselungsschlüssel in `/var/www/keys/secret.php`
- Kopiert alle Anwendungsdateien ins Webverzeichnis
- Setzt die richtigen Berechtigungen
- Legt eine `.htaccess` für saubere URLs an
- Richtet einen Cron‑Job ein, der jede Minute die Zeitschaltung prüft

Starten Sie das Skript mit:

```bash
chmod +x setup.sh
sudo ./setup.sh
```

> **Hinweis:** Das Skript kopiert die Dateien automatisch aus dem aktuellen Verzeichnis nach `/var/www/html/AGP`. Führen Sie es daher **im entpackten Ordner** aus.

---

## ⚙️ Nach der Installation

- **Zeitzone** auf dem Server korrekt setzen:

```bash
timedatectl
timedatectl list-timezones | grep -i WUNSCHORT
sudo timedatectl set-timezone ANGEZEIGTER-TREFFER-WUNSCHORT
```

- **Webanwendung** aufrufen: `http://IHRE-SERVER-IP:8083`
- Da noch kein Benutzer existiert, werden Sie automatisch zur **Ersteinrichtung** weitergeleitet.
- Legen Sie den ersten Admin‑Benutzer an (Benutzername und Passwort).
- Melden Sie sich an und gehen Sie zu **Einstellungen**, um Ihre AdGuard‑Zugangsdaten zu hinterlegen.
- Unter **Steuerung** können Sie Geräte konfigurieren, Whitelists und Sperrzeiten definieren.

---

## 🧹 Aufräumen

Nach erfolgreicher Installation können Sie den entpackten Ordner (z.B. `AdGuardParental`) löschen – die Anwendung liegt sicher im Webverzeichnis.

```bash
cd ..
rm -rf AdGuardParental
```

---

## 🔧 Funktionsweise

- Ein Cron‑Job führt jede Minute die Datei `cron.php` aus.
- Diese vergleicht die definierten Zeitintervalle mit der Systemzeit und setzt bei Bedarf die entsprechenden Filterregeln in AdGuard Home.
- Alle Regeln werden in den **Benutzerdefinierten Filterregeln** von AdGuard Home gespeichert – vorhandene Regeln werden dabei **überschrieben**.

---

## 🔒 Wichtige Sicherheitshinweise

- Die Datei `/var/www/keys/secret.php` enthält Ihren individuellen Verschlüsselungsschlüssel. **Sichern Sie diese Datei unbedingt** – ohne sie können die gespeicherten AdGuard‑Zugangsdaten und Benutzerkonten nicht wiederhergestellt werden.
- Das Verzeichnis `/var/www/keys` ist nur für den Benutzer `www-data` lesbar und liegt **außerhalb des Webroots** – es ist nicht über das Internet erreichbar.

---

## 📜 Rechtliche Hinweise

### Hinweis zur Nutzung
Diese App ist ausschließlich für die Überwachung **eigener Geräte** und die Kindersicherung **im eigenen Haushalt** bestimmt. Der Nutzer ist verpflichtet, die geltenden Datenschutzgesetze einzuhalten und die App nur in Übereinstimmung mit den nationalen Rechtsvorschriften zu verwenden. Eine unbefugte Überwachung Dritter ist nicht gestattet.

### Haftungsausschluss
Der Entwickler übernimmt keine Haftung für direkte oder indirekte Schäden, die durch die Nutzung dieser Software entstehen könnten. Die App wurde sorgfältig entwickelt, dennoch können Fehler nicht vollständig ausgeschlossen werden. Bei Problemen nehmen wir gerne Hinweise entgegen.

### Markenrecht
AdGuard ist eine eingetragene Marke der AdGuard Software Limited. Diese App ist eine unabhängige Drittanbieter‑Erweiterung und steht in keiner offiziellen Verbindung mit AdGuard Software Limited. Die Verwendung des Namens dient ausschließlich der Kennzeichnung der Kompatibilität mit AdGuard‑Produkten.

---

## 🎯 Warum NetPause?

- ❌ **Manuelle Filteranpassung** – zeitaufwändig und vergesslich
- ✅ **NetPause** – einmal konfigurieren, automatisch steuern lassen

---

## 📄 Lizenz

**GNU General Public License v3.0** – Nutzung, Modifikation und Weitergabe erlaubt unter gleichen Bedingungen.

---

## 🤝 Beiträge

Issues und Pull Requests sind willkommen!  
Bei Fragen oder Verbesserungsvorschlägen einfach ein Issue eröffnen.

---

## 🐛 Bekannte Fehler

*(Aktuell keine bekannten Fehler – bei Problemen bitte melden.)*

---

<p align="center">
  <b>Dein Name</b> | 2026
</p>
```
