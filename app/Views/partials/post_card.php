<?php $small = $small ?? false; ?>
<article class="card h-100 border<?= $small ? '' : ' shadow-sm' ?>">
    <?php if ($p['image']): ?>
        <img src="<?= media_url($p['image']) ?>" class="card-img-top" alt="<?= esc($p['image_alt'] ?: $p['title'], 'attr') ?>" loading="lazy" decoding="async" width="600" height="360" style="aspect-ratio: 5/3; height: auto; object-fit: cover;">
    <?php endif ?>
    <div class="card-body">
        <span class="badge <?= in_array($p['category'], ['opini', 'artikel', 'pengumuman'], true) ? 'bg-pa-secondary text-dark' : 'bg-pa-primary' ?> mb-2"><?= esc($cats[$p['category']] ?? $p['category']) ?></span>
        <h3 class="font-display fw-bold <?= $small ? 'h6' : 'h5' ?> text-pa-primary">
            <a href="<?= site_url('berita/' . esc($p['slug'], 'url')) ?>" class="text-reset text-decoration-none stretched-link"><?= esc($p['title']) ?></a>
        </h3>
        <p class="text-secondary small mb-2"><?= tanggal_id($p['published_at']) ?><?= $p['author'] ? ' | ' . esc($p['author']) : '' ?></p>
        <p class="text-secondary small line-clamp-3 mb-0"><?= esc($p['excerpt'] ?: plain_excerpt($p['content'], 180)) ?></p>
    </div>
</article>
