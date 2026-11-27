<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 font-display fw-bold text-pa-primary mb-0">Dashboard Kepatuhan SINOVA</h1>
        <p class="text-secondary small mb-0">Pemeriksaan otomatis kriteria Lampiran II SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 · <?= is_pta() ? 'Tingkat Banding' : 'Tingkat Pertama' ?></p>
    </div>
    <form action="<?= admin_url('cache/bersihkan') ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Bersihkan Cache</button></form>
</div>

<?php if (! empty($security) && ($security['summary']['danger'] + $security['summary']['warn']) > 0): $sd = $security['summary']; ?>
    <div class="alert alert-<?= $sd['danger'] ? 'danger' : 'warning' ?> d-flex flex-wrap align-items-center gap-3" role="alert">
        <i class="bi bi-shield-exclamation fs-4" aria-hidden="true"></i>
        <div class="me-auto"><strong>Pemeriksaan Keamanan: <?= $sd['danger'] + $sd['warn'] ?> temuan</strong>
            (<?= $sd['danger'] ?> bahaya, <?= $sd['warn'] ?> perlu diperiksa) — mis. berkas yang diubah, berkas skrip asing, atau sisipan judi online.</div>
        <a href="<?= admin_url('keamanan') ?>" class="btn btn-<?= $sd['danger'] ? 'danger' : 'warning' ?> fw-semibold"><i class="bi bi-shield-check" aria-hidden="true"></i> Lihat temuan</a>
    </div>
<?php endif ?>
<?php if (! empty($health)): $hd = count(array_filter($health, static fn ($h) => $h['level'] === 'danger')); ?>
    <div class="card border-<?= $hd ? 'danger' : 'warning' ?> mb-3" id="kesehatan">
        <div class="card-header bg-white fw-bold"><i class="bi bi-heart-pulse text-<?= $hd ? 'danger' : 'warning' ?>" aria-hidden="true"></i> Kesehatan Website — <?= count($health) ?> hal perlu diperhatikan</div>
        <ul class="list-group list-group-flush">
            <?php foreach ($health as $h): ?>
                <li class="list-group-item d-flex flex-wrap gap-2 align-items-start">
                    <span class="badge <?= $h['level'] === 'danger' ? 'text-bg-danger' : 'text-bg-warning' ?> mt-1"><?= $h['level'] === 'danger' ? 'Penting' : 'Saran' ?></span>
                    <div class="me-auto" style="flex:1;min-width:16rem"><strong><?= esc($h['title']) ?></strong><br><span class="small text-secondary"><?= esc($h['advice']) ?></span></div>
                    <?php if ($h['link']): ?><a href="<?= admin_url($h['link']) ?>" class="btn btn-sm btn-outline-secondary">Buka</a><?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>
<?php if (! empty($update) && ! empty($update['available'])): ?>
    <div class="alert alert-info" role="alert" id="pembaruan">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <i class="bi bi-arrow-up-circle fs-4" aria-hidden="true"></i>
            <div class="me-auto" style="min-width:16rem;flex:1">
                <strong>Versi baru website tersedia.</strong>
                <div class="d-flex flex-wrap align-items-center gap-2 my-1">
                    <span class="badge text-bg-light border fs-6 fw-semibold">Terpasang: <?= esc(\App\Libraries\VersionCheck::label((int) $update['local'], $update['localVersion'] ?? '', $update['localName'] ?? '')) ?></span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i><span class="visually-hidden">diperbarui ke</span>
                    <span class="badge text-bg-primary fs-6 fw-semibold">Baru: <?= esc(\App\Libraries\VersionCheck::label((int) $update['latest'], $update['version'], $update['name'] ?? '')) ?></span>
                </div>
                Klik tombol <strong>Update</strong>: sistem mengunduh <strong>hanya berkas yang berubah</strong> dari GitHub, mencadangkan database dan berkas lama, lalu menjalankan pembaruan database.
                Data Anda (berita, pengaturan, unggahan, database) tidak terpengaruh.
            </div>
            <form action="<?= admin_url('pembaruan-aplikasi') ?>" method="post" data-loading="Memperbarui… jangan tutup halaman"
                  data-confirm="Perbarui website dari Versi <?= esc($update['localName'] ?: 'build ' . $update['local'], 'attr') ?> ke Versi <?= esc($update['name'] ?: 'build ' . $update['latest'], 'attr') ?> sekarang? <?= count($update['files']) ?> berkas akan diunduh dari GitHub dan menimpa berkas lama (cadangan otomatis disimpan di writable/backups/). Cadangan database dibuat otomatis lebih dulu.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-cloud-download" aria-hidden="true"></i> Update ke Versi <?= esc($update['name'] ?: (string) $update['latest']) ?></button>
            </form>
            <a href="<?= esc($update['url'], 'attr') ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm"><i class="bi bi-github" aria-hidden="true"></i> GitHub</a>
        </div>
        <?php if (! empty($update['releases'])): ?>
            <div class="small mt-2 mb-1"><strong>Yang baru:</strong>
                <ul class="mb-1"><?php foreach ($update['releases'] as $r): ?><li><strong><?= esc(\App\Libraries\VersionCheck::label($r['build'], $r['version'], $r['name'] ?? '')) ?>:</strong> <?= esc($r['notes']) ?></li><?php endforeach ?></ul>
            </div>
        <?php endif ?>
        <?php if (! empty($update['files'])): ?>
            <details class="small">
                <summary><strong>Berkas yang akan diperbarui (<?= count($update['files']) ?>)</strong></summary>
                <ul class="mt-2 mb-2 font-monospace" style="columns:2;max-width:46rem"><?php foreach ($update['files'] as $f): ?><li><?= esc($f) ?></li><?php endforeach ?></ul>
                <p class="mb-0 text-secondary">Bila pembaruan otomatis tidak bisa dijalankan (mis. hosting tidak mengizinkan PHP menulis berkas), timpa berkas di atas secara manual dari GitHub. Jangan timpa <code>.env</code>, folder <code>writable/</code>, dan <code>public/uploads/</code>. Rincian di menu <a href="<?= admin_url('panduan') ?>#pemasangan">Panduan</a>.</p>
            </details>
        <?php endif ?>
    </div>
<?php elseif (! empty($update)): ?>
    <div class="small text-secondary mb-3" id="pembaruan">
        <i class="bi bi-<?= empty($update['error']) ? 'check-circle text-success' : 'wifi-off' ?>" aria-hidden="true"></i>
        <strong class="text-body"><?= esc(\App\Libraries\VersionCheck::label((int) $update['local'], $update['localVersion'] ?? '', $update['localName'] ?? '')) ?></strong>
        <?php if (! empty($update['error']) && ! empty($update['repo'])): ?>
            · pemeriksaan versi baru belum berhasil (<?= esc($update['error']) ?>), akan dicoba lagi otomatis.
        <?php elseif (! empty($update['repo'])): ?>
            · sudah versi terbaru<?= ! empty($update['checkedAt']) ? ' (diperiksa ' . esc(date('d/m/Y H:i', (int) $update['checkedAt'])) . ')' : '' ?>.
        <?php endif ?>
        <?php if (! empty($update['history'])): ?>
            <details class="d-inline-block ms-1"><summary class="d-inline">Riwayat versi</summary>
                <ul class="mt-2 mb-0"><?php foreach ($update['history'] as $r): ?><li><strong><?= esc(\App\Libraries\VersionCheck::label($r['build'], $r['version'], $r['name'] ?? '')) ?>:</strong> <?= esc($r['notes']) ?></li><?php endforeach ?></ul>
            </details>
        <?php endif ?>
    </div>
<?php endif ?>
<?php if (! empty($lastUpdate)):
    $vcJson = (new \App\Libraries\VersionCheck())->local();
    $vName  = static fn (int $b): string => \App\Libraries\VersionCheck::nameOf($vcJson, $b) ?: (\App\Libraries\VersionCheck::nameOf(\App\Libraries\VersionCheck::cachedLatest()['data'], $b) ?: 'build ' . $b); ?>
    <div class="alert alert-light border d-flex flex-wrap align-items-center gap-3 small" role="status">
        <i class="bi bi-clock-history fs-5" aria-hidden="true"></i>
        <div class="me-auto">Website diperbarui otomatis dari Versi <?= esc($vName((int) $lastUpdate['from'])) ?> ke Versi <?= esc($vName((int) $lastUpdate['to'])) ?> (<?= esc(date('d/m/Y H:i', strtotime($lastUpdate['at']))) ?>). Bila ada yang tidak berjalan semestinya, pembaruan dapat dibatalkan.</div>
        <form action="<?= admin_url('pembaruan-aplikasi/batalkan') ?>" method="post" data-loading="Mengembalikan…"
              data-confirm="Kembalikan berkas website ke Versi <?= esc($vName((int) $lastUpdate['from']), 'attr') ?>? Berkas dari pembaruan terakhir akan diganti dengan cadangannya.">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Batalkan pembaruan terakhir</button>
        </form>
    </div>
<?php endif ?>
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
            <?php if (can([])): ?><a href="<?= admin_url('statistik-pengunjung') ?>" class="mt-auto pt-2">Rincian &amp; asal pengunjung &rarr;</a><?php endif ?>
        </div>
    </div>
</div>

<?php
// Tombol "Lengkapi/Kelola" hanya untuk bagian yang boleh dikelola pengguna ini (operator tidak diarahkan ke halaman 404).
$adminRes    = config('AdminResources')->resources;
$pageRoles   = array_map(static fn ($s) => $s['roles'] ?? [], config('SiteStructure')->sections) + ['informasi' => []];
$pageSection = array_column(db_connect()->table('pages')->select('id, section')->get()->getResultArray(), 'section', 'id');
?>
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
                            $fix = ! can([]) ? null : admin_url('pengaturan?tab=' . (str_contains($r['check'], 'ikm') ? 'survei' : (str_contains($r['check'], 'address') ? 'kontak' : 'tautan')));
                        } elseif (str_starts_with($r['check'], 'docs:')) {
                            $fix = can($adminRes['dokumen']['roles'] ?? []) ? admin_url('data/dokumen') : null;
                        } elseif (str_starts_with($r['check'], 'table:')) {
                            $map = ['hearings' => 'jadwal-sidang', 'case_stats' => 'statistik-perkara', 'ghaib' => 'panggilan-ghaib', 'lhkpn' => 'lhkpn', 'info_requests' => 'akses-informasi', 'complaints' => 'pengaduan', 'jurisdictions' => 'wilayah', 'employees' => 'pegawai'];
                            $t   = explode(':', $r['check'])[1];
                            $fix = isset($map[$t]) && can($adminRes[$map[$t]]['roles'] ?? []) ? admin_url('data/' . $map[$t]) : null;
                        } elseif ($r['page_id'] && can($pageRoles[$pageSection[$r['page_id']] ?? ''] ?? ['__none__'])) {
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
