# Kebijakan Keamanan

Website pengadilan adalah layanan publik. Mohon **jangan** melaporkan celah keamanan melalui *issue* publik.

## Melaporkan celah keamanan

Gunakan fitur **Security → Report a vulnerability** (GitHub Private Vulnerability Reporting) pada repositori ini. Sertakan:

- versi / commit yang terdampak,
- langkah reproduksi dan dampaknya,
- saran perbaikan bila ada.

Laporan akan ditanggapi secepatnya. Setelah perbaikan dirilis, pelapor akan dicantumkan dalam catatan rilis (bila berkenan).

## Versi yang didukung

Hanya rilis terbaru di cabang `main` yang menerima pembaruan keamanan. Selalu jalankan `git pull`, `composer install`, dan `php spark migrate` secara berkala.

## Tanggung jawab satker

Keamanan aplikasi hanya separuh dari ketentuan SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 Bab II. Pastikan juga checklist server di README terpenuhi (HTTPS, TLS ≥ 1.2, WAF, database hanya `127.0.0.1`, versi PHP/OS tidak EOL, backup offsite).
