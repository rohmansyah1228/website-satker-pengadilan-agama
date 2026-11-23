<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h1 class="h3 font-display fw-bold text-pa-primary mb-0">Halaman</h1>
        <p class="text-secondary small mb-0">Halaman bertanda <i class="bi bi-lock-fill" aria-hidden="true"></i> wajib menurut SK — alamatnya dikunci agar URL yang diinput ke SINOVA tetap berlaku.</p></div>
    <a href="<?= admin_url('halaman/baru') ?>" class="btn btn-pa-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Halaman Baru</a>
</div>
<?php if (sk_unlocked()): ?>
<div class="alert alert-warning d-flex align-items-start gap-2"><i class="bi bi-unlock-fill mt-1" aria-hidden="true"></i><div><strong>Kunci halaman wajib SK sedang terbuka.</strong> Alamat (slug) dan modul halaman wajib SK kini dapat diubah/dihapus untuk menyesuaikan SK baru. Setelah selesai, kembalikan ke <em>Terkunci</em> di <a href="<?= admin_url('pengaturan?tab=keamanan') ?>">Pengaturan → Keamanan</a>.</div></div>
<?php endif ?>
<?php foreach ($sections as $sec => $label): ?>
    <?php if (empty($grouped[$sec])) continue; ?>
    <div class="card mb-3">
        <div class="card-header bg-white"><h2 class="h6 fw-bold mb-0"><?= esc($label) ?></h2></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead><tr><th scope="col">Judul</th><th scope="col">Alamat</th><th scope="col">Modul</th><th scope="col">Kriteria</th><th scope="col">Status</th><th scope="col">Diperbarui</th><th scope="col" class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($grouped[$sec] as $p): $path = $p['section'] . '/' . $p['slug']; ?>
                    <tr>
                        <td><a href="<?= admin_url('halaman/' . $p['id']) ?>" class="fw-semibold text-decoration-none"><?= esc($p['title']) ?></a><?= $p['is_required'] ? ' <i class="bi bi-lock-fill text-secondary" title="Halaman wajib SK" aria-label="Halaman wajib SK"></i>' : '' ?></td>
                        <td><code>/<?= esc($path) ?></code></td>
                        <td><?= esc(config('SiteStructure')->modules[$p['module']] ?? '–') ?></td>
                        <td><?= esc(implode(', ', $criteria[$path] ?? [])) ?: '–' ?></td>
                        <td><?= $p['status'] === 'published' ? '<span class="badge bg-success">Terbit</span>' : '<span class="badge bg-secondary">Draft</span>' ?></td>
                        <td class="text-nowrap"><?= esc(tanggal_id($p['updated_at'])) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= site_url($path) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Lihat <?= esc($p['title']) ?></span></a>
                            <a href="<?= admin_url('halaman/' . $p['id']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil" aria-hidden="true"></i> Ubah</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach ?>
<?= $this->endSection() ?>
