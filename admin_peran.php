<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (!admin_()) tolak();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  try {
    $a = $_POST['aksi'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($a === 'hapus') {
      $r = row("SELECT * FROM peran WHERE id=?", [$id]);
      if (!$r || $r['bawaan']) throw new Pesan('Peran bawaan tidak dapat dihapus.');
      if (val("SELECT COUNT(*) FROM pengguna WHERE peran_id=?", [$id])) throw new Pesan('Peran masih dipakai pengguna.');
      ex("DELETE FROM peran WHERE id=?", [$id]); log_tulis('peran_ubah', ['admin' => 1, 'info' => 'Hapus peran ' . $r['nama']]); flash('ok', 'Peran dihapus.');
    } else {
      $nm = trim((string)($_POST['nama'] ?? '')); $c = (string)($_POST['cakupan'] ?? '');
      if ($nm === '' || mb_strlen($nm) > 60) throw new Pesan('Nama peran wajib diisi.');
      if (!in_array($c, ['semua', 'bab', 'tugas'], true)) throw new Pesan('Pilih cakupan.');
      if ($id === 1) throw new Pesan('Peran Super Admin tidak dapat diubah.');
      if ($id) { if (!row("SELECT id FROM peran WHERE id=?", [$id])) throw new Pesan('Peran tidak ditemukan.'); ex("UPDATE peran SET nama=?, cakupan=? WHERE id=?", [$nm, $c, $id]); }
      else $id = ins("INSERT INTO peran (nama,cakupan) VALUES (?,?)", [$nm, $c]);
      ex("DELETE FROM hak_akses WHERE peran_id=?", [$id]);
      $hk = [];
      foreach ((array)($_POST['hak'] ?? []) as $h) if (array_key_exists($h, DAFTAR_HAK)) { ex("INSERT INTO hak_akses (peran_id,hak) VALUES (?,?)", [$id, $h]); $hk[] = $h; }
      log_tulis('peran_ubah', ['admin' => 1, 'info' => $nm . ' (cakupan ' . $c . '): ' . implode(', ', $hk)]); flash('ok', 'Peran disimpan.');
    }
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
  redirect('admin_peran.php');
}
$eid = (int)($_GET['id'] ?? 0);
$e = $eid > 1 ? row("SELECT * FROM peran WHERE id=?", [$eid]) : null;
$e = $e ?: ['id' => 0, 'nama' => '', 'cakupan' => 'tugas'];
$pun = array_column(rows("SELECT hak FROM hak_akses WHERE peran_id=?", [(int)$e['id']]), 'hak');
$list = rows("SELECT r.*, (SELECT COUNT(*) FROM pengguna p WHERE p.peran_id=r.id) jml FROM peran r ORDER BY r.id");
kepala('Peran dan Hak Akses');
echo '<div class="kartu"><h2 class="jb">' . ($e['id'] ? 'Ubah peran' : 'Peran baru') . '</h2><form method="post">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$e['id'] . '"><div class="grid2"><div><label>Nama peran</label><input type="text" name="nama" value="' . e($e['nama']) . '" required></div><div><label>Cakupan data</label><select name="cakupan">';
foreach (['semua' => 'Semua bab', 'bab' => 'Satu bab (seperti PJ)', 'tugas' => 'EP yang ditugaskan (seperti Anggota Tim)'] as $k => $v) echo '<option value="' . $k . '"' . ($e['cakupan'] === $k ? ' selected' : '') . '>' . e($v) . '</option>';
echo '</select></div></div><label>Hak akses</label><div class="hak">';
foreach (DAFTAR_HAK as $k => $v) echo '<label><input type="checkbox" name="hak[]" value="' . $k . '"' . (in_array($k, $pun, true) ? ' checked' : '') . '> ' . e($v) . '</label>';
echo '</div><p><button class="tombol">Simpan</button> <a class="tombol sekunder" href="admin_peran.php">Peran baru</a></p></form></div>';
echo '<div class="kartu"><h2 class="jb">Daftar peran</h2><div class="gulung"><table><tr><th>Peran</th><th>Cakupan</th><th>Pengguna</th><th></th></tr>';
foreach ($list as $r) {
  echo '<tr><td>' . e($r['nama']) . ($r['bawaan'] ? ' <span class="badge b-abu">bawaan</span>' : '') . '</td><td>' . e($r['cakupan']) . '</td><td>' . (int)$r['jml'] . '</td><td>';
  if ((int)$r['id'] === 1) echo '<span class="hint">Semua hak, tidak dapat diubah</span>';
  else { echo '<a class="tombol kecil sekunder" href="?id=' . (int)$r['id'] . '">Ubah</a> ';
    if (!$r['bawaan'] && !$r['jml']) echo '<form method="post" style="display:inline">' . csrf_field() . '<input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="' . (int)$r['id'] . '"><button class="tombol kecil bahaya" data-konfirmasi="Hapus peran ini?">Hapus</button></form>'; }
  echo '</td></tr>';
}
echo '</table></div></div>';
kaki();
