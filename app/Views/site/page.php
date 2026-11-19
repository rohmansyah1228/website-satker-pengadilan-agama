<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small mb-2">
            <li class="breadcrumb-item"><a href="<?= site_url('/') ?>" class="text-pa-primary">Beranda</a></li>
            <li class="breadcrumb-item"><?= esc($sectionLabel) ?></li>
            <li class="breadcrumb-item active" aria-current="page"><?= esc($page['title']) ?></li>
        </ol>
    </nav>
    <p class="font-display h2 fw-bold text-pa-primary mb-4" aria-hidden="true"><?= esc($sectionLabel) ?></p>
    <div class="row g-4">
        <?= view('site/_page_sidebar', ['sidebar' => $sidebar ?? [], 'sectionLabel' => $sectionLabel, 'activeHref' => current_url()]) ?>
        <div class="<?= ! empty($sidebar) ? 'col-lg-9 order-1 order-lg-2' : 'col-12' ?>">
            <article class="bg-white p-4 p-md-5 rounded-3 shadow-sm">
                <h1 class="font-display h3 fw-bold text-pa-primary page-title mb-4"><?= esc($page['title']) ?></h1>
                <?php if ($page['status'] !== 'published'): ?>
                    <div class="alert alert-warning small">Pratinjau: halaman ini berstatus <strong>draft</strong> dan belum tampil untuk publik.</div>
                <?php endif ?>

                <?php if ($page['image']): ?>
                    <figure class="mb-4">
                        <a href="<?= media_url($page['image']) ?>" target="_blank" rel="noopener"><img src="<?= media_url($page['image']) ?>" alt="<?= esc($page['title'], 'attr') ?>" class="img-fluid rounded border shadow-sm" loading="lazy" decoding="async"></a>
                    </figure>
                <?php endif ?>

                <?php foreach ($blocks as $b): ?>
                    <?= view('site/page_blocks/' . $b['type'], ['b' => $b]) ?>
                <?php endforeach ?>

                <?php
                // Modul tanpa data disembunyikan bila halaman sudah punya isi sendiri
                $hasOwnContent = (bool) $blocks;
                $showModule    = $page['module'] && ! ($hasOwnContent && \App\Libraries\ModuleData::isEmpty($page['module'], $moduleData));
                ?>
                <?php if ($showModule): ?>
                    <?= view('site/modules/' . $page['module'], $moduleData + ['page' => $page]) ?>
                <?php endif ?>

                <p class="text-secondary small mt-4 mb-0 border-top pt-3"><i class="bi bi-clock-history me-1" aria-hidden="true"></i>Diperbarui: <?= tanggal_id($page['updated_at']) ?></p>
            </article>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
