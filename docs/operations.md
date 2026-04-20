# Betrieb und Workflows

Zurück zum [Dokumentationsindex](./index.md).

## Speiseplan-Workflow

Der Adminbereich erwartet pro Speiseplan genau zwei Dokumente:

- `normal`
- `vegetarisch`

Ablauf:

1. Beide Dateien werden hochgeladen.
2. Jede Datei wird validiert.
3. PDFs werden direkt übernommen.
4. DOCX-Dateien werden, falls möglich, via LibreOffice in PDF umgewandelt.
5. Beide PDFs müssen jeweils genau eine Seite haben.
6. Die PDFs werden in definierter Reihenfolge gemerged.
7. Der Speiseplan wird als `draft` gespeichert.
8. In der Prüfansicht kann er aktiviert oder verworfen werden.

Die finale Ausgabe ist immer ein zusammengeführtes 2-seitiges PDF:

- Seite 1 `normal`
- Seite 2 `vegetarisch`

## `DOCX -> PDF`

Die Konvertierung ist modular in [`app/Services/DocumentProcessor.php`](../app/Services/DocumentProcessor.php) gekapselt.

Verhalten:

- Wenn `soffice` verfügbar ist, wird DOCX serverseitig konvertiert.
- Wenn `soffice` fehlt, funktionieren PDF-Uploads weiterhin.
- DOCX-Uploads werden in diesem Fall mit klarer Fehlermeldung abgebrochen.

## PDF-Prüfung und Merge

Die Seitenzahlprüfung und das Zusammenführen der PDFs laufen über `qpdf`.

Wenn `qpdf` fehlt:

- Uploads werden sauber abgebrochen.
- Der Adminbereich zeigt den Status verständlich an.

## Branding

Im Adminbereich kann ein eigenes App-Icon oder Kundenlogo hochgeladen werden.

Eigenschaften:

- akzeptiert `PNG`, `JPG`, `WEBP` und `ICO`
- wird außerhalb des Webroots unter `storage/uploads/branding/` gespeichert
- erscheint in Navigation und Browser-Favicon
- wird per Route ausgeliefert und braucht keine zusätzlichen Webserver-Mounts

## Updates

Die Update-Funktion ist bewusst pragmatisch:

- nur für Admins sichtbar
- nur aktiv, wenn `features.update_runner = true`
- nur nutzbar, wenn `git` vorhanden ist
- nur nutzbar, wenn das konfigurierte Verzeichnis ein Git-Repository ist
- nur nutzbar, wenn das Working Tree sauber ist

Ablauf:

1. `git fetch --all --prune`
2. `git pull --ff-only origin <branch>`

Die sichtbare Versionsnummer kommt aus [`package.json`](../package.json). Wenn sich diese beim Update ändert, wird der Versionswechsel im Update-Log dokumentiert.

## Versionierung

Das Projekt nutzt Semantic Versioning über das Feld `version` in [`package.json`](../package.json).

Hilfsskripte:

- `npm run hooks:install`
- `npm run version:patch`
- `npm run version:minor`
- `npm run version:major`

Die Befehle aktualisieren `package.json` und `package-lock.json`, ohne automatisch einen Git-Tag zu erzeugen.

Fuer dieses Repository ist ausserdem ein versionierter `pre-commit`-Hook unter [`.githooks/pre-commit`](../.githooks/pre-commit) vorgesehen. Nach `npm run hooks:install` setzt Git lokal `core.hooksPath` auf `.githooks`; danach erhoeht jeder normale `git commit` die Minor-Version automatisch und staged `package.json` sowie `package-lock.json` mit in denselben Commit.

Falls der automatische Sprung in einer Ausnahme bewusst uebersprungen werden soll, geht das mit `git commit --no-verify` oder temporaer mit `FOODNG_SKIP_AUTO_VERSION_BUMP=1 git commit`.

## Logs

Die Anwendung protokolliert Aktionen:

- in der Datenbank
- in der Logdatei unter `storage/logs/`
- im Adminbereich über die Logansicht

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
- kein ORM
- kein Dependency-Injector-Overkill
- kein Composer-Zwang

## Demo-Daten

Es gibt absichtlich keinen Seeder für Beispiel-Speisepläne. Reale PDF- und DOCX-Flows sollen unverfälscht bleiben, während der Initial-Admin bereits automatisch erzeugt werden kann.
