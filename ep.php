<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('lihat_berkas');
$w = ['1=1']; $p = [];
if ($U['cakupan'] === 'bab') { $w[] = 'bab_id=?'; $p[] = (int)$U['bab_id']; }
elseif ($U['cakupan'] === 'tugas') { $w[] = 'anggota_id=?'; $p[] = (int)$U['id']; }
$fb = (int)($_GET['bab'] ?? 0); $fs = $_GET['st'] ?? ''; $fq = trim((string)($_GET['q'] ?? ''));
if ($fb && semua()) { $w[] = 'bab_id=?'; $p[] = $fb; }
if ($fs === 'tdd') $w[] = 'tdd=1';
elseif ($fs !== '' && ctype_digit((string)$fs) && (int)$fs <= 4) { $w[] = 'tdd=0 AND st=?'; $p[] = (int)$fs; }
if ($fq !== '') { $w[] = '(nomor LIKE ? OR bunyi LIKE ?)'; $l = '%' . addcslashes($fq, '%_') . '%'; $p[] = $l; $p[] = $l; }
$dasar = "SELECT e.id,e.nomor,e.urut,e.bunyi,e.tdd,s.bab_id,pn.anggota_id,pn.tenggat,u.nama anggota," . EPST . " st,(SELECT COUNT(*) FROM dokumen_ep dx WHERE dx.ep_id=e.id AND dx.aktif=1) jml FROM ep e JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id LEFT JOIN pengguna u ON u.id=pn.anggota_id";
$where = implode(' AND ', $w);
$total = (int)val("SELECT COUNT(*) FROM ($dasar) z WHERE $where", $p);
$per = 50;
$data = rows("SELECT * FROM ($dasar) z WHERE $where ORDER BY urut LIMIT ? OFFSET ?", array_merge($p, [$per, ($per) * (hal() - 1)]));
kepala('Berkas', 'ep');
echo '<div class="kartu"><h2 class="jb">Berkas per EP</h2><form method="get" class="baris-form">';
if (semua()) echo '<select name="bab"><option value="">Semua bab</option>' . opsi(rows("SELECT id, CONCAT('Bab ',nomor) nama FROM bab ORDER BY nomor"), $fb) . '</select>';
echo '<select name="st"><option value="">Semua status</option>';
foreach (['0' => 'Belum diunggah', '1' => 'Revisi', '2' => 'Menunggu verifikasi', '3' => 'Terverifikasi PJ', '4' => 'Valid', 'tdd' => 'TDD'] as $k => $v) echo '<option value="' . $k . '"' . ((string)$fs === (string)$k ? ' selected' : '') . '>' . $v . '</option>';
echo '</select><input type="text" name="q" value="' . e($fq) . '" placeholder="Cari nomor atau bunyi EP"><button class="tombol">Cari</button></form>';
echo '<div class="gulung"><table><tr><th>EP</th><th>Bunyi EP</th><th>Pemegang</th><th>Tenggat</th><th>Dokumen</th><th>Status</th></tr>';
foreach ($data as $r) echo '<tr><td><a href="epdetail.php?id=' . (int)$r['id'] . '"><b>' . e($r['nomor']) . '</b></a></td><td>' . e(potong($r['bunyi'], 110)) . '</td><td>' . e($r['anggota'] ?: '-') . '</td><td>' . e(tgl($r['tenggat'])) . '</td><td>' . (int)$r['jml'] . '</td><td>' . badge_ep($r['st'], $r['tdd']) . '</td></tr>';
if (!$data) echo '<tr><td colspan="6" class="hint">Tidak ada EP yang cocok.</td></tr>';
echo '</table></div>' . pager($total, $per) . '</div>';
kaki();
