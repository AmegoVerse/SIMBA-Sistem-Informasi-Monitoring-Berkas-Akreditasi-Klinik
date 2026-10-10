<?php
function tolak($m = 'Anda tidak memiliki akses ke halaman ini.'){
  global $U;
  http_response_code(403);
  if (empty($U)) { echo e($m); exit; }
  kepala('Akses ditolak');
  echo '<div class="kartu"><h2 class="jb">Akses ditolak</h2><p>' . e($m) . '</p></div>';
  kaki();
  exit;
}
function kepala($judul, $aktif = '', $menu = true){
  global $U;
  header('Content-Type: text/html; charset=UTF-8');
  echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($judul) . ' – SIMBA</title><link rel="icon" href="' . e(path_media('logo', 'aset/logo.png')) . '"><link rel="stylesheet" href="aset/tema.css"><style>' . tema_css() . '</style><script src="aset/app.js" defer></script></head><body>';
  echo '<div class="garis-atas"></div><div class="bungkus">';
  echo '<header class="kepala"><img src="' . e(path_media('logo', 'aset/logo.png')) . '" alt="Logo SIMBA"><div><span class="lencana">SIMBA</span><h1>Sistem Informasi Manajemen Berkas Akreditasi</h1>' . instansi_html() . '</div></header>';
  if ($menu && !empty($U)) nav($aktif);
  echo '<main>';
  tampil_flash();
}
function nav($aktif){
  global $U;
  if (surveior()) {
    $m = [['index.php', 'Dashboard', 'dash', true], ['indeks.php', 'Indeks EP', 'indeks', true]];
  } else {
    $n = tindakan_total();
    $m = [['index.php', 'Dashboard', 'dash', true]];
    $m[] = ['tindakan.php', 'Perlu Tindakan' . ($n ? ' <span class="angka">' . $n . '</span>' : ''), 'tindakan', true];
    $vv = punya('verifikasi') && punya('validasi') ? 'Verifikasi &amp; Validasi' : (punya('verifikasi') ? 'Verifikasi' : 'Validasi');
    $m[] = ['verifikasi.php', $vv, 'verifikasi', punya('verifikasi') || punya('validasi')];
    $m[] = ['tugas.php', 'Tugas Saya', 'tugas', $U['cakupan'] === 'tugas'];
    $m[] = ['bagikan.php', 'Pembagian EP', 'bagikan', punya('bagikan_ep')];
    $m[] = ['ep.php', 'Berkas', 'ep', punya('lihat_berkas')];
    $m[] = ['daftar_dokumen.php', 'Daftar Dokumen', 'daftar', punya('lihat_daftar_dokumen')];
    $m[] = ['unggah.php', 'Unggah', 'unggah', punya('unggah')];
    $m[] = ['penilaian.php', 'Penilaian', 'penilaian', true];
    $m[] = ['temuan.php', 'Temuan', 'temuan', punya('lihat_temuan')];
    $m[] = ['indeks.php', 'Indeks EP', 'indeks', punya('lihat_berkas')];
    $m[] = ['log.php', 'Log Aktivitas', 'log', punya('lihat_log')];
    $m[] = ['admin_pengaturan.php', 'Pengaturan', 'pengaturan', admin_()];
  }
  echo '<nav class="menu">';
  foreach ($m as $x) if ($x[3]) echo '<a href="' . $x[0] . '"' . ($aktif === $x[2] ? ' class="aktif"' : '') . '>' . $x[1] . '</a>';
  if (admin_()) {
    echo '<details class="turun"><summary>Administrasi ▾</summary><div><a href="admin_pengguna.php">Pengguna</a><a href="admin_peran.php">Peran dan Hak Akses</a><a href="admin_jenis.php">Jenis Dokumen</a><a href="admin_impor.php">Impor EP</a><a href="admin_backup.php">Backup</a></div></details>';
  }
  echo '<span class="akun">' . e($U['nama']) . ' · ' . e($U['peran_nama']) . '<form method="post" action="logout.php">' . csrf_field() . '<button class="tombol kecil sekunder">Keluar</button></form></span>';
  echo '</nav>';
}
function kaki(){
  echo '</main><footer><div class="garis-kecil"></div>Cincop by <a href="https://github.com/AmegoVerse/" target="_blank" rel="noopener">Ata Lio Mego</a></footer></div></body></html>';
}
