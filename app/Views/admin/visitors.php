<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h1 class="h3 font-display fw-bold text-pa-primary mb-1">Statistik Pengunjung</h1>
<p class="small text-secondary mb-3">Rincian lengkap hanya terlihat di panel admin. Di halaman publik tampil ringkasan<?= setting('visitor_detail_public', '1') === '1' ? ' serta jenis perangkat, browser & sistem operasi' : '' ?>.
    <a href="<?= admin_url('pengaturan?tab=keamanan') ?>">Atur</a> · <a href="<?= site_url('layanan-publik/statistik-pengunjung') ?>" target="_blank" rel="noopener">Lihat halaman publik</a></p>
<div class="row g-3 row-cols-2 row-cols-md-4 mb-3">
    <?php foreach (['today' => 'Hari Ini', 'month' => 'Bulan Ini', 'year' => 'Tahun Ini', 'total' => 'Total'] as $k => $l): ?>
        <div class="col"><div class="card p-3 text-center h-100"><div class="small text-secondary">Pengunjung <?= $l ?></div><div class="h3 fw-bold text-pa-primary mb-0"><?= number_format($summary[$k] ?? 0, 0, ',', '.') ?></div></div></div>
    <?php endforeach ?>
</div>
<div class="card p-4 mb-3">
    <?php $d = $details; ?>
    <?php if ($d['days'] === 0): ?>
        <h2 class="h6 fw-bold">Rincian pengunjung</h2>
        <p class="text-secondary mb-0">Statistik rinci tidak dicatat. Aktifkan di <a href="<?= admin_url('pengaturan?tab=keamanan') ?>">Pengaturan → Keamanan</a>.</p>
    <?php else: ?>
        <h2 class="h6 fw-bold mb-1">Rincian Pengunjung <?= $d['days'] === 1 ? 'Hari Ini' : $d['days'] . ' Hari Terakhir' ?></h2>
        <?= view('partials/visitor_details', ['d' => $d, 'kinds' => ['provinsi', 'kota', 'perangkat', 'browser', 'os'], 'limit' => 15]) ?>
    <?php endif ?>
</div>
<div class="card p-4">
    <h2 class="h6 fw-bold">Kunjungan 12 Bulan Terakhir</h2>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th scope="col">Bulan</th><th scope="col" class="text-end">Pengunjung unik harian</th><th scope="col" class="text-end">Halaman dilihat</th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($monthly, true) as $ym => $m): ?>
            <tr><td><?= bulan_id((int) substr($ym, 5, 2)) ?> <?= substr($ym, 0, 4) ?></td><td class="text-end"><?= number_format($m['visitors'], 0, ',', '.') ?></td><td class="text-end"><?= number_format($m['pageviews'], 0, ',', '.') ?></td></tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
</div>
<?= $this->endSection() ?>
