<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Benutzerverwaltung</h1>
        <p class="lead">Klein gehaltenes Rollenmodell mit `admin` und `editor`.</p>
    </div>
    <div class="button-row">
        <a class="button" href="<?= e(url('admin/users/create')) ?>">Benutzer anlegen</a>
    </div>
</section>

<section class="panel">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Benutzer</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th>Letzter Login</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= e($user['display_name']) ?></strong><br>
                            <span class="muted"><?= e($user['username']) ?></span>
                        </td>
                        <td><?= e($user['role']) ?></td>
                        <td><span class="status status--<?= (int) $user['is_active'] === 1 ? 'success' : 'muted' ?>"><?= (int) $user['is_active'] === 1 ? 'aktiv' : 'deaktiviert' ?></span></td>
                        <td><?= e(format_datetime($user['last_login_at'])) ?></td>
                        <td>
                            <div class="action-list">
                                <a class="button button--ghost" href="<?= e(url('admin/users/' . $user['id'] . '/edit')) ?>">Bearbeiten</a>
                                <form method="post" action="<?= e(url('admin/users/' . $user['id'] . '/toggle')) ?>" class="inline-form" data-confirm="Benutzerstatus wirklich umschalten?">
                                    <?= csrf_field($csrfToken) ?>
                                    <button type="submit" class="button button--ghost"><?= (int) $user['is_active'] === 1 ? 'Deaktivieren' : 'Aktivieren' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
