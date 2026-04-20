<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Updater</h1>
        <p class="lead">Separater Update-Lauf mit Live-Ausgabe, sobald wirklich ein neues Update bereitsteht.</p>
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
                <dt>Remote-Version</dt>
                <dd><?= e($updateStatus['remote_version'] !== null ? 'v' . $updateStatus['remote_version'] : '—') ?></dd>
            </div>
            <div>
                <dt>Commits hinter Remote</dt>
                <dd><?= e($updateStatus['commits_behind'] === null ? '—' : (string) $updateStatus['commits_behind']) ?></dd>
            </div>
            <div>
                <dt>Update verfügbar</dt>
                <dd><?= $updateStatus['update_available'] ? 'Ja' : 'Nein' ?></dd>
            </div>
            <div>
                <dt>Worktree sauber</dt>
                <dd><?= $updateStatus['worktree_clean'] === null ? 'unbekannt' : ($updateStatus['worktree_clean'] ? 'ja' : 'nein') ?></dd>
            </div>
            <div>
                <dt>Update läuft bereits</dt>
                <dd><?= $updateStatus['update_in_progress'] ? 'Ja' : 'Nein' ?></dd>
            </div>
            <div>
                <dt>Repository</dt>
                <dd><?= e($updateStatus['repo_path']) ?></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><?= e($updateStatus['message']) ?></dd>
            </div>
        </dl>

        <div class="button-row">
            <a class="button button--ghost" href="<?= e(url('admin/settings')) ?>">Zurück zu Einstellungen</a>
            <button
                type="button"
                class="button"
                data-updater-start
                <?= !$updateStatus['can_run'] ? 'disabled' : '' ?>
            >
                Update jetzt starten
            </button>
        </div>
    </article>

    <article class="panel">
        <div class="panel__header">
            <div>
                <h2>Live-Ausgabe</h2>
            </div>
        </div>

        <div
            class="updater-console"
            data-updater-console
            data-stream-url="<?= e(url('admin/update/stream')) ?>"
            data-csrf-field="<?= e(app_config('security.csrf_field', '_csrf')) ?>"
            data-csrf-token="<?= e($csrfToken) ?>"
        >
            <div class="callout" data-updater-status><?= e($updateStatus['message']) ?></div>
            <pre class="terminal-output" data-updater-output><?= e('Noch kein Update-Lauf gestartet.') ?></pre>
        </div>
    </article>
</section>

<section class="panel">
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
</section>
