<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Logs</h1>
        <p class="lead">Wichtige Aktionen und Fehler kompakt in der Datenbank nachvollziehen.</p>
    </div>
</section>

<section class="panel">
    <form class="filter-bar" method="get" action="<?= e(url('admin/logs')) ?>">
        <label>
            <span>Level</span>
            <select name="level">
                <option value="">alle</option>
                <?php foreach (['info', 'warning', 'error'] as $level): ?>
                    <option value="<?= e($level) ?>" <?= ($filters['level'] ?? '') === $level ? 'selected' : '' ?>><?= e($level) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Aktion</span>
            <input type="text" name="action" value="<?= e($filters['action'] ?? '') ?>" placeholder="z. B. login">
        </label>
        <label>
            <span>Suche</span>
            <input type="text" name="query" value="<?= e($filters['query'] ?? '') ?>" placeholder="Freitext">
        </label>
        <button class="button button--ghost" type="submit">Filtern</button>
    </form>

    <div class="list-meta">
        <span class="muted"><?= e((string) $totalLogs) ?> Einträge insgesamt</span>
        <span class="muted">Seite <?= e((string) $currentPage) ?> von <?= e((string) $totalPages) ?></span>
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Zeit</th>
                    <th>Level</th>
                    <th>Aktion</th>
                    <th>Meldung</th>
                    <th>Benutzer</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs === []): ?>
                    <tr>
                        <td colspan="6" class="muted">Keine Logeinträge für diese Filter gefunden.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= e(format_datetime($log['created_at'])) ?></td>
                            <td><span class="status status--<?= e(status_badge_class($log['level'])) ?>"><?= e($log['level']) ?></span></td>
                            <td><?= e($log['action']) ?></td>
                            <td>
                                <?= e($log['message']) ?>
                                <?php if (!empty($log['context_json'])): ?>
                                    <details class="details-inline">
                                        <summary>Kontext</summary>
                                        <pre><?= e($log['context_json']) ?></pre>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <td><?= e($log['username'] ?? 'System') ?></td>
                            <td><?= e($log['ip_address'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Log-Seiten">
            <?php
            $previousQuery = $paginationQuery;
            if ($currentPage > 2) {
                $previousQuery['page'] = $currentPage - 1;
            } else {
                unset($previousQuery['page']);
            }

            $nextQuery = $paginationQuery;
            $nextQuery['page'] = $currentPage + 1;
            ?>
            <?php if ($currentPage > 1): ?>
                <a class="button button--ghost" href="<?= e(url_with_query('admin/logs', $previousQuery)) ?>">Zurück</a>
            <?php else: ?>
                <span class="button button--disabled">Zurück</span>
            <?php endif; ?>

            <div class="pagination__pages">
                <?php for ($page = max(1, $currentPage - 2); $page <= min($totalPages, $currentPage + 2); $page++): ?>
                    <?php
                    $pageQuery = $paginationQuery;
                    if ($page > 1) {
                        $pageQuery['page'] = $page;
                    }
                    ?>
                    <?php if ($page === $currentPage): ?>
                        <span class="pagination__page pagination__page--active"><?= e((string) $page) ?></span>
                    <?php else: ?>
                        <a class="pagination__page" href="<?= e(url_with_query('admin/logs', $pageQuery)) ?>"><?= e((string) $page) ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            <?php if ($currentPage < $totalPages): ?>
                <a class="button button--ghost" href="<?= e(url_with_query('admin/logs', $nextQuery)) ?>">Weiter</a>
            <?php else: ?>
                <span class="button button--disabled">Weiter</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
