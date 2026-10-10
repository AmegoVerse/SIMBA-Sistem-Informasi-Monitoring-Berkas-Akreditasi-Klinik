<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('unggah');
$w = 'e.tdd=0'; $p = [];
if ($U['cakupan'] === 'bab') { $w .= ' AND s.bab_id=?'; $p[] = (int)$U['bab_id']; }
elseif ($U['cakupan'] === 'tugas') { $w .= ' AND pn.anggota_id=?'; $p[] = (int)$U['id']; }
$pilih = rows("SELECT e.id, CONCAT(e.nomor,' – ',LEFT(e.bunyi,70)) nama FROM ep e JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id WHERE $w ORDER BY e.urut", $p);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  $up = null;
  try {
    $ep = ep_row((int)($_POST['ep_id'] ?? 0));
    if (!$ep || !bolehEP($ep) || $ep['tdd']) throw new Pesan('EP tidak valid atau bukan tugas Anda.');
    $judul = trim((string)($_POST['judul'] ?? ''));
    if ($judul === '' || mb_strlen($judul) > 200) throw new Pesan('Judul dokumen wajib diisi (maksimal 200 karakter).');
    $jenis = (int)($_POST['jenis_id'] ?? 0);
    if (!val("SELECT id FROM jenis_dokumen WHERE id=?", [$jenis])) throw new Pesan('Pilih jenis dokumen.');
    $berlaku = tgl_valid($_POST['berlaku_sampai'] ?? ''); $tinjau = tgl_valid($_POST['tinjau_ulang'] ?? '');
    $up = simpan_upload($_FILES['berkas'] ?? []);
    $bab = $U['bab_id'] ? (int)$U['bab_id'] : (int)$ep['bab_id'];
    db()->begin_transaction();
    $did = ins("INSERT INTO dokumen (judul,jenis_id,pemilik_id,bab_id,status,berlaku_sampai,tinjau_ulang) VALUES (?,?,?,?, 'menunggu', ?, ?)", [$judul, $jenis, (int)$U['id'], $bab, $berlaku, $tinjau]);
    $vid = ins("INSERT INTO dokumen_versi (dokumen_id,nomor,judul,file_nama,file_asli,ukuran,hash,pengunggah_id) VALUES (?,1,?,?,?,?,?,?)", [$did, $judul, $up['nama'], $up['asli'], $up['ukuran'], $up['hash'], (int)$U['id']]);
    ex("UPDATE dokumen SET versi_aktif_id=? WHERE id=?", [$vid, $did]);
    ex("INSERT INTO dokumen_ep (dokumen_id,ep_id,ditautkan_oleh) VALUES (?,?,?)", [$did, (int)$ep['id'], (int)$U['id']]);
    db()->commit();
    $up = null;
    log_tulis('unggah', ['bab_id' => $bab, 'ep_id' => (int)$ep['id'], 'dokumen_id' => $did, 'judul' => $judul, 'info' => 'EP ' . $ep['nomor'] . ', versi 1']);
    flash('ok', 'Dokumen diunggah dan menunggu verifikasi.');
    redirect('dokumen.php?id=' . $did);
  } catch (Pesan $x) { if ($up) { try { db()->rollback(); } catch (Throwable $t) {} hapus_file($up['nama']); } flash('err', $x->getMessage()); }
  catch (Throwable $x) { if ($up) { try { db()->rollback(); } catch (Throwable $t) {} hapus_file($up['nama']); } throw $x; }
}
$sel = (int)($_POST['ep_id'] ?? $_GET['ep'] ?? 0);
kepala('Unggah', 'unggah');
echo '<div class="kartu"><h2 class="jb">Unggah dokumen</h2>';
if (!$pilih) echo '<p class="hint">Belum ada EP yang bisa Anda isi. PJ perlu membagikan EP kepada Anda lebih dulu.</p></div>';
else {
  echo '<form method="post" enctype="multipart/form-data">' . csrf_field() . '<label>EP</label><select name="ep_id" required>' . opsi($pilih, $sel, '— pilih EP —') . '</select>';
  echo '<label>Judul dokumen</label><input type="text" name="judul" maxlength="200" value="' . e($_POST['judul'] ?? '') . '" required><label>Jenis dokumen</label><select name="jenis_id" required>' . opsi(rows("SELECT id,nama FROM jenis_dokumen ORDER BY id"), $_POST['jenis_id'] ?? '', '— pilih jenis —') . '</select>';
  echo '<label>Berkas <small>(PDF, Word, Excel, PowerPoint, JPG, PNG; maks ' . (MAKS_UPLOAD / 1048576) . ' MB)</small></label><input type="file" name="berkas" required>';
  echo '<div class="grid2"><div><label>Berlaku sampai <small>(opsional)</small></label><input type="date" name="berlaku_sampai"></div><div><label>Tinjau ulang <small>(opsional)</small></label><input type="date" name="tinjau_ulang"></div></div>';
  echo '<div class="aksi-bawah"><button class="tombol">Unggah</button></div></form></div>';
}
kaki();
