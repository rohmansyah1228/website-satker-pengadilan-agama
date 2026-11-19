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
<?php endif ?>
<?= $this->endSection() ?>
