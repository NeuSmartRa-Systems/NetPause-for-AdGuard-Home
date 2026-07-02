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
<h2 style="text-align:center;">Über NetPause</h2>
<div style="text-align:center; margin:15px;">
    <img src="Image.png" style="max-width:20vw;" alt="NetPause Logo">
</div>




<p style="text-align:center; font-size:1.1rem; line-height:1.6;">
    <strong>NetPause</strong> ergänzt AdGuard Home um eine flexible <strong>zeitgesteuerte Steuerung des Internetzugriffs</strong>.
    Damit können Sie für einzelne Geräte festlegen, <strong>wann das Internet gesperrt sein soll</strong> – und trotzdem wichtige Seiten freigeben.
</p>

<hr>

<h3>🎯 Ziel der Anwendung</h3>

<p>
    AdGuard Home filtert Werbung und Tracker, bietet aber keine Möglichkeit, den Internetzugriff zeitlich einzuschränken.
    Genau hier setzt NetPause an: Es ermöglicht Ihnen, für jedes Gerät individuelle Sperrzeiten zu definieren – vollautomatisch und ohne manuelle Eingriffe.
</p>

<hr>

<h3>✨ Funktionen im Überblick</h3>

<ul>

    <li>
        <strong>📅 Individuelle Zeitintervalle:</strong><br>
        Sie bestimmen selbst, wann ein Gerät gesperrt wird – ob nachts, während der Schulzeit oder zu bestimmten Lernzeiten.
    </li>

    <li>
        <strong>✅ Erlaubte Ausnahmen:</strong><br>
        Auch während einer Sperre können bestimmte Webseiten freigeschaltet werden, z.B. Lernportale, Bibliotheken oder Schul-Clouds.
    </li>

    <li>
        <strong>🤖 Automatische Steuerung:</strong><br>
        Ein Hintergrundprozess (Cron-Job) prüft minütlich die aktuellen Zeiten und passt die Regeln in AdGuard Home an.
    </li>

    <li>
        <strong>🔐 Sicher & lokal:</strong><br>
        Alle Daten bleiben auf Ihrem Server. Zugangsdaten werden verschlüsselt und außerhalb des Webverzeichnisses gespeichert.
    </li>

    <li>
        <strong>👨‍👩‍👧‍👦 Für Familien gemacht:</strong><br>
        Keine Diskussionen mehr über Onlinezeiten – die Technik setzt klare, vorhersehbare Regeln durch.
    </li>

</ul>

<hr>

<h3>⚙️ So funktioniert es</h3>

<ol>
    <li><strong>AdGuard Home installieren</strong> und Clients (Geräte) anlegen.</li>
    <li><strong>NetPause installieren</strong> (siehe unten).</li>
    <li><strong>AdGuard-Zugangsdaten hinterlegen</strong> (in den Einstellungen).</li>
    <li><strong>Geräte konfigurieren:</strong> Für jedes Gerät legen Sie erlaubte Seiten und Sperrzeiten fest.</li>
    <li><strong>Fertig:</strong> Die App übernimmt die Steuerung – Sie müssen nichts weiter tun.</li>
</ol>

<p>
    Ein Cron-Job prüft im Hintergrund jede Minute die aktuelle Uhrzeit. Liegt sie in einem Sperrintervall, werden in AdGuard Home die passenden Filterregeln gesetzt. Sobald die Sperrzeit endet, werden die Regeln wieder entfernt.
</p>

<hr>

<h3>🚀 Installation in zwei Minuten</h3>

<p>
    Laden Sie das aktuelle Archiv herunter, entpacken Sie es und führen Sie das Setup-Skript aus:
</p>

<pre>
unzip AdGuardParental.zip
cd AdGuardParental
chmod +x setup.sh
sudo ./setup.sh
</pre>

<p>
    Das Skript installiert alle benötigten Komponenten (Apache, PHP, Erweiterungen), legt ein sicheres Schlüsselverzeichnis an, generiert einen Verschlüsselungsschlüssel und richtet einen eigenen VirtualHost auf Port <code>8083</code> ein.
</p>

<p>
    Nach der Installation erreichen Sie die App unter:
</p>

<p style="text-align:center; font-family:monospace;">
    http://Ihre-Server-IP:8083
</p>

<p>
    Beim ersten Aufruf werden Sie zur Einrichtung des Admin-Benutzers weitergeleitet.
</p>

<hr>

<h3>🔐 Sicherheitshinweise</h3>

<ul>
    <li>Der Verschlüsselungsschlüssel liegt in <code>/var/www/keys/secret.php</code>. <strong>Sichern Sie diese Datei!</strong> Ohne sie können die gespeicherten Zugangsdaten nicht wiederhergestellt werden.</li>
    <li>Das Verzeichnis <code>/var/www/keys</code> ist nur für den Benutzer <code>www-data</code> lesbar und liegt außerhalb des Webroots – es ist nicht über das Internet erreichbar.</li>
</ul>

<hr>

<h3>⚠️ Wichtiger Hinweis zur Zeitzone</h3>

<p>
    Die Sperrzeiten richten sich nach der Systemzeit Ihres Servers. Stellen Sie daher sicher, dass die richtige Zeitzone eingestellt ist:
</p>

<pre>
sudo timedatectl set-timezone Europe/Berlin
sudo dpkg-reconfigure --frontend noninteractive tzdata
</pre>

<p>
    (Ersetzen Sie „Europe/Berlin“ durch Ihre lokale Zeitzone.)
</p>

<hr>

<h3>📄 Rechtliche Hinweise</h3>

<p>
    <strong>Urheberrecht:</strong> © 2026 Neusmartra. Veröffentlicht unter: GNU General Public License
</p>

<p>
    <strong>Hinweis zur Nutzung:</strong> Diese App ist ausschließlich für die Überwachung eigener Geräte und die Kindersicherung im eigenen Haushalt bestimmt. Der Nutzer ist verpflichtet, die geltenden Datenschutzgesetze einzuhalten und die App nur in Übereinstimmung mit den nationalen Rechtsvorschriften zu verwenden. Eine unbefugte Überwachung Dritter ist nicht gestattet.
</p>

<p>
    <strong>Haftungsausschluss:</strong> Der Entwickler übernimmt keine Haftung für direkte oder indirekte Schäden, die durch die Nutzung dieser Software entstehen könnten. Die App wurde sorgfältig entwickelt, dennoch können Fehler nicht vollständig ausgeschlossen werden. Bei Problemen nehmen Sie gerne Kontakt auf: <a href="mailto:support@neusmartra.de">support@neusmartra.de</a>.
</p>

<p>
    <strong>Markenrecht:</strong> AdGuard und das AdGuard-Logo sind eingetragene Marken der AdGuard Software Limited. Diese App ist eine unabhängige Drittanbieter-Erweiterung und steht in keiner offiziellen Verbindung mit AdGuard Software Limited. Die Verwendung des Namens dient ausschließlich der Kennzeichnung der Kompatibilität.
</p>