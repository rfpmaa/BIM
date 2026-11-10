# Portal Admin/Guru

Halaman admin berada di `admin/login.html`. Setelah login, menu dipisahkan menjadi file masing-masing.

## Membuat akun admin

Buat hash password menggunakan PHP:

```powershell
php -r "echo password_hash('PasswordAdminAnda', PASSWORD_DEFAULT), PHP_EOL;"
```

Lalu masukkan akun ke tabel `admin` melalui phpMyAdmin atau MySQL:

```sql
INSERT INTO admin (nama, email, password)
VALUES ('Nama Admin', 'admin@contoh.com', 'HASH_PASSWORD_DARI_PERINTAH_PHP');
```

Tabel `admin`, `materi`, dan `progres_siswa` dibuat otomatis saat endpoint aplikasi dipanggil. Akun siswa tetap berada di tabel `user`.

## Struktur

- `admin/login.html`: login guru
- `admin/dashboard.html`: ringkasan dashboard guru
- `admin/materi.html`: tambah, ubah, dan hapus materi
- `admin/cp-tp.html`: tambah, ubah, hapus, dan publikasikan CP/TP
- `admin/latihan.html`: tambah, ubah, dan hapus soal latihan siswa
- `admin/siswa.html`: daftar dan progres siswa
- `admin/index.html`: pengarah kompatibilitas ke dashboard
- `admin_guard.php`: pengecekan role dan persiapan tabel
- `admin_login.php`: autentikasi guru
- `admin_api.php`: ringkasan siswa
- `materi_api.php`: tambah, ubah, hapus materi
- `cp_tp_api.php`: kelola dan tampilkan capaian serta tujuan pembelajaran
- `latihan_api.php`: daftar dan kelola soal latihan per modul
- `materi_public.php`: materi terpublikasi untuk siswa
- `progress_api.php`: progress belajar per akun