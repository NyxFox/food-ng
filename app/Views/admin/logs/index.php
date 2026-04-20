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
            </tbody>
        </table>
    </div>
</section>
