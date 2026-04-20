<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Dateiverwaltung</p>
        <h1>Speisepläne</h1>
        <p class="lead">Alle Uploads, Entwürfe und aktive Versionen im schnellen Überblick.</p>
    </div>
    <div class="button-row">
        <a class="button" href="<?= e(url('admin/meal-plans/upload')) ?>">Neuen Speiseplan hochladen</a>
    </div>
</section>

<?php if (!$documentStatus['docx_conversion_available']): ?>
    <div class="notice notice--warn">
        DOCX-Konvertierung ist aktuell nicht verfügbar. PDF-Uploads funktionieren weiterhin, DOCX-Uploads werden mit einer klaren Fehlermeldung abgebrochen.
    </div>
<?php endif; ?>

<section class="panel">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Upload</th>
                    <th>Ursprung</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td>
                            <strong><?= e($plan['title']) ?></strong><br>
                            <span class="muted">von <?= e($plan['display_name'] ?: $plan['username']) ?></span>
                        </td>
                        <td><?= e(format_datetime($plan['created_at'])) ?></td>
                        <td><?= e(source_type_label($plan['normal_source_type'])) ?> / <?= e(source_type_label($plan['vegetarian_source_type'])) ?></td>
                        <td>
                            <?php if ((int) $plan['is_active'] === 1): ?>
                                <span class="status status--success">aktiv</span>
                            <?php else: ?>
                                <span class="status status--<?= e(status_badge_class($plan['status'])) ?>"><?= e($plan['status']) ?></span>
                            <?php endif; ?>
                            <span class="status status--<?= e(status_badge_class($plan['preview_status'])) ?>"><?= e($plan['preview_status']) ?></span>
                        </td>
                        <td>
                            <div class="action-list">
                                <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $plan['id'])) ?>">Ansehen</a>
                                <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $plan['id'] . '/preview')) ?>">Prüfen</a>
                                <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $plan['id'] . '/download')) ?>">Download</a>

                                <form method="post" action="<?= e(url('admin/meal-plans/' . $plan['id'] . '/activate')) ?>" class="inline-form">
                                    <?= csrf_field($csrfToken) ?>
                                    <button type="submit" class="button" <?= (int) $plan['is_active'] === 1 ? 'disabled' : '' ?>>Aktiv setzen</button>
                                </form>

                                <form method="post" action="<?= e(url('admin/meal-plans/' . $plan['id'] . '/archive')) ?>" class="inline-form" data-confirm="Speiseplan wirklich archivieren?">
                                    <?= csrf_field($csrfToken) ?>
                                    <button type="submit" class="button button--ghost">Archivieren</button>
                                </form>

                                <form method="post" action="<?= e(url('admin/meal-plans/' . $plan['id'] . '/delete')) ?>" class="inline-form" data-confirm="Speiseplan wirklich löschen?">
                                    <?= csrf_field($csrfToken) ?>
                                    <button type="submit" class="button button--danger" <?= (int) $plan['is_active'] === 1 ? 'disabled' : '' ?>>Löschen</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if ($plans === []): ?>
                    <tr>
                        <td colspan="5" class="muted">Noch keine Speisepläne vorhanden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
