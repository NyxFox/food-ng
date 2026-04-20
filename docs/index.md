# Dokumentation

Food NG ist ein kleines, produktionsnahes CMS für Speisepläne. Die Anwendung bleibt bewusst schlank:

- kein schweres Framework
- kein Composer-Zwang im Regelbetrieb
- kein Node-Build im Regelbetrieb
- klassischer Front Controller unter `public/`
- SQLite für kleine Installationen
- lokale `pdf.js`-Assets für die Browser-Vorschau

## Einstieg nach Thema

- [Installation und lokaler Betrieb](./installation.md)
  Falls du die Anwendung aufsetzen, lokal starten oder auf einer frischen Maschine vorbereiten willst.
- [Konfiguration](./configuration.md)
  Falls du Basiswerte, lokale `config/app.php`-Overrides, Upload-Limits, Tool-Pfade oder den Initial-Admin anpassen willst.
- [Architektur](./architecture.md)
  Falls du verstehen willst, wie Requests, Services, Views und Storage zusammenspielen.
- [Betrieb und Workflows](./operations.md)
  Falls du den Upload-Prozess, PDF-Verarbeitung, Branding, Logging oder Updates suchst.
- [Deployment](./deployment.md)
  Falls du Apache, Nginx, IIS oder Shared Hosting bei STRATO, IONOS und netcup vorbereitest.

## Funktionen

- öffentliche Startseite mit aktivem Speiseplan
- Login / Logout
- Adminbereich für Speisepläne, Benutzer, Einstellungen, Logs und Updates
- Upload von `PDF` und optional `DOCX`
- serverseitige Vorschau- und Aktivierungsstrecke
- Benutzerverwaltung mit Rollen `admin` und `editor`
- pflegbarer Hinweisbanner
- mehrere Theme-Presets inklusive CJD-Theme plus Akzentfarbe
- austauschbares App-Icon oder Kundenlogo
- Semantic Versioning über [`package.json`](../package.json)

## Empfohlene Lesereihenfolge

1. [Installation und lokaler Betrieb](./installation.md)
2. [Konfiguration](./configuration.md)
3. [Betrieb und Workflows](./operations.md)
4. [Deployment](./deployment.md)
5. [Architektur](./architecture.md)

## Quellcode-Einstiegspunkte

- [`public/index.php`](../public/index.php) als HTTP-Einstiegspunkt
- [`app/bootstrap.php`](../app/bootstrap.php) für Bootstrapping und Container
- [`app/routes.php`](../app/routes.php) für alle Routen
- [`app/Services/`](../app/Services) für fachliche Logik
- [`storage/`](../storage) für SQLite, Uploads, generierte PDFs, Logs und Cache
