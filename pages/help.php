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

?>


<h2 style="text-align:center;">Hilfe & Dokumentation</h2>

<p style="text-align:center; font-size:1.1rem;">
    Hier finden Sie alles, um NetPause erfolgreich einzurichten und zu nutzen.
</p>

<hr>

<h3>📘 AdGuard Home – Grundlagen</h3>

<p>
    NetPause benötigt ein bestehendes AdGuard Home. Stellen Sie sicher, dass:
</p>
<ul>
    <li>AdGuard Home installiert und erreichbar ist.</li>
    <li>Sie einen Administrator-Zugang (Benutzername/Passwort) haben.</li>
    <li>Unter <strong>Clients</strong> die Geräte angelegt sind, die Sie später steuern wollen.</li>
</ul>

<hr>

<h3>🧠 Wie die Zeitsteuerung funktioniert</h3>

<p>
    Die App prüft minütlich, ob die aktuelle Uhrzeit in einem Ihrer definierten Sperrintervalle liegt. Ist das der Fall, werden in AdGuard Home zwei Arten von Regeln gesetzt:
</p>
<ul>
    <li>Eine <strong>Sperrregel</strong> für das gesamte Internet: <code>||*^$client=GERÄT</code></li>
    <li><strong>Ausnahmeregeln</strong> für die von Ihnen freigegebenen Seiten: <code>@@||domain^$client=GERÄT</code></li>
</ul>
<p>
    Außerhalb der Intervalle werden diese Regeln vollständig entfernt – das Gerät hat wieder normalen Zugriff.
</p>

<hr>

<h3>⏰ Sinnvolle Zeitintervalle</h3>

<p>
    Sie können für jedes Gerät beliebig viele Intervalle angeben. Einige Beispiele:
</p>

<ul>
    <li><strong>Nachtruhe:</strong> 21:00 – 07:00 (funktioniert auch über Mitternacht)</li>
    <li><strong>Schulzeit:</strong> 08:00 – 13:00</li>
    <li><strong>Lernzeit:</strong> 14:00 – 16:00</li>
    <li><strong>Dauerhaft sperren:</strong> 00:00 – 23:59</li>
</ul>

<p>
    Sobald die aktuelle Uhrzeit in <em>einem</em> dieser Intervalle liegt, wird das Gerät gesperrt.
</p>

<hr>

<h3>📱 Geräte richtig konfigurieren (der wichtigste Schritt!)</h3>

<p>
    Damit NetPause überhaupt eingreifen kann, muss der gesamte DNS-Verkehr des Geräts über Ihren AdGuard Home Server laufen. Achten Sie auf folgende Punkte:
</p>

<ul>
    <li><strong>Feste IP-Adresse</strong> – entweder per Router-Reservierung oder manuell im Gerät.</li>
    <li><strong>DNS-Server</strong> – tragen Sie die IP Ihres AdGuard Home Servers ein (und <strong>keine</strong> externen DNS wie 8.8.8.8).</li>
    <li><strong>IPv6 deaktivieren</strong> – viele Geräte nutzen sonst IPv6-DNS und umgehen die Sperre.</li>
    <li><strong>DNS over HTTPS (DoH) / DNS over TLS (DoT)</strong> – deaktivieren, da diese Verbindungen verschlüsselt sind und AdGuard Home sie nicht sieht.</li>
</ul>

<p>
    Im Zweifel prüfen Sie mit einem Blick in das <strong>Abfrageprotokoll</strong> von AdGuard Home, ob die Anfragen des Geräts dort auftauchen.
</p>

<hr>

<h3>🪟 Windows richtig einstellen</h3>
<ol>
    <li><strong>Statische IP</strong> konfigurieren (in den Netzwerkeinstellungen von Windows oder per Router-Reservierung).</li>
    <li><strong>DNS-Server</strong> unter IPv4 manuell auf die AdGuard-IP setzen.</li>
    <li><strong>IPv6 deaktivieren</strong>:
        <ul>
            <li>In den Adaptereinstellungen das Häkchen bei „Internetprotokoll Version 6 (TCP/IPv6)“ entfernen.</li>
            <li>Oder via Registry:
                <pre>Windows-Taste + R → <code>regedit</code><br>
                    <code>HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\Tcpip6\Parameters</code><br>
                    DWORD-Wert (32-Bit) <code>DisabledComponents</code> = <code>0xff</code> (hexadezimal für 255)</pre>
            </li>
        </ul>
    </li>
    <li><strong>DoH deaktivieren</strong> (falls aktiviert – in Windows meist nicht nötig):
        <ul>
            <li>Via Registry:
                <pre>Windows-Taste + R → <code>regedit</code><br>
                    <code>HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\Dnscache\Parameters</code><br>
                    DWORD-Wert (32-Bit) <code>EnableAutoDoh</code> = <code>0</code></pre>
            </li>
        </ul>
    </li>
</ol>
<hr>

<h3>📱 Android konfigurieren</h3>

<ol>
    <li>WLAN-Einstellungen → Netzwerk lange drücken → „Netzwerk ändern“.</li>
    <li>„Erweiterte Optionen“ anzeigen → IP-Einstellungen auf <strong>Statisch</strong>.</li>
    <li>Bei DNS 1 und DNS 2 die IP Ihres AdGuard Home Servers eintragen.</li>
    <li>Unter <strong>Privates DNS</strong> (in den Systemeinstellungen) die Option <strong>„Aus“</strong> wählen.</li>
</ol>

<hr>

<h3>🍏 iPhone / iPad einrichten</h3>

<ol>
    <li>Einstellungen → WLAN → (i) neben Ihrem Netzwerk.</li>
    <li>IP konfigurieren → <strong>Manuell</strong> – vergeben Sie eine statische IP (oder notieren Sie die aktuelle und reservieren Sie sie im Router).</li>
    <li>DNS konfigurieren → <strong>Manuell</strong> – vorhandene Einträge löschen und Ihre AdGuard-IP hinzufügen.</li>
    <li>Stellen Sie sicher, dass <strong>iCloud Private Relay</strong> deaktiviert ist (falls aktiv).</li>
</ol>

<hr>

<h3>🐧 Linux (mit NetworkManager)</h3>

<p>
    Wenn Sie einen Linux-Rechner direkt steuern möchten, können Sie die DNS-Einstellungen z.B. so setzen (als root):
</p>

<pre>
# Verbindungsname herausfinden
nmcli connection show

# IPv6 deaktivieren, DNS setzen
sudo nmcli connection modify "Verbindungsname" ipv6.method "disabled"
sudo nmcli connection modify "Verbindungsname" ipv4.ignore-auto-dns yes
sudo nmcli connection modify "Verbindungsname" ipv4.dns "192.168.1.100"

# Verbindung neu starten
sudo nmcli connection down "Verbindungsname" && sudo nmcli connection up "Verbindungsname"
</pre>

<p>
    (Ersetzen Sie „Verbindungsname“ und die IP durch Ihre Werte.)
</p>

<hr>

<h3>🛡️ Umgehung durch DNS over HTTPS (DoH) verhindern</h3>

<p>
    Manche Browser (Firefox, Chrome) oder Betriebssysteme (Android 9+) versuchen, verschlüsseltes DNS zu nutzen, um die Sperre zu umgehen. Die effektivste Lösung ist, diese Verbindungen auf AdGuard Home-Ebene zu blockieren.
</p>

<p>
    Fügen Sie dazu die <strong>DoH-Blocklist von HaGeZi</strong> in AdGuard Home ein:
</p>

<ol>
    <li>Öffnen Sie AdGuard Home → <strong>Filter → Benutzerdefinierte Filter</strong>.</li>
    <li>Klicken Sie auf <strong>Filter hinzufügen</strong>.</li>
    <li>Geben Sie einen Namen ein, z.B. „DoH-Blocklist“.</li>
    <li>Fügen Sie folgende URL ein:<br>
        <code>https://raw.githubusercontent.com/hagezi/dns-blocklists/main/adblock/doh.txt</code>
    </li>
    <li>Speichern Sie den Filter und warten Sie auf die Aktualisierung.</li>
</ol>

<p>
    Damit werden bekannte DoH-Server blockiert, und das Gerät kann nicht mehr darauf ausweichen.
</p>

<hr>

<h3>🕒 Zeitzone des Servers prüfen</h3>

<p>
    Die automatische Steuerung basiert auf der Systemzeit Ihres Servers. Wenn die Sperrzeiten nicht korrekt greifen, liegt es oft an einer falschen Zeitzone.
</p>

<pre>
# Aktuelle Zeitzone anzeigen
timedatectl

# Zeitzone setzen (z.B. für Deutschland)
sudo timedatectl set-timezone Europe/Berlin
sudo dpkg-reconfigure --frontend noninteractive tzdata
</pre>

<hr>

<h3>❓ Häufige Probleme – und was Sie tun können</h3>

<ul>

    <li>
        <strong>Die Sperre hat keine Wirkung:</strong><br>
        Prüfen Sie im Abfrageprotokoll von AdGuard Home, ob die DNS-Anfragen des Geräts dort auftauchen. Wenn nicht, verwendet das Gerät einen anderen DNS-Server (z.B. über DoH oder fest eingetragene externe DNS). Korrigieren Sie die Geräteeinstellungen wie oben beschrieben.
    </li>

    <li>
        <strong>Ein Gerät wird nicht erkannt:</strong><br>
        Der Client-Name in AdGuard Home muss exakt mit dem Namen in der App übereinstimmen. Achten Sie auf Groß-/Kleinschreibung und eventuelle Anhänge (z.B. „-“ oder Leerzeichen).
    </li>

    <li>
        <strong>Regeln werden nicht gesetzt:</strong><br>
        Prüfen Sie die Log-Dateien der App: <code>/var/www/html/AdGuardParental/kinderschutz.log</code> und <code>cron-error.log</code>. Dort steht, ob der Cron-Job läuft und ob die Verbindung zu AdGuard Home klappt.
    </li>

    <li>
        <strong>Sperrzeiten passen zeitlich nicht:</strong><br>
        Stimmt die Zeitzone des Servers? Kontrollieren Sie mit <code><small>date</small></code> die aktuelle Systemzeit.
    </li>

    <li>
        <strong>Fehler „Keine AdGuard-Konfiguration“:</strong><br>
        Hinterlegen Sie Ihre Zugangsdaten in den Einstellungen. Die URL muss dabei vollständig sein (z.B. <code>http://192.168.1.100:80</code>).
    </li>

    <li>
        <strong>Passwort für die App vergessen?</strong><br>
        Löschen Sie die Datei <code>/var/www/keys/users.enc</code> – dann startet die App neu und Sie können einen neuen Admin-Benutzer anlegen. Alle anderen Einstellungen (AdGuard-Zugangsdaten, Geräte) bleiben erhalten.
    </li>

</ul>