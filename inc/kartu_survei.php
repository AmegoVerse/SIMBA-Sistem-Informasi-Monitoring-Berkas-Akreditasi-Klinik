<?php
$ts = pengaturan('tanggal_survei');
$hr = (int)pengaturan('hari_menggantung', 7);
$men = (int)val("SELECT COUNT(*) FROM dokumen d LEFT JOIN dokumen_versi vr ON vr.id=d.versi_aktif_id WHERE d.diarsipkan=0 AND ((d.status='menunggu' AND vr.dibuat < DATE_SUB(NOW(), INTERVAL ? DAY)) OR (d.status='terverifikasi' AND d.verifikasi_pada < DATE_SUB(NOW(), INTERVAL ? DAY)))", [$hr, $hr]);
$lewat = (int)val("SELECT COUNT(*) FROM penugasan pn JOIN ep e ON e.id=pn.ep_id WHERE e.tdd=0 AND pn.tenggat < CURDATE() AND " . EPST . " < 4");
$tem = (int)val("SELECT COUNT(*) FROM temuan WHERE status<>'ditutup'");
$rek = row("SELECT SUM(CASE WHEN e.tdd=0 THEN COALESCE(pm.skor,0) ELSE 0 END) jml, SUM(e.tdd=0) berlaku FROM ep e LEFT JOIN penilaian pm ON pm.ep_id=e.id");
echo '<div class="kartu"><h2 class="jb">Menuju survei</h2><div class="angka-besar">';
if ($ts) { $sisa = (int)floor((strtotime($ts) - strtotime(date('Y-m-d'))) / 86400); echo '<div><b>' . ($sisa >= 0 ? $sisa . ' hari' : 'Lewat ' . abs($sisa) . ' hari') . '</b>menuju survei (' . e(tgl($ts)) . ')</div>'; }
else echo '<div><b>-</b>Tanggal survei belum diatur</div>';
echo '<div><b>' . pct((int)$rek['jml'], 10 * (int)$rek['berlaku']) . '%</b>capaian penilaian mandiri</div><div><b>' . $men . '</b>dokumen menggantung &gt; ' . $hr . ' hari</div><div><b>' . $lewat . '</b>EP lewat tenggat</div><div><b>' . $tem . '</b>temuan belum ditutup</div></div></div>';
