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
    <div class="list-meta">
        <span class="muted"><?= e((string) $totalUsers) ?> Benutzer insgesamt</span>
        <span class="muted">Seite <?= e((string) $currentPage) ?> von <?= e((string) $totalPages) ?></span>
    </div>

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
                <?php if ($users === []): ?>
                    <tr>
                        <td colspan="5" class="muted">Noch keine Benutzer vorhanden.</td>
                    </tr>
                <?php else: ?>
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
                                        <input type="hidden" name="page" value="<?= e((string) $currentPage) ?>">
                                        <button type="submit" class="button button--ghost"><?= (int) $user['is_active'] === 1 ? 'Deaktivieren' : 'Aktivieren' ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Benutzer-Seiten">
            <?php if ($currentPage > 1): ?>
                <a class="button button--ghost" href="<?= e(url_with_query('admin/users', $currentPage > 2 ? ['page' => $currentPage - 1] : [])) ?>">Zurück</a>
            <?php else: ?>
                <span class="button button--disabled">Zurück</span>
            <?php endif; ?>

            <div class="pagination__pages">
                <?php for ($page = max(1, $currentPage - 2); $page <= min($totalPages, $currentPage + 2); $page++): ?>
                    <?php if ($page === $currentPage): ?>
                        <span class="pagination__page pagination__page--active"><?= e((string) $page) ?></span>
                    <?php else: ?>
                        <a class="pagination__page" href="<?= e(url_with_query('admin/users', $page > 1 ? ['page' => $page] : [])) ?>"><?= e((string) $page) ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            <?php if ($currentPage < $totalPages): ?>
                <a class="button button--ghost" href="<?= e(url_with_query('admin/users', ['page' => $currentPage + 1])) ?>">Weiter</a>
            <?php else: ?>
                <span class="button button--disabled">Weiter</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
