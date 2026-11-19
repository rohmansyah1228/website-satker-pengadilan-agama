<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 font-display fw-bold text-pa-primary mb-0">Dashboard Kepatuhan SINOVA</h1>
        <p class="text-secondary small mb-0">Pemeriksaan otomatis kriteria Lampiran II SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 · <?= is_pta() ? 'Tingkat Banding' : 'Tingkat Pertama' ?></p>
    </div>
    <form action="<?= admin_url('cache/bersihkan') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Bersihkan Cache</button></form>
</div>

<?php if (! empty($pendingMigrations)): ?>
    <div class="alert alert-warning d-flex flex-wrap align-items-center gap-3" role="alert">
        <i class="bi bi-database-up fs-4" aria-hidden="true"></i>
        <div class="me-auto"><strong>Ada <?= count($pendingMigrations) ?> pembaruan database</strong> dari versi aplikasi terbaru (<?= esc(implode(', ', $pendingMigrations)) ?>).<br>
            <span class="small">Cadangkan database lebih dulu (phpMyAdmin → Export), lalu jalankan pembaruan.</span></div>
        <form action="<?= admin_url('pembaruan-database') ?>" method="post" data-confirm="Jalankan <?= count($pendingMigrations) ?> pembaruan database sekarang? Pastikan database sudah dicadangkan."><?= csrf_field() ?><button class="btn btn-warning fw-semibold"><i class="bi bi-play-fill" aria-hidden="true"></i> Jalankan pembaruan</button></form>
    </div>
<?php endif ?>
<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-3">
        <div class="card h-100 p-3 d-flex flex-row align-items-center gap-3">
            <div class="progress-ring" style="--p: <?= $summary['percent'] ?>" role="img" aria-label="Skor kelengkapan <?= $summary['percent'] ?> persen"><span><?= $summary['percent'] ?>%</span></div>
            <div class="small"><strong class="d-block">Kelengkapan konten</strong><span class="text-secondary">Perkiraan internal, bukan nilai resmi SINOVA.</span></div>
        </div>
    </div>
    <?php foreach (['ok' => ['Lengkap', 'bi-check-circle-fill'], 'kurang' => ['Perlu Perbaikan', 'bi-exclamation-circle-fill'], 'kosong' => ['Belum Ada', 'bi-x-circle-fill']] as $k => [$l, $ic]): ?>
        <div class="col-sm-4 col-md col-xl-2">
            <div class="card h-100 p-3"><i class="bi <?= $ic ?> fs-3 status-<?= $k ?>" aria-hidden="true"></i><div class="display-6 fw-bold"><?= $summary[$k] ?></div><div class="small text-secondary"><?= $l ?> (nilai <?= ['ok' => 10, 'kurang' => 5, 'kosong' => 0][$k] ?>)</div></div>
        </div>
    <?php endforeach ?>
    <div class="col-md col-xl-3">
        <div class="card h-100 p-3 small">
            <strong class="mb-2"><i class="bi bi-graph-up text-pa-primary" aria-hidden="true"></i> Pengunjung</strong>
            <div class="d-flex justify-content-between"><span>Hari ini</span><strong><?= number_format($visitors['today'], 0, ',', '.') ?></strong></div>
            <div class="d-flex justify-content-between"><span>Bulan ini</span><strong><?= number_format($visitors['month'], 0, ',', '.') ?></strong></div>
            <div class="d-flex justify-content-between"><span>Total</span><strong><?= number_format($visitors['total'], 0, ',', '.') ?></strong></div>
        </div>
    </div>
</div>

<?php foreach (['berkala' => 'A.1 Informasi Berkala (bobot 40%)', 'wajib' => 'A.2 Informasi Wajib (bobot 20%)'] as $grp => $label): ?>
<div class="card mb-4">
    <div class="card-header bg-white"><h2 class="h6 fw-bold mb-0"><?= $label ?></h2></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead><tr><th scope="col">Kode</th><th scope="col">Kriteria</th><th scope="col">Status</th><th scope="col">Catatan</th><th scope="col">URL bukti untuk SINOVA</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): if ($r['group'] !== $grp) continue; ?>
                <tr>
                    <td class="text-nowrap fw-semibold"><?= esc($r['code']) ?></td>
                    <td><?= esc($r['label']) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ['ok' => 'Lengkap', 'kurang' => 'Perlu perbaikan', 'kosong' => 'Belum ada'][$r['status']] ?></span></td>
                    <td class="text-secondary"><?= esc($r['note']) ?></td>
                    <td class="text-nowrap">
                        <?php if ($r['url']): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="<?= esc($r['url'], 'attr') ?>" title="<?= esc($r['url'], 'attr') ?>"><i class="bi bi-clipboard" aria-hidden="true"></i> Salin URL<span class="visually-hidden"> <?= esc($r['label']) ?></span></button>
                            <a href="<?= esc($r['url'], 'attr') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link">Buka</a>
                        <?php else: ?>–<?php endif ?>
                    </td>
                    <td class="text-nowrap">
                        <?php
                        $fix = null;
                        if (str_starts_with($r['check'], 'setting:')) {
                            $fix = admin_url('pengaturan?tab=' . (str_contains($r['check'], 'ikm') ? 'survei' : (str_contains($r['check'], 'address') ? 'kontak' : 'tautan')));
                        } elseif (str_starts_with($r['check'], 'docs:')) {
                            $fix = admin_url('data/dokumen');
                        } elseif (str_starts_with($r['check'], 'table:')) {
                            $map = ['hearings' => 'jadwal-sidang', 'case_stats' => 'statistik-perkara', 'ghaib' => 'panggilan-ghaib', 'lhkpn' => 'lhkpn', 'info_requests' => 'akses-informasi', 'complaints' => 'pengaduan', 'jurisdictions' => 'wilayah', 'employees' => 'pegawai'];
                            $t   = explode(':', $r['check'])[1];
                            $fix = isset($map[$t]) ? admin_url('data/' . $map[$t]) : null;
                        } elseif ($r['page_id']) {
                            $fix = admin_url('halaman/' . $r['page_id']);
                        }
                        ?>
                        <?php if ($fix && $r['status'] !== 'ok'): ?><a href="<?= $fix ?>" class="btn btn-sm btn-pa-primary">Lengkapi</a><?php elseif ($fix): ?><a href="<?= $fix ?>" class="btn btn-sm btn-outline-success">Kelola</a><?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach ?>

<div class="card mb-4">
    <div class="card-header bg-white"><h2 class="h6 fw-bold mb-0">Aspek Desain, UI/UX & Aksesibilitas (20%) dan Performa & Keamanan (20%)</h2></div>
    <div class="card-body small">
        <ul class="mb-0">
            <li><strong>Sudah tersedia di aplikasi:</strong> tampilan responsif, widget disabilitas (pembaca layar, kontras tinggi, perbesar teks), pencarian internal, menu hierarkis, MFA pada login admin, kompresi gambar WebP, aset lokal tanpa CDN.</li>
            <li><strong>Tanggung jawab server:</strong> sertifikat SSL valid + redirect HTTP→HTTPS, TLS ≥ 1.2, versi PHP & web server tidak EOL, WAF, database hanya <code>127.0.0.1</code>, port publik hanya 80/443, backup mingguan offsite. Lihat README bagian "Checklist Server".</li>
            <li>Periksa tautan rusak & kecepatan muat (&lt; 5 detik) secara berkala, misalnya dengan Lighthouse di Chrome DevTools.</li>
        </ul>
    </div>
</div>

<?php if (can([], true) && $recent): ?>
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between"><h2 class="h6 fw-bold mb-0">Aktivitas Terbaru</h2><a href="<?= admin_url('log') ?>" class="small">Semua</a></div>
    <ul class="list-group list-group-flush small">
        <?php foreach ($recent as $a): ?>
            <li class="list-group-item d-flex justify-content-between gap-2"><span><strong><?= esc($a['username']) ?></strong> <?= esc($a['action']) ?> — <?= esc($a['subject']) ?></span><span class="text-secondary text-nowrap"><?= esc($a['created_at']) ?></span></li>
        <?php endforeach ?>
    </ul>
</div>
<?php endif ?>
<?= $this->endSection() ?>
