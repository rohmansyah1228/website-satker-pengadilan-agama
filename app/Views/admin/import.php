<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$host = $siteUrl !== '' ? rtrim($siteUrl, '/') : 'https://pa-namasatker.go.id';
$opts = static function (string $sel) use ($categories) {
    $o = '';
    foreach ($categories as $k => $l) {
        $o .= '<option value="' . esc($k, 'attr') . '"' . ($k === $sel ? ' selected' : '') . '>' . esc($l) . '</option>';
    }

    return $o;
};
?>
<h1 class="h3 font-display fw-bold text-pa-primary mb-1">Impor Berita dari Website Lama</h1>
<p class="text-secondary mb-3">Pindahkan berita dari website lama berbasis Joomla beserta tanggal, penulis, jumlah dibaca, gambar, dan lampiran PDF. Impor dapat diulang kapan saja: berita yang sudah dipindah dilewati, jadi tidak ada data dobel.</p>

<?php if (! $ready): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Fitur ini memerlukan pembaruan database. Buka <a href="<?= admin_url() ?>">Dashboard</a> lalu klik <strong>Jalankan pembaruan</strong>.</div>
<?php endif ?>

<form id="importForm" class="row g-3" novalidate>
    <?= csrf_field() ?>
    <div class="col-xl-8">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 fw-bold">1. Website lama</h2>
            <label class="form-label" for="impSite">Alamat website lama</label>
            <input type="url" class="form-control" id="impSite" name="site" value="<?= esc($siteUrl) ?>" placeholder="https://pa-namasatker.go.id" required>
            <div class="form-text">Server website ini yang mengambil berita, jadi server harus terhubung ke internet. Gambar dan PDF hanya diunduh dari alamat ini.</div>

            <h2 class="h6 fw-bold mt-4">2. Kategori yang dipindah</h2>
            <div class="table-responsive"><table class="table table-sm align-middle mb-2" id="impRows">
                <thead><tr><th scope="col" style="width: 3rem">Ikut</th><th scope="col">Alamat halaman kategori di website lama</th><th scope="col" style="width: 13rem">Masuk ke kategori</th><th scope="col" style="width: 7rem">Jumlah</th><th scope="col" style="width: 3rem"><span class="visually-hidden">Hapus</span></th></tr></thead>
                <tbody>
                    <?php foreach ([['/berita/berita-terkini.html', 'berita', true], ['/berita/arsip-berita.html', 'pengumuman', false]] as [$path, $cat, $on]): ?>
                        <tr>
                            <td><input class="form-check-input" type="checkbox" data-imp="on" <?= $on ? 'checked' : '' ?> aria-label="Ikut diimpor"></td>
                            <td><input type="text" class="form-control form-control-sm" data-imp="url" value="<?= esc($host . $path) ?>" aria-label="Alamat halaman kategori"></td>
                            <td><select class="form-select form-select-sm" data-imp="cat" aria-label="Kategori tujuan"><?= $opts($cat) ?></select></td>
                            <td class="small" data-imp="count">–</td>
                            <td><button type="button" class="btn btn-sm btn-link text-danger" data-imp-del title="Hapus baris"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="visually-hidden">Hapus baris</span></button></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="impAddRow"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah kategori</button>
            <div class="form-text">Buka menu berita di website lama, lalu salin alamat halaman daftarnya, mis. <code>…/berita/berita-terkini.html</code>. Di website lama Badilag, <em>Arsip Berita</em> umumnya berisi surat/undangan dari Badilag &amp; MA, cocok masuk <strong>Pengumuman</strong>.</div>
        </div></div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6 fw-bold">3. Pilihan</h2>
            <label class="form-label" for="impSince">Hanya berita sejak tahun</label>
            <input type="number" class="form-control mb-1" id="impSince" name="since" min="2000" max="<?= date('Y') ?>" placeholder="Semua tahun">
            <div class="form-text mb-3">Kosongkan untuk memindahkan semua berita.</div>
            <label class="form-label" for="impStatus">Status setelah dipindah</label>
            <select class="form-select mb-3" id="impStatus" name="status">
                <option value="published">Langsung terbit</option>
                <option value="draft">Draft (diperiksa dulu)</option>
            </select>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="impImages" name="images" value="1" checked>
                <label class="form-check-label" for="impImages">Unduh gambar &amp; PDF ke website baru</label>
                <div class="form-text">Disarankan: gambar tetap tampil walau website lama dimatikan.</div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="impInsecure" name="insecure" value="1">
                <label class="form-check-label" for="impInsecure">Abaikan verifikasi sertifikat SSL</label>
                <div class="form-text">Centang hanya bila muncul galat sertifikat (umum di XAMPP Windows).</div>
            </div>
        </div></div>
        <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-success" id="impCheck" <?= $ready ? '' : 'disabled' ?>><i class="bi bi-search" aria-hidden="true"></i> Periksa jumlah berita</button>
            <button type="button" class="btn btn-pa-primary" id="impStart" <?= $ready ? '' : 'disabled' ?>><i class="bi bi-cloud-download" aria-hidden="true"></i> Mulai Impor</button>
            <button type="button" class="btn btn-outline-danger d-none" id="impStop"><i class="bi bi-stop-circle" aria-hidden="true"></i> Hentikan</button>
        </div>
        <p class="small text-secondary mt-2 mb-0"><?= number_format($imported, 0, ',', '.') ?> berita hasil impor saat ini.</p>
    </div>
</form>

<div class="card mt-3 d-none" id="impProgress"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
        <strong id="impPhase" role="status" aria-live="polite">Menyiapkan…</strong>
        <span class="small">
            <span class="badge text-bg-success" id="impOk">0 dipindah</span>
            <span class="badge text-bg-secondary" id="impSkip">0 dilewati</span>
            <span class="badge text-bg-danger" id="impErr">0 gagal</span>
        </span>
    </div>
    <div class="progress mb-3" role="progressbar" aria-label="Kemajuan impor" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" id="impBar" style="width: 0%"></div></div>
    <div class="table-responsive" style="max-height: 28rem"><table class="table table-sm small align-middle mb-0">
        <thead class="sticky-top bg-white"><tr><th scope="col">Hasil</th><th scope="col">Judul</th><th scope="col">Tanggal</th><th scope="col">Keterangan</th></tr></thead>
        <tbody id="impLog"></tbody>
    </table></div>
</div></div>

<div class="alert alert-light border mt-3 small mb-0">
    <i class="bi bi-signpost-split me-1" aria-hidden="true"></i> Setelah domain dipindah ke website baru, alamat berita lama (mis. <code>/berita/berita-terkini/3064-judul.html</code>) otomatis dialihkan ke berita yang sama di website ini, jadi tautan di Google dan media sosial tetap berfungsi.
</div>
<script src="<?= asset('assets/js/import.js') ?>" defer></script>
<?= $this->endSection() ?>
