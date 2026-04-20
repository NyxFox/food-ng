<section class="panel panel--narrow">
    <div class="panel__header">
        <div>
            <p class="eyebrow">Fehler</p>
            <h1><?= e((string) $statusCode) ?></h1>
        </div>
    </div>

    <p><?= e($message) ?></p>
    <div class="button-row">
        <a class="button" href="<?= e(url('')) ?>">Zur Startseite</a>
        <?php if ($currentUser !== null): ?>
            <a class="button button--ghost" href="<?= e(url('admin')) ?>">Zum Admin</a>
        <?php endif; ?>
    </div>
</section>
