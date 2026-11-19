<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5">
    <a href="<?= site_url('berita') ?>" class="btn btn-outline-success mb-4"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Kembali ke Daftar Berita</a>
    <div class="row g-4">
        <div class="col-lg-8">
            <article class="bg-white p-4 p-md-5 rounded-3 shadow-sm">
                <span class="badge bg-pa-primary mb-2"><?= esc($categories[$post['category']] ?? $post['category']) ?></span>
                <h1 class="font-display h2 fw-bold text-pa-primary"><?= esc($post['title']) ?></h1>
                <p class="text-secondary small border-bottom pb-3 mb-4">
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= tanggal_id($post['published_at'], true) ?>
                    <?= $post['author'] ? ' | <i class="bi bi-person me-1" aria-hidden="true"></i>' . esc($post['author']) : '' ?>
                </p>
                <?php if ($post['image']): ?>
                    <img src="<?= media_url($post['image']) ?>" alt="<?= esc($post['image_alt'] ?: $post['title'], 'attr') ?>" class="img-fluid rounded mb-4 w-100" style="max-height: 420px; object-fit: cover;" fetchpriority="high">
                <?php endif ?>
                <div class="prose"><?= $post['content'] ?></div>
            </article>
        </div>
        <aside class="col-lg-4" aria-labelledby="relatedTitle">
            <div class="bg-white p-4 rounded-3 shadow-sm">
                <h2 id="relatedTitle" class="font-display h6 fw-bold text-pa-primary border-bottom pb-2">Publikasi Terkait</h2>
                <?php foreach ($related as $r): ?>
                    <a href="<?= site_url('berita/' . esc($r['slug'], 'url')) ?>" class="d-block py-2 border-bottom text-decoration-none">
                        <span class="fw-semibold text-dark d-block"><?= esc($r['title']) ?></span>
                        <small class="text-secondary"><?= tanggal_id($r['published_at']) ?></small>
                    </a>
                <?php endforeach ?>
                <?php if (! $related): ?><p class="text-secondary small mb-0">Belum ada publikasi terkait.</p><?php endif ?>
            </div>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
