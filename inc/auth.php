<?php
function wajib_login(){
  global $U, $HAK;
  if (empty($_SESSION['uid'])) redirect('login.php');
  if (time() - ($_SESSION['aktif_terakhir'] ?? 0) > WAKTU_TIDAK_AKTIF) keluar('Sesi berakhir karena tidak aktif. Silakan masuk lagi.');
  $_SESSION['aktif_terakhir'] = time();
  $U = row("SELECT p.*, r.nama peran_nama, r.cakupan FROM pengguna p JOIN peran r ON r.id=p.peran_id WHERE p.id=?", [(int)$_SESSION['uid']]);
  if (!$U || !$U['aktif']) keluar('Akun tidak aktif.');
  if ($U['berlaku_sampai'] && strtotime($U['berlaku_sampai']) < time()) keluar('Masa berlaku akun ini sudah habis.');
  $HAK = array_column(rows("SELECT hak FROM hak_akses WHERE peran_id=?", [(int)$U['peran_id']]), 'hak');
  if (surveior() && !in_array(basename($_SERVER['SCRIPT_NAME']), ['index.php', 'indeks.php', 'dokumen.php', 'unduh.php', 'logout.php'], true)) redirect('indeks.php');
}
function surveior(){ global $HAK; return !admin_() && in_array('mode_surveior', (array)$HAK, true); }
function keluar($pesan = ''){
  session_regenerate_id(true);
  $_SESSION = [];
  if ($pesan !== '') $_SESSION['flash_login'] = $pesan;
  redirect('login.php');
}
function admin_(){ global $U; return (int)$U['peran_id'] === 1; }
function punya($h){ global $U, $HAK; return admin_() || in_array($h, $HAK, true); }
function wajib($h){ if (!punya($h)) tolak(); }
function semua(){ global $U; return $U['cakupan'] === 'semua'; }

function bolehEP($ep){
  global $U;
  if (semua()) return true;
  if ($U['cakupan'] === 'bab') return $U['bab_id'] && (int)$U['bab_id'] === (int)$ep['bab_id'];
  return (int)$ep['anggota_id'] === (int)$U['id'];
}
function ep_row($id){
  return row("SELECT e.*, s.bab_id, s.nomor snomor, s.nama snama, pn.anggota_id, pn.tenggat, u.nama anggota_nama, " . EPST . " st FROM ep e JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id LEFT JOIN pengguna u ON u.id=pn.anggota_id WHERE e.id=?", [(int)$id]);
}
function dok_row($id){
  return row("SELECT d.*, j.nama jenis, u.nama pemilik, b.nama bab_nama, vr.nomor versi_no FROM dokumen d LEFT JOIN jenis_dokumen j ON j.id=d.jenis_id JOIN pengguna u ON u.id=d.pemilik_id LEFT JOIN bab b ON b.id=d.bab_id LEFT JOIN dokumen_versi vr ON vr.id=d.versi_aktif_id WHERE d.id=?", [(int)$id]);
}
// pemilik, PJ bab pemilik, atau Super Admin
function kelola_dok($d){
  global $U;
  if (admin_() || (int)$d['pemilik_id'] === (int)$U['id']) return true;
  return $U['cakupan'] === 'bab' && $U['bab_id'] && (int)$U['bab_id'] === (int)$d['bab_id'];
}
// cakupan peran untuk aksi verifikasi, validasi, dan sejenisnya
function lingkup_dok($d){
  global $U;
  if (semua()) return true;
  if ($U['cakupan'] === 'bab') return $U['bab_id'] && (int)$U['bab_id'] === (int)$d['bab_id'];
  return (int)$d['pemilik_id'] === (int)$U['id'];
}
function boleh_lihat_dok($d){
  global $U;
  if (!punya('lihat_berkas')) return false;
  if (surveior()) return $d['status'] === 'valid' && !$d['diarsipkan'];
  if (semua() || kelola_dok($d)) return true;
  if ($d['status'] !== 'valid' || $d['diarsipkan']) return false;
  if ($U['cakupan'] === 'bab') {
    $n = val("SELECT COUNT(*) FROM dokumen_ep de JOIN ep e ON e.id=de.ep_id JOIN standar s ON s.id=e.standar_id WHERE de.dokumen_id=? AND de.aktif=1 AND s.bab_id=?", [(int)$d['id'], (int)$U['bab_id']]);
  } else {
    $n = val("SELECT COUNT(*) FROM dokumen_ep de JOIN penugasan pn ON pn.ep_id=de.ep_id WHERE de.dokumen_id=? AND de.aktif=1 AND pn.anggota_id=?", [(int)$d['id'], (int)$U['id']]);
  }
  return $n > 0;
}
