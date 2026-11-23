<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php if (sk_unlocked()): ?>
<div class="alert alert-warning d-flex align-items-start gap-2 mb-3"><i class="bi bi-unlock-fill mt-1" aria-hidden="true"></i><div><strong>Kunci menu wajib SK sedang terbuka.</strong> Menu wajib SK kini dapat dipindah/dihapus untuk menyesuaikan SK baru. Setelah selesai, kembalikan ke <em>Terkunci</em> di <a href="<?= admin_url('pengaturan?tab=keamanan') ?>">Pengaturan → Keamanan</a>.</div></div>
<?php endif ?>
<?php
$moved = (int) session('moved');
$max   = \App\Libraries\MenuTree::MAX_DEPTH;
$renderTree = static function (array $items, int $depth = 1, ?int $top = null) use (&$renderTree, $moved, $max): string {
    $count = count($items);
    ob_start(); ?>
    <ul class="menu-tree"<?= $depth === 1 ? ' aria-label="Struktur menu" id="menuTree"' : '' ?>>
        <?php foreach ($items as $i => $m): ?>
            <li data-id="<?= $m['id'] ?>" data-top="<?= $top ?? $m['id'] ?>"<?= ($m['is_required'] && ! sk_unlocked()) ? ' data-required' : '' ?>>
                <div class="menu-item<?= $moved === (int) $m['id'] ? ' moved' : '' ?>" id="menu-<?= $m['id'] ?>">
                    <div class="menu-main">
                        <span class="menu-handle" title="Seret untuk memindahkan" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
                        <div class="min-w-0">
                        <strong><?= esc($m['label']) ?></strong>
                        <?php if ($m['is_required']): ?><span class="badge text-bg-light border ms-1"><i class="bi bi-lock-fill" aria-hidden="true"></i> Wajib SK</span><?php endif ?>
                        <?php if (! $m['is_active']): ?><span class="badge bg-secondary ms-1">Disembunyikan</span><?php endif ?>
                        <div class="meta">
                            <?= ['page' => 'Halaman', 'route' => 'Alamat internal', 'url' => 'Tautan luar', 'section' => 'Menu utama berisi sub-menu', 'none' => 'Judul grup (tanpa tautan)'][$m['type']] ?? $m['type'] ?>
                            <?php if ($m['type'] === 'url'): ?>· <?= esc($m['url']) ?><?php elseif ($m['type'] !== 'none'): ?>· <?= esc(preg_replace('#^https?://[^/]+#', '', $m['href'])) ?: '/' ?><?php endif ?>
                        </div>
                        </div>
                    </div>
                    <div class="actions">
                        <form action="<?= admin_url('menu/' . $m['id'] . '/pindah') ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="dir" value="up">
                            <button class="btn btn-sm btn-outline-secondary" data-dir="up"<?= $i === 0 ? ' disabled' : '' ?>><i class="bi bi-arrow-up" aria-hidden="true"></i><span class="visually-hidden">Pindah <?= esc($m['label']) ?> ke atas</span></button></form>
                        <form action="<?= admin_url('menu/' . $m['id'] . '/pindah') ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="dir" value="down">
                            <button class="btn btn-sm btn-outline-secondary" data-dir="down"<?= $i === $count - 1 ? ' disabled' : '' ?>><i class="bi bi-arrow-down" aria-hidden="true"></i><span class="visually-hidden">Pindah <?= esc($m['label']) ?> ke bawah</span></button></form>
                        <a href="<?= admin_url('menu/baru?parent=' . $m['id']) ?>" class="btn btn-sm btn-outline-success" data-submenu<?= $depth >= $max ? ' hidden' : '' ?>><i class="bi bi-plus" aria-hidden="true"></i> Sub-menu<span class="visually-hidden"> untuk <?= esc($m['label']) ?></span></a>
                        <a href="<?= admin_url('menu/' . $m['id']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Ubah <?= esc($m['label']) ?></span></a>
                        <?php if (! $m['is_required'] || sk_unlocked()): ?>
                            <form action="<?= admin_url('menu/' . $m['id'] . '/hapus') ?>" method="post" class="d-inline" data-confirm="Hapus menu “<?= esc($m['label'], 'attr') ?>”? Halaman yang ditautkan tidak ikut terhapus."><?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Hapus <?= esc($m['label']) ?></span></button></form>
                        <?php endif ?>
                    </div>
                </div>
                <?php if ($m['children']): ?><?= $renderTree($m['children'], $depth + 1, $top ?? (int) $m['id']) ?><?php endif ?>
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
<div class="alert alert-success small d-flex gap-2 align-items-start"><i class="bi bi-arrows-move fs-5" aria-hidden="true"></i>
    <div><strong>Seret menu dengan kursor</strong> (pegang ikon <i class="bi bi-grip-vertical" aria-hidden="true"></i> atau kotak menunya) untuk mengubah urutan.
        Lepaskan di <strong>tengah</strong> menu lain untuk menjadikannya <strong>sub-menu</strong>; lepaskan di bagian <strong>atas/bawah</strong> untuk menaruhnya sebelum/sesudah. Maksimal <?= $max ?> tingkat. Susunan tersimpan otomatis.</div></div>
<div class="card p-3" data-menu-tree data-save-url="<?= admin_url('menu/susun') ?>" data-max-depth="<?= $max ?>">
    <?= csrf_field() ?>
    <div class="menu-status small mb-2" role="status" aria-live="polite"></div>
    <?= $renderTree($tree) ?>
</div>
<script src="<?= asset('assets/js/menu-builder.js') ?>" defer></script>
<?= $this->endSection() ?>
