<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Struktur baku website sesuai SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026.
 *
 * - $sections      : 6 menu utama (Bab III huruf F)
 * - $requiredPages : halaman wajib — slug/URL dikunci agar URL yang diinput ke SINOVA tidak berubah
 * - $docTypes      : jenis dokumen awal (dapat ditambah admin)
 * - $criteria      : kriteria Lampiran II untuk Dashboard Kepatuhan SINOVA
 *
 * Seeder membaca file ini untuk data awal. Mengubah file ini setelah instalasi
 * tidak mengubah database, kecuali dashboard kepatuhan ($criteria) yang dibaca langsung.
 */
class SiteStructure extends BaseConfig
{
    /** Peran pengguna dan modul yang boleh dikelola. */
    public array $roles = [
        'superadmin'      => 'Super Admin',
        'admin'           => 'Admin Konten',
        'kepaniteraan'    => 'Operator Kepaniteraan',
        'kesekretariatan' => 'Operator Kesekretariatan',
        'humas'           => 'Operator Humas / Berita',
    ];

    /** section => [label, ikon, peran pengelola halaman] */
    public array $sections = [
        'profil'         => ['label' => 'Profil Pengadilan', 'icon' => 'bi-building', 'roles' => ['kesekretariatan']],
        'layanan-hukum'  => ['label' => 'Layanan Hukum', 'icon' => 'bi-bank', 'roles' => ['kepaniteraan']],
        'transparansi'   => ['label' => 'Transparansi', 'icon' => 'bi-clipboard-data', 'roles' => ['kesekretariatan']],
        'layanan-publik' => ['label' => 'Layanan Publik & Pengaduan', 'icon' => 'bi-people', 'roles' => ['kesekretariatan', 'kepaniteraan']],
    ];

    /**
     * Modul yang bisa ditempelkan pada halaman.
     * key => label
     */
    public array $modules = [
        ''                => '— Tanpa modul (konten saja) —',
        'documents'       => 'Daftar Dokumen (pilih jenis dokumen)',
        'pegawai'         => 'Profil Pejabat & Pegawai',
        'yurisdiksi'      => 'Wilayah Yurisdiksi',
        'kontak'          => 'Alamat & Kontak',
        'jadwal'          => 'Jadwal Persidangan Harian',
        'ghaib'           => 'Panggilan Ghaib',
        'statistik'       => 'Statistik Perkara Bulanan',
        'lhkpn'           => 'LHKPN Pejabat',
        'akses_informasi' => 'Laporan Akses Informasi',
        'pengaduan'       => 'Rekapitulasi Pengaduan',
        'hukdis'          => 'Hukuman Disiplin',
        'survei'          => 'Indeks & Laporan Survei',
        'pengunjung'      => 'Statistik Kunjungan Website',
        'ecourt'          => 'Tautan e-Court & Direktori Putusan',
    ];

    /**
     * Halaman wajib: section => [slug => [title, module, param(kode jenis dokumen)]]
     * Urutan di sini = urutan menu awal.
     */
    public array $requiredPages = [
        'profil' => [
            'visi-misi'  => ['Visi dan Misi', '', ''],
            'tupoksi'    => ['Tugas Pokok dan Fungsi', '', ''],
            'yurisdiksi' => ['Wilayah Yurisdiksi', 'yurisdiksi', ''],
            'struktur'   => ['Struktur Organisasi', '', ''],
            'pegawai'    => ['Profil Pejabat dan Pegawai', 'pegawai', ''],
            'kontak'     => ['Alamat dan Kontak', 'kontak', ''],
        ],
        'layanan-hukum' => [
            'jadwal-sidang'         => ['Jadwal Persidangan', 'jadwal', ''],
            'biaya-perkara'         => ['Biaya Proses Berperkara & Radius', 'documents', 'panjar-biaya,radius'],
            'prosedur-berperkara'   => ['Prosedur Berperkara', '', ''],
            'hak-pencari-keadilan'  => ['Hak Pencari Keadilan (Prodeo/Posbakum)', '', ''],
            'panggilan-ghaib'       => ['Panggilan Ghaib', 'ghaib', ''],
            'mediasi'               => ['Mediasi', '', ''],
            'statistik-perkara'     => ['Statistik Perkara', 'statistik', ''],
            'laporan-biaya-perkara' => ['Laporan Penggunaan Biaya Perkara', 'documents', 'laporan-biaya-perkara'],
            'sisa-panjar'           => ['Laporan Pengembalian Sisa Panjar', 'documents', 'sisa-panjar'],
            'ecourt'                => ['e-Court & Direktori Putusan', 'ecourt', ''],
        ],
        'transparansi' => [
            'rencana-strategis'       => ['Rencana Strategis (Renstra)', 'documents', 'renstra'],
            'rencana-kinerja-tahunan' => ['Rencana Kinerja Tahunan (RKT)', 'documents', 'rkt'],
            'perjanjian-kinerja'      => ['Perjanjian Kinerja (PK)', 'documents', 'pk'],
            'rencana-aksi-kinerja'    => ['Rencana Aksi Kinerja', 'documents', 'rak'],
            'indikator-kinerja-utama' => ['Indikator Kinerja Utama (IKU)', 'documents', 'iku'],
            'lkjip'                   => ['Laporan Kinerja (LKjIP)', 'documents', 'lkjip'],
            'laporan-tahunan'         => ['Laporan Tahunan', 'documents', 'laporan-tahunan'],
            'dipa'                    => ['DIPA, Rencana & Realisasi Anggaran', 'documents', 'dipa,rencana-anggaran,realisasi-dipa'],
            'lra'                     => ['Laporan Realisasi Anggaran (LRA)', 'documents', 'lra'],
            'calk'                    => ['Catatan atas Laporan Keuangan (CALK)', 'documents', 'calk'],
            'pengadaan'               => ['Pengadaan Barang dan Jasa', 'documents', 'rup'],
            'lhkpn'                   => ['LHKPN / LHKASN', 'lhkpn', ''],
        ],
        'layanan-publik' => [
            'maklumat-pelayanan'      => ['Standar dan Maklumat Pelayanan', 'documents', 'standar-pelayanan'],
            'ppid'                    => ['Layanan Informasi (PPID)', '', ''],
            'laporan-akses-informasi' => ['Laporan Akses Informasi Publik', 'akses_informasi', ''],
            'pengaduan'               => ['Pengaduan (SIWAS) & Rekapitulasi', 'pengaduan', ''],
            'hukuman-disiplin'        => ['Hukuman Disiplin Hasil Pengawasan', 'hukdis', ''],
            'disabilitas'             => ['Layanan Kelompok Rentan & Disabilitas', '', ''],
            'survei'                  => ['Survei Pelayanan Publik', 'survei', 'laporan-survei'],
            'statistik-pengunjung'    => ['Statistik Kunjungan Website', 'pengunjung', ''],
        ],
    ];

    /**
     * Jenis dokumen awal.
     * code => [nama, section pengelola, periode (tahunan|bulanan|tetap), minimal tahun]
     */
    public array $docTypes = [
        'panjar-biaya'          => ['SK Panjar Biaya Perkara', 'layanan-hukum', 'tetap', 1],
        'radius'                => ['SK Radius Biaya Panggilan', 'layanan-hukum', 'tetap', 1],
        'laporan-biaya-perkara' => ['Laporan Penggunaan Biaya Perkara', 'layanan-hukum', 'bulanan', 1],
        'sisa-panjar'           => ['Laporan Pengembalian Sisa Panjar', 'layanan-hukum', 'bulanan', 1],
        'renstra'               => ['Rencana Strategis / Reviu Renstra', 'transparansi', 'tahunan', 5],
        'rkt'                   => ['Rencana Kinerja Tahunan', 'transparansi', 'tahunan', 5],
        'pk'                    => ['Perjanjian Kinerja', 'transparansi', 'tahunan', 5],
        'rak'                   => ['Rencana Aksi Kinerja', 'transparansi', 'tahunan', 5],
        'iku'                   => ['Indikator Kinerja Utama / Reviu IKU', 'transparansi', 'tahunan', 5],
        'lkjip'                 => ['Laporan Kinerja Instansi Pemerintah', 'transparansi', 'tahunan', 5],
        'laporan-tahunan'       => ['Laporan Tahunan', 'transparansi', 'tahunan', 5],
        'dipa'                  => ['DIPA', 'transparansi', 'tahunan', 1],
        'rencana-anggaran'      => ['Rencana Penggunaan Anggaran', 'transparansi', 'tahunan', 1],
        'realisasi-dipa'        => ['Realisasi DIPA', 'transparansi', 'tahunan', 1],
        'lra'                   => ['Laporan Realisasi Anggaran', 'transparansi', 'tahunan', 1],
        'calk'                  => ['Catatan atas Laporan Keuangan', 'transparansi', 'tahunan', 1],
        'rup'                   => ['Rencana Umum Pengadaan (RUP)', 'transparansi', 'tahunan', 1],
        'standar-pelayanan'     => ['Standar & Maklumat Pelayanan', 'layanan-publik', 'tetap', 1],
        'laporan-survei'        => ['Laporan Survei (SKM, SPAK, SPKP)', 'layanan-publik', 'tahunan', 1],
    ];

    /**
     * Kriteria Lampiran II untuk Dashboard Kepatuhan.
     * group: berkala (bobot 40%) | wajib (bobot 20%)
     * page : section/slug halaman bukti
     * check: jenis pemeriksaan otomatis
     *   content            : halaman berisi teks/gambar
     *   docs:<kode>[,..]   : ada dokumen (tahunan → jumlah tahun minimal; bulanan → bulan lalu tersedia)
     *   table:<tabel>      : tabel berisi data
     *   setting:<k>[,..]   : pengaturan terisi
     * level: all | pa | pta
     */
    public array $criteria = [
        // ---------- INFORMASI BERKALA (40%) ----------
        ['B1.1', 'berkala', 'Rencana Aksi Kinerja (5 tahun)', 'transparansi/rencana-aksi-kinerja', 'docs:rak', 'all'],
        ['B1.2', 'berkala', 'Laporan Tahunan / Pelaksanaan Kegiatan (5 tahun)', 'transparansi/laporan-tahunan', 'docs:laporan-tahunan', 'all'],
        ['B2.1', 'berkala', 'Tautan SIPP', '', 'setting:sipp_url', 'all'],
        ['B2.2', 'berkala', 'Agenda/Jadwal Persidangan per hari', 'layanan-hukum/jadwal-sidang', 'table:hearings', 'all'],
        ['B2.3', 'berkala', 'Statistik perkara per bulan', 'layanan-hukum/statistik-perkara', 'table:case_stats', 'all'],
        ['B2.4', 'berkala', 'Panggilan Ghaib', 'layanan-hukum/panggilan-ghaib', 'table:ghaib', 'pa'],
        ['B2.4', 'berkala', 'Penjelasan Panggilan Ghaib Tingkat I', 'layanan-hukum/panggilan-ghaib', 'content', 'pta'],
        ['B2.5', 'berkala', 'Laporan penggunaan biaya perkara per bulan', 'layanan-hukum/laporan-biaya-perkara', 'docs:laporan-biaya-perkara', 'all'],
        ['B2.6', 'berkala', 'Laporan pengembalian sisa panjar per bulan', 'layanan-hukum/sisa-panjar', 'docs:sisa-panjar', 'pa'],
        ['B3.1', 'berkala', 'LHKPN pejabat (ikhtisar, min. 2 tahun)', 'transparansi/lhkpn', 'table:lhkpn', 'all'],
        ['B3.2', 'berkala', 'DIPA, Rencana Penggunaan & Realisasi', 'transparansi/dipa', 'docs:dipa,rencana-anggaran,realisasi-dipa', 'all'],
        ['B3.3', 'berkala', 'Laporan Realisasi Anggaran (LRA)', 'transparansi/lra', 'docs:lra', 'all'],
        ['B3.4', 'berkala', 'Catatan atas Laporan Keuangan (CALK)', 'transparansi/calk', 'docs:calk', 'all'],
        ['B3.5', 'berkala', 'Rencana Umum Pengadaan Barang & Jasa', 'transparansi/pengadaan', 'docs:rup', 'all'],
        ['B3.6', 'berkala', 'Rencana Strategis (5 tahun)', 'transparansi/rencana-strategis', 'docs:renstra', 'all'],
        ['B3.7', 'berkala', 'Rencana Kinerja Tahunan (5 tahun)', 'transparansi/rencana-kinerja-tahunan', 'docs:rkt', 'all'],
        ['B3.8', 'berkala', 'Rencana Aksi Kinerja tahun berjalan', 'transparansi/rencana-aksi-kinerja', 'docs:rak', 'all'],
        ['B3.9', 'berkala', 'Perjanjian Kinerja (5 tahun)', 'transparansi/perjanjian-kinerja', 'docs:pk', 'all'],
        ['B3.10', 'berkala', 'IKU dan Reviu IKU (5 tahun)', 'transparansi/indikator-kinerja-utama', 'docs:iku', 'all'],
        ['B3.11', 'berkala', 'LKjIP (5 tahun)', 'transparansi/lkjip', 'docs:lkjip', 'all'],
        ['B4.1', 'berkala', 'Laporan Akses Informasi', 'layanan-publik/laporan-akses-informasi', 'table:info_requests', 'all'],
        ['B4.2', 'berkala', 'Laporan Rekapitulasi Pengaduan', 'layanan-publik/pengaduan', 'table:complaints:pengaduan', 'all'],
        ['B4.3', 'berkala', 'Hukuman Disiplin Hasil Pengawasan', 'layanan-publik/hukuman-disiplin', 'table:complaints:hukdis', 'all'],
        ['B4.4', 'berkala', 'Nilai & Laporan Survei (SKM, SPAK, SPKP)', 'layanan-publik/survei', 'setting:ikm,ipak,ipkp', 'all'],
        ['B5.1', 'berkala', 'Statistik Kunjungan Website', 'layanan-publik/statistik-pengunjung', 'table:visitor_daily', 'all'],
        // ---------- INFORMASI WAJIB (20%) ----------
        ['W1.1', 'wajib', 'Visi dan Misi', 'profil/visi-misi', 'content', 'all'],
        ['W1.2', 'wajib', 'Tugas Pokok dan Fungsi', 'profil/tupoksi', 'content', 'all'],
        ['W1.3', 'wajib', 'Wilayah Yurisdiksi + peta', 'profil/yurisdiksi', 'table:jurisdictions', 'all'],
        ['W1.4', 'wajib', 'Struktur Organisasi (diagram, nama pejabat & jabatan)', 'profil/struktur', 'content', 'all'],
        ['W1.5', 'wajib', 'Alamat lengkap, telepon, email, URL, foto gedung', 'profil/kontak', 'setting:address_street,address_village,address_district,address_province,postal_code,phone,email,building_photo', 'all'],
        ['W2.1', 'wajib', 'Informasi & tautan e-Court', 'layanan-hukum/ecourt', 'setting:ecourt_url', 'all'],
        ['W2.2', 'wajib', 'Tautan Direktori Putusan satker', 'layanan-hukum/ecourt', 'setting:direktori_url', 'all'],
        ['W2.3', 'wajib', 'Prosedur Berperkara (6 jenis)', 'layanan-hukum/prosedur-berperkara', 'content', 'all'],
        ['W2.4', 'wajib', 'Rincian biaya proses berperkara', 'layanan-hukum/biaya-perkara', 'docs:panjar-biaya', 'all'],
        ['W3.1', 'wajib', 'Profil Pejabat, Pegawai & Honorer', 'profil/pegawai', 'table:employees', 'all'],
        ['W4.1', 'wajib', 'Standar dan Maklumat Pelayanan', 'layanan-publik/maklumat-pelayanan', 'content', 'all'],
    ];

    /** Kategori berita (Bab III huruf D & menu 6). */
    public array $postCategories = [
        'berita'      => 'Berita Kegiatan',
        'pengumuman'  => 'Pengumuman',
        'inovasi'     => 'Inovasi Layanan',
        'opini'       => 'Opini & Gagasan Aparatur',
        'artikel'     => 'Artikel Ilmiah',
        'serta-merta' => 'Informasi Serta Merta',
        'infografis'  => 'Infografis Pelayanan',
    ];

    /** Kelompok pegawai. */
    public array $employeeGroups = [
        'pimpinan' => 'Pimpinan',
        'hakim'    => 'Hakim',
        'pejabat'  => 'Pejabat Struktural/Fungsional',
        'pegawai'  => 'Pegawai',
        'honorer'  => 'PPNPN / Tenaga Honorer',
    ];

    public int $maxSliders = 5;
}
