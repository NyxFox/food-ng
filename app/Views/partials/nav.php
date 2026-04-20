<header class="site-header">
    <div class="page-shell site-header__inner">
        <?php $siteSubtitle = trim((string) ($settings['site_subtitle'] ?? 'CJD Dortmund')); ?>
        <a class="site-brand" href="<?= e(url('')) ?>">
            <?php if ($brandingIconUrl !== null): ?>
                <span class="site-brand__mark site-brand__mark--image">
                    <img class="site-brand__logo" src="<?= e($brandingIconUrl) ?>" alt="">
                </span>
            <?php else: ?>
                <span class="site-brand__mark">SP</span>
            <?php endif; ?>
            <span>
                <strong><?= e($settings['site_title'] ?? 'Speiseplan') ?></strong>
                <?php if ($siteSubtitle !== ''): ?>
                    <small><?= e($siteSubtitle) ?></small>
                <?php endif; ?>
            </span>
        </a>

        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="main-navigation">
            Menü
        </button>

        <nav class="site-nav" id="main-navigation" data-nav>
            <?php $homeActive = rtrim($currentPath, '/') === rtrim(url(''), '/') || $currentPath === url(''); ?>
            <a class="<?= $homeActive ? 'is-active' : '' ?>" href="<?= e(url('')) ?>">Startseite</a>

            <?php if ($currentUser !== null): ?>
                <span class="site-nav__divider"></span>
                <a class="<?= current_path_starts_with($currentPath, 'admin') && !current_path_starts_with($currentPath, 'admin/meal-plans') ? 'is-active' : '' ?>" href="<?= e(url('admin')) ?>">Admin</a>
                <a class="<?= current_path_starts_with($currentPath, 'admin/meal-plans') ? 'is-active' : '' ?>" href="<?= e(url('admin/meal-plans')) ?>">Speisepläne</a>

                <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                    <a class="<?= current_path_starts_with($currentPath, 'admin/users') ? 'is-active' : '' ?>" href="<?= e(url('admin/users')) ?>">Benutzer</a>
                    <a class="<?= current_path_starts_with($currentPath, 'admin/settings') ? 'is-active' : '' ?>" href="<?= e(url('admin/settings')) ?>">Einstellungen</a>
                    <a class="<?= current_path_starts_with($currentPath, 'admin/logs') ? 'is-active' : '' ?>" href="<?= e(url('admin/logs')) ?>">Logs</a>
                    <a class="<?= current_path_starts_with($currentPath, 'admin/update') ? 'is-active' : '' ?>" href="<?= e(url('admin/update')) ?>">Updates</a>
                <?php endif; ?>

                <span class="site-nav__user"><?= e($currentUser['display_name']) ?> (<?= e($currentUser['role']) ?>)</span>
                <form method="post" action="<?= e(url('logout')) ?>" class="inline-form">
                    <?= csrf_field($csrfToken) ?>
                    <button type="submit" class="button button--ghost">Logout</button>
                </form>
            <?php else: ?>
                <a class="<?= current_path_starts_with($currentPath, 'login') ? 'is-active' : '' ?>" href="<?= e(url('login')) ?>">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
