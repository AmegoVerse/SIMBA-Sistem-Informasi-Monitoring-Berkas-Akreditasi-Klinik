<p align="center"><img src="aset/logo.png" alt="Logo SIMBA" width="160"></p>

# SIMBA – Sistem Informasi Manajemen Berkas Akreditasi

Aplikasi web untuk mengelola berkas persiapan akreditasi: membagi Elemen Penilaian (EP) ke tim, mengunggah dokumen bukti, memverifikasi dan memvalidasinya, lalu memantau kesiapan survei dalam satu tempat. Dibuat dengan PHP dan MySQL tanpa kerangka kerja, sehingga bisa berjalan di hosting bersama biasa.

> **Status: beta (v1.0.0-beta).** Fitur lengkap, tetapi belum diuji luas di berbagai server. Uji dengan data contoh sebelum dipakai untuk akreditasi sungguhan, dan laporkan masalah lewat Issues.

## Fitur

- **Alur dokumen:** unggah → verifikasi (PJ) → validasi (Ketua), lengkap dengan pengembalian untuk revisi, pembatalan satu langkah, nomor dokumen SK/SPO, versi berkas dengan hash SHA-256, arsip, dan hapus permanen.
- **Satu dokumen untuk banyak EP:** unggah sekali, tautkan ke EP lain, termasuk lintas bab.
- **Pembagian tugas:** PJ per bab membagikan EP ke Anggota Tim, dengan tenggat dan penanda TDD (tidak dapat diterapkan).
- **Menu Verifikasi dan Validasi:** antrean dokumen untuk Super Admin (keduanya), Ketua (validasi), dan PJ (verifikasi), mengikuti hak akses peran.
- **Hak akses fleksibel:** peran bawaan (Super Admin, Ketua, PJ, Anggota Tim, Pendamping, Surveior) dan peran buatan sendiri dengan hak yang bisa dicentang.
- **Pemantauan:** dashboard progres per bab, standar, dan anggota; Perlu Tindakan; hitung mundur ke tanggal survei; dokumen yang menggantung atau segera berakhir.
- **Penilaian mandiri 10/5/0**, temuan dan tindak lanjut, diskusi dan laporan masalah pada dokumen.
- **Mode Surveior:** akun baca-saja (bisa diberi batas waktu) yang hanya melihat dokumen Valid, plus Indeks EP–Dokumen yang bisa dicetak.
- **Log Aktivitas** yang tidak dapat diubah dari aplikasi, dengan hash berantai (HMAC) untuk mendeteksi perubahan langsung ke database.
- **Pengaturan:** nama dan alamat instansi, logo, gambar latar, warna tema (HEX), tanggal survei.

## Persyaratan

PHP 7.4 atau lebih baru (disarankan 8.x) dengan ekstensi `mysqli` (mysqlnd), `mbstring`, `fileinfo`, `ctype`; MySQL atau MariaDB; dan HTTPS (sangat disarankan). Folder `uploads/` dan `media/` harus dapat ditulis.

## Instalasi

1. Buat **database MySQL kosong** lewat panel hosting Anda.
2. Unggah seluruh isi repositori ini ke folder web (termasuk berkas tersembunyi `.htaccess`).
3. Buka `https://alamat-anda/pasang.php`. Installer memeriksa persyaratan, lalu meminta data database, **nama dan alamat instansi**, dan akun Super Admin. Installer membuat `inc/config.php`, mengimpor tabel, dan mengunci dirinya sendiri.
4. **Hapus `pasang.php`** dari server.
5. Masuk, lalu di menu **Administrasi**: buat pengguna (satu PJ per bab, Anggota Tim, Ketua) dan **impor daftar EP** dari CSV. Contoh format ada di [`data/contoh_ep_klinik.csv`](data/contoh_ep_klinik.csv).

Buat satu akun Super Admin cadangan: SIMBA sengaja tidak memiliki fitur reset kata sandi, jadi Super Admin lain yang dapat mengganti kata sandi Anda.

### Setelah dipasang, uji

- Membuka `/inc/config.php` dan `/uploads/` di browser harus **ditolak** (403). Bila tidak, `.htaccess` tidak aktif di server Anda dan harus diperbaiki sebelum dipakai.
- Unggah berkas uji, lalu coba buka dengan akun yang tidak berhak.

### Pengembangan lokal

Dengan XAMPP atau sejenisnya, buka lewat `http://localhost/...`. Pengalihan paksa ke HTTPS dinonaktifkan otomatis untuk `localhost`.

## Format CSV impor EP

Baris judul dikenali otomatis. Wajib: `nomor_ep` (pola `1.1.1`) dan `bunyi_ep`. Opsional: `standar` (nama standar), `bukti`, `skor`, `jenis_telusur`, `nomor_resmi`. Bab dan standar dibuat dari nomor EP. Mengimpor ulang memperbarui EP bernomor sama tanpa menghapus penugasan atau dokumen.

Tiga bab bawaan (nomor 1–3) mengikuti struktur standar akreditasi klinik (Kepmenkes HK.01.07/MENKES/1983/2022). Jenis instansi lain dengan struktur berbeda belum didukung penuh pada versi ini.

## Keamanan

Kata sandi di-hash (`password_hash`), semua kueri memakai prepared statement, token CSRF di setiap formulir, keluaran di-escape, sesi bercookie `HttpOnly`/`SameSite`, pembatasan percobaan login dan verifikasi penjumlahan di halaman login, validasi jenis dan isi berkas unggahan, serta berkas hanya dapat diunduh lewat skrip yang memeriksa hak akses. Laporkan celah keamanan sesuai [SECURITY.md](SECURITY.md).

## Struktur folder

`inc/` pustaka inti (ditutup dari akses web) · `aset/` CSS, JS, logo · `uploads/` berkas dokumen (ditutup dari akses web) · `media/` logo dan latar kustom · `data/` contoh CSV · `install.sql` skema database · `pasang.php` installer.

## Berkontribusi

Lihat [CONTRIBUTING.md](CONTRIBUTING.md). Riwayat perubahan ada di [CHANGELOG.md](CHANGELOG.md).

## Lisensi

Kode dilisensikan di bawah [MIT](LICENSE). **Logo SIMBA** (`aset/logo.png`) tidak tercakup lisensi MIT; hak ciptanya tetap pada pemilik proyek. Pengguna dapat mengganti logo lewat menu Pengaturan.

Cincop by [Ata Lio Mego](https://github.com/AmegoVerse/).
