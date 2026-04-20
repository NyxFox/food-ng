<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($settings['site_title'] ?? app_config('app.name', 'Food-NG')) ?></title>
    <meta name="color-scheme" content="light dark">
    <?php if ($brandingIconUrl !== null): ?>
        <link rel="icon" href="<?= e($brandingIconUrl) ?>" type="<?= e($brandingIconMime ?? 'image/png') ?>">
        <link rel="apple-touch-icon" href="<?= e($brandingIconUrl) ?>">
    <?php endif; ?>
    <style>
        :root {
            --accent: <?= e($settings['accent_color'] ?? '#0f766e') ?>;
        }
    </style>
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
</head>
<body class="theme-<?= e($settings['theme_mode'] ?? 'light') ?>">
    <?php include __DIR__ . '/../partials/nav.php'; ?>

    <?php if (bool_setting($settings, 'banner_enabled') && trim((string) ($settings['banner_text'] ?? '')) !== ''): ?>
        <section class="page-shell">
            <div class="notice notice--<?= e($settings['banner_style'] ?? 'info') ?>">
                <?= nl2br_safe($settings['banner_text'] ?? '') ?>
            </div>
        </section>
    <?php endif; ?>

    <main class="page-shell page-content">
        <?php if ($flashMessages !== []): ?>
            <div class="flash-stack">
                <?php foreach ($flashMessages as $flash): ?>
                    <div class="flash flash--<?= e($flash['type']) ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
    <script type="module" src="<?= e(asset_url('js/pdf-viewer.js')) ?>"></script>
</body>
</html>
