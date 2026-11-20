<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= site_url('/') ?>">Beranda</a></li><li class="breadcrumb-item"><a href="<?= site_url('berita') ?>">Berita &amp; Publikasi</a></li><li class="breadcrumb-item active" aria-current="page">Galeri Video</li></ol></nav>
    <h1 class="font-display h2 fw-bold text-pa-primary mb-2">Galeri Video</h1>
    <p class="text-secondary mb-4">Dokumentasi video kegiatan, maklumat pelayanan, dan inovasi <?= esc(setting('site_name', 'Pengadilan Agama')) ?>. Klik video untuk memutar.</p>
    <?php if ($videos): ?>
        <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-3">
            <?php foreach ($videos as $v): ?><div class="col"><?= view('partials/video_card', ['v' => $v]) ?></div><?php endforeach ?>
        </div>
        <div class="mt-4"><?= $pager ?></div>
    <?php else: ?>
        <div class="bg-white p-5 rounded-3 text-center text-secondary">Belum ada video.</div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
