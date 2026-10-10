<?php
require __DIR__ . '/inc/mulai.php';
wajib_login();
$id = (int)($_GET['id'] ?? 0);
$d = dok_row($id);
if (!$d || !boleh_lihat_dok($d)) tolak('Dokumen tidak ditemukan atau tidak dapat Anda buka.');
$self = 'dokumen.php?id=' . $id;

function ubah_status($id, $ver, $dari, $ke, $sets = '', $p = []){
  $in = "'" . implode("','", $dari) . "'";
  $n = ex("UPDATE dokumen SET status=?$sets WHERE id=? AND versi_aktif_id=? AND status IN ($in)", array_merge([$ke], $p, [$id, $ver]));
  if (!$n) throw new Pesan('Status atau versi dokumen sudah berubah. Muat ulang halaman lalu periksa versi terbaru.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  $a = (string)($_POST['aksi'] ?? ''); $cat = trim((string)($_POST['catatan'] ?? '')); $ver = (int)($_POST['versi'] ?? 0);
  $uid = (int)$U['id'];
  $lg = ['bab_id' => $d['bab_id'], 'dokumen_id' => $id, 'judul' => $d['judul'], 'nomor' => $d['nomor_dokumen']];
  $up = null;
  try {
    if ($d['diarsipkan'] && !in_array($a, ['pulihkan', 'hapus'], true)) throw new Pesan('Dokumen sedang diarsipkan.');
    $perluLingkup = ['verifikasi' => 'verifikasi', 'revisi' => 'kembalikan', 'validasi' => 'validasi', 'batal_verifikasi' => 'batal_verifikasi', 'batal_validasi' => 'batal_validasi', 'ubah_nomor' => 'validasi'];
    if (isset($perluLingkup[$a])) {
      wajib($perluLingkup[$a]);
      if (!lingkup_dok($d)) tolak();
    }
    if (in_array($a, ['verifikasi', 'revisi', 'validasi', 'batal_verifikasi', 'batal_validasi'], true) && $ver !== (int)$d['versi_aktif_id']) throw new Pesan('Versi dokumen sudah berubah. Muat ulang halaman dan periksa versi terbaru sebelum menyetujui.');
    if (in_array($a, ['revisi', 'batal_verifikasi', 'batal_validasi'], true) && $cat === '') throw new Pesan('Catatan atau alasan wajib diisi.');
    switch ($a) {
      case 'verifikasi':
        ubah_status($id, $ver, ['menunggu'], 'terverifikasi', ', verifikator_id=?, verifikasi_pada=NOW()', [$uid]);
        log_tulis('verifikasi', $lg + ['info' => 'Versi ' . $d['versi_no'] . '. ' . ep_terdampak($id)]); flash('ok', 'Dokumen terverifikasi.'); break;
      case 'revisi':
        ubah_status($id, $ver, ['menunggu', 'terverifikasi'], 'revisi', ', verifikator_id=NULL, verifikasi_pada=NULL');
        log_tulis('revisi', $lg + ['catatan' => $cat, 'info' => 'EP terdampak: ' . ep_terdampak($id)]); flash('ok', 'Dokumen dikembalikan untuk revisi.'); break;
      case 'validasi':
        $nomor = trim((string)($_POST['nomor_dokumen'] ?? ''));
        if (mb_strlen($nomor) > 100) throw new Pesan('Nomor dokumen terlalu panjang.');
        ubah_status($id, $ver, ['terverifikasi'], 'valid', ', nomor_dokumen=COALESCE(NULLIF(?,\'\'),nomor_dokumen), validator_id=?, validasi_pada=NOW()', [$nomor, $uid]);
        $lg['nomor'] = $nomor !== '' ? $nomor : $d['nomor_dokumen'];
        log_tulis('validasi', $lg + ['info' => 'Versi ' . $d['versi_no'] . '. ' . ep_terdampak($id)]); flash('ok', 'Dokumen divalidasi.'); break;
      case 'batal_verifikasi':
        ubah_status($id, $ver, ['terverifikasi'], 'menunggu', ', verifikator_id=NULL, verifikasi_pada=NULL');
        log_tulis('batal_verifikasi', $lg + ['catatan' => $cat, 'info' => 'EP terdampak: ' . ep_terdampak($id)]); flash('ok', 'Verifikasi dibatalkan.'); break;
      case 'batal_validasi':
        ubah_status($id, $ver, ['valid'], 'terverifikasi', ', validator_id=NULL, validasi_pada=NULL');
        log_tulis('batal_validasi', $lg + ['catatan' => $cat, 'info' => 'EP terdampak: ' . ep_terdampak($id)]); flash('ok', 'Validasi dibatalkan.'); break;
      case 'ubah_nomor':
        if ($d['status'] !== 'valid') throw new Pesan('Nomor hanya dapat diubah pada dokumen Valid.');
        $nomor = trim((string)($_POST['nomor_dokumen'] ?? ''));
        if (mb_strlen($nomor) > 100) throw new Pesan('Nomor dokumen terlalu panjang.');
        ex("UPDATE dokumen SET nomor_dokumen=? WHERE id=?", [$nomor === '' ? null : $nomor, $id]);
        log_tulis('ubah_nomor', ['nomor' => $nomor] + $lg + ['info' => 'Dari "' . $d['nomor_dokumen'] . '" ke "' . $nomor . '"']); flash('ok', 'Nomor dokumen diperbarui.'); break;
      case 'edit':
        wajib('edit'); if (!kelola_dok($d)) tolak();
        $judul = trim((string)($_POST['judul'] ?? ''));
        if ($judul === '' || mb_strlen($judul) > 200) throw new Pesan('Judul wajib diisi (maksimal 200 karakter).');
        $jenis = (int)($_POST['jenis_id'] ?? 0);
        if (!val("SELECT id FROM jenis_dokumen WHERE id=?", [$jenis])) throw new Pesan('Pilih jenis dokumen.');
        ex("UPDATE dokumen SET judul=?, jenis_id=?, berlaku_sampai=?, tinjau_ulang=? WHERE id=?", [$judul, $jenis, tgl_valid($_POST['berlaku_sampai'] ?? ''), tgl_valid($_POST['tinjau_ulang'] ?? ''), $id]);
        log_tulis('edit_dokumen', ['judul' => $judul] + $lg); flash('ok', 'Data dokumen diperbarui.'); break;
      case 'versi_baru':
        wajib('edit'); if (!kelola_dok($d)) tolak('Hanya pemilik dan PJ bab pemilik yang dapat mengganti versi.');
        $up = simpan_upload($_FILES['berkas'] ?? []);
        $jd = trim((string)($_POST['judul_baru'] ?? '')); if ($jd === '') $jd = $d['judul'];
        if (mb_strlen($jd) > 200) throw new Pesan('Judul terlalu panjang.');
        $lama = rows("SELECT id,file_nama FROM dokumen_versi WHERE dokumen_id=? AND file_dihapus=0", [$id]);
        $sebelum = $d['status'];
        db()->begin_transaction();
        $no = 1 + (int)val("SELECT MAX(nomor) FROM dokumen_versi WHERE dokumen_id=?", [$id]);
        $vid = ins("INSERT INTO dokumen_versi (dokumen_id,nomor,judul,file_nama,file_asli,ukuran,hash,pengunggah_id) VALUES (?,?,?,?,?,?,?,?)", [$id, $no, $jd, $up['nama'], $up['asli'], $up['ukuran'], $up['hash'], $uid]);
        ex("UPDATE dokumen SET judul=?, versi_aktif_id=?, status='menunggu', verifikator_id=NULL, verifikasi_pada=NULL, validator_id=NULL, validasi_pada=NULL WHERE id=?", [$jd, $vid, $id]);
        foreach ($lama as $l) ex("UPDATE dokumen_versi SET file_dihapus=1, file_nama=NULL WHERE id=?", [(int)$l['id']]);
        db()->commit();
        $up = null;
        foreach ($lama as $l) hapus_file($l['file_nama']);
        log_tulis('versi_baru', ['judul' => $jd] + $lg + ['catatan' => $cat !== '' ? $cat : null, 'info' => 'Versi ' . $no . ' (sebelumnya ' . $sebelum . ', kini menunggu verifikasi). File versi lama dihapus. ' . ep_terdampak($id)]);
        flash('ok', 'Versi ' . $no . ' diunggah. Status kembali ke Menunggu verifikasi.'); break;
      case 'arsip':
      case 'hapus':
        wajib('edit'); if (!kelola_dok($d)) tolak();
        if (empty($_POST['konfirmasi'])) throw new Pesan('Centang konfirmasi terlebih dahulu.');
        $n = (int)val("SELECT COUNT(*) FROM dokumen_ep WHERE dokumen_id=? AND aktif=1", [$id]);
        $info = 'Dipakai oleh ' . $n . ' EP: ' . ep_terdampak($id);
        if ($a === 'arsip') {
          ex("UPDATE dokumen SET diarsipkan=1 WHERE id=?", [$id]); ex("UPDATE dokumen_ep SET aktif=0 WHERE dokumen_id=?", [$id]);
          log_tulis('arsip', $lg + ['info' => $info]); flash('ok', 'Dokumen diarsipkan dan tautan ke EP dilepas.'); break;
        }
        $files = rows("SELECT file_nama FROM dokumen_versi WHERE dokumen_id=? AND file_dihapus=0", [$id]);
        ex("DELETE FROM dokumen WHERE id=?", [$id]);
        foreach ($files as $f) hapus_file($f['file_nama']);
        log_tulis('hapus_dokumen', $lg + ['info' => $info . '. Versi terakhir: ' . $d['versi_no']]);
        flash('ok', 'Dokumen dihapus permanen.'); redirect('daftar_dokumen.php');
      case 'pulihkan':
        wajib('edit'); if (!kelola_dok($d)) tolak();
        if (!empty($_POST['tautan'])) ex("UPDATE dokumen_ep SET aktif=1 WHERE dokumen_id=?", [$id]); else ex("DELETE FROM dokumen_ep WHERE dokumen_id=?", [$id]);
        ex("UPDATE dokumen SET diarsipkan=0 WHERE id=?", [$id]);
        log_tulis('pulihkan', $lg + ['info' => !empty($_POST['tautan']) ? 'Tautan lama dikembalikan' : 'Tanpa tautan lama']); flash('ok', 'Dokumen dipulihkan.'); break;
      case 'pindah_pemilik':
        if (!(admin_() || ($U['cakupan'] === 'bab' && $U['bab_id'] && (int)$U['bab_id'] === (int)$d['bab_id']))) tolak();
        $t = row("SELECT p.id,p.nama,p.bab_id FROM pengguna p JOIN peran r ON r.id=p.peran_id WHERE p.id=? AND p.aktif=1 AND r.cakupan IN ('tugas','bab')", [(int)($_POST['pemilik_id'] ?? 0)]);
        if (!$t) throw new Pesan('Pilih Anggota Tim atau PJ yang aktif.');
        ex("UPDATE dokumen SET pemilik_id=?, bab_id=? WHERE id=?", [(int)$t['id'], $t['bab_id'], $id]);
        log_tulis('pindah_pemilik', $lg + ['info' => 'Dari ' . $d['pemilik'] . ' ke ' . $t['nama']]); flash('ok', 'Kepemilikan dipindahkan ke ' . $t['nama'] . '.'); break;
      case 'komentar':
        if (surveior() || !punya('lihat_berkas')) tolak();
        if ($cat === '' || mb_strlen($cat) > 2000) throw new Pesan('Tulis komentar (maksimal 2000 karakter).');
        ins("INSERT INTO komentar (dokumen_id,pengirim_id,isi) VALUES (?,?,?)", [$id, $uid, $cat]); flash('ok', 'Komentar terkirim.'); break;
      case 'laporan_selesai':
        if (!kelola_dok($d)) tolak();
        $n = ex("UPDATE laporan SET status='selesai', tanggapan=?, ditangani_oleh=?, ditangani_pada=NOW() WHERE id=? AND dokumen_id=? AND status='terbuka'", [$cat !== '' ? $cat : null, $uid, (int)($_POST['laporan_id'] ?? 0), $id]);
        if (!$n) throw new Pesan('Laporan tidak ditemukan atau sudah selesai.');
        log_tulis('laporan_selesai', $lg + ['catatan' => $cat !== '' ? $cat : null]); flash('ok', 'Laporan ditandai selesai.'); break;
      default: throw new Pesan('Aksi tidak dikenal.');
    }
  } catch (Pesan $x) { if ($up) { try { db()->rollback(); } catch (Throwable $t) {} hapus_file($up['nama']); } flash('err', $x->getMessage()); }
  catch (Throwable $x) { if ($up) { try { db()->rollback(); } catch (Throwable $t) {} hapus_file($up['nama']); } throw $x; }
  redirect($self);
}

function form_aksi($aksi, $ver, $isi, $tombol, $kelas = 'tombol', $konf = ''){
  return '<form method="post" class="baris-form">' . csrf_field() . '<input type="hidden" name="aksi" value="' . $aksi . '"><input type="hidden" name="versi" value="' . (int)$ver . '">' . $isi . '<button class="' . $kelas . '"' . ($konf ? ' data-konfirmasi="' . e($konf) . '"' : '') . '>' . e($tombol) . '</button></form>';
}
$ver = (int)$d['versi_aktif_id']; $st = $d['status']; $kel = kelola_dok($d); $ling = lingkup_dok($d);
$eps = rows("SELECT e.id,e.nomor,s.bab_id,pn.anggota_id,de.aktif FROM dokumen_ep de JOIN ep e ON e.id=de.ep_id JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id WHERE de.dokumen_id=? ORDER BY e.urut", [$id]);
$aktifN = 0; foreach ($eps as $x) if ($x['aktif']) $aktifN++;
$versi = rows("SELECT v.*, u.nama oleh FROM dokumen_versi v JOIN pengguna u ON u.id=v.pengunggah_id WHERE v.dokumen_id=? ORDER BY v.nomor DESC", [$id]);
kepala($d['judul'], 'daftar');
echo '<div class="kartu"><h2 class="jb">' . e($d['judul']) . '</h2><p>' . badge_dok($st) . ($d['diarsipkan'] ? ' <span class="badge b-abu">Diarsipkan</span>' : '') . '</p>';
echo '<table><tr><th>Jenis</th><td>' . e($d['jenis']) . '</td><th>Nomor dokumen</th><td>' . e($d['nomor_dokumen'] ?: '-') . '</td></tr><tr><th>Pemilik</th><td>' . e($d['pemilik']) . '</td><th>Bab</th><td>' . e($d['bab_nama'] ?: '-') . '</td></tr><tr><th>Berlaku sampai</th><td>' . e(tgl($d['berlaku_sampai'])) . '</td><th>Tinjau ulang</th><td>' . e(tgl($d['tinjau_ulang'])) . '</td></tr><tr><th>Versi aktif</th><td>' . (int)$d['versi_no'] . '</td><th>Tanggal validasi</th><td>' . e(tgl($d['validasi_pada'], true)) . '</td></tr></table>';
if ($st === 'revisi' && punya('lihat_riwayat')) {
  $c = row("SELECT catatan,pelaku_nama FROM log WHERE dokumen_id=? AND aksi='revisi' ORDER BY id DESC LIMIT 1", [$id]);
  if ($c) echo '<div class="catatan"><b>Catatan revisi dari ' . e($c['pelaku_nama']) . ':</b><br>' . nl2br(e($c['catatan'])) . '</div>';
}
if ($ver) echo '<p><a class="tombol" href="unduh.php?v=' . $ver . '">Unduh versi ' . (int)$d['versi_no'] . '</a></p>';
echo '</div>';
echo '<div class="kartu"><h2 class="jb">EP yang memakai dokumen ini (' . $aktifN . ')</h2><p>';
foreach ($eps as $x) { if (!$x['aktif']) continue; echo bolehEP($x) && punya('lihat_berkas') ? '<a class="badge b-biru" href="epdetail.php?id=' . (int)$x['id'] . '">' . e($x['nomor']) . '</a> ' : '<span class="badge b-abu">' . e($x['nomor']) . '</span> '; }
if (!$aktifN) echo '<span class="hint">Tidak ada tautan aktif.</span>';
echo '</p></div>';

if (!$d['diarsipkan']) {
  $aks = '';
  if ($st === 'menunggu') {
    if (punya('verifikasi') && $ling) $aks .= form_aksi('verifikasi', $ver, '', 'Verifikasi', 'tombol', 'Verifikasi versi ' . (int)$d['versi_no'] . ' dokumen ini?');
    if (punya('kembalikan') && $ling) $aks .= form_aksi('revisi', $ver, '<div><label>Catatan revisi</label><textarea name="catatan" required></textarea></div>', 'Kembalikan untuk revisi', 'tombol bahaya');
  }
  if ($st === 'terverifikasi') {
    if (punya('validasi') && $ling) $aks .= form_aksi('validasi', $ver, '<div><label>Nomor dokumen (SK / SPO) <small>(tidak wajib)</small></label><input type="text" name="nomor_dokumen" maxlength="100" value="' . e($d['nomor_dokumen']) . '"></div>', 'Validasi', 'tombol', 'Validasi versi ' . (int)$d['versi_no'] . ' dokumen ini?');
    if (punya('kembalikan') && $ling) $aks .= form_aksi('revisi', $ver, '<div><label>Catatan revisi</label><textarea name="catatan" required></textarea></div>', 'Kembalikan untuk revisi', 'tombol bahaya');
    if (punya('batal_verifikasi') && $ling) $aks .= form_aksi('batal_verifikasi', $ver, '<div><label>Alasan pembatalan</label><textarea name="catatan" required></textarea></div>', 'Batalkan verifikasi', 'tombol sekunder');
  }
  if ($st === 'valid') {
    if (punya('batal_validasi') && $ling) $aks .= form_aksi('batal_validasi', $ver, '<div><label>Alasan pembatalan</label><textarea name="catatan" required></textarea></div>', 'Batalkan validasi', 'tombol sekunder');
    if (punya('validasi') && $ling) $aks .= form_aksi('ubah_nomor', $ver, '<div><label>Nomor dokumen</label><input type="text" name="nomor_dokumen" maxlength="100" value="' . e($d['nomor_dokumen']) . '"></div>', 'Ubah nomor', 'tombol sekunder');
  }
  if ($aks) echo '<div class="kartu"><h2 class="jb">Tindakan</h2>' . $aks . '</div>';
}
if ($kel && punya('edit')) {
  echo '<div class="kartu"><h2 class="jb">Kelola dokumen</h2>';
  if ($d['diarsipkan']) {
    echo '<form method="post" class="baris-form">' . csrf_field() . '<input type="hidden" name="aksi" value="pulihkan"><label><input type="checkbox" name="tautan" value="1" checked> Kembalikan tautan EP yang lama</label><button class="tombol tambah">Pulihkan dokumen</button></form>';
  } else {
    echo '<div class="kotak"><h3>Ganti versi</h3><form method="post" enctype="multipart/form-data">' . csrf_field() . '<input type="hidden" name="aksi" value="versi_baru"><input type="file" name="berkas" required><label>Judul baru <small>(opsional)</small></label><input type="text" name="judul_baru" maxlength="200"><label>Catatan <small>(opsional)</small></label><input type="text" name="catatan"><p class="hint">Versi baru mengembalikan status ke Menunggu verifikasi di semua EP' . ($aktifN ? ' (dokumen ini dipakai oleh ' . $aktifN . ' EP)' : '') . '. File versi lama dihapus, riwayatnya tetap tercatat.</p><button class="tombol" data-konfirmasi="Unggah versi baru? File versi lama akan dihapus.">Unggah versi baru</button></form></div>';
    echo '<div class="kotak"><h3>Ubah data</h3><form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="edit"><label>Judul</label><input type="text" name="judul" maxlength="200" value="' . e($d['judul']) . '" required><label>Jenis</label><select name="jenis_id">' . opsi(rows("SELECT id,nama FROM jenis_dokumen ORDER BY id"), $d['jenis_id']) . '</select><div class="grid2"><div><label>Berlaku sampai</label><input type="date" name="berlaku_sampai" value="' . e($d['berlaku_sampai']) . '"></div><div><label>Tinjau ulang</label><input type="date" name="tinjau_ulang" value="' . e($d['tinjau_ulang']) . '"></div></div><p><button class="tombol sekunder">Simpan</button></p></form></div>';
  }
  $peringatan = $aktifN ? 'Dokumen ini dipakai oleh ' . $aktifN . ' EP. ' : '';
  echo '<div class="kotak"><h3>Arsip atau hapus</h3><p class="hint">' . e($peringatan) . 'Arsip menyembunyikan dokumen dan melepas semua tautan EP; dokumen bisa dipulihkan. Hapus permanen menghapus file dan tidak bisa dibatalkan.</p><form method="post" class="baris-form">' . csrf_field() . '<label><input type="checkbox" name="konfirmasi" value="1"> ' . e($peringatan) . 'Saya mengerti dan ingin melanjutkan.</label>';
  if (!$d['diarsipkan']) echo '<button name="aksi" value="arsip" class="tombol sekunder">Arsipkan</button>';
  echo '<button name="aksi" value="hapus" class="tombol bahaya" data-konfirmasi="Hapus permanen dokumen ini? Tindakan tidak bisa dibatalkan.">Hapus permanen</button></form></div></div>';
}
if (admin_() || ($U['cakupan'] === 'bab' && $U['bab_id'] && (int)$U['bab_id'] === (int)$d['bab_id'])) {
  $cal = rows("SELECT p.id, CONCAT(p.nama,' (',r.nama,')') nama FROM pengguna p JOIN peran r ON r.id=p.peran_id WHERE p.aktif=1 AND r.cakupan IN ('tugas','bab') ORDER BY p.nama");
  echo '<div class="kartu"><h2 class="jb">Pindah kepemilikan</h2><form method="post" class="baris-form">' . csrf_field() . '<input type="hidden" name="aksi" value="pindah_pemilik"><select name="pemilik_id">' . opsi($cal, $d['pemilik_id']) . '</select><button class="tombol sekunder" data-konfirmasi="Pindahkan kepemilikan dokumen ini?">Pindahkan</button></form></div>';
}
if (!surveior()) {
  $lap = rows("SELECT l.*, u.nama pelapor FROM laporan l JOIN pengguna u ON u.id=l.pelapor_id WHERE l.dokumen_id=? ORDER BY l.status='selesai', l.id DESC", [$id]);
  $lap = array_values(array_filter($lap, function ($l) use ($kel, $U) { return $kel || (int)$l['pelapor_id'] === (int)$U['id']; }));
  if ($lap) {
    echo '<div class="kartu"><h2 class="jb">Laporan masalah</h2>';
    foreach ($lap as $l) {
      echo '<div class="kotak"><p><b>' . e($l['pelapor']) . '</b> · ' . e(tgl($l['dibuat'], true)) . ' ' . ($l['status'] === 'terbuka' ? '<span class="badge b-merah">Terbuka</span>' : '<span class="badge b-hijau">Selesai</span>') . '</p><p>' . nl2br(e($l['pesan'])) . '</p>';
      if ($l['status'] === 'selesai' && $l['tanggapan']) echo '<p class="hint">Tanggapan: ' . nl2br(e($l['tanggapan'])) . '</p>';
      if ($l['status'] === 'terbuka' && $kel && !$d['diarsipkan']) echo '<form method="post" class="baris-form">' . csrf_field() . '<input type="hidden" name="aksi" value="laporan_selesai"><input type="hidden" name="laporan_id" value="' . (int)$l['id'] . '"><input type="text" name="catatan" placeholder="Tanggapan (opsional)"><button class="tombol kecil tambah">Tandai selesai</button></form>';
      echo '</div>';
    }
    echo '</div>';
  }
  if (punya('lihat_berkas') && !$d['diarsipkan']) {
    echo '<div class="kartu"><h2 class="jb">Diskusi</h2>';
    foreach (rows("SELECT k.*, u.nama FROM komentar k JOIN pengguna u ON u.id=k.pengirim_id WHERE k.dokumen_id=? ORDER BY k.id", [$id]) as $k) echo '<div class="kotak"><p><b>' . e($k['nama']) . '</b> <span class="hint">' . e(tgl($k['dibuat'], true)) . '</span></p><p>' . nl2br(e($k['isi'])) . '</p></div>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="komentar"><textarea name="catatan" maxlength="2000" placeholder="Tulis komentar" required></textarea><p><button class="tombol sekunder">Kirim komentar</button></p></form></div>';
  }
}
echo '<div class="kartu"><h2 class="jb">Riwayat versi</h2><div class="gulung"><table><tr><th>Versi</th><th>Judul</th><th>Pengunggah</th><th>Tanggal</th><th>SHA-256</th><th>File</th></tr>';
foreach ($versi as $v) echo '<tr><td>' . (int)$v['nomor'] . '</td><td>' . e($v['judul']) . '</td><td>' . e($v['oleh']) . '</td><td>' . e(tgl($v['dibuat'], true)) . '</td><td title="' . e($v['hash']) . '">' . e(substr($v['hash'], 0, 12)) . '…</td><td>' . ($v['file_dihapus'] ? '<span class="hint">File versi ini sudah dihapus</span>' : '<a href="unduh.php?v=' . (int)$v['id'] . '">Unduh</a>') . '</td></tr>';
echo '</table></div></div>';
if (punya('lihat_riwayat')) {
  $rw = rows("SELECT * FROM log WHERE dokumen_id=? ORDER BY id DESC LIMIT 100", [$id]);
  echo '<div class="kartu"><h2 class="jb">Riwayat aktivitas</h2><div class="gulung"><table><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Catatan</th></tr>';
  foreach ($rw as $r) echo '<tr><td>' . e(tgl($r['waktu'], true)) . '</td><td>' . e($r['pelaku_nama']) . '</td><td>' . e(LABEL_AKSI[$r['aksi']] ?? $r['aksi']) . '</td><td>' . nl2br(e(trim($r['catatan'] . "\n" . $r['info']))) . '</td></tr>';
  echo '</table></div></div>';
}
kaki();
