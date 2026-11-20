<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$toc = [
    'pendahuluan' => ['1', 'Pendahuluan', 'bi-info-circle'],
    'pemasangan'  => ['2', 'Pemasangan & Hosting', 'bi-hdd-network'],
    'login'       => ['3', 'Masuk Panel Admin & MFA', 'bi-shield-lock'],
    'sinova'      => ['4', 'Dashboard SINOVA', 'bi-speedometer2'],
    'halaman'     => ['5', 'Halaman & Editor Blok', 'bi-file-earmark-richtext'],
    'menu'        => ['6', 'Menu Navigasi', 'bi-list-nested'],
    'beranda'     => ['7', 'Tampilan Beranda', 'bi-layout-text-window-reverse'],
    'berita'      => ['8', 'Berita & Informasi Serta Merta', 'bi-newspaper'],
    'slider'      => ['9', 'Slider Beranda', 'bi-images'],
    'pintasan'    => ['10', 'Pintasan Layanan', 'bi-grid-3x3-gap'],
    'video'       => ['11', 'Galeri Video', 'bi-camera-video'],
    'data'        => ['12', 'Dokumen & Data Layanan', 'bi-folder2-open'],
    'pengaturan'  => ['13', 'Pengaturan Situs', 'bi-gear'],
    'pengunjung'  => ['14', 'Fitur untuk Pengunjung', 'bi-universal-access'],
    'pengguna'    => ['15', 'Pengguna, Peran & Log', 'bi-people'],
    'perawatan'   => ['16', 'Perawatan & Keamanan', 'bi-tools'],
    'faq'         => ['17', 'Pertanyaan Umum', 'bi-question-circle'],
];
$img = static function (string $file, string $cap): string {
    $url = base_url('assets/img/panduan/' . $file);
    $dim = @getimagesize(FCPATH . 'assets/img/panduan/' . $file); // ukuran tetap → posisi bab tidak bergeser saat gambar dimuat
    $wh  = $dim ? ' width="' . $dim[0] . '" height="' . $dim[1] . '"' : '';

    return '<figure class="guide-fig"><a href="' . $url . '" target="_blank" rel="noopener" title="Buka gambar ukuran penuh"><img src="' . $url . '" alt="' . esc($cap, 'attr') . '"' . $wh . ' loading="lazy" decoding="async"></a><figcaption>' . esc($cap) . '</figcaption></figure>';
};
$go  = static fn (string $path, string $label) => '<a href="' . admin_url($path) . '" class="guide-go"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> ' . esc($label) . '</a>';
$tip = static fn (string $html, string $kind = 'tip') => '<div class="guide-note guide-' . $kind . '"><i class="bi ' . ($kind === 'warn' ? 'bi-exclamation-triangle' : 'bi-lightbulb') . '" aria-hidden="true"></i><div>' . $html . '</div></div>';
$site = esc(setting('site_name', 'Pengadilan Agama'));
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 guide-head">
    <div>
        <h1 class="h3 font-display fw-bold text-pa-primary mb-0"><i class="bi bi-book" aria-hidden="true"></i> Panduan Penggunaan</h1>
        <p class="text-secondary small mb-0">Manual book website <?= $site ?> — SK Dirjen Badilag Nomor 2722/DJA/SK.TI1/X/2026</p>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <input type="search" class="form-control form-control-sm" id="guideSearch" placeholder="Cari di panduan… (mis. popup, MFA, tabel)" aria-label="Cari di panduan" style="min-width: 17rem">
        <button type="button" class="btn btn-sm btn-outline-success" data-guide-print><i class="bi bi-printer" aria-hidden="true"></i> Cetak / PDF</button>
    </div>
</div>
<p class="small text-secondary d-none" id="guideNoResult" role="status">Tidak ada bagian yang cocok.</p>

<div class="guide-layout">
    <nav class="guide-toc d-print-none" aria-label="Daftar isi panduan">
        <strong class="d-block small text-uppercase text-secondary mb-2">Daftar Isi</strong>
        <ol class="list-unstyled mb-0">
            <?php foreach ($toc as $id => [$no, $t, $ic]): ?>
                <li><a href="#<?= $id ?>" data-toc="<?= $id ?>"><i class="bi <?= $ic ?>" aria-hidden="true"></i><span><?= $no ?>. <?= esc($t) ?></span></a></li>
            <?php endforeach ?>
        </ol>
    </nav>

    <div class="guide-body">

<section id="pendahuluan" class="guide-sec card">
    <h2>1. Pendahuluan</h2>
    <p>Website ini mengikuti <strong>SK Dirjen Badilag Nomor 2722/DJA/SK.TI1/X/2026</strong> tentang Standar Tata Kelola Website, dan dapat dipakai Pengadilan Agama mana pun. Nama satker, logo, alamat, dan tautan layanan diambil dari menu <em>Pengaturan</em>.</p>
    <p>Panduan ini untuk <strong>Super Admin</strong> (pemasangan, pengguna, keamanan) dan <strong>admin/operator konten</strong> (Humas, Kepaniteraan, Kesekretariatan). Tombol <span class="badge text-bg-light border"><i class="bi bi-book"></i> Panduan</span> di bagian atas setiap layar admin langsung membuka bab yang sesuai.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Bagian</th><th>Yang bisa dilakukan</th></tr></thead>
        <tbody>
            <tr><td>Halaman</td><td>Isi bebas: blok teks, tabel bertombol <em>Lihat</em>, gambar, sematan Google Drive/YouTube</td></tr>
            <tr><td>Menu Navigasi</td><td>Seret & lepas, sub-menu hingga 4 tingkat</td></tr>
            <tr><td>Tampilan Beranda</td><td>Susun blok: slider, pintasan, pengumuman, berita, survei, video, jam layanan</td></tr>
            <tr><td>Dashboard SINOVA</td><td>Pantau kelengkapan kriteria penilaian website secara otomatis</td></tr>
            <tr><td>Publikasi</td><td>Berita, Informasi Serta Merta, slider, pintasan, galeri video</td></tr>
            <tr><td>Data layanan</td><td>Arsip dokumen, jadwal sidang, panggilan ghaib, statistik, pegawai, LHKPN, dll.</td></tr>
            <tr><td>Pengunjung</td><td>Sarana disabilitas & Suara Panduan, popup pembuka, tombol CCTV & WhatsApp</td></tr>
        </tbody>
    </table></div>
    <?= $img('01-beranda.jpg', 'Beranda: menu utama, pita Informasi Serta Merta, slider, tombol CCTV (kiri atas), WhatsApp (kiri bawah), dan Disabilitas (kanan bawah).') ?>
</section>

<section id="pemasangan" class="guide-sec card">
    <h2>2. Pemasangan & Hosting</h2>
    <p>Website berjalan tanpa perintah terminal. <strong>Kebutuhan:</strong> PHP 8.1+ (disarankan 8.3), MySQL/MariaDB, Apache (mod_rewrite) atau Nginx, minimal 2 CPU & 2 GB RAM, HTTPS.</p>
    <h3>Di komputer (XAMPP)</h3>
    <ol>
        <li>Salin folder website ke <code>C:\xampp\htdocs\website-satker</code>.</li>
        <li>Jalankan <strong>Apache</strong> dan <strong>MySQL</strong> di XAMPP Control Panel.</li>
        <li>Buka <code>http://localhost/website-satker/</code> → otomatis ke halaman <strong>Pemasangan</strong>.</li>
        <li>Isi koneksi database, nama satker, dan akun Super Admin → <strong>Pasang</strong>. Halaman pemasangan tertutup otomatis setelahnya.</li>
    </ol>
    <h3>Di hosting (cPanel)</h3>
    <ol>
        <li>Unggah seluruh isi folder ke <code>public_html</code> (atau folder domain).</li>
        <li>Buat database & pengguna database di cPanel.</li>
        <li>Buka domain di browser → ikuti halaman Pemasangan.</li>
        <li>Aktifkan HTTPS. Untuk Nginx, pakai contoh <code>deploy/nginx-satker.conf</code>.</li>
    </ol>
    <h3>Pindah domain / dipakai PA lain</h3>
    <p>Alamat website terdeteksi otomatis (domain, IP, atau folder berganti tidak perlu diatur). Untuk PA lain cukup ganti <em>Pengaturan → Identitas Satker</em> dan <em>Alamat & Kontak</em>.</p>
    <h3>Pembaruan database</h3>
    <p>Setiap kali file website diperbarui, buka <strong>Dashboard SINOVA</strong>. Bila muncul kotak <strong>Pembaruan database tersedia</strong>, klik <strong>Jalankan pembaruan</strong> (Super Admin). Aman dijalankan ulang dan tidak menghapus data.</p>
    <?= $tip('Sebelum memperbarui website, cadangkan database serta folder <code>public/uploads</code> dan <code>writable/uploads</code>.', 'warn') ?>
</section>

<section id="login" class="guide-sec card">
    <h2>3. Masuk Panel Admin & MFA</h2>
    <ol>
        <li>Buka <code><?= esc(admin_url()) ?></code>.</li>
        <li>Isi <strong>Nama pengguna</strong> dan <strong>Kata sandi</strong> → <strong>Masuk</strong>. Setelah beberapa kali salah, login dikunci sementara.</li>
        <li><strong>Login pertama:</strong> aktifkan MFA. Pasang aplikasi authenticator (Google Authenticator, Microsoft Authenticator, Aegis), pindai kode QR, masukkan 6 digit kode.</li>
        <li><strong>Login berikutnya:</strong> setelah kata sandi, masukkan 6 digit kode dari aplikasi.</li>
    </ol>
    <div class="guide-grid2">
        <?= $img('03-login.jpg', 'Halaman Masuk Panel Admin.') ?>
        <?= $img('03-mfa.jpg', 'Verifikasi Dua Langkah: 6 digit kode dari aplikasi authenticator.') ?>
    </div>
    <h3>Bagian panel admin</h3>
    <ul>
        <li><strong>Menu samping:</strong> Konten, Publikasi, Dokumen, Kepaniteraan, Kesekretariatan, Layanan Publik, Sistem — menyesuaikan peran.</li>
        <li><strong>Baris atas:</strong> <strong>← Kembali</strong> (Alt + ←), <strong>Panduan</strong>, <strong>Lihat Situs</strong>, nama pengguna, <strong>Keluar</strong>.</li>
        <li><strong>Formulir:</strong> isian utama di kiri, pengaturan tampilan di panel kanan; tombol <strong>Simpan</strong>, <strong>Simpan &amp; Lihat</strong>, <strong>Simpan &amp; Tambah Lagi</strong>.</li>
    </ul>
    <?= $tip('Lupa kode MFA / HP hilang: Super Admin lain dapat <strong>Reset MFA</strong> di menu Pengguna. Bila semua Super Admin terkunci, pengelola server menjalankan <code>php spark satker:reset-mfa &lt;username&gt;</code>.') ?>
    <?= $go('akun', 'Buka Akun Saya (ganti kata sandi & MFA)') ?>
</section>

<section id="sinova" class="guide-sec card">
    <h2>4. Dashboard SINOVA</h2>
    <p>Halaman pertama setelah login. Setiap kriteria penilaian website (Lampiran II SK 2722/2026) diperiksa otomatis.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Status</th><th>Arti</th><th>Tindakan</th></tr></thead>
        <tbody>
            <tr><td><span class="badge badge-ok">Lengkap</span></td><td>Data/isi halaman sudah terbaca</td><td>Cek catatan jumlahnya</td></tr>
            <tr><td><span class="badge badge-kurang">Perlu perbaikan</span></td><td>Ada isi tetapi kurang (tahun/bulan belum ada, teks [LENGKAPI])</td><td>Baca kolom Catatan</td></tr>
            <tr><td><span class="badge badge-kosong">Belum ada</span></td><td>Belum ada data</td><td>Klik <strong>Lengkapi</strong></td></tr>
        </tbody>
    </table></div>
    <?= $img('04-dashboard.jpg', 'Dashboard SINOVA: status, catatan jumlah yang terbaca, tombol Salin URL, Buka, dan Lengkapi.') ?>
    <h3>Agar terbaca dengan benar</h3>
    <ul>
        <li>Data dihitung dari <strong>menu admin</strong> (Arsip Dokumen, LHKPN, dll.) dan <strong>isi halaman</strong> (tautan di tabel, teks, gambar, sematan).</li>
        <li>Tulis <strong>tahun</strong> di judul tabel atau baris, mis. <em>DIPA Tahun 2026</em>.</li>
        <li>Tulis <strong>bulan</strong> untuk laporan bulanan, mis. <em>Oktober 2026</em>.</li>
        <li>Isi kolom tombol tautan — baris dengan tautan kosong tidak dihitung.</li>
        <li>Pengaduan/hukuman disiplin yang kosong: tulis <strong>Nihil</strong> di halamannya.</li>
        <li>Hapus semua teks <strong>[LENGKAPI]</strong>.</li>
    </ul>
    <p><strong>Salin URL</strong> menyalin alamat bukti untuk diisikan ke aplikasi SINOVA Badilag. Persentase = Lengkap (nilai penuh) + Perlu perbaikan (setengah).</p>
    <?= $go('', 'Buka Dashboard SINOVA') ?>
</section>

<section id="halaman" class="guide-sec card">
    <h2>5. Halaman & Editor Blok</h2>
    <p>Setiap halaman (Visi Misi, DIPA, Jadwal Sidang, …) disusun dari <strong>blok</strong>. Buka <em>Konten → Halaman</em> → <strong>Ubah</strong> atau <strong>Halaman Baru</strong>.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Isian</th><th>Keterangan</th></tr></thead>
        <tbody>
            <tr><td>Judul</td><td>Nama halaman, juga di menu</td></tr>
            <tr><td>Bagian</td><td>Profil, Layanan Hukum, Transparansi, Layanan Publik</td></tr>
            <tr><td>Alamat (slug)</td><td>Bila diubah, alamat lama dialihkan otomatis (301). Halaman wajib SK dikunci.</td></tr>
            <tr><td>Modul data</td><td>Data otomatis: Daftar Dokumen, Pejabat & Pegawai, Jadwal Sidang, dll.</td></tr>
            <tr><td>Jenis dokumen</td><td>Untuk modul Daftar Dokumen</td></tr>
            <tr><td>Status / SEO / Gambar sampul</td><td>Terbit atau Draft; deskripsi mesin pencari; diagram/peta di atas isi</td></tr>
        </tbody>
    </table></div>
    <?= $img('05-halaman-blok.jpg', 'Ubah Halaman DIPA: kotak Data Arsip Dokumen di atas, isian halaman di panel kanan.') ?>
    <h3>Menambah & mengatur blok</h3>
    <ul>
        <li><strong>Tambah blok di atas</strong> → blok baru paling atas; <strong>Tambah blok di bawah</strong> → paling bawah.</li>
        <li>Kepala blok: <strong>↑ ↓</strong> pindah, <strong>+</strong> sisip di bawahnya, gandakan, ciutkan, hapus; atau seret ikon ⠿.</li>
    </ul>
    <h3>Blok Teks</h3>
    <p>Tab <strong>Editor</strong>, <strong>Kode HTML</strong>, <strong>Pratinjau</strong>. Tombol sisip: Gambar, Dokumen PDF, Tautan Halaman/Berita, Tabel, Video/Google Drive, Garis Pemisah. Saat membuat tautan, pilih cara membuka:</p>
    <ul>
        <li><em>Tampilkan di halaman ini (popup)</em> — terbuka di jendela dalam website + tombol Buka di tab baru;</li>
        <li><em>Tampilkan di halaman ini, tanpa tombol Buka di tab baru</em>;</li>
        <li><em>Pindah ke halaman tujuan</em>; <em>Buka di tab baru</em>.</li>
    </ul>
    <h3>Blok Tabel (untuk daftar dokumen Google Drive)</h3>
    <ol>
        <li>Isi <strong>Judul tabel</strong> beserta tahun (mis. <em>DIPA Tahun 2026</em>); centang <strong>Nomor urut otomatis</strong> bila perlu.</li>
        <li>Atur kolom: <strong>Teks</strong> atau <strong>Tombol tautan</strong> (label tombol, cara buka, tampil/tidak tombol Buka di tab baru).</li>
        <li><strong>+ Baris</strong>, <strong>+ Kolom</strong>, atau <strong>Tempel dari Excel</strong> untuk banyak baris sekaligus.</li>
    </ol>
    <h3>Blok Gambar & Sematan</h3>
    <p><strong>Gambar:</strong> ukuran (penuh/75%/50%/33%), perataan, teks alternatif (wajib), keterangan, tautan. <strong>Sematan:</strong> Google Drive/Docs/Sheets/Slides/Form, YouTube, PDF, Google Maps, atau situs lain — atur judul & tinggi, klik <strong>Pratinjau</strong>.</p>
    <?= $tip('Halaman bermodul data menampilkan kotak <strong>Data …</strong> di atas formulir: datanya sama dengan menu samping, cukup diisi sekali. Halaman Survei juga punya kotak <strong>Nilai Survei</strong> (IKM, IPKP, IPAK, periode, tautan website survei).') ?>
    <?= $go('halaman', 'Buka Halaman') ?>
</section>

<section id="menu" class="guide-sec card">
    <h2>6. Menu Navigasi</h2>
    <p>Atur menu atas website di <em>Konten → Menu Navigasi</em>. Susunan tersimpan otomatis.</p>
    <ol>
        <li>Pegang ikon ⠿ atau kotak menu, lalu seret.</li>
        <li>Lepas di <strong>atas/bawah</strong> menu lain → sebelum/sesudahnya.</li>
        <li>Lepas di <strong>tengah</strong> menu lain → jadi <strong>sub-menu</strong>.</li>
        <li>Tanda hijau = tempat jatuh; merah = tidak diizinkan (alasan ditampilkan).</li>
    </ol>
    <?= $img('06-menu-admin.jpg', 'Menu Navigasi: seret lewat ikon ⠿; menu wajib SK bertanda gembok.') ?>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Jenis menu</th><th>Kegunaan</th></tr></thead>
        <tbody>
            <tr><td>Halaman situs</td><td>Menuju halaman dari menu Halaman</td></tr>
            <tr><td>Alamat internal</td><td>Mis. <code>/berita/kategori/inovasi</code></td></tr>
            <tr><td>Tautan luar</td><td>Website lain (https://…), bisa tab baru</td></tr>
            <tr><td>Judul grup tanpa tautan</td><td>Pengelompok sub-menu, mis. <em>Laporan Satker</em></td></tr>
        </tbody>
    </table></div>
    <ul>
        <li>Maksimal <strong>4 tingkat</strong>.</li>
        <li><strong>Menu wajib SK</strong> (gembok) tidak bisa dihapus; boleh diurutkan/dipindah ke judul grup tetapi tetap di bawah menu utamanya. Enam menu utama tetap tingkat 1.</li>
        <li>Sembunyikan menu tanpa menghapus: matikan <strong>Tampilkan di situs</strong>.</li>
        <li>Jaga informasi utama (SIPP, jadwal sidang) tercapai dalam ≤ 2 klik (SK Bab III huruf C).</li>
    </ul>
    <?= $img('06-menu-publik.jpg', 'Menu tarik-turun di website.') ?>
    <?= $go('menu', 'Buka Menu Navigasi') ?>
</section>

<section id="beranda" class="guide-sec card">
    <h2>7. Tampilan Beranda</h2>
    <p>Beranda tersusun dari kotak <strong>tampilan</strong> pada grid 12 kolom di <em>Konten → Tampilan Beranda</em>.</p>
    <ul>
        <li><strong>Tambah Tampilan</strong> → pilih jenis → isi judul & pengaturan → Simpan.</li>
        <li><strong>Pindah:</strong> seret ikon panah empat arah (keyboard: tombol panah). <strong>Ubah ukuran:</strong> tarik sudut kanan bawah (Shift + panah).</li>
        <li>Daftar di bawah grid: Ubah, Duplikat, Tampil/Tersembunyi, Hapus. Atur warna latar & garis aksen per tampilan.</li>
    </ul>
    <?= $img('07-tampilan-beranda.jpg', 'Tampilan Beranda: kotak-kotak tampilan pada grid 12 kolom.') ?>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Jenis tampilan</th><th>Isi diambil dari</th></tr></thead>
        <tbody>
            <tr><td>Slider Utama</td><td>Publikasi → Slider Beranda</td></tr>
            <tr><td>Pintasan Layanan (per kelompok, 3 gaya)</td><td>Publikasi → Pintasan Layanan</td></tr>
            <tr><td>Galeri Video</td><td>Publikasi → Galeri Video</td></tr>
            <tr><td>Pengumuman & Panggilan Ghaib</td><td>Panggilan Ghaib + berita Pengumuman/Serta Merta</td></tr>
            <tr><td>Berita & Publikasi</td><td>Berita (bisa per kategori)</td></tr>
            <tr><td>Jadwal Sidang Hari Ini · Dokumen Terbaru · Statistik Perkara</td><td>Menu data terkait</td></tr>
            <tr><td>Indeks Survei Pelayanan</td><td>Halaman Survei / Pengaturan → Nilai Survei</td></tr>
            <tr><td>Jam Pelayanan & Sidang</td><td>Pengaturan → Alamat & Kontak</td></tr>
            <tr><td>Konten Bebas · Gambar/Banner · Kartu Info · Kotak Pencarian</td><td>Diisi langsung di tampilan</td></tr>
            <tr><td>Baris Kolom</td><td>Wadah beberapa tampilan berdampingan</td></tr>
        </tbody>
    </table></div>
    <?= $tip('Perubahan di panel admin langsung tampil di Beranda. Data Beranda disimpan sementara 60 detik dan dikosongkan otomatis setiap admin menyimpan.') ?>
    <?= $go('beranda', 'Buka Tampilan Beranda') ?>
</section>

<section id="berita" class="guide-sec card">
    <h2>8. Berita & Informasi Serta Merta</h2>
    <p><em>Publikasi → Berita & Publikasi → Tambah</em>: judul, ringkasan, isi, gambar utama + teks alternatif, penulis, tanggal terbit, kategori, status. Tanggal terbit di masa depan = terjadwal.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Kategori</th><th>Tampil di</th></tr></thead>
        <tbody>
            <tr><td>Berita Kegiatan, Inovasi Layanan, Opini, Infografis</td><td>Blok Berita Beranda & halaman Berita (9 per halaman)</td></tr>
            <tr><td>Pengumuman</td><td>Blok Pengumuman & Panggilan Ghaib</td></tr>
            <tr><td>Informasi Serta Merta</td><td>Pita kuning di atas semua halaman + blok Pengumuman</td></tr>
        </tbody>
    </table></div>
    <h3>Pita Informasi Serta Merta</h3>
    <ol>
        <li>Pilih kategori <strong>Informasi Serta Merta</strong> → muncul isian <strong>Tampilkan pita sampai</strong>.</li>
        <li>Isi waktu berakhir (mis. Sabtu 24.00). Pita tampil sejak terbit sampai waktu itu, lalu hilang otomatis.</li>
        <li>Kosong = tampil 7 hari sejak terbit. Ubah status ke <strong>Draft</strong> untuk menghentikan seketika.</li>
    </ol>
    <?= $img('08-berita-serta-merta.jpg', 'Berita kategori Informasi Serta Merta dengan isian Tampilkan pita sampai.') ?>
    <?= $tip('Berita sebaiknya berorientasi pada pelayanan & inovasi, bukan sekadar liputan seremonial (SK Bab III huruf D).') ?>
    <?= $go('data/berita', 'Buka Berita & Publikasi') ?>
</section>

<section id="slider" class="guide-sec card">
    <h2>9. Slider Beranda</h2>
    <p>Maksimal <strong>5 slide</strong>. Isi: gambar latar (disarankan 1600 × 700 px), label kecil, judul, deskripsi, tombol utama & kedua (teks + tautan), urutan, tampilkan. Tulisan di slide bergambar otomatis bergaris tepi hitam agar terbaca di gambar terang maupun gelap.</p>
    <?= $go('data/slider', 'Buka Slider Beranda') ?>
</section>

<section id="pintasan" class="guide-sec card">
    <h2>10. Pintasan Layanan</h2>
    <p>Kotak tautan cepat: SIPP, e-Court, SIWAS, Zona Integritas, aplikasi pendukung.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Isian</th><th>Keterangan</th></tr></thead>
        <tbody>
            <tr><td>Judul & Keterangan</td><td>Teks pada kartu</td></tr>
            <tr><td>Tautan</td><td>Alamat tujuan; bisa token <code>{sipp_url}</code> <code>{ecourt_url}</code> <code>{direktori_url}</code> <code>{siwas_url}</code> <code>{survey_url}</code> dari Pengaturan</td></tr>
            <tr><td>Gambar (opsional)</td><td>Logo/banner, menggantikan ikon; GIF bergerak didukung (maks. 2 MB)</td></tr>
            <tr><td>Ikon</td><td>Klik <strong>Pilih ikon</strong>, cari dari 2.050 ikon (boleh bahasa Indonesia: <em>dokumen, uang, jadwal</em>)</td></tr>
            <tr><td>Kelompok</td><td>Kosong = Pintasan Layanan Utama; atau mis. <em>Zona Integritas</em>, <em>Aplikasi Pendukung</em></td></tr>
        </tbody>
    </table></div>
    <?= $img('08-pemilih-ikon.jpg', 'Pemilih ikon: ketik kata kunci, lalu klik ikonnya.') ?>
    <p>Tampilkan tiap kelompok lewat <em>Tampilan Beranda → Tambah → Pintasan Layanan</em>: pilih kelompok dan gaya <strong>Kartu</strong>, <strong>Gambar saja (banner)</strong>, atau <strong>Tombol kotak</strong>.</p>
    <div class="guide-grid2">
        <?= $img('08-pintasan-zi.jpg', 'Gaya Gambar saja (banner), mis. Zona Integritas.') ?>
        <?= $img('08-pintasan-aplikasi.jpg', 'Gaya Tombol kotak, mis. Aplikasi Pendukung.') ?>
    </div>
    <?= $tip('Pintasan bertoken yang belum diisi di Pengaturan tidak ditampilkan, agar tidak ada tautan mati.') ?>
    <?= $go('data/pintasan', 'Buka Pintasan Layanan') ?>
</section>

<section id="video" class="guide-sec card">
    <h2>11. Galeri Video</h2>
    <ol>
        <li><em>Publikasi → Galeri Video → Tambah</em>.</li>
        <li>Isi judul dan tempel tautan YouTube (<em>watch</em>, <em>youtu.be</em>, <em>shorts</em>) atau Google Drive. <code>https://</code> dilengkapi otomatis.</li>
        <li>Sampul YouTube otomatis; untuk Google Drive unggah gambar sampul sendiri.</li>
    </ol>
    <p>Video diputar di dalam website. Halaman <strong>Galeri Video</strong> (menu Berita & Publikasi) menampilkan 12 video per halaman; tambahkan blok <strong>Galeri Video</strong> di Beranda untuk video terbaru.</p>
    <?= $img('08-galeri-video.jpg', 'Halaman Galeri Video; klik sampul untuk memutar.') ?>
    <?= $go('data/video', 'Buka Galeri Video') ?>
</section>

<section id="data" class="guide-sec card">
    <h2>12. Dokumen & Data Layanan</h2>
    <p>Data di menu samping otomatis tampil di halaman terkait dan dihitung Dashboard SINOVA.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Menu</th><th>Isian utama</th><th>Tampil di</th></tr></thead>
        <tbody>
            <tr><td>Arsip Dokumen</td><td>Jenis, judul, tahun, bulan, PDF atau tautan luar</td><td>Halaman sesuai jenis</td></tr>
            <tr><td>Jenis Dokumen</td><td>Nama, kode, bagian, periode, minimal tahun</td><td>Pengelompokan & cek SINOVA</td></tr>
            <tr><td>Jadwal Sidang</td><td>Tanggal, jam, nomor perkara, agenda, ruang</td><td>Jadwal Persidangan, blok Jadwal Hari Ini</td></tr>
            <tr><td>Panggilan Ghaib</td><td>Nama pihak, nomor perkara, tanggal sidang, relaas PDF</td><td>Panggilan Ghaib, blok Pengumuman</td></tr>
            <tr><td>Statistik Perkara</td><td>Tahun, bulan, sisa lalu, masuk, putus, sisa</td><td>Statistik Perkara</td></tr>
            <tr><td>Pejabat & Pegawai</td><td>Nama, jabatan, kelompok, pangkat, foto, riwayat, SIMTEPA</td><td>Profil Pejabat & Pegawai + profil per orang</td></tr>
            <tr><td>LHKPN / LHKASN</td><td>Nama, jabatan, tahun, ikhtisar PDF</td><td>LHKPN</td></tr>
            <tr><td>Wilayah Yurisdiksi</td><td>Nama kecamatan / kab-kota</td><td>Wilayah Yurisdiksi</td></tr>
            <tr><td>Pengaduan & Hukuman Disiplin</td><td>Jenis, tahun, inisial, pelanggaran, tindak lanjut</td><td>Pengaduan, Hukuman Disiplin</td></tr>
            <tr><td>Laporan Akses Informasi</td><td>Tahun, bulan, permohonan, waktu, hasil</td><td>Laporan Akses Informasi</td></tr>
        </tbody>
    </table></div>
    <?= $img('09-arsip-dokumen.jpg', 'Daftar Arsip Dokumen dengan pencarian dan saringan jenis.') ?>
    <ul>
        <li>PDF maks. <strong>20 MB</strong>, gambar maks. 8 MB. Berkas lebih besar: pakai <strong>Tautan Luar</strong> Google Drive.</li>
        <li>Laporan bulanan wajib diisi <strong>Bulan</strong>; dokumen 5 tahunan diisi setiap tahun.</li>
        <li>Jadwal sidang bisa diambil dari database SIPP (Pengaturan → Tautan Layanan).</li>
        <li>Pengaduan/hukuman disiplin memakai <strong>inisial</strong>.</li>
    </ul>
    <?= $go('data/dokumen', 'Buka Arsip Dokumen') ?>
</section>

<section id="pengaturan" class="guide-sec card">
    <h2>13. Pengaturan Situs</h2>
    <p><em>Sistem → Pengaturan</em> (Super Admin). Klik <strong>Simpan Pengaturan</strong> di setiap tab.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Tab</th><th>Isian penting</th></tr></thead>
        <tbody>
            <tr><td>Identitas Satker</td><td>Nama satker, sub-judul, tingkat (PA/PTA), kode satker, <strong>logo</strong> (juga ikon tab browser), deskripsi situs</td></tr>
            <tr><td>Alamat & Kontak</td><td>Alamat, telepon, <strong>WhatsApp PTSP</strong> & pesan awal, email, <strong>jam pelayanan, istirahat, sidang</strong>, peta, foto gedung, media sosial</td></tr>
            <tr><td>Tautan Layanan</td><td>SIPP, e-Court, Direktori Putusan, SIWAS, PPID, <strong>CCTV Online</strong> + ikon/GIF, website survei, jadwal dari SIPP</td></tr>
            <tr><td>Nilai Survei</td><td>IKM, IPKP, IPAK, periode</td></tr>
            <tr><td>Popup Pembuka</td><td>Aktif, lokasi, frekuensi, ukuran, gambar, judul, teks, tombol</td></tr>
            <tr><td>Keamanan</td><td>Wajibkan MFA</td></tr>
        </tbody>
    </table></div>
    <?= $img('10-pengaturan-kontak.jpg', 'Tab Alamat & Kontak, termasuk WhatsApp PTSP dan jam pelayanan.') ?>
    <p><strong>Format jam:</strong> satu baris per hari, <code>Hari: jam</code> — mis. <code>Senin – Kamis: 08.00 – 16.30 WIB</code>.</p>
    <h3>Label nilai survei</h3>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Skala 1–4</th><th>Label</th><th>IPAK</th></tr></thead>
        <tbody>
            <tr><td>1,00 – 2,5996</td><td>Tidak Baik</td><td>Tidak Bersih</td></tr>
            <tr><td>2,60 – 3,0640</td><td>Kurang Baik</td><td>Kurang Bersih</td></tr>
            <tr><td>3,0644 – 3,532</td><td>Baik</td><td>Bersih</td></tr>
            <tr><td>3,5324 – 4,00</td><td>Sangat Baik</td><td>Sangat Bersih</td></tr>
        </tbody>
    </table></div>
    <p class="small text-secondary">Nilai skala 100 (mis. 92,40) dibagi 25 terlebih dahulu.</p>
    <?= $img('10-pengaturan-popup.jpg', 'Tab Popup Pembuka.') ?>
    <?= $tip('Popup: ukuran Sedang (720 px) dan frekuensi <em>Sekali per kunjungan</em> disarankan. Popup tampil lagi bila gambar/judul diganti. Bila logo diganti, hapus berkas <code>public/favicon.ico</code> agar ikon tab lama tidak dipakai.') ?>
    <?php if (can([])): ?><?= $go('pengaturan', 'Buka Pengaturan') ?><?php endif ?>
</section>

<section id="pengunjung" class="guide-sec card">
    <h2>14. Fitur untuk Pengunjung</h2>
    <h3>Sarana Disabilitas & Suara Panduan</h3>
    <p>Tombol <strong>Disabilitas</strong> (kanan bawah): perbesar/perkecil teks, kontras tinggi, <strong>Suara Panduan</strong> (aktif bawaan), <strong>Bacakan Isi Halaman</strong>, reset.</p>
    <ol>
        <li>Sambutan <em>"Selamat datang di website <?= $site ?>"</em> sekali per kunjungan.</li>
        <li>Setiap klik dibacakan, mis. klik <em>Jadwal Persidangan</em> → "Jadwal Persidangan".</li>
        <li>Klik menu langsung membuka halaman, lalu nama halaman diumumkan. Pengguna Tab juga dibacakan.</li>
    </ol>
    <?= $img('11-disabilitas.jpg', 'Panel Sarana Disabilitas.') ?>
    <?= $tip('Chrome/Edge/Safari baru mengizinkan suara setelah klik pertama — sambutan biasanya terdengar saat pengunjung menutup popup pembuka.') ?>
    <h3>Popup, CCTV, WhatsApp</h3>
    <ul>
        <li><strong>Popup pembuka</strong>: ditutup dengan ✕ merah, klik di luar, atau Esc.</li>
        <li><strong>CCTV Online</strong> (kiri atas) & <strong>Chat WhatsApp</strong> (kiri bawah): tampil bila diisi di Pengaturan; di layar kecil menjadi ikon bundar.</li>
        <li><strong>Pencarian</strong> (15 hasil per halaman), penampil dokumen dalam website, pita Informasi Serta Merta.</li>
    </ul>
    <?= $img('11-popup.jpg', 'Popup pembuka ukuran Sedang (720 px).') ?>
</section>

<section id="pengguna" class="guide-sec card">
    <h2>15. Pengguna, Peran & Log Aktivitas</h2>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Peran</th><th>Boleh mengelola</th></tr></thead>
        <tbody>
            <?php foreach (config('SiteStructure')->roles as $k => $l): ?>
                <tr><td><?= esc($l) ?></td><td><?= ['superadmin' => 'Semua menu, Pengaturan, Pengguna, Log, keamanan, pembaruan database', 'admin' => 'Semua konten & data (tanpa Pengguna & Keamanan)', 'kepaniteraan' => 'Jadwal Sidang, Panggilan Ghaib, Statistik Perkara, dokumen kepaniteraan, halaman Layanan Hukum', 'kesekretariatan' => 'Pejabat & Pegawai, LHKPN, Wilayah Yurisdiksi, dokumen kesekretariatan, halaman Profil & Transparansi', 'humas' => 'Berita & Publikasi, Slider, Galeri Video, Tampilan Beranda'][$k] ?? '' ?></td></tr>
            <?php endforeach ?>
        </tbody>
    </table></div>
    <?= $img('12-pengguna.jpg', 'Menu Pengguna: peran, status MFA, dan tombol Reset MFA.') ?>
    <ul>
        <li>Setiap petugas memakai akunnya sendiri; jangan berbagi akun.</li>
        <li><strong>Reset MFA</strong> bila petugas ganti HP; <strong>nonaktifkan/hapus</strong> akun petugas yang pindah. Super Admin terakhir tidak bisa dihapus.</li>
        <li><strong>Log Aktivitas</strong> mencatat login, tambah, ubah, hapus, pengaturan, susunan menu, pembaruan database (SK Bab II huruf G.2).</li>
    </ul>
    <?php if (can([], true)): ?><?= $go('pengguna', 'Buka Pengguna') ?> <?= $go('log', 'Buka Log Aktivitas') ?><?php endif ?>
</section>

<section id="perawatan" class="guide-sec card">
    <h2>16. Perawatan & Keamanan</h2>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Kapan</th><th>Tugas</th></tr></thead>
        <tbody>
            <tr><td>Setiap hari kerja</td><td>Jadwal sidang (bila tidak dari SIPP), panggilan ghaib, berita/pengumuman</td></tr>
            <tr><td>Awal bulan</td><td>Laporan biaya perkara & sisa panjar bulan lalu, statistik perkara, akses informasi</td></tr>
            <tr><td>Triwulan/semester</td><td>Nilai survei, LRA, laporan pengaduan</td></tr>
            <tr><td>Tahunan</td><td>DIPA, RKT, PK, IKU, LKjIP, Laporan Tahunan, LHKPN, RUP</td></tr>
            <tr><td>Mingguan</td><td>Cek Dashboard SINOVA & Log Aktivitas; cadangkan database</td></tr>
            <tr><td>Setelah perbarui file</td><td>Dashboard → <strong>Jalankan pembaruan</strong></td></tr>
        </tbody>
    </table></div>
    <ul>
        <li>Wajibkan MFA; gunakan HTTPS & domain <code>.go.id</code>.</li>
        <li>Hanya folder <code>public</code> yang boleh diakses internet; file <code>.env</code> berisi kata sandi database — jangan dibagikan.</li>
        <li>Isi editor dibersihkan otomatis dari kode berbahaya.</li>
        <li><strong>Kapasitas</strong> (uji 500 pengunjung serentak, 2 CPU/8 GB): 0 gagal, 95% halaman &lt; 33 ms, CPU rata-rata 40%. Untuk lonjakan, aktifkan cache nginx dari <code>deploy/nginx-satker.conf</code>.</li>
    </ul>
</section>

<section id="faq" class="guide-sec card">
    <h2>17. Pertanyaan Umum</h2>
    <div class="accordion" id="faqAcc">
        <?php $faq = [
            ['Tampilan belum berubah setelah memperbarui file', 'Tekan <strong>Ctrl+F5</strong> atau buka di jendela penyamaran (cache browser).'],
            ['Muncul pesan galat setelah memperbarui file', 'Buka Dashboard → klik <strong>Jalankan pembaruan</strong>.'],
            ['Kriteria SINOVA tetap "Belum ada"', 'Baca kolom Catatan; tulis tahun/bulan di judul tabel, isi tautan, hapus [LENGKAPI].'],
            ['Tombol pintasan/CCTV/WhatsApp/survei tidak muncul', 'Tautan atau nomornya belum diisi di Pengaturan.'],
            ['Gambar tidak bisa diunggah', 'Gunakan JPG/PNG/WebP maks. 8 MB atau GIF maks. 2 MB.'],
            ['PDF tidak bisa diunggah', 'Maks. 20 MB; berkas lebih besar pakai tautan Google Drive.'],
            ['Video tidak bersampul', 'Pastikan tautan YouTube lengkap; untuk Google Drive unggah gambar sampul.'],
            ['Sambutan suara tidak terdengar', 'Browser baru mengizinkan suara setelah klik pertama; cek Suara Panduan aktif & volume.'],
            ['Pita Informasi Serta Merta tidak hilang', 'Cek isian <em>Tampilkan pita sampai</em>, atau ubah status ke Draft. Perubahan tampil dalam ≤ 60 detik.'],
            ['Lupa kata sandi / kode MFA', 'Minta Super Admin mengatur ulang di menu Pengguna.'],
        ];
        foreach ($faq as $i => [$q, $a]): ?>
            <div class="accordion-item">
                <h3 class="accordion-header"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>" aria-expanded="false" aria-controls="faq<?= $i ?>"><?= esc($q) ?></button></h3>
                <div id="faq<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small"><?= $a ?></div></div>
            </div>
        <?php endforeach ?>
    </div>
    <p class="small text-secondary mt-3 mb-0">Kode sumber & pembaruan: <a href="https://github.com/rohmansyah1228/website-satker-pengadilan-agama" target="_blank" rel="noopener">github.com/rohmansyah1228/website-satker-pengadilan-agama</a></p>
</section>

    </div>
</div>
<script src="<?= asset('assets/js/guide.js') ?>" defer></script>
<?= $this->endSection() ?>
