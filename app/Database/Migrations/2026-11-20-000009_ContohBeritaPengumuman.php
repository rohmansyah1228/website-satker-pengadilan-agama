<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Contoh isi Beranda: Berita Pelayanan & Inovasi Satker, Pengumuman, dan Panggilan Ghaib.
 * Hanya ditambahkan bila belum ada (berdasarkan slug / nomor perkara). Silakan ubah atau hapus
 * lewat panel admin (Berita & Publikasi, Panggilan Ghaib) setelah data asli tersedia.
 */
class ContohBeritaPengumuman extends Migration
{
    public function up()
    {
        $db   = $this->db;
        $now  = time();
        $name = $this->setting('site_name') ?: 'Pengadilan Agama';
        $code = $this->setting('court_code') ?: 'PA.xxx';
        $year = date('Y', $now);
        $at   = static fn (int $daysAgo, string $time = '09:00:00') => date('Y-m-d', $now - $daysAgo * 86400) . ' ' . $time;

        $p = static fn (string ...$x) => implode('', array_map(static fn ($t) => '<p>' . $t . '</p>', $x));

        $posts = [
            // ---------------- Berita Pelayanan & Inovasi Satker ----------------
            [
                'category' => 'inovasi', 'slug' => 'notifikasi-whatsapp-otomatis-pengambilan-akta-cerai',
                'title'    => 'Implementasi Notifikasi WhatsApp Otomatis Pengambilan Akta Cerai',
                'excerpt'  => 'Memudahkan para pihak mengetahui status penerbitan produk pengadilan secara real-time tanpa harus bolak-balik ke kantor.',
                'image'    => 'assets/img/contoh/inovasi-notifikasi-whatsapp.webp', 'image_alt' => 'Inovasi Layanan: notifikasi WhatsApp akta cerai',
                'content'  => $p(
                    "{$name} menerapkan layanan notifikasi WhatsApp otomatis untuk memberi tahu para pihak ketika Akta Cerai dan salinan putusan sudah dapat diambil.",
                    'Notifikasi dikirim ke nomor yang terdaftar saat pendaftaran perkara, berisi nomor perkara, jenis produk, serta jam pelayanan pengambilan di meja PTSP. Dengan begitu, masyarakat tidak perlu datang berulang kali hanya untuk menanyakan status.',
                    '<strong>Cara memanfaatkan layanan:</strong>',
                ) . '<ol><li>Pastikan nomor WhatsApp yang dicantumkan saat pendaftaran aktif.</li><li>Tunggu pesan pemberitahuan dari nomor resmi PTSP.</li><li>Datang ke meja pengambilan produk dengan membawa KTP asli.</li></ol>'
                    . $p('Layanan ini tidak dipungut biaya. Waspadai pesan yang meminta transfer uang atas nama pengadilan, dan laporkan melalui SIWAS MARI.'),
                'published_at' => $at(1, '10:00:00'),
            ],
            [
                'category' => 'berita', 'slug' => 'sidang-di-luar-gedung-dan-layanan-terpadu-itsbat-nikah',
                'title'    => 'Sidang di Luar Gedung & Layanan Terpadu Itsbat Nikah Kecamatan Terjauh',
                'excerpt'  => 'Mendekatkan akses keadilan bagi masyarakat pencari keadilan yang terkendala jarak dan biaya transportasi.',
                'image'    => 'assets/img/contoh/sidang-keliling-itsbat.webp', 'image_alt' => 'Sidang keliling dan itsbat nikah terpadu',
                'content'  => $p(
                    "{$name} kembali melaksanakan sidang di luar gedung pengadilan (sidang keliling) bersama Kantor Urusan Agama dan Dinas Kependudukan dan Pencatatan Sipil.",
                    'Melalui layanan terpadu ini, pasangan yang telah menikah secara agama namun belum tercatat dapat mengajukan itsbat nikah, lalu langsung memperoleh buku nikah dari KUA serta Kartu Keluarga dan akta kelahiran anak dari Dukcapil pada hari yang sama.',
                    'Bagi masyarakat tidak mampu, tersedia layanan pembebasan biaya perkara (prodeo) dengan melampirkan Surat Keterangan Tidak Mampu dari desa/kelurahan. Informasi jadwal sidang keliling berikutnya diumumkan di halaman Pengumuman dan media sosial resmi.',
                ),
                'published_at' => $at(3, '13:30:00'),
            ],
            [
                'category' => 'inovasi', 'slug' => 'pojok-e-court-pendampingan-pendaftaran-perkara-elektronik',
                'title'    => 'Pojok e-Court: Pendampingan Pendaftaran Perkara Secara Elektronik',
                'excerpt'  => 'Petugas siap membantu masyarakat dan advokat mendaftar, membayar panjar, dan bersidang secara elektronik.',
                'image'    => 'assets/img/contoh/pojok-ecourt.webp', 'image_alt' => 'Pojok e-Court',
                'content'  => $p(
                    'Pojok e-Court disediakan di ruang PTSP lengkap dengan komputer dan petugas pendamping. Pengguna terdaftar maupun pengguna lain dapat dibantu membuat akun, mengunggah dokumen gugatan/permohonan, hingga membayar panjar biaya perkara secara elektronik.',
                    'Dengan e-Court, pemanggilan para pihak dan pengiriman dokumen persidangan dilakukan secara elektronik sehingga lebih cepat, hemat biaya, dan transparan.',
                ),
                'published_at' => $at(6, '09:15:00'),
            ],
            [
                'category' => 'inovasi', 'slug' => 'layanan-prioritas-disabilitas-lansia-ibu-hamil',
                'title'    => 'Layanan Prioritas bagi Penyandang Disabilitas, Lansia, dan Ibu Hamil',
                'excerpt'  => 'Antrean prioritas, kursi roda, dan pendampingan petugas tersedia di seluruh meja pelayanan.',
                'image'    => 'assets/img/contoh/layanan-prioritas.webp', 'image_alt' => 'Layanan prioritas kelompok rentan',
                'content'  => $p(
                    "Sebagai wujud pelayanan yang inklusif, {$name} menyediakan layanan prioritas bagi penyandang disabilitas, lanjut usia, ibu hamil, dan ibu menyusui.",
                    'Fasilitas yang tersedia antara lain jalur landai (ramp), kursi roda, toilet ramah disabilitas, ruang laktasi, serta petugas pendamping yang dapat dihubungi melalui meja informasi.',
                ),
                'published_at' => $at(9, '08:45:00'),
            ],
            // ---------------- Pengumuman ----------------
            [
                'category' => 'pengumuman', 'slug' => "pengumuman-rencana-umum-pengadaan-tahun-anggaran-{$year}",
                'title'    => "Pengumuman Rencana Umum Pengadaan (RUP) Tahun Anggaran {$year}",
                'excerpt'  => "Rencana Umum Pengadaan barang/jasa {$name} Tahun Anggaran {$year}.",
                'content'  => $p(
                    "Dalam rangka transparansi pengadaan barang/jasa pemerintah, {$name} mengumumkan Rencana Umum Pengadaan (RUP) Tahun Anggaran {$year}.",
                    'Rincian paket pengadaan dapat dilihat pada Sistem Informasi Rencana Umum Pengadaan (SiRUP) LKPP dan halaman Transparansi → Pengadaan Barang dan Jasa di website ini.',
                ),
                'published_at' => $at(2, '08:00:00'),
            ],
            [
                'category' => 'serta-merta', 'slug' => 'pemberitahuan-pemeliharaan-server-sipp',
                'title'    => 'Pemberitahuan Pemeliharaan Server SIPP Lokal (Sabtu, 22.00 – 24.00 WIB)',
                'excerpt'  => 'Layanan SIPP dan informasi jadwal sidang daring sementara tidak dapat diakses selama pemeliharaan.',
                'content'  => $p(
                    'Diberitahukan kepada masyarakat pencari keadilan bahwa akan dilakukan pemeliharaan server Sistem Informasi Penelusuran Perkara (SIPP) pada hari Sabtu pukul 22.00 – 24.00 WIB.',
                    'Selama pemeliharaan, penelusuran perkara dan jadwal sidang daring sementara tidak dapat diakses. Pelayanan tatap muka di PTSP pada hari kerja tidak terpengaruh. Mohon maaf atas ketidaknyamanannya.',
                ),
                'published_at' => $at(4, '15:00:00'),
            ],
            [
                'category' => 'pengumuman', 'slug' => "jadwal-pelayanan-ptsp-libur-akhir-tahun-{$year}",
                'title'    => "Jadwal Pelayanan PTSP Selama Libur Nasional dan Cuti Bersama Akhir Tahun {$year}",
                'excerpt'  => 'Pelayanan PTSP tetap dibuka sesuai jadwal piket selama cuti bersama.',
                'content'  => $p(
                    'Sehubungan dengan libur nasional dan cuti bersama akhir tahun, pelayanan PTSP tidak dibuka pada tanggal merah. Pada hari cuti bersama, pelayanan pendaftaran perkara dan pengambilan produk tetap dibuka dengan petugas piket pukul 08.00 – 12.00.',
                    'Pendaftaran perkara secara elektronik melalui e-Court tetap dapat dilakukan setiap saat.',
                ),
                'published_at' => $at(7, '10:30:00'),
            ],
        ];

        foreach ($posts as $post) {
            if ($db->table('posts')->where('slug', $post['slug'])->countAllResults()) {
                continue;
            }
            $db->table('posts')->insert($post + [
                'image' => null, 'image_alt' => null, 'status' => 'published', 'author' => 'Humas',
                'views' => 0, 'created_at' => $post['published_at'], 'updated_at' => $post['published_at'],
            ]);
        }

        // ---------------- Panggilan Ghaib (contoh — ganti dengan relaas asli) ----------------
        $ghaib = [
            ['CONTOH – Pihak Tergugat (ganti dengan data asli)', "123/Pdt.G/{$year}/{$code}", date('Y-m-d', $now + 21 * 86400)],
            ['CONTOH – Pihak Termohon (ganti dengan data asli)', "131/Pdt.G/{$year}/{$code}", date('Y-m-d', $now + 28 * 86400)],
        ];
        foreach ($ghaib as [$party, $no, $date]) {
            if ($db->table('ghaib')->where('case_number', $no)->countAllResults()) {
                continue;
            }
            $db->table('ghaib')->insert([
                'party_name' => $party, 'case_number' => $no, 'hearing_date' => $date, 'file_id' => null, 'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s', $now), 'updated_at' => date('Y-m-d H:i:s', $now),
            ]);
        }

        try {
            cache()->delete('visitor_summary');
            \App\Libraries\MenuTree::flush();
        } catch (\Throwable) {
        }
    }

    public function down()
    {
        $this->db->table('posts')->like('image', 'assets/img/contoh/', 'after')->delete();
        $this->db->table('posts')->whereIn('slug', ['pemberitahuan-pemeliharaan-server-sipp'])->delete();
        $this->db->table('posts')->like('slug', 'pengumuman-rencana-umum-pengadaan-tahun-anggaran-', 'after')->delete();
        $this->db->table('posts')->like('slug', 'jadwal-pelayanan-ptsp-libur-akhir-tahun-', 'after')->delete();
        $this->db->table('ghaib')->like('party_name', 'CONTOH –', 'after')->delete();
    }

    private function setting(string $key): string
    {
        $row = $this->db->table('settings')->where('key', $key)->get()->getRow();

        return trim((string) ($row->value ?? ''));
    }
}
