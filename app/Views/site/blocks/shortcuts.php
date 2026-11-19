<?php
$items = [];
foreach ($data['shortcuts'] ?? [] as $s) {
    $u = link_url($s['url']);
    if ($u !== '#' && $u !== '') { $items[] = $s + ['href' => $u]; } // token pengaturan kosong → tidak ditampilkan (cegah tautan mati)
}
$cols = (int) ($b['settings']['columns'] ?? 4);
$cls  = $nested ? 'row-cols-1 row-cols-sm-2' : 'row-cols-1 row-cols-sm-2 row-cols-lg-' . $cols;
?>
<?php if (! $items): ?><p class="text-secondary small mb-0">Belum ada pintasan.</p><?php return; endif ?>
<div class="row g-4 <?= $cls ?> justify-content-center">
    <?php foreach ($items as $s): $ext = is_external($s['href']); ?>
        <div class="col">
            <a href="<?= esc($s['href'], 'attr') ?>"<?= $ext ? ' target="_blank" rel="noopener noreferrer"' : '' ?> class="d-block bg-light p-4 rounded-3 text-center shadow-sm text-decoration-none h-100 card-shortcut">
                <i class="bi <?= esc($s['icon'] ?: 'bi-link-45deg', 'attr') ?> display-4" aria-hidden="true"></i>
                <span class="d-block font-display fw-bold fs-5 mt-3 text-pa-primary"><?= esc($s['title']) ?><?= $ext ? '<span class="visually-hidden"> (membuka tab baru)</span>' : '' ?></span>
                <span class="d-block text-secondary small mt-2"><?= esc($s['description']) ?></span>
            </a>
        </div>
    <?php endforeach ?>
</div>
