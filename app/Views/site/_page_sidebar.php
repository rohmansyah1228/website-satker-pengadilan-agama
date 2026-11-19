        <?php if (! empty($sidebar)): ?>
        <aside class="col-lg-3 order-2 order-lg-1" aria-label="Sub-menu <?= esc($sectionLabel, 'attr') ?>">
            <div class="bg-white rounded-3 shadow-sm p-3 position-sticky" style="top: 7.5rem;">
                <nav class="nav flex-column gap-1">
                    <?php foreach ($sidebar as $s): ?>
                        <?php $active = rtrim($s['href'], '/') === rtrim($activeHref, '/'); ?>
                        <a href="<?= esc($s['href'], 'attr') ?>" class="nav-link sidebar-nav-link fw-semibold py-2 px-3<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?><?= ($s['type'] ?? '') === 'url' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= esc($s['label']) ?></a>
                    <?php endforeach ?>
                </nav>
            </div>
        </aside>
        <?php endif ?>
