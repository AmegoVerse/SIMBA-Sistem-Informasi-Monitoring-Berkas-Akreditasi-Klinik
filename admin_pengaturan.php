<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (!admin_()) tolak();
const WARNA_BAWAAN = ['warna_utama' => '#06539f', 'warna_sekunder' => '#0a6f4a'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  $baru = []; $lama = [];
  try {
    $nm = trim((string)($_POST['nama_instansi'] ?? ''));
    if ($nm === '' || mb_strlen($nm) > 120) throw new Pesan('Nama instansi wajib diisi (maksimal 120 karakter).');
    $al = trim((string)($_POST['alamat_instansi'] ?? ''));
    if (mb_strlen($al) > 250) throw new Pesan('Alamat maksimal 250 karakter.');
    $wu = strtolower(trim((string)($_POST['warna_utama'] ?? ''))); $ws = strtolower(trim((string)($_POST['warna_sekunder'] ?? '')));
    if (!empty($_POST['reset_warna'])) { $wu = WARNA_BAWAAN['warna_utama']; $ws = WARNA_BAWAAN['warna_sekunder']; }
    if (!hex_ok($wu) || !hex_ok($ws)) throw new Pesan('Warna harus berformat HEX enam digit, contoh #06539f.');
    $ts = tgl_valid($_POST['tanggal_survei'] ?? '') ?? '';
    $hr = (int)($_POST['hari_menggantung'] ?? 7); if ($hr < 1 || $hr > 60) throw new Pesan('Batas hari menggantung 1-60.');
    $am = trim((string)($_POST['ambang_nilai'] ?? '')); if ($am !== '' && (!ctype_digit($am) || (int)$am > 100)) throw new Pesan('Target capaian berupa angka 0-100 atau kosong.');
    $logo = pengaturan('logo', ''); $bg = pengaturan('background', '');
    if (!empty($_FILES['logo']['name'])) { $n = simpan_gambar($_FILES['logo'], 2 * 1048576); $baru[] = $n; $lama[] = $logo; $logo = $n; }
    elseif (!empty($_POST['hapus_logo'])) { $lama[] = $logo; $logo = ''; }
    if (!empty($_FILES['background']['name'])) { $n = simpan_gambar($_FILES['background'], 3 * 1048576); $baru[] = $n; $lama[] = $bg; $bg = $n; }
    elseif (!empty($_POST['hapus_background'])) { $lama[] = $bg; $bg = ''; }
    foreach (['nama_instansi' => $nm, 'alamat_instansi' => $al, 'logo' => $logo, 'background' => $bg, 'warna_utama' => $wu, 'warna_sekunder' => $ws, 'tanggal_survei' => $ts, 'hari_menggantung' => (string)$hr, 'ambang_nilai' => $am] as $k => $v)
      ex("INSERT INTO pengaturan (kunci,nilai) VALUES (?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)", [$k, (string)$v]);
    foreach ($lama as $l) if ($l && $l !== $logo && $l !== $bg) hapus_media($l);
    log_tulis('pengaturan_ubah', ['admin' => 1, 'info' => "Nama: $nm; alamat: $al; warna $wu / $ws; logo " . ($logo ? 'kustom' : 'bawaan') . '; latar ' . ($bg ? 'kustom' : 'tanpa') . "; survei: $ts; menggantung: $hr hari; target: " . ($am === '' ? '-' : $am . '%')]);
    flash('ok', 'Pengaturan disimpan.');
  } catch (Pesan $x) { foreach ($baru as $b) hapus_media($b); flash('err', $x->getMessage()); }
  catch (Throwable $x) { foreach ($baru as $b) hapus_media($b); throw $x; }
  redirect('admin_pengaturan.php');
}
$wu = pengaturan('warna_utama', WARNA_BAWAAN['warna_utama']); $ws = pengaturan('warna_sekunder', WARNA_BAWAAN['warna_sekunder']);
if (!hex_ok($wu)) $wu = WARNA_BAWAAN['warna_utama'];
if (!hex_ok($ws)) $ws = WARNA_BAWAAN['warna_sekunder'];
$logo = path_media('logo', 'aset/logo.png'); $bg = path_media('background', '');
kepala('Pengaturan', 'pengaturan');
echo '<form method="post" enctype="multipart/form-data">' . csrf_field();
echo '<div class="kartu"><h2 class="jb">Identitas instansi</h2><label>Nama instansi <small>(tampil di header dan cetakan)</small></label><input type="text" name="nama_instansi" maxlength="120" value="' . e(nama_instansi()) . '" required><label>Alamat <small>(tampil di header dan cetakan)</small></label><input type="text" name="alamat_instansi" maxlength="250" value="' . e(alamat_instansi()) . '">';
echo '<label>Logo instansi <small>(PNG, JPG, atau WEBP, maks. 2 MB; disarankan persegi)</small></label><p><img src="' . e($logo) . '" alt="Logo saat ini" style="width:72px;height:72px;border-radius:50%;object-fit:contain;border:1px solid var(--garis);background:#fff"></p><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"><label><input type="checkbox" name="hapus_logo" value="1"> Kembalikan ke logo bawaan SIMBA</label></div>';
echo '<div class="kartu"><h2 class="jb">Tampilan</h2><label>Gambar latar <small>(PNG, JPG, atau WEBP, maks. 3 MB; dilapisi warna terang agar teks tetap terbaca)</small></label>' . ($bg ? '<p><img src="' . e($bg) . '" alt="Latar saat ini" style="max-width:240px;max-height:120px;border-radius:12px;border:1px solid var(--garis)"></p>' : '<p class="hint">Belum ada gambar latar.</p>') . '<input type="file" name="background" accept="image/png,image/jpeg,image/webp"><label><input type="checkbox" name="hapus_background" value="1"> Hapus gambar latar</label>';
echo '<div class="grid2"><div><label>Warna utama <small>(HEX; tombol, judul, awal header)</small></label><div class="baris-form"><input type="color" id="cu" value="' . e($wu) . '" data-hex="#hu" data-var="--biru" style="flex:0 0 56px;padding:2px;height:44px"><input type="text" id="hu" name="warna_utama" value="' . e($wu) . '" maxlength="7" pattern="#[0-9A-Fa-f]{6}" data-warna="#cu" data-var="--biru" placeholder="#06539f"></div></div>';
echo '<div><label>Warna sekunder <small>(HEX; ujung header)</small></label><div class="baris-form"><input type="color" id="cs" value="' . e($ws) . '" data-hex="#hs" data-var="--h3" style="flex:0 0 56px;padding:2px;height:44px"><input type="text" id="hs" name="warna_sekunder" value="' . e($ws) . '" maxlength="7" pattern="#[0-9A-Fa-f]{6}" data-warna="#cs" data-var="--h3" placeholder="#0a6f4a"></div></div></div>';
echo '<label><input type="checkbox" name="reset_warna" value="1"> Kembalikan warna bawaan (#06539f dan #0a6f4a)</label><p class="hint">Warna langsung terlihat sebagai pratinjau di halaman ini, dan baru berlaku untuk semua halaman setelah disimpan.</p></div>';
echo '<div class="kartu"><h2 class="jb">Survei dan penilaian</h2><label>Tanggal survei <small>(untuk hitung mundur di Dashboard)</small></label><input type="date" name="tanggal_survei" value="' . e(pengaturan('tanggal_survei')) . '"><label>Dokumen dianggap menggantung setelah <small>(hari menunggu verifikasi atau validasi)</small></label><input type="number" name="hari_menggantung" min="1" max="60" value="' . e(pengaturan('hari_menggantung', 7)) . '"><label>Target capaian penilaian mandiri, % <small>(opsional; cocokkan dengan Permenkes 34/2022, kosongkan bila belum yakin)</small></label><input type="number" name="ambang_nilai" min="0" max="100" value="' . e(pengaturan('ambang_nilai')) . '">';
echo '<div class="aksi-bawah"><button class="tombol">Simpan pengaturan</button></div></div></form>';
kaki();
