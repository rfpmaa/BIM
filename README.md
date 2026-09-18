# BIM Learn

Aplikasi pembelajaran BIM untuk siswa dan guru.

## Struktur Folder

```text
BIM/
|- user/                 Halaman siswa
|  |- index.html         Beranda
|  |- login.html         Login dan registrasi siswa
|  |- modul.html         Daftar modul
|  |- video.html         Materi/video
|  |- latihan.html       Latihan
|  |- progres.html       Progress siswa
|  |- profil.html        Profil siswa
|  `- simulasi.html      Simulasi 3D
|- admin/                Portal guru
|  |- login.html         Login guru
|  |- dashboard.html     Ringkasan dashboard
|  |- materi.html        Kelola materi
|  |- siswa.html         Lihat progres siswa
|  `- admin.js           Navigasi dan fungsi bersama admin
|- style.css             Gaya bersama halaman siswa dan admin
|- script.js             Fungsi bersama halaman siswa
|- *_api.php             Endpoint data aplikasi
|- login.php             Endpoint login siswa
|- register.php          Endpoint registrasi
|- logout.php            Endpoint logout
|- koneksi.php           Koneksi database dan session
`- index.html            Pengarah ke user/index.html
```

## URL Utama

- Siswa: `user/index.html`
- Login siswa: `user/login.html`
- Guru: `admin/login.html`

Endpoint PHP tetap berada di root agar path API terpusat dan mudah dirawat.
