<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (surveior()) tolak();
$h = tindakan_daftar();
$_SESSION['tn'] = [time(), array_sum(array_map('count', $h))];
kepala('Perlu Tindakan', 'tindakan');
$ada = false;
foreach ($h as $judul => $list) {
  if (!$list) continue;
  $ada = true;
  echo '<div class="kartu"><h2 class="jb">' . e($judul) . ' (' . count($list) . ')</h2><div class="gulung"><table>';
  foreach ($list as $r) echo '<tr><td><a href="' . e($r['url']) . '">' . e($r['teks']) . '</a></td><td>' . e($r['sub']) . '</td><td>' . $r['badge'] . '</td></tr>';
  echo '</table></div></div>';
}
if (!$ada) echo '<div class="kartu"><h2 class="jb">Perlu Tindakan</h2><p>Tidak ada yang menunggu tindakan Anda.</p></div>';
kaki();
