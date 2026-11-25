<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Jenis blok yang dapat disusun pada halaman Beranda (menu admin "Tampilan Beranda").
 *
 * type => [
 *   label, icon, description,
 *   'child'    => boleh dipakai sebagai sub-tampilan di dalam "Baris Kolom",
 *   'top'      => boleh dipakai sebagai tampilan utama,
 *   'children' => dapat berisi sub-tampilan,
 *   'fields'   => isian khusus (format sama dengan AdminResources: [label, jenis, aturan, ...opsi])
 * ]
 * Menambah jenis blok baru: tambahkan entri di sini, view di app/Views/site/blocks/{type}.php,
 * dan (bila perlu data) case baru di App\Libraries\HomeBlocks::data().
 */
class HomeBlocks extends BaseConfig
{
    public array $types = [];

    /** Ukuran awal (lebar kolom dari 12, tinggi baris) saat tampilan baru ditambahkan. */
    public array $sizes = [
        'slider' => [12, 1], 'shortcuts' => [12, 1], 'videos' => [12, 1], 'survey' => [12, 1], 'stats' => [12, 1],
        'announcements' => [6, 1], 'feed' => [6, 1], 'news' => [6, 1], 'hearings' => [8, 1], 'documents' => [6, 1], 'greetings' => [12, 1], 'achievements' => [12, 1],
        'html' => [8, 1], 'image' => [4, 1], 'card' => [4, 1], 'search' => [4, 1], 'hours' => [4, 1],
    ];

    /** (Tidak dipakai lagi sejak grid bebas — dipertahankan untuk migrasi lama.) */
    public array $regions = [
        'top'    => ['Atas (lebar penuh)', 'Bagian lebar penuh di atas, mis. slider dan pintasan layanan.'],
        'main'   => ['Kolom Utama', 'Kolom isi utama di samping sidebar, mis. pengumuman, berita, sambutan.'],
        'side'   => ['Sidebar', 'Kolom samping (kiri atau kanan), mis. pencarian, foto pimpinan, jam pelayanan, banner survei.'],
        'bottom' => ['Bawah (lebar penuh)', 'Bagian lebar penuh di bawah, mis. indeks survei, statistik perkara.'],
    ];

    /** Warna latar tampilan utama. */
    public array $backgrounds = [
        'white'   => 'Putih',
        'light'   => 'Abu-abu muda',
        'primary' => 'Hijau (warna utama)',
    ];

    /** Lebar sub-tampilan (dari 12 kolom). */
    public array $widths = [
        12 => 'Penuh (12/12)', 11 => '11/12', 10 => '10/12', 9 => '3/4 (9/12)', 8 => '2/3 (8/12)', 7 => '7/12', 6 => 'Setengah (6/12)',
        5 => '5/12', 4 => '1/3 (4/12)', 3 => '1/4 (3/12)',
    ];

    /** Garis aksen di atas kotak sub-tampilan. */
    public array $accents = [
        'primary'   => 'Hijau',
        'secondary' => 'Oranye',
        'none'      => 'Tanpa garis',
    ];

    public function __construct()
    {
        parent::__construct();
        $cats  = ['' => 'Semua kategori'] + config(SiteStructure::class)->postCategories;
        $limit = static fn (int $def, int $max) => ['Jumlah item ditampilkan', 'number', "required|integer|greater_than[0]|less_than[" . ($max + 1) . ']', 'default' => $def, 'help' => "1–{$max}"];

        $this->types = [
            'slider' => [
                'Slider Utama', 'bi-images', 'Slide besar di bagian atas. Isi slide dikelola di menu Publikasi → Slider Beranda (maks. 5).',
                'top' => true, 'child' => false, 'regions' => ['top', 'bottom'], 'fields' => [],
            ],
            'shortcuts' => [
                'Pintasan Layanan', 'bi-grid-3x3-gap', 'Kartu pintasan bergambar/ikon: SIPP, e-Court, SIWAS, Zona Integritas, aplikasi pendukung, dll. Isi dikelola di Publikasi → Pintasan Layanan.',
                'top' => true, 'child' => true, 'fields' => [
                    'group'   => ['Kelompok pintasan', 'select', 'permit_empty', 'source' => 'shortcut_groups', 'default' => '',
                        'help' => 'Pilih kelompok yang ditampilkan di blok ini (atur kelompok di Publikasi → Pintasan Layanan).'],
                    'style'   => ['Gaya tampilan', 'select', 'required|in_list[card,tile,button]', 'default' => 'card', 'options' => [
                        'card'   => 'Kartu (gambar/ikon + judul + keterangan)',
                        'tile'   => 'Gambar saja (banner, mis. Zona Integritas)',
                        'button' => 'Tombol kotak (ikon/gambar + judul, mis. aplikasi pendukung)',
                    ]],
                    'columns' => ['Kartu per baris (layar lebar)', 'select', 'required|in_list[2,3,4,5,6]', 'options' => ['2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'], 'default' => '4'],
                ],
            ],
            'videos' => [
                'Galeri Video', 'bi-camera-video', 'Video terbaru (YouTube/Drive) yang diputar di dalam website. Isi dikelola di Publikasi → Galeri Video.',
                'top' => true, 'child' => true, 'fields' => [
                    'limit'   => $limit(8, 24),
                    'columns' => ['Video per baris (layar lebar)', 'select', 'required|in_list[2,3,4]', 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'default' => '4'],
                ],
            ],
            'announcements' => [
                'Pengumuman & Panggilan Ghaib', 'bi-megaphone', 'Panggilan ghaib terdekat dan publikasi kategori Pengumuman / Informasi Serta Merta.',
                'top' => true, 'child' => true, 'fields' => [
                    'limit'      => $limit(4, 12),
                    'show_ghaib' => ['Tampilkan panggilan ghaib', 'checkbox', 'permit_empty', 'default' => 1],
                ],
            ],
            'feed' => [
                'Pengumuman Badilag & MA', 'bi-broadcast', 'Pengumuman terbaru dari Badilag dan Mahkamah Agung, diambil otomatis setiap jam dan menaut ke situs sumbernya. Sumber diatur di Pengaturan → Tautan Layanan.',
                'top' => true, 'child' => true, 'fields' => [
                    'limit'  => $limit(6, 15),
                    'source' => ['Sumber', 'select', 'permit_empty|in_list[,badilag,ma]', 'default' => '', 'options' => ['' => 'Badilag & Mahkamah Agung', 'badilag' => 'Badilag saja', 'ma' => 'Mahkamah Agung saja']],
                ],
            ],
            'news' => [
                'Berita & Publikasi', 'bi-newspaper', 'Daftar berita terbaru, dapat difilter per kategori (mis. hanya Inovasi Layanan).',
                'top' => true, 'child' => true, 'fields' => [
                    'category' => ['Kategori', 'select', 'permit_empty', 'options' => $cats, 'default' => ''],
                    'limit'    => $limit(3, 12),
                    'columns'  => ['Kolom per baris', 'select', 'required|in_list[1,2,3,4]', 'options' => ['1' => '1 (daftar)', '2' => '2', '3' => '3', '4' => '4'], 'default' => '3'],
                ],
            ],
            'hearings' => [
                'Jadwal Sidang Hari Ini', 'bi-calendar-week', 'Agenda persidangan hari ini (dari data manual atau SIPP). Bila tabel kosong, dapat menampilkan sematan (mis. slide jadwal SIPP) dari halaman Jadwal Sidang.',
                'top' => true, 'child' => true, 'fields' => [
                    'limit'          => $limit(8, 30),
                    'embed_fallback' => ['Bila jadwal kosong, tampilkan sematan dari halaman Jadwal Sidang', 'checkbox', 'permit_empty', 'default' => 1,
                        'help' => 'Memakai blok "Sematkan" pertama di halaman Layanan Hukum → Jadwal Sidang (mis. https://sipp.…/slide_sidang).'],
                ],
            ],
            'documents' => [
                'Dokumen Terbaru', 'bi-file-earmark-pdf', 'Dokumen yang terakhir diunggah, semua jenis atau satu jenis tertentu.',
                'top' => true, 'child' => true, 'fields' => [
                    'doc_type' => ['Jenis dokumen', 'select', 'permit_empty', 'source' => 'doc_types', 'default' => ''],
                    'limit'    => $limit(5, 20),
                ],
            ],
            'greetings' => [
                'Ucapan & Belasungkawa', 'bi-card-image', 'Dua kolom berdampingan: Ucapan Selamat & Belasungkawa, masing-masing memutar banner terbaru (carousel). Isi dikelola di Publikasi → Ucapan & Belasungkawa.',
                'top' => true, 'child' => true, 'fields' => [
                    'limit' => ['Banner per kolom (yang terbaru)', 'number', 'required|integer|greater_than[0]|less_than[11]', 'default' => 3, 'help' => '1–10. Mis. 3 = memutar 3 banner terbaru di tiap kolom.'],
                    'show'  => ['Kolom yang ditampilkan', 'select', 'required|in_list[both,selamat,belasungkawa]', 'default' => 'both', 'options' => [
                        'both'         => 'Keduanya (Selamat & Belasungkawa)',
                        'selamat'      => 'Ucapan Selamat saja',
                        'belasungkawa' => 'Belasungkawa saja',
                    ]],
                ],
            ],
            'achievements' => [
                'Galeri Prestasi', 'bi-trophy', 'Piagam / penghargaan satker, prestasi terbaru tampil paling awal. Isi dikelola di Publikasi → Galeri Prestasi.',
                'top' => true, 'child' => true, 'fields' => [
                    'limit' => ['Jumlah prestasi ditampilkan (yang terbaru)', 'number', 'required|integer|greater_than[0]|less_than[31]', 'default' => 6, 'help' => '1–30. Mis. 6 = 6 prestasi terbaru.'],
                    'style' => ['Bentuk tampilan', 'select', 'required|in_list[slider,grid]', 'default' => 'slider', 'options' => [
                        'slider' => 'Slider (satu piagam besar, bergantian otomatis)',
                        'grid'   => 'Kartu berjajar (beberapa piagam sekaligus)',
                    ]],
                    'caption' => ['Tampilkan keterangan di bawah piagam', 'checkbox', 'permit_empty', 'default' => 1],
                ],
            ],
            'stats' => [
                'Statistik Perkara', 'bi-bar-chart', 'Ringkasan keadaan perkara bulan terakhir yang diisi.',
                'top' => true, 'child' => true, 'fields' => [],
            ],
            'survey' => [
                'Indeks Survei Pelayanan', 'bi-emoji-smile', 'Nilai IKM, IPKP, IPAK dan tombol ke website survei (diisi di halaman Survei atau Pengaturan → Nilai Survei).',
                'top' => true, 'child' => true, 'fields' => [],
            ],
            'html' => [
                'Konten Bebas', 'bi-text-paragraph', 'Teks berformat: sambutan pimpinan, maklumat, informasi layanan, dll.',
                'top' => true, 'child' => true, 'fields' => [
                    'content' => ['Isi', 'richtext', 'permit_empty'],
                ],
            ],
            'image' => [
                'Gambar / Banner', 'bi-image', 'Gambar tunggal dengan keterangan & tautan opsional (mis. foto pimpinan, banner survei, infografis).',
                'top' => true, 'child' => true, 'fields' => [
                    'image'     => ['Gambar', 'image', 'permit_empty', 'width' => 1600],
                    'image_alt' => ['Teks alternatif gambar', 'text', 'permit_empty|max_length[255]', 'help' => 'Wajib untuk aksesibilitas: jelaskan isi gambar.'],
                    'caption'   => ['Keterangan di bawah gambar (opsional)', 'textarea', 'permit_empty|max_length[300]', 'help' => 'Mis. nama dan jabatan pimpinan. Baris baru = baris baru.'],
                    'url'       => ['Tautan (opsional)', 'url', 'permit_empty|max_length[255]'],
                ],
            ],
            'card' => [
                'Kartu Info', 'bi-postcard', 'Kartu berikon dengan judul, teks singkat, dan tombol. Dapat di kolom utama, sidebar, atau berjajar dalam Baris Kolom.',
                'top' => true, 'child' => true, 'regions' => ['main', 'side'], 'fields' => [
                    'icon'        => ['Ikon (Bootstrap Icons)', 'icon', 'permit_empty|max_length[60]', 'default' => 'bi-info-circle', 'help' => 'Contoh: bi-people, bi-clock, bi-telephone. Daftar: icons.getbootstrap.com'],
                    'text'        => ['Teks', 'textarea', 'permit_empty|max_length[600]'],
                    'button_text' => ['Teks tombol', 'text', 'permit_empty|max_length[60]'],
                    'url'         => ['Tautan tombol', 'url', 'permit_empty|max_length[255]', 'help' => 'Boleh alamat internal (/layanan-publik/ppid) atau token {sipp_url}.'],
                ],
            ],
            'row' => [
                'Baris Kolom', 'bi-layout-three-columns', 'Wadah untuk menyusun beberapa sub-tampilan berdampingan (mis. Pengumuman 5/12 + Berita 7/12).',
                'top' => false, 'child' => false, 'children' => true, 'hidden' => true, 'fields' => [],
            ],
            'search' => [
                'Kotak Pencarian', 'bi-search', 'Kolom pencarian internal.',
                'top' => true, 'child' => true, 'fields' => [],
            ],
            'hours' => [
                'Jam Pelayanan & Sidang', 'bi-clock', 'Jam pelayanan PTSP, jam istirahat, dan waktu sidang dari Pengaturan → Alamat & Kontak.',
                'top' => true, 'child' => true, 'fields' => [
                    'show_break'   => ['Tampilkan jam istirahat', 'checkbox', 'permit_empty', 'default' => 1],
                    'show_hearing' => ['Tampilkan waktu sidang', 'checkbox', 'permit_empty', 'default' => 1],
                ],
            ],
        ];
    }
}
