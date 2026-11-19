# Panduan Kontribusi

Terima kasih ingin ikut mengembangkan Website Satker Peradilan Agama! Kontribusi dari tim IT satker mana pun sangat diharapkan.

## Alur kerja

1. *Fork* repositori, buat cabang dari `main`: `git checkout -b fitur/nama-fitur`.
2. Pasang lingkungan lokal (lihat README → Instalasi di Windows/XAMPP).
3. Buat perubahan kecil dan terfokus. Perubahan skema database **wajib** lewat migration baru (`php spark make:migration`), jangan mengubah migration lama.
4. Pastikan `php -l` bersih dan fitur diuji manual di browser (desktop & ponsel).
5. Kirim *Pull Request* dengan penjelasan: apa yang berubah, mengapa, dan tangkapan layar bila menyangkut tampilan.

## Pedoman kode

- Ikuti gaya CodeIgniter 4 / PSR-12 yang sudah ada.
- Semua keluaran di view memakai `esc()`; isi editor teks kaya dibersihkan dengan `service('purifier')`.
- Jangan menambah CDN. Pustaka front-end di-host lokal di `public/assets/vendor/` dan harus berlisensi terbuka.
- Pertahankan aksesibilitas: label form, teks alternatif gambar, navigasi keyboard, kontras warna.
- Teks antarmuka dalam Bahasa Indonesia.
- Warna resmi `#056e4f` / `#ff9900` tidak boleh diubah (SK Bab III huruf B).

## Ide kontribusi

- Integrasi SIPP yang sudah teruji untuk versi SIPP tertentu (`app/Config/Sipp.php`).
- Modul baru sesuai kebutuhan satker (lihat README → Menambah modul data).
- Terjemahan pesan, perbaikan aksesibilitas, pengujian otomatis.

Dengan mengirim kontribusi, Anda setuju kontribusi tersebut dirilis di bawah lisensi MIT proyek ini.
