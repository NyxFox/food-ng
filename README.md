# Food NG

Kleines, produktionsnahes Mini-CMS für Speisepläne auf Basis von PHP, SQLite, Vanilla JS und pdf.js.

Die Anwendung ist bewusst schlank gehalten:

- kein schweres Framework
- kein Composer-Zwang
- kein Node-Build im Regelbetrieb
- klassischer Front Controller unter `public/`
- SQLite für kleine Installationen
- Upload von genau zwei Dokumenten: `normal` und `vegetarisch`
- finale Ausgabe immer als 2-seitiges PDF

## Architektur

Die App nutzt eine einfache MVC-artige Struktur mit Front Controller:

- `public/index.php` ist der einzige Einstiegspunkt
- `app/Core` enthält Router, Request, View und Basisklassen
- `app/Controllers` kapselt HTTP-Flows
- `app/Services` kapselt Datenbank, Auth, Upload, PDF/DOCX-Verarbeitung, Logging und Updates
- `app/Views` enthält PHP-Templates
- `storage/` hält SQLite, Uploads, generierte PDFs und Logdateien außerhalb des Webroots

Technische Kernentscheidungen:

- Authentifizierung: Session-basiert
- Passwörter: `password_hash()` / `password_verify()`
- CSRF: Session-Token pro Formular
- PDF-Rendering im Browser: lokale `pdf.js`-Assets
- PDF-Merge: `qpdf`, wenn verfügbar
- DOCX -> PDF: `LibreOffice/soffice` optional, sauber gekapselt
- Updates: optionaler Git-Runner, nur bei sauberem Repository

## Ordnerstruktur

```text
food-ng/
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Helpers/
│   ├── Services/
│   ├── Views/
│   │   ├── admin/
│   │   ├── auth/
│   │   ├── layouts/
│   │   └── partials/
│   ├── bootstrap.php
│   └── routes.php
├── config/
│   ├── app.example.php
│   └── app.php
├── database/
│   └── schema.sql
├── docker/
│   └── php/
│       └── Dockerfile
├── public/
│   ├── assets/
│   │   ├── css/app.css
│   │   ├── js/app.js
│   │   ├── js/pdf-viewer.js
│   │   └── vendor/pdfjs/
│   ├── .htaccess
│   ├── index.php
│   └── web.config
├── scripts/
│   ├── check_requirements.php
│   ├── serve.bat
│   └── serve.sh
├── storage/
│   ├── cache/
│   ├── generated/
│   ├── logs/
│   ├── uploads/
│   └── .gitignore
├── .gitignore
├── compose.yaml
└── README.md
```

## Funktionen

- Öffentliche Startseite mit aktivem Speiseplan
- Zwei PDF-Seiten untereinander per pdf.js
- Login / Logout
- Dateiverwaltung für Speisepläne
- Upload für `normal` + `vegetarisch`
- PDF/DOCX gemischt möglich
- DOCX-Konvertierung modular und optional
- Vorschau vor Aktivierung
- Benutzerverwaltung mit `admin` / `editor`
- pflegbarer Hinweisbanner
- helles / dunkles Theme plus Akzentfarbe
- Logansicht
- optionale Git-Updatefunktion
- Semantic Versioning über `package.json`
- austauschbares App-Icon / Kundenlogo im Adminbereich

## SQLite-Schema und Migration

Die Migration läuft automatisch beim ersten Request über `app/Services/Migrator.php`.

Schema-Datei: [`database/schema.sql`](./database/schema.sql)

Tabellen:

- `users`
- `meal_plans`
- `settings`
- `logs`

Wichtige Spalten in `meal_plans`:

- `id`
- `title`
- `original_normal_filename`
- `original_vegetarian_filename`
- `normal_source_type`
- `vegetarian_source_type`
- `normal_storage_path`
- `vegetarian_storage_path`
- `merged_pdf_path`
- `preview_status`
- `status`
- `is_active`
- `created_at`
- `updated_at`
- `created_by`

## Voraussetzungen

### Minimaler Regelbetrieb

- PHP 8.1+ empfohlen, getestet für 8.3
- PHP-Erweiterungen:
  - `pdo_sqlite`
  - `fileinfo`
  - `openssl`
  - `session`
  - `mbstring`
- Schreibrechte auf `storage/`
- `proc_open` darf für optionale externe Tools nicht deaktiviert sein

### Für PDF-Verarbeitung

- `qpdf` für:
  - Seitenzahl prüfen
  - 2 PDFs zusammenführen

### Optional für DOCX -> PDF

- `LibreOffice` bzw. `soffice` im PATH oder per Config-Pfad

### Optional für Update-Funktion

- `git`
- Webserver-/PHP-Benutzer muss im Repository lesen und `git pull` ausführen dürfen

## Konfiguration

Zentrale Konfigurationsdatei:

- [`config/app.php`](./config/app.php)

Vorlage:

- [`config/app.example.php`](./config/app.example.php)

Wichtige Optionen:

- `app.base_path`
  - falls die App in einem Unterordner läuft
- `paths.database`
  - SQLite-Datei
- `security.max_upload_bytes`
  - Upload-Limit
- `security.max_branding_upload_bytes`
  - Upload-Limit für App-Icon / Kundenlogo
- `commands.qpdf`
  - optionaler fester Pfad zu `qpdf`
- `commands.soffice`
  - optionaler fester Pfad zu `soffice`
- `commands.git`
  - optionaler fester Pfad zu `git`
- `features.update_runner`
  - Update-Funktion an/aus
- `updates.repo_path`
  - Repository für `git pull`
- `setup.default_admin_*`
  - Initial-Admin

## Fresh Setup

Die Anwendung bleibt bewusst cross-platform:

- Kernruntime ist reines PHP mit SQLite
- keine Composer-Pflicht im Betrieb
- keine Node-Build-Pipeline im Betrieb
- keine Symlink-Pflicht für Uploads oder Branding-Dateien
- hochgeladene PDFs und App-Icons werden per PHP ausgeliefert und müssen nicht in den Webroot gelegt werden

Für alle Plattformen gilt:

1. Repository auschecken
2. `config/app.example.php` nach `config/app.php` kopieren
3. Schreibrechte auf `storage/` sicherstellen
4. Optional Pfade für `git`, `qpdf` und `soffice` in `config/app.php` oder per Umgebungsvariablen setzen
5. `php scripts/check_requirements.php` ausführen
6. App lokal oder im Ziel-Webserver starten

### Ubuntu / Debian

Beispielpakete für einen frischen Host:

```bash
sudo apt update
sudo apt install -y php php-cli php-fpm php-sqlite3 php-mbstring git qpdf libreoffice
```

Projekt vorbereiten:

```bash
cp config/app.example.php config/app.php
mkdir -p storage/uploads storage/generated storage/logs storage/cache
chmod -R 775 storage
php scripts/check_requirements.php
```

Wichtige Hinweise:

- bei Apache den `DocumentRoot` auf `public/` setzen
- bei Nginx `root` auf `public/` zeigen lassen und PHP via FPM anbinden
- wenn `soffice`, `qpdf` oder `git` nicht im PATH liegen, feste Pfade in `config/app.php` setzen

### Rocky Linux / AlmaLinux / RHEL-kompatibel

Typische Pakete:

```bash
sudo dnf install -y php php-cli php-fpm php-sqlite3 php-mbstring git qpdf libreoffice
```

Danach gelten dieselben Projektschritte wie unter Ubuntu/Debian.

### Windows mit IIS

Empfohlene Basis:

- PHP 8.1+ für IIS
- aktivierte Erweiterungen `pdo_sqlite`, `fileinfo`, `openssl`, `mbstring`
- Rewrite-Modul für IIS
- optional: Git for Windows, qpdf für Windows, LibreOffice

Projekt vorbereiten:

1. Repository entpacken oder klonen
2. `config/app.example.php` zu `config/app.php` kopieren
3. Dem IIS-AppPool-Schreibrechte auf `storage\` geben
4. Site-Root auf [`public/`](./public/) setzen
5. Falls Tools nicht global im PATH liegen, Windows-Pfade in `config/app.php` eintragen

Beispiel:

```php
'commands' => [
    'qpdf' => 'C:\\Program Files\\qpdf\\bin\\qpdf.exe',
    'soffice' => 'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
    'git' => 'C:\\Program Files\\Git\\cmd\\git.exe',
],
```

Hinweise:

- [`public/web.config`](./public/web.config) ist bereits enthalten
- hochgeladene Dateien bleiben außerhalb des Webroots und funktionieren unter IIS ohne zusätzliche Freigaben
- für die Update-Funktion muss `git pull` unter dem AppPool-Benutzer erlaubt sein

### Windows mit Apache / XAMPP

Auch hier bleibt die App lauffähig, solange:

- Apache auf `public/` zeigt
- `mod_rewrite` aktiv ist
- PHP die benötigten Erweiterungen geladen hat
- `storage\` beschreibbar ist

Pragmatischer Ablauf:

1. Projekt nach `C:\xampp\htdocs\food-ng` oder einen anderen Pfad legen
2. virtuellen Host oder Apache-DocumentRoot auf `...\food-ng\public` setzen
3. `config/app.php` anpassen
4. `php scripts/check_requirements.php` in einer Konsole ausführen

### Docker

Für lokale oder homogene Deployments ist Docker Compose weiterhin eine gute Option:

```bash
docker compose up --build
```

Damit bekommst du bereits:

- PHP CLI
- `pdo_sqlite`
- `qpdf`
- `git`

Wenn DOCX-Konvertierung benötigt wird, sollte das Image zusätzlich LibreOffice enthalten oder `soffice` in einem angepassten Image bereitgestellt werden.

## Plattform-Checkliste

Vor dem Livegang auf Linux oder Windows sollten diese Punkte erfüllt sein:

- `storage/` ist beschreibbar
- SQLite-Datei kann angelegt und beschrieben werden
- `pdo_sqlite`, `fileinfo`, `openssl`, `mbstring` sind aktiv
- `proc_open` ist aktiv, falls Update-Runner, qpdf oder LibreOffice genutzt werden
- `qpdf` ist installiert, wenn PDFs serverseitig geprüft und zusammengeführt werden sollen
- `LibreOffice/soffice` ist installiert, wenn DOCX-Uploads erlaubt sein sollen
- `git` ist installiert, wenn die Update-Funktion verwendet werden soll
- `app.base_path` ist gesetzt, falls die App nicht im Domain-Root läuft
- bei Windows sind Pfade mit Backslashes korrekt escaped oder alternativ mit `/` angegeben

## Default-Admin / Initial-Setup

Beim ersten Start wird automatisch ein Administrator angelegt, falls die Tabelle `users` leer ist.

Standardwerte aus `config/app.php`:

- Benutzername: `admin`
- Anzeigename: `Administrator`
- Passwort: `change-me-now!`
- Rolle: `admin`

Wichtig:

1. Nach dem ersten Login Passwort sofort im Adminbereich ändern.
2. Vor produktivem Betrieb `config/app.php` anpassen.
3. Falls kein Auto-Admin gewünscht ist, `setup.auto_create_default_admin` auf `false` setzen.

## Schreibrechte

Der PHP-/Webserver-Benutzer braucht Schreibrechte auf:

- `storage/database.sqlite`
- `storage/uploads/`
- `storage/generated/`
- `storage/logs/`
- `storage/cache/`

Beispiel Linux:

```bash
mkdir -p storage/uploads storage/generated storage/logs storage/cache
chmod -R 775 storage
```

## Lokal starten

### Variante A: lokal mit vorhandenem PHP

```bash
php scripts/check_requirements.php
php -S 127.0.0.1:8080 -t public
```

oder:

```bash
./scripts/serve.sh
```

unter Windows alternativ:

```bat
scripts\serve.bat
```

Dann öffnen:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

### Variante B: lokal per Docker Compose

Wenn lokal noch kein PHP vorhanden ist:

```bash
docker compose up --build
```

Dann öffnen:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

Hinweis für Linux:

- `compose.yaml` startet den Container absichtlich mit deiner lokalen UID/GID, damit `storage/database.sqlite` und Upload-Dateien nicht als `root` im Projekt landen.
- Falls früher schon einmal eine root-eigene SQLite-Datei entstanden ist, einfach löschen:

```bash
rm -f storage/database.sqlite
```

Die Docker-Entwicklungsumgebung installiert:

- PHP 8.3 CLI
- `pdo_sqlite`
- `qpdf`
- `git`

## Upload- und Vorschau-Workflow

1. Im Adminbereich genau zwei Dateien hochladen:
   - `normal`
   - `vegetarian`
2. Jede Datei wird validiert.
3. PDFs werden direkt übernommen.
4. DOCX-Dateien werden, falls verfügbar, via LibreOffice in PDF konvertiert.
5. Beide PDFs müssen jeweils genau eine Seite haben.
6. Die PDFs werden in definierter Reihenfolge gemerged:
   - Seite 1: normal
   - Seite 2: vegetarisch
7. Der Speiseplan wird als `draft` gespeichert.
8. In der Prüfansicht kann er aktiviert oder verworfen werden.

## DOCX -> PDF-Konvertierung

Die Konvertierung ist bewusst modular gehalten in:

- [`app/Services/DocumentProcessor.php`](./app/Services/DocumentProcessor.php)

Verhalten:

- wenn `soffice` verfügbar ist:
  - DOCX wird serverseitig konvertiert
- wenn `soffice` nicht verfügbar ist:
  - PDF-Uploads funktionieren weiter
  - DOCX-Upload wird mit klarer Fehlermeldung abgebrochen
  - die App bleibt ansonsten vollständig nutzbar

Beispiel Konfigurationsoption:

```php
'commands' => [
    'soffice' => '/usr/bin/soffice',
]
```

## Hinweise zur PDF-Zusammenführung

PDF-Merge und Seitenzahlprüfung laufen über `qpdf`.

Wenn `qpdf` fehlt:

- Uploads werden sauber abgebrochen
- der Adminbereich zeigt den Status verständlich an

Beispiel Konfigurationsoption:

```php
'commands' => [
    'qpdf' => '/usr/bin/qpdf',
]
```

## Deployment

Wichtig:

- Document Root immer auf `public/` setzen
- `storage/` darf nicht öffentlich ausführbar sein
- SQLite-Datei und Upload-Verzeichnisse außerhalb direkter Upload-Ausführung halten

### Apache

- `public/.htaccess` ist bereits enthalten
- `mod_rewrite` muss aktiv sein
- `DocumentRoot` auf `.../food-ng/public`

### Nginx

Beispiel:

```nginx
server {
    listen 80;
    server_name example.test;
    root /var/www/food-ng/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

### IIS

- `public/web.config` ist enthalten
- Site-Root auf `public/` setzen
- PHP für IIS korrekt zuweisen

### Einfacher Webspace

Wenn nur ein Unterordner möglich ist:

1. App hochladen
2. Webroot auf `public/` legen oder dessen Inhalt dort bereitstellen
3. `app.base_path` in `config/app.php` setzen, wenn die App unter einem Unterpfad erreichbar ist

## Update-Funktion

Die Update-Funktion ist absichtlich pragmatisch:

- nur für Admins sichtbar
- nur aktiv, wenn `features.update_runner = true`
- nur nutzbar, wenn:
  - `git` vorhanden ist
  - das konfigurierte Verzeichnis ein Git-Repo ist
  - das Working Tree sauber ist

Ablauf:

1. `git fetch --all --prune`
2. `git pull --ff-only origin <branch>`

Die App-Version wird dabei aus `package.json` gelesen und im Adminbereich angezeigt. Wenn ein Update eine neue Release-Version mitbringt, wird diese nach dem `git pull` automatisch übernommen und im Update-Log als Versionswechsel dokumentiert.

Wenn das nicht möglich ist, zeigt die UI den Grund an.

## Versionierung

Das Projekt nutzt Semantic Versioning im Feld `version` der Datei [`package.json`](./package.json).

Für lokale Release-Bumps gibt es drei Hilfsskripte:

- `npm run version:patch`
- `npm run version:minor`
- `npm run version:major`

Diese Befehle aktualisieren `package.json` und `package-lock.json`, ohne automatisch einen Git-Tag zu erzeugen.

## Branding / App-Icon

Im Adminbereich unter Einstellungen kann ein eigenes App-Icon bzw. Kundenlogo hochgeladen werden.

Eigenschaften:

- akzeptiert `PNG`, `JPG`, `WEBP` und `ICO`
- wird außerhalb des Webroots unter `storage/uploads/branding/` gespeichert
- erscheint in der Navigation und als Browser-Favicon
- bleibt auf Linux und Windows ohne zusätzliche Webserver-Mounts nutzbar, weil die Datei per Route ausgeliefert wird

Einschränkungen:

- auf klassischem Shared Hosting oft nicht praktikabel
- auf Windows/IIS nur sinnvoll, wenn `git` ausführbar und das Repository korrekt vorhanden ist

## Sicherheit und Pragmatik

Umgesetzt:

- Session-Login
- Passwort-Hashes
- CSRF-Schutz
- Upload-Validierung
- keine direkte Ausführung hochgeladener Dateien
- Uploads und generierte PDFs außerhalb des Webroots
- Aktionslogging in Datenbank und Logdatei

Bewusst simpel gehalten:

- keine komplexe Rollenmatrix
- kein WYSIWYG
- kein Dependency-Injector-Overkill
- kein ORM
- kein Composer-Zwang

## Beispiel-Daten / Seeder

Es gibt absichtlich keinen Demo-Seeder für Speisepläne.

Grund:

- reale PDF/DOCX-Flows sollen unverfälscht bleiben
- Initial-Admin wird bereits automatisch erzeugt

## Wichtige Dateien

- Einstiegspunkt: [`public/index.php`](./public/index.php)
- Routen: [`app/routes.php`](./app/routes.php)
- Migration: [`database/schema.sql`](./database/schema.sql)
- Upload-/PDF-Logik: [`app/Services/DocumentProcessor.php`](./app/Services/DocumentProcessor.php)
- Meal-Plan-Workflow: [`app/Services/MealPlanService.php`](./app/Services/MealPlanService.php)
- Auth: [`app/Services/AuthService.php`](./app/Services/AuthService.php)
- Branding: [`app/Services/BrandingService.php`](./app/Services/BrandingService.php)
- Einstellungen: [`app/Services/SettingsService.php`](./app/Services/SettingsService.php)
- Update-Runner: [`app/Services/UpdateService.php`](./app/Services/UpdateService.php)

## Schnelltest nach Installation

1. Startseite aufrufen
2. Mit Default-Admin anmelden
3. Zwei 1-seitige PDFs hochladen
4. Prüfansicht öffnen
5. Aktivieren
6. Startseite neu laden
7. Banner / Theme / Impressum / Datenschutz im Admin ändern
8. Logs und Benutzerverwaltung prüfen

Damit sind die Akzeptanzkriterien der ersten Version abgedeckt.
