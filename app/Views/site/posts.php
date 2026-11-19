<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5">
    <h1 class="font-display h2 fw-bold text-pa-primary mb-2"><?= esc($title) ?></h1>
    <p class="text-secondary mb-4">Publikasi informasi pelayanan masyarakat, inovasi satuan kerja, serta opini aparatur mengenai reformasi birokrasi peradilan.</p>
    <nav class="mb-4" aria-label="Kategori berita">
        <ul class="nav nav-pills gap-2 small">
            <li class="nav-item"><a class="nav-link<?= $category ? ' bg-white text-pa-primary' : ' bg-pa-primary text-white' ?>" href="<?= site_url('berita') ?>"<?= $category ? '' : ' aria-current="page"' ?>>Semua</a></li>
            <?php foreach ($categories as $k => $v): ?>
                <li class="nav-item"><a class="nav-link<?= $category === $k ? ' bg-pa-primary text-white' : ' bg-white text-pa-primary' ?>" href="<?= site_url('berita/kategori/' . $k) ?>"<?= $category === $k ? ' aria-current="page"' : '' ?>><?= esc($v) ?></a></li>
            <?php endforeach ?>
        </ul>
    </nav>
    <?php if ($posts): ?>
        <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-3">
            <?php foreach ($posts as $p): ?><div class="col"><?= view('partials/post_card', ['p' => $p, 'cats' => $categories]) ?></div><?php endforeach ?>
        </div>
        <div class="mt-4"><?= $pager ?></div>
    <?php else: ?>
        <div class="bg-white p-5 rounded-3 text-center text-secondary">Belum ada publikasi pada kategori ini.</div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
