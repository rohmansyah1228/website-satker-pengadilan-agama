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
    <?= favicon_tags() ?>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fonts/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/site.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="bg-light text-dark"<?= setting('image_zoom', '1') === '1' ? ' data-img-zoom' : '' ?>>
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
    <?= $this->include('partials/popup') ?>
    <?= $this->include('partials/floating') ?>
    <!-- Penampil tautan di dalam website (tombol "Lihat" & tautan bertarget "tampil") -->
<dialog id="previewDialog" class="preview-dialog" aria-labelledby="previewTitle">
    <div class="preview-dialog__head">
        <h2 id="previewTitle" class="h6 fw-bold mb-0 text-truncate">Pratinjau</h2>
        <span id="previewHint" class="small text-secondary d-none d-lg-inline text-truncate">Tidak tampil? Sebagian situs menolak ditampilkan di situs lain — gunakan “Buka di tab baru”.</span>
        <div class="ms-auto d-flex gap-2 flex-shrink-0"><a id="previewOpen" href="#" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="d-none d-sm-inline"> Buka di tab baru</span></a>
        <button type="button" class="btn btn-sm btn-pa-primary" data-close-preview aria-label="Tutup pratinjau"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
    </div>
    <iframe id="previewFrame" title="Pratinjau dokumen" src="about:blank" allow="fullscreen; autoplay; encrypted-media; picture-in-picture" sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-forms allow-downloads allow-presentation"></iframe>
</dialog>


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
