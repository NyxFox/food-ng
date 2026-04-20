# Food NG

Schlankes Mini-CMS für Speisepläne auf Basis von PHP, SQLite, Vanilla JS und `pdf.js`.

Die ausführliche Projektdokumentation liegt nicht mehr in dieser Datei, sondern gebündelt unter [`docs/`](./docs/index.md).

## Schnellstart

1. Konfiguration pruefen:

`config/app.example.php` ist direkt als Basis nutzbar. Nur wenn du lokale Overrides brauchst, legst du optional `config/app.php` an.

2. Storage-Verzeichnisse vorbereiten:

```bash
mkdir -p storage/uploads storage/generated storage/logs storage/cache
chmod -R 775 storage
```

3. Anforderungen prüfen:

```bash
php scripts/check_requirements.php
```

4. Lokal starten:

```bash
php -S 127.0.0.1:8080 -t public
```

oder per Docker:

```bash
docker compose up --build
```

5. Anwendung im Browser öffnen:

- [http://127.0.0.1:8080](http://127.0.0.1:8080)

## Dokumentation

- [Dokumentationsindex](./docs/index.md)
- [Architektur](./docs/architecture.md)
- [Installation und lokaler Betrieb](./docs/installation.md)
- [Konfiguration](./docs/configuration.md)
- [Betrieb und Workflows](./docs/operations.md)
- [Deployment](./docs/deployment.md)

## Projekt in Kurzform

- Front Controller unter [`public/index.php`](./public/index.php)
- einfache MVC-artige Struktur ohne schweres Framework
- SQLite für kleine Installationen
- Upload von genau zwei Dokumenten pro Speiseplan: `normal` und `vegetarisch`
- finale Ausgabe immer als zusammengeführtes 2-seitiges PDF
- optionale externe Tools für `qpdf`, `soffice` und Git-Updates

## Wichtige Dateien

- Einstiegspunkt: [`public/index.php`](./public/index.php)
- Routen: [`app/routes.php`](./app/routes.php)
- Basis-Konfiguration: [`config/app.example.php`](./config/app.example.php)
- Datenbankschema: [`database/schema.sql`](./database/schema.sql)
- Anforderungen prüfen: [`scripts/check_requirements.php`](./scripts/check_requirements.php)
