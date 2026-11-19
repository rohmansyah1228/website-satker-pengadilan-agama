<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?= admin_url('beranda') ?>">Tampilan Beranda</a></li><li class="breadcrumb-item active" aria-current="page">Pilih jenis</li></ol></nav>
<h1 class="h3 font-display fw-bold text-pa-primary mb-1">Tambah Tampilan</h1>
<p class="text-secondary small mb-3">Tampilan baru ditaruh di baris paling bawah; posisinya bisa digeser bebas di editor.</p>
<div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3">
    <?php foreach ($types as $k => $t): ?>
        <div class="col">
            <a href="<?= admin_url('beranda/baru?type=' . $k) ?>" class="card h-100 p-3 text-decoration-none text-dark d-flex flex-row gap-3 align-items-start">
                <i class="bi <?= $t[1] ?> fs-2 text-pa-primary" aria-hidden="true"></i>
                <span><strong class="d-block"><?= esc($t[0]) ?></strong><span class="small text-secondary"><?= esc($t[2]) ?></span></span>
            </a>
        </div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
