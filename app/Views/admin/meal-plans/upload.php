<section class="panel panel--narrow">
    <div class="panel__header">
        <div>
            <p class="eyebrow">Upload</p>
            <h1>Neuen Speiseplan hochladen</h1>
            <p class="muted">Genau zwei Dateien: Seite 1 normal, Seite 2 vegetarisch. Erlaubt sind PDF oder DOCX.</p>
        </div>
    </div>

    <div class="capability-grid">
        <article class="mini-card">
            <strong>PDF-Merge</strong>
            <p><?= $documentStatus['merge_available'] ? 'Verfügbar' : 'Nicht verfügbar' ?></p>
        </article>
        <article class="mini-card">
            <strong>DOCX-Konvertierung</strong>
            <p><?= $documentStatus['docx_conversion_available'] ? 'Verfügbar' : 'Nicht verfügbar' ?></p>
        </article>
    </div>

    <form class="form-grid" method="post" action="<?= e(url('admin/meal-plans/upload')) ?>" enctype="multipart/form-data">
        <?= csrf_field($csrfToken) ?>

        <label>
            <span>Titel / Name</span>
            <input type="text" name="title" required maxlength="120" value="<?= e(old($oldInput, 'title')) ?>" placeholder="z. B. Speiseplan KW 17">
        </label>

        <label>
            <span>Datei normal</span>
            <input type="file" name="normal" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
        </label>

        <label>
            <span>Datei vegetarisch</span>
            <input type="file" name="vegetarian" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
        </label>

        <div class="callout">
            <strong>Verarbeitung</strong>
            <p>Beide Dateien werden geprüft, bei Bedarf in PDF konvertiert und zu einem finalen 2-seitigen PDF zusammengeführt. Erst danach folgt die Prüfansicht.</p>
        </div>

        <button type="submit" class="button" <?= !$documentStatus['merge_available'] ? 'disabled' : '' ?>>Hochladen und prüfen</button>
    </form>
</section>
