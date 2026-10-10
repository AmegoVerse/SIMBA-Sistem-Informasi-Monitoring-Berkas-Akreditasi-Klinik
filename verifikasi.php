<?php
require __DIR__ . '/inc/mulai.php';
wajib_login();
$bisaV = punya('verifikasi'); $bisaVal = punya('validasi');
if (!$bisaV && !$bisaVal) tolak();
$tab = (string)($_GET['tab'] ?? ($bisaV ? 'verifikasi' : 'validasi'));
if ($tab === 'verifikasi' && !$bisaV) $tab = 'validasi';
if ($tab !== 'verifikasi' && $tab !== 'validasi') $tab = $bisaV ? 'verifikasi' : 'validasi';
if ($tab === 'validasi' && !$bisaVal) $tab = 'verifikasi';
list($w, $p) = lingkup_sql('d');
$hari = max(1, (int)pengaturan('hari_menggantung', 7));
$antre = function ($status) use ($w, $p) {
  return rows("SELECT d.*, j.nama jenis, u.nama pemilik, b.nomor bab_no, vr.id versi_id, vr.nomor versi_no, (CASE d.status WHEN 'menunggu' THEN vr.dibuat ELSE d.verifikasi_pada END) sejak, (SELECT COUNT(*) FROM dokumen_ep de WHERE de.dokumen_id=d.id AND de.aktif=1) jml FROM dokumen d LEFT JOIN jenis_dokumen j ON j.id=d.jenis_id JOIN pengguna u ON u.id=d.pemilik_id LEFT JOIN bab b ON b.id=d.bab_id LEFT JOIN dokumen_versi vr ON vr.id=d.versi_aktif_id WHERE d.diarsipkan=0 AND $w AND d.status=? ORDER BY sejak, d.id LIMIT 200", array_merge($p, [$status]));
};
$nV = $bisaV ? count($antre('menunggu')) : 0; $nVal = $bisaVal ? count($antre('terverifikasi')) : 0;
$data = $tab === 'verifikasi' ? $antre('menunggu') : $antre('terverifikasi');
kepala('Verifikasi dan Validasi', 'verifikasi');
echo '<div class="kartu"><h2 class="jb">' . ($bisaV && $bisaVal ? 'Verifikasi dan Validasi' : ($bisaV ? 'Verifikasi' : 'Validasi')) . '</h2>';
if ($bisaV && $bisaVal) {
  echo '<div class="tab"><a href="?tab=verifikasi"' . ($tab === 'verifikasi' ? ' class="aktif"' : '') . '>Verifikasi (' . $nV . ')</a><a href="?tab=validasi"' . ($tab === 'validasi' ? ' class="aktif"' : '') . '>Validasi (' . $nVal . ')</a></div>';
}
echo '<p class="hint">' . ($tab === 'verifikasi' ? 'Dokumen berstatus Menunggu verifikasi.' : 'Dokumen berstatus Terverifikasi PJ dan siap divalidasi.') . ' Buka dokumen untuk memeriksa berkasnya; persetujuan selalu terikat pada versi yang sedang Anda lihat.</p>';
echo '<div class="gulung"><table><tr><th>Dokumen</th><th>Jenis</th><th>Pemilik</th><th>Bab</th><th>Ver.</th><th>EP</th><th>Menunggu</th><th></th></tr>';
foreach ($data as $r) {
  $lama = $r['sejak'] ? (int)floor((time() - strtotime($r['sejak'])) / 86400) : 0;
  $bisaBuka = boleh_lihat_dok($r);
  echo '<tr><td>' . ($bisaBuka ? '<a href="dokumen.php?id=' . (int)$r['id'] . '">' . e($r['judul']) . '</a>' : e($r['judul'])) . ($r['nomor_dokumen'] ? '<br><small>' . e($r['nomor_dokumen']) . '</small>' : '') . '</td><td>' . e($r['jenis']) . '</td><td>' . e($r['pemilik']) . '</td><td>' . e($r['bab_no'] ?: '-') . '</td><td>' . (int)$r['versi_no'] . '</td><td>' . (int)$r['jml'] . '</td><td>' . $lama . ' hari' . ($lama >= $hari ? ' <span class="badge b-merah">menggantung</span>' : '') . '</td><td>';
  if ($bisaBuka) echo '<a class="tombol kecil" href="dokumen.php?id=' . (int)$r['id'] . '">Periksa</a> ' . ($r['versi_id'] ? '<a class="tombol kecil sekunder" href="unduh.php?v=' . (int)$r['versi_id'] . '">Unduh</a>' : '');
  echo '</td></tr>';
}
if (!$data) echo '<tr><td colspan="8" class="hint">Tidak ada dokumen dalam antrean.</td></tr>';
echo '</table></div></div>';
kaki();
