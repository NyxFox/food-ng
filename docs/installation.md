# Installation und lokaler Betrieb

Zurück zum [Dokumentationsindex](./index.md).

## Voraussetzungen

### Minimaler Regelbetrieb

- PHP 8.1+ empfohlen, getestet für 8.3
- PHP-Erweiterungen `pdo_sqlite`, `fileinfo`, `openssl`, `session` und `mbstring`
- Schreibrechte auf `storage/`
- `proc_open` darf für optionale externe Tools nicht deaktiviert sein

### Optional für PDF-Verarbeitung

- `qpdf` für Seitenzahlprüfung und PDF-Merge

### Optional für `DOCX -> PDF`

- `LibreOffice` beziehungsweise `soffice` im `PATH` oder per festem Konfigurationspfad

### Optional für die Update-Funktion

- `git`
- der Webserver- oder PHP-Benutzer muss das Repository lesen und `git pull` ausführen dürfen

## Basisinstallation

1. Repository auschecken.
2. [`config/app.example.php`](../config/app.example.php) nach `config/app.php` kopieren.
3. Storage-Verzeichnisse anlegen und Schreibrechte setzen.
4. Optional Pfade für `git`, `qpdf` und `soffice` in [`config/app.php`](../config/app.php) anpassen.
5. Anforderungen prüfen:

```bash
php scripts/check_requirements.php
```

## Schreibrechte

Der PHP- oder Webserver-Benutzer braucht Schreibrechte auf:

- `storage/database.sqlite`
- `storage/uploads/`
- `storage/generated/`
- `storage/logs/`
- `storage/cache/`

Beispiel unter Linux:

```bash
mkdir -p storage/uploads storage/generated storage/logs storage/cache
chmod -R 775 storage
```

## Plattformen

### Ubuntu / Debian

Typische Pakete:

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

### Rocky Linux / AlmaLinux / RHEL-kompatibel

Typische Pakete:

```bash
sudo dnf install -y php php-cli php-fpm php-sqlite3 php-mbstring git qpdf libreoffice
```

Danach gelten dieselben Projektschritte wie unter Ubuntu oder Debian.

### Windows mit IIS

Empfohlene Basis:

- PHP 8.1+ für IIS
- aktivierte Erweiterungen `pdo_sqlite`, `fileinfo`, `openssl`, `mbstring`
- Rewrite-Modul für IIS
- optional Git for Windows, `qpdf` und LibreOffice

Projekt vorbereiten:

1. Repository entpacken oder klonen.
2. `config/app.example.php` zu `config/app.php` kopieren.
3. Dem IIS-AppPool Schreibrechte auf `storage\` geben.
4. Site-Root auf [`public/`](../public/) setzen.
5. Falls Tools nicht global im `PATH` liegen, Windows-Pfade in [`config/app.php`](../config/app.php) eintragen.

### Windows mit Apache / XAMPP

Die Anwendung läuft, solange:

- Apache auf `public/` zeigt
- `mod_rewrite` aktiv ist
- PHP die benötigten Erweiterungen geladen hat
- `storage\` beschreibbar ist

Pragmatischer Ablauf:

1. Projekt nach `C:\xampp\htdocs\food-ng` oder an einen anderen Pfad legen.
2. Virtuellen Host oder DocumentRoot auf `...\food-ng\public` setzen.
3. [`config/app.php`](../config/app.php) anpassen.
4. `php scripts/check_requirements.php` in einer Konsole ausführen.

### Docker

Für lokale oder homogene Umgebungen:

```bash
docker compose up --build
```

Die Compose-Umgebung startet den Container mit lokaler `UID:GID`, damit Dateien in `storage/` nicht als `root` im Projekt landen.

Wenn früher bereits eine root-eigene SQLite-Datei entstanden ist:

```bash
rm -f storage/database.sqlite
```

Die Docker-Entwicklungsumgebung bringt bereits mit:

- PHP 8.3 CLI
- `pdo_sqlite`
- `qpdf`
- `git`

## Lokal starten

### Variante A: vorhandenes PHP

```bash
php scripts/check_requirements.php
php -S 127.0.0.1:8080 -t public
```

Alternativ:

```bash
./scripts/serve.sh
```

Unter Windows:

```bat
scripts\serve.bat
```

### Variante B: Docker Compose

```bash
docker compose up --build
```

Danach öffnen:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

## Default-Admin

Beim ersten Start wird automatisch ein Administrator angelegt, falls die Tabelle `users` leer ist.

Standardwerte aus [`config/app.php`](../config/app.php):

- Benutzername `admin`
- Anzeigename `Administrator`
- Passwort `change-me-now!`
- Rolle `admin`

Vor produktivem Betrieb:

1. Passwort nach dem ersten Login sofort ändern.
2. Default-Werte in [`config/app.php`](../config/app.php) anpassen.
3. `setup.auto_create_default_admin` deaktivieren, falls kein Auto-Admin gewünscht ist.

## Schnelltest nach der Installation

1. Startseite aufrufen.
2. Mit dem Default-Admin anmelden.
3. Zwei einseitige PDFs hochladen.
4. Die Prüfansicht öffnen.
5. Den Speiseplan aktivieren.
6. Die Startseite neu laden.
7. Banner, Theme, Impressum und Datenschutz im Adminbereich ändern.
8. Logs und Benutzerverwaltung prüfen.
