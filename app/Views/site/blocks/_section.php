<?php
/** Pembungkus tampilan utama Beranda. @var array $b */
$bgClass = ['white' => 'bg-white', 'light' => '', 'primary' => 'bg-pa-primary text-white'][$b['bg']] ?? 'bg-white';
$onDark  = $b['bg'] === 'primary';
$id      = 'blok-' . $b['id'];

if ($b['type'] === 'slider') {
    echo view('site/blocks/slider', ['b' => $b, 'data' => $b['data']]);

    return;
}
$titleId = $b['title'] ? $id . '-judul' : null;
?>
<section id="<?= $id ?>" class="py-5 home-block home-<?= esc($b['type'], 'attr') ?> <?= $bgClass ?>"<?= $titleId ? ' aria-labelledby="' . $titleId . '"' : '' ?>>
    <div class="container-lg">
        <?php if ($b['title'] || $b['subtitle']): ?>
            <div class="text-center mb-5">
                <?php if ($b['title']): ?><h2 id="<?= $titleId ?>" class="font-display h2 fw-bold <?= $onDark ? '' : 'text-pa-primary' ?>"><?= esc($b['title']) ?></h2><?php endif ?>
                <?php if ($b['subtitle']): ?><p class="<?= $onDark ? 'text-white-75' : 'text-secondary' ?> mx-auto mb-0" style="max-width: 40rem;"><?= esc($b['subtitle']) ?></p><?php endif ?>
            </div>
        <?php endif ?>

        <?php if ($b['type'] === 'row'): ?>
            <div class="row g-4">
                <?php foreach ($b['children'] as $c): ?>
                    <?php $w = (int) $c['width'] ?: 12; ?>
                    <div class="col-12<?= $w <= 4 ? ' col-md-6' : '' ?> col-lg-<?= $w ?>">
                        <?= view('site/blocks/_child', ['c' => $c, 'level' => $b['title'] ? 3 : 2]) ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php else: ?>
            <?= view('site/blocks/' . $b['type'], ['b' => $b, 'data' => $b['data'], 'nested' => false, 'onDark' => $onDark]) ?>
        <?php endif ?>
    </div>
</section>
