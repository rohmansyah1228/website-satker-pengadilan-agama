<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $locked = $id && ! empty($res['locked']) && ! empty($row[$res['locked']]); ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?= admin_url("data/{$key}") ?>"><?= esc($res['title']) ?></a></li><li class="breadcrumb-item active" aria-current="page"><?= $id ? 'Ubah' : 'Tambah' ?></li></ol></nav>
<h1 class="h3 font-display fw-bold text-pa-primary mb-3"><?= $id ? 'Ubah' : 'Tambah' ?> <?= esc($res['singular']) ?></h1>
<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert" tabindex="-1" id="formErrors"><strong>Periksa kembali isian berikut:</strong>
        <ul class="mb-0"><?php foreach ($errors as $n => $e): ?><li><a href="#f_<?= esc($n, 'attr') ?>" class="alert-link"><?= esc($e) ?></a></li><?php endforeach ?></ul></div>
<?php endif ?>
<?php if ($locked): ?><div class="alert alert-info small"><i class="bi bi-lock-fill" aria-hidden="true"></i> Data bawaan SK 2722/2026 — sebagian isian dikunci.</div><?php endif ?>
<form action="<?= $id ? admin_url("data/{$key}/{$id}") : admin_url("data/{$key}/baru") ?>" method="post" enctype="multipart/form-data" class="card p-4" novalidate>
    <?= csrf_field() ?>
    <div class="row">
        <?php foreach ($res['fields'] as $name => $f): ?>
            <?php $wide = in_array($f[1], ['richtext', 'textarea', 'image', 'pdf'], true) || $name === 'title'; ?>
            <div class="<?= $wide ? 'col-12' : 'col-md-6' ?>">
                <?= view('admin/_field', ['name' => $name, 'f' => $f, 'value' => $row[$name] ?? null, 'errors' => $errors, 'lookups' => $lookups, 'lockedField' => $locked && ! empty($f['lockable'])]) ?>
            </div>
        <?php endforeach ?>
    </div>
    <div class="d-flex flex-wrap gap-2 border-top pt-3">
        <button type="submit" class="btn btn-pa-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan</button>
        <?php if (! $id): ?><button type="submit" name="_after" value="new" class="btn btn-outline-success">Simpan & Tambah Lagi</button><?php endif ?>
        <a href="<?= admin_url("data/{$key}") ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
<?= $this->endSection() ?>
