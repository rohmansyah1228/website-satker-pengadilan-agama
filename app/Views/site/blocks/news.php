<?php
$cats  = config('SiteStructure')->postCategories;
$posts = $data['posts'] ?? [];
$cols  = (int) ($b['settings']['columns'] ?? 3);
?>
<?php if (! $posts): ?><p class="text-secondary small mb-0">Belum ada berita.</p><?php return; endif ?>
<?php if ($cols === 1): ?>
    <div class="list-group list-group-flush">
        <?php foreach ($posts as $p): ?>
            <a href="<?= site_url('berita/' . esc($p['slug'], 'url')) ?>" class="list-group-item list-group-item-action px-0 py-3 d-flex gap-3">
                <?php if ($p['image']): ?><img src="<?= media_url($p['image']) ?>" alt="" width="96" height="64" class="rounded flex-shrink-0" style="object-fit: cover;" loading="lazy"><?php endif ?>
                <span><span class="d-block fw-semibold text-dark"><?= esc($p['title']) ?></span><small class="text-secondary"><?= tanggal_id($p['published_at']) ?></small></span>
            </a>
        <?php endforeach ?>
    </div>
<?php else: ?>
    <div class="row g-3 row-cols-1 row-cols-md-2<?= $cols > 2 ? ' row-cols-lg-' . $cols : '' ?>">
        <?php foreach ($posts as $p): ?><div class="col"><?= view('partials/post_card', ['p' => $p, 'cats' => $cats, 'small' => $nested]) ?></div><?php endforeach ?>
    </div>
<?php endif ?>
<?php if (! $nested): ?>
    <div class="text-center mt-4"><a href="<?= site_url(! empty($b['settings']['category']) ? 'berita/kategori/' . $b['settings']['category'] : 'berita') ?>" class="btn btn-pa-primary">Lihat semua publikasi</a></div>
<?php endif ?>
