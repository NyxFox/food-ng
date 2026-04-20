<section class="hero hero--compact">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Einstellungen</h1>
        <p class="lead">Banner, Theme, Rechtstexte und Systemstatus an einer Stelle.</p>
    </div>
</section>

<section class="grid-2">
    <form class="panel form-grid" method="post" action="<?= e(url('admin/settings')) ?>" enctype="multipart/form-data">
        <?= csrf_field($csrfToken) ?>
        <div class="panel__header">
            <div>
                <h2>Darstellung und Inhalte</h2>
            </div>
        </div>

        <label>
            <span>Seitentitel</span>
            <input type="text" name="site_title" required maxlength="120" value="<?= e($settings['site_title'] ?? 'Speiseplan') ?>">
        </label>

        <label>
            <span>Unterzeile in Navigation</span>
            <input type="text" name="site_subtitle" maxlength="120" value="<?= e($settings['site_subtitle'] ?? 'CJD Dortmund') ?>" placeholder="z. B. CJD Dortmund">
        </label>

        <label>
            <span>Theme</span>
            <select name="theme_mode">
                <option value="light" <?= ($settings['theme_mode'] ?? 'light') === 'light' ? 'selected' : '' ?>>hell</option>
                <option value="dark" <?= ($settings['theme_mode'] ?? 'light') === 'dark' ? 'selected' : '' ?>>dunkel</option>
            </select>
        </label>

        <label>
            <span>Akzentfarbe</span>
            <input type="text" name="accent_color" required pattern="^#[0-9a-fA-F]{6}$" value="<?= e($settings['accent_color'] ?? '#0f766e') ?>">
        </label>

        <label class="checkbox-field">
            <input type="checkbox" name="banner_enabled" value="1" <?= bool_setting($settings, 'banner_enabled') ? 'checked' : '' ?>>
            <span>Hinweisbanner aktivieren</span>
        </label>

        <label>
            <span>Banner-Stil</span>
            <select name="banner_style">
                <option value="info" <?= ($settings['banner_style'] ?? 'info') === 'info' ? 'selected' : '' ?>>info</option>
                <option value="warn" <?= ($settings['banner_style'] ?? 'info') === 'warn' ? 'selected' : '' ?>>warnung</option>
                <option value="important" <?= ($settings['banner_style'] ?? 'info') === 'important' ? 'selected' : '' ?>>wichtig</option>
            </select>
        </label>

        <label>
            <span>Banner-Text</span>
            <textarea name="banner_text" rows="5"><?= e($settings['banner_text'] ?? '') ?></textarea>
        </label>

        <label>
            <span>GitHub-Link</span>
            <input type="url" name="github_url" value="<?= e($settings['github_url'] ?? '') ?>" placeholder="https://github.com/...">
        </label>

        <div class="branding-preview">
            <div class="branding-preview__image">
                <?php if ($brandingIconUrl !== null): ?>
                    <img src="<?= e($brandingIconUrl) ?>" alt="Aktuelles App-Icon">
                <?php else: ?>
                    <span>SP</span>
                <?php endif; ?>
            </div>
            <div>
                <strong>App-Icon / Kundenlogo</strong>
                <p class="muted">Wird in Navigation und Browser-Tab verwendet. Erlaubt sind PNG, JPG, WEBP oder ICO bis 2 MB.</p>
            </div>
        </div>

        <label>
            <span>Neues App-Icon hochladen</span>
            <input type="file" name="app_icon" accept=".png,.jpg,.jpeg,.webp,.ico,image/png,image/jpeg,image/webp,image/x-icon,image/vnd.microsoft.icon">
        </label>

        <label class="checkbox-field">
            <input type="checkbox" name="remove_app_icon" value="1">
            <span>Eigenes App-Icon entfernen und Standard-Monogramm verwenden</span>
        </label>

        <label>
            <span>Impressum</span>
            <textarea name="imprint_text" rows="12"><?= e($settings['imprint_text'] ?? '') ?></textarea>
        </label>

        <label>
            <span>Datenschutz</span>
            <textarea name="privacy_text" rows="12"><?= e($settings['privacy_text'] ?? '') ?></textarea>
        </label>

        <button type="submit" class="button">Einstellungen speichern</button>
    </form>

    <div class="stack">
        <article class="panel">
            <div class="panel__header">
                <div>
                    <h2>Dokumentenstatus</h2>
                </div>
            </div>
            <dl class="key-value">
                <div>
                    <dt>PDF-Merge</dt>
                    <dd><?= $documentStatus['merge_available'] ? 'Verfügbar' : 'Nicht verfügbar' ?></dd>
                </div>
                <div>
                    <dt>DOCX-Konvertierung</dt>
                    <dd><?= $documentStatus['docx_conversion_available'] ? 'Verfügbar' : 'Nicht verfügbar' ?></dd>
                </div>
                <div>
                    <dt>Merge-Binary</dt>
                    <dd><?= e($documentStatus['merge_binary'] ?? '—') ?></dd>
                </div>
                <div>
                    <dt>DOCX-Binary</dt>
                    <dd><?= e($documentStatus['docx_binary'] ?? '—') ?></dd>
                </div>
            </dl>
        </article>

        <article class="panel">
            <div class="panel__header">
                <div>
                    <h2>Update-Status</h2>
                </div>
            </div>
            <?php
            $updateButtonLabel = $updateStatus['can_run']
                ? 'Updater öffnen'
                : ($updateStatus['update_in_progress']
                    ? 'Update läuft bereits'
                    : ($updateStatus['remote_checked'] && !$updateStatus['update_available']
                        ? 'Kein Update verfügbar'
                        : 'Update derzeit nicht startbar'));
            ?>
            <dl class="key-value">
                <div>
                    <dt>Installierte Version</dt>
                    <dd>v<?= e($updateStatus['current_version']) ?></dd>
                </div>
                <div>
                    <dt>Aktiv</dt>
                    <dd><?= $updateStatus['enabled'] ? 'Ja' : 'Nein' ?></dd>
                </div>
                <div>
                    <dt>Repository</dt>
                    <dd><?= e($updateStatus['repo_path']) ?></dd>
                </div>
                <div>
                    <dt>Branch</dt>
                    <dd><?= e($updateStatus['branch']) ?></dd>
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
                    <dt>Status</dt>
                    <dd><?= e($updateStatus['message']) ?></dd>
                </div>
            </dl>

            <div class="button-row">
                <a class="button <?= !$updateStatus['can_run'] ? 'button--disabled' : '' ?>" href="<?= e(url('admin/update')) ?>" <?= !$updateStatus['can_run'] ? 'aria-disabled="true"' : '' ?>><?= e($updateButtonLabel) ?></a>
            </div>
        </article>
    </div>
</section>
