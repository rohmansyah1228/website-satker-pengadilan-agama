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
    'ucapan'      => ['12', 'Ucapan & Belasungkawa', 'bi-card-image'],
    'data'        => ['13', 'Dokumen & Data Layanan', 'bi-folder2-open'],
    'pengaturan'  => ['14', 'Pengaturan Situs', 'bi-gear'],
    'pengunjung'  => ['15', 'Fitur untuk Pengunjung', 'bi-universal-access'],
    'pengguna'    => ['16', 'Pengguna, Peran & Log', 'bi-people'],
    'perawatan'   => ['17', 'Perawatan & Keamanan', 'bi-tools'],
    'faq'         => ['18', 'Pertanyaan Umum', 'bi-question-circle'],
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
            <tr><td>Publikasi</td><td>Berita, Informasi Serta Merta, slider, pintasan, galeri video, ucapan &amp; belasungkawa</td></tr>
            <tr><td>Data layanan</td><td>Arsip dokumen, jadwal sidang, panggilan ghaib, statistik, pegawai, LHKPN, dll.</td></tr>
            <tr><td>Pengunjung</td><td>Sarana disabilitas & Suara Panduan, popup pembuka, tombol CCTV & WhatsApp</td></tr>
            <tr><td>Pembaruan</td><td>Tombol <strong>Update Website</strong> di Dashboard: versi baru dari GitHub dipasang otomatis</td></tr>
        </tbody>
    </table></div>
    <?= $tip('Membuka panel admin dari HP/tablet: menu samping disembunyikan agar layar lega. Ketuk tombol <i class="bi bi-list"></i> di kiri atas untuk membuka menu, ketuk lagi, area gelap, atau tombol Esc untuk menutup.') ?>
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
    <?= $tip('<strong>Apakah mengganggu subdomain lain?</strong> Bila hosting hanya berisi website ini — tidak. Bila hosting juga menjalankan subdomain lain dan subdomainnya ikut terbelokkan, cara termudah (tanpa perlu paham teknis): minta penyedia hosting <strong>mengarahkan Document Root domain langsung ke folder <code>public/</code></strong>. Alternatif: gunakan berkas <code>deploy/htaccess-subdomain-bersama.txt</code> (ganti nama domainnya, lalu timpakan ke <code>public_html/.htaccess</code>). Rincian ada di README bagian Instalasi di Hosting.') ?>
    <h3>Pindah domain / dipakai PA lain</h3>
    <p>Alamat website terdeteksi otomatis (domain, IP, atau folder berganti tidak perlu diatur). Untuk PA lain cukup ganti <em>Pengaturan → Identitas Satker</em> dan <em>Alamat & Kontak</em>.</p>
    <h3>Pembaruan website (otomatis)</h3>
    <p><strong>Versi website</strong> yang terpasang tertulis di bagian bawah menu samping panel admin, mis. <em>Versi 1.8.0 (24 November 2026)</em>. Bila ada versi baru, di bawahnya muncul tanda oranye mis. <em>Versi 1.8.1 tersedia</em>.</p>
    <p>Bila ada versi baru di GitHub, <strong>Dashboard SINOVA</strong> menampilkan kotak <strong>Versi baru website tersedia</strong>: versi yang terpasang → versi baru, beserta isi perubahan setiap versi. Super Admin cukup klik <strong>Update ke Versi …</strong>, konfirmasi, lalu tunggu sampai muncul pesan berhasil (jangan tutup halaman). Sistem akan:</p>
    <ol>
        <li>mengunduh <strong>hanya berkas yang berubah</strong> dari repositori resmi di GitHub dan memeriksanya lebih dulu — bila satu berkas gagal, tidak ada yang diubah;</li>
        <li>menyimpan <strong>cadangan berkas lama</strong> di <code>writable/backups/pembaruan-…</code>;</li>
        <li>menimpa berkas, lalu menjalankan <strong>pembaruan database</strong> bila ada.</li>
    </ol>
    <p>Berkas <code>.env</code>, folder <code>writable/</code>, dan <code>public/uploads/</code> tidak pernah disentuh, sehingga berita, pengaturan, unggahan, dan database tetap aman. Bila setelah pembaruan ada yang tidak berjalan semestinya, klik <strong>Batalkan pembaruan terakhir</strong> di Dashboard untuk mengembalikan berkas.</p>
    <?= $tip('<strong>Bila tombol Update Website gagal</strong> (mis. hosting tidak mengizinkan PHP menulis berkas, atau server tidak bisa menghubungi GitHub), pesan galatnya menjelaskan sebabnya. Perbarui secara manual: buka rincian <em>Berkas yang akan diperbarui</em>, unduh berkas tersebut dari GitHub dan timpakan ke hosting, lalu muat ulang Dashboard dan klik <strong>Jalankan pembaruan</strong> bila diminta. Website satker lain tidak ikut berubah saat repositori diperbarui — tiap satker memasang pembaruannya sendiri.') ?>
    <?= $tip('<strong>Pertama kali:</strong> website yang dipasang sebelum fitur ini ada belum memiliki tombol Update Website. Perbarui secara manual sekali (timpa berkas yang tercantum di Dashboard); setelah itu pembaruan berikutnya cukup dengan tombol.') ?>
    <h3>Pembaruan database</h3>
    <p>Pembaruan otomatis sudah menjalankan pembaruan database. Bila Anda memperbarui berkas secara manual, buka <strong>Dashboard SINOVA</strong>; bila muncul kotak <strong>Pembaruan database tersedia</strong>, klik <strong>Jalankan pembaruan</strong> (Super Admin). Aman dijalankan ulang dan tidak menghapus data.</p>
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
    <h3>Kotak di bagian atas Dashboard</h3>
    <ul>
        <li><strong>Versi baru website tersedia</strong> (Super Admin): berisi catatan perubahan dan tombol <strong>Update Website</strong>. Lihat <a href="#pemasangan">bab 2 — Pembaruan website</a>.</li>
        <li><strong>Website diperbarui otomatis …</strong>: muncul setelah pembaruan, dengan tombol <strong>Batalkan pembaruan terakhir</strong>.</li>
        <li><strong>Ada pembaruan database</strong>: klik <strong>Jalankan pembaruan</strong> (biasanya sudah dijalankan otomatis oleh Update Website).</li>
    </ul>
    <?= $img('04-update-website.jpg', 'Kotak Versi baru website tersedia dengan tombol Update Website.') ?>
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
    <h3 id="arsip-bulanan">Blok Arsip Bulanan (laporan per bulan dalam satu tahun)</h3>
    <p>Cocok untuk laporan bulanan seperti <em>Realisasi/Serapan Anggaran DIPA 01 &amp; 04</em>, laporan keuangan, atau LIPA: setiap bulan tampil sebagai kartu, yang sudah diisi tautannya bisa diklik, yang belum tertulis <em>Menunggu Publikasi</em>. Di bagian atas tampil tahun dan jumlah bulan yang sudah terpublikasi.</p>
    <ol>
        <li>Pada <em>Konten Tambahan Halaman</em> klik <strong>Arsip Bulanan</strong>.</li>
        <li>Isi <strong>Tahun</strong>. Label boleh dikosongkan (tampil <em>Arsip Terjadwal 2026</em>).</li>
        <li>Isi nama baris, mis. <strong>SERAPAN 01</strong>, lalu tautan dokumen (Google Drive) di kotak bulannya. Klik <strong>+ Tambah baris</strong> untuk SERAPAN 04 dan seterusnya.</li>
        <li>Banyak tautan sekaligus: salin satu kolom tautan dari Excel (Januari–Desember), lalu tempel di kotak <em>Januari</em>; tautan terisi berurutan ke bulan berikutnya.</li>
        <li>Untuk tahun berikutnya, klik <strong>Gandakan</strong> pada blok, ganti tahunnya, lalu kosongkan tautannya.</li>
    </ol>
    <?= $img('05-arsip-bulanan.jpg', 'Blok Arsip Bulanan di halaman publik: bulan yang terisi bisa diklik, yang belum tertulis Menunggu Publikasi.') ?>
    <?= $img('05-arsip-bulanan-editor.jpg', 'Mengisi blok Arsip Bulanan di editor halaman.') ?>
    <?= $tip('Bulan yang terisi ikut dihitung Dashboard SINOVA sebagai laporan bulan tersebut, jadi tidak perlu lagi menulis nama bulan di judul tabel.') ?>
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
            <tr><td>Ucapan & Belasungkawa (carousel banner terbaru)</td><td>Publikasi → Ucapan & Belasungkawa</td></tr>
            <tr><td>Pengumuman & Panggilan Ghaib</td><td>Panggilan Ghaib + berita Pengumuman/Serta Merta</td></tr>
            <tr><td>Berita & Publikasi</td><td>Berita (bisa per kategori)</td></tr>
            <tr><td>Jadwal Sidang Hari Ini · Dokumen Terbaru · Statistik Perkara</td><td>Menu data terkait</td></tr>
            <tr><td>Indeks Survei Pelayanan</td><td>Halaman Survei / Pengaturan → Nilai Survei</td></tr>
            <tr><td>Jam Pelayanan & Sidang</td><td>Pengaturan → Alamat & Kontak</td></tr>
            <tr><td>Konten Bebas · Gambar/Banner · Kartu Info · Kotak Pencarian</td><td>Diisi langsung di tampilan</td></tr>
            <tr><td>Baris Kolom</td><td>Wadah beberapa tampilan berdampingan</td></tr>
        </tbody>
    </table></div>
    <?= $tip('<strong>Jadwal Sidang Hari Ini</strong>: bila tabel jadwal kosong (mis. jadwal belum diinput atau sinkron SIPP tidak aktif), tampilan ini otomatis menampilkan blok <strong>Sematkan</strong> pertama dari halaman <em>Layanan Hukum → Jadwal Sidang</em> — mis. slide jadwal SIPP <code>https://sipp.…/slide_sidang</code>. Matikan lewat pengaturan tampilan <em>Bila jadwal kosong, tampilkan sematan</em>.') ?>
    <?= $tip('Perubahan di panel admin langsung tampil di Beranda. Data Beranda disimpan sementara 60 detik dan dikosongkan otomatis setiap admin menyimpan.') ?>
    <?= $go('beranda', 'Buka Tampilan Beranda') ?>
</section>

<section id="berita" class="guide-sec card">
    <h2>8. Berita & Informasi Serta Merta</h2>
    <p><em>Publikasi → Berita & Publikasi → Tambah</em>: judul, ringkasan, isi, gambar utama + teks alternatif, penulis, tanggal terbit, kategori, status. Tanggal terbit di masa depan = terjadwal.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Kategori</th><th>Tampil di</th></tr></thead>
        <tbody>
            <tr><td>Berita Kegiatan, Inovasi Layanan, Opini, <strong>Artikel Ilmiah</strong>, Infografis</td><td>Blok Berita Beranda & halaman Berita (9 per halaman)</td></tr>
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
    <h3>Pengumuman Badilag &amp; Mahkamah Agung (otomatis)</h3>
    <p>Pengumuman dari <em>badilag.mahkamahagung.go.id/pengumuman-elektronik</em> dan <em>mahkamahagung.go.id/id/pengumuman</em> disimpan sebagai arsip (judul, tanggal, tautan ke sumber) dan tampil di halaman <strong>Berita &amp; Publikasi → Pengumuman Badilag &amp; MA</strong> serta blok Beranda <strong>Pengumuman Badilag &amp; MA</strong>.</p>
    <ul>
        <li>Setiap jam hanya pengumuman yang <strong>belum ada</strong> yang diambil; yang sudah tersimpan dilewati, jadi tidak ada yang dobel.</li>
        <li>Pengumuman lama diambil bertahap mundur sampai tahun di <em>Pengaturan → Tautan Layanan → Arsip pengumuman mulai tahun</em>. Klik <strong>Ambil sekarang</strong> untuk mempercepat.</li>
        <li>Alamat sumber dapat diubah atau dikosongkan (mematikan) di tab yang sama. Situs MA saat ini menolak pengambilan otomatis (HTTP 403); pengunjung tetap mendapat tautan ke halaman pengumuman MA.</li>
    </ul>
    <h3 id="impor-berita">Memindahkan berita dari website lama</h3>
    <p>Super Admin: <em>Sistem → Impor Berita Lama</em>. Ada dua tab sesuai jenis website lama: <strong>Website Joomla</strong> (template website PA/PTA lama Badilag) dan <strong>Website WordPress</strong>. Impor dapat dihentikan dan dilanjutkan; berita yang sudah dipindah dilewati, jadi tidak ada data dobel. Setelah domain dipindah, alamat berita lama otomatis dialihkan ke berita yang sama di website baru.</p>
    <h4 class="h6 fw-bold mt-3">a. Dari website Joomla</h4>
    <ol>
        <li>Isi <strong>Alamat website lama</strong>, mis. <code>https://pa-namasatker.go.id</code>.</li>
        <li>Isi alamat halaman daftar kategori, mis. <em>…/berita/berita-terkini.html</em> → Berita Kegiatan, <em>…/berita/arsip-berita.html</em> → Pengumuman.</li>
        <li>Klik <strong>Periksa jumlah berita</strong>, lalu <strong>Mulai Impor</strong>.</li>
    </ol>
    <p>Tanggal, penulis, jumlah dibaca, gambar, dan PDF ikut dipindah.</p>
    <h4 class="h6 fw-bold mt-3">b. Dari website WordPress</h4>
    <ol>
        <li>Buka tab <strong>Website WordPress</strong>, isi alamat website lama.</li>
        <li>Klik <strong>Ambil daftar kategori dari WordPress</strong>: semua kategori tampil beserta jumlah beritanya.</li>
        <li>Centang kategori berita (mis. <em>Berita</em>, <em>Pengumuman</em>). <em>Uncategorized</em> biasanya berisi halaman informasi, bukan berita — jangan dicentang.</li>
        <li>Pilih <strong>Masuk ke kategori</strong> untuk tiap baris, lalu <strong>Periksa jumlah berita</strong> dan <strong>Mulai Impor</strong>.</li>
    </ol>
    <p>Judul, isi, tanggal &amp; jam terbit, gambar utama, gambar di dalam isi, PDF, dan jumlah dibaca (bila tersedia) ikut dipindah. Penulis berupa nama akun admin (mis. <em>adm …</em>) diganti <em>Humas</em>.</p>
    <?= $img('08-impor-wordpress.jpg', 'Tab Website WordPress: daftar kategori dari website lama, kategori tujuan, dan jumlah berita.') ?>
    <?= $tip('Impor WordPress memakai jalur data bawaan WordPress (REST API <code>/wp-json/</code>). Bila muncul pesan bahwa jalur itu ditutup, minta pengelola website lama membukanya sementara (biasanya lewat pengaturan plugin keamanan) selama proses impor.') ?>
    <h4 class="h6 fw-bold mt-3">c. Impor satu berita</h4>
    <p>Di kotak <strong>Impor satu berita</strong>, tempel alamat beritanya (bukan halaman kategori), satu per baris — mis. <code>…/berita/berita-terkini/3066-judul.html</code> (Joomla) atau <code>…/judul-berita/</code> (WordPress). Pilih kategori tujuan lalu <strong>Impor berita ini</strong>. Berita yang sudah ada dilewati, kecuali dicentang <strong>Timpa bila berita sudah ada</strong>.</p>
    <?= $tip('Bila muncul galat sertifikat SSL (umum di XAMPP Windows), centang <strong>Abaikan verifikasi sertifikat SSL</strong> di panel Pilihan.') ?>
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
            <tr><td>Gambar (opsional)</td><td>Logo/banner, menggantikan ikon; GIF bergerak didukung (maks. 100 MB)</td></tr>
            <tr><td>Ikon</td><td>Klik <strong>Pilih ikon</strong>, cari dari 2.050 ikon (boleh bahasa Indonesia: <em>dokumen, uang, jadwal</em>)</td></tr>
            <tr><td>Kelompok</td><td>Kosong = Pintasan Layanan Utama; atau mis. <em>Zona Integritas</em>, <em>Aplikasi Pendukung</em></td></tr>
        </tbody>
    </table></div>
    <?= $img('08-pemilih-ikon.jpg', 'Pemilih ikon: ketik kata kunci, lalu klik ikonnya.') ?>
    <p>Tampilkan tiap kelompok lewat <em>Tampilan Beranda → Tambah → Pintasan Layanan</em>: pilih kelompok dan gaya <strong>Kartu</strong>, <strong>Gambar saja (banner)</strong>, atau <strong>Tombol kotak</strong>.</p>
    <p><strong>Kartu per baris</strong> menentukan jumlah kartu yang sejajar; kartu berikutnya otomatis turun ke baris baru. Contoh: Zona Integritas dengan 3 kartu per baris → 3 kartu sejajar, kartu ke-4 di baris kedua. Berlaku mulai layar sedang (tablet/laptop); di HP kartu tersusun 1–2 per baris. Bila kartu terasa sempit, perlebar kotak tampilannya (mis. 12 kolom).</p>
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

<section id="ucapan" class="guide-sec card">
    <h2>12. Ucapan & Belasungkawa</h2>
    <p>Banner <strong>ucapan selamat</strong> (hari besar, ucapan sukses, purnabakti) dan <strong>belasungkawa</strong> tampil di Beranda sebagai carousel: banner terbaru bergantian otomatis setiap 6 detik.</p>
    <h3>Menambah banner</h3>
    <ol>
        <li><em>Publikasi → Ucapan &amp; Belasungkawa → Tambah</em>.</li>
        <li>Pilih <strong>Jenis</strong>: <em>Ucapan Selamat</em> atau <em>Belasungkawa</em>.</li>
        <li>Unggah <strong>Banner</strong> (wajib). Poster persegi (1:1, mis. 1080 × 1080 px seperti untuk Instagram) paling pas; gambar otomatis dikompres ke WebP.</li>
        <li>Isi <strong>Judul / Keterangan</strong>, mis. <em>Hari Guru Nasional 2026</em> atau nama yang berduka. Judul tidak tampil di bawah banner, tetapi dibacakan untuk pengguna pembaca layar.</li>
        <li><strong>Tanggal</strong> menentukan urutan "terbaru" (kosong = hari ini). <strong>Tautan</strong> opsional: banner dapat diklik menuju berita terkait.</li>
        <li>Matikan <strong>Tampilkan</strong> untuk menyembunyikan banner tanpa menghapusnya.</li>
    </ol>
    <?= $img('12-ucapan-form.jpg', 'Tambah Ucapan: banner, jenis, dan tanggal di panel kanan.') ?>
    <h3>Menampilkan di Beranda</h3>
    <ol>
        <li><em>Tampilan Beranda → Tambah Tampilan → Ucapan &amp; Belasungkawa</em>.</li>
        <li><strong>Banner per kolom</strong>: jumlah banner terbaru yang diputar (mis. <strong>3</strong>).</li>
        <li><strong>Kolom yang ditampilkan</strong>: <em>Keduanya</em> (dua kolom dalam satu kotak), atau <em>Ucapan Selamat saja</em> / <em>Belasungkawa saja</em>.</li>
    </ol>
    <p>Agar seperti contoh di bawah, buat <strong>dua tampilan</strong> berdampingan selebar 6 kolom: satu berjudul <em>Ucapan Selamat</em> (Ucapan Selamat saja, aksen hijau) dan satu berjudul <em>Belasungkawa</em> (Belasungkawa saja, aksen oranye). Judul kotak menjadi satu-satunya judul; kotak tanpa banner menampilkan keterangan singkat.</p>
    <?= $img('12-ucapan-beranda.jpg', 'Beranda: kotak Ucapan Selamat (carousel 2 banner) dan Belasungkawa berdampingan.') ?>
    <?= $tip('Belasungkawa sebaiknya dinonaktifkan atau diberi tanggal lama setelah masa berkabung, agar ucapan selamat terbaru yang tampil.') ?>
    <?= $go('data/ucapan', 'Buka Ucapan & Belasungkawa') ?>
</section>

<section id="data" class="guide-sec card">
    <h2>13. Dokumen & Data Layanan</h2>
    <p>Data di menu samping otomatis tampil di halaman terkait dan dihitung Dashboard SINOVA.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Menu</th><th>Isian utama</th><th>Tampil di</th></tr></thead>
        <tbody>
            <tr><td>Arsip Dokumen</td><td>Jenis, judul, tahun, bulan, PDF atau tautan luar</td><td>Halaman sesuai jenis</td></tr>
            <tr><td>Jenis Dokumen</td><td>Nama, kode, bagian, periode, minimal tahun</td><td>Pengelompokan & cek SINOVA</td></tr>
            <tr><td>Jadwal Sidang</td><td>Tanggal, jam, nomor perkara, agenda, ruang</td><td>Jadwal Persidangan, blok Jadwal Hari Ini</td></tr>
            <tr><td>Panggilan Ghaib</td><td>Nama pihak, nomor perkara, tanggal sidang, relaas PDF; isi <em>Tanggal Putus</em> bila perkara sudah diputus (pindah ke tabel Sudah Putus)</td><td>Panggilan Ghaib, blok Pengumuman</td></tr>
            <tr><td>Statistik Perkara</td><td>Tahun, bulan, sisa lalu, masuk, putus, sisa</td><td>Statistik Perkara</td></tr>
            <tr><td>Pejabat & Pegawai</td><td>Nama, jabatan, kelompok, pangkat, foto, riwayat, SIMTEPA</td><td>Profil Pejabat & Pegawai + profil per orang</td></tr>
            <tr><td>LHKPN / LHKASN</td><td>Nama, jabatan, tahun, ikhtisar PDF</td><td>LHKPN</td></tr>
            <tr><td>Wilayah Yurisdiksi</td><td>Nama kecamatan / kab-kota</td><td>Wilayah Yurisdiksi</td></tr>
            <tr><td>Pengaduan & Hukuman Disiplin</td><td>Jenis, tahun, inisial, pelanggaran, tindak lanjut</td><td>Pengaduan, Hukuman Disiplin</td></tr>
            <tr><td>Laporan Akses Informasi</td><td>Tahun, bulan, permohonan, waktu, hasil</td><td>Laporan Akses Informasi</td></tr>
        </tbody>
    </table></div>
    <?= $img('09-arsip-dokumen.jpg', 'Daftar Arsip Dokumen dengan pencarian dan saringan jenis.') ?>
    <h3 id="panggilan-ghaib">Panggilan Ghaib</h3>
    <p>Halaman Panggilan Ghaib menampilkan dua tabel:</p>
    <ol>
        <li><strong>Panggilan Sidang (Perkara Belum Putus)</strong>: nama pihak, nomor perkara, tanggal sidang, dan tombol <em>Lihat Relaas (PDF)</em>. Baris otomatis tidak tampil 7 hari setelah tanggal sidang.</li>
        <li><strong>Perkara Ghaib yang Sudah Putus</strong>: perkara yang sudah diputus, dengan tanggal putus dan relaasnya, tampil selama 1 tahun sejak tanggal putus. Tabel ini hanya muncul bila ada isinya.</li>
    </ol>
    <?= $img('13-panggilan-ghaib.jpg', 'Halaman Panggilan Ghaib: tabel panggilan sidang dan tabel perkara yang sudah putus.') ?>
    <p>Mengisi manual: <em>Kepaniteraan → Panggilan Ghaib → Tambah</em>. Isi nama pihak, nomor perkara, tanggal sidang, dan unggah relaas PDF. Bila perkara sudah diputus, isi <strong>Tanggal Putus</strong>; barisnya pindah ke tabel Sudah Putus dan tidak lagi tampil di blok Pengumuman Beranda.</p>
    <?= $img('13-ghaib-admin.jpg', 'Form Panggilan Ghaib dengan isian Tanggal Putus.') ?>
    <?= $tip('Bila Integrasi SIPP aktif, perkara ghaib (belum putus maupun sudah putus) beserta relaasnya terisi otomatis dari SIPP. Lihat <a href="#integrasi-sipp">Integrasi SIPP</a>. Relaas yang Anda unggah manual tidak pernah ditimpa.') ?>
    <ul>
        <li>PDF maks. <strong>20 MB</strong>, gambar JPG/PNG/WebP maks. 8 MB, GIF bergerak maks. 100 MB. Berkas lebih besar: pakai <strong>Tautan Luar</strong> Google Drive.</li>
        <li>Laporan bulanan wajib diisi <strong>Bulan</strong>; dokumen 5 tahunan diisi setiap tahun.</li>
        <li>Jadwal sidang, statistik perkara, dan panggilan ghaib bisa terisi otomatis dari SIPP (<a href="#integrasi-sipp">Integrasi SIPP</a>).</li>
        <li>Pengaduan/hukuman disiplin memakai <strong>inisial</strong>.</li>
    </ul>
    <?= $go('data/dokumen', 'Buka Arsip Dokumen') ?>
</section>

<section id="pengaturan" class="guide-sec card">
    <h2>14. Pengaturan Situs</h2>
    <p><em>Sistem → Pengaturan</em> (Super Admin). Klik <strong>Simpan Pengaturan</strong> di setiap tab.</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Tab</th><th>Isian penting</th></tr></thead>
        <tbody>
            <tr><td>Identitas Satker</td><td>Nama satker, sub-judul, tingkat (PA/PTA), kode satker, <strong>logo</strong> (juga ikon tab browser), deskripsi situs</td></tr>
            <tr><td>Alamat & Kontak</td><td>Alamat, telepon, <strong>WhatsApp PTSP</strong> & pesan awal, email, <strong>jam pelayanan, istirahat, sidang</strong>, peta, foto gedung, media sosial</td></tr>
            <tr><td>Tautan Layanan</td><td>SIPP, e-Court, Direktori Putusan, SIWAS, PPID, <strong>Tautan di kaki halaman</strong> (judul &amp; daftar tautan, satu per baris <code>Nama | https://alamat</code>), <strong>CCTV Online</strong> + ikon/GIF, website survei</td></tr>
            <tr><td>Nilai Survei</td><td>IKM, IPKP, IPAK, periode</td></tr>
            <tr><td>Popup Pembuka</td><td>Aktif, lokasi, frekuensi, ukuran, gambar, judul, teks, tombol</td></tr>
            <tr><td>Integrasi SIPP</td><td>Kunci SIPP API kantor, data yang diisi otomatis (jadwal, statistik, panggilan ghaib), status kiriman terakhir</td></tr>
            <tr><td>Keamanan</td><td>Wajibkan MFA; buka/kunci halaman &amp; menu wajib SK</td></tr>
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
    <h3 id="integrasi-sipp">Integrasi SIPP (data otomatis dari server kantor)</h3>
    <p>Website di hosting tidak bisa membaca database SIPP di jaringan kantor. Karena itu aplikasi <strong>SIPP API</strong> dipasang di server kantor: aplikasi ini membaca database SIPP (hanya membaca) lalu <strong>mengirim</strong> data ke website secara terjadwal. Data yang diterima langsung mengisi modul website:</p>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Data dari SIPP</th><th>Tampil di</th></tr></thead>
        <tbody>
            <tr><td>Jadwal sidang (hari ini s.d. 14 hari ke depan)</td><td>Halaman Jadwal Sidang (ditambah kolom jenis perkara) dan blok Beranda <em>Jadwal Sidang Hari Ini</em>. Nama pihak tidak ditampilkan.</td></tr>
            <tr><td>Statistik perkara tahun berjalan</td><td>Menu & halaman Statistik Perkara (per bulan: sisa lalu, masuk, putus, sisa) serta Dashboard SINOVA</td></tr>
            <tr><td>Panggilan ghaib (belum putus &amp; sudah putus)</td><td>Menu & halaman Panggilan Ghaib serta blok Pengumuman. Perkara yang sudah putus pindah ke tabel <em>Perkara Ghaib yang Sudah Putus</em> (tampil 1 tahun sejak tanggal putus). <strong>Relaas (PDF) terbaru Tergugat/Termohon ikut dikirim</strong> dari folder dokumen SIPP dan tampil sebagai tombol <em>Lihat Relaas (PDF)</em>.</td></tr>
        </tbody>
    </table></div>
    <?= $img('14-integrasi-sipp.jpg', 'Tab Integrasi SIPP: unduh aplikasi kantor, pilihan data otomatis, dan tabel Kiriman terakhir.') ?>
    <h4 class="h6 fw-bold mt-3">Memasang (cara termudah)</h4>
    <ol>
        <li>Website: <em>Sistem → Pengaturan → Integrasi SIPP</em> (Super Admin) → klik <strong>Unduh SIPP API Kantor</strong>. Paket zip yang terunduh <strong>sudah berisi alamat &amp; kunci website ini</strong>, dan Integrasi SIPP otomatis diaktifkan.</li>
        <li>Di server kantor tempat SIPP berada: ekstrak zip, salin folder <code>sipp-api-kantor</code> ke web root (mis. <code>C:\xampp\htdocs\</code> atau <code>/var/www/html/</code>).</li>
        <li>Buka <code>http://IP-SERVER/sipp-api-kantor/</code>, buat password dashboard. Koneksi database SIPP diambil otomatis dari aplikasi SIPP di server yang sama; bila tidak ditemukan, isi di menu Pengaturan lalu <strong>Tes koneksi database</strong>.</li>
        <li>Klik <strong>Kirim Semua Data Sekarang</strong>, lalu nyalakan <strong>Mode Otomatis</strong> (atau jadwalkan <code>php kirim.php</code> lewat cron / Task Scheduler).</li>
        <li>Aplikasi kantor <strong>hanya mengirim data yang berubah atau baru</strong>. Data yang tidak berubah tetap dikirim ulang setiap 6 jam sebagai tanda aplikasi masih berjalan; tombol <em>Kirim ulang semua</em> di dashboard kantor mengirim semuanya sekarang.</li>
        <li>Kembali ke website: tabel <strong>Kiriman terakhir</strong> di tab Integrasi SIPP menampilkan waktu diterima dan jumlah data.</li>
    </ol>
    <?= $img('14-sipp-kantor.jpg', 'Dashboard SIPP API Kantor: hanya data yang berubah yang dikirim.') ?>
    <h4 class="h6 fw-bold mt-3">Pengiriman otomatis tanpa membuka browser (disarankan)</h4>
    <p>Menu <em>Mode Otomatis</em> hanya berjalan selama tab browsernya terbuka. Agar pengiriman tetap berjalan walau tidak ada yang login, jadwalkan <code>kirim.php</code> di server kantor. Perintah lengkapnya (sudah berisi lokasi PHP dan aplikasi di server itu) ada di <em>Mode Otomatis → Ingin berjalan otomatis tanpa membuka halaman?</em></p>
    <ul>
        <li><strong>Windows</strong>: buka Command Prompt <em>sebagai Administrator</em>, tempel perintah <code>schtasks /Create /TN "SIPP API Kantor" /SC MINUTE /MO 5 …</code> sekali saja. Tugas berjalan tiap 5 menit, juga setelah server dinyalakan ulang.</li>
        <li><strong>Linux</strong>: tambahkan baris cron <code>*/5 * * * * php /var/www/html/sipp-api-kantor/kirim.php</code>.</li>
        <li>Server kantor tidak memerlukan IP publik: kantor yang mengirim ke website, bukan website yang mengambil.</li>
    </ul>
    <p class="small">Sudah memasang aplikasi kantor sebelumnya? Di dashboard kantor buka <em>Pengaturan → Website satker</em>, klik <strong>Salin</strong>, lalu tempel teksnya di kotak <strong>Tempel kunci</strong> pada tab Integrasi SIPP dan Simpan.</p>
    <h4 class="h6 fw-bold mt-3">Relaas panggilan ghaib otomatis</h4>
    <ul>
        <li>Aplikasi kantor mencari relaas Tergugat/Termohon seperti halaman Monitoring Panggilan Ghaib SIMERAK: tabel <code>perkara_pelaksanaan_relaas</code>, berkasnya di folder <code>sipp/resources/file/doc/TAHUN/BULAN/</code>. Yang dikirim hanya <strong>relaas terbaru</strong> yang berkasnya sudah diunggah di SIPP.</li>
        <li>Berkas dikirim sekali saja; bila di SIPP muncul relaas baru, relaas di website otomatis diganti.</li>
        <li><strong>Relaas yang diunggah manual di menu Panggilan Ghaib tidak pernah ditimpa.</strong> Untuk kembali ke relaas otomatis, hapus berkas manualnya.</li>
        <li>Mematikan: tab Integrasi SIPP → <em>Relaas (PDF) ikut dikirim otomatis</em> = Tidak, atau di dashboard kantor hilangkan centang <em>Kirim dokumen relaas</em>.</li>
        <li>Batas ukuran relaas 20 MB. Karena dikirim dalam bentuk teks, hosting harus menerima kiriman ± 28 MB: atur <code>post_max_size = 32M</code> di PHP hosting (dan <code>client_max_body_size 32m</code> bila memakai nginx). Bila dashboard kantor menulis <em>lebih besar dari batas hosting</em>, naikkan batas itu atau unggah relaas itu manual.</li>
        <li>Folder dokumen SIPP dicari otomatis. Bila dashboard kantor menulis <em>folder dokumen SIPP tidak ditemukan</em>, isi di Pengaturan → bagian <em>1. Database SIPP</em> → <strong>Folder aplikasi SIPP</strong>, mis. <code>C:\xampp\htdocs\sipp</code> (folder aplikasi SIPP atau langsung folder <code>resources/file/doc</code>). Di bawah isian tampil folder dokumen yang dipakai, atau peringatan bila salah.</li>
    </ul>
    <?= $tip('Paket unduhan berisi <strong>kunci rahasia</strong> website: simpan hanya di server kantor dan jangan dibagikan. Bila bocor, buat kunci baru di dashboard kantor lalu tempel di tab Integrasi SIPP — kunci lama otomatis tidak berlaku.', 'warn') ?>
    <?= $tip('Setiap kiriman diperiksa dengan kunci API dan tanda tangan digital; kiriman yang kuncinya salah, isinya diubah, atau berumur lebih dari 10 menit ditolak. Bila dashboard kantor menampilkan <em>Timestamp tidak valid</em>, samakan jam server kantor. Bila jadwal dari SIPP tidak tersedia untuk suatu tanggal, website memakai jadwal yang diisi manual.') ?>
</section>

<section id="pengunjung" class="guide-sec card">
    <h2>15. Fitur untuk Pengunjung</h2>
    <h3>Sarana Disabilitas & Suara Panduan</h3>
    <p>Tombol <strong>Disabilitas</strong> (kanan bawah): perbesar/perkecil teks, kontras tinggi, <strong>Suara Panduan</strong> (aktif bawaan), <strong>Bacakan Isi Halaman</strong>, reset.</p>
    <ol>
        <li>Sambutan <em>"Selamat datang di website <?= $site ?>"</em> sekali per kunjungan.</li>
        <li>Setiap klik dibacakan, mis. klik <em>Jadwal Persidangan</em> → "Jadwal Persidangan".</li>
        <li>Klik menu langsung membuka halaman, lalu nama halaman diumumkan. Pengguna Tab juga dibacakan.</li>
        <li><strong>Blok (sorot) teks</strong> apa pun di halaman, misalnya visi, misi, atau isi berita, maka teks itu langsung dibacakan. Bisa dengan mouse, keyboard (Shift + panah), atau tekan lama di HP.</li>
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
    <h2>16. Pengguna, Peran & Log Aktivitas</h2>
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
    <h2>17. Perawatan & Keamanan</h2>
    <div class="table-responsive"><table class="table table-sm align-middle">
        <thead><tr><th>Kapan</th><th>Tugas</th></tr></thead>
        <tbody>
            <tr><td>Setiap hari kerja</td><td>Jadwal sidang (bila tidak dari SIPP), panggilan ghaib, berita/pengumuman</td></tr>
            <tr><td>Awal bulan</td><td>Laporan biaya perkara & sisa panjar bulan lalu, statistik perkara, akses informasi</td></tr>
            <tr><td>Triwulan/semester</td><td>Nilai survei, LRA, laporan pengaduan</td></tr>
            <tr><td>Tahunan</td><td>DIPA, RKT, PK, IKU, LKjIP, Laporan Tahunan, LHKPN, RUP</td></tr>
            <tr><td>Mingguan</td><td>Cek Dashboard SINOVA & Log Aktivitas; cadangkan database</td></tr>
            <tr><td>Ada versi baru</td><td>Dashboard → <strong>Update Website</strong> (bila manual: timpa berkas lalu <strong>Jalankan pembaruan</strong>)</td></tr>
        </tbody>
    </table></div>
    <ul>
        <li>Wajibkan MFA; gunakan HTTPS & domain <code>.go.id</code>.</li>
        <li><strong>Buka kunci halaman &amp; menu wajib SK</strong> (Pengaturan → Keamanan): normalnya terkunci agar alamat yang dinilai SINOVA tetap. Buka hanya saat ada <strong>SK baru</strong> yang mengubah struktur — sesuaikan slug/menunya (alamat lama otomatis dialihkan), lalu <strong>kunci kembali</strong>.</li>
        <li>Hanya folder <code>public</code> yang boleh diakses internet; file <code>.env</code> berisi kata sandi database — jangan dibagikan.</li>
        <li>Isi editor dibersihkan otomatis dari kode berbahaya.</li>
        <li><strong>Kapasitas</strong> (uji 500 pengunjung serentak, 2 CPU/8 GB): 0 gagal, 95% halaman &lt; 33 ms, CPU rata-rata 40%. Untuk lonjakan, aktifkan cache nginx dari <code>deploy/nginx-satker.conf</code>.</li>
    </ul>
    <h3 id="cloudflare">Memakai Cloudflare (opsional)</h3>
    <p>Cloudflare menyaring bot dan serangan, serta mempercepat gambar/CSS. Website ini sudah mengenali Cloudflare: IP asli pengunjung dibaca dari jaringan Cloudflare, sehingga hitungan pengunjung, kunci percobaan login, dan log aktivitas tetap benar.</p>
    <ol>
        <li>Daftar di cloudflare.com → <em>Add a site</em> → masukkan domain → paket <strong>Free</strong>. Ganti <strong>nameserver</strong> domain ke milik Cloudflare (lewat pengelola domain .go.id).</li>
        <li><strong>SSL/TLS → Full (strict)</strong>. Jangan pilih <em>Flexible</em> (halaman bisa berputar-putar/redirect terus).</li>
        <li><strong>Security → Bots → Bot Fight Mode</strong>: aktifkan.</li>
        <li><strong>Security → WAF → Custom rules</strong>, buat aturan <em>Skip</em> (lewati semua pemeriksaan, termasuk Bot Fight Mode) untuk <em>URI Path equals</em> <code>/api/sipp/terima</code>. Tanpa ini kiriman dari SIPP API Kantor bisa dianggap bot dan ditolak.</li>
        <li><strong>Jangan</strong> aktifkan <em>Cache Everything</em> untuk halaman HTML dan <strong>matikan Rocket Loader</strong> (Speed → Optimization). Halaman HTML yang di-cache Cloudflare tidak terhitung di statistik pengunjung dan panel admin bisa menampilkan data lama.</li>
        <li>Opsional: <em>Caching → Cache Rules</em> → <em>Bypass cache</em> untuk alamat panel admin dan <code>/api/</code>.</li>
    </ol>
    <?= $tip('Perbarui website ke versi terbaru <strong>sebelum</strong> mengaktifkan Cloudflare. Versi lama membaca semua pengunjung sebagai IP Cloudflare: hitungan pengunjung jadi sangat kecil dan percobaan login yang salah dari satu orang bisa mengunci semua admin.', 'warn') ?>
</section>

<section id="faq" class="guide-sec card">
    <h2>18. Pertanyaan Umum</h2>
    <div class="accordion" id="faqAcc">
        <?php $faq = [
            ['Tampilan belum berubah setelah memperbarui file', 'Tekan <strong>Ctrl+F5</strong> atau buka di jendela penyamaran (cache browser).'],
            ['Muncul pesan galat setelah memperbarui file', 'Buka Dashboard → klik <strong>Jalankan pembaruan</strong>. Bila masih galat setelah Update Website, klik <strong>Batalkan pembaruan terakhir</strong>.'],
            ['Update Website gagal: "Server tidak mengizinkan menulis berkas"', 'Minta penyedia hosting memberi izin tulis pada folder website untuk PHP, atau perbarui berkas secara manual.'],
            ['Kriteria SINOVA tetap "Belum ada"', 'Baca kolom Catatan; tulis tahun/bulan di judul tabel, isi tautan, hapus [LENGKAPI].'],
            ['Tombol pintasan/CCTV/WhatsApp/survei tidak muncul', 'Tautan atau nomornya belum diisi di Pengaturan.'],
            ['Gambar tidak bisa diunggah', 'Gunakan JPG/PNG/WebP maks. 8 MB atau GIF maks. 100 MB. GIF besar juga dibatasi upload_max_filesize & post_max_size PHP server (mis. 128M).'],
            ['PDF tidak bisa diunggah', 'Maks. 20 MB; berkas lebih besar pakai tautan Google Drive.'],
            ['Video tidak bersampul', 'Pastikan tautan YouTube lengkap; untuk Google Drive unggah gambar sampul.'],
            ['Sambutan suara tidak terdengar', 'Browser baru mengizinkan suara setelah klik pertama; cek Suara Panduan aktif & volume.'],
            ['Pita Informasi Serta Merta tidak hilang', 'Cek isian <em>Tampilkan pita sampai</em>, atau ubah status ke Draft. Perubahan tampil dalam ≤ 60 detik.'],
            ['Lupa kata sandi / kode MFA', 'Minta Super Admin mengatur ulang di menu Pengguna.'],
            ['Tombol Update Website tidak muncul', 'Tombol hanya untuk Super Admin dan hanya bila ada versi baru. Klik <strong>Bersihkan Cache</strong> di Dashboard untuk memeriksa ulang. Website yang dipasang sebelum fitur ini ada perlu diperbarui manual sekali.'],
            ['Banner ucapan tidak tampil di Beranda', 'Pastikan banner berstatus Tampilkan, jenisnya sesuai dengan pilihan <em>Kolom yang ditampilkan</em> di tampilan Beranda, dan termasuk yang terbaru sesuai <em>Banner per kolom</em>.'],
            ['Impor WordPress: "Kategori tidak ditemukan" atau jalur data ditutup', 'Gunakan tombol <strong>Ambil daftar kategori</strong> agar alamat kategori tepat. Bila REST API ditutup, minta pengelola website lama membukanya sementara.'],
            ['Data SIPP tidak muncul di website', 'Buka <em>Pengaturan → Integrasi SIPP</em>: pastikan <strong>Pakai data kiriman SIPP API kantor</strong> = Ya, kunci berstatus tersimpan, dan tabel Kiriman terakhir terisi. Bila belum ada kiriman, klik Kirim Semua Data Sekarang di dashboard kantor dan baca pesan galatnya.'],
            ['Kolom Dokumen Relaas Panggilan Ghaib masih "-"', 'Relaas dikirim otomatis bila berkasnya sudah diunggah di SIPP. Pastikan Dashboard → <strong>Jalankan pembaruan</strong> sudah diklik, aplikasi kantor versi terbaru, dan dashboard kantor tidak menulis <em>folder dokumen SIPP tidak ditemukan</em>. Relaas di atas batas hosting dilewati (lihat pesan di dashboard kantor).'],
            ['Dashboard kantor menulis "Tidak ada perubahan, tidak dikirim"', 'Itu normal: data sama dengan kiriman terakhir. Klik <strong>Kirim ulang semua</strong> bila ingin mengirim paksa.'],
            ['GIF ditolak "mengandung kode yang tidak diperbolehkan"', 'Perbarui website (Update Website). GIF sah tidak lagi ditolak; yang ditolak hanya GIF yang disisipi kode program.'],
            ['Pengiriman SIPP berhenti saat komputer/browser ditutup', 'Mode Otomatis butuh tab yang terbuka. Jadwalkan <code>kirim.php</code> dengan Task Scheduler/cron (lihat <a href="#integrasi-sipp">Integrasi SIPP</a>).'],
            ['Menambah tautan di kaki halaman (Tautan Ekosistem Peradilan)', '<em>Pengaturan → Tautan Layanan → Tautan di kaki halaman</em>: tambahkan baris baru <code>Nama | https://alamat</code>, lalu Simpan. Urutan baris = urutan tampil; lebih dari 6 tautan otomatis tampil dua kolom.'],
            ['Apakah data pengunjung membuat database terus membesar?', 'Tidak. Jumlah kunjungan disimpan <strong>satu baris per hari</strong> (tabel <code>visitor_daily</code>, ± 365 baris per tahun). Tabel <code>visitor_hits</code> hanya penanda sementara agar satu pengunjung tidak dihitung dua kali dalam sehari, dan otomatis dikosongkan setiap hari (hanya 2 hari terakhir yang disimpan). Total kunjungan tetap utuh.'],
            ['Pengunjung sangat banyak, apakah ada bot?', 'Website hanya menghitung satu kali per pengunjung per hari dan tidak menghitung bot yang mengaku bot. Bot yang menyamar sebagai browser tetap terhitung; untuk menyaringnya gunakan Cloudflare dengan Bot Fight Mode (lihat bab Perawatan & Keamanan).'],
            ['Kartu Pintasan hanya 2 per baris', 'Atur <em>Kartu per baris</em> di tampilan Pintasan Layanan, perlebar kotak tampilannya, lalu tekan Ctrl+F5.'],
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
