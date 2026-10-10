<?php
require __DIR__ . '/inc/mulai.php';
if (!empty($_SESSION['uid'])) redirect('index.php');
$pesan = '';
function cap_baru(){
  $a = random_int(1, 20); $b = random_int(1, 20);
  $_SESSION['cap'] = ['jawab' => $a + $b, 'waktu' => time()];
  return [$a, $b];
}
function cap_periksa(){
  $c = $_SESSION['cap'] ?? null;
  unset($_SESSION['cap']);
  if (!is_array($c) || !empty($_POST['situs_web'])) return false;
  $t = time() - (int)$c['waktu'];
  if ($t < 2 || $t > 600) return false;
  $j = trim((string)($_POST['verifikasi'] ?? ''));
  return $j !== '' && ctype_digit($j) && (int)$j === (int)$c['jawab'];
}
if (!empty($_SESSION['flash_login'])) { $pesan = $_SESSION['flash_login']; unset($_SESSION['flash_login']); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  if (!cap_periksa()) {
    usleep(300000);
    $pesan = 'Jawaban verifikasi salah, terlalu cepat, atau soal sudah kedaluwarsa. Silakan coba lagi.';
  } else {
  $un = trim((string)($_POST['username'] ?? ''));
  $pw = (string)($_POST['password'] ?? '');
  $u = row("SELECT * FROM pengguna WHERE username=?", [$un]);
  $gagal = 'Nama pengguna atau kata sandi salah, atau akun terkunci sementara.';
  $terkunci = $u && $u['terkunci_sampai'] && strtotime($u['terkunci_sampai']) > time();
  if ($u && !$terkunci && $u['aktif'] && password_verify($pw, $u['password_hash'])) {
    if ($u['berlaku_sampai'] && strtotime($u['berlaku_sampai']) < time()) { kepala('Masuk', '', false); echo '<div class="kartu login"><div class="pesan err">Masa berlaku akun ini sudah habis. Hubungi Super Admin.</div><p><a href="login.php">Kembali</a></p></div>'; kaki(); exit; }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['aktif_terakhir'] = time();
    ex("UPDATE pengguna SET gagal_login=0, terkunci_sampai=NULL WHERE id=?", [(int)$u['id']]);
    redirect('index.php');
  }
  if ($u && !$terkunci) {
    $g = (int)$u['gagal_login'] + 1;
    if ($g >= 5) ex("UPDATE pengguna SET gagal_login=0, terkunci_sampai=? WHERE id=?", [date('Y-m-d H:i:s', time() + 300), (int)$u['id']]);
    else ex("UPDATE pengguna SET gagal_login=? WHERE id=?", [$g, (int)$u['id']]);
  }
  usleep(300000);
  $pesan = $gagal;
  }
}
list($ca, $cb) = cap_baru();
kepala('Masuk', '', false);
echo '<div class="kartu login"><h2 class="jb">Masuk</h2>';
if ($pesan) echo '<div class="pesan err">' . e($pesan) . '</div>';
echo '<form method="post">' . csrf_field() . '<label>Nama pengguna</label><input type="text" name="username" autocomplete="username" required autofocus><label>Kata sandi</label><input type="password" name="password" autocomplete="current-password" required><label>Verifikasi: berapa hasil ' . $ca . ' + ' . $cb . '?</label><input type="text" name="verifikasi" inputmode="numeric" autocomplete="off" required><div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden" aria-hidden="true"><label>Situs web</label><input type="text" name="situs_web" tabindex="-1" autocomplete="off"></div><p><button class="tombol">Masuk</button></p></form></div>';
kaki();
