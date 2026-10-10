<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (!admin_()) tolak();
$perans = rows("SELECT id,nama,cakupan FROM peran ORDER BY id");
$cak = array_column($perans, 'cakupan', 'id');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  try {
    $id = (int)($_POST['id'] ?? 0);
    $un = trim((string)($_POST['username'] ?? '')); $nm = trim((string)($_POST['nama'] ?? '')); $pw = (string)($_POST['password'] ?? '');
    $pr = (int)($_POST['peran_id'] ?? 0); $akt = !empty($_POST['aktif']) ? 1 : 0;
    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $un)) throw new Pesan('Nama pengguna 3-40 karakter (huruf, angka, titik, strip).');
    if ($nm === '') throw new Pesan('Nama wajib diisi.');
    if (!isset($cak[$pr])) throw new Pesan('Pilih peran.');
    if (!$id && strlen($pw) < 8) throw new Pesan('Kata sandi minimal 8 karakter.');
    if ($pw !== '' && strlen($pw) < 8) throw new Pesan('Kata sandi minimal 8 karakter.');
    if (val("SELECT id FROM pengguna WHERE username=? AND id<>?", [$un, $id])) throw new Pesan('Nama pengguna sudah dipakai.');
    $lama = $id ? row("SELECT * FROM pengguna WHERE id=?", [$id]) : null;
    if ($id && !$lama) throw new Pesan('Pengguna tidak ditemukan.');
    if ($lama && (int)$lama['peran_id'] === 1 && ($pr !== 1 || !$akt) && (int)val("SELECT COUNT(*) FROM pengguna WHERE peran_id=1 AND aktif=1 AND id<>?", [$id]) === 0) throw new Pesan('Super Admin aktif terakhir tidak boleh dinonaktifkan atau diganti perannya.');
    $bsd = tgl_valid($_POST['berlaku_sampai'] ?? ''); $bs = $bsd ? $bsd . ' 23:59:59' : null;
    $babId = null; $atasan = null; $c = $cak[$pr];
    if ($c === 'bab') { $babId = (int)($_POST['bab_id'] ?? 0); if (!val("SELECT id FROM bab WHERE id=?", [$babId])) throw new Pesan('PJ harus dikaitkan ke satu bab.'); }
    elseif ($c === 'tugas') {
      $atasan = (int)($_POST['atasan_id'] ?? 0);
      $a = row("SELECT p.id,p.bab_id FROM pengguna p JOIN peran r ON r.id=p.peran_id WHERE p.id=? AND r.cakupan='bab' AND p.bab_id IS NOT NULL", [$atasan]);
      if (!$a) throw new Pesan('Anggota Tim harus berada di bawah satu PJ (yang sudah terhubung ke bab).');
      $babId = (int)$a['bab_id'];
    }
    if ($id) {
      ex("UPDATE pengguna SET username=?, nama=?, peran_id=?, atasan_id=?, bab_id=?, aktif=?, berlaku_sampai=? WHERE id=?", [$un, $nm, $pr, $atasan, $babId, $akt, $bs, $id]);
      if ($pw !== '') ex("UPDATE pengguna SET password_hash=?, gagal_login=0, terkunci_sampai=NULL WHERE id=?", [password_hash($pw, PASSWORD_DEFAULT), $id]);
    } else {
      $id = ins("INSERT INTO pengguna (username,nama,password_hash,peran_id,atasan_id,bab_id,aktif,berlaku_sampai) VALUES (?,?,?,?,?,?,?,?)", [$un, $nm, password_hash($pw, PASSWORD_DEFAULT), $pr, $atasan, $babId, $akt, $bs]);
    }
    // sinkron PJ per bab
    ex("UPDATE bab SET pj_id=NULL WHERE pj_id=? AND (id<>? OR ?=0)", [$id, (int)$babId, $c === 'bab' ? 1 : 0]);
    $info = '';
    if ($c === 'bab') {
      $lamaPj = (int)val("SELECT pj_id FROM bab WHERE id=?", [$babId]);
      if ($lamaPj && $lamaPj !== $id) {
        ex("UPDATE pengguna SET bab_id=NULL WHERE id=?", [$lamaPj]);
        $n = ex("UPDATE pengguna SET atasan_id=?, bab_id=? WHERE atasan_id=?", [$id, $babId, $lamaPj]);
        $info = ' PJ lama dilepas dari bab; ' . $n . ' Anggota Tim dipindahkan ke PJ baru.';
      }
      ex("UPDATE bab SET pj_id=? WHERE id=?", [$id, $babId]);
    }
    log_tulis($lama ? 'pengguna_ubah' : 'pengguna_baru', ['admin' => 1, 'info' => $un . ' (' . $nm . ')' . ($pw !== '' ? ', kata sandi diatur' : '') . $info]);
    flash('ok', 'Pengguna disimpan.' . $info);
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
  redirect('admin_pengguna.php');
}
$eid = (int)($_GET['id'] ?? 0);
$e = $eid ? row("SELECT * FROM pengguna WHERE id=?", [$eid]) : null;
$e = $e ?: ['id' => 0, 'username' => '', 'nama' => '', 'peran_id' => 4, 'atasan_id' => '', 'bab_id' => '', 'aktif' => 1, 'berlaku_sampai' => null];
$list = rows("SELECT p.*, r.nama peran, b.nomor bab_no, a.nama atasan FROM pengguna p JOIN peran r ON r.id=p.peran_id LEFT JOIN bab b ON b.id=p.bab_id LEFT JOIN pengguna a ON a.id=p.atasan_id ORDER BY p.aktif DESC, r.id, p.nama");
$pjs = rows("SELECT p.id, CONCAT(p.nama,' (Bab ',b.nomor,')') nama FROM pengguna p JOIN peran r ON r.id=p.peran_id AND r.cakupan='bab' JOIN bab b ON b.id=p.bab_id WHERE p.aktif=1 ORDER BY b.nomor");
kepala('Pengguna');
echo '<div class="kartu"><h2 class="jb">' . ($e['id'] ? 'Ubah pengguna' : 'Pengguna baru') . '</h2><form method="post">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$e['id'] . '"><div class="grid2"><div><label>Nama pengguna</label><input type="text" name="username" value="' . e($e['username']) . '" required></div><div><label>Nama lengkap</label><input type="text" name="nama" value="' . e($e['nama']) . '" required></div><div><label>Peran</label><select name="peran_id">' . opsi($perans, $e['peran_id']) . '</select></div><div><label>Kata sandi ' . ($e['id'] ? '<small>(kosongkan bila tidak diubah)</small>' : '<small>(min. 8 karakter)</small>') . '</label><input type="password" name="password" autocomplete="new-password"></div><div><label>Bab <small>(khusus PJ)</small></label><select name="bab_id">' . opsi(rows("SELECT id, CONCAT('Bab ',nomor,' – ',nama) nama FROM bab ORDER BY nomor"), $e['bab_id'], '—') . '</select></div><div><label>PJ atasan <small>(khusus Anggota Tim)</small></label><select name="atasan_id">' . opsi($pjs, $e['atasan_id'], '—') . '</select><div><label>Akun berlaku sampai <small>(opsional, untuk akun sementara seperti Surveior)</small></label><input type="date" name="berlaku_sampai" value="' . e(substr((string)$e['berlaku_sampai'], 0, 10)) . '"></div></div><label><input type="checkbox" name="aktif" value="1"' . ($e['aktif'] ? ' checked' : '') . '> Akun aktif</label><p class="hint">Satu bab tepat satu PJ. Menetapkan PJ baru pada bab yang sudah punya PJ akan melepas PJ lama dan memindahkan Anggota Timnya ke PJ baru.</p><p><button class="tombol">Simpan</button> <a class="tombol sekunder" href="admin_pengguna.php">Batal</a></p></form></div>';
echo '<div class="kartu"><h2 class="jb">Daftar pengguna</h2><div class="gulung"><table><tr><th>Nama</th><th>Pengguna</th><th>Peran</th><th>Bab</th><th>PJ atasan</th><th>Status</th><th>Berlaku s.d.</th><th></th></tr>';
foreach ($list as $u) echo '<tr><td>' . e($u['nama']) . '</td><td>' . e($u['username']) . '</td><td>' . e($u['peran']) . '</td><td>' . e($u['bab_no'] ?: '-') . '</td><td>' . e($u['atasan'] ?: '-') . '</td><td>' . ($u['aktif'] ? '<span class="badge b-hijau">Aktif</span>' : '<span class="badge b-abu">Nonaktif</span>') . '</td><td>' . e(tgl($u['berlaku_sampai'])) . '</td><td><a class="tombol kecil sekunder" href="?id=' . (int)$u['id'] . '">Ubah</a></td></tr>';
echo '</table></div></div>';
kaki();
