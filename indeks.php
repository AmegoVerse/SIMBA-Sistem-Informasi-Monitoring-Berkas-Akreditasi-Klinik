<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('lihat_berkas');
$fb = (int)($_GET['bab'] ?? 0);
$w = '1=1'; $p = []; if ($fb) { $w = 's.bab_id=?'; $p = [$fb]; }
$eps = rows("SELECT e.id,e.nomor,e.bunyi,e.tdd,e.tdd_alasan,s.nomor snomor,s.bab_id FROM ep e JOIN standar s ON s.id=e.standar_id WHERE $w ORDER BY e.urut", $p);
$dk = [];
foreach (rows("SELECT de.ep_id, d.id,d.judul,d.nomor_dokumen,d.pemilik_id,d.bab_id,d.status,d.diarsipkan,j.nama jenis FROM dokumen_ep de JOIN dokumen d ON d.id=de.dokumen_id AND d.diarsipkan=0 AND d.status='valid' LEFT JOIN jenis_dokumen j ON j.id=d.jenis_id WHERE de.aktif=1 ORDER BY j.nama, d.judul") as $r) $dk[(int)$r['ep_id']][] = $r;
kepala('Indeks EP', 'indeks');
echo '<div class="kartu"><h2 class="jb">Indeks EP dan dokumen Valid</h2><p class="hint">Daftar EP beserta dokumen berstatus Valid yang menjadi buktinya. Draf yang belum divalidasi tidak ditampilkan.</p>';
echo '<form method="get" class="baris-form noprint"><select name="bab"><option value="">Semua bab</option>' . opsi(rows("SELECT id, CONCAT('Bab ',nomor,' – ',nama) nama FROM bab ORDER BY nomor"), $fb) . '</select><button class="tombol sekunder">Tampilkan</button><a href="#" class="tombol" data-cetak>Cetak / simpan PDF</a></form>';
echo '<p class="hint">Dicetak ' . e(date('d/m/Y H:i')) . ' · ' . e(trim(nama_instansi() . ' ' . alamat_instansi())) . '</p><div class="gulung"><table><tr><th style="width:70px">EP</th><th>Bunyi EP</th><th>Dokumen Valid</th></tr>';
$sn = '';
foreach ($eps as $e) {
  if ($e['snomor'] !== $sn) { $sn = $e['snomor']; echo '<tr class="grup"><td colspan="3">Standar ' . e($sn) . '</td></tr>'; }
  echo '<tr><td><b>' . e($e['nomor']) . '</b></td><td>' . e(potong($e['bunyi'], 160)) . '</td><td>';
  if ($e['tdd']) echo '<span class="badge b-abu">TDD</span> ' . e($e['tdd_alasan']);
  elseif (!empty($dk[(int)$e['id']])) { foreach ($dk[(int)$e['id']] as $d) echo (boleh_lihat_dok($d) ? '<a href="dokumen.php?id=' . (int)$d['id'] . '">' . e($d['judul']) . '</a>' : e($d['judul'])) . ' <small>[' . e($d['jenis']) . ($d['nomor_dokumen'] ? ' · No. ' . e($d['nomor_dokumen']) : '') . ']</small><br>'; }
  else echo '<span class="hint">Belum ada dokumen Valid</span>';
  echo '</td></tr>';
}
if (!$eps) echo '<tr><td colspan="3" class="hint">Belum ada EP.</td></tr>';
echo '</table></div></div>';
kaki();
