<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$fmtAge = static function (int $t): string {
    $d = time() - $t;

    return $d < 120 ? 'baru saja' : ($d < 7200 ? round($d / 60) . ' menit lalu' : ($d < 172800 ? round($d / 3600) . ' jam lalu' : round($d / 86400) . ' hari lalu'));
};
$levelBadge = static fn (string $l): string => $l === 'danger'
    ? '<span class="badge text-bg-danger">Bahaya</span>'
    : '<span class="badge text-bg-warning">Periksa</span>';
$kindLabel = ['changed' => 'Diubah', 'missing' => 'Hilang', 'unknown' => 'Berkas asing', 'suspect' => 'Mencurigakan'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 font-display fw-bold text-pa-primary mb-0"><i class="bi bi-shield-check" aria-hidden="true"></i> Pemeriksaan Keamanan</h1>
        <p class="text-secondary small mb-0">Memeriksa berkas website terhadap versi resmi bertanda tangan digital, dan isi website dari sisipan judi online / kode berbahaya.</p>
    </div>
    <form action="<?= admin_url('keamanan/periksa') ?>" method="post" data-loading="Memeriksa… mohon tunggu"><?= csrf_field() ?>
        <button class="btn btn-pa-primary"><i class="bi bi-search" aria-hidden="true"></i> Periksa sekarang</button>
    </form>
</div>

<?php if (! $scan): ?>
    <div class="alert alert-light border">Belum pernah diperiksa. Klik <strong>Periksa sekarang</strong>. Pemeriksaan juga berjalan otomatis setiap 24 jam saat Super Admin membuka Dashboard.</div>
<?php else:
    $sum   = $scan['summary'];
    $total = $sum['danger'] + $sum['warn'];
    $ref   = $scan['ref']; ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100 border-<?= $sum['danger'] ? 'danger' : ($sum['warn'] ? 'warning' : 'success') ?>">
                <div class="card-body">
                    <div class="small text-secondary">Hasil</div>
                    <div class="fs-4 fw-bold <?= $sum['danger'] ? 'text-danger' : ($sum['warn'] ? 'text-warning-emphasis' : 'text-success') ?>">
                        <?= $total ? $total . ' temuan' : '<i class="bi bi-check-circle-fill" aria-hidden="true"></i> Aman' ?>
                    </div>
                    <div class="small"><?= $sum['danger'] ?> bahaya · <?= $sum['warn'] ?> perlu diperiksa<?= $scan['ignored'] ? ' · ' . $scan['ignored'] . ' ditandai aman' : '' ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="small text-secondary">Pembanding berkas</div>
                <?php if ($ref['ok']): ?>
                    <div class="fw-semibold"><i class="bi bi-patch-check-fill text-success" aria-hidden="true"></i> Versi resmi <?= esc($ref['name'] ?: 'build ' . $ref['build']) ?></div>
                    <div class="small">Tanda tangan digital sah · sumber: <?= $ref['source'] === 'github' ? 'GitHub' : 'salinan lokal' ?> · <?= number_format($scan['checked'], 0, ',', '.') ?> berkas</div>
                <?php else: ?>
                    <div class="fw-semibold text-warning-emphasis"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Belum tersedia</div>
                    <div class="small"><?= esc($ref['error'] ?? '') ?>. Isi website tetap diperiksa.</div>
                <?php endif ?>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <div class="small text-secondary">Diperiksa</div>
                <div class="fw-semibold"><?= esc(date('d/m/Y H:i', $scan['at'])) ?></div>
                <div class="small"><?= $fmtAge($scan['at']) ?> · <?= esc((string) $scan['seconds']) ?> detik</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white fw-bold"><i class="bi bi-file-earmark-code" aria-hidden="true"></i> Berkas website (<?= count($scan['files']) ?>)</div>
        <?php if (! $scan['files']): ?>
            <div class="card-body text-success small"><i class="bi bi-check-circle" aria-hidden="true"></i> <?= $ref['ok'] ? 'Semua berkas sama dengan versi resmi dan tidak ada berkas skrip asing.' : 'Tidak ada berkas skrip asing yang ditemukan.' ?></div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>Status</th><th>Berkas</th><th>Keterangan</th><th class="text-end">Tindakan</th></tr></thead>
                <tbody>
                <?php foreach ($scan['files'] as $f): $info = $f['info'] ?? []; ?>
                    <tr>
                        <td class="text-nowrap"><?= $levelBadge($f['level']) ?> <span class="small"><?= $kindLabel[$f['kind']] ?? '' ?></span></td>
                        <td class="font-monospace small text-break"><?= esc($f['path']) ?></td>
                        <td class="small"><?= esc($f['message']) ?>
                            <?php if (! empty($info['mtime'])): ?><br><span class="text-secondary">Diubah <?= esc(date('d/m/Y H:i', $info['mtime'])) ?> · <?= number_format($info['size'] / 1024, 1, ',', '.') ?> KB</span><?php endif ?>
                            <?php if (! empty($info['judi'])): ?><br><strong class="text-danger">Memuat kata judi: “<?= esc($info['judi']) ?>”</strong><?php endif ?>
                            <?php if (! empty($info['code'])): ?><br><strong class="text-danger">Memuat kode mencurigakan: <code><?= esc($info['code']) ?></code></strong><?php endif ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if (in_array($f['kind'], ['changed', 'missing'], true) && $ref['ok']): ?>
                                <form action="<?= admin_url('keamanan/pulihkan') ?>" method="post" class="d-inline" data-confirm-ok="Ya, Pulihkan" data-confirm="Kembalikan <?= esc($f['path'], 'attr') ?> ke isi resmi dari GitHub? Salinan berkas sekarang disimpan di writable/backups/."><?= csrf_field() ?>
                                    <input type="hidden" name="path" value="<?= esc($f['path'], 'attr') ?>"><button class="btn btn-sm btn-pa-primary"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Pulihkan</button></form>
                            <?php elseif ($f['kind'] === 'unknown' || ($f['kind'] === 'suspect' && $f['level'] === 'danger')): ?>
                                <form action="<?= admin_url('keamanan/karantina') ?>" method="post" class="d-inline" data-confirm-ok="Ya, Karantina" data-confirm="Pindahkan <?= esc($f['path'], 'attr') ?> ke karantina (writable/karantina)? Berkas tidak dihapus, tetapi tidak bisa dijalankan lagi dari web."><?= csrf_field() ?>
                                    <input type="hidden" name="path" value="<?= esc($f['path'], 'attr') ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-shield-x" aria-hidden="true"></i> Karantina</button></form>
                            <?php endif ?>
                            <form action="<?= admin_url('keamanan/aman') ?>" method="post" class="d-inline" data-confirm-ok="Ya, Tandai Aman" data-confirm="Tandai <?= esc($f['path'], 'attr') ?> aman? Temuan ini tidak ditampilkan lagi sampai isinya berubah."><?= csrf_field() ?>
                                <input type="hidden" name="key" value="<?= esc($f['key'], 'attr') ?>"><input type="hidden" name="label" value="<?= esc($f['path'], 'attr') ?>">
                                <button class="btn btn-sm btn-outline-secondary">Aman</button></form>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table></div>
        <?php endif ?>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white fw-bold"><i class="bi bi-file-text" aria-hidden="true"></i> Isi website (<?= count($scan['content']) ?>)</div>
        <?php if (! $scan['content']): ?>
            <div class="card-body text-success small"><i class="bi bi-check-circle" aria-hidden="true"></i> Tidak ditemukan kata kunci judi online, tautan tersembunyi, atau kode sisipan pada halaman, berita, menu, beranda, dan pengaturan.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead><tr><th>Status</th><th>Lokasi</th><th>Temuan</th><th class="text-end">Tindakan</th></tr></thead>
                <tbody>
                <?php foreach ($scan['content'] as $f): ?>
                    <tr>
                        <td><?= $levelBadge($f['level']) ?></td>
                        <td class="small text-nowrap"><?= esc($f['path']) ?></td>
                        <td class="small"><strong><?= esc($f['message']) ?></strong><br><span class="text-secondary"><?= esc($f['snippet']) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= admin_url($f['link']) ?>" class="btn btn-sm btn-pa-primary"><i class="bi bi-pencil" aria-hidden="true"></i> Buka</a>
                            <form action="<?= admin_url('keamanan/aman') ?>" method="post" class="d-inline" data-confirm-ok="Ya, Tandai Aman" data-confirm="Tandai temuan ini aman (mis. berita sosialisasi bahaya judi online)?"><?= csrf_field() ?>
                                <input type="hidden" name="key" value="<?= esc($f['key'], 'attr') ?>"><input type="hidden" name="label" value="<?= esc($f['path'] . ' — ' . $f['message'], 'attr') ?>">
                                <button class="btn btn-sm btn-outline-secondary">Aman</button></form>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table></div>
        <?php endif ?>
    </div>
    <?php if ($scan['ignored']): ?>
        <form action="<?= admin_url('keamanan/aman') ?>" method="post" class="mb-3"><?= csrf_field() ?><input type="hidden" name="all" value="1">
            <button class="btn btn-link btn-sm p-0">Tampilkan lagi <?= $scan['ignored'] ?> temuan yang ditandai aman</button></form>
    <?php endif ?>
<?php endif ?>

<div class="alert alert-light border small">
    <strong>Cara membaca:</strong>
    <ul class="mb-0">
        <li><strong>Diubah</strong>: isi berkas berbeda dengan versi resmi. Bila Anda tidak sengaja mengubahnya, klik <strong>Pulihkan</strong>. Berkas PHP/JS/.htaccess yang diubah tanpa sepengetahuan Anda adalah tanda website disusupi.</li>
        <li><strong>Berkas asing</strong>: berkas skrip yang bukan bagian dari website (mis. <code>wp-login.php</code>, <code>shell.php</code>, <code>.htaccess</code> tambahan). Klik <strong>Karantina</strong> untuk menonaktifkannya tanpa menghapus.</li>
        <li><strong>Isi website</strong>: kata kunci judi online atau tautan tersembunyi pada halaman/berita/menu. Buka lalu hapus bagian tersebut. Berita yang memang membahas judi (mis. sosialisasi bahaya judi online) tandai <strong>Aman</strong>.</li>
        <li>Bila ditemukan sisipan: ganti password semua admin dan password hosting/FTP, lalu laporkan ke pengelola TI.</li>
    </ul>
</div>
<?= $this->endSection() ?>
