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
<?php $q = $back ? '?kembali=' . rawurlencode($back) : ''; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><?php foreach ($res['list'] as $c => $l): ?><th scope="col"><?= esc($l) ?></th><?php endforeach ?><th scope="col" class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php $first = true; foreach ($res['list'] as $c => $l): ?>
                        <?php if ($first): $first = false; ?>
                            <td><a href="<?= admin_url("data/{$key}/{$row['id']}") . $q ?>" class="fw-semibold text-decoration-none"><?= $render($c, $row) ?: '#' . $row['id'] ?></a>
                                <?php if (! empty($res['locked']) && ! empty($row[$res['locked']])): ?> <span class="badge text-bg-light border" title="Bawaan SK"><i class="bi bi-lock-fill" aria-hidden="true"></i> SK</span><?php endif ?>
                            </td>
                        <?php else: ?>
                            <td><?= $render($c, $row) ?></td>
                        <?php endif ?>
                    <?php endforeach ?>
                    <td class="text-end text-nowrap">
                        <?php if (! empty($res['view']) && ($row['status'] ?? 'published') === 'published' && ($row['is_active'] ?? 1)): ?>
                            <a href="<?= site_url(str_replace(['{slug}', '{id}'], [$row['slug'] ?? '', $row['id']], $res['view'])) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Lihat"><i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Lihat</span></a>
                        <?php endif ?>
                        <a href="<?= admin_url("data/{$key}/{$row['id']}") . $q ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil" aria-hidden="true"></i> Ubah</a>
                        <?php if (empty($res['locked']) || empty($row[$res['locked']])): ?>
<?php if ($back): /* disematkan di dalam formulir lain: tanpa <form> bersarang */ ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-delete-url="<?= admin_url("data/{$key}/{$row['id']}/hapus") ?>" data-kembali="<?= esc($back, 'attr') ?>" data-confirm-msg="Hapus <?= esc(strtolower($res['singular']), 'attr') ?> ini? Data juga terhapus dari menu <?= esc($res['title'], 'attr') ?>."><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Hapus</span></button>
                        <?php else: ?>
                                                <form action="<?= admin_url("data/{$key}/{$row['id']}/hapus") ?>" method="post" class="d-inline" data-confirm="Hapus <?= esc(strtolower($res['singular']), 'attr') ?> ini? Tindakan tidak dapat dibatalkan.">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Hapus</span></button>
                        </form>
                        <?php endif ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if (! $rows): ?><tr><td colspan="<?= count($res['list']) + 1 ?>" class="text-center text-secondary py-4">Belum ada data.</td></tr><?php endif ?>
            </tbody>
        </table>
    </div>
</div>
