<?php
$siteName = setting('site_name', 'Pengadilan Agama');
$logo     = setting('logo') ? media_url(setting('logo')) : base_url('assets/img/logo-default.svg');
$activeId = $activeTop['id'] ?? null;
$curUrl   = rtrim(current_url(), '/');
// Isi dropdown bertingkat: tingkat 2 tampil penuh, tingkat 3–4 menjorok di bawah induknya.
$dropItems = static function (array $items, int $depth) use (&$dropItems, $curUrl): string {
    $out = '';
    foreach ($items as $c) {
        $ext    = $c['type'] === 'url';
        $tab    = $c['new_tab'] || $ext ? ' target="_blank" rel="noopener noreferrer"' : '';
        $cls    = 'dropdown-item py-2 menu-lv' . $depth . ($c['children'] ? ' has-sub' : '') . (rtrim($c['href'], '/') === $curUrl ? ' active' : '');
        $icon   = $ext ? ' <i class="bi bi-box-arrow-up-right small" aria-hidden="true"></i>' : '';
        if ($c['type'] === 'none') {
            $out .= '<li><span class="dropdown-header ' . trim(str_replace('dropdown-item py-2', '', $cls)) . '">' . esc($c['label']) . '</span></li>';
        } else {
            $cur  = str_contains($cls, ' active') ? ' aria-current="page"' : '';
            $out .= '<li><a class="' . $cls . '" href="' . esc($c['href'], 'attr') . '"' . $tab . $cur . '>' . esc($c['label']) . $icon . '</a></li>';
        }
        if ($c['children']) {
            $out .= $dropItems($c['children'], $depth + 1);
        }
    }

    return $out;
};
?>
<header class="sticky-top shadow-sm bg-white">
    <!-- Top Bar Informasi Kontak -->
    <div class="bg-pa-primary text-white py-1" style="font-size: 0.85rem;">
        <div class="container-fluid header-container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <?php if (setting('phone')): ?><span><i class="bi bi-telephone-fill text-pa-secondary me-1" aria-hidden="true"></i> <?= esc(setting('phone')) ?></span><?php endif ?>
                <?php if (setting('email')): ?><span class="d-none d-md-inline"><i class="bi bi-envelope-fill text-pa-secondary me-1" aria-hidden="true"></i> <?= esc(setting('email')) ?></span><?php endif ?>
                <span class="d-none d-lg-inline"><i class="bi bi-shield-lock-fill text-pa-secondary me-1" aria-hidden="true"></i> Portal Resmi (.go.id)</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="<?= site_url('layanan-publik/pengaduan') ?>" class="text-white text-decoration-none fw-semibold">
                    <i class="bi bi-megaphone-fill text-pa-secondary me-1" aria-hidden="true"></i> SIWAS MARI
                </a>
                <?php $soc = array_filter(['facebook' => setting('facebook'), 'instagram' => setting('instagram'), 'youtube' => setting('youtube')]); ?>
                <?php if ($soc): ?>
                    <span class="text-white-50" aria-hidden="true">|</span>
                    <div class="d-flex gap-2">
                        <?php foreach ($soc as $net => $url): ?>
                            <a href="<?= esc(safe_href($url), 'attr') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= ucfirst($net) ?> <?= esc($siteName) ?>" class="text-white"><i class="bi bi-<?= $net ?>" aria-hidden="true"></i></a>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Navigasi 6 Menu Utama (Bab III huruf F) -->
    <nav class="navbar navbar-expand-xl bg-white py-2" aria-label="Navigasi Utama Website">
        <div class="container-fluid header-container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="<?= site_url('/') ?>">
                <img src="<?= esc($logo, 'attr') ?>" alt="Logo Mahkamah Agung RI" class="brand-logo object-fit-contain" width="52" height="52">
                <div class="lh-sm">
                    <span class="d-block font-display fw-bold text-pa-primary brand-name text-uppercase"><?= esc($siteName) ?></span>
                    <span class="d-block text-secondary small fw-medium"><?= esc(setting('site_tagline', 'Mahkamah Agung Republik Indonesia')) ?></span>
                </div>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Buka Menu Navigasi">
                <i class="bi bi-list fs-1 text-pa-primary" aria-hidden="true"></i>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-xl-0 align-items-xl-center gap-1">
                    <?php foreach ($menu as $item): ?>
                        <?php $isActive = $activeId === $item['id']; $ext = $item['type'] === 'url'; ?>
                        <?php if ($item['children']): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle<?= $isActive ? ' active' : '' ?>" href="<?= esc(safe_href($item['href']), 'attr') ?>" id="menu<?= $item['id'] ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false"<?= $isActive ? ' aria-current="true"' : '' ?>>
                                    <?= esc($item['label']) ?>
                                </a>
                                <ul class="dropdown-menu shadow border-0" aria-labelledby="menu<?= $item['id'] ?>">
                                    <?= $dropItems($item['children'], 2) ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= esc(safe_href($item['href']), 'attr') ?>"<?= $isActive ? ' aria-current="page"' : '' ?><?= $item['new_tab'] || $ext ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= esc($item['label']) ?></a>
                            </li>
                        <?php endif ?>
                    <?php endforeach ?>
                    <li class="nav-item ms-xl-2">
                        <button class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 rounded-pill px-3" type="button" data-open-search data-fallback="<?= site_url('cari') ?>" aria-label="Buka Pencarian Internal">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <span class="d-xl-none d-xxl-inline">Cari</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
