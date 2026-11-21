<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h1 class="h3 font-display fw-bold text-pa-primary mb-3">Pengaturan Situs</h1>
<nav aria-label="Kelompok pengaturan">
    <ul class="nav nav-tabs mb-0">
        <?php foreach ($groups as $k => [$label]): if ($k === 'keamanan' && ! can([], true)) continue; ?>
            <li class="nav-item"><a class="nav-link<?= $tab === $k ? ' active' : '' ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?> href="<?= admin_url('pengaturan?tab=' . $k) ?>"><?= esc($label) ?></a></li>
        <?php endforeach ?>
    </ul>
</nav>
<?php if (isset($groups[$tab])): [$label, $fields] = $groups[$tab]; ?>
<form action="<?= admin_url('pengaturan') ?>" method="post" enctype="multipart/form-data" class="card p-4 border-top-0 rounded-top-0" novalidate>
    <?= csrf_field() ?><input type="hidden" name="_tab" value="<?= esc($tab, 'attr') ?>">
    <?php if ($tab === 'identitas'): ?><div class="alert alert-light border small"><i class="bi bi-palette text-pa-primary" aria-hidden="true"></i> Warna resmi Peradilan Agama (<code>#056e4f</code> & <code>#ff9900</code>) dikunci sesuai SK Bab III huruf B.1.</div><?php endif ?>
    <div class="row">
        <?php foreach ($fields as $key => $def): [$lbl, $type, $help] = $def;
            $f = [$lbl, $type, '', 'help' => $help];
            if ($type === 'select') { $f['options'] = $def[3]; } ?>
            <div class="<?= in_array($type, ['textarea', 'image'], true) ? 'col-12' : 'col-md-6' ?>">
                <?= view('admin/_field', ['name' => $key, 'f' => $f, 'value' => $values[$key] ?? '', 'errors' => [], 'lookups' => []]) ?>
            </div>
        <?php endforeach ?>
    </div>
    <div><button class="btn btn-pa-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan Pengaturan</button></div>
</form>
<?php if ($tab === 'tautan' && ! empty($feed)): $st = $feed['status']; ?>
<div class="card p-4 mt-3">
    <h2 class="h6 fw-bold mb-2"><i class="bi bi-broadcast text-pa-primary" aria-hidden="true"></i> Arsip Pengumuman Badilag &amp; MA</h2>
    <p class="small text-secondary mb-2">Setiap jam hanya pengumuman yang belum ada yang diambil; yang sudah tersimpan dilewati. Pengumuman lama diambil bertahap mundur sampai tahun <?= (int) $st['since'] ?>. Terakhir diperiksa: <?= $st['at'] ? esc(date('d-m-Y H:i', $st['at'])) : 'belum pernah' ?>.</p>
    <ul class="small mb-3">
        <?php foreach ($feed['sources'] as $k => [$label, $url]): $s = $st['state'][$k] ?? []; ?>
            <li><strong><?= esc($label) ?></strong> — <?= number_format((int) ($st['counts'][$k] ?? 0), 0, ',', '.') ?> pengumuman tersimpan
                <?php if (! empty($s['feed'])): ?>· <?= ! empty($s['done']) ? 'arsip lama lengkap' : 'arsip lama sedang diambil bertahap' ?><?php endif ?>
                <?php if (! empty($st['errors'][$k])): ?><span class="text-danger">· gagal diambil terakhir kali: <?= esc($st['errors'][$k]) ?><?= str_contains($st['errors'][$k], '403') ? ' (situs sumber menolak pengambilan otomatis)' : '' ?></span><?php endif ?>
                <br><a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener" class="text-secondary"><?= esc($url) ?></a></li>
        <?php endforeach ?>
        <?php if (! $feed['sources']): ?><li class="text-secondary">Semua sumber dimatikan.</li><?php endif ?>
    </ul>
    <form action="<?= admin_url('pengaturan/pengumuman-segarkan') ?>" method="post" class="d-flex flex-wrap gap-2 align-items-center">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Ambil sekarang</button>
        <a href="<?= site_url('pengumuman-badilag-ma') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link">Lihat halaman publik</a>
    </form>
</div>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
