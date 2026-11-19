<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Definisi modul data di panel admin (CRUD generik).
 * Menambah modul baru cukup dengan menambah entri di sini + tabel di migration.
 *
 * Jenis field: text, textarea, richtext, number, date, time, url, select, image, pdf, checkbox, icon, month, year
 */
class AdminResources extends BaseConfig
{
    public array $resources = [];

    public function __construct()
    {
        parent::__construct();
        $s      = config(SiteStructure::class);
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = bulan_id($m);
        }

        $this->resources = [
            // ------------------------------------------------ PUBLIKASI
            'berita' => [
                'table' => 'posts', 'title' => 'Berita & Publikasi', 'singular' => 'Publikasi', 'icon' => 'bi-newspaper',
                'group' => 'Publikasi', 'roles' => ['humas'], 'order' => ['published_at', 'DESC'],
                'list'  => ['title' => 'Judul', 'category' => 'Kategori', 'status' => 'Status', 'published_at' => 'Tanggal Terbit', 'views' => 'Dibaca'],
                'search' => ['title', 'content'], 'filter' => ['category' => $s->postCategories],
                'slug'  => ['field' => 'slug', 'from' => 'title'], 'view' => 'berita/{slug}',
                'hint'  => 'Berita berorientasi pada pelayanan masyarakat dan inovasi, bukan sekadar liputan seremonial (Bab III huruf D). Kategori "Informasi Serta Merta" tampil sebagai pengumuman di atas semua halaman selama 7 hari.',
                'fields' => [
                    'title'        => ['Judul', 'text', 'required|max_length[255]'],
                    'category'     => ['Kategori', 'select', 'required', 'options' => $s->postCategories],
                    'excerpt'      => ['Ringkasan', 'textarea', 'permit_empty|max_length[500]', 'help' => 'Tampil di daftar berita & hasil pencarian. Kosongkan untuk otomatis.'],
                    'content'      => ['Isi', 'richtext', 'required'],
                    'image'        => ['Gambar Utama', 'image', 'permit_empty', 'width' => 1200],
                    'image_alt'    => ['Teks Alternatif Gambar (alt)', 'text', 'permit_empty|max_length[255]', 'help' => 'Deskripsikan gambar untuk pengguna pembaca layar.'],
                    'author'       => ['Penulis', 'text', 'permit_empty|max_length[120]'],
                    'published_at' => ['Tanggal Terbit', 'datetime', 'required'],
                    'status'       => ['Status', 'select', 'required|in_list[published,draft]', 'options' => ['published' => 'Terbit', 'draft' => 'Draft']],
                ],
            ],
            'slider' => [
                'table' => 'sliders', 'title' => 'Slider Beranda', 'singular' => 'Slide', 'icon' => 'bi-images',
                'group' => 'Publikasi', 'roles' => ['humas'], 'order' => ['sort', 'ASC'], 'max' => $s->maxSliders,
                'list'  => ['image' => 'Gambar', 'title' => 'Judul', 'sort' => 'Urutan', 'is_active' => 'Aktif'],
                'hint'  => 'Maksimal ' . $s->maxSliders . ' slide aktif (Bab III huruf C). Gambar otomatis dikompres ke WebP. Tombol dapat memakai token {sipp_url}, {ecourt_url}, {direktori_url}.',
                'fields' => [
                    'badge'        => ['Label Kecil', 'text', 'permit_empty|max_length[120]'],
                    'title'        => ['Judul', 'text', 'required|max_length[255]'],
                    'subtitle'     => ['Deskripsi', 'textarea', 'permit_empty|max_length[400]'],
                    'image'        => ['Gambar Latar (1600×700)', 'image', 'permit_empty', 'width' => 1600],
                    'image_alt'    => ['Teks Alternatif Gambar', 'text', 'permit_empty|max_length[255]'],
                    'button_text'  => ['Teks Tombol Utama', 'text', 'permit_empty|max_length[60]'],
                    'button_url'   => ['Tautan Tombol Utama', 'url', 'permit_empty|max_length[255]'],
                    'button2_text' => ['Teks Tombol Kedua', 'text', 'permit_empty|max_length[60]'],
                    'button2_url'  => ['Tautan Tombol Kedua', 'url', 'permit_empty|max_length[255]'],
                    'sort'         => ['Urutan', 'number', 'permit_empty|integer'],
                    'is_active'    => ['Tampilkan', 'checkbox', 'permit_empty'],
                ],
            ],
            'pintasan' => [
                'table' => 'shortcuts', 'title' => 'Pintasan Layanan', 'singular' => 'Pintasan', 'icon' => 'bi-grid-3x3-gap',
                'group' => 'Publikasi', 'roles' => [], 'order' => ['sort', 'ASC'],
                'list'  => ['icon' => 'Ikon', 'title' => 'Judul', 'url' => 'Tautan', 'sort' => 'Urutan', 'is_active' => 'Aktif'],
                'hint'  => 'Kartu pintasan di beranda (SIPP, e-Court, Direktori Putusan, pengaduan). Tautan dapat berupa token {sipp_url}, {ecourt_url}, {direktori_url}, {siwas_url}, {survey_url} yang diambil dari Pengaturan.',
                'fields' => [
                    'title'       => ['Judul', 'text', 'required|max_length[120]'],
                    'description' => ['Keterangan', 'text', 'permit_empty|max_length[255]'],
                    'icon'        => ['Ikon (Bootstrap Icons)', 'icon', 'permit_empty|max_length[60]', 'help' => 'Contoh: bi-search, bi-laptop, bi-journal-bookmark-fill. Daftar: icons.getbootstrap.com'],
                    'url'         => ['Tautan', 'url', 'required|max_length[255]'],
                    'sort'        => ['Urutan', 'number', 'permit_empty|integer'],
                    'is_active'   => ['Tampilkan', 'checkbox', 'permit_empty'],
                ],
            ],

            // ------------------------------------------------ DOKUMEN
            'dokumen' => [
                'table' => 'documents', 'title' => 'Arsip Dokumen', 'singular' => 'Dokumen', 'icon' => 'bi-file-earmark-pdf',
                'group' => 'Dokumen', 'roles' => ['kepaniteraan', 'kesekretariatan'], 'order' => ['year', 'DESC'], 'scopeDocTypes' => true,
                'list'  => ['title' => 'Judul', 'doc_type_id' => 'Jenis', 'year' => 'Tahun', 'month' => 'Bulan', 'file_id' => 'Berkas'],
                'search' => ['title'], 'filter' => ['doc_type_id' => 'doc_types'],
                'hint'  => 'Dokumen SAKIP, keuangan, PBJ, laporan bulanan, SK panjar, dll. Dokumen tahunan wajib tersedia minimal 5 tahun terakhir untuk Renstra, RKT, PK, IKU, LKjIP, Rencana Aksi & Laporan Tahunan.',
                'fields' => [
                    'doc_type_id'  => ['Jenis Dokumen', 'select', 'required|is_natural_no_zero', 'source' => ['doc_types', 'id', 'name']],
                    'title'        => ['Judul Dokumen', 'text', 'required|max_length[255]'],
                    'year'         => ['Tahun', 'year', 'required|integer|greater_than[1999]|less_than[2100]'],
                    'month'        => ['Bulan (untuk laporan bulanan)', 'select', 'permit_empty|integer', 'options' => ['' => '—'] + $months],
                    'file_id'      => ['Berkas PDF', 'pdf', 'permit_empty'],
                    'external_url' => ['atau Tautan Luar', 'url', 'permit_empty|valid_url_strict|max_length[255]', 'help' => 'Isi bila dokumen tersimpan di sistem lain. Diutamakan unggah PDF.'],
                ],
            ],
            'jenis-dokumen' => [
                'table' => 'doc_types', 'title' => 'Jenis Dokumen', 'singular' => 'Jenis Dokumen', 'icon' => 'bi-tags',
                'group' => 'Dokumen', 'roles' => [], 'order' => ['sort', 'ASC'], 'locked' => 'is_required',
                'list'  => ['name' => 'Nama', 'code' => 'Kode', 'section' => 'Bagian', 'period' => 'Periode', 'min_years' => 'Min. Tahun'],
                'hint'  => 'Jenis dokumen bawaan SK terkunci (tidak dapat dihapus/diubah kodenya). Tambahkan jenis baru lalu tempelkan pada halaman lewat modul "Daftar Dokumen".',
                'fields' => [
                    'name'        => ['Nama', 'text', 'required|max_length[150]'],
                    'code'        => ['Kode (huruf kecil & tanda -)', 'text', 'required|alpha_dash|max_length[60]', 'lockable' => true],
                    'section'     => ['Bagian Pengelola', 'select', 'required', 'options' => array_map(static fn ($x) => $x['label'], $s->sections)],
                    'period'      => ['Periode', 'select', 'required|in_list[tahunan,bulanan,tetap]', 'options' => ['tahunan' => 'Tahunan', 'bulanan' => 'Bulanan', 'tetap' => 'Tetap / sewaktu-waktu']],
                    'min_years'   => ['Minimal Tahun Tersedia', 'number', 'required|integer|greater_than[0]|less_than[11]'],
                    'description' => ['Keterangan', 'textarea', 'permit_empty'],
                    'sort'        => ['Urutan', 'number', 'permit_empty|integer'],
                ],
            ],

            // ------------------------------------------------ KEPANITERAAN
            'jadwal-sidang' => [
                'table' => 'hearings', 'title' => 'Jadwal Sidang', 'singular' => 'Jadwal', 'icon' => 'bi-calendar-week',
                'group' => 'Kepaniteraan', 'roles' => ['kepaniteraan'], 'order' => ['hearing_date', 'DESC'],
                'list'  => ['hearing_date' => 'Tanggal', 'hearing_time' => 'Jam', 'case_number' => 'Nomor Perkara', 'agenda' => 'Agenda', 'room' => 'Ruang'],
                'search' => ['case_number', 'agenda'],
                'hint'  => 'Diisi manual bila integrasi SIPP tidak diaktifkan (Pengaturan → Integrasi).',
                'fields' => [
                    'hearing_date' => ['Tanggal Sidang', 'date', 'required|valid_date[Y-m-d]'],
                    'hearing_time' => ['Jam', 'time', 'permit_empty|max_length[10]'],
                    'case_number'  => ['Nomor Perkara', 'text', 'required|max_length[80]'],
                    'agenda'       => ['Agenda Sidang', 'text', 'permit_empty|max_length[255]'],
                    'room'         => ['Ruang Sidang', 'text', 'permit_empty|max_length[100]'],
                ],
            ],
            'panggilan-ghaib' => [
                'table' => 'ghaib', 'title' => 'Panggilan Ghaib', 'singular' => 'Panggilan Ghaib', 'icon' => 'bi-megaphone',
                'group' => 'Kepaniteraan', 'roles' => ['kepaniteraan'], 'order' => ['hearing_date', 'DESC'], 'timestamps' => true,
                'list'  => ['party_name' => 'Nama Pihak', 'case_number' => 'Nomor Perkara', 'hearing_date' => 'Tanggal Sidang', 'file_id' => 'Relaas', 'is_active' => 'Tampil'],
                'search' => ['party_name', 'case_number'],
                'hint'  => 'Wajib memuat nama pihak, nomor perkara, tanggal sidang, dan tautan relaas. Panggilan otomatis tidak tampil 7 hari setelah tanggal sidang.',
                'fields' => [
                    'party_name'   => ['Nama Pihak', 'text', 'required|max_length[150]'],
                    'case_number'  => ['Nomor Perkara', 'text', 'required|max_length[80]'],
                    'hearing_date' => ['Tanggal Sidang', 'date', 'required|valid_date[Y-m-d]'],
                    'file_id'      => ['Dokumen Relaas (PDF)', 'pdf', 'permit_empty'],
                    'is_active'    => ['Tampilkan', 'checkbox', 'permit_empty'],
                ],
            ],
            'statistik-perkara' => [
                'table' => 'case_stats', 'title' => 'Statistik Perkara', 'singular' => 'Statistik Bulanan', 'icon' => 'bi-bar-chart',
                'group' => 'Kepaniteraan', 'roles' => ['kepaniteraan'], 'order' => ['year', 'DESC', 'month', 'DESC'], 'unique' => ['year', 'month'],
                'list'  => ['year' => 'Tahun', 'month' => 'Bulan', 'carried_over' => 'Sisa Lalu', 'received' => 'Masuk', 'decided' => 'Putus', 'remaining' => 'Sisa'],
                'hint'  => 'Isi setiap awal bulan untuk bulan sebelumnya. Sisa perkara dihitung otomatis bila dikosongkan.',
                'fields' => [
                    'year'         => ['Tahun', 'year', 'required|integer'],
                    'month'        => ['Bulan', 'select', 'required|integer|greater_than[0]|less_than[13]', 'options' => $months],
                    'carried_over' => ['Sisa Perkara Bulan Lalu', 'number', 'required|integer'],
                    'received'     => ['Perkara Masuk', 'number', 'required|integer'],
                    'decided'      => ['Perkara Putus', 'number', 'required|integer'],
                    'remaining'    => ['Sisa Perkara', 'number', 'permit_empty|integer'],
                ],
            ],

            // ------------------------------------------------ KESEKRETARIATAN
            'pegawai' => [
                'table' => 'employees', 'title' => 'Pejabat & Pegawai', 'singular' => 'Pegawai', 'icon' => 'bi-person-badge',
                'group' => 'Kesekretariatan', 'roles' => ['kesekretariatan'], 'order' => ['sort', 'ASC'],
                'list'  => ['photo' => 'Foto', 'name' => 'Nama', 'position' => 'Jabatan', 'grp' => 'Kelompok', 'is_active' => 'Tampil'],
                'search' => ['name', 'position'], 'filter' => ['grp' => $s->employeeGroups],
                'hint'  => 'Data dapat diambil dari SIMTEPA. Setiap pegawai yang ditampilkan memiliki halaman profil sendiri (tombol mata di daftar). Tampilan daftar (kartu/tabel) diatur pada Halaman → Profil Pejabat dan Pegawai.',
                'view'  => 'profil/pegawai/{id}',
                'fields' => [
                    'name'      => ['Nama Lengkap & Gelar', 'text', 'required|max_length[150]', 'help' => 'mis. Rifky Ardhitika, S.H.I., M.H.I.'],
                    'position'  => ['Jabatan', 'text', 'permit_empty|max_length[150]', 'help' => 'mis. Ketua, Wakil Ketua, Hakim, Panitera, Sekretaris, Juru Sita'],
                    'grp'       => ['Kelompok', 'select', 'required', 'options' => $s->employeeGroups],
                    'rank'      => ['Pangkat/Golongan', 'text', 'permit_empty|max_length[100]', 'help' => 'mis. Pembina Tingkat I (IV/b). Kosongkan untuk PPNPN.'],
                    'tmt'       => ['TMT Jabatan Terakhir / Mulai Bekerja', 'date', 'permit_empty|valid_date[Y-m-d]'],
                    'religion'  => ['Agama', 'select', 'permit_empty|in_list[,Islam,Kristen,Katolik,Hindu,Buddha,Konghucu]', 'options' => ['' => '—', 'Islam' => 'Islam', 'Kristen' => 'Kristen', 'Katolik' => 'Katolik', 'Hindu' => 'Hindu', 'Buddha' => 'Buddha', 'Konghucu' => 'Konghucu']],
                    'gender'    => ['Jenis Kelamin', 'select', 'permit_empty|in_list[,L,P]', 'options' => ['' => '—', 'L' => 'Pria', 'P' => 'Wanita']],
                    'photo'     => ['Foto', 'image', 'permit_empty', 'width' => 600, 'help' => 'Foto formal, sebaiknya rasio 3:4 atau persegi.'],
                    'profile_url' => ['Tautan Profil SIMTEPA (opsional)', 'url', 'permit_empty|valid_url_strict[http,https]|max_length[500]', 'help' => 'Bila diisi (mis. https://simtepa.mahkamahagung.go.id/share/profil_ketua/html/…), tombol profil membuka halaman ini di dalam website (popup). Bila kosong, memakai profil dari isian di bawah.'],
                    'bio'       => ['Profil Singkat / Sambutan (opsional)', 'richtext', 'permit_empty', 'height' => 320],
                    'education' => ['Riwayat Pendidikan', 'richtext', 'permit_empty', 'height' => 300, 'plainAs' => 'ol', 'help' => 'Gunakan daftar bernomor, mis. S-1 - IAIN Raden Fatah Palembang, Tahun Lulus 2003. Teks yang ditempel dari SIMTEPA boleh langsung dipakai.'],
                    'career'    => ['Riwayat Pekerjaan/Jabatan', 'richtext', 'permit_empty', 'height' => 300, 'plainAs' => 'ol', 'help' => 'Gunakan daftar bernomor, mis. Hakim, Pengadilan Agama Kayuagung, Tahun 2011.'],
                    'training'  => ['Riwayat Diklat/Pelatihan (opsional)', 'richtext', 'permit_empty', 'height' => 260, 'plainAs' => 'ol'],
                    'sort'      => ['Urutan', 'number', 'permit_empty|integer', 'help' => 'Angka kecil tampil lebih dulu dalam kelompoknya.'],
                    'is_active' => ['Tampilkan di website', 'checkbox', 'permit_empty'],
                ],
            ],
            'lhkpn' => [
                'table' => 'lhkpn', 'title' => 'LHKPN / LHKASN', 'singular' => 'LHKPN', 'icon' => 'bi-wallet2',
                'group' => 'Kesekretariatan', 'roles' => ['kesekretariatan'], 'order' => ['year', 'DESC'],
                'list'  => ['name' => 'Nama', 'position' => 'Jabatan', 'year' => 'Tahun', 'file_id' => 'Ikhtisar'],
                'search' => ['name'],
                'hint'  => 'Dokumen yang ditampilkan adalah Ikhtisar Kekayaan, minimal 2 tahun terakhir per pejabat.',
                'fields' => [
                    'name'     => ['Nama Pejabat', 'text', 'required|max_length[150]'],
                    'position' => ['Jabatan', 'text', 'permit_empty|max_length[150]'],
                    'year'     => ['Tahun Lapor', 'year', 'required|integer'],
                    'file_id'  => ['Ikhtisar Kekayaan (PDF)', 'pdf', 'permit_empty'],
                ],
            ],
            'wilayah' => [
                'table' => 'jurisdictions', 'title' => 'Wilayah Yurisdiksi', 'singular' => 'Wilayah', 'icon' => 'bi-geo-alt',
                'group' => 'Kesekretariatan', 'roles' => ['kesekretariatan'], 'order' => ['sort', 'ASC'],
                'list'  => ['name' => 'Nama', 'sort' => 'Urutan'],
                'hint'  => 'Tingkat pertama: nama kecamatan. Tingkat banding: nama kabupaten/kota. Peta diunggah sebagai gambar pada halaman Profil → Wilayah Yurisdiksi.',
                'fields' => [
                    'name' => ['Nama Kecamatan / Kabupaten/Kota', 'text', 'required|max_length[150]'],
                    'sort' => ['Urutan', 'number', 'permit_empty|integer'],
                ],
            ],

            // ------------------------------------------------ LAYANAN PUBLIK
            'pengaduan' => [
                'table' => 'complaints', 'title' => 'Pengaduan & Hukuman Disiplin', 'singular' => 'Data Pengawasan', 'icon' => 'bi-shield-exclamation',
                'group' => 'Layanan Publik', 'roles' => [], 'order' => ['year', 'DESC'],
                'list'  => ['kind' => 'Jenis', 'year' => 'Tahun', 'initials' => 'Inisial', 'violation' => 'Pelanggaran', 'status' => 'Status'],
                'filter' => ['kind' => ['pengaduan' => 'Rekap Pengaduan', 'hukdis' => 'Hukuman Disiplin']],
                'hint'  => 'Hanya inisial nama yang ditampilkan.',
                'fields' => [
                    'kind'        => ['Jenis', 'select', 'required|in_list[pengaduan,hukdis]', 'options' => ['pengaduan' => 'Rekapitulasi Pengaduan', 'hukdis' => 'Hukuman Disiplin Hasil Pengawasan']],
                    'year'        => ['Tahun', 'year', 'required|integer'],
                    'initials'    => ['Inisial Nama', 'text', 'required|max_length[20]'],
                    'violation'   => ['Jenis Pelanggaran', 'text', 'required|max_length[255]'],
                    'description' => ['Deskripsi Singkat', 'textarea', 'permit_empty'],
                    'status'      => ['Tindak Lanjut / Status Pemeriksaan', 'text', 'permit_empty|max_length[255]'],
                    'penalty'     => ['Hukuman Disiplin', 'text', 'permit_empty|max_length[255]'],
                ],
            ],
            'akses-informasi' => [
                'table' => 'info_requests', 'title' => 'Laporan Akses Informasi', 'singular' => 'Laporan Bulanan', 'icon' => 'bi-info-circle',
                'group' => 'Layanan Publik', 'roles' => [], 'order' => ['year', 'DESC', 'month', 'DESC'], 'unique' => ['year', 'month'],
                'list'  => ['year' => 'Tahun', 'month' => 'Bulan', 'received' => 'Diterima', 'granted_full' => 'Dikabulkan', 'rejected' => 'Ditolak'],
                'fields' => [
                    'year'            => ['Tahun', 'year', 'required|integer'],
                    'month'           => ['Bulan', 'select', 'required|integer', 'options' => $months],
                    'received'        => ['Jumlah Permohonan Diterima', 'number', 'required|integer'],
                    'avg_days'        => ['Rata-rata Waktu Pemenuhan (hari)', 'number', 'permit_empty|decimal'],
                    'granted_full'    => ['Dikabulkan Seluruhnya', 'number', 'required|integer'],
                    'granted_partial' => ['Dikabulkan Sebagian', 'number', 'required|integer'],
                    'rejected'        => ['Ditolak', 'number', 'required|integer'],
                    'reject_reasons'  => ['Alasan Penolakan', 'textarea', 'permit_empty'],
                ],
            ],
        ];
    }
}
