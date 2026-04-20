<section class="panel panel--narrow">
    <div class="panel__header">
        <div>
            <p class="eyebrow">Benutzer</p>
            <h1><?= $isCreate ? 'Benutzer anlegen' : 'Benutzer bearbeiten' ?></h1>
        </div>
    </div>

    <form class="form-grid" method="post" action="<?= e($formAction) ?>">
        <?= csrf_field($csrfToken) ?>

        <label>
            <span>Anzeigename</span>
            <input type="text" name="display_name" required maxlength="100" value="<?= e(old($oldInput, 'display_name', $user['display_name'] ?? '')) ?>">
        </label>

        <label>
            <span>Benutzername</span>
            <input type="text" name="username" required maxlength="50" value="<?= e(old($oldInput, 'username', $user['username'] ?? '')) ?>">
        </label>

        <label>
            <span>Rolle</span>
            <?php $selectedRole = old($oldInput, 'role', $user['role'] ?? 'editor'); ?>
            <select name="role" required>
                <option value="editor" <?= $selectedRole === 'editor' ? 'selected' : '' ?>>editor</option>
                <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>admin</option>
            </select>
        </label>

        <?php if ($isCreate): ?>
            <label>
                <span>Initiales Passwort</span>
                <input type="password" name="password" required minlength="8">
            </label>
        <?php else: ?>
            <label class="checkbox-field">
                <input type="checkbox" name="is_active" value="1" <?= old($oldInput, 'is_active', (int) ($user['is_active'] ?? 1) === 1 ? '1' : '0') === '1' ? 'checked' : '' ?>>
                <span>Benutzer aktiv</span>
            </label>
        <?php endif; ?>

        <button type="submit" class="button"><?= $isCreate ? 'Benutzer anlegen' : 'Änderungen speichern' ?></button>
    </form>

    <?php if (!$isCreate && $user !== null): ?>
        <hr class="separator">
        <form class="form-grid" method="post" action="<?= e(url('admin/users/' . $user['id'] . '/password')) ?>">
            <?= csrf_field($csrfToken) ?>
            <label>
                <span>Neues Passwort</span>
                <input type="password" name="password" required minlength="8">
            </label>
            <button type="submit" class="button button--ghost">Passwort neu setzen</button>
        </form>
    <?php endif; ?>
</section>
