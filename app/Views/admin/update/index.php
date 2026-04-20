<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Updates</h1>
        <p class="lead">Pragmatische Git-Aktualisierung, nur wenn das System es sauber zulässt.</p>
    </div>
</section>

<section class="grid-2">
    <article class="panel">
        <div class="panel__header">
            <div>
                <h2>Status</h2>
            </div>
        </div>

        <dl class="key-value">
            <div>
                <dt>Anwendungsversion</dt>
                <dd>v<?= e($updateStatus['current_version']) ?></dd>
            </div>
            <div>
                <dt>Aktiviert</dt>
                <dd><?= $updateStatus['enabled'] ? 'Ja' : 'Nein' ?></dd>
            </div>
            <div>
                <dt>Git-Binary</dt>
                <dd><?= e($updateStatus['git_binary'] ?? '—') ?></dd>
            </div>
            <div>
                <dt>Repository</dt>
                <dd><?= e($updateStatus['repo_path']) ?></dd>
            </div>
            <div>
                <dt>Konfigurierter Branch</dt>
                <dd><?= e($updateStatus['branch']) ?></dd>
            </div>
            <div>
                <dt>Aktueller Branch</dt>
                <dd><?= e($updateStatus['current_branch'] ?? '—') ?></dd>
            </div>
            <div>
                <dt>Worktree sauber</dt>
                <dd><?= $updateStatus['worktree_clean'] === null ? 'unbekannt' : ($updateStatus['worktree_clean'] ? 'ja' : 'nein') ?></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><?= e($updateStatus['message']) ?></dd>
            </div>
        </dl>

        <form method="post" action="<?= e(url('admin/update')) ?>" class="inline-form">
            <?= csrf_field($csrfToken) ?>
            <button type="submit" class="button" <?= !$updateStatus['can_run'] ? 'disabled' : '' ?>>Update jetzt ausführen</button>
        </form>
    </article>

    <article class="panel">
        <div class="panel__header">
            <div>
                <h2>Letzte Update-Läufe</h2>
            </div>
        </div>

        <?php if ($updateLogs === []): ?>
            <p class="muted">Bisher wurden noch keine erfolgreichen Update-Läufe protokolliert.</p>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($updateLogs as $log): ?>
                    <li>
                        <strong><?= e(format_datetime($log['created_at'])) ?></strong>
                        <span><?= e($log['message']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>
