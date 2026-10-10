# Berkontribusi pada SIMBA

Terima kasih sudah ingin membantu. Beberapa pedoman singkat:

1. **Buka Issue lebih dulu** untuk perubahan besar agar arah pengerjaan disepakati.
2. **Fork**, buat cabang dari `main`, lalu ajukan Pull Request dengan deskripsi singkat dan alasan perubahan.
3. **Gaya kode:** PHP 7.4 kompatibel, semua kueri memakai prepared statement, semua keluaran di-escape dengan `e()`, setiap formulir POST memanggil `post_wajib()` dan menyertakan `csrf_field()`, dan setiap aksi memeriksa hak lewat `punya()`/`wajib()` di sisi server.
4. **Jangan menyertakan** data pasien, kata sandi, atau berkas `inc/config.php` dalam commit.
5. Pastikan `php -l` lolos untuk semua berkas (dicek otomatis oleh GitHub Actions).
6. Perubahan skema database harus disertai penyesuaian `install.sql` dan catatan di `CHANGELOG.md`.
