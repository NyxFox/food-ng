# Konfiguration

Zurück zum [Dokumentationsindex](./index.md).

## Dateien

- aktive Konfiguration: [`config/app.php`](../config/app.php)
- Vorlage: [`config/app.example.php`](../config/app.example.php)

Die Anwendung liest ihre zentrale Konfiguration aus `config/app.php`. Die Vorlage in `config/app.example.php` ist der Startpunkt für neue Installationen.

## Bereich `app`

- `app.name`
  Anzeigename der Anwendung
- `app.base_path`
  erforderlich, wenn die App in einem Unterordner läuft
- `app.timezone`
  Standard-Zeitzone der Anwendung
- `app.debug`
  aktiviert ausführlichere Fehlerausgaben für Entwicklung

## Bereich `paths`

- `paths.root`
  Projektwurzel
- `paths.storage`
  Basispfad für Storage-Dateien
- `paths.database`
  Speicherort der SQLite-Datei
- `paths.log_file`
  Pfad zur Anwendungs-Logdatei

## Bereich `security`

- `security.session_name`
  Session-Cookie-Name
- `security.csrf_field`
  Formularfeldname für CSRF-Tokens
- `security.max_upload_bytes`
  Upload-Limit für Speiseplan-Dokumente
- `security.max_branding_upload_bytes`
  Upload-Limit für App-Icon und Kundenlogo

## Bereich `features`

- `features.update_runner`
  aktiviert oder deaktiviert die Git-basierte Update-Funktion im Adminbereich

## Bereich `commands`

- `commands.qpdf`
  optionaler fester Pfad zu `qpdf`
- `commands.soffice`
  optionaler fester Pfad zu `soffice`
- `commands.git`
  optionaler fester Pfad zu `git`

Wenn diese Werte `null` bleiben, versucht die Anwendung die Tools über den `PATH` zu finden.

## Bereich `updates`

- `updates.repo_path`
  Repository-Pfad, in dem Updates ausgeführt werden
- `updates.branch`
  Ziel-Branch für `git pull --ff-only`

## Bereich `setup`

- `setup.auto_create_default_admin`
  legt beim ersten Request automatisch einen Admin an
- `setup.default_admin_username`
- `setup.default_admin_display_name`
- `setup.default_admin_password`
- `setup.default_admin_role`

Diese Werte sollten vor produktivem Betrieb angepasst werden.

## Bereich `defaults.settings`

Hier liegen die Startwerte für:

- Seitentitel und Untertitel
- Banner-Anzeige, Stil und Text
- Theme-Modus und Akzentfarbe
- GitHub-URL
- Branding-Dateien
- Impressum
- Datenschutz

## Typische Anpassungen

### Betrieb unter Unterpfad

Wenn die Anwendung nicht direkt unter dem Domain-Root läuft, `app.base_path` setzen.

Beispiel:

```php
'app' => [
    'base_path' => '/food-ng',
],
```

### Feste Tool-Pfade unter Linux

```php
'commands' => [
    'qpdf' => '/usr/bin/qpdf',
    'soffice' => '/usr/bin/soffice',
    'git' => '/usr/bin/git',
],
```

### Feste Tool-Pfade unter Windows

```php
'commands' => [
    'qpdf' => 'C:\\Program Files\\qpdf\\bin\\qpdf.exe',
    'soffice' => 'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
    'git' => 'C:\\Program Files\\Git\\cmd\\git.exe',
],
```

## Konfigurationshinweise

- `qpdf` wird benötigt, wenn PDFs serverseitig geprüft und zusammengeführt werden sollen.
- `soffice` wird nur für `DOCX -> PDF` benötigt.
- `git` wird nur für den optionalen Update-Runner benötigt.
- Auf Windows müssen Backslashes korrekt escaped sein. Alternativ funktionieren oft auch Pfade mit `/`.
