<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (!admin_()) tolak();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  $tabel = ['siklus', 'peran', 'hak_akses', 'bab', 'pengguna', 'standar', 'ep', 'penugasan', 'jenis_dokumen', 'dokumen', 'dokumen_versi', 'dokumen_ep', 'log'];
  log_tulis('backup', ['admin' => 1, 'info' => 'Ekspor SQL data (tanpa berkas unggahan)']);
  header('Content-Type: application/sql; charset=UTF-8');
  header('Content-Disposition: attachment; filename="backup-akreditasi-' . date('Ymd-His') . '.sql"');
  echo "-- Backup data akreditasi, " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n";
  foreach ($tabel as $t) {
    $res = db()->query("SELECT * FROM `$t`");
    $n = 0;
    while ($r = $res->fetch_assoc()) {
      $v = array_map(function ($x) { return $x === null ? 'NULL' : "'" . db()->real_escape_string($x) . "'"; }, $r);
      echo "INSERT INTO `$t` (`" . implode('`,`', array_keys($r)) . "`) VALUES (" . implode(',', $v) . ");\n"; $n++;
    }
    $res->free();
  }
  echo "SET FOREIGN_KEY_CHECKS=1;\n";
  exit;
}
kepala('Backup');
echo '<div class="kartu"><h2 class="jb">Backup data</h2><p>Mengunduh seluruh data (pengguna, EP, dokumen, riwayat, Log) sebagai berkas SQL. Berkas unggahan tidak ikut; unduh folder <code>uploads</code> lewat File Manager atau FTP bila diperlukan.</p><p class="catatan">Berkas ini memuat data sensitif. Simpan di tempat aman dan jangan dibiarkan di folder web.</p><form method="post">' . csrf_field() . '<button class="tombol">Unduh backup SQL</button></form></div>';
kaki();
