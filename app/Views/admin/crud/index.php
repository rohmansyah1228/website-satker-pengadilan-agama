<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 font-display fw-bold text-pa-primary mb-0"><i class="bi <?= $res['icon'] ?> me-1" aria-hidden="true"></i><?= esc($res['title']) ?></h1>
        <p class="text-secondary small mb-0"><?= number_format($total, 0, ',', '.') ?> data<?= $activeCount !== null ? " · {$activeCount} dari {$res['max']} aktif" : '' ?></p>
    </div>
    <a href="<?= admin_url("data/{$key}/baru") ?>" class="btn btn-pa-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah <?= esc($res['singular']) ?></a>
</div>
<?php if (! empty($res['hint'])): ?><div class="alert alert-light border small"><i class="bi bi-info-circle text-pa-primary me-1" aria-hidden="true"></i><?= esc($res['hint']) ?></div><?php endif ?>

<?php if (! empty($res['search']) || ! empty($res['filter'])): ?>
<form method="get" class="row g-2 mb-3" role="search">
    <?php if (! empty($res['search'])): ?>
        <div class="col-md-5"><label class="visually-hidden" for="q">Cari</label><input type="search" id="q" name="q" value="<?= esc($q, 'attr') ?>" class="form-control" placeholder="Cari…"></div>
    <?php endif ?>
    <?php foreach (($res['filter'] ?? []) as $col => $opts): $opts = is_array($opts) ? $opts : ($lookups[$col] ?? []); ?>
        <div class="col-md-3">
            <label class="visually-hidden" for="f_<?= $col ?>"><?= esc($fields[$col][0] ?? $col) ?></label>
            <select id="f_<?= $col ?>" name="<?= $col ?>" class="form-select" data-autosubmit>
                <option value="">Semua <?= esc(strtolower($fields[$col][0] ?? '')) ?></option>
                <?php foreach ($opts as $k => $v): ?><option value="<?= esc((string) $k, 'attr') ?>"<?= (string) ($filterValues[$col] ?? '') === (string) $k ? ' selected' : '' ?>><?= esc($v) ?></option><?php endforeach ?>
            </select>
        </div>
    <?php endforeach ?>
    <div class="col-auto"><button class="btn btn-outline-secondary">Terapkan</button></div>
</form>
<?php endif ?>

<?= view('admin/crud/_table', ['key' => $key, 'res' => $res, 'rows' => $rows, 'lookups' => $lookups, 'back' => null]) ?>
<div class="mt-3"><?= $pager ?></div>
<?= $this->endSection() ?>
