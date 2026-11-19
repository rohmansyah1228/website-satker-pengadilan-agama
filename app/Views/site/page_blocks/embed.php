<?php use App\Libraries\PageBlocks;
$host  = (string) (parse_url($b['url'], PHP_URL_HOST) ?: parse_url(base_url(), PHP_URL_HOST));
$title = $b['heading'] !== '' ? $b['heading'] : 'Konten tersemat dari ' . $host; ?>
<div class="page-block page-embed mb-4">
    <?php if ($b['heading'] !== ''): ?><h2 class="h5 fw-bold text-pa-primary mb-3"><?= esc($b['heading']) ?></h2><?php endif ?>
    <div class="page-embed__frame" style="--embed-h: <?= (int) $b['height'] ?>px">
        <iframe src="<?= esc(PageBlocks::embedUrl($b['url']), 'attr') ?>" title="<?= esc($title, 'attr') ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-presentation allow-downloads"></iframe>
    </div>
    <p class="small text-secondary mt-2 mb-0">
        Tidak tampil? <a href="<?= esc($b['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer" class="text-pa-primary fw-semibold">Buka di tab baru <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
        <span class="ms-1">· Sumber: <?= esc($host) ?></span>
    </p>
</div>
