<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (surveior()) tolak();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib(); wajib('nilai');
  try {
    $sk = (array)($_POST['skor'] ?? []); $ct = (array)($_POST['cat'] ?? []); $lm = (array)($_POST['lama'] ?? []);
    $per = []; $n = 0;
    foreach ($sk as $eid => $s) {
      $eid = (int)$eid; $s = trim((string)$s); $c = trim((string)($ct[$eid] ?? ''));
      if (mb_strlen($c) > 500) throw new Pesan('Catatan maksimal 500 karakter.');
      if ($s !== '' && !in_array($s, ['0', '5', '10'], true)) continue;
      if ($s . '|' . $c === (string)($lm[$eid] ?? '')) continue;
      $ep = ep_row($eid);
      if (!$ep || !bolehEP($ep) || $ep['tdd']) continue;
      if ($s === '') ex("DELETE FROM penilaian WHERE ep_id=?", [$eid]);
      else ex("INSERT INTO penilaian (ep_id,skor,catatan,dinilai_oleh) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE skor=VALUES(skor), catatan=VALUES(catatan), dinilai_oleh=VALUES(dinilai_oleh)", [$eid, (int)$s, $c !== '' ? $c : null, (int)$U['id']]);
      $per[(int)$ep['bab_id']][] = $ep['nomor'] . '=' . ($s === '' ? '-' : $s); $n++;
    }
    foreach ($per as $b => $l) log_tulis('nilai', ['bab_id' => $b, 'info' => 'EP ' . implode(', ', $l)]);
    flash('ok', $n ? $n . ' penilaian disimpan.' : 'Tidak ada perubahan.');
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
  redirect('penilaian.php?' . http_build_query(array_diff_key($_GET, ['h' => 1])));
}
$ambang = pengaturan('ambang_nilai');
$rk = rows("SELECT s.bab_id, s.id sid, s.nomor, SUM(e.tdd=0) berlaku, SUM(e.tdd=0 AND pm.skor IS NOT NULL) dinilai, SUM(CASE WHEN e.tdd=0 THEN COALESCE(pm.skor,0) ELSE 0 END) jumlah, SUM(e.tdd=0 AND pm.skor=10) s10, SUM(e.tdd=0 AND pm.skor=5) s5, SUM(e.tdd=0 AND pm.skor=0) s0 FROM standar s JOIN ep e ON e.standar_id=s.id LEFT JOIN penilaian pm ON pm.ep_id=e.id GROUP BY s.bab_id, s.id, s.nomor ORDER BY s.bab_id, s.id");
$babs = rows("SELECT id,nomor,nama FROM bab ORDER BY nomor");
$tot = ['b' => 0, 'j' => 0]; $pb = [];
foreach ($rk as $r) { $b = (int)$r['bab_id']; $pb[$b] = $pb[$b] ?? ['berlaku' => 0, 'dinilai' => 0, 'jumlah' => 0, 's10' => 0, 's5' => 0, 's0' => 0]; foreach (['berlaku', 'dinilai', 'jumlah', 's10', 's5', 's0'] as $k) $pb[$b][$k] += (int)$r[$k]; }
function kelas_capaian($p, $ambang){ return $ambang !== '' ? ($p >= (int)$ambang ? 'b-hijau' : 'b-kuning') : 'b-biru'; }
kepala('Penilaian Mandiri', 'penilaian');
echo '<div class="kartu"><h2 class="jb">Rekap penilaian mandiri</h2><p class="hint">Skor per EP: 10 (lengkap), 5 (sebagian), 0 (belum). Capaian = jumlah skor ÷ (10 × EP berlaku). EP TDD tidak dihitung.' . ($ambang !== '' ? ' Target yang diatur Super Admin: <b>' . e($ambang) . '%</b> (cocokkan dengan Permenkes 34/2022).' : '') . '</p><div class="gulung"><table><tr><th>Bab</th><th>EP</th><th>Dinilai</th><th>10</th><th>5</th><th>0</th><th>Capaian</th></tr>';
$tj = 0; $tb = 0;
foreach ($babs as $b) { $x = $pb[(int)$b['id']] ?? ['berlaku' => 0, 'dinilai' => 0, 'jumlah' => 0, 's10' => 0, 's5' => 0, 's0' => 0]; $p = pct($x['jumlah'], 10 * $x['berlaku']); $tj += $x['jumlah']; $tb += $x['berlaku'];
  echo '<tr><td><b>' . (int)$b['nomor'] . '</b>. ' . e($b['nama']) . '</td><td>' . $x['berlaku'] . '</td><td>' . $x['dinilai'] . '</td><td>' . $x['s10'] . '</td><td>' . $x['s5'] . '</td><td>' . $x['s0'] . '</td><td><span class="badge ' . kelas_capaian($p, $ambang) . '">' . $p . '%</span></td></tr>'; }
$pt = pct($tj, 10 * $tb);
echo '<tr class="tebal-baris"><td>Seluruhnya</td><td>' . $tb . '</td><td colspan="4"></td><td><span class="badge ' . kelas_capaian($pt, $ambang) . '">' . $pt . '%</span></td></tr></table></div>';
echo '<details><summary>Rekap per standar</summary><div class="gulung"><table><tr><th>Standar</th><th>EP</th><th>Dinilai</th><th>Capaian</th></tr>';
foreach ($rk as $r) { $p = pct($r['jumlah'], 10 * $r['berlaku']); echo '<tr><td>Standar ' . e($r['nomor']) . '</td><td>' . (int)$r['berlaku'] . '</td><td>' . (int)$r['dinilai'] . '</td><td>' . bar($p) . ' ' . $p . '%</td></tr>'; }
echo '</table></div></details></div>';

if (punya('lihat_berkas') || punya('nilai')) {
  $w = ['1=1']; $p = [];
  if ($U['cakupan'] === 'bab') { $w[] = 'bab_id=?'; $p[] = (int)$U['bab_id']; }
  elseif ($U['cakupan'] === 'tugas') { $w[] = 'anggota_id=?'; $p[] = (int)$U['id']; }
  $fb = (int)($_GET['bab'] ?? 0); $fs = trim((string)($_GET['std'] ?? ''));
  if ($fb && semua()) { $w[] = 'bab_id=?'; $p[] = $fb; }
  if ($fs !== '') { $w[] = 'snomor=?'; $p[] = $fs; }
  $dasar = "SELECT e.id,e.nomor,e.urut,e.bunyi,e.tdd,s.nomor snomor,s.bab_id,pn.anggota_id,pm.skor,pm.catatan," . EPST . " st FROM ep e JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id LEFT JOIN penilaian pm ON pm.ep_id=e.id";
  $where = implode(' AND ', $w);
  $total = (int)val("SELECT COUNT(*) FROM ($dasar) z WHERE $where", $p);
  $per = 40;
  $data = rows("SELECT * FROM ($dasar) z WHERE $where ORDER BY urut LIMIT ? OFFSET ?", array_merge($p, [$per, $per * (hal() - 1)]));
  echo '<div class="kartu"><h2 class="jb">Penilaian per EP</h2><form method="get" class="baris-form">';
  if (semua()) echo '<select name="bab"><option value="">Semua bab</option>' . opsi(rows("SELECT id, CONCAT('Bab ',nomor) nama FROM bab ORDER BY nomor"), $fb) . '</select>';
  echo '<select name="std"><option value="">Semua standar</option>';
  foreach (rows("SELECT nomor FROM standar ORDER BY id") as $s) echo '<option value="' . e($s['nomor']) . '"' . ($fs === $s['nomor'] ? ' selected' : '') . '>Standar ' . e($s['nomor']) . '</option>';
  echo '</select><button class="tombol sekunder">Filter</button></form>';
  echo '<form method="post">' . csrf_field() . '<div class="gulung"><table><tr><th>EP</th><th>Bunyi EP</th><th>Dokumen</th><th>Skor</th><th>Catatan</th></tr>';
  foreach ($data as $r) {
    $edit = punya('nilai') && bolehEP($r) && !$r['tdd'];
    $sk = $r['skor'] === null ? '' : (string)(int)$r['skor']; $ct = (string)$r['catatan'];
    echo '<tr><td><a href="epdetail.php?id=' . (int)$r['id'] . '"><b>' . e($r['nomor']) . '</b></a></td><td>' . e(potong($r['bunyi'], 90)) . '</td><td>' . badge_ep($r['st'], $r['tdd']) . '</td><td>';
    if ($r['tdd']) echo '-';
    elseif ($edit) { echo '<input type="hidden" name="lama[' . (int)$r['id'] . ']" value="' . e($sk . '|' . $ct) . '"><select name="skor[' . (int)$r['id'] . ']">'; foreach (['' => '—', '10' => '10', '5' => '5', '0' => '0'] as $k => $v) echo '<option value="' . $k . '"' . ((string)$k === $sk ? ' selected' : '') . '>' . $v . '</option>'; echo '</select>'; }
    else echo e($sk === '' ? '—' : $sk);
    if ($sk === '10' && (int)$r['st'] < 4 && !$r['tdd']) echo ' <span class="badge b-kuning" title="Skor 10 tetapi belum ada dokumen Valid">Cek bukti</span>';
    echo '</td><td>' . ($edit ? '<input type="text" name="cat[' . (int)$r['id'] . ']" maxlength="500" value="' . e($ct) . '">' : e($ct)) . '</td></tr>';
  }
  if (!$data) echo '<tr><td colspan="5" class="hint">Tidak ada EP.</td></tr>';
  echo '</table></div>';
  if (punya('nilai') && $data) echo '<div class="aksi-bawah"><button class="tombol">Simpan penilaian</button></div>';
  echo '</form>' . pager($total, $per) . '</div>';
}
kaki();
