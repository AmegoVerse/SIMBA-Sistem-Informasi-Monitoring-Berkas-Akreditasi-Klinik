# Kebijakan Keamanan

## Melaporkan celah keamanan

Jangan membuka Issue publik untuk celah keamanan. Gunakan **Security → Report a vulnerability** (pelaporan pribadi GitHub) pada repositori ini, dan sertakan langkah untuk mereproduksi, versi SIMBA, serta versi PHP/MySQL yang dipakai.

Kami berusaha menanggapi laporan dalam waktu yang wajar dan akan menyebut pelapor pada catatan rilis bila diinginkan.

## Versi yang didukung

Hanya rilis terbaru yang menerima perbaikan keamanan.

## Catatan untuk pengguna

- Hapus `pasang.php` setelah instalasi.
- Pastikan `/inc/` dan `/uploads/` tidak dapat dibuka lewat browser.
- Bukti akreditasi dapat memuat data pasien. Samarkan identitas pasien sebelum mengunggah dan patuhi peraturan pelindungan data pribadi yang berlaku.
- Simpan backup di tempat aman; berkas backup memuat data sensitif.
