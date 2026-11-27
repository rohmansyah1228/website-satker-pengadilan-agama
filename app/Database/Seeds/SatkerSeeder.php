<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\SiteStructure;

/**
 * Data awal: pengaturan, halaman & menu wajib, jenis dokumen, slider, pintasan,
 * dan akun Super Admin. Aman dijalankan sekali pada instalasi baru.
 *
 *   php spark db:seed SatkerSeeder
 */
class SatkerSeeder extends Seeder
{
    public function run()
    {
        $s   = config(SiteStructure::class);
        $now = date('Y-m-d H:i:s');

        // ---------------- Pengaturan ----------------
        $settings = [
            'site_name'        => 'Pengadilan Agama Contoh',
            'site_tagline'     => 'Mahkamah Agung Republik Indonesia',
            'court_level'      => 'pa',
            'court_code'       => 'PA.Cth',
            'logo'             => '',
            'meta_description' => 'Portal Resmi Pengadilan Agama Contoh — informasi perkara, layanan hukum, dan keterbukaan informasi publik.',
            'address_street'   => 'Jl. Keadilan Utama No. 1',
            'address_village'  => 'Kelurahan Merdeka',
            'address_district' => 'Kecamatan Contoh Utara',
            'address_city'     => 'Kota Contoh',
            'address_province' => 'Provinsi Contoh',
            'postal_code'      => '10110',
            'phone'            => '(021) 1234567',
            'whatsapp'         => '',
            'email'            => 'info@pa-contoh.go.id',
            'website_url'      => 'https://www.pa-contoh.go.id',
            'building_photo'   => '',
            'map_url'          => '',
            'office_hours'     => "Senin – Kamis: 08.00 – 16.30 WIB\nJumat: 08.00 – 17.00 WIB",
            'break_hours'      => "Senin – Kamis: 12.00 – 13.00 WIB\nJumat: 11.30 – 13.00 WIB",
            'hearing_hours'    => 'Senin – Kamis: 09.00 WIB – selesai',
            'facebook'         => '',
            'instagram'        => '',
            'youtube'          => '',
            'sipp_url'         => '',
            'ecourt_url'       => 'https://ecourt.mahkamahagung.go.id',
            'direktori_url'    => '',
            'siwas_url'        => 'https://siwas.mahkamahagung.go.id',
            'survey_url'       => '',
            'ppid_form_url'    => '',
            'ikm'              => '',
            'ipkp'             => '',
            'ipak'             => '',
            'survey_period'    => '',
            'require_2fa'      => '1',
            'footer_note'      => '',
            'sipp_sync'        => '0',
        ];
        foreach ($settings as $k => $v) {
            $this->db->table('settings')->ignore(true)->insert(['key' => $k, 'value' => $v]);
        }

        // ---------------- Jenis dokumen ----------------
        $i = 0;
        foreach ($s->docTypes as $code => [$name, $section, $period, $min]) {
            $this->db->table('doc_types')->ignore(true)->insert([
                'code' => $code, 'name' => $name, 'section' => $section, 'period' => $period,
                'min_years' => $min, 'is_required' => 1, 'sort' => ++$i,
            ]);
        }

        // ---------------- Halaman wajib + menu ----------------
        $content = $this->defaultContent();
        $menu    = $this->db->table('menus');
        $menu->insert(['label' => 'Beranda', 'type' => 'route', 'url' => '/', 'sort' => 1, 'is_required' => 1, 'is_active' => 1]);

        $sort = 1;
        foreach ($s->requiredPages as $section => $pages) {
            $menu->insert([
                'label' => $s->sections[$section]['label'], 'type' => 'section', 'section' => $section,
                'sort' => ++$sort, 'is_required' => 1, 'is_active' => 1,
            ]);
            $parentId = $this->db->insertID();
            $child    = 0;
            foreach ($pages as $slug => [$title, $module, $param]) {
                $this->db->table('pages')->insert([
                    'section' => $section, 'slug' => $slug, 'title' => $title,
                    'content' => $content["{$section}/{$slug}"] ?? '',
                    'module' => $module, 'module_param' => $param, 'is_required' => 1,
                    'status' => 'published', 'created_at' => $now, 'updated_at' => $now,
                ]);
                $menu->insert([
                    'parent_id' => $parentId, 'label' => $title, 'type' => 'page', 'page_id' => $this->db->insertID(),
                    'sort' => ++$child, 'is_required' => 1, 'is_active' => 1,
                ]);
            }
        }
        $menu->insert(['label' => 'Berita & Publikasi', 'type' => 'route', 'url' => '/berita', 'sort' => ++$sort, 'is_required' => 1, 'is_active' => 1]);

        // ---------------- Pintasan beranda ----------------
        $shortcuts = [
            ['SIPP', 'Sistem Informasi Penelusuran Perkara & Jadwal Sidang', 'bi-search', '{sipp_url}'],
            ['e-Court MA RI', 'Pendaftaran, Pembayaran & Persidangan Elektronik', 'bi-laptop', '{ecourt_url}'],
            ['Direktori Putusan', 'Akses Salinan Putusan Pengadilan', 'bi-journal-bookmark-fill', '{direktori_url}'],
            ['SIWAS MARI', 'Sistem Pengawasan & Pengaduan Masyarakat', 'bi-shield-exclamation', '{siwas_url}'],
        ];
        foreach ($shortcuts as $n => [$t, $d, $ic, $u]) {
            $this->db->table('shortcuts')->insert(['title' => $t, 'description' => $d, 'icon' => $ic, 'url' => $u, 'sort' => $n + 1, 'is_active' => 1]);
        }

        // ---------------- Slider (maks. 5) ----------------
        $img     = $this->placeholderImages();
        $sliders = [
            ['PORTAL PELAYANAN DIGITAL PERADILAN AGAMA', 'Pelayanan Peradilan yang Cepat, Transparan & Akuntabel',
                'Akses informasi jadwal persidangan, pendaftaran perkara secara elektronik (e-Court), dan keterbukaan informasi publik dalam satu pintu.',
                'Cek Jadwal Sidang', '/layanan-hukum/jadwal-sidang', 'Rincian Biaya Perkara', '/layanan-hukum/biaya-perkara'],
            ['BERPERKARA SECARA ELEKTRONIK', 'Pendaftaran Perkara Mudah Melalui e-Court',
                'Daftarkan perkara, bayar panjar secara virtual, dan ikuti persidangan secara elektronik tanpa harus antre di pengadilan.',
                'Panduan & Tautan e-Court', '/layanan-hukum/ecourt', '', ''],
            ['PERADILAN INKLUSIF', 'Layanan Ramah Kelompok Rentan & Disabilitas',
                'Kami menyediakan sarana prasarana aksesibel, pendampingan khusus, serta bantuan hukum cuma-cuma (Prodeo & Posbakum).',
                'Lihat Fasilitas Layanan', '/layanan-publik/disabilitas', '', ''],
        ];
        foreach ($sliders as $n => [$b, $t, $sub, $bt, $bu, $b2t, $b2u]) {
            $this->db->table('sliders')->insert([
                'badge' => $b, 'title' => $t, 'subtitle' => $sub, 'image' => $img[$n] ?? null, 'image_alt' => $t,
                'button_text' => $bt, 'button_url' => $bu, 'button2_text' => $b2t, 'button2_url' => $b2u,
                'sort' => $n + 1, 'is_active' => 1,
            ]);
        }

        // ---------------- Super Admin ----------------
        if ($this->db->table('users')->countAllResults() === 0) {
            $password = env('SEED_ADMIN_PASSWORD') ?: bin2hex(random_bytes(6)) . 'A!';
            $this->db->table('users')->insert([
                'username' => 'admin', 'name' => 'Super Admin', 'email' => '',
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'superadmin', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            if (is_cli()) {
                echo "\n  Akun Super Admin dibuat → username: admin | password: {$password}\n";
                echo "  Segera ganti password dan aktifkan 2FA setelah login pertama.\n\n";
            }
        }
    }

    /** Gambar latar slider sederhana (WebP) agar tidak bergantung layanan luar. */
    private function placeholderImages(): array
    {
        $dir = FCPATH . 'uploads/images/default/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $out    = [];
        $colors = [[5, 110, 79], [3, 77, 55], [4, 94, 67]];
        foreach ($colors as $n => [$r, $g, $b]) {
            $ext  = \App\Libraries\Uploader::webpSupported() ? 'webp' : 'jpg';
            $file = $dir . 'slide-' . ($n + 1) . '.' . $ext;
            if (! is_file($file) && function_exists('imagecreatetruecolor')) {
                $im = imagecreatetruecolor(1600, 700);
                for ($x = 0; $x < 1600; $x += 4) {
                    $f = $x / 1600;
                    $c = imagecolorallocate($im, (int) ($r + (255 - $r) * 0.10 * $f), (int) ($g + 30 * $f), (int) ($b + 20 * $f));
                    imagefilledrectangle($im, $x, 0, $x + 3, 699, $c);
                }
                $line = imagecolorallocatealpha($im, 255, 153, 0, 110);
                for ($k = -700; $k < 1600; $k += 60) {
                    imageline($im, $k, 700, $k + 700, 0, $line);
                }
                $ext === 'webp' ? imagewebp($im, $file, 70) : imagejpeg($im, $file, 80);
                imagedestroy($im);
            }
            $out[] = 'uploads/images/default/slide-' . ($n + 1) . '.' . $ext;
        }

        return $out;
    }

    /**
     * Konten awal. Teks bertanda [LENGKAPI] wajib diganti — dashboard kepatuhan
     * menandai halaman tersebut belum lengkap selama penanda masih ada.
     */
    private function defaultContent(): array
    {
        $proc = '';
        foreach (['Prosedur Berperkara Tingkat Pertama', 'Prosedur Upaya Hukum Banding', 'Prosedur Upaya Hukum Kasasi',
            'Prosedur Peninjauan Kembali', 'Prosedur Pengambilan Produk Pengadilan', 'Prosedur Gugatan Sederhana'] as $n => $t) {
            $proc .= '<h3>' . ($n + 1) . ". {$t}</h3><p>[LENGKAPI] Uraikan alur dan persyaratan {$t}.</p>";
        }

        return [
            'profil/visi-misi' => '<h3>Visi</h3><blockquote><p>Terwujudnya Badan Peradilan Indonesia yang Agung</p></blockquote>'
                . '<h3>Misi</h3><ol><li>Menjaga kemandirian badan peradilan.</li><li>Memberikan pelayanan hukum yang berkeadilan kepada pencari keadilan.</li>'
                . '<li>Meningkatkan kualitas kepemimpinan badan peradilan.</li><li>Meningkatkan kredibilitas dan transparansi badan peradilan.</li></ol>',
            'profil/tupoksi' => '<h3>Tugas Pokok</h3><p>Berdasarkan Undang-Undang Nomor 7 Tahun 1989 tentang Peradilan Agama sebagaimana telah diubah dengan Undang-Undang Nomor 3 Tahun 2006 dan Undang-Undang Nomor 50 Tahun 2009, Pengadilan Agama bertugas dan berwenang memeriksa, memutus, dan menyelesaikan perkara di tingkat pertama antara orang-orang yang beragama Islam di bidang: perkawinan, waris, wasiat, hibah, wakaf, zakat, infaq, shadaqah, dan ekonomi syari\'ah.</p>'
                . '<h3>Fungsi</h3><p>[LENGKAPI] Uraikan fungsi mengadili, pembinaan, pengawasan, nasehat, administratif, dan fungsi lainnya.</p>',
            'profil/yurisdiksi' => '<p>Wilayah hukum meliputi daerah-daerah berikut. Peta wilayah yurisdiksi ditampilkan di bawah.</p>',
            'profil/struktur' => '<p>[LENGKAPI] Unggah diagram struktur organisasi (gambar halaman) yang memuat nama pejabat dan nama jabatan. Staf dan tenaga honorer tidak perlu ditampilkan.</p>',
            'profil/pegawai' => '<p>Data profil aparatur bersumber dari SIMTEPA Badilag.</p>',
            'layanan-hukum/jadwal-sidang' => '<p>Jadwal persidangan harian. Pilih tanggal untuk melihat agenda sidang.</p>',
            'layanan-hukum/biaya-perkara' => '<p>Rincian panjar biaya perkara dan radius biaya panggilan berdasarkan Surat Keputusan Ketua Pengadilan.</p>',
            'layanan-hukum/prosedur-berperkara' => '<p>Panduan alur pendaftaran hingga penyelesaian perkara:</p>' . $proc,
            'layanan-hukum/hak-pencari-keadilan' => '<p>Setiap pencari keadilan berhak mendapatkan bantuan hukum berupa layanan pembebasan biaya perkara (Prodeo) bagi masyarakat tidak mampu serta layanan konsultasi dan pembuatan gugatan secara cuma-cuma melalui Pos Bantuan Hukum (Posbakum).</p>',
            'layanan-hukum/panggilan-ghaib' => '<p>Daftar pemanggilan para pihak yang tidak diketahui alamatnya (ghaib).</p>',
            'layanan-hukum/mediasi' => '<p>Mediasi adalah cara penyelesaian sengketa melalui proses perundingan untuk memperoleh kesepakatan para pihak dengan dibantu oleh mediator, sesuai Peraturan Mahkamah Agung Nomor 1 Tahun 2016.</p><p>[LENGKAPI] Daftar mediator dan jadwal layanan mediasi.</p>',
            'layanan-hukum/statistik-perkara' => '<p>Keadaan perkara per bulan: sisa bulan lalu, perkara masuk, perkara putus, dan sisa perkara.</p>',
            'layanan-hukum/ecourt' => '<p><strong>e-Court Mahkamah Agung RI</strong> adalah layanan bagi Pengguna Terdaftar dan Pengguna Lain untuk Pendaftaran Perkara Secara Online (e-Filing), mendapatkan Taksiran Panjar Biaya secara elektronik (e-SKUM), Pembayaran secara elektronik (e-Payment), Pemanggilan secara elektronik (e-Summons), dan Persidangan secara elektronik (e-Litigation).</p>',
            'transparansi/lhkpn' => '<p>Daftar nama wajib lapor, tahun penerbitan LHKPN terakhir, dan tautan dokumen Ikhtisar Kekayaan.</p>',
            'layanan-publik/maklumat-pelayanan' => '<blockquote><p>"Dengan ini kami menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan dan apabila tidak menepati janji ini, kami siap menerima sanksi sesuai peraturan perundang-undangan yang berlaku."</p></blockquote>',
            'layanan-publik/ppid' => '<p>Layanan informasi publik diselenggarakan sesuai Keputusan Ketua Mahkamah Agung Nomor 2-144/KMA/SK/VIII/2022 tentang Standar Pelayanan Informasi Publik di Pengadilan.</p>',
            'layanan-publik/pengaduan' => '<p>Laporkan dugaan pelanggaran perilaku aparatur pengadilan secara rahasia melalui Sistem Informasi Pengawasan Mahkamah Agung RI (SIWAS).</p>',
            'layanan-publik/hukuman-disiplin' => '<p>Rekapitulasi hukuman disiplin hasil pengawasan.</p>',
            'layanan-publik/disabilitas' => '<p>Fasilitas yang tersedia: jalur pemandu (guiding block), kursi roda, ruang tunggu prioritas, ruang sidang ramah anak dan perempuan, petugas pendamping disabilitas, serta fitur aksesibilitas website (pembaca layar, kontras tinggi, dan pembesar teks).</p>',
            'layanan-publik/survei' => '<p>Nilai dan laporan Survei Kepuasan Masyarakat (SKM), Survei Persepsi Anti Korupsi (SPAK), dan Survei Persepsi Kualitas Pelayanan (SPKP).</p>',
        ];
    }
}
