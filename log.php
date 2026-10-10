<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('lihat_log');
$w = ['1=1']; $p = [];
if (!admin_()) $w[] = 'l.admin_only=0';
if (!semua()) { if ($U['bab_id']) { $w[] = 'l.bab_id=?'; $p[] = (int)$U['bab_id']; } else $w[] = '1=0'; }
$fu = (int)($_GET['u'] ?? 0); $fa = (string)($_GET['aksi'] ?? ''); $d1 = (string)($_GET['dari'] ?? ''); $d2 = (string)($_GET['sampai'] ?? '');
if ($fu) { $w[] = 'l.pelaku_id=?'; $p[] = $fu; }
if ($fa !== '' && array_key_exists($fa, LABEL_AKSI)) { $w[] = 'l.aksi=?'; $p[] = $fa; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d1)) { $w[] = 'l.waktu>=?'; $p[] = $d1 . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d2)) { $w[] = 'l.waktu<=?'; $p[] = $d2 . ' 23:59:59'; }
$where = implode(' AND ', $w);
$total = (int)val("SELECT COUNT(*) FROM log l WHERE $where", $p);
$per = 50;
$data = rows("SELECT l.* FROM log l WHERE $where ORDER BY l.id DESC LIMIT ? OFFSET ?", array_merge($p, [$per, $per * (hal() - 1)]));
kepala('Log Aktivitas', 'log');
if (admin_() && isset($_GET['cek'])) { $c = log_cek(); echo '<div class="pesan ' . ($c[0] ? 'ok' : 'err') . '">' . ($c[0] ? 'Rantai Log utuh (' . $c[2] . ' baris diperiksa).' : 'Rantai Log TIDAK cocok mulai baris #' . $c[1] . '. Ada perubahan langsung pada database.') . '</div>'; }
echo '<div class="kartu"><h2 class="jb">Log Aktivitas</h2><form method="get" class="baris-form"><select name="u"><option value="">Semua pelaku</option>' . opsi(rows("SELECT id,nama FROM pengguna ORDER BY nama"), $fu) . '</select><select name="aksi"><option value="">Semua aksi</option>';
foreach (LABEL_AKSI as $k => $v) { if (!admin_() && in_array($k, ['pengguna_baru', 'pengguna_ubah', 'peran_ubah', 'jenis_ubah', 'impor_ep', 'backup', 'pengaturan_ubah'], true)) continue; echo '<option value="' . $k . '"' . ($fa === $k ? ' selected' : '') . '>' . e($v) . '</option>'; }
echo '</select><input type="date" name="dari" value="' . e($d1) . '"><input type="date" name="sampai" value="' . e($d2) . '"><button class="tombol">Filter</button>' . (admin_() ? '<a class="tombol sekunder" href="?cek=1">Periksa integritas</a>' : '') . '</form>';
$lihatCat = punya('lihat_riwayat');
echo '<div class="gulung"><table><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>EP</th><th>Dokumen</th>' . ($lihatCat ? '<th>Catatan</th>' : '') . '</tr>';
foreach ($data as $r) {
  $dok = e($r['dokumen_judul'] ?: '-');
  if ($r['dokumen_id'] && punya('lihat_berkas')) { $x = dok_row($r['dokumen_id']); if ($x && boleh_lihat_dok($x)) $dok = '<a href="dokumen.php?id=' . (int)$r['dokumen_id'] . '">' . $dok . '</a>'; }
  $ep = $r['ep_id'] ? e(val("SELECT nomor FROM ep WHERE id=?", [(int)$r['ep_id']])) : '';
  if ($lihatCat && !$ep && $r['info'] && preg_match('/EP [\d., ]+/', $r['info'], $m)) $ep = e(potong($m[0], 40));
  echo '<tr><td>' . e(tgl($r['waktu'], true)) . '</td><td>' . e($r['pelaku_nama']) . '</td><td>' . e(LABEL_AKSI[$r['aksi']] ?? $r['aksi']) . '</td><td>' . ($ep ?: '-') . '</td><td>' . $dok . ($r['dokumen_nomor'] ? '<br><small>' . e($r['dokumen_nomor']) . '</small>' : '') . '</td>' . ($lihatCat ? '<td>' . nl2br(e(trim($r['catatan'] . "\n" . $r['info']))) . '</td>' : '') . '</tr>';
}
if (!$data) echo '<tr><td colspan="6" class="hint">Belum ada aktivitas.</td></tr>';
echo '</table></div>' . pager($total, $per) . '</div>';
kaki();
