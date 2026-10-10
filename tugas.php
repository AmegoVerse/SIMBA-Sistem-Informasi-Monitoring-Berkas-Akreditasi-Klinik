<?php
require __DIR__ . '/inc/mulai.php';
wajib_login();
$data = rows("SELECT e.id,e.nomor,e.bunyi,e.tdd,pn.tenggat," . EPST . " st FROM penugasan pn JOIN ep e ON e.id=pn.ep_id WHERE pn.anggota_id=? ORDER BY e.urut", [(int)$U['id']]);
kepala('Tugas Saya', 'tugas');
echo '<div class="kartu"><h2 class="jb">Tugas saya</h2><div class="gulung"><table><tr><th>EP</th><th>Bunyi EP</th><th>Tenggat</th><th>Status</th><th></th></tr>';
foreach ($data as $r) {
  $lewat = $r['tenggat'] && !$r['tdd'] && (int)$r['st'] < 4 && $r['tenggat'] < date('Y-m-d');
  echo '<tr><td><a href="epdetail.php?id=' . (int)$r['id'] . '"><b>' . e($r['nomor']) . '</b></a></td><td>' . e(potong($r['bunyi'], 110)) . '</td><td>' . e(tgl($r['tenggat'])) . ($lewat ? ' <span class="badge b-merah">Lewat</span>' : '') . '</td><td>' . badge_ep($r['st'], $r['tdd']) . '</td><td>' . (!$r['tdd'] && punya('unggah') ? '<a class="tombol kecil" href="unggah.php?ep=' . (int)$r['id'] . '">Unggah</a>' : '') . '</td></tr>';
}
if (!$data) echo '<tr><td colspan="5" class="hint">Belum ada EP yang dibagikan kepada Anda.</td></tr>';
echo '</table></div></div>';
kaki();
