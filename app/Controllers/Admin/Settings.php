<?php

namespace App\Controllers\Admin;

use RuntimeException;

class Settings extends AdminController
{
    /** Kelompok pengaturan: [judul, [key => [label, type, help]]] */
    public static function groups(): array
    {
        return [
            'identitas' => ['Identitas Satker', [
                'site_name'        => ['Nama Satuan Kerja', 'text', 'Mis. Pengadilan Agama Sleman'],
                'site_tagline'     => ['Sub-judul', 'text', ''],
                'court_level'      => ['Tingkat Pengadilan', 'select', 'Menyesuaikan kriteria SINOVA & teks (kecamatan / kab-kota, panggilan ghaib, SIPP banding).', ['pa' => 'Tingkat Pertama (PA / MS)', 'pta' => 'Tingkat Banding (PTA / MS Aceh)']],
                'court_code'       => ['Kode Satker', 'text', 'Mis. PA.Smn'],
                'logo'             => ['Logo (PNG/WebP)', 'image', 'Logo resmi Mahkamah Agung RI.'],
                'meta_description' => ['Deskripsi Situs (SEO)', 'textarea', ''],
                'footer_note'      => ['Catatan Kaki', 'text', ''],
            ]],
            'kontak' => ['Alamat & Kontak', [
                'address_street'   => ['Nama Jalan', 'text', ''],
                'address_village'  => ['Desa/Kelurahan', 'text', ''],
                'address_district' => ['Kecamatan', 'text', ''],
                'address_city'     => ['Kabupaten/Kota', 'text', ''],
                'address_province' => ['Provinsi', 'text', ''],
                'postal_code'      => ['Kode Pos', 'text', ''],
                'phone'            => ['Telepon', 'text', ''],
                'whatsapp'         => ['WhatsApp PTSP', 'text', 'Mis. 0812 3456 7890. Tombol WhatsApp hijau tampil di kiri bawah semua halaman.'],
                'whatsapp_text'    => ['Pesan awal WhatsApp', 'text', 'Teks yang otomatis terisi saat pengunjung membuka chat. Mis. Assalamualaikum, saya ingin bertanya tentang layanan pengadilan.'],
                'email'            => ['Email Resmi', 'email', ''],
                'website_url'      => ['Alamat Website', 'url', 'Wajib domain .go.id'],
                'office_hours'     => ['Jam Pelayanan', 'textarea', 'Satu baris per hari, format "Hari: jam". Mis. Senin – Kamis: 08.00 – 16.30 WIB'],
                'break_hours'      => ['Jam Istirahat', 'textarea', 'Format sama. Mis. Senin – Kamis: 12.00 – 13.00 WIB (baris kedua: Jumat: 11.30 – 13.00 WIB)'],
                'hearing_hours'    => ['Jam Sidang', 'textarea', 'Format sama. Mis. Senin – Kamis: 09.00 WIB – selesai. Tampil di blok Jam Pelayanan Beranda, kaki halaman, dan halaman Jadwal Sidang.'],
                'map_url'          => ['Tautan Peta (Google Maps)', 'url', ''],
                'building_photo'   => ['Foto Gedung Kantor', 'image', 'Wajib (kriteria 1.5 Informasi Wajib).'],
                'facebook'         => ['Facebook', 'url', ''],
                'instagram'        => ['Instagram', 'url', ''],
                'youtube'          => ['YouTube', 'url', ''],
            ]],
            'tautan' => ['Tautan Layanan', [
                'sipp_url'      => ['URL SIPP Satker', 'url', 'Tingkat banding: https://banding.mahkamahagung.go.id/'],
                'ecourt_url'    => ['URL e-Court', 'url', ''],
                'direktori_url' => ['URL Direktori Putusan Satker', 'url', 'Tautan langsung ke direktori putusan satker Anda di putusan3.mahkamahagung.go.id'],
                'siwas_url'     => ['URL SIWAS', 'url', ''],
                'ppid_form_url' => ['URL Formulir Permohonan Informasi', 'url', ''],
                'cctv_url'      => ['URL Akses CCTV Online', 'url', 'Tautan display CCTV satker di cctv.badilag.net. Tombol CCTV tampil di kiri atas semua halaman.'],
                'cctv_image'    => ['Ikon/GIF tombol CCTV (opsional)', 'image', 'Kosongkan untuk memakai ikon kamera beranimasi bawaan. PNG/GIF/WebP, sebaiknya persegi.'],
                'survey_url'    => ['URL Website Survei (tombol “Isi Survei Pelayanan”)', 'url', 'Mis. https://survei.badilag.net/s/… — tampil sebagai tombol di Beranda & halaman Survei.'],
                'feed_badilag_url' => ['Sumber Pengumuman Badilag (otomatis)', 'url', 'Pengumuman Badilag tampil otomatis di halaman Pengumuman Badilag & MA dan blok Beranda. Kosongkan untuk mematikan. Bawaan: https://badilag.mahkamahagung.go.id/pengumuman-elektronik'],
                'feed_ma_url'      => ['Sumber Pengumuman Mahkamah Agung (otomatis)', 'url', 'Bawaan: https://www.mahkamahagung.go.id/id/pengumuman. Kosongkan untuk mematikan.'],
                'feed_since'       => ['Arsip pengumuman Badilag & MA mulai tahun', 'select', 'Pengumuman lama diambil bertahap mundur sampai tahun ini. Pengumuman yang sudah ada tidak diambil ulang.', array_combine($y = array_map('strval', range((int) date('Y'), 2012)), $y)],
            ]],
            'sipp' => ['Integrasi SIPP', [
                'sipp_api_aktif'     => ['Pakai data kiriman SIPP API kantor', 'select', 'Jadwal Sidang (halaman & Beranda), Statistik Perkara, dan Panggilan Ghaib diisi otomatis dari data yang dikirim aplikasi SIPP API di server kantor. Input manual tetap dipakai sebagai cadangan.', ['0' => 'Tidak', '1' => 'Ya']],
                'sipp_api_tempel'    => ['Tempel kunci dari dashboard SIPP API kantor', 'textarea', 'Di dashboard SIPP API kantor: Pengaturan → Website satker → klik Salin, lalu tempel seluruh teksnya di sini dan Simpan. Kosongkan bila tidak ingin mengganti kunci. Kunci yang tersimpan tidak ditampilkan lagi.'],
                'sipp_api_statistik' => ['Isi Statistik Perkara otomatis', 'select', 'Data per bulan tahun berjalan dari SIPP menimpa isian Statistik Perkara bulan yang sama.', ['1' => 'Ya', '0' => 'Tidak']],
                'sipp_api_ghaib'     => ['Isi Panggilan Ghaib otomatis', 'select', 'Perkara ghaib yang belum putus (dengan tanggal sidang berikutnya) dan yang sudah putus 1 tahun terakhir (tabel Sudah Putus) ditambahkan ke Panggilan Ghaib.', ['1' => 'Ya', '0' => 'Tidak']],
                'sipp_api_relaas'    => ['Relaas (PDF) ikut dikirim otomatis', 'select', 'Relaas terbaru Tergugat/Termohon di SIPP dipasang ke Panggilan Ghaib. Relaas yang diunggah manual tidak pernah ditimpa.', ['1' => 'Ya', '0' => 'Tidak']],
                'sipp_sync'          => ['Cara lain: baca langsung database SIPP', 'select', 'Hanya bila website satu jaringan dengan server SIPP (bukan hosting). Butuh koneksi database grup "sipp" di .env dan query di app/Config/Sipp.php.', ['0' => 'Tidak (disarankan untuk hosting)', '1' => 'Ya']],
            ]],
            'survei' => ['Nilai Survei', [
                'ikm'           => ['Indeks Kepuasan Masyarakat (IKM)', 'text', ''],
                'ipkp'          => ['Indeks Persepsi Kualitas Pelayanan (IPKP)', 'text', ''],
                'ipak'          => ['Indeks Persepsi Anti Korupsi (IPAK)', 'text', ''],
                'survey_period' => ['Periode Survei', 'text', 'Mis. Triwulan III 2026'],
            ]],
            'popup' => ['Popup Pembuka', [
                'popup_active'    => ['Tampilkan popup', 'select', 'Popup muncul saat pengunjung membuka website (mis. ucapan selamat datang, maklumat, pengumuman penting).', ['0' => 'Tidak', '1' => 'Ya']],
                'popup_where'     => ['Tampil di', 'select', '', ['home' => 'Beranda saja', 'all' => 'Semua halaman']],
                'popup_frequency' => ['Seberapa sering', 'select', 'Popup baru (gambar/judul diganti) otomatis tampil lagi untuk semua pengunjung.', ['session' => 'Sekali per kunjungan (disarankan)', 'day' => 'Sekali sehari', 'always' => 'Setiap kali halaman dibuka']],
                'popup_size'      => ['Ukuran', 'select', '', ['md' => 'Sedang — lebar maks. 720 px (disarankan)', 'sm' => 'Kecil — lebar maks. 480 px', 'lg' => 'Besar — lebar maks. 960 px']],
                'popup_image'     => ['Gambar / Banner', 'image', 'JPG/PNG/WebP. Gambar diperkecil agar pas di layar (tidak memenuhi layar).'],
                'popup_title'     => ['Judul (opsional)', 'text', 'Juga dipakai sebagai teks alternatif gambar untuk pembaca layar.'],
                'popup_text'      => ['Teks (opsional)', 'textarea', ''],
                'popup_button'    => ['Teks tombol (opsional)', 'text', 'Mis. Selengkapnya'],
                'popup_link'      => ['Tautan tombol / gambar (opsional)', 'url', ''],
            ]],
            'keamanan' => ['Keamanan', [
                'require_2fa' => ['Wajibkan MFA untuk semua pengguna', 'select', 'SK 2722/2026 Bab II B.3: login admin dilindungi Captcha dan/atau MFA. Dinilai pada kriteria Keamanan.', ['1' => 'Ya (disarankan)', '0' => 'Tidak']],
                'sk_unlock'   => ['Buka kunci halaman & menu wajib SK', 'select', 'Normalnya halaman dan menu wajib SK terkunci (slug & jenis tidak bisa diubah, tidak bisa dihapus/dipindah) agar alamat yang dinilai SINOVA tetap berlaku. Buka HANYA saat ada SK baru yang mengubah struktur, sesuaikan halaman/menunya, lalu kunci kembali. Saat terbuka, mengubah alamat halaman yang sudah dinilai dapat memengaruhi penilaian SINOVA sampai diperbarui.', ['0' => 'Terkunci (disarankan)', '1' => 'Terbuka — untuk menyesuaikan SK baru']],
            ]],
        ];
    }

    public function index()
    {
        $this->authorize([]);

        $tab  = $this->request->getGet('tab') ?: 'identitas';
        $feed = null;
        if ($tab === 'tautan') {
            $f    = new \App\Libraries\AnnouncementFeed();
            $feed = ['sources' => $f->sources(), 'status' => $f->status()];
        }

        $values = service('siteSettings')->all();
        $sipp   = null;
        if ($tab === 'sipp') {
            $this->authorize([], true);
            [$key, $secret, $from] = \App\Libraries\SippApi::credentials();
            $sipp = ['url' => site_url('api/sipp/terima'), 'from' => $from, 'hasKey' => $key !== '' && $secret !== '',
                'keyHint' => $key !== '' ? '…' . substr($key, -4) : '', 'status' => (new \App\Libraries\SippApi())->status()];
            unset($values['sipp_api_key'], $values['sipp_api_secret']);
            $values += ['sipp_api_aktif' => '0', 'sipp_api_statistik' => '1', 'sipp_api_ghaib' => '1', 'sipp_api_relaas' => '1', 'sipp_sync' => '0']; // nilai bawaan yang berlaku
        }
        if ($feed) {
            $values['feed_since'] = (string) $f->sinceYear(); // tampilkan nilai yang berlaku walau belum pernah disimpan
            foreach (\App\Libraries\AnnouncementFeed::DEFAULT as $k => [, $url]) {
                $values['feed_' . $k . '_url'] ??= $url;
            }
        }

        return $this->view('settings', ['title' => 'Pengaturan', 'groups' => self::groups(), 'values' => $values, 'tab' => $tab, 'feed' => $feed, 'sipp' => $sipp]);
    }

    /** Ambil pengumuman Badilag & MA yang belum ada sekarang (tanpa menunggu 1 jam), termasuk isi awal arsip lama. */
    public function refreshFeed()
    {
        $this->authorize([]);
        $f = new \App\Libraries\AnnouncementFeed();
        $r = redirect()->to(admin_url('pengaturan?tab=tautan'));
        try {
            $d = $f->sync(15);
        } catch (\Throwable $e) {
            return $r->with('error', esc($e->getMessage()));
        }
        $msg = [];
        foreach ($f->sources() as $k => [$label]) {
            $msg[] = $label . ': ' . (isset($d['errors'][$k]) ? 'gagal (' . $d['errors'][$k] . ')'
                : ($d['added'][$k] ?? 0) . ' pengumuman baru' . (empty($d['done'][$k]) ? ', arsip lama masih dilanjutkan (klik lagi untuk mempercepat)' : ''));
        }
        $this->flushPublic();
        $this->log('ubah', 'Ambil pengumuman Badilag & MA');

        return $d['errors'] ? $r->with('error', esc(implode(' · ', $msg))) : $r->with('success', esc(implode(' · ', $msg)));
    }

    /** Unduh aplikasi SIPP API Kantor yang sudah berisi alamat & kunci website ini (Super Admin). */
    public function downloadSippKantor()
    {
        $this->authorize([], true);
        try {
            $tmp = (new \App\Libraries\SippApi())->buildKantorZip();
        } catch (\Throwable $e) {
            return redirect()->to(admin_url('pengaturan?tab=sipp'))->with('error', esc($e->getMessage()));
        }
        $data = (string) file_get_contents($tmp);
        @unlink($tmp);
        if (setting('sipp_api_aktif') !== '1') { // aman: selama belum ada kiriman, website tetap memakai data manual
            service('siteSettings')->set(['sipp_api_aktif' => '1']);
        }
        $this->log('unduh', 'Aplikasi SIPP API Kantor (berisi kunci integrasi)');
        $host = preg_replace('/[^a-z0-9.-]+/i', '-', (string) parse_url(base_url(), PHP_URL_HOST));

        return $this->response->download('sipp-api-kantor-' . $host . '.zip', $data)
            ->setHeader('Cache-Control', 'no-store, max-age=0');
    }

    public function save()
    {
        $this->authorize([]);
        $groups = self::groups();
        $tab    = (string) $this->request->getPost('_tab');
        if (! isset($groups[$tab])) {
            return redirect()->back();
        }
        if ($tab === 'keamanan' || $tab === 'sipp') {
            $this->authorize([], true);
        }
        $values = [];
        $errors = [];
        foreach ($groups[$tab][1] as $key => [$label, $type]) {
            if ($type === 'image') {
                $f = $this->request->getFile($key);
                try {
                    if ($f && $f->getError() !== UPLOAD_ERR_NO_FILE) {
                        $values[$key] = service('uploader')->storeImage($f, $key === 'logo' ? 400 : 1600);
                        service('uploader')->deleteImage(setting($key));
                    } elseif ($this->request->getPost($key . '__remove')) {
                        service('uploader')->deleteImage(setting($key));
                        $values[$key] = '';
                    }
                } catch (RuntimeException $e) {
                    $errors[] = "{$label}: " . $e->getMessage();
                }
                continue;
            }
            $v = trim((string) $this->request->getPost($key));
            if ($key === 'sipp_api_tempel') { // kunci SIPP API: dipisah, disimpan, tidak pernah ditampilkan lagi
                if ($v !== '') {
                    $keys = \App\Libraries\SippApi::parseKeys($v);
                    if ($keys) {
                        [$values['sipp_api_key'], $values['sipp_api_secret']] = $keys;
                    } else {
                        $errors[] = "{$label}: teks tidak dikenali. Salin ulang dari dashboard SIPP API kantor (tombol Salin).";
                    }
                }
                continue;
            }
            if ($type === 'url' && $v !== '' && ! filter_var($v, FILTER_VALIDATE_URL)) {
                $errors[] = "{$label}: format URL tidak valid (awali dengan https://).";
                continue;
            }
            if ($type === 'email' && $v !== '' && ! filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "{$label}: format email tidak valid.";
                continue;
            }
            if ($type === 'select' && ! array_key_exists($v, $groups[$tab][1][$key][3])) {
                continue;
            }
            $values[$key] = mb_substr($v, 0, 2000);
        }
        service('siteSettings')->set($values);
        if ($tab === 'tautan') {
            cache()->delete('ann_feed'); // sumber / batas tahun mungkin berubah → ambil lagi saat halaman dibuka
        }
        $this->flushPublic();
        $this->log('ubah', 'Pengaturan: ' . $groups[$tab][0]);

        $r = redirect()->to(admin_url('pengaturan?tab=' . $tab));

        return $errors ? $r->with('error', implode('<br>', array_map('esc', $errors))) : $r->with('success', 'Pengaturan tersimpan.');
    }
}
