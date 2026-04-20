<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Übersicht</h1>
        <p class="lead">Kurzer Betriebszustand für Speisepläne, Benutzer und Systemfunktionen.</p>
    </div>

    <div class="button-row">
        <a class="button" href="<?= e(url('admin/meal-plans/upload')) ?>">Neuen Plan hochladen</a>
        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
            <a class="button button--ghost" href="<?= e(url('admin/settings')) ?>">Einstellungen</a>
        <?php endif; ?>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span>Gesamt</span>
        <strong><?= e((string) $summary['total']) ?></strong>
        <small>alle Speisepläne</small>
    </article>
    <article class="stat-card">
        <span>Entwürfe</span>
        <strong><?= e((string) $summary['draft']) ?></strong>
        <small>warten auf Prüfung</small>
    </article>
    <article class="stat-card">
        <span>Aktiv</span>
        <strong><?= e((string) $summary['active']) ?></strong>
        <small>öffentlich sichtbar</small>
    </article>
    <article class="stat-card">
        <span>Benutzer</span>
        <strong><?= e((string) $userCount) ?></strong>
        <small>aktive Verwaltung</small>
    </article>
</section>

<section class="grid-2">
    <article class="panel">
        <div class="panel__header">
            <div>
                <h2>Aktiver Speiseplan</h2>
                <p class="muted">Direkt verlinkt zur öffentlichen Ansicht.</p>
            </div>
        </div>

        <?php if ($summary['active_plan'] === null): ?>
            <p class="muted">Derzeit ist kein Speiseplan aktiv.</p>
        <?php else: ?>
            <p><strong><?= e($summary['active_plan']['title']) ?></strong></p>
            <p class="muted"><?= e(format_datetime($summary['active_plan']['updated_at'])) ?></p>
            <div class="button-row">
                <a class="button button--ghost" href="<?= e(url('admin/meal-plans/' . $summary['active_plan']['id'])) ?>">Ansehen</a>
                <a class="button button--ghost" href="<?= e(url('')) ?>">Öffentliche Seite</a>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="panel__header">
            <div>
                <h2>Systemstatus</h2>
                <p class="muted">Konvertierung, Merge und Updatefunktion.</p>
            </div>
        </div>

        <dl class="key-value">
            <div>
                <dt>App-Version</dt>
                <dd>v<?= e($appVersion) ?></dd>
            </div>
            <div>
                <dt>PDF-Merge</dt>
                <dd><?= $documentStatus['merge_available'] ? 'Verfügbar' : 'Nicht verfügbar' ?></dd>
            </div>
            <div>
                <dt>DOCX-Konvertierung</dt>
                <dd><?= $documentStatus['docx_conversion_available'] ? 'Verfügbar' : 'Nicht verfügbar' ?></dd>
            </div>
            <div>
                <dt>Update-Runner</dt>
                <dd><?= $updateStatus['enabled'] ? 'Aktiviert' : 'Deaktiviert' ?></dd>
            </div>
            <div>
                <dt>Git-Status</dt>
                <dd><?= e($updateStatus['message']) ?></dd>
            </div>
        </dl>
    </article>
</section>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Seitenaufrufe</h2>
            <p class="muted">Sessionbasierte Statistiken – keine IP-Speicherung.</p>
        </div>
    </div>

    <div class="stats-grid">
        <article class="stat-card">
            <span>Heute</span>
            <strong><?= e((string) $statsSummary['views_today']) ?></strong>
            <small>Aufrufe</small>
        </article>
        <article class="stat-card">
            <span>Heute</span>
            <strong><?= e((string) $statsSummary['unique_sessions_today']) ?></strong>
            <small>einmalige Sitzungen</small>
        </article>
        <article class="stat-card">
            <span>7 Tage</span>
            <strong><?= e((string) $statsSummary['views_7days']) ?></strong>
            <small>Aufrufe</small>
        </article>
        <article class="stat-card">
            <span>7 Tage</span>
            <strong><?= e((string) $statsSummary['unique_sessions_7days']) ?></strong>
            <small>einmalige Sitzungen</small>
        </article>
        <article class="stat-card">
            <span>Gesamt</span>
            <strong><?= e((string) $statsSummary['views_total']) ?></strong>
            <small>Aufrufe</small>
        </article>
        <article class="stat-card">
            <span>Gesamt</span>
            <strong><?= e((string) $statsSummary['unique_sessions_total']) ?></strong>
            <small>einmalige Sitzungen</small>
        </article>
    </div>

    <?php if (!empty($statsSummary['daily'])): ?>
        <div class="table-scroll" style="margin-top: 1rem;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Aufrufe</th>
                        <th>Einmalige Sitzungen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($statsSummary['daily']) as $day): ?>
                        <tr>
                            <td><?= e($day['day']) ?></td>
                            <td><?= e((string) $day['views']) ?></td>
                            <td><?= e((string) $day['unique_sessions']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Letzte Ereignisse</h2>
            <p class="muted">Kompakter Blick in das Aktionslog.</p>
        </div>
        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
            <a class="button button--ghost" href="<?= e(url('admin/logs')) ?>">Alle Logs</a>
        <?php endif; ?>
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Zeit</th>
                    <th>Aktion</th>
                    <th>Meldung</th>
                    <th>Benutzer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><?= e(format_datetime($log['created_at'])) ?></td>
                        <td><span class="status status--<?= e(status_badge_class($log['level'])) ?>"><?= e($log['action']) ?></span></td>
                        <td><?= e($log['message']) ?></td>
                        <td><?= e($log['username'] ?? 'System') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
