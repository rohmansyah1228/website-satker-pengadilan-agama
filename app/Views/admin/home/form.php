<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?= admin_url('beranda') ?>">Tampilan Beranda</a></li><li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li></ol></nav>
<h1 class="h3 font-display fw-bold text-pa-primary mb-1"><i class="bi <?= $type[1] ?> me-1" aria-hidden="true"></i><?= esc($title) ?></h1>
<p class="text-secondary small"><?= esc($type[2]) ?></p>
<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert"><strong>Periksa kembali isian berikut:</strong><ul class="mb-0"><?php foreach ($errors as $k => $e): ?><li><a class="alert-link" href="#f_<?= esc($k, 'attr') ?>"><?= esc($e) ?></a></li><?php endforeach ?></ul></div>
<?php endif ?>
<?php $cols = []; for ($i = 1; $i <= 12; $i++) { $cols[$i] = (string) $i; } $rows = []; for ($i = 1; $i <= 12; $i++) { $rows[$i] = $i . ' baris'; } ?>
<form action="<?= $id ? admin_url('beranda/' . $id) : admin_url('beranda/baru') ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <?php if (! $id): ?><input type="hidden" name="type" value="<?= esc($r['type'], 'attr') ?>"><?php endif ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card p-4">
                <?= view('admin/_field', ['inputName' => null, 'name' => 'title', 'f' => [$r['type'] === 'card' ? 'Judul kartu' : 'Judul', 'text', 'permit_empty', 'help' => 'Kosongkan bila tidak perlu judul.'], 'value' => $r['title'] ?? '', 'errors' => $errors]) ?>
                <?php if ($r['type'] !== 'slider'): ?>
                    <?= view('admin/_field', ['inputName' => null, 'name' => 'subtitle', 'f' => ['Sub-judul (tampil bila kotak selebar layar)', 'textarea', 'permit_empty'], 'value' => $r['subtitle'] ?? '', 'errors' => $errors]) ?>
                <?php endif ?>
                <?php foreach ($type['fields'] as $k => $f): ?>
                    <?= view('admin/_field', [
                        'name' => 'settings_' . $k, 'inputName' => $f[1] === 'image' ? 'settings_' . $k : "settings[{$k}]",
                        'f' => $f + (isset($lookups[$k]) ? ['options' => $lookups[$k]] : []),
                        'value' => $r['settings'][$k] ?? ($f['default'] ?? ''), 'errors' => $errors, 'lookups' => [],
                    ]) ?>
                <?php endforeach ?>
                <?php if ($r['type'] === 'videos'): ?>
                    <p class="small mb-0"><i class="bi bi-arrow-right-circle text-pa-primary" aria-hidden="true"></i> Video dikelola di <?php if (can(config('AdminResources')->resources['video']['roles'] ?? [])): ?><a href="<?= admin_url('data/video') ?>">Galeri Video</a><?php else: ?>menu Galeri Video<?php endif ?>.</p>
                <?php endif ?>
                <?php if (in_array($r['type'], ['slider', 'shortcuts'], true)): ?>
                    <p class="small mb-0"><i class="bi bi-arrow-right-circle text-pa-primary" aria-hidden="true"></i>
                        <?php $resKey = $r['type'] === 'slider' ? 'slider' : 'pintasan'; $resLbl = $r['type'] === 'slider' ? 'Slider Beranda' : 'Pintasan Layanan'; ?>
                        Isi <?= $r['type'] === 'slider' ? 'slide' : 'kartu pintasan' ?> dikelola di <?php if (can(config('AdminResources')->resources[$resKey]['roles'] ?? [])): ?><a href="<?= admin_url('data/' . $resKey) ?>"><?= $resLbl ?></a><?php else: ?>menu <?= $resLbl ?> (oleh Admin)<?php endif ?>.</p>
                <?php endif ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4">
                <fieldset class="mb-2">
                    <legend class="fs-6 fw-bold mb-1">Posisi & Ukuran</legend>
                    <p class="small text-secondary mb-2">Lebih mudah diatur dengan menyeret di editor. Kotak lain yang tertimpa otomatis bergeser ke bawah.</p>
                    <div class="row g-2">
                        <div class="col-6"><?= view('admin/_field', ['inputName' => null, 'name' => 'grid_x', 'f' => ['Kolom mulai', 'select', 'required', 'options' => $cols], 'value' => $r['grid_x'], 'errors' => $errors]) ?></div>
                        <div class="col-6"><?= view('admin/_field', ['inputName' => null, 'name' => 'grid_w', 'f' => ['Lebar (kolom)', 'select', 'required', 'options' => $cols], 'value' => $r['grid_w'], 'errors' => $errors]) ?></div>
                        <div class="col-6"><?= view('admin/_field', ['inputName' => null, 'name' => 'grid_y', 'f' => ['Baris', 'number', 'required|integer'], 'value' => $r['grid_y'], 'errors' => $errors]) ?></div>
                        <div class="col-6"><?= view('admin/_field', ['inputName' => null, 'name' => 'grid_h', 'f' => ['Tinggi', 'select', 'required', 'options' => $rows], 'value' => $r['grid_h'], 'errors' => $errors]) ?></div>
                    </div>
                </fieldset>
                <?php if ($r['type'] !== 'slider'): ?>
                    <?= view('admin/_field', ['inputName' => null, 'name' => 'bg', 'f' => ['Warna latar (kotak selebar layar)', 'select', 'required', 'options' => $cfg->backgrounds], 'value' => $r['bg'], 'errors' => $errors]) ?>
                    <?php if (! in_array($r['type'], ['card'], true)): ?>
                        <?= view('admin/_field', ['inputName' => null, 'name' => 'accent', 'f' => ['Garis aksen kotak', 'select', 'required', 'options' => $cfg->accents, 'help' => $r['type'] === 'image' ? 'Pilih "Tanpa garis" agar gambar tampil tanpa kotak.' : ''], 'value' => $r['accent'], 'errors' => $errors]) ?>
                    <?php endif ?>
                <?php endif ?>
                <?= view('admin/_field', ['inputName' => null, 'name' => 'is_active', 'f' => ['Tampilkan di beranda', 'checkbox', 'permit_empty'], 'value' => $r['is_active'], 'errors' => $errors]) ?>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-pa-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan</button>
                    <button type="submit" name="_after" value="view" class="btn btn-outline-success">Simpan & Lihat Beranda</button>
                    <a href="<?= admin_url('beranda') ?>" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection() ?>
