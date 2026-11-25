<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Backup;

$mb = static fn (int $b): string => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format(max(1, $b / 1024), 0, ',', '.') . ' KB';
$badge = ['terjadwal' => 'text-bg-success', 'pembaruan' => 'text-bg-primary', 'manual' => 'text-bg-secondary'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 font-display fw-bold text-pa-primary mb-0"><i class="bi bi-database-down" aria-hidden="true"></i> Cadangan Database</h1>
        <p class="text-secondary small mb-0">Salinan seluruh isi database (berita, halaman, pengaturan, data layanan). Disimpan di <code>writable/backups/database/</code> yang tidak bisa dibuka dari web.</p>
    </div>
    <form action="<?= admin_url('cadangan/buat') ?>" method="post" data-loading="Membuat cadangan… mohon tunggu"><?= csrf_field() ?>
        <button class="btn btn-pa-primary"><i class="bi bi-plus-circle" aria-hidden="true"></i> Buat cadangan sekarang</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 fw-bold">Pengaturan</h2>
            <form action="<?= admin_url('cadangan/pengaturan') ?>" method="post" class="row g-2 align-items-end"><?= csrf_field() ?>
                <div class="col-sm-5">
                    <label class="form-label small fw-semibold" for="bs">Cadangan otomatis</label>
                    <select class="form-select" id="bs" name="backup_schedule">
                        <?php foreach (['daily' => 'Harian', 'weekly' => 'Mingguan', 'off' => 'Nonaktif'] as $k => $l): ?>
                            <option value="<?= $k ?>"<?= $schedule === $k ? ' selected' : '' ?>><?= $l ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold" for="bk">Jumlah yang disimpan</label>
                    <select class="form-select" id="bk" name="backup_keep">
                        <?php for ($i = 1; $i <= Backup::MAX_KEEP; $i++): ?>
                            <option value="<?= $i ?>"<?= $keep === $i ? ' selected' : '' ?>><?= $i ?> terakhir</option>
                        <?php endfor ?>
                    </select>
                </div>
                <div class="col-sm-3"><button class="btn btn-outline-secondary w-100">Simpan</button></div>
            </form>
            <p class="small text-secondary mb-0 mt-2">Cadangan <strong>otomatis</strong> dan <strong>sebelum Update Website</strong> masing-masing disimpan <?= $keep ?> terakhir; yang lebih lama <strong>dihapus otomatis</strong>. Cadangan <strong>manual</strong> tidak dihapus otomatis.</p>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100"><div class="card-body small">
            <h2 class="h6 fw-bold">Ringkasan</h2>
            <div>Jumlah cadangan database: <strong><?= count($list) ?></strong></div>
            <div>Ruang terpakai folder cadangan: <strong><?= $mb($usage) ?></strong></div>
            <div>Terakhir diunduh ke komputer: <strong class="<?= time() - $lastDownload > 30 * 86400 ? 'text-danger' : '' ?>"><?= $lastDownload ? esc(date('d/m/Y H:i', $lastDownload)) : 'belum pernah' ?></strong></div>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Dibuat</th><th>Jenis</th><th>Ukuran</th><th class="text-end">Tindakan</th></tr></thead>
        <tbody>
        <?php if (! $list): ?><tr><td colspan="4" class="text-center text-secondary py-4">Belum ada cadangan. Klik <strong>Buat cadangan sekarang</strong>.</td></tr><?php endif ?>
        <?php foreach ($list as $b): ?>
            <tr>
                <td><?= esc(date('d/m/Y H:i', $b['time'])) ?><br><span class="small text-secondary font-monospace"><?= esc($b['name']) ?></span></td>
                <td><span class="badge <?= $badge[$b['type']] ?>"><?= Backup::TYPES[$b['type']] ?></span></td>
                <td><?= $mb($b['size']) ?></td>
                <td class="text-end text-nowrap">
                    <a href="<?= admin_url('cadangan/unduh/' . $b['name']) ?>" class="btn btn-sm btn-pa-primary"><i class="bi bi-download" aria-hidden="true"></i> Unduh</a>
                    <form action="<?= admin_url('cadangan/hapus') ?>" method="post" class="d-inline" data-confirm="Hapus cadangan <?= esc($b['name'], 'attr') ?>? Berkas tidak dapat dikembalikan."><?= csrf_field() ?>
                        <input type="hidden" name="name" value="<?= esc($b['name'], 'attr') ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i></button></form>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
</div>

<div class="alert alert-light border small">
    <strong>Penting:</strong>
    <ul class="mb-0">
        <li>Cadangan di hosting <strong>tidak menolong bila hostingnya rusak atau diretas</strong>. Unduh cadangan terbaru ke komputer kantor secara rutin, mis. sebulan sekali.</li>
        <li>Memulihkan: phpMyAdmin → pilih database → <em>Impor</em> → pilih berkas <code>.sql.gz</code> (tidak perlu diekstrak).</li>
        <li>Cadangan ini berisi database saja. Gambar dan PDF unggahan ada di folder <code>public/uploads</code> dan <code>writable/uploads</code>; cadangkan lewat fitur Backup di cPanel.</li>
    </ul>
</div>
<?= $this->endSection() ?>
