# Portal Admin/Guru

Halaman admin berada di `admin/login.html`. Setelah login, menu dipisahkan menjadi file masing-masing.

## Mengaktifkan akun guru

Daftarkan akun melalui halaman biasa, lalu ubah role akun tersebut dari phpMyAdmin atau MySQL:

```sql
UPDATE user SET role = 'guru' WHERE email = 'email-guru@contoh.com';
```

Kolom `role`, tabel `materi`, dan tabel `progres_siswa` dibuat otomatis saat endpoint aplikasi dipanggil.

## Struktur

- `admin/login.html`: login guru
- `admin/dashboard.html`: ringkasan dashboard guru
- `admin/materi.html`: tambah, ubah, dan hapus materi
- `admin/siswa.html`: daftar dan progres siswa
- `admin/index.html`: pengarah kompatibilitas ke dashboard
- `admin_guard.php`: pengecekan role dan persiapan tabel
- `admin_login.php`: autentikasi guru
- `admin_api.php`: ringkasan siswa
- `materi_api.php`: tambah, ubah, hapus materi
- `materi_public.php`: materi terpublikasi untuk siswa
- `progress_api.php`: progress belajar per akun