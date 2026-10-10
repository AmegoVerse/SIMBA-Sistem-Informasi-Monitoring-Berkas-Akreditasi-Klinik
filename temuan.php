<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('lihat_temuan'); if (surveior()) tolak();
$mybab = (int)$U['bab_id'];
function pic_bab($bab){ return rows("SELECT p.id,p.nama FROM pengguna p JOIN peran r ON r.id=p.peran_id WHERE p.aktif=1 AND p.bab_id=? AND r.cakupan IN ('tugas','bab') ORDER BY p.nama", [(int)$bab]); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib(); wajib('catat_temuan');
  try {
    $bab = semua() ? (int)($_POST['bab_id'] ?? 0) : $mybab;
    if (!val("SELECT id FROM bab WHERE id=?", [$bab])) throw new Pesan('Pilih bab.');
    $sumber = (string)($_POST['sumber'] ?? ''); if (!array_key_exists($sumber, SUMBER_TEMUAN)) throw new Pesan('Pilih sumber temuan.');
    $ur = trim((string)($_POST['uraian'] ?? '')); if ($ur === '' || mb_strlen($ur) > 3000) throw new Pesan('Uraian temuan wajib diisi.');
    $epn = trim((string)($_POST['ep_nomor'] ?? '')); $epid = null;
    if ($epn !== '') { $epid = val("SELECT e.id FROM ep e JOIN standar s ON s.id=e.standar_id WHERE e.nomor=? AND s.bab_id=?", [$epn, $bab]); if (!$epid) throw new Pesan('Nomor EP tidak ditemukan pada bab ini.'); $epid = (int)$epid; }
    $pic = (int)($_POST['pic_id'] ?? 0); if ($pic && !in_array($pic, array_map('intval', array_column(pic_bab($bab), 'id')), true)) throw new Pesan('Penanggung jawab harus pengguna aktif pada bab ini.');
    $id = ins("INSERT INTO temuan (bab_id,ep_id,sumber,uraian,pic_id,tenggat,dibuat_oleh) VALUES (?,?,?,?,?,?,?)", [$bab, $epid, $sumber, $ur, $pic ?: null, tgl_valid($_POST['tenggat'] ?? ''), (int)$U['id']]);
    log_tulis('temuan_baru', ['bab_id' => $bab, 'ep_id' => $epid, 'catatan' => $ur, 'info' => 'Temuan #' . $id . ' (' . SUMBER_TEMUAN[$sumber] . ')']);
    flash('ok', 'Temuan dicatat.'); redirect('temuan_detail.php?id=' . $id);
  } catch (Pesan $x) { flash('err', $x->getMessage()); redirect('temuan.php'); }
}
$w = ['1=1']; $p = [];
if ($U['cakupan'] === 'bab') { $w[] = 't.bab_id=?'; $p[] = $mybab; } elseif ($U['cakupan'] === 'tugas') { $w[] = 't.pic_id=?'; $p[] = (int)$U['id']; }
$fs = (string)($_GET['st'] ?? 'buka'); $fb = (int)($_GET['bab'] ?? 0);
if ($fs === 'buka') $w[] = "t.status<>'ditutup'"; elseif (array_key_exists($fs, STATUS_TEMUAN)) { $w[] = 't.status=?'; $p[] = $fs; }
if ($fb && semua()) { $w[] = 't.bab_id=?'; $p[] = $fb; }
$where = implode(' AND ', $w);
$total = (int)val("SELECT COUNT(*) FROM temuan t WHERE $where", $p); $per = 50;
$data = rows("SELECT t.*, e.nomor ep_nomor, u.nama pic FROM temuan t LEFT JOIN ep e ON e.id=t.ep_id LEFT JOIN pengguna u ON u.id=t.pic_id WHERE $where ORDER BY t.status='ditutup', t.tenggat IS NULL, t.tenggat, t.id DESC LIMIT ? OFFSET ?", array_merge($p, [$per, $per * (hal() - 1)]));
kepala('Temuan', 'temuan');
echo '<div class="kartu"><h2 class="jb">Temuan dan tindak lanjut</h2><form method="get" class="baris-form"><select name="st"><option value="buka"' . ($fs === 'buka' ? ' selected' : '') . '>Belum ditutup</option><option value="all"' . ($fs === 'all' ? ' selected' : '') . '>Semua</option>';
foreach (STATUS_TEMUAN as $k => $v) echo '<option value="' . $k . '"' . ($fs === $k ? ' selected' : '') . '>' . e($v[0]) . '</option>';
echo '</select>';
if (semua()) echo '<select name="bab"><option value="">Semua bab</option>' . opsi(rows("SELECT id, CONCAT('Bab ',nomor) nama FROM bab ORDER BY nomor"), $fb) . '</select>';
echo '<button class="tombol sekunder">Filter</button></form><div class="gulung"><table><tr><th>#</th><th>Bab</th><th>EP</th><th>Sumber</th><th>Uraian</th><th>Penanggung jawab</th><th>Tenggat</th><th>Status</th></tr>';
foreach ($data as $t) {
  $lw = $t['tenggat'] && $t['status'] !== 'ditutup' && $t['tenggat'] < date('Y-m-d');
  echo '<tr><td><a href="temuan_detail.php?id=' . (int)$t['id'] . '">' . (int)$t['id'] . '</a></td><td>' . (int)$t['bab_id'] . '</td><td>' . e($t['ep_nomor'] ?: '-') . '</td><td>' . e(SUMBER_TEMUAN[$t['sumber']]) . '</td><td>' . e(potong($t['uraian'], 90)) . '</td><td>' . e($t['pic'] ?: '-') . '</td><td>' . e(tgl($t['tenggat'])) . ($lw ? ' <span class="badge b-merah">Lewat</span>' : '') . '</td><td>' . badge_temuan($t['status']) . '</td></tr>';
}
if (!$data) echo '<tr><td colspan="8" class="hint">Tidak ada temuan.</td></tr>';
echo '</table></div>' . pager($total, $per) . '</div>';
if (punya('catat_temuan')) {
  $bb = semua() ? 0 : $mybab; $pics = $bb ? pic_bab($bb) : rows("SELECT p.id, CONCAT(p.nama,' (Bab ',p.bab_id,')') nama FROM pengguna p JOIN peran r ON r.id=p.peran_id WHERE p.aktif=1 AND p.bab_id IS NOT NULL AND r.cakupan IN ('tugas','bab') ORDER BY p.bab_id,p.nama");
  echo '<div class="kartu"><h2 class="jb">Catat temuan baru</h2><form method="post">' . csrf_field() . '<div class="grid2">';
  if (semua()) echo '<div><label>Bab</label><select name="bab_id" required>' . opsi(rows("SELECT id, CONCAT('Bab ',nomor,' – ',nama) nama FROM bab ORDER BY nomor"), '', '— pilih —') . '</select></div>';
  echo '<div><label>Sumber</label><select name="sumber">';
  foreach (SUMBER_TEMUAN as $k => $v) echo '<option value="' . $k . '">' . e($v) . '</option>';
  echo '</select></div><div><label>Nomor EP terkait <small>(opsional, mis. 1.1.3)</small></label><input type="text" name="ep_nomor" maxlength="20"></div><div><label>Penanggung jawab <small>(opsional)</small></label><select name="pic_id">' . opsi($pics, '', '—') . '</select></div><div><label>Tenggat <small>(opsional)</small></label><input type="date" name="tenggat"></div></div><label>Uraian temuan</label><textarea name="uraian" required></textarea><p><button class="tombol">Simpan temuan</button></p></form></div>';
}
kaki();
