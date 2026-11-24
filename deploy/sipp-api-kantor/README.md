# SIPP API Kantor → Website Satker

Aplikasi kecil yang dipasang di **server kantor** (tempat aplikasi SIPP berada). Aplikasi ini **membaca** database SIPP
(hanya `SELECT`, tidak pernah mengubah apa pun) lalu **mengirim** data ke website satker secara terjadwal.
Website di hosting tidak bisa menjangkau jaringan kantor, karena itu kantor yang mengirim (push).

```
[database SIPP] ──baca──▶ [sipp-api-kantor] ──HTTPS POST (API key + tanda tangan HMAC)──▶ [website satker /api/sipp/terima]
                               │ cadangan (bila SIPP internal tidak bisa diakses)
                               └──GET──▶ [sipp-publik /api/jadwal_sidang]
```

## Data yang dikirim

| Data | Isi | Tampil di website |
|---|---|---|
| Jadwal sidang | Hari ini s.d. 14 hari ke depan: nomor & jenis perkara, jam, agenda, ruang (nama pihak **tidak** dikirim) | Halaman Jadwal Sidang & blok Beranda |
| Statistik perkara | Tahun berjalan per bulan: sisa lalu, masuk, putus, sisa; per jenis perkara | Statistik Perkara & Dashboard SINOVA |
| Panggilan ghaib | Perkara ghaib belum putus (sidang berikutnya) dan yang **sudah putus 1 tahun terakhir** (tanggal putus): nomor, jenis, nama Tergugat/Termohon (alamat tidak dikirim) + **relaas (PDF) terbaru** | Panggilan Ghaib: tabel panggilan & tabel *Sudah Putus*, tombol *Lihat Relaas (PDF)* |
| Antrian | Bebas, dari query di `config.php` (nonaktif sampai diisi) | Tersedia sebagai data JSON |

## Cara pasang

### Bila diunduh dari panel website (disarankan)
Panel Admin website → **Pengaturan → Integrasi SIPP → Unduh SIPP API Kantor**.
Paket hasil unduhan **sudah berisi alamat dan kunci website Anda**, jadi tidak perlu menyalin kunci.

1. Ekstrak dan salin folder `sipp-api-kantor/` ke web root server kantor tempat SIPP berada
   (mis. `C:\xampp\htdocs\` atau `/var/www/html/`).
   Koneksi database SIPP **diambil otomatis** dari aplikasi SIPP di server yang sama (apa pun nama foldernya),
   atau dari SIMERAK. Bila tidak ditemukan, isi lewat menu Pengaturan.
2. Buka `http://IP-SERVER/sipp-api-kantor/` di browser, lalu **buat password dashboard**.
3. Menu **Pengaturan**: periksa "Database SIPP" (tes koneksi), lalu klik **Tes koneksi website**.
4. Menu **Beranda** → **Kirim Semua Data Sekarang**. Setiap kotak menunjukkan lampu hijau (berhasil) atau merah
   beserta saran perbaikan.
5. Di website: **Pengaturan → Integrasi SIPP**, pastikan *Pakai data kiriman SIPP API kantor* = **Ya**.
   Tabel *Kiriman terakhir* menunjukkan data yang sudah diterima.

### Bila memakai paket polos (tanpa kunci)
Langkah sama, ditambah: di dashboard kantor menu **Pengaturan → Website satker**, isi alamat utama website, klik
**Simpan** (kunci dibuat otomatis) lalu **Salin**. Tempel teksnya di website: **Pengaturan → Integrasi SIPP →
Tempel kunci dari dashboard SIPP API kantor**, Simpan.

### Hanya data yang berubah yang dikirim
Setiap putaran, aplikasi menghitung sidik isi tiap jenis data dan membandingkannya dengan kiriman terakhir yang
berhasil. Jenis yang **tidak berubah tidak dikirim** (dashboard menulis *Tidak ada perubahan*); jenis yang berubah,
bertambah, atau berkurang (mis. perkara sudah putus, jadwal ditunda) dikirim. Relaas PDF hanya dikirim bila website
belum memilikinya.

- Data yang sama tetap dikirim ulang setiap `kirim_ulang_jam` jam (bawaan 6) sebagai tanda aplikasi kantor masih
  berjalan; website menandai kiriman yang berumur lebih dari 24 jam.
- Kirim walaupun tidak berubah: tombol **Kirim ulang semua** / tombol **Kirim** per jenis di dashboard, atau
  `php kirim.php --paksa`.

### Pengiriman otomatis
- **Cara mudah:** menu **Mode Otomatis**, pilih interval, klik **Mulai**, dan biarkan tab itu terbuka di komputer
  kantor yang selalu menyala.
- **Cara disarankan (tanpa browser):** jadwalkan `php kirim.php` tiap 5 menit. Perintah lengkap ada di halaman
  Mode Otomatis. Windows (Command Prompt sebagai Administrator, sekali saja):
  `schtasks /Create /TN "SIPP API Kantor" /SC MINUTE /MO 5 /RU SYSTEM /F /TR "\"C:\xampp\php\php.exe\" \"C:\xampp\htdocs\sipp-api-kantor\kirim.php\""`.
  Linux: `*/5 * * * * php /var/www/html/sipp-api-kantor/kirim.php`. Server kantor tidak perlu IP publik.

**Menyimpan pengaturan secara permanen:** jika folder `data/` bisa ditulis, pengaturan langsung tersimpan di sana.
Jika tidak, muncul kotak **"Satu langkah lagi"** dengan tombol **Unduh config.php**. Salin file itu ke folder aplikasi
(timpa yang lama). Ulangi setiap kali pengaturan diubah.

## Relaas panggilan ghaib

Relaas dicari dengan cara yang sama seperti halaman Monitoring Panggilan Ghaib di SIMERAK:

1. tabel `perkara_pelaksanaan_relaas` (kolom `doc_relaas*` dan tanggal dideteksi otomatis), hanya relaas untuk
   pihak di `perkara_pihak2` (Tergugat/Termohon), dan hanya yang berkasnya ada di disk;
2. bila tabel/kolom itu tidak ada: langsung di folder dengan pola `PA.Tlb_2026_Pdt.G_0671_relaas_*.pdf`.

Folder dokumen SIPP (`.../sipp/resources/file/doc`) dicari otomatis; bila tidak ketemu isi di
**Pengaturan → Pengaturan lanjutan → Folder dokumen SIPP**. Alurnya:

```
kantor ──panggilan_ghaib (+ sidik sha256 relaas)──▶ website
kantor ◀──"butuh_relaas": [sidik yang belum dimiliki], "maks_relaas_byte"── website
kantor ──relaas_ghaib (PDF base64, bertanda tangan)──▶ website → Panggilan Ghaib
```

- Berkas hanya dikirim bila website belum memilikinya (maks. 30 per putaran), jadi tidak membebani koneksi.
- Website memeriksa sidik sha256 dan tanda PDF sebelum menyimpan, dan **tidak pernah menimpa relaas yang diunggah
  manual** oleh admin.
- Batas ukuran: `relaas_maks_mb` (bawaan 20 MB). Hosting website harus menerima kiriman ± 28 MB
  (`post_max_size = 32M`; nginx: `client_max_body_size 32m`). Relaas yang melebihi batas hosting dilewati dan
  dicatat di dashboard.
- Matikan dengan menghilangkan centang **Kirim dokumen relaas** di Pengaturan.

## Keamanan

- Setiap kiriman diberi **API key + tanda tangan HMAC-SHA256** atas isi data dan waktu kirim. Website menolak
  kiriman yang kuncinya salah, isinya diubah, berumur lebih dari 10 menit, atau lebih lama dari data yang sudah
  diterima. Karena itu **jam server kantor harus sesuai** (sinkron NTP).
- `config.php` hasil unduhan berisi **kunci rahasia**: jangan dibagikan. Bila bocor, buat kunci baru di dashboard
  kantor (Pengaturan → Buat kunci baru) lalu tempel ulang di website, atau unduh ulang paket dari website.
- `config.php`, `lib/`, `data/`, `logs/` dilindungi `.htaccess` (Apache). Password dashboard disimpan dalam bentuk hash.
- Database SIPP hanya dibaca. Disarankan memakai akun MySQL/MariaDB khusus dengan hak `SELECT` saja.

## Masalah umum

| Gejala | Penyebab / solusi |
|---|---|
| `cURL: SSL ...` / `unable to get local issuer certificate` | PHP/OpenSSL server kantor terlalu lama. Perbarui CA bundle (`curl.cainfo` di php.ini), atau hilangkan centang "Periksa sertifikat SSL" di Pengaturan. |
| `HTTP 401 - Timestamp tidak valid` | Jam server kantor berbeda dengan hosting. Sinkronkan jam. |
| `HTTP 401 - API key / Tanda tangan tidak valid` | Kunci di kantor berbeda dengan di website. Unduh ulang paket dari website, atau salin ulang kunci. |
| `HTTP 503 - Penerima belum dikonfigurasi` | Kunci belum ditempel di website (Pengaturan → Integrasi SIPP). |
| `HTTP 409` | Jam server kantor mundur dibanding kiriman sebelumnya. Sinkronkan jam. |
| `HTTP 404` | Alamat website salah, atau website belum versi yang mendukung Integrasi SIPP (perbarui website). |
| Jadwal "Memakai cadangan sipp-publik" | Database SIPP internal tidak bisa diakses. Periksa koneksi database di Pengaturan. |

Kompatibel PHP 5.2 sampai 8.x (mysql_* / mysqli).
