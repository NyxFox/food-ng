# Architektur

Zurück zum [Dokumentationsindex](./index.md).

## Grundaufbau

Food NG nutzt eine einfache MVC-artige Struktur mit Front Controller:

- [`public/index.php`](../public/index.php) ist der einzige Einstiegspunkt.
- [`app/bootstrap.php`](../app/bootstrap.php) baut Konfiguration, Container und gemeinsame Services auf.
- [`app/routes.php`](../app/routes.php) verdrahtet alle HTTP-Routen.
- `app/Controllers` kapselt HTTP-Flows.
- `app/Services` kapselt Geschäftslogik und Integrationen.
- `app/Views` enthält PHP-Templates.
- `storage/` liegt außerhalb des Webroots und hält Datenbank, Uploads, generierte PDFs, Logs und Cache.

## Request-Flow

1. Der Webserver zeigt auf `public/`.
2. [`public/index.php`](../public/index.php) lädt das Bootstrapping.
3. Der Router aus [`app/routes.php`](../app/routes.php) wählt Controller und Action.
4. Controller delegieren Fachlogik an Services.
5. Views rendern HTML, während Datei- und PDF-Routen Inhalte gezielt aus dem Storage ausliefern.

## Wichtige Controller

- `HomeController` für Startseite, Rechtstexte, PDF-Ausgabe und App-Icon
- `AuthController` für Login und Logout
- `MealPlanController` für Upload, Vorschau, Aktivierung, Archivierung und Download
- `UserController` für Benutzerverwaltung
- `SettingsController` für Banner, Theme, Rechtstexte und Branding
- `LogsController` für die Logansicht
- `UpdateController` für den optionalen Git-basierten Update-Runner

## Wichtige Services

- [`AuthService`](../app/Services/AuthService.php) für Session-Login und Rollenprüfung
- [`CsrfService`](../app/Services/CsrfService.php) für Formschutz
- [`MealPlanService`](../app/Services/MealPlanService.php) für Speiseplan-Lebenszyklus
- [`DocumentProcessor`](../app/Services/DocumentProcessor.php) für Upload-Validierung, DOCX-Konvertierung und PDF-Prüfung
- [`BrandingService`](../app/Services/BrandingService.php) für App-Icon und Kundenlogo
- [`SettingsService`](../app/Services/SettingsService.php) für persistente Oberflächen-Einstellungen
- [`UpdateService`](../app/Services/UpdateService.php) für `git fetch` und `git pull`
- [`LoggerService`](../app/Services/LoggerService.php) für Datenbank- und Dateilogs
- [`Migrator`](../app/Services/Migrator.php) für die automatische Schema-Anlage beim ersten Request
- [`VersionService`](../app/Services/VersionService.php) für die Anzeige der App-Version aus [`package.json`](../package.json)

## Technische Leitplanken

- Authentifizierung ist session-basiert.
- Passwörter werden mit `password_hash()` und `password_verify()` verarbeitet.
- Formulare nutzen CSRF-Tokens aus der Session.
- Browserseitiges PDF-Rendering nutzt lokale `pdf.js`-Assets.
- PDF-Merge und Seitenzahlprüfung laufen über `qpdf`, wenn vorhanden.
- `DOCX -> PDF` läuft optional über `LibreOffice` beziehungsweise `soffice`.
- Git-Updates sind optional und nur für saubere Repositories gedacht.

## Verzeichnisstruktur

```text
food-ng/
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Helpers/
│   ├── Services/
│   ├── Views/
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
├── compose.yaml
└── README.md
```

## Datenmodell

Das Schema liegt in [`database/schema.sql`](../database/schema.sql) und wird beim ersten Request automatisch eingespielt.

Tabellen:

- `users`
- `meal_plans`
- `settings`
- `logs`

Wichtige Spalten in `meal_plans`:

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

## Relevante Dateien

- Einstiegspunkt: [`public/index.php`](../public/index.php)
- Routen: [`app/routes.php`](../app/routes.php)
- Migration: [`database/schema.sql`](../database/schema.sql)
- Upload- und PDF-Logik: [`app/Services/DocumentProcessor.php`](../app/Services/DocumentProcessor.php)
- Speiseplan-Workflow: [`app/Services/MealPlanService.php`](../app/Services/MealPlanService.php)
- Einstellungen: [`app/Services/SettingsService.php`](../app/Services/SettingsService.php)
- Branding: [`app/Services/BrandingService.php`](../app/Services/BrandingService.php)
- Update-Runner: [`app/Services/UpdateService.php`](../app/Services/UpdateService.php)
