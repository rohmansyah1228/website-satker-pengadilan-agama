<?php
/**
 * Kartu satu prestasi (dipakai halaman Galeri Prestasi & blok Beranda bentuk kartu).
 * @var array $a  baris achievements
 */
$alt  = trim((string) ($a['image_alt'] ?? '')) ?: (string) $a['title'];
$full = media_url($a['image']);
?>
<figure class="ach-card h-100 bg-white rounded-3 shadow-sm overflow-hidden mb-0">
    <a href="<?= esc($full, 'attr') ?>" target="_blank" rel="noopener" class="ach-frame img-zoom d-block" title="Lihat piagam ukuran penuh">
        <img src="<?= esc($full, 'attr') ?>" alt="<?= esc($alt, 'attr') ?>" loading="lazy" decoding="async">
    </a>
    <figcaption class="p-3">
        <div class="fw-semibold text-dark lh-sm"><?= esc($a['title']) ?></div>
        <?php if (! empty($a['issuer']) || ! empty($a['awarded_at'])): ?>
            <div class="small text-secondary mt-1">
                <?= esc((string) ($a['issuer'] ?? '')) ?><?= ! empty($a['issuer']) && ! empty($a['awarded_at']) ? ' · ' : '' ?><?= ! empty($a['awarded_at']) ? esc(tanggal_id((string) $a['awarded_at'])) : '' ?>
            </div>
        <?php endif ?>
        <?php if (! empty($a['description'])): ?><p class="small text-secondary mb-0 mt-2"><?= nl2br(esc($a['description'])) ?></p><?php endif ?>
    </figcaption>
</figure>
