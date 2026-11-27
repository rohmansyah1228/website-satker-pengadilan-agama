<?php
/**
 * Blok Beranda "Galeri Prestasi": piagam terbaru lebih dulu.
 * Bentuk "slider" (satu piagam besar bergantian, seperti contoh) atau "grid" (kartu berjajar).
 * @var array $data ['items' => [...]]
 * @var array $b    blok + settings
 */
$items   = $data['items'] ?? [];
$style   = ($b['settings']['style'] ?? 'slider') === 'grid' ? 'grid' : 'slider';
$caption = (int) ($b['settings']['caption'] ?? 1) === 1;
if (! $items) {
    echo '<p class="text-secondary small mb-0">Belum ada prestasi untuk ditampilkan.</p>';

    return;
}
$cid = 'ach-' . (int) $b['id'];
?>
<?php if ($style === 'grid'): ?>
    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3">
        <?php foreach ($items as $a): ?><div class="col"><?= view('partials/achievement_card', ['a' => $caption ? $a : ['title' => $a['title'], 'image' => $a['image'], 'image_alt' => $a['image_alt'] ?? '']]) ?></div><?php endforeach ?>
    </div>
<?php else: ?>
    <div id="<?= $cid ?>" class="carousel slide ach-slider" <?php $sec = (int) ($b['settings']['interval'] ?? 6); $sec = in_array($sec, [0, 3, 4, 5, 6, 7, 8, 10, 12, 15, 20, 30], true) ? $sec : 6; ?><?= $sec > 0 ? 'data-bs-ride="carousel" data-bs-interval="' . ($sec * 1000) . '"' : 'data-bs-interval="false"' ?> aria-roledescription="carousel" aria-label="Galeri Prestasi">
        <div class="carousel-inner">
            <?php foreach ($items as $i => $a): $alt = trim((string) ($a['image_alt'] ?? '')) ?: (string) $a['title']; ?>
                <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>" aria-roledescription="slide" aria-label="<?= $i + 1 ?> dari <?= count($items) ?>">
                    <a href="<?= esc(media_url($a['image']), 'attr') ?>" target="_blank" rel="noopener" class="ach-slide-frame d-block" title="Lihat piagam ukuran penuh">
                        <img src="<?= esc(media_url($a['image']), 'attr') ?>" alt="<?= esc($alt, 'attr') ?>" <?= $i === 0 ? 'loading="eager"' : 'loading="lazy"' ?> decoding="async">
                    </a>
                    <?php if ($caption): ?>
                        <div class="ach-caption text-center px-3 pt-3">
                            <div class="fw-semibold"><?= esc($a['title']) ?></div>
                            <?php if (! empty($a['issuer']) || ! empty($a['awarded_at'])): ?>
                                <div class="small text-secondary"><?= esc((string) ($a['issuer'] ?? '')) ?><?= ! empty($a['issuer']) && ! empty($a['awarded_at']) ? ' · ' : '' ?><?= ! empty($a['awarded_at']) ? esc(tanggal_id((string) $a['awarded_at'])) : '' ?></div>
                            <?php endif ?>
                        </div>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
        <?php if (count($items) > 1): ?>
            <button class="carousel-control-prev ach-nav" type="button" data-bs-target="#<?= $cid ?>" data-bs-slide="prev"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">Prestasi sebelumnya</span></button>
            <button class="carousel-control-next ach-nav" type="button" data-bs-target="#<?= $cid ?>" data-bs-slide="next"><i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Prestasi berikutnya</span></button>
            <div class="carousel-indicators ach-dots">
                <?php foreach ($items as $i => $a): ?>
                    <button type="button" data-bs-target="#<?= $cid ?>" data-bs-slide-to="<?= $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Prestasi <?= $i + 1 ?>"></button>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
<?php endif ?>
<div class="text-end mt-3"><a href="<?= site_url('galeri-prestasi') ?>" class="small fw-semibold text-pa-primary text-decoration-none">Lihat semua prestasi <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
