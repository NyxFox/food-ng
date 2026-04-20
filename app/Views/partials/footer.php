<footer class="site-footer">
    <div class="page-shell site-footer__inner">
        <div>
            <strong><?= e($settings['site_title'] ?? 'Food-NG') ?></strong>
            <div class="muted">Version v<?= e($appVersion) ?></div>
        </div>

        <nav class="footer-links">
            <a href="<?= e(url('impressum')) ?>">Impressum</a>
            <a href="<?= e(url('datenschutz')) ?>">Datenschutz</a>
            <?php if (trim((string) ($settings['github_url'] ?? '')) !== ''): ?>
                <a href="<?= e($settings['github_url']) ?>" target="_blank" rel="noopener noreferrer">GitHub</a>
            <?php endif; ?>
        </nav>
    </div>
</footer>
