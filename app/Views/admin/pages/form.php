<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $req = ! empty($page['is_required']); $codes = array_filter(explode(',', (string) ($page['module_param'] ?? ''))); ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?= admin_url('halaman') ?>">Halaman</a></li><li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li></ol></nav>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 font-display fw-bold text-pa-primary mb-0"><?= esc($title) ?></h1>
    <?php if ($id): ?><a href="<?= site_url($page['section'] . '/' . $page['slug']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye" aria-hidden="true"></i> Lihat halaman</a><?php endif ?>
</div>
<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $n => $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div>
<?php endif ?>
<?php if ($criteria): ?>
    <div class="alert alert-light border small"><strong><i class="bi bi-clipboard-check text-pa-primary" aria-hidden="true"></i> Kriteria SINOVA untuk halaman ini:</strong>
        <ul class="mb-0"><?php foreach ($criteria as $c): ?><li><strong><?= esc($c[0]) ?></strong> — <?= esc($c[2]) ?></li><?php endforeach ?></ul>
        <?php if (str_contains((string) ($page['content'] ?? ''), '[LENGKAPI]')): ?><p class="mb-0 mt-2 text-danger">Hapus/ganti semua teks bertanda <strong>[LENGKAPI]</strong> dengan isi sebenarnya.</p><?php endif ?>
    </div>
<?php endif ?>
<form action="<?= $id ? admin_url('halaman/' . $id) : admin_url('halaman/baru') ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card p-4">
                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">Judul <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" id="title" name="title" value="<?= esc($page['title'] ?? '', 'attr') ?>" class="form-control form-control-lg" required data-slug-source>
                </div>
                <fieldset class="mb-4">
                    <legend class="form-label fw-semibold fs-6 mb-1">Isi Halaman</legend>
                    <p class="form-text mt-0 mb-2">Susun isi dari beberapa blok: teks, tabel (kolom tautan tampil sebagai tombol <em>Lihat</em> — cocok untuk tautan Google Drive), gambar, atau halaman lain yang disematkan. Seret <i class="bi bi-grip-vertical" aria-hidden="true"></i> untuk mengubah urutan.</p>
                    <div id="pbList" class="pb-list" data-media="<?= esc(base_url(), 'attr') ?>"></div>
                    <div class="pb-add" role="group" aria-label="Tambah blok">
                        <span class="small fw-semibold text-secondary me-1">Tambah blok:</span>
                        <?php foreach ($blockTypes as $k => $t): ?>
                            <button type="button" class="btn btn-sm btn-outline-success" data-pb-add="<?= $k ?>"><i class="bi <?= $t['icon'] ?>" aria-hidden="true"></i> <?= esc($k === 'embed' ? 'Sematkan' : $t['label']) ?></button>
                        <?php endforeach ?>
                    </div>
                    <input type="hidden" name="blocks_json" id="pbJson" value="">
                    <script type="application/json" id="pbData"><?= json_encode(array_values($blocks), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
                    <script type="application/json" id="pbConfig"><?= json_encode(['types' => $blockTypes, 'sizes' => $sizes, 'aligns' => $aligns], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
                </fieldset>
                <div class="mb-0">
                    <label for="image" class="form-label fw-semibold">Gambar Sampul Halaman <span class="fw-normal text-secondary">(opsional)</span></label>
                    <?php if (! empty($page['image'])): ?>
                        <div class="d-flex align-items-center gap-3 mb-2"><img src="<?= media_url($page['image']) ?>" alt="Gambar saat ini" style="max-height: 110px" class="rounded border">
                            <div class="form-check"><input type="checkbox" class="form-check-input" id="image_rm" name="image__remove" value="1"><label class="form-check-label small" for="image_rm">Hapus gambar</label></div></div>
                    <?php endif ?>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="form-control" aria-describedby="imageHelp">
                    <div id="imageHelp" class="form-text">Untuk diagram struktur organisasi, peta yurisdiksi, alur prosedur, dll. Tampil di atas isi halaman.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 mb-3">
                <div class="mb-3">
                    <label for="section" class="form-label fw-semibold">Bagian (menu utama)</label>
                    <select id="section" name="section" class="form-select"<?= $req ? ' disabled' : '' ?>>
                        <?php foreach ($sections as $k => $l): ?><option value="<?= $k ?>"<?= ($page['section'] ?? '') === $k ? ' selected' : '' ?>><?= esc($l) ?></option><?php endforeach ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="slug" class="form-label fw-semibold">Alamat (slug)</label>
                    <input type="text" id="slug" name="slug" value="<?= esc($page['slug'] ?? '', 'attr') ?>" class="form-control font-monospace" spellcheck="false" data-slug-target<?= $req ? ' readonly' : '' ?> aria-describedby="slugHelp">
                    <div id="slugHelp" class="form-text"><?= $req ? 'Dikunci (halaman wajib SK).' : 'Bila diubah, alamat lama otomatis dialihkan (301).' ?></div>
                </div>
                <div class="mb-3">
                    <label for="module" class="form-label fw-semibold">Modul data</label>
                    <select id="module" name="module" class="form-select"<?= $req ? ' disabled' : '' ?>>
                        <?php foreach ($modules as $k => $l): ?><option value="<?= $k ?>"<?= ($page['module'] ?? '') === $k ? ' selected' : '' ?>><?= esc($l) ?></option><?php endforeach ?>
                    </select>
                    <?php if ($req): ?><input type="hidden" name="module" value="<?= esc($page['module'], 'attr') ?>"><?php endif ?>
                </div>
                <div class="mb-3" data-show-when="module=pegawai">
                    <label for="employee_view" class="form-label fw-semibold">Tampilan daftar pegawai</label>
                    <select id="employee_view" name="employee_view" class="form-select">
                        <option value="kartu"<?= ($page['module_param'] ?? '') !== 'tabel' ? ' selected' : '' ?>>Kartu berfoto</option>
                        <option value="tabel"<?= ($page['module_param'] ?? '') === 'tabel' ? ' selected' : '' ?>>Tabel dengan tombol Detail</option>
                    </select>
                    <div class="form-text">Setiap pegawai punya halaman profil sendiri. Data diisi di <a href="<?= admin_url('data/pegawai') ?>">Pejabat &amp; Pegawai</a>.</div>
                </div>
                <fieldset class="mb-3" data-show-when="module=documents,survei">
                    <legend class="form-label fw-semibold fs-6">Jenis dokumen yang ditampilkan</legend>
                    <div class="border rounded p-2" style="max-height: 220px; overflow:auto">
                        <?php foreach ($docTypes as $t): $checked = in_array($t['code'], $codes, true); $base = $req && $checked; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="doc_codes[]" value="<?= esc($t['code'], 'attr') ?>" id="dc<?= $t['id'] ?>"<?= $checked ? ' checked' : '' ?><?= $base ? ' disabled' : '' ?>>
                                <label class="form-check-label small" for="dc<?= $t['id'] ?>"><?= esc($t['name']) ?><?= $base ? ' <i class="bi bi-lock-fill text-secondary" aria-label="bawaan"></i>' : '' ?></label>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <a href="<?= admin_url('data/jenis-dokumen/baru') ?>" class="small">+ Jenis dokumen baru</a>
                </fieldset>
                <div class="mb-3">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select id="status" name="status" class="form-select"<?= $req ? ' disabled' : '' ?>>
                        <option value="published"<?= ($page['status'] ?? '') === 'published' ? ' selected' : '' ?>>Terbit</option>
                        <option value="draft"<?= ($page['status'] ?? '') === 'draft' ? ' selected' : '' ?>>Draft</option>
                    </select>
                    <?php if ($req): ?><input type="hidden" name="status" value="published"><?php endif ?>
                </div>
                <div class="mb-3">
                    <label for="meta_description" class="form-label fw-semibold">Deskripsi singkat (SEO)</label>
                    <textarea id="meta_description" name="meta_description" rows="2" maxlength="255" class="form-control"><?= esc($page['meta_description'] ?? '') ?></textarea>
                </div>
                <?php if (! $id): ?>
                    <div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="add_to_menu" name="add_to_menu" value="1" checked><label class="form-check-label" for="add_to_menu">Tambahkan ke menu bagian ini</label></div>
                <?php endif ?>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-pa-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan</button>
                    <button type="submit" name="_after" value="view" class="btn btn-outline-success">Simpan & Lihat</button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php if ($id && ! $req): ?>
<div class="row"><div class="col-lg-4 offset-lg-8">
    <form action="<?= admin_url('halaman/' . $id . '/hapus') ?>" method="post" data-confirm="Hapus halaman “<?= esc($page['title'], 'attr') ?>” beserta tautan menunya?">
        <?= csrf_field() ?><button class="btn btn-outline-danger w-100"><i class="bi bi-trash" aria-hidden="true"></i> Hapus Halaman</button>
    </form>
</div></div>
<?php endif ?>
<?= $this->endSection() ?>
