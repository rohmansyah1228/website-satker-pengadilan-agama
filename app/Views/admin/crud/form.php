<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$locked = $id && ! empty($res['locked']) && ! empty($row[$res['locked']]);
$layout = $res['layout'] ?? [];

// Bagi field ke kolom kiri (main) & panel kanan (side) sesuai tata letak menu
$placed = [];
$side   = [];
foreach ($layout['side'] ?? [] as $label => $names) {
    $side[$label] = array_values(array_filter($names, static fn ($n) => isset($res['fields'][$n])));
    $placed       = array_merge($placed, $side[$label]);
}
$main = [];
foreach ($layout['main'] ?? [] as $label => $names) {
    $main[$label] = array_values(array_filter($names, static fn ($n) => isset($res['fields'][$n])));
    $placed       = array_merge($placed, $main[$label]);
}
$rest = array_values(array_diff(array_keys($res['fields']), $placed));
if ($rest) {
    $main[''] = array_merge($main[''] ?? [], $rest);
}
$primary = null; // field utama tampil besar seperti "Judul" di Ubah Halaman
foreach (array_merge(...array_values($main ?: [[]])) as $n) {
    if (in_array($res['fields'][$n][1], ['text'], true)) {
        $primary = $n;
        break;
    }
}
$field = static fn (string $n, bool $large = false) => view('admin/_field', [
    'name' => $n, 'f' => $res['fields'][$n] + ($large ? ['large' => true] : []), 'value' => $row[$n] ?? null, 'errors' => $errors,
    'lookups' => $lookups, 'lockedField' => $locked && ! empty($res['fields'][$n]['lockable']), 'inputName' => null,
]);
$viewUrl = ($id && ! empty($res['view'])) ? site_url(str_replace(['{slug}', '{id}'], [$row['slug'] ?? '', $id], $res['view'])) : null;
$isLive  = ($row['status'] ?? 'published') === 'published' && ($row['is_active'] ?? 1) && (empty($row['published_at']) || $row['published_at'] <= date('Y-m-d H:i:s'));
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><?php if ($kembali): ?><li class="breadcrumb-item"><a href="<?= esc($kembali, 'attr') ?>">Ubah Halaman</a></li><?php else: ?><li class="breadcrumb-item"><a href="<?= admin_url("data/{$key}") ?>"><?= esc($res['title']) ?></a></li><?php endif ?><li class="breadcrumb-item active" aria-current="page"><?= $id ? 'Ubah' : 'Tambah' ?></li></ol></nav>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 font-display fw-bold text-pa-primary mb-0"><i class="bi <?= $res['icon'] ?> me-1" aria-hidden="true"></i><?= $id ? 'Ubah' : 'Tambah' ?> <?= esc($res['singular']) ?></h1>
    <?php if ($viewUrl): ?><a href="<?= esc($viewUrl, 'attr') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye" aria-hidden="true"></i> <?= $isLive ? 'Lihat di situs' : 'Pratinjau' ?></a><?php endif ?>
</div>
<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert" tabindex="-1" id="formErrors"><strong>Periksa kembali isian berikut:</strong>
        <ul class="mb-0"><?php foreach ($errors as $n => $e): ?><li><a href="#f_<?= esc($n, 'attr') ?>" class="alert-link"><?= esc($e) ?></a></li><?php endforeach ?></ul></div>
<?php endif ?>
<?php if ($locked): ?><div class="alert alert-info small"><i class="bi bi-lock-fill" aria-hidden="true"></i> Data bawaan SK 2722/2026 — sebagian isian dikunci.</div><?php endif ?>
<?php if (! empty($layout['intro'])): ?><div class="alert alert-light border small py-2"><i class="bi bi-info-circle text-pa-primary me-1" aria-hidden="true"></i><?= esc($layout['intro']) ?></div><?php endif ?>

<form action="<?= $id ? admin_url("data/{$key}/{$id}") : admin_url("data/{$key}/baru") ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <?php if ($kembali): ?><input type="hidden" name="kembali" value="<?= esc($kembali, 'attr') ?>"><?php endif ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card p-4">
                <?php foreach ($main as $label => $names): if (! $names) continue; ?>
                    <?php if ($label !== ''): ?><h2 class="crud-section"><?= esc($label) ?></h2><?php endif ?>
                    <div class="row">
                        <?php foreach ($names as $n): $t = $res['fields'][$n][1]; $wide = $n === $primary || in_array($t, ['richtext', 'textarea', 'image', 'pdf', 'url'], true); ?>
                            <div class="<?= $wide ? 'col-12' : 'col-md-6' ?>"><?= $field($n, $n === $primary) ?></div>
                        <?php endforeach ?>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 crud-side">
                <?php foreach ($side as $label => $names): if (! $names) continue; ?>
                    <h2 class="crud-side__title"><?= esc($label) ?></h2>
                    <?php foreach ($names as $n): ?><?= $field($n) ?><?php endforeach ?>
                <?php endforeach ?>

                <?php if (! empty($shownOn)): ?>
                    <div data-shown-on>
                    <h2 class="crud-side__title">Tampil di halaman</h2>
                    <ul class="list-unstyled small mb-3">
                        <?php foreach ($shownOn as $p): ?>
                            <li class="d-flex align-items-center gap-2 mb-1"<?= isset($p['types']) ? ' data-types="' . implode(',', $p['types']) . '"' : '' ?>>
                                <i class="bi bi-file-earmark-text text-pa-primary" aria-hidden="true"></i>
                                <a href="<?= esc($p['url'], 'attr') ?>" target="_blank" rel="noopener" class="text-truncate"><?= esc($p['label']) ?></a>
                                <?php if (! empty($p['edit'])): ?><a href="<?= esc($p['edit'], 'attr') ?>" class="ms-auto small text-secondary" title="Ubah halaman">ubah</a><?php endif ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                    <p class="small text-secondary mb-3" data-shown-none hidden>Jenis dokumen ini belum dipakai di halaman mana pun. Pilih jenisnya di <em>Halaman → Ubah → Jenis dokumen yang ditampilkan</em>.</p>
                    </div>
                <?php endif ?>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-pa-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan</button>
                    <?php if (! empty($res['view'])): ?><button type="submit" name="_after" value="view" class="btn btn-outline-success">Simpan & Lihat</button><?php endif ?>
                    <?php if (! $id): ?><button type="submit" name="_after" value="new" class="btn btn-outline-success">Simpan & Tambah Lagi</button><?php endif ?>
                    <a href="<?= esc($kembali ?? admin_url("data/{$key}"), 'attr') ?>" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection() ?>
