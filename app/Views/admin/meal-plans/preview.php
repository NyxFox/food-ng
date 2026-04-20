<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Prüfansicht</p>
        <h1><?= e($plan['title']) ?></h1>
        <p class="lead">Vor dem Aktivstellen kann das zusammengeführte PDF direkt geprüft werden.</p>
    </div>

    <div class="button-row">
        <a class="button button--ghost" href="<?= e(url('admin/meal-plans')) ?>">Zurück zur Liste</a>
        <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $plan['id'])) ?>">Detailansicht</a>
    </div>
</section>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Vorschau</h2>
            <p class="muted">Seite 1 normal, Seite 2 vegetarisch.</p>
        </div>
    </div>

    <div class="pdf-viewer" data-pdf-url="<?= e(url('meal-plans/' . $plan['id'] . '/pdf')) ?>" data-pages="1,2"></div>
</section>

<section class="panel panel--narrow">
    <div class="button-row">
        <form method="post" action="<?= e(url('admin/meal-plans/' . $plan['id'] . '/activate')) ?>" class="inline-form">
            <?= csrf_field($csrfToken) ?>
            <button type="submit" class="button">Freigeben und aktiv setzen</button>
        </form>

        <form method="post" action="<?= e(url('admin/meal-plans/' . $plan['id'] . '/archive')) ?>" class="inline-form" data-confirm="Entwurf wirklich verwerfen und archivieren?">
            <?= csrf_field($csrfToken) ?>
            <button type="submit" class="button button--ghost">Verwerfen</button>
        </form>
    </div>
</section>
