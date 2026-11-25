<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= site_url('/') ?>">Beranda</a></li><li class="breadcrumb-item"><a href="<?= site_url('berita') ?>">Berita &amp; Publikasi</a></li><li class="breadcrumb-item active" aria-current="page">Galeri Prestasi</li></ol></nav>
    <h1 class="font-display h2 fw-bold text-pa-primary mb-2">Galeri Prestasi</h1>
    <p class="text-secondary mb-4">Piagam dan penghargaan yang diterima <?= esc(setting('site_name', 'Pengadilan Agama')) ?>, dari yang terbaru. Klik piagam untuk melihat ukuran penuh.</p>
    <?php if ($items): ?>
        <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-3">
            <?php foreach ($items as $a): ?><div class="col"><?= view('partials/achievement_card', ['a' => $a]) ?></div><?php endforeach ?>
        </div>
        <div class="mt-4"><?= $pager ?></div>
    <?php else: ?>
        <div class="bg-white p-5 rounded-3 text-center text-secondary">Belum ada prestasi.</div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
