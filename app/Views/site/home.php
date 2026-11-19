<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php /* Susunan beranda = grid bebas 12 kolom, diatur di Panel Admin → Tampilan Beranda */ ?>
<?php if (! $hasH1): ?><h1 class="visually-hidden"><?= esc(setting('site_name')) ?></h1><?php endif ?>

<?php foreach ($segments as $sg): ?>
    <?php if ($sg['kind'] === 'band'): ?>
        <?= view('site/blocks/_section', ['b' => $sg['item']]) ?>
    <?php else: ?>
        <div class="container-lg py-5">
            <div class="home-grid">
                <?php foreach ($sg['items'] as $it): ?>
                    <div class="home-cell" style="grid-column: <?= (int) $it['grid_x'] ?> / span <?= (int) $it['grid_w'] ?>; grid-row: <?= (int) $it['row'] ?> / span <?= (int) $it['rspan'] ?>;">
                        <?= view('site/blocks/_child', ['c' => $it, 'level' => 2, 'anchor' => true, 'inSide' => false]) ?>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    <?php endif ?>
<?php endforeach ?>

<?php if (! $segments): ?>
    <div class="container-lg py-5 text-center text-secondary">Beranda belum disusun. Atur melalui Panel Admin → Tampilan Beranda.</div>
<?php endif ?>
<?= $this->endSection() ?>
