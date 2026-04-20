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

## Zusammenhang mit dem Betrieb

Für Upload-Workflows, Branding, Updates und externe Tools siehe auch:

- [Konfiguration](./configuration.md)
- [Betrieb und Workflows](./operations.md)
