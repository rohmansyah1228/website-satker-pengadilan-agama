<?php
$path = '/' . trim(service('request')->getPath(), '/');
$base = '/' . trim(config('App')->adminPath, '/');
$is   = static fn (string $p, bool $exact = false) => $exact ? $path === rtrim($base . '/' . $p, '/') : str_starts_with($path . '/', rtrim($base . '/' . $p, '/') . '/');
$nav  = [];
foreach ($resources as $k => $r) {
    if (can($r['roles'])) {
        $nav[$r['group']][] = ['data/' . $k, $r['icon'], $r['title']];
    }
}
$logo = setting('logo') ? media_url(setting('logo')) : base_url('assets/img/logo-default.svg');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Panel Admin') ?> — Panel Admin <?= esc(setting('site_name')) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fonts/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <?php if (! empty($richtext)): ?><link rel="stylesheet" href="<?= asset('assets/vendor/quill/quill.snow.css') ?>"><?php endif ?>
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body data-moved-dir="<?= esc(session('movedDir') ?? '', 'attr') ?>">
<a class="visually-hidden-focusable btn btn-pa-secondary position-absolute m-2" style="z-index:2000" href="#adminMain">Lewati ke konten</a>
<div class="admin-shell">
    <nav id="adminSidebar" class="admin-sidebar" aria-label="Navigasi panel admin">
        <div class="brand d-flex align-items-center gap-2">
            <img src="<?= esc($logo, 'attr') ?>" alt="" width="36" height="36" style="object-fit:contain">
            <div class="lh-sm"><strong class="d-block text-white small"><?= esc(setting('site_name')) ?></strong><span class="small" style="color:#9fd1bf">Panel Admin</span></div>
        </div>
        <a href="<?= admin_url() ?>" class="<?= $is('', true) ? 'active' : '' ?>"<?= $is('', true) ? ' aria-current="page"' : '' ?>><i class="bi bi-speedometer2" aria-hidden="true"></i>Dashboard SINOVA</a>
        <div class="grp">Konten</div>
        <a href="<?= admin_url('halaman') ?>" class="<?= $is('halaman') ? 'active' : '' ?>"><i class="bi bi-file-earmark-richtext" aria-hidden="true"></i>Halaman</a>
        <?php if (can(['humas'])): ?><a href="<?= admin_url('beranda') ?>" class="<?= $is('beranda') ? 'active' : '' ?>"<?= $is('beranda') ? ' aria-current="page"' : '' ?>><i class="bi bi-layout-text-window-reverse" aria-hidden="true"></i>Tampilan Beranda</a><?php endif ?>
        <?php if (can([])): ?><a href="<?= admin_url('menu') ?>" class="<?= $is('menu') ? 'active' : '' ?>"><i class="bi bi-list-nested" aria-hidden="true"></i>Menu Navigasi</a><?php endif ?>
        <?php foreach ($nav as $grp => $items): ?>
            <div class="grp"><?= esc($grp) ?></div>
            <?php foreach ($items as [$p, $ic, $lbl]): ?>
                <a href="<?= admin_url($p) ?>" class="<?= $is($p) ? 'active' : '' ?>"<?= $is($p) ? ' aria-current="page"' : '' ?>><i class="bi <?= $ic ?>" aria-hidden="true"></i><?= esc($lbl) ?></a>
            <?php endforeach ?>
        <?php endforeach ?>
        <div class="grp">Sistem</div>
        <?php if (can([])): ?><a href="<?= admin_url('pengaturan') ?>" class="<?= $is('pengaturan') ? 'active' : '' ?>"><i class="bi bi-gear" aria-hidden="true"></i>Pengaturan</a><?php endif ?>
        <?php if (can([], true)): ?>
            <a href="<?= admin_url('pengguna') ?>" class="<?= $is('pengguna') ? 'active' : '' ?>"><i class="bi bi-people" aria-hidden="true"></i>Pengguna</a>
            <a href="<?= admin_url('log') ?>" class="<?= $is('log') ? 'active' : '' ?>"><i class="bi bi-journal-text" aria-hidden="true"></i>Log Aktivitas</a>
        <?php endif ?>
        <a href="<?= admin_url('akun') ?>" class="<?= $is('akun') ? 'active' : '' ?>"><i class="bi bi-person-circle" aria-hidden="true"></i>Akun Saya</a>
        <div class="p-3"></div>
    </nav>
    <div>
        <header class="admin-topbar px-3 py-2 d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" data-toggle-sidebar aria-controls="adminSidebar" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Menu</span></button>
            <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Lihat Situs</a>
            <div class="ms-auto d-flex align-items-center gap-2 small">
                <span class="d-none d-sm-inline text-secondary"><i class="bi bi-person" aria-hidden="true"></i> <?= esc($user['name']) ?> · <?= esc(config('SiteStructure')->roles[$user['role']] ?? $user['role']) ?></span>
                <form action="<?= admin_url('logout') ?>" method="post" class="m-0"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Keluar</button></form>
            </div>
        </header>
        <main id="adminMain" class="admin-main" tabindex="-1">
            <?= $this->include('admin/_flash') ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<dialog id="confirmDialog" class="confirm" aria-labelledby="confirmTitle">
    <form method="dialog" class="p-4">
        <h2 id="confirmTitle" class="h5 fw-bold text-danger"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>Konfirmasi</h2>
        <p data-confirm-text class="mb-4"></p>
        <div class="d-flex justify-content-end gap-2">
            <button value="cancel" class="btn btn-outline-secondary" autofocus>Batal</button>
            <button value="ok" class="btn btn-danger">Ya, Hapus</button>
        </div>
    </form>
</dialog>
<p id="adminLive" class="visually-hidden" role="status"></p>

<script src="<?= asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>" defer></script>
<?php if (! empty($richtext)): ?><script src="<?= asset('assets/vendor/quill/quill.js') ?>" defer></script><?php endif ?>
<?php if (! empty($qrcode)): ?><script src="<?= asset('assets/vendor/qrcode/qrcode.js') ?>" defer></script><?php endif ?>
<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
<?php if (! empty($pageBuilder)): ?><script src="<?= asset('assets/js/page-builder.js') ?>" defer></script><?php endif ?>
<?php if (! empty($homeBuilder)): ?><script src="<?= asset('assets/js/home-builder.js') ?>" defer></script><?php endif ?>
</body>
</html>
