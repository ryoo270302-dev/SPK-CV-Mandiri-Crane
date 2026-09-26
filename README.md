# SPK Crane - AHP & TOPSIS

Sistem Pendukung Keputusan (SPK) berbasis web untuk menentukan rekomendasi
crane terbaik pada **CV Mandiri Crane**, menggunakan dan membandingkan dua
metode pengambilan keputusan multikriteria: **AHP** (Analytical Hierarchy
Process) dan **TOPSIS** (Technique for Order Preference by Similarity to
Ideal Solution).

Dibuat mengacu pada PRD "Sistem Rekomendasi Crane Terbaik Berbasis Web
dengan Metode AHP dan TOPSIS" (skripsi Panca Rio Nugroho, NIM 221011401141,
Teknik Informatika, Universitas Pamulang).

## Teknologi

- **Backend:** PHP 8 (murni, pola MVC sederhana tanpa framework), PDO + prepared statement
- **Basis data:** MySQL/MariaDB (XAMPP)
- **Frontend:** Bootstrap 5 (disertakan secara lokal di `assets/bootstrap/`, tanpa CDN, agar aplikasi dapat berjalan sepenuhnya offline sesuai NFR-06)

## Instalasi (XAMPP)

1. Salin folder project ini ke `C:\xampp\htdocs\spk-crane` (Windows) atau
   `/opt/lampp/htdocs/spk-crane` (Linux/XAMPP).
2. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
3. Buka phpMyAdmin (`http://localhost/phpmyadmin`), buat database baru atau
   langsung import file `database/spk_crane.sql` (database `spk_crane` akan
   dibuat otomatis oleh script tersebut).
4. Jika kredensial MySQL Anda bukan default (`root` tanpa password), sesuaikan
   `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'spk_crane');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
5. Jika nama folder project di `htdocs` BUKAN `spk-crane`, sesuaikan juga:
   ```php
   define('BASE_URL', '/spk-crane');
   ```
6. Buka `http://localhost/spk-crane/login.php` di peramban (Chrome/Edge/Firefox terbaru).

### Menjalankan cepat tanpa Apache (opsional, untuk pengembangan)

```
cd spk-crane
php -S localhost:8000
```
lalu buka `http://localhost:8000/login.php`. Cara ini tetap membutuhkan MySQL
(dari XAMPP) yang sedang berjalan di `localhost:3306`.

## Akun Default

| Username | Password  | Role   |
|----------|-----------|--------|
| admin    | admin123  | admin  |
| viewer   | viewer123 | viewer |

**Segera ganti password default** setelah login pertama kali, melalui menu
nama pengguna di pojok kanan atas → **Ubah Password**.

## Alur Penggunaan

1. Login sebagai **admin**.
2. Kelola **Kriteria** (menu "Kriteria") — pastikan tipe benefit/cost sudah
   sesuai kesepakatan dengan pihak CV.
3. Kelola **Data Crane** (menu "Data Crane") sebagai alternatif yang akan dinilai.
4. Isi **Penilaian** (menu "Penilaian") — skor tiap crane terhadap tiap
   kriteria aktif, skala 1–5.
5. Buka **Hitung Rekomendasi** (menu ini) — isi matriks perbandingan
   berpasangan antar kriteria (skala Saaty 1–9), lalu klik "Hitung AHP & TOPSIS".
   Sistem otomatis menghitung bobot AHP (dengan uji konsistensi CR ≤ 0.1),
   peringkat alternatif AHP, peringkat alternatif TOPSIS (memakai bobot AHP),
   dan perbandingan kedua metode (termasuk korelasi Spearman) dalam satu sesi.
6. Lihat hasil lengkap di halaman **Hasil AHP**, **Hasil TOPSIS**, dan
   **Perbandingan**, atau cetak sebagai laporan PDF via tombol "Cetak" (memakai
   fitur cetak peramban → Save as PDF).
7. Semua sesi tersimpan di menu **Riwayat** dan dapat dibuka atau dihapus kembali.

Pengguna dengan role **viewer** hanya dapat melihat dashboard, hasil,
perbandingan, riwayat, dan laporan (tidak dapat mengubah data).

## Struktur Folder

```
spk-crane/
├── config/          # koneksi database, konfigurasi, auth/session guard
├── database/        # skema SQL + data contoh
├── models/          # akses data (query) per entitas
├── services/        # mesin perhitungan murni: ahp.php, topsis.php, comparison.php
├── views/layout/     # header, footer, halaman 403
├── crane/, criteria/, scores/, ahp/, topsis/, comparison/,
│   report/, history/, users/, profile/   # halaman per modul
├── assets/          # Bootstrap 5 (lokal) + CSS kustom
├── index.php        # dashboard
├── login.php / logout.php
```

## Verifikasi Perhitungan (NFR-01)

Logika AHP dan TOPSIS ditulis terpisah di `services/ahp.php` dan
`services/topsis.php` sehingga dapat diuji secara terpisah dari tampilan,
misalnya untuk dibandingkan dengan perhitungan manual/Excel pada lampiran
skripsi. Seluruh nilai ditampilkan dengan pembulatan 4 angka desimal.

## Catatan Keamanan

- Password disimpan dengan `password_hash()` (bcrypt).
- Semua query menggunakan PDO prepared statement (anti SQL injection).
- Form penting dilindungi token CSRF.
- Ganti akun default sebelum digunakan pada data produksi/asli.

## Di Luar Ruang Lingkup

Sesuai PRD, aplikasi ini tidak mencakup: analisis teknis keselamatan/struktur
crane, integrasi ERP/akuntansi/GPS/IoT, modul transaksi sewa/pembayaran/presensi,
maupun aplikasi mobile native.
