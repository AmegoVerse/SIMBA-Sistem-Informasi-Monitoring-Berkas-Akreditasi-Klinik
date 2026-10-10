<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('lihat_daftar_dokumen');
$tab = $_GET['tab'] ?? 'valid';
if (!in_array($tab, ['valid', 'belum', 'berakhir', 'arsip'], true)) $tab = 'valid';
$w = []; $p = [];
if ($tab === 'valid') { $w[] = "d.diarsipkan=0 AND d.status='valid'"; }
elseif ($tab === 'belum') { $w[] = "d.diarsipkan=0 AND d.status<>'valid'"; $sf = $_GET['st'] ?? ''; if (in_array($sf, ['menunggu', 'terverifikasi', 'revisi'], true)) { $w[] = 'd.status=?'; $p[] = $sf; } }
elseif ($tab === 'berakhir') { $w[] = "d.diarsipkan=0 AND d.berlaku_sampai IS NOT NULL AND d.berlaku_sampai <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)"; }
else {
  $w[] = 'd.diarsipkan=1';
  if (!admin_()) { if ($U['cakupan'] === 'bab') { $w[] = 'd.bab_id=?'; $p[] = (int)$U['bab_id']; } else { $w[] = 'd.pemilik_id=?'; $p[] = (int)$U['id']; } }
}
$fb = (int)($_GET['bab'] ?? 0); $fj = (int)($_GET['jenis'] ?? 0); $fq = trim((string)($_GET['q'] ?? ''));
if ($fb) { $w[] = 'd.bab_id=?'; $p[] = $fb; }
if ($fj) { $w[] = 'd.jenis_id=?'; $p[] = $fj; }
if ($fq !== '') { $w[] = '(d.judul LIKE ? OR d.nomor_dokumen LIKE ?)'; $l = '%' . addcslashes($fq, '%_') . '%'; $p[] = $l; $p[] = $l; }
$where = implode(' AND ', $w);
$from = "FROM dokumen d LEFT JOIN jenis_dokumen j ON j.id=d.jenis_id JOIN pengguna u ON u.id=d.pemilik_id LEFT JOIN bab b ON b.id=d.bab_id LEFT JOIN dokumen_versi vr ON vr.id=d.versi_aktif_id WHERE $where";
$total = (int)val("SELECT COUNT(*) $from", $p);
$per = 50;
$data = rows("SELECT d.*, j.nama jenis, u.nama pemilik, b.nomor bab_no, vr.nomor versi_no, (SELECT COUNT(*) FROM dokumen_ep de WHERE de.dokumen_id=d.id AND de.aktif=1) jml $from ORDER BY d.judul LIMIT ? OFFSET ?", array_merge($p, [$per, $per * (hal() - 1)]));
$cnt = function ($c) { return (int)val("SELECT COUNT(*) FROM dokumen d WHERE $c"); };
kepala('Daftar Dokumen', 'daftar');
echo '<div class="kartu"><h2 class="jb">Daftar dokumen</h2><div class="tab">';
foreach (['valid' => 'Sudah Valid (' . $cnt("d.diarsipkan=0 AND d.status='valid'") . ')', 'belum' => 'Belum Valid (' . $cnt("d.diarsipkan=0 AND d.status<>'valid'") . ')', 'berakhir' => 'Segera berakhir', 'arsip' => 'Arsip'] as $k => $v) echo '<a href="?tab=' . $k . '"' . ($tab === $k ? ' class="aktif"' : '') . '>' . e($v) . '</a>';
echo '</div><form method="get" class="baris-form"><input type="hidden" name="tab" value="' . e($tab) . '">';
if ($tab === 'belum') { echo '<select name="st"><option value="">Semua status</option>'; foreach (['menunggu' => 'Menunggu verifikasi', 'terverifikasi' => 'Terverifikasi PJ', 'revisi' => 'Revisi'] as $k => $v) echo '<option value="' . $k . '"' . (($sf ?? '') === $k ? ' selected' : '') . '>' . $v . '</option>'; echo '</select>'; }
echo '<select name="bab"><option value="">Semua bab</option>' . opsi(rows("SELECT id, CONCAT('Bab ',nomor) nama FROM bab ORDER BY nomor"), $fb) . '</select><select name="jenis"><option value="">Semua jenis</option>' . opsi(rows("SELECT id,nama FROM jenis_dokumen ORDER BY nama"), $fj) . '</select><input type="text" name="q" value="' . e($fq) . '" placeholder="Cari judul atau nomor"><button class="tombol">Cari</button></form>';
echo '<div class="gulung"><table><tr><th>Judul</th><th>Jenis</th><th>Nomor</th><th>Pemilik</th><th>Bab</th><th>Status</th><th>Ver.</th><th>EP</th><th>Divalidasi</th><th>Berlaku s.d.</th></tr>';
$hari = date('Y-m-d'); $s90 = date('Y-m-d', strtotime('+90 days'));
foreach ($data as $r) {
  $bl = tgl($r['berlaku_sampai']);
  if ($r['berlaku_sampai']) { if ($r['berlaku_sampai'] < $hari) $bl .= ' <span class="badge b-merah">Lewat</span>'; elseif ($r['berlaku_sampai'] <= $s90) $bl .= ' <span class="badge b-kuning">≤ 90 hari</span>'; }
  echo '<tr><td>' . (boleh_lihat_dok($r) ? '<a href="dokumen.php?id=' . (int)$r['id'] . '">' . e($r['judul']) . '</a>' : e($r['judul'])) . '</td><td>' . e($r['jenis']) . '</td><td>' . e($r['nomor_dokumen'] ?: '-') . '</td><td>' . e($r['pemilik']) . '</td><td>' . e($r['bab_no'] ?: '-') . '</td><td>' . badge_dok($r['status']) . '</td><td>' . (int)$r['versi_no'] . '</td><td>' . (int)$r['jml'] . '</td><td>' . e(tgl($r['validasi_pada'])) . '</td><td>' . $bl . '</td></tr>';
}
if (!$data) echo '<tr><td colspan="10" class="hint">Tidak ada dokumen.</td></tr>';
echo '</table></div>' . pager($total, $per) . '</div>';
kaki();
