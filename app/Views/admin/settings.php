<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h1 class="h3 font-display fw-bold text-pa-primary mb-3">Pengaturan Situs</h1>
<nav aria-label="Kelompok pengaturan">
    <ul class="nav nav-tabs mb-0">
        <?php foreach ($groups as $k => [$label]): if (in_array($k, ['keamanan', 'sipp'], true) && ! can([], true)) continue; ?>
            <li class="nav-item"><a class="nav-link<?= $tab === $k ? ' active' : '' ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?> href="<?= admin_url('pengaturan?tab=' . $k) ?>"><?= esc($label) ?></a></li>
        <?php endforeach ?>
    </ul>
</nav>
<?php if (isset($groups[$tab])): [$label, $fields] = $groups[$tab]; ?>
<form action="<?= admin_url('pengaturan') ?>" method="post" enctype="multipart/form-data" class="card p-4 border-top-0 rounded-top-0" novalidate>
    <?= csrf_field() ?><input type="hidden" name="_tab" value="<?= esc($tab, 'attr') ?>">
    <?php if ($tab === 'sipp' && ! empty($sipp)): ?>
        <div class="alert alert-light border small">
            <p class="mb-2"><i class="bi bi-diagram-3 text-pa-primary" aria-hidden="true"></i> Aplikasi <strong>SIPP API</strong> di server kantor membaca database SIPP lalu <strong>mengirim</strong> jadwal sidang, statistik perkara, dan panggilan ghaib ke website ini secara terjadwal. Isi <em>Alamat website satker</em> di dashboard kantor dengan alamat utama website ini; alamat penerimanya:</p>
            <code class="d-inline-block user-select-all mb-2"><?= esc($sipp['url']) ?></code>
            <p class="mb-0">Kunci: <?= $sipp['hasKey']
                ? '<span class="badge text-bg-success">tersimpan</span> ' . esc($sipp['keyHint']) . ($sipp['from'] === 'env' ? ' (dari berkas .env)' : '')
                : '<span class="badge text-bg-warning">belum ada</span> dibuat otomatis saat mengunduh aplikasi di bawah, atau tempel teks dari tombol Salin di dashboard kantor.' ?></p>
        </div>
        <div class="border rounded-3 p-3 mb-3 bg-white">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <i class="bi bi-file-earmark-zip fs-2 text-pa-primary" aria-hidden="true"></i>
                <div class="me-auto" style="flex:1;min-width:16rem">
                    <strong>Aplikasi SIPP API Kantor</strong> — dipasang di server kantor tempat SIPP berada (PHP 5.2–8.x).
                    Paket unduhan <strong>sudah berisi alamat &amp; kunci website ini</strong> (dan mengaktifkan Integrasi SIPP), jadi tidak perlu menyalin kunci:
                    <ol class="small mb-0 mt-1">
                        <li>Ekstrak, salin folder <code>sipp-api-kantor</code> ke web root server kantor (mis. <code>C:\xampp\htdocs\</code>).</li>
                        <li>Buka <code>http://IP-SERVER/sipp-api-kantor/</code>, buat password dashboard.</li>
                        <li>Klik <strong>Kirim Semua Data Sekarang</strong>, lalu jadwalkan pengiriman otomatis (Task Scheduler / cron, atau menu <strong>Mode Otomatis</strong>). Hanya data yang berubah yang dikirim. Data yang diterima tampil di tabel <em>Kiriman terakhir</em> di bawah.</li>
                    </ol>
                </div>
                <a href="<?= admin_url('pengaturan/sipp-kantor') ?>" class="btn btn-pa-primary" download data-reload-after="2500"><i class="bi bi-download" aria-hidden="true"></i> Unduh SIPP API Kantor</a>
            </div>
            <p class="small text-danger mb-0 mt-2"><i class="bi bi-shield-lock" aria-hidden="true"></i> Paket berisi kunci rahasia website: simpan hanya di server kantor, jangan dibagikan. Bila bocor, tempel kunci baru dari dashboard kantor di bawah.</p>
        </div>
    <?php endif ?>
    <?php if ($tab === 'identitas'): ?><div class="alert alert-light border small"><i class="bi bi-palette text-pa-primary" aria-hidden="true"></i> Warna resmi Peradilan Agama (<code>#056e4f</code> & <code>#ff9900</code>) dikunci sesuai SK Bab III huruf B.1.</div><?php endif ?>
    <div class="row">
        <?php foreach ($fields as $key => $def): [$lbl, $type, $help] = $def;
            $f = [$lbl, $type, '', 'help' => $help];
            if ($type === 'select') { $f['options'] = $def[3]; }
            if ($key === 'lhkpn_jabatan') { $f['rows'] = 12; } ?>
            <div class="<?= in_array($type, ['textarea', 'image'], true) ? 'col-12' : 'col-md-6' ?>">
                <?= view('admin/_field', ['name' => $key, 'f' => $f, 'value' => $values[$key] ?? '', 'errors' => [], 'lookups' => []]) ?>
            </div>
        <?php endforeach ?>
    </div>
    <div><button class="btn btn-pa-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan Pengaturan</button></div>
</form>
<?php if ($tab === 'sipp' && ! empty($sipp)): ?>
<div class="card p-4 mt-3">
    <h2 class="h6 fw-bold mb-2"><i class="bi bi-inbox text-pa-primary" aria-hidden="true"></i> Kiriman terakhir dari SIPP API kantor</h2>
    <?php $names = ['jadwal_sidang' => 'Jadwal Sidang', 'statistik_perkara' => 'Statistik Perkara', 'panggilan_ghaib' => 'Panggilan Ghaib', 'antrian' => 'Antrian']; ?>
    <div class="table-responsive"><table class="table table-sm align-middle small mb-2">
        <thead><tr><th scope="col">Data</th><th scope="col">Diterima</th><th scope="col">Jumlah</th><th scope="col">Sumber</th><th scope="col">JSON</th></tr></thead>
        <tbody>
        <?php foreach ($names as $k => $n): $r = $sipp['status'][$k] ?? null; $old = $r && (time() - strtotime((string) $r['diterima_pada'])) > 86400; ?>
            <tr><td><?= esc($n) ?></td>
                <td><?= $r ? '<span class="' . ($old ? 'text-danger' : 'text-success') . '">' . esc(date('d-m-Y H:i', strtotime((string) $r['diterima_pada']))) . '</span>' . ($old ? ' <span class="text-danger">(lebih dari 1 hari)</span>' : '') : '<span class="text-secondary">belum pernah</span>' ?></td>
                <td><?= $r ? number_format((int) $r['jumlah'], 0, ',', '.') : '–' ?></td>
                <td><?= $r ? esc(['sipp_internal' => 'SIPP kantor', 'sipp_publik' => 'SIPP publik (cadangan)'][$r['sumber']] ?? $r['sumber']) : '–' ?></td>
                <td><?= $r ? '<a href="' . site_url('api/sipp/' . $k) . '" target="_blank" rel="noopener">lihat</a>' : '–' ?></td></tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
    <p class="small text-secondary mb-0">Belum ada kiriman? Di dashboard SIPP API kantor klik <strong>Tes koneksi website</strong> lalu <strong>Kirim Semua Data Sekarang</strong>. Bila muncul "Timestamp tidak valid", samakan jam server kantor.</p>
</div>
<?php endif ?>
<?php if ($tab === 'tautan' && ! empty($feed)): $st = $feed['status']; ?>
<div class="card p-4 mt-3">
    <h2 class="h6 fw-bold mb-2"><i class="bi bi-broadcast text-pa-primary" aria-hidden="true"></i> Arsip Pengumuman Badilag &amp; MA</h2>
    <p class="small text-secondary mb-2">Setiap jam hanya pengumuman yang belum ada yang diambil; yang sudah tersimpan dilewati. Pengumuman lama diambil bertahap mundur sampai tahun <?= (int) $st['since'] ?>. Terakhir diperiksa: <?= $st['at'] ? esc(date('d-m-Y H:i', $st['at'])) : 'belum pernah' ?>.</p>
    <ul class="small mb-3">
        <?php foreach ($feed['sources'] as $k => [$label, $url]): $s = $st['state'][$k] ?? []; ?>
            <li><strong><?= esc($label) ?></strong> — <?= number_format((int) ($st['counts'][$k] ?? 0), 0, ',', '.') ?> pengumuman tersimpan
                <?php if (! empty($s['feed'])): ?>· <?= ! empty($s['done']) ? 'arsip lama lengkap' : 'arsip lama sedang diambil bertahap' ?><?php endif ?>
                <?php if (! empty($st['errors'][$k])): ?><span class="text-danger">· gagal diambil terakhir kali: <?= esc($st['errors'][$k]) ?><?= str_contains($st['errors'][$k], '403') ? ' (situs sumber menolak pengambilan otomatis)' : '' ?></span><?php endif ?>
                <br><a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener" class="text-secondary"><?= esc($url) ?></a></li>
        <?php endforeach ?>
        <?php if (! $feed['sources']): ?><li class="text-secondary">Semua sumber dimatikan.</li><?php endif ?>
    </ul>
    <form action="<?= admin_url('pengaturan/pengumuman-segarkan') ?>" method="post" class="d-flex flex-wrap gap-2 align-items-center">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Ambil sekarang</button>
        <a href="<?= site_url('pengumuman-badilag-ma') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link">Lihat halaman publik</a>
    </form>
</div>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
