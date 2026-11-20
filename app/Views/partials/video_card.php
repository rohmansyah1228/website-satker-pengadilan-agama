<?php
/** @var array $v baris videos */
use App\Libraries\PageBlocks;

$thumb = PageBlocks::videoThumb($v);
$pv    = PageBlocks::previewUrl((string) $v['url']);
$small = $small ?? false;
?>
<figure class="video-card h-100 mb-0">
    <a href="<?= esc($v['url'], 'attr') ?>" class="video-thumb d-block position-relative rounded-3 overflow-hidden"<?= $pv ? ' data-preview="' . esc($pv . (str_contains($pv, 'youtube') ? '?autoplay=1&rel=0' : ''), 'attr') . '"' : '' ?> target="_blank" rel="noopener noreferrer" data-preview-title="<?= esc($v['title'], 'attr') ?>">
        <?php if ($thumb): ?>
            <img src="<?= esc($thumb, 'attr') ?>" alt="" loading="lazy" decoding="async" width="480" height="270" onerror="this.remove()">
        <?php else: ?>
            <span class="video-thumb-empty"><i class="bi bi-camera-video" aria-hidden="true"></i></span>
        <?php endif ?>
        <span class="video-play" aria-hidden="true"><i class="bi bi-play-fill"></i></span>
        <span class="visually-hidden">Putar video: <?= esc($v['title']) ?></span>
    </a>
    <figcaption class="mt-2">
        <span class="d-block fw-semibold video-title<?= $small ? ' small' : '' ?>"><?= esc($v['title']) ?></span>
        <?php if (! empty($v['published_at'])): ?><small class="text-secondary"><?= tanggal_id($v['published_at']) ?></small><?php endif ?>
        <?php if (! $small && ! empty($v['description'])): ?><p class="small text-secondary mb-0 mt-1 line-clamp-3"><?= esc($v['description']) ?></p><?php endif ?>
    </figcaption>
</figure>
