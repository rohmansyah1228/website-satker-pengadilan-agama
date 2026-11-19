<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $rowsUsed = 1; foreach ($items as $it) { $rowsUsed = max($rowsUsed, (int) $it['grid_y'] + (int) $it['grid_h'] - 1); } ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 font-display fw-bold text-pa-primary mb-0">Tampilan Beranda</h1>
        <p class="text-secondary small mb-0">Susun beranda dengan bebas: seret kotak ke mana saja, tarik sudutnya untuk mengubah lebar & tinggi.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('/') ?>" target="_blank" rel="noopener" class="btn btn-outline-success"><i class="bi bi-eye" aria-hidden="true"></i> Lihat Beranda</a>
        <a href="<?= admin_url('beranda/baru') ?>" class="btn btn-pa-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Tampilan</a>
    </div>
</div>

<div class="card p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
        <ul class="small text-secondary mb-0 ps-3">
            <li><strong>Pindah:</strong> seret kotak lewat <i class="bi bi-arrows-move" aria-hidden="true"></i> ke kiri, kanan, atas, atau bawah. Kotak lain bergeser otomatis agar tidak bertumpuk.</li>
            <li><strong>Ubah ukuran:</strong> tarik sudut kanan-bawah <i class="bi bi-arrows-angle-expand" aria-hidden="true"></i> (lebar 1–12 kolom, tinggi 1–12 baris).</li>
            <li><strong>Keyboard:</strong> fokus ke <i class="bi bi-arrows-move" aria-hidden="true"></i> lalu tekan panah untuk memindah, <kbd>Shift</kbd>+panah untuk mengubah ukuran.</li>
            <li>Kotak selebar 12 kolom yang sendirian di barisnya tampil <strong>selebar layar</strong> (cocok untuk slider, pintasan, indeks survei).</li>
        </ul>
        <form id="hbForm" action="<?= admin_url('beranda/susunan') ?>" method="post" class="d-flex align-items-center gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="layout" id="hbLayout">
            <span id="hbDirty" class="small text-warning-emphasis fw-semibold" hidden>● Belum disimpan</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="hbTidy" title="Naikkan semua kotak dan hapus baris kosong"><i class="bi bi-arrow-bar-up" aria-hidden="true"></i> Rapikan</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="hbUndo" disabled><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Batalkan</button>
            <button type="submit" class="btn btn-sm btn-pa-primary" id="hbSave" disabled><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan Susunan</button>
        </form>
    </div>

    <div class="gb-wrap">
        <div class="gb-ruler" aria-hidden="true"><?php for ($i = 1; $i <= 12; $i++): ?><span><?= $i ?></span><?php endfor ?></div>
        <div id="gbGrid" class="gb-grid" style="--rows: <?= $rowsUsed + 2 ?>" aria-label="Susunan beranda (grid 12 kolom)">
            <?php foreach ($items as $it): $t = $types[$it['type']] ?? [$it['type'], 'bi-square']; $name = $it['title'] ?: $t[0]; ?>
                <div class="gb-item<?= $it['is_active'] ? '' : ' gb-off' ?>" id="tile-<?= $it['id'] ?>" data-id="<?= $it['id'] ?>" data-name="<?= esc($name, 'attr') ?>"
                     data-x="<?= (int) $it['grid_x'] ?>" data-y="<?= (int) $it['grid_y'] ?>" data-w="<?= (int) $it['grid_w'] ?>" data-h="<?= (int) $it['grid_h'] ?>"
                     style="grid-column: <?= (int) $it['grid_x'] ?> / span <?= (int) $it['grid_w'] ?>; grid-row: <?= (int) $it['grid_y'] ?> / span <?= (int) $it['grid_h'] ?>;">
                    <div class="gb-head">
                        <button type="button" class="gb-move" aria-label="Pindah atau ubah ukuran <?= esc($name, 'attr') ?>" aria-describedby="gbHelp"><i class="bi bi-arrows-move" aria-hidden="true"></i></button>
                        <i class="bi <?= $t[1] ?> text-pa-primary" aria-hidden="true"></i>
                        <span class="gb-name"><?= esc($name) ?></span>
                        <a href="<?= admin_url('beranda/' . $it['id']) ?>" class="gb-edit"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Ubah <?= esc($name) ?></span></a>
                    </div>
                    <div class="gb-meta"><?= esc($t[0]) ?><?php if (! $it['is_active']): ?> · <span class="badge bg-secondary">Tersembunyi</span><?php endif ?></div>
                    <span class="gb-size" aria-hidden="true"><?= (int) $it['grid_w'] ?>×<?= (int) $it['grid_h'] ?></span>
                    <span class="gb-resize" aria-hidden="true" title="Tarik untuk mengubah lebar & tinggi"><i class="bi bi-arrows-angle-expand"></i></span>
                </div>
            <?php endforeach ?>
            <div class="gb-preview" hidden></div>
        </div>
    </div>
    <p id="gbHelp" class="visually-hidden">Panah: pindah satu sel. Shift + panah: ubah lebar atau tinggi.</p>
    <p class="visually-hidden" role="status" id="hbLive"></p>
    <?php if (! $items): ?><p class="text-center text-secondary small mt-2 mb-0">Beranda masih kosong. <a href="<?= admin_url('beranda/baru') ?>">Tambah tampilan pertama</a>.</p><?php endif ?>
</div>

<div class="alert alert-light border small"><i class="bi bi-lightbulb text-pa-secondary" aria-hidden="true"></i> SK 2722/2026 Bab III huruf C: beranda sederhana dan tidak terlalu ramai, SIPP & jadwal sidang mudah ditemukan, hindari elemen berat (animasi berlebihan, siaran langsung) di halaman utama.</div>

<h2 class="h5 fw-bold">Daftar Tampilan</h2>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead><tr><th scope="col">Tampilan</th><th scope="col">Jenis</th><th scope="col">Posisi (kolom, baris)</th><th scope="col">Ukuran</th><th scope="col">Status</th><th scope="col" class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): $t = $types[$it['type']] ?? [$it['type'], 'bi-square']; $name = $it['title'] ?: $t[0]; ?>
                <tr>
                    <td class="fw-semibold"><i class="bi <?= $t[1] ?> text-pa-primary me-1" aria-hidden="true"></i><?= esc($name) ?></td>
                    <td><?= esc($t[0]) ?></td>
                    <td>kolom <?= (int) $it['grid_x'] ?>, baris <?= (int) $it['grid_y'] ?></td>
                    <td><?= (int) $it['grid_w'] ?>/12 × <?= (int) $it['grid_h'] ?> baris</td>
                    <td><?= $it['is_active'] ? '<span class="badge bg-success">Tampil</span>' : '<span class="badge bg-secondary">Tersembunyi</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= admin_url('beranda/' . $it['id']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil" aria-hidden="true"></i> Ubah</a>
                        <form action="<?= admin_url('beranda/' . $it['id'] . '/status') ?>" method="post" class="d-inline"><?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary"><i class="bi <?= $it['is_active'] ? 'bi-eye-slash' : 'bi-eye' ?>" aria-hidden="true"></i><span class="visually-hidden"><?= $it['is_active'] ? 'Sembunyikan' : 'Tampilkan' ?> <?= esc($name) ?></span></button></form>
                        <form action="<?= admin_url('beranda/' . $it['id'] . '/salin') ?>" method="post" class="d-inline"><?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-copy" aria-hidden="true"></i><span class="visually-hidden">Duplikat <?= esc($name) ?></span></button></form>
                        <form action="<?= admin_url('beranda/' . $it['id'] . '/hapus') ?>" method="post" class="d-inline" data-confirm="Hapus tampilan “<?= esc($name, 'attr') ?>”?"><?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Hapus <?= esc($name) ?></span></button></form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
