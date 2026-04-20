<section class="hero">
    <div>
        <p class="eyebrow">Öffentliche Ansicht</p>
        <h1>Aktueller Speiseplan</h1>
    </div>

    <?php if ($currentUser !== null): ?>
        <a class="button" href="<?= e(url('admin/meal-plans')) ?>">Zum Adminbereich</a>
    <?php endif; ?>
</section>

<?php if ($activePlan === null): ?>
    <section class="empty-state">
        <h2>Noch kein aktiver Speiseplan</h2>
        <p>Im Adminbereich kann ein Entwurf hochgeladen, geprüft und anschließend aktiviert werden.</p>
    </section>
<?php else: ?>
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2><?= e($activePlan['title']) ?></h2>
                <p class="muted">
                    Aktiv seit <?= e(format_datetime($activePlan['updated_at'])) ?>
                    · Ursprung <?= e(source_type_label($activePlan['normal_source_type'])) ?> / <?= e(source_type_label($activePlan['vegetarian_source_type'])) ?>
                </p>
            </div>

            <?php if ($currentUser !== null): ?>
                <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $activePlan['id'])) ?>">Im Admin ansehen</a>
            <?php endif; ?>
        </div>

        <div class="pdf-viewer" data-pdf-url="<?= e(url('meal-plans/' . $activePlan['id'] . '/pdf')) ?>" data-pages="1,2"></div>
    </section>
<?php endif; ?>
