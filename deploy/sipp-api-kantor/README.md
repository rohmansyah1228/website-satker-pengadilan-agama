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
| Panggilan ghaib | Perkara ghaib belum putus: nomor, jenis, nama Tergugat/Termohon, sidang berikutnya (alamat tidak dikirim) | Panggilan Ghaib (relaas tetap diunggah manual) |
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

### Pengiriman otomatis
- **Cara mudah:** menu **Mode Otomatis**, pilih interval, klik **Mulai**, dan biarkan tab itu terbuka di komputer
  kantor yang selalu menyala.
- **Cara teknisi:** jadwalkan `php kirim.php` lewat cron / Task Scheduler (perintah lengkap ada di halaman Mode Otomatis).

**Menyimpan pengaturan secara permanen:** jika folder `data/` bisa ditulis, pengaturan langsung tersimpan di sana.
Jika tidak, muncul kotak **"Satu langkah lagi"** dengan tombol **Unduh config.php**. Salin file itu ke folder aplikasi
(timpa yang lama). Ulangi setiap kali pengaturan diubah.

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
