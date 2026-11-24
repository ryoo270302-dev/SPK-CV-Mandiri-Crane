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
6. Muat data hasil kuisioner penelitian (lihat bagian "Data Kuisioner" di bawah):
   ```
   C:\xampp\php\php.exe database\seed_kuisioner.php
   ```
7. Buka `http://localhost/spk-crane/login.php` di peramban (Chrome/Edge/Firefox terbaru).

### Menjalankan cepat tanpa Apache (opsional, untuk pengembangan)

```
cd spk-crane
php -S localhost:8000
```
lalu buka `http://localhost:8000/login.php`. Cara ini tetap membutuhkan MySQL
(dari XAMPP) yang sedang berjalan di `localhost:3306`.

## Data Kuisioner

`database/seed_kuisioner.php` memasukkan data kuisioner responden **Basuki**
(Kepala Operasional Pool, diisi 7 November 2026) ke basis data:

| Bagian kuisioner | Masuk ke | Isi |
|---|---|---|
| B | `cranes` | 5 unit crane (kapasitas, tahun, harga sewa, jangkauan) |
| C + E1 | `scores` | 35 skor (5 crane x 7 kriteria). C5-C7 dari penilaian responden; C1-C4 dikonversi dari spesifikasi memakai rentang E1 yang sudah dikonfirmasi |
| D | sesi perhitungan | 21 perbandingan berpasangan, lalu AHP + TOPSIS dijalankan otomatis |

Skrip ini idempoten (aman diulang) dan memakai mesin hitung aplikasi sendiri.
Usia alat dihitung dari tahun acuan 2026. Nama unit yang sudah ada di basis
data tidak ditimpa.

### Unit tambahan (27 unit dari `data_manajemen_crane.html`)

`database/seed_crane_tambahan.php` menambahkan 27 unit lain. Kode di file
sumber bentrok dengan 5 unit kuisioner, jadi unit kuisioner dipertahankan dan
unit tambahan diberi kode **CR-06 s/d CR-32** (nomor sumber + 5). Skor C1-C4
dikonversi dari spesifikasi dengan rentang E1; **skor C5-C7 adalah estimasi**
(kondisi dari tahun, mobilitas dari jenis dan kapasitas, ketersediaan dari
status) dan perlu diganti penilaian responden lewat menu Penilaian. Status
"Terbatas"/"Sangat Terbatas" dicatat di keterangan dan tercermin pada C7.

Urutan menjalankan: `seed_kuisioner.php` dulu, lalu `seed_crane_tambahan.php`.
Sesi "Kuisioner Basuki" selalu hanya memuat 5 unit kuisioner.

Hasil dengan data kuisioner: CR = 0,0122 (konsisten); TOPSIS peringkat 1 = CR-03
XCMG 80T (V = 0,7329), AHP peringkat 1 = CR-03 juga; Spearman = 0,9000.

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
