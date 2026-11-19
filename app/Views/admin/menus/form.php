<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $req = ! empty($m['is_required']); ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?= admin_url('menu') ?>">Menu Navigasi</a></li><li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li></ol></nav>
<h1 class="h3 font-display fw-bold text-pa-primary mb-3"><?= esc($title) ?></h1>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?php if ($req): ?><div class="alert alert-info small"><i class="bi bi-lock-fill" aria-hidden="true"></i> Menu wajib SK: hanya label yang dapat diubah. Urutan diatur dari daftar menu.</div><?php endif ?>
<form action="<?= $id ? admin_url('menu/' . $id) : admin_url('menu/baru') ?>" method="post" class="card p-4" style="max-width: 46rem" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="label" class="form-label fw-semibold">Label <span class="text-danger" aria-hidden="true">*</span></label>
        <input type="text" id="label" name="label" value="<?= esc($m['label'] ?? '', 'attr') ?>" class="form-control" required maxlength="120">
    </div>
    <?php if (! $req): ?>
        <div class="mb-3">
            <label for="parent_id" class="form-label fw-semibold">Induk</label>
            <select id="parent_id" name="parent_id" class="form-select">
                <option value="">— Menu utama (tingkat 1) —</option>
                <?php foreach ($parents as $pid => $pl): ?><option value="<?= $pid ?>"<?= (string) ($m['parent_id'] ?? '') === (string) $pid ? ' selected' : '' ?>><?= esc($pl) ?></option><?php endforeach ?>
            </select>
        </div>
        <fieldset class="mb-3">
            <legend class="form-label fw-semibold fs-6">Jenis tautan</legend>
            <?php foreach (['page' => 'Halaman situs', 'route' => 'Alamat internal (mis. /berita/kategori/inovasi)', 'url' => 'Tautan luar (https://…)'] as $k => $l): ?>
                <div class="form-check"><input class="form-check-input" type="radio" name="type" id="t_<?= $k ?>" value="<?= $k ?>"<?= ($m['type'] ?? 'page') === $k ? ' checked' : '' ?>><label class="form-check-label" for="t_<?= $k ?>"><?= $l ?></label></div>
            <?php endforeach ?>
        </fieldset>
        <div class="mb-3" data-show-when="type=page">
            <label for="page_id" class="form-label fw-semibold">Halaman</label>
            <select id="page_id" name="page_id" class="form-select">
                <option value="">— Pilih halaman —</option>
                <?php $cur = null; foreach ($pages as $p): if ($cur !== $p['section']): if ($cur !== null): ?></optgroup><?php endif; $cur = $p['section']; ?><optgroup label="<?= esc($sections[$p['section']]['label'] ?? $p['section'], 'attr') ?>"><?php endif ?>
                    <option value="<?= $p['id'] ?>"<?= (string) ($m['page_id'] ?? '') === (string) $p['id'] ? ' selected' : '' ?>><?= esc($p['title']) ?><?= $p['status'] !== 'published' ? ' (draft)' : '' ?></option>
                <?php endforeach ?><?php if ($cur !== null): ?></optgroup><?php endif ?>
            </select>
            <div class="form-text">Belum ada halamannya? <a href="<?= admin_url('halaman/baru') ?>">Buat halaman baru</a>.</div>
        </div>
        <div class="mb-3" data-show-when="type=route,url">
            <label for="url" class="form-label fw-semibold">Alamat / Tautan</label>
            <input type="text" id="url" name="url" value="<?= esc($m['url'] ?? '', 'attr') ?>" class="form-control font-monospace" spellcheck="false" inputmode="url">
        </div>
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="new_tab" name="new_tab" value="1"<?= ! empty($m['new_tab']) ? ' checked' : '' ?>><label class="form-check-label" for="new_tab">Buka di tab baru</label></div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"<?= ! empty($m['is_active']) ? ' checked' : '' ?>><label class="form-check-label" for="is_active">Tampilkan di situs</label></div>
    <?php endif ?>
    <div class="d-flex gap-2"><button class="btn btn-pa-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan</button><a href="<?= admin_url('menu') ?>" class="btn btn-outline-secondary">Batal</a></div>
</form>
<?= $this->endSection() ?>
