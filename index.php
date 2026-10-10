<?php
require __DIR__ . '/inc/mulai.php';
wajib_login();
$dasar = "SELECT e.id, e.tdd, s.bab_id, s.id sid, " . EPST . " st FROM ep e JOIN standar s ON s.id=e.standar_id";
$agg = "COUNT(*) total, SUM(tdd=1) tdd, SUM(tdd=0 AND st=4) valid, SUM(tdd=0 AND st>=2) terisi, SUM(tdd=0 AND st=1) revisi";
$bab = rows("SELECT b.id,b.nomor,b.nama,pj.nama pj,x.* FROM bab b LEFT JOIN pengguna pj ON pj.id=b.pj_id LEFT JOIN (SELECT bab_id, $agg FROM ($dasar) y GROUP BY bab_id) x ON x.bab_id=b.id ORDER BY b.nomor");
$std = rows("SELECT s.id,s.bab_id,s.nomor,s.nama,x.* FROM standar s LEFT JOIN (SELECT sid, $agg FROM ($dasar) y GROUP BY sid) x ON x.sid=s.id ORDER BY s.bab_id, s.id");
$ang = rows("SELECT u.id,u.nama,b.nomor bab_no, COUNT(pn.ep_id) total, SUM(y.tdd=0 AND y.st=4) valid, SUM(y.tdd=0 AND y.st=0) belum, SUM(y.tdd=0 AND y.st=1) revisi, SUM(y.tdd=0 AND y.st IN (2,3)) proses FROM pengguna u JOIN peran r ON r.id=u.peran_id AND r.cakupan='tugas' LEFT JOIN bab b ON b.id=u.bab_id LEFT JOIN penugasan pn ON pn.anggota_id=u.id LEFT JOIN ($dasar) y ON y.id=pn.ep_id WHERE u.aktif=1 GROUP BY u.id, u.nama, b.nomor ORDER BY b.nomor, u.nama");
$T = ['total' => 0, 'tdd' => 0, 'valid' => 0, 'terisi' => 0, 'revisi' => 0];
foreach ($bab as $b) foreach ($T as $k => $_) $T[$k] += (int)$b[$k];
$aktifEP = $T['total'] - $T['tdd'];
$segera = punya('lihat_daftar_dokumen') ? (int)val("SELECT COUNT(*) FROM dokumen WHERE diarsipkan=0 AND berlaku_sampai IS NOT NULL AND berlaku_sampai <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)") : 0;
kepala('Dashboard', 'dash');
echo '<div class="kartu"><h2 class="jb">Progres akreditasi</h2><div class="angka-besar">';
echo '<div><b>' . pct($T['valid'], $aktifEP) . '%</b>EP berstatus Valid</div><div><b>' . $T['valid'] . ' / ' . $aktifEP . '</b>EP Valid dari EP berlaku</div><div><b>' . $T['terisi'] . '</b>EP sudah ada dokumen</div><div><b>' . $T['revisi'] . '</b>EP perlu revisi</div><div><b>' . $T['tdd'] . '</b>EP TDD</div>';
echo '</div>';
if ($segera) echo '<p class="catatan">' . $segera . ' dokumen sudah atau akan berakhir masa berlakunya dalam 90 hari. <a href="daftar_dokumen.php?tab=berakhir">Lihat</a></p>';
echo '</div>';
require __DIR__ . '/inc/kartu_survei.php';
echo '<div class="kartu"><h2 class="jb">Progres per bab</h2><div class="gulung"><table><tr><th>Bab</th><th>PJ</th><th>EP</th><th>Valid</th><th>Progres</th></tr>';
foreach ($bab as $b) { $a = (int)$b['total'] - (int)$b['tdd']; $p = pct($b['valid'], $a);
  echo '<tr><td><b>' . e($b['nomor']) . '</b>. ' . e($b['nama']) . '</td><td>' . e($b['pj'] ?: '-') . '</td><td>' . $a . '</td><td>' . (int)$b['valid'] . '</td><td>' . bar($p) . ' ' . $p . '%</td></tr>'; }
echo '</table></div></div><div class="kartu"><h2 class="jb">Progres per standar</h2>';
foreach ($bab as $b) {
  echo '<details><summary><b>Bab ' . e($b['nomor']) . '</b> ' . e($b['nama']) . '</summary><div class="gulung"><table><tr><th>Standar</th><th>EP</th><th>Valid</th><th>Revisi</th><th>Progres</th></tr>';
  foreach ($std as $s) if ((int)$s['bab_id'] === (int)$b['id']) { $a = (int)$s['total'] - (int)$s['tdd']; $p = pct($s['valid'], $a);
    echo '<tr><td>' . e(nama_std($s['nomor'], (string)$s['nama'])) . '</td><td>' . $a . '</td><td>' . (int)$s['valid'] . '</td><td>' . (int)$s['revisi'] . '</td><td>' . bar($p) . ' ' . $p . '%</td></tr>'; }
  echo '</table></div></details>';
}
if (!$std) echo '<p class="hint">Belum ada EP. Super Admin dapat mengimpor EP lewat Administrasi → Impor EP.</p>';
echo '</div><div class="kartu"><h2 class="jb">Progres per Anggota Tim</h2><div class="gulung"><table><tr><th>Nama</th><th>Bab</th><th>Tugas</th><th>Belum</th><th>Proses</th><th>Revisi</th><th>Valid</th></tr>';
foreach ($ang as $a) echo '<tr><td>' . e($a['nama']) . '</td><td>' . e($a['bab_no'] ?: '-') . '</td><td>' . (int)$a['total'] . '</td><td>' . (int)$a['belum'] . '</td><td>' . (int)$a['proses'] . '</td><td>' . (int)$a['revisi'] . '</td><td>' . (int)$a['valid'] . '</td></tr>';
if (!$ang) echo '<tr><td colspan="7" class="hint">Belum ada Anggota Tim.</td></tr>';
echo '</table></div></div>';
kaki();
