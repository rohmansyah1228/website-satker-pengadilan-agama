<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$fields = $res['fields'];
$render = static function (string $col, array $row) use ($fields, $lookups) {
    $v = $row[$col] ?? null;
    $f = $fields[$col] ?? null;
    if ($col === 'month' && $v) return bulan_id((int) $v);
    if (! $f) return esc((string) $v);
    switch ($f[1]) {
        case 'image': return $v ? '<img src="' . media_url($v) . '" alt="" class="' . ($col === 'photo' ? 'thumb-round' : 'thumb') . '">' : '<span class="text-secondary">–</span>';
        case 'pdf': return $v ? '<a href="' . esc(file_url((int) $v), 'attr') . '" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> PDF</a>' : ($row['external_url'] ?? '' ? '<i class="bi bi-link-45deg"></i> Tautan' : '<span class="text-danger small">belum ada</span>');
        case 'checkbox': return $v ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>';
        case 'icon': return '<i class="bi ' . esc((string) $v, 'attr') . ' fs-5 text-pa-primary" aria-hidden="true"></i>';
        case 'date': return esc(tanggal_id((string) $v));
        case 'datetime': return esc(tanggal_id((string) $v));
        case 'select':
            $opts = $f['options'] ?? ($lookups[$col] ?? []);
            return esc((string) ($opts[$v] ?? $v));
    }
    return esc(mb_strimwidth((string) $v, 0, 80, '…'));
};
?>
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

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><?php foreach ($res['list'] as $c => $l): ?><th scope="col"><?= esc($l) ?></th><?php endforeach ?><th scope="col" class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php $first = true; foreach ($res['list'] as $c => $l): ?>
                        <?php if ($first): $first = false; ?>
                            <td><a href="<?= admin_url("data/{$key}/{$row['id']}") ?>" class="fw-semibold text-decoration-none"><?= $render($c, $row) ?: '#' . $row['id'] ?></a>
                                <?php if (! empty($res['locked']) && ! empty($row[$res['locked']])): ?> <span class="badge text-bg-light border" title="Bawaan SK"><i class="bi bi-lock-fill" aria-hidden="true"></i> SK</span><?php endif ?>
                            </td>
                        <?php else: ?>
                            <td><?= $render($c, $row) ?></td>
                        <?php endif ?>
                    <?php endforeach ?>
                    <td class="text-end text-nowrap">
                        <?php if (! empty($res['view']) && ($row['status'] ?? 'published') === 'published'): ?>
                            <a href="<?= site_url(str_replace('{slug}', $row['slug'] ?? '', $res['view'])) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Lihat"><i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Lihat</span></a>
                        <?php endif ?>
                        <a href="<?= admin_url("data/{$key}/{$row['id']}") ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil" aria-hidden="true"></i> Ubah</a>
                        <?php if (empty($res['locked']) || empty($row[$res['locked']])): ?>
                        <form action="<?= admin_url("data/{$key}/{$row['id']}/hapus") ?>" method="post" class="d-inline" data-confirm="Hapus <?= esc(strtolower($res['singular']), 'attr') ?> ini? Tindakan tidak dapat dibatalkan.">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Hapus</span></button>
                        </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if (! $rows): ?><tr><td colspan="<?= count($res['list']) + 1 ?>" class="text-center text-secondary py-4">Belum ada data.</td></tr><?php endif ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= $pager ?></div>
<?= $this->endSection() ?>
