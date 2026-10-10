<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (!admin_()) tolak();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  try {
    $a = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0); $nm = trim((string)($_POST['nama'] ?? ''));
    if ($a === 'hapus') {
      if (val("SELECT COUNT(*) FROM dokumen WHERE jenis_id=?", [$id])) throw new Pesan('Jenis masih dipakai dokumen.');
      ex("DELETE FROM jenis_dokumen WHERE id=?", [$id]); log_tulis('jenis_ubah', ['admin' => 1, 'info' => 'Hapus jenis #' . $id]); flash('ok', 'Jenis dihapus.');
    } else {
      if ($nm === '' || mb_strlen($nm) > 60) throw new Pesan('Nama jenis wajib diisi (maks. 60 karakter).');
      if ($id) ex("UPDATE jenis_dokumen SET nama=? WHERE id=?", [$nm, $id]); else ins("INSERT INTO jenis_dokumen (nama) VALUES (?)", [$nm]);
      log_tulis('jenis_ubah', ['admin' => 1, 'info' => $nm]); flash('ok', 'Jenis disimpan.');
    }
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
  redirect('admin_jenis.php');
}
kepala('Jenis Dokumen');
echo '<div class="kartu"><h2 class="jb">Jenis dokumen</h2><div class="gulung"><table>';
foreach (rows("SELECT j.*, (SELECT COUNT(*) FROM dokumen d WHERE d.jenis_id=j.id) jml FROM jenis_dokumen j ORDER BY j.id") as $j) {
  echo '<tr><td><form method="post" class="baris-form">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$j['id'] . '"><input type="text" name="nama" value="' . e($j['nama']) . '"><button class="tombol kecil sekunder">Simpan</button></form></td><td>' . (int)$j['jml'] . ' dokumen</td><td>';
  if (!$j['jml']) echo '<form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="' . (int)$j['id'] . '"><button class="tombol kecil bahaya" data-konfirmasi="Hapus jenis ini?">Hapus</button></form>';
  echo '</td></tr>';
}
echo '</table></div><form method="post" class="baris-form">' . csrf_field() . '<input type="text" name="nama" placeholder="Jenis dokumen baru" required><button class="tombol tambah">+ Tambah</button></form></div>';
kaki();
