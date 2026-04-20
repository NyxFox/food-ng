<section class="panel panel--narrow">
    <div class="panel__header">
        <div>
            <p class="eyebrow">Interner Bereich</p>
            <h1>Anmeldung</h1>
        </div>
    </div>

    <form class="form-grid" method="post" action="<?= e(url('login')) ?>">
        <?= csrf_field($csrfToken) ?>

        <label>
            <span>Benutzername</span>
            <input type="text" name="username" autocomplete="username" required value="<?= e(old($oldInput, 'username')) ?>">
        </label>

        <label>
            <span>Passwort</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>

        <button type="submit" class="button">Anmelden</button>
    </form>

</section>
