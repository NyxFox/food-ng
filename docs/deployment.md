# Deployment

Zurück zum [Dokumentationsindex](./index.md).

## Grundregeln

- Document Root immer auf `public/` setzen
- `storage/` darf nicht öffentlich ausführbar sein
- SQLite-Datei, Uploads und generierte PDFs außerhalb direkter Web-Ausführung halten
- `app.base_path` setzen, wenn die App unter einem Unterpfad läuft

## Preflight-Checkliste

Vor dem Livegang sollten diese Punkte erfüllt sein:

- `storage/` ist beschreibbar
- die SQLite-Datei kann angelegt und beschrieben werden
- `pdo_sqlite`, `fileinfo`, `openssl` und `mbstring` sind aktiv
- `proc_open` ist aktiv, falls Update-Runner, `qpdf` oder LibreOffice genutzt werden
- `qpdf` ist installiert, wenn PDFs serverseitig geprüft und zusammengeführt werden sollen
- LibreOffice beziehungsweise `soffice` ist installiert, wenn DOCX-Uploads erlaubt sein sollen
- `git` ist installiert, wenn die Update-Funktion verwendet werden soll
- `app.base_path` ist gesetzt, falls die App nicht im Domain-Root läuft
- auf Windows sind Pfade korrekt escaped oder mit `/` angegeben

## Apache

- [`public/.htaccess`](../public/.htaccess) ist bereits enthalten
- `mod_rewrite` muss aktiv sein
- `DocumentRoot` zeigt auf `.../food-ng/public`

## Nginx

Beispielkonfiguration:

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

## IIS

- [`public/web.config`](../public/web.config) ist bereits enthalten
- Site-Root auf `public/` setzen
- PHP für IIS korrekt zuweisen

## Einfacher Webspace

Wenn nur ein Unterordner möglich ist:

1. Anwendung hochladen.
2. Webroot auf `public/` legen oder dessen Inhalt dort bereitstellen.
3. `app.base_path` in [`config/app.php`](../config/app.php) setzen, wenn die App unter einem Unterpfad erreichbar ist.

## Shared Hosting bei STRATO, IONOS und netcup

Die Anwendung passt grundsätzlich gut zu klassischem Webspace:

- kein Composer-Zwang im Regelbetrieb
- kein Node-Build im Regelbetrieb
- klassischer Apache-Front-Controller unter `public/`
- SQLite statt separater Datenbankserver

Vor dem Hochladen trotzdem ehrlich prüfen:

- PHP 8.1+ ist im Tarif auswählbar, idealerweise `8.3`
- `pdo_sqlite`, `fileinfo`, `openssl` und `mbstring` sind aktiv
- `storage/` ist beschreibbar
- `proc_open` ist aktiv, wenn externe Tools genutzt werden sollen
- `qpdf` ist vorhanden, wenn der Upload-Workflow produktiv genutzt werden soll

Wichtig: Ohne `qpdf` läuft der Upload von Speiseplänen nicht sauber durch. Ohne `soffice` funktionieren `DOCX`-Uploads nicht, PDF-Uploads aber weiterhin.

### Empfohlene Struktur

Am saubersten ist diese Struktur im Webspace:

```text
food-ng/
├── app/
├── config/
├── docs/
├── public/
├── storage/
└── ...
```

Die Domain oder Subdomain sollte dabei auf `food-ng/public/` zeigen, nicht auf die Projektwurzel.

### Variante A: Domain oder Subdomain zeigt direkt auf `public/`

Das ist die bevorzugte Variante und funktioniert auf netcup direkt über den konfigurierbaren `Document Root`, bei IONOS über die Verbindung einer Domain mit einem Webspace-Verzeichnis und bei STRATO typischerweise über ein eigenes Unterverzeichnis im Webspace.

Vorgehen:

1. Projekt vollständig hochladen, zum Beispiel nach `food-ng/`.
2. [`config/app.example.php`](../config/app.example.php) nach `config/app.php` kopieren.
3. `storage/uploads`, `storage/generated`, `storage/logs` und `storage/cache` anlegen.
4. Domain, Subdomain oder Zielverzeichnis auf `food-ng/public/` setzen.
5. `app.base_path` leer lassen, solange die App unter der Domain direkt im URL-Root läuft.
6. Falls der Tarif kein `git` oder `proc_open` erlaubt, `features.update_runner` in [`config/app.php`](../config/app.php) auf `false` setzen.
7. Falls `qpdf` oder `soffice` nicht im `PATH` liegen, feste Pfade in [`config/app.php`](../config/app.php) hinterlegen.

Wenn die App stattdessen unter einer URL wie `https://example.tld/food-ng/` erreichbar ist, muss `app.base_path` auf `/food-ng` gesetzt werden.

### Variante B: Fester Webroot wie `htdocs`, `httpdocs` oder `html`

Falls der Hoster den Webroot nicht auf `public/` umstellen kann, bleibt als Fallback:

1. vollständiges Projekt in ein Unterverzeichnis hochladen, zum Beispiel `food-ng/`
2. den kompletten Inhalt von [`public/`](../public/) in den echten Webroot kopieren
3. die beiden `require`-Zeilen in der kopierten `index.php` auf das hochgeladene Projektverzeichnis anpassen
4. sicherstellen, dass die kopierte `.htaccess` im Webroot liegt
5. `storage/` im Projektverzeichnis belassen und nicht in den Webroot verschieben

Beispiel für eine angepasste `index.php`, wenn Webroot und Projektordner nebeneinander liegen:

```php
$container = require __DIR__ . '/../food-ng/app/bootstrap.php';
$router = require __DIR__ . '/../food-ng/app/routes.php';
```

Diese Fallback-Variante ist wartungsintensiver, weil Änderungen an [`public/index.php`](../public/index.php), Assets oder Rewrite-Dateien bei Deployments in den echten Webroot übernommen werden müssen.

### Anbieterhinweise

- STRATO: Arbeite mit einem Unterverzeichnis wie `food-ng/public`, nicht mit der Projektwurzel. Gerade auf der neuen Plattform werden Domains und Subdomains auf Unterverzeichnisse gelegt.
- IONOS: Verbinde die Domain direkt mit dem Verzeichnis `food-ng/public`. Wenn die Domain auf ein Unterverzeichnis statt auf den URL-Root zeigt, `app.base_path` passend setzen.
- netcup: Setze den `Document Root` der Domain oder Subdomain direkt auf `food-ng/public`. Das ist in der Regel die sauberste Shared-Hosting-Variante für dieses Projekt.

### Shared-Hosting-Checkliste nach dem Upload

1. PHP-Version im Hoster-Panel auf `8.3` oder kompatibel setzen.
2. Schreibrechte für `storage/` prüfen.
3. Optional per SSH `php scripts/check_requirements.php` ausführen.
4. Startseite aufrufen.
5. Mit dem Default-Admin anmelden.
6. Im Adminbereich prüfen, ob `qpdf`, `soffice` und `git` wie erwartet erkannt wurden.
7. Einen echten Test-Upload mit zwei einseitigen PDFs durchführen.

## Zusammenhang mit dem Betrieb

Für Upload-Workflows, Branding, Updates und externe Tools siehe auch:

- [Konfiguration](./configuration.md)
- [Betrieb und Workflows](./operations.md)
