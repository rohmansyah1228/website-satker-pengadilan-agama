<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$moved = (int) session('moved');
$renderTree = static function (array $items, int $depth = 1) use (&$renderTree, $moved): string {
    $count = count($items);
    ob_start(); ?>
    <ul class="menu-tree"<?= $depth === 1 ? ' aria-label="Struktur menu"' : '' ?>>
        <?php foreach ($items as $i => $m): ?>
            <li>
                <div class="menu-item<?= $moved === (int) $m['id'] ? ' moved' : '' ?>" id="menu-<?= $m['id'] ?>">
                    <div>
                        <strong><?= esc($m['label']) ?></strong>
                        <?php if ($m['is_required']): ?><span class="badge text-bg-light border ms-1"><i class="bi bi-lock-fill" aria-hidden="true"></i> Wajib SK</span><?php endif ?>
                        <?php if (! $m['is_active']): ?><span class="badge bg-secondary ms-1">Disembunyikan</span><?php endif ?>
                        <div class="meta">
                            <?= ['page' => 'Halaman', 'route' => 'Alamat internal', 'url' => 'Tautan luar', 'section' => 'Menu utama berisi sub-menu'][$m['type']] ?? $m['type'] ?>
                            · <?= esc(preg_replace('#^https?://[^/]+#', '', $m['href'])) ?: '/' ?>
                        </div>
                    </div>
                    <div class="actions">
                        <form action="<?= admin_url('menu/' . $m['id'] . '/pindah') ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="dir" value="up">
                            <button class="btn btn-sm btn-outline-secondary" data-dir="up"<?= $i === 0 ? ' disabled' : '' ?>><i class="bi bi-arrow-up" aria-hidden="true"></i><span class="visually-hidden">Pindah <?= esc($m['label']) ?> ke atas</span></button></form>
                        <form action="<?= admin_url('menu/' . $m['id'] . '/pindah') ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="dir" value="down">
                            <button class="btn btn-sm btn-outline-secondary" data-dir="down"<?= $i === $count - 1 ? ' disabled' : '' ?>><i class="bi bi-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Pindah <?= esc($m['label']) ?> ke bawah</span></button></form>
                        <?php if ($depth < 3 && $m['type'] !== 'url' && $m['type'] !== 'route'): ?>
                            <a href="<?= admin_url('menu/baru?parent=' . $m['id']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-plus" aria-hidden="true"></i> Sub-menu<span class="visually-hidden"> untuk <?= esc($m['label']) ?></span></a>
                        <?php endif ?>
                        <a href="<?= admin_url('menu/' . $m['id']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Ubah <?= esc($m['label']) ?></span></a>
                        <?php if (! $m['is_required']): ?>
                            <form action="<?= admin_url('menu/' . $m['id'] . '/hapus') ?>" method="post" class="d-inline" data-confirm="Hapus menu “<?= esc($m['label'], 'attr') ?>”? Halaman yang ditautkan tidak ikut terhapus."><?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Hapus <?= esc($m['label']) ?></span></button></form>
                        <?php endif ?>
                    </div>
                </div>
                <?php if ($m['children']): ?><?= $renderTree($m['children'], $depth + 1) ?><?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
    <?php return ob_get_clean();
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h1 class="h3 font-display fw-bold text-pa-primary mb-0">Menu Navigasi</h1>
        <p class="text-secondary small mb-0">Atur label, urutan, dan sub-menu. Menu wajib SK (6 menu utama & halaman kriteria) tidak dapat dihapus, tetapi label dan urutannya dapat diubah.</p></div>
    <a href="<?= admin_url('menu/baru') ?>" class="btn btn-pa-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Menu Baru</a>
</div>
<div class="alert alert-light border small"><i class="bi bi-lightbulb text-pa-secondary" aria-hidden="true"></i> Jaga agar informasi utama (SIPP, jadwal sidang) dapat dicapai dalam 2 klik atau kurang (Bab III huruf C). Hindari menu yang tidak relevan dengan tugas dan fungsi pengadilan.</div>
<div class="card p-3"><?= $renderTree($tree) ?></div>
<?= $this->endSection() ?>
