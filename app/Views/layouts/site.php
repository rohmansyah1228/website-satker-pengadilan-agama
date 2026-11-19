<?php
/** @var array $menu */
$siteName = setting('site_name', 'Pengadilan Agama');
$logo     = setting('logo') ? media_url(setting('logo')) : base_url('assets/img/logo-default.svg');
$pageTitle = isset($title) && $title !== $siteName ? $title . ' — ' . $siteName : $siteName . ' — Portal Resmi';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? setting('meta_description')) ?>">
    <meta name="theme-color" content="#056e4f">
    <meta property="og:title" content="<?= esc($title ?? $siteName) ?>">
    <meta property="og:site_name" content="<?= esc($siteName) ?>">
    <?php if (! empty($ogImage)): ?><meta property="og:image" content="<?= esc($ogImage) ?>"><?php endif ?>
    <link rel="canonical" href="<?= esc(current_url()) ?>">
    <link rel="icon" href="<?= esc($logo) ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/fonts/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/site.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="bg-light text-dark">
    <a class="skip-link btn btn-pa-secondary" href="#main-content">Lewati ke konten utama</a>

    <?= $this->include('partials/header') ?>

    <?php if (! empty($alert)): ?>
        <div class="alert alert-warning alert-serta-merta border-0 border-bottom border-3 border-pa-secondary py-2" role="status">
            <div class="container-lg small d-flex gap-2 align-items-center">
                <i class="bi bi-exclamation-triangle-fill text-pa-secondary" aria-hidden="true"></i>
                <strong>Informasi Serta Merta:</strong>
                <a class="text-dark fw-semibold" href="<?= site_url('berita/' . esc($alert['slug'], 'url')) ?>"><?= esc($alert['title']) ?></a>
            </div>
        </div>
    <?php endif ?>

    <main id="main-content" tabindex="-1">
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('partials/footer') ?>
    <?= $this->include('partials/a11y') ?>

    <!-- Dialog Pencarian Internal (Lampiran II huruf B – Navigasi & Pencarian) -->
    <dialog id="searchDialog" class="search-dialog" aria-labelledby="searchDialogTitle">
        <div class="bg-pa-primary text-white px-4 py-3 d-flex justify-content-between align-items-center">
            <h2 id="searchDialogTitle" class="h5 font-display fw-bold mb-0"><i class="bi bi-search me-2" aria-hidden="true"></i>Pencarian Informasi Website</h2>
            <button type="button" class="btn-close btn-close-white" data-close-dialog aria-label="Tutup pencarian"></button>
        </div>
        <form action="<?= site_url('cari') ?>" method="get" role="search" class="p-4">
            <label for="dlgSearch" class="form-label fw-semibold">Kata kunci</label>
            <div class="d-flex gap-2">
                <input type="search" id="dlgSearch" name="q" class="form-control" minlength="3" required autofocus enterkeyhint="search"
                       placeholder="misal: jadwal sidang, panjar, DIPA, LHKPN">
                <button type="submit" class="btn btn-pa-secondary">Cari</button>
            </div>
        </form>
    </dialog>

    <script src="<?= asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>" defer></script>
    <script src="<?= asset('assets/js/site.js') ?>" defer></script>
</body>
</html>
