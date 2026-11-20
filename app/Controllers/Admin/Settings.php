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
                'whatsapp'         => ['WhatsApp PTSP', 'text', ''],
                'email'            => ['Email Resmi', 'email', ''],
                'website_url'      => ['Alamat Website', 'url', 'Wajib domain .go.id'],
                'office_hours'     => ['Jam Pelayanan', 'textarea', ''],
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
                'survey_url'    => ['URL Website Survei (tombol “Isi Survei Pelayanan”)', 'url', 'Mis. https://survei.badilag.net/s/… — tampil sebagai tombol di Beranda & halaman Survei.'],
                'sipp_sync'     => ['Ambil Jadwal Sidang dari Database SIPP', 'select', 'Butuh koneksi database grup "sipp" di .env dan query di app/Config/Sipp.php.', ['0' => 'Tidak (isi manual)', '1' => 'Ya']],
            ]],
            'survei' => ['Nilai Survei', [
                'ikm'           => ['Indeks Kepuasan Masyarakat (IKM)', 'text', ''],
                'ipkp'          => ['Indeks Persepsi Kualitas Pelayanan (IPKP)', 'text', ''],
                'ipak'          => ['Indeks Persepsi Anti Korupsi (IPAK)', 'text', ''],
                'survey_period' => ['Periode Survei', 'text', 'Mis. Triwulan III 2026'],
            ]],
            'keamanan' => ['Keamanan', [
                'require_2fa' => ['Wajibkan MFA untuk semua pengguna', 'select', 'SK 2722/2026 Bab II B.3: login admin dilindungi Captcha dan/atau MFA. Dinilai pada kriteria Keamanan.', ['1' => 'Ya (disarankan)', '0' => 'Tidak']],
            ]],
        ];
    }

    public function index()
    {
        $this->authorize([]);

        return $this->view('settings', ['title' => 'Pengaturan', 'groups' => self::groups(), 'values' => service('siteSettings')->all(), 'tab' => $this->request->getGet('tab') ?: 'identitas']);
    }

    public function save()
    {
        $this->authorize([]);
        $groups = self::groups();
        $tab    = (string) $this->request->getPost('_tab');
        if (! isset($groups[$tab])) {
            return redirect()->back();
        }
        if ($tab === 'keamanan') {
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
        $this->flushPublic();
        $this->log('ubah', 'Pengaturan: ' . $groups[$tab][0]);

        $r = redirect()->to(admin_url('pengaturan?tab=' . $tab));

        return $errors ? $r->with('error', implode('<br>', array_map('esc', $errors))) : $r->with('success', 'Pengaturan tersimpan.');
    }
}
