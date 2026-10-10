<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('lihat_temuan'); if (surveior()) tolak();
$id = (int)($_GET['id'] ?? 0);
$t = row("SELECT t.*, e.nomor ep_nomor, u.nama pic, c.nama pembuat FROM temuan t LEFT JOIN ep e ON e.id=t.ep_id LEFT JOIN pengguna u ON u.id=t.pic_id LEFT JOIN pengguna c ON c.id=t.dibuat_oleh WHERE t.id=?", [$id]);
$mybab = (int)$U['bab_id'];
$lihat = function ($t) use ($U, $mybab) { if (semua()) return true; if ($U['cakupan'] === 'bab') return $mybab && $mybab === (int)$t['bab_id']; return (int)$t['pic_id'] === (int)$U['id']; };
$kelola = function ($t) use ($mybab) { return punya('catat_temuan') && (semua() || ($GLOBALS['U']['cakupan'] === 'bab' && $mybab && $mybab === (int)$t['bab_id'])); };
if (!$t || !$lihat($t)) tolak('Temuan tidak ditemukan atau bukan bagian dari lingkup Anda.');
$self = 'temuan_detail.php?id=' . $id; $adaPic = (int)$t['pic_id'] === (int)$U['id']; $kel = $kelola($t);
$lg = ['bab_id' => $t['bab_id'], 'ep_id' => $t['ep_id'], 'info' => 'Temuan #' . $id];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  try {
    $a = (string)($_POST['aksi'] ?? ''); $cat = trim((string)($_POST['catatan'] ?? ''));
    $ubahStatus = function ($dari, $ke, $sets = '', $p = []) use ($id) {
      $in = "'" . implode("','", $dari) . "'";
      if (!val("SELECT id FROM temuan WHERE id=? AND status IN ($in)", [$id])) throw new Pesan('Status temuan sudah berubah. Muat ulang halaman.');
      ex("UPDATE temuan SET status=?$sets WHERE id=?", array_merge([$ke], $p, [$id]));
    };
    if ($a === 'kerja') {
      if (!$adaPic && !$kel) tolak();
      $ke = (string)($_POST['status'] ?? ''); $tl = trim((string)($_POST['tindak_lanjut'] ?? ''));
      if (!in_array($ke, ['dikerjakan', 'selesai'], true)) throw new Pesan('Status tidak valid.');
      if ($ke === 'selesai' && $tl === '') throw new Pesan('Isi tindak lanjut yang dilakukan sebelum menandai selesai.');
      if (mb_strlen($tl) > 3000) throw new Pesan('Tindak lanjut terlalu panjang.');
      $ubahStatus(['terbuka', 'dikerjakan'], $ke, ', tindak_lanjut=?, selesai_pada=' . ($ke === 'selesai' ? 'NOW()' : 'NULL'), [$tl !== '' ? $tl : null]);
      log_tulis('temuan_kerja', $lg + ['catatan' => $tl !== '' ? $tl : null, 'judul' => null]); flash('ok', 'Tindak lanjut disimpan.');
    } elseif ($a === 'tutup') {
      if (!$kel) tolak();
      $ubahStatus(['selesai'], 'ditutup', ', ditutup_oleh=?, ditutup_pada=NOW()', [(int)$U['id']]);
      log_tulis('temuan_tutup', $lg + ['catatan' => $cat !== '' ? $cat : null]); flash('ok', 'Temuan ditutup.');
    } elseif ($a === 'buka') {
      if (!$kel) tolak(); if ($cat === '') throw new Pesan('Alasan membuka kembali wajib diisi.');
      $ubahStatus(['selesai', 'ditutup'], 'dikerjakan', ', selesai_pada=NULL, ditutup_oleh=NULL, ditutup_pada=NULL');
      log_tulis('temuan_buka', $lg + ['catatan' => $cat]); flash('ok', 'Temuan dibuka kembali.');
    } elseif ($a === 'ubah') {
      if (!$kel) tolak(); if ($t['status'] === 'ditutup') throw new Pesan('Temuan yang sudah ditutup tidak dapat diubah.');
      $ur = trim((string)($_POST['uraian'] ?? '')); if ($ur === '' || mb_strlen($ur) > 3000) throw new Pesan('Uraian wajib diisi.');
      $sumber = (string)($_POST['sumber'] ?? ''); if (!array_key_exists($sumber, SUMBER_TEMUAN)) throw new Pesan('Pilih sumber.');
      $epn = trim((string)($_POST['ep_nomor'] ?? '')); $epid = null;
      if ($epn !== '') { $epid = val("SELECT e.id FROM ep e JOIN standar s ON s.id=e.standar_id WHERE e.nomor=? AND s.bab_id=?", [$epn, (int)$t['bab_id']]); if (!$epid) throw new Pesan('Nomor EP tidak ditemukan pada bab ini.'); }
      $pic = (int)($_POST['pic_id'] ?? 0); if ($pic && !val("SELECT p.id FROM pengguna p WHERE p.id=? AND p.aktif=1 AND p.bab_id=?", [$pic, (int)$t['bab_id']])) throw new Pesan('Penanggung jawab harus pengguna aktif pada bab ini.');
      ex("UPDATE temuan SET uraian=?, sumber=?, ep_id=?, pic_id=?, tenggat=? WHERE id=?", [$ur, $sumber, $epid ? (int)$epid : null, $pic ?: null, tgl_valid($_POST['tenggat'] ?? ''), $id]);
      log_tulis('temuan_ubah', array_merge($lg, ['ep_id' => $epid ? (int)$epid : null, 'catatan' => $ur])); flash('ok', 'Temuan diperbarui.');
    } else throw new Pesan('Aksi tidak dikenal.');
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
  redirect($self);
}
kepala('Temuan #' . $id, 'temuan');
echo '<div class="kartu"><h2 class="jb">Temuan #' . $id . '</h2><p>' . badge_temuan($t['status']) . '</p><table><tr><th>Bab</th><td>' . (int)$t['bab_id'] . '</td><th>EP</th><td>' . e($t['ep_nomor'] ?: '-') . '</td></tr><tr><th>Sumber</th><td>' . e(SUMBER_TEMUAN[$t['sumber']]) . '</td><th>Penanggung jawab</th><td>' . e($t['pic'] ?: '-') . '</td></tr><tr><th>Tenggat</th><td>' . e(tgl($t['tenggat'])) . '</td><th>Dicatat oleh</th><td>' . e($t['pembuat']) . ', ' . e(tgl($t['dibuat'])) . '</td></tr></table><h3>Uraian</h3><p>' . nl2br(e($t['uraian'])) . '</p>';
if ($t['tindak_lanjut']) echo '<h3>Tindak lanjut</h3><p>' . nl2br(e($t['tindak_lanjut'])) . '</p>';
echo '</div>';
if (($adaPic || $kel) && in_array($t['status'], ['terbuka', 'dikerjakan'], true)) echo '<div class="kartu"><h2 class="jb">Tindak lanjut</h2><form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="kerja"><label>Tindakan yang dilakukan</label><textarea name="tindak_lanjut">' . e($t['tindak_lanjut']) . '</textarea><div class="baris-form"><button name="status" value="dikerjakan" class="tombol sekunder">Simpan sebagai sedang dikerjakan</button><button name="status" value="selesai" class="tombol" data-konfirmasi="Tandai temuan ini selesai dan kirim untuk penutupan?">Tandai selesai</button></div></form></div>';
if ($kel && $t['status'] === 'selesai') echo '<div class="kartu"><h2 class="jb">Penutupan</h2><form method="post" class="baris-form">' . csrf_field() . '<input type="hidden" name="aksi" value="tutup"><input type="text" name="catatan" placeholder="Catatan penutupan (opsional)"><button class="tombol">Tutup temuan</button></form></div>';
if ($kel && in_array($t['status'], ['selesai', 'ditutup'], true)) echo '<div class="kartu"><h2 class="jb">Buka kembali</h2><form method="post" class="baris-form">' . csrf_field() . '<input type="hidden" name="aksi" value="buka"><input type="text" name="catatan" placeholder="Alasan (wajib)" required><button class="tombol sekunder">Buka kembali</button></form></div>';
if ($kel && $t['status'] !== 'ditutup') {
  $pics = rows("SELECT p.id,p.nama FROM pengguna p JOIN peran r ON r.id=p.peran_id WHERE p.aktif=1 AND p.bab_id=? AND r.cakupan IN ('tugas','bab') ORDER BY p.nama", [(int)$t['bab_id']]);
  echo '<div class="kartu"><h2 class="jb">Ubah temuan</h2><form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="ubah"><div class="grid2"><div><label>Sumber</label><select name="sumber">';
  foreach (SUMBER_TEMUAN as $k => $v) echo '<option value="' . $k . '"' . ($t['sumber'] === $k ? ' selected' : '') . '>' . e($v) . '</option>';
  echo '</select></div><div><label>Nomor EP</label><input type="text" name="ep_nomor" value="' . e($t['ep_nomor']) . '"></div><div><label>Penanggung jawab</label><select name="pic_id">' . opsi($pics, $t['pic_id'], '—') . '</select></div><div><label>Tenggat</label><input type="date" name="tenggat" value="' . e($t['tenggat']) . '"></div></div><label>Uraian</label><textarea name="uraian" required>' . e($t['uraian']) . '</textarea><p><button class="tombol sekunder">Simpan perubahan</button></p></form></div>';
}
if (punya('lihat_riwayat')) {
  echo '<div class="kartu"><h2 class="jb">Riwayat</h2><div class="gulung"><table><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Catatan</th></tr>';
  foreach (rows("SELECT * FROM log WHERE (info=? OR info LIKE ?) AND bab_id=? AND aksi LIKE 'temuan%' ORDER BY id DESC LIMIT 100", ['Temuan #' . $id, 'Temuan #' . $id . ' (%', (int)$t['bab_id']]) as $r) echo '<tr><td>' . e(tgl($r['waktu'], true)) . '</td><td>' . e($r['pelaku_nama']) . '</td><td>' . e(LABEL_AKSI[$r['aksi']] ?? $r['aksi']) . '</td><td>' . nl2br(e($r['catatan'])) . '</td></tr>';
  echo '</table></div></div>';
}
kaki();
