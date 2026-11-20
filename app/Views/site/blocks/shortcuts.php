<?php
$items = [];
foreach ($data['shortcuts'] ?? [] as $s) {
    $u = link_url($s['url']);
    if ($u !== '#' && $u !== '') { $items[] = $s + ['href' => $u]; } // token pengaturan kosong → tidak ditampilkan (cegah tautan mati)
}
$style = $b['settings']['style'] ?? 'card';
$cols  = (int) ($b['settings']['columns'] ?? 4);
$base  = $style === 'card' ? 'row-cols-1 row-cols-sm-2' : 'row-cols-2 row-cols-sm-3';
$cls   = $nested ? $base : $base . ' row-cols-lg-' . $cols;
?>
<?php if (! $items): ?><p class="<?= $onDark ? 'text-white-75' : 'text-secondary' ?> small mb-0">Belum ada pintasan.</p><?php return; endif ?>
<div class="row <?= $style === 'card' ? 'g-4' : 'g-3' ?> <?= $cls ?> justify-content-center">
    <?php foreach ($items as $s):
        $ext  = is_external($s['href']);
        $img  = ! empty($s['image']) ? media_url($s['image']) : '';
        $attr = $ext ? ' target="_blank" rel="noopener noreferrer"' : '';
        $sr   = $ext ? '<span class="visually-hidden"> (membuka tab baru)</span>' : '';
        ?>
        <div class="col">
            <?php if ($style === 'tile'): ?>
                <a href="<?= esc($s['href'], 'attr') ?>"<?= $attr ?> class="d-block h-100 shortcut-tile" title="<?= esc($s['title'], 'attr') ?>">
                    <?php if ($img): ?>
                        <img src="<?= esc($img, 'attr') ?>" alt="<?= esc($s['title'], 'attr') ?>" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span class="shortcut-tile-fallback"><i class="bi <?= esc($s['icon'] ?: 'bi-link-45deg', 'attr') ?>" aria-hidden="true"></i><span><?= esc($s['title']) ?></span></span>
                    <?php endif ?><?= $sr ?>
                </a>
            <?php elseif ($style === 'button'): ?>
                <a href="<?= esc($s['href'], 'attr') ?>"<?= $attr ?> class="d-flex flex-column align-items-center justify-content-center h-100 text-center text-decoration-none shortcut-button">
                    <?php if ($img): ?><img src="<?= esc($img, 'attr') ?>" alt="" loading="lazy" decoding="async"><?php else: ?><i class="bi <?= esc($s['icon'] ?: 'bi-link-45deg', 'attr') ?>" aria-hidden="true"></i><?php endif ?>
                    <span class="shortcut-button-label"><?= esc($s['title']) ?></span><?= $sr ?>
                </a>
            <?php else: ?>
                <a href="<?= esc($s['href'], 'attr') ?>"<?= $attr ?> class="d-block bg-light p-4 rounded-3 text-center shadow-sm text-decoration-none h-100 card-shortcut">
                    <?php if ($img): ?>
                        <img src="<?= esc($img, 'attr') ?>" alt="" class="shortcut-img" loading="lazy" decoding="async">
                    <?php else: ?>
                        <i class="bi <?= esc($s['icon'] ?: 'bi-link-45deg', 'attr') ?> display-4" aria-hidden="true"></i>
                    <?php endif ?>
                    <span class="d-block font-display fw-bold fs-5 mt-3 text-pa-primary"><?= esc($s['title']) ?><?= $sr ?></span>
                    <?php if ($s['description'] !== '' && $s['description'] !== null): ?><span class="d-block text-secondary small mt-2"><?= esc($s['description']) ?></span><?php endif ?>
                </a>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>
