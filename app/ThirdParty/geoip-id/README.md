# Basis data lokasi IP Indonesia (provinsi & kota)

Diekstrak dari **DB-IP IP to City Lite** edisi 2026-06 (paket npm `@ip-location-db/dbip-city-mmdb` 2.3.2026060513),
hanya rentang IP berkode negara `ID`, untuk statistik asal kunjungan (perkiraan) tanpa mengirim IP pengunjung ke layanan luar.

Lisensi data: Creative Commons Attribution 4.0 — **IP Geolocation by DB-IP** (https://db-ip.com). Atribusi tampil di halaman Statistik Kunjungan.

Format: `geoip-id-v4.bin` (10 byte/rentang: awal 4 + akhir 4 + nomor lokasi 2, big-endian, urut), `geoip-id-v6.bin`
(34 byte/rentang: 16 + 16 + 2), `geoip-id-lokasi.json` (daftar [provinsi, kota] sesuai nomor lokasi).
