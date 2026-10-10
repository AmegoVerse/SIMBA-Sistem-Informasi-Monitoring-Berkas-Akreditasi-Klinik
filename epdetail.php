<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('lihat_berkas');
$id = (int)($_GET['id'] ?? 0);
$ep = ep_row($id);
if (!$ep || !bolehEP($ep)) tolak('EP tidak ditemukan atau bukan bagian dari tugas Anda.');
$self = 'epdetail.php?id=' . $id;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  try {
    $a = $_POST['aksi'] ?? ''; $did = (int)($_POST['dokumen_id'] ?? 0);
    if ($a !== 'lapor') wajib('tautkan');
    $d = row("SELECT * FROM dokumen WHERE id=?", [$did]);
    if (!$d) throw new Pesan('Dokumen tidak ditemukan.');
    $lg = ['bab_id' => $ep['bab_id'], 'ep_id' => $id, 'dokumen_id' => $did, 'judul' => $d['judul'], 'nomor' => $d['nomor_dokumen'], 'info' => 'EP ' . $ep['nomor']];
    if ($a === 'tautkan') {
      if ($d['diarsipkan']) throw new Pesan('Dokumen sudah diarsipkan.');
      if ($ep['tdd']) throw new Pesan('EP ini berstatus TDD.');
      ex("INSERT INTO dokumen_ep (dokumen_id,ep_id,ditautkan_oleh,aktif) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE aktif=1, ditautkan_oleh=VALUES(ditautkan_oleh)", [$did, $id, (int)$U['id']]);
      log_tulis('tautkan', $lg); flash('ok', 'Dokumen ditautkan ke EP ' . $ep['nomor'] . '.');
    } elseif ($a === 'lapor') {
      if (surveior()) tolak();
      $ps = trim((string)($_POST['pesan'] ?? ''));
      if ($ps === '' || mb_strlen($ps) > 1000) throw new Pesan('Tulis pesan laporan (maksimal 1000 karakter).');
      if (!val("SELECT 1 FROM dokumen_ep WHERE dokumen_id=? AND ep_id=? AND aktif=1", [$did, $id])) throw new Pesan('Dokumen tidak tertaut ke EP ini.');
      ins("INSERT INTO laporan (dokumen_id,ep_id,pelapor_id,pesan) VALUES (?,?,?,?)", [$did, $id, (int)$U['id'], $ps]);
      log_tulis('lapor', array_merge($lg, ['bab_id' => $d['bab_id'], 'catatan' => $ps]));
      flash('ok', 'Laporan dikirim ke pemilik dokumen dan PJ bab terkait.');
    } elseif ($a === 'lepas') {
      ex("DELETE FROM dokumen_ep WHERE dokumen_id=? AND ep_id=?", [$did, $id]);
      log_tulis('lepas_tautan', $lg); flash('ok', 'Tautan dilepas. Dokumen tidak dihapus.');
    }
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
  redirect($self);
}
$docs = rows("SELECT d.*, j.nama jenis, u.nama pemilik, vr.nomor versi_no FROM dokumen_ep de JOIN dokumen d ON d.id=de.dokumen_id AND d.diarsipkan=0 LEFT JOIN jenis_dokumen j ON j.id=d.jenis_id JOIN pengguna u ON u.id=d.pemilik_id LEFT JOIN dokumen_versi vr ON vr.id=d.versi_aktif_id WHERE de.ep_id=? AND de.aktif=1 ORDER BY d.judul", [$id]);
$bisaTaut = punya('tautkan') && !$ep['tdd'];
kepala('EP ' . $ep['nomor'], 'ep');
echo '<div class="kartu"><h2 class="jb">EP ' . e($ep['nomor']) . ' · ' . e(nama_std($ep['snomor'], (string)$ep['snama'])) . '</h2><p>' . nl2br(e($ep['bunyi'])) . '</p>';
echo '<p>' . badge_ep($ep['st'], $ep['tdd']) . ' &nbsp; Pemegang: <b>' . e($ep['anggota_nama'] ?: '-') . '</b> &nbsp; Tenggat: <b>' . e(tgl($ep['tenggat'])) . '</b> &nbsp; Skor: ' . e($ep['skor'] ?: '-') . '</p>';
if ($ep['tdd']) echo '<div class="catatan">EP ini ditandai TDD (tidak dapat diterapkan). Alasan: ' . e($ep['tdd_alasan']) . '</div>';
if ($ep['bukti']) echo '<details><summary>Kelengkapan bukti yang diminta</summary><p>' . nl2br(e($ep['bukti'])) . '</p></details>';
echo '</div><div class="kartu"><h2 class="jb">Dokumen pada EP ini</h2><div class="gulung"><table><tr><th>Dokumen</th><th>Jenis</th><th>Nomor</th><th>Versi</th><th>Pemilik</th><th>Status</th><th></th></tr>';
foreach ($docs as $d) {
  echo '<tr><td>' . (boleh_lihat_dok($d) ? '<a href="dokumen.php?id=' . (int)$d['id'] . '">' . e($d['judul']) . '</a>' : e($d['judul'])) . '</td><td>' . e($d['jenis']) . '</td><td>' . e($d['nomor_dokumen'] ?: '-') . '</td><td>' . (int)$d['versi_no'] . '</td><td>' . e($d['pemilik']) . '</td><td>' . badge_dok($d['status']) . '</td><td>';
  if ($bisaTaut) echo '<form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="lepas"><input type="hidden" name="dokumen_id" value="' . (int)$d['id'] . '"><button class="tombol kecil bahaya" data-konfirmasi="Lepas tautan dokumen ini dari EP ' . e($ep['nomor']) . '? Dokumen tidak dihapus.">Lepas</button></form>';
  if (punya('lihat_berkas') && (int)$d['pemilik_id'] !== (int)$U['id']) echo '<details><summary class="hint">Laporkan masalah</summary><form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="lapor"><input type="hidden" name="dokumen_id" value="' . (int)$d['id'] . '"><textarea name="pesan" maxlength="1000" placeholder="Jelaskan masalah pada dokumen ini" required></textarea><button class="tombol kecil sekunder">Kirim laporan</button></form></details>';
  echo '</td></tr>';
}
if (!$docs) echo '<tr><td colspan="7" class="hint">Belum ada dokumen.</td></tr>';
echo '</table></div>';
if (punya('unggah') && !$ep['tdd']) echo '<p><a class="tombol" href="unggah.php?ep=' . $id . '">Unggah dokumen baru</a></p>';
echo '</div>';
if ($bisaTaut) {
  $q = trim((string)($_GET['q'] ?? '')); $jn = (int)($_GET['jenis'] ?? 0);
  echo '<div class="kartu"><h2 class="jb">Tautkan dokumen yang sudah ada</h2><form method="get" class="baris-form"><input type="hidden" name="id" value="' . $id . '"><input type="text" name="q" value="' . e($q) . '" placeholder="Cari judul dokumen"><select name="jenis">' . '<option value="">Semua jenis</option>' . opsi(rows("SELECT id,nama FROM jenis_dokumen ORDER BY nama"), $jn) . '</select><button class="tombol">Cari</button></form>';
  if ($q !== '' || $jn) {
    $w = "d.diarsipkan=0 AND d.judul LIKE ? AND d.id NOT IN (SELECT dokumen_id FROM dokumen_ep WHERE ep_id=? AND aktif=1)"; $p = ['%' . addcslashes($q, '%_') . '%', $id];
    if ($jn) { $w .= ' AND d.jenis_id=?'; $p[] = $jn; }
    $hasil = rows("SELECT d.id,d.judul,j.nama jenis,u.nama pemilik FROM dokumen d LEFT JOIN jenis_dokumen j ON j.id=d.jenis_id JOIN pengguna u ON u.id=d.pemilik_id WHERE $w ORDER BY d.judul LIMIT 30", $p);
    echo '<div class="gulung"><table><tr><th>Judul</th><th>Jenis</th><th>Pemilik</th><th></th></tr>';
    foreach ($hasil as $h) echo '<tr><td>' . e($h['judul']) . '</td><td>' . e($h['jenis']) . '</td><td>' . e($h['pemilik']) . '</td><td><form method="post">' . csrf_field() . '<input type="hidden" name="aksi" value="tautkan"><input type="hidden" name="dokumen_id" value="' . (int)$h['id'] . '"><button class="tombol kecil tambah">Tautkan</button></form></td></tr>';
    if (!$hasil) echo '<tr><td colspan="4" class="hint">Tidak ada dokumen yang cocok.</td></tr>';
    echo '</table></div>';
  }
  echo '</div>';
}
kaki();
