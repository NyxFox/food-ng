<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Speiseplan</p>
        <h1><?= e($plan['title']) ?></h1>
        <p class="lead">Detailansicht des finalen 2-seitigen PDFs mit Metadaten und Schnellaktionen.</p>
    </div>

    <div class="button-row">
        <a class="button button--ghost" href="<?= e(url('admin/meal-plans')) ?>">Zur Liste</a>
        <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $plan['id'] . '/download')) ?>">PDF herunterladen</a>
    </div>
</section>

<section class="grid-2">
    <article class="panel">
        <div class="panel__header">
            <div>
                <h2>Metadaten</h2>
            </div>
        </div>

        <dl class="key-value">
            <div>
                <dt>Status</dt>
                <dd>
                    <span class="status status--<?= e(status_badge_class($plan['status'])) ?>"><?= e($plan['status']) ?></span>
                    <?php if ((int) $plan['is_active'] === 1): ?>
                        <span class="status status--success">aktiv</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Prüfstatus</dt>
                <dd><span class="status status--<?= e(status_badge_class($plan['preview_status'])) ?>"><?= e($plan['preview_status']) ?></span></dd>
            </div>
            <div>
                <dt>Upload</dt>
                <dd><?= e(format_datetime($plan['created_at'])) ?></dd>
            </div>
            <div>
                <dt>Ursprung</dt>
                <dd><?= e(source_type_label($plan['normal_source_type'])) ?> / <?= e(source_type_label($plan['vegetarian_source_type'])) ?></dd>
            </div>
            <div>
                <dt>Originaldateien</dt>
                <dd><?= e($plan['original_normal_filename']) ?> · <?= e($plan['original_vegetarian_filename']) ?></dd>
            </div>
            <div>
                <dt>Hinweis</dt>
                <dd><?= e($plan['conversion_notes'] ?: 'Keine zusätzlichen Hinweise.') ?></dd>
            </div>
        </dl>

        <div class="button-row">
            <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $plan['id'] . '/preview')) ?>">Prüfen</a>

            <form method="post" action="<?= e(url('admin/meal-plans/' . $plan['id'] . '/activate')) ?>" class="inline-form">
                <?= csrf_field($csrfToken) ?>
                <button type="submit" class="button" <?= (int) $plan['is_active'] === 1 ? 'disabled' : '' ?>>Aktiv setzen</button>
            </form>
        </div>
    </article>

    <article class="panel">
        <div class="pdf-viewer" data-pdf-url="<?= e(url('meal-plans/' . $plan['id'] . '/pdf')) ?>" data-pages="1,2"></div>
    </article>
</section>
