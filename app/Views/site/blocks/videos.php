<?php
$videos = $data['videos'] ?? [];
$cols   = (int) ($b['settings']['columns'] ?? 4);
?>
<?php if (! $videos): ?><p class="<?= $onDark ? 'text-white-75' : 'text-secondary' ?> small mb-0">Belum ada video.</p><?php return; endif ?>
<div class="row g-3 row-cols-1 row-cols-sm-2<?= $nested ? '' : ' row-cols-lg-' . $cols ?><?= $onDark ? ' video-on-dark' : '' ?>">
    <?php foreach ($videos as $v): ?><div class="col"><?= view('partials/video_card', ['v' => $v, 'small' => true]) ?></div><?php endforeach ?>
</div>
<?php if (! $nested): ?>
    <div class="text-center mt-4"><a href="<?= site_url('galeri-video') ?>" class="btn <?= $onDark ? 'btn-outline-light' : 'btn-pa-primary' ?>">Lihat semua video</a></div>
<?php endif ?>
