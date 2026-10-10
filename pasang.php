<?php
// Installer SIMBA. Berjalan sekali dan terkunci otomatis setelah selesai.
const VERSI_SIMBA = '1.0.0-beta';
$root = __DIR__;
$kunci = $root . '/inc/terpasang.lock';
$cfg = $root . '/inc/config.php';
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('display_errors', '0');
session_name('SIMBAPASANG');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; script-src 'none'");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function halaman($judul, $isi){
  header('Content-Type: text/html; charset=UTF-8');
  echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . h($judul) . ' – SIMBA</title><link rel="icon" href="aset/logo.png"><link rel="stylesheet" href="aset/tema.css"></head><body><div class="garis-atas"></div><div class="bungkus">';
  echo '<header class="kepala"><img src="aset/logo.png" alt="Logo SIMBA"><div><span class="lencana">SIMBA</span><h1>Sistem Informasi Manajemen Berkas Akreditasi</h1><p>Pemasangan</p></div></header><main>' . $isi . '</main>';
  echo '<footer><div class="garis-kecil"></div>Cincop by <a href="https://github.com/AmegoVerse/" target="_blank" rel="noopener">Ata Lio Mego</a></footer></div></body></html>';
  exit;
}
function sudah_terpasang($cfg, $kunci){
  if (is_file($kunci)) return true;
  if (!is_file($cfg)) return false;
  try {
    require_once $cfg;
    mysqli_report(MYSQLI_REPORT_OFF);
    $m = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($m->connect_errno) return false;
    $r = $m->query("SELECT COUNT(*) FROM pengguna");
    return $r && (int)$r->fetch_row()[0] > 0;
  } catch (Throwable $x) { return false; }
}
if (sudah_terpasang($cfg, $kunci)) {
  halaman('Sudah terpasang', '<div class="kartu"><h2 class="jb">Sudah terpasang</h2><div class="pesan ok">SIMBA sudah terpasang. Demi keamanan, hapus berkas <b>pasang.php</b> dari server.</div><p><a class="tombol" href="login.php">Ke halaman masuk</a></p></div>');
}
if (empty($_SESSION['t'])) $_SESSION['t'] = bin2hex(random_bytes(32));

$syarat = [
  ['PHP 7.4 atau lebih baru (terdeteksi ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '7.4.0', '>='), true],
  ['Ekstensi mysqli dengan mysqlnd', extension_loaded('mysqli') && function_exists('mysqli_stmt_get_result'), true],
  ['Ekstensi mbstring', extension_loaded('mbstring'), true],
  ['Ekstensi fileinfo', class_exists('finfo'), true],
  ['Ekstensi ctype', function_exists('ctype_digit'), true],
  ['Folder uploads/ dapat ditulis', is_writable($root . '/uploads'), true],
  ['Folder media/ dapat ditulis', is_writable($root . '/media'), true],
  ['Folder inc/ dapat ditulis (untuk membuat config.php otomatis)', is_writable($root . '/inc'), false],
  ['Koneksi HTTPS aktif (disarankan)', $https, false],
];
$siap = true; foreach ($syarat as $s) if ($s[2] && !$s[1]) $siap = false;

$v = ['host' => 'localhost', 'db' => '', 'user' => '', 'instansi' => '', 'alamat' => '', 'un' => '', 'nama' => ''];
$galat = []; $fallback = '';
if ($siap && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['t'], (string)($_POST['t'] ?? ''))) { $galat[] = 'Sesi tidak valid. Muat ulang halaman.'; }
  else {
    foreach ($v as $k => $_) $v[$k] = trim((string)($_POST[$k] ?? $v[$k]));
    $pass = (string)($_POST['pass'] ?? ''); $pw = (string)($_POST['pw'] ?? ''); $pw2 = (string)($_POST['pw2'] ?? '');
    if ($v['host'] === '' || $v['db'] === '' || $v['user'] === '') $galat[] = 'Host, nama database, dan pengguna database wajib diisi.';
    if ($v['instansi'] === '' || mb_strlen($v['instansi']) > 120) $galat[] = 'Nama instansi wajib diisi (maksimal 120 karakter).';
    if (mb_strlen($v['alamat']) > 250) $galat[] = 'Alamat maksimal 250 karakter.';
    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $v['un'])) $galat[] = 'Nama pengguna admin 3-40 karakter (huruf, angka, titik, garis).';
    if ($v['nama'] === '') $galat[] = 'Nama lengkap admin wajib diisi.';
    if (strlen($pw) < 8) $galat[] = 'Kata sandi admin minimal 8 karakter.';
    if ($pw !== $pw2) $galat[] = 'Konfirmasi kata sandi tidak sama.';
    if (!$galat) {
      mysqli_report(MYSQLI_REPORT_OFF);
      $m = @new mysqli($v['host'], $v['user'], $pass, $v['db']);
      if ($m->connect_errno) $galat[] = 'Tidak dapat terhubung ke database: ' . $m->connect_error;
      else {
        $m->set_charset('utf8mb4');
        $r = $m->query("SHOW TABLES LIKE 'pengguna'");
        if ($r && $r->num_rows) $galat[] = 'Database tidak kosong (tabel SIMBA sudah ada). Gunakan database kosong, atau kosongkan dulu bila sebelumnya instalasi gagal di tengah jalan.';
      }
    }
    if (!$galat) {
      $isi = "<?php\n// Dibuat otomatis oleh installer SIMBA " . VERSI_SIMBA . " pada " . date('Y-m-d H:i') . "\n"
        . "define('DB_HOST', " . var_export($v['host'], true) . ");\n"
        . "define('DB_USER', " . var_export($v['user'], true) . ");\n"
        . "define('DB_PASS', " . var_export($pass, true) . ");\n"
        . "define('DB_NAME', " . var_export($v['db'], true) . ");\n"
        . "define('KUNCI_LOG', " . var_export(bin2hex(random_bytes(32)), true) . ");\n"
        . "define('PAKSA_HTTPS', " . ($https ? 'true' : 'false') . ");\n"
        . "define('MAKS_UPLOAD', 10 * 1024 * 1024);\n"
        . "define('WAKTU_TIDAK_AKTIF', 3600);\n";
      if (@file_put_contents($cfg, $isi, LOCK_EX) === false) {
        $fallback = $isi;
        $galat[] = 'Berkas inc/config.php tidak dapat dibuat otomatis. Buat berkas itu secara manual dengan isi di bawah ini, lalu kirim ulang formulir ini.';
      } else {
        $sql = file_get_contents($root . '/install.sql');
        $sql = implode("\n", array_filter(explode("\n", (string)$sql), function ($l) { return strpos(ltrim($l), '--') !== 0; }));
        foreach (preg_split('/;\s*[\r\n]+/', $sql . "\n") as $q) {
          $q = trim($q);
          if ($q === '') continue;
          if (!$m->query($q)) { $galat[] = 'Gagal mengimpor tabel: ' . $m->error . ' (pada: ' . mb_substr($q, 0, 60) . '…). Kosongkan database lalu coba lagi.'; @unlink($cfg); break; }
        }
        if (!$galat) {
          $st = $m->prepare("INSERT INTO pengaturan (kunci,nilai) VALUES (?,?)");
          foreach (['nama_instansi' => $v['instansi'], 'alamat_instansi' => $v['alamat'], 'versi_simba' => VERSI_SIMBA] as $k => $nilai) { $st->bind_param('ss', $k, $nilai); $st->execute(); }
          $hash = password_hash($pw, PASSWORD_DEFAULT);
          $st = $m->prepare("INSERT INTO pengguna (username,nama,password_hash,peran_id) VALUES (?,?,?,1)");
          $st->bind_param('sss', $v['un'], $v['nama'], $hash); $st->execute();
          @file_put_contents($kunci, date('c'));
          $_SESSION = [];
          halaman('Selesai', '<div class="kartu"><h2 class="jb">Pemasangan selesai</h2><div class="pesan ok">SIMBA berhasil dipasang untuk ' . h($v['instansi']) . '.</div><p><b>Langkah terakhir:</b> hapus berkas <b>pasang.php</b> dari server. Installer sudah terkunci, tetapi berkas ini tidak diperlukan lagi.</p><p>Setelah masuk, buka menu Administrasi untuk membuat pengguna (PJ per bab, Anggota Tim, Ketua) dan mengimpor daftar EP. Sebaiknya buat satu akun Super Admin cadangan, karena SIMBA tidak punya fitur reset kata sandi.</p><p><a class="tombol" href="login.php">Masuk sekarang</a></p></div>');
        }
      }
    }
  }
}
$o = '<div class="kartu"><h2 class="jb">Pemeriksaan persyaratan</h2><table>';
foreach ($syarat as $s) $o .= '<tr><td>' . h($s[0]) . '</td><td>' . ($s[1] ? '<span class="badge b-hijau">OK</span>' : ($s[2] ? '<span class="badge b-merah">Perlu diperbaiki</span>' : '<span class="badge b-kuning">Peringatan</span>')) . '</td></tr>';
$o .= '</table>' . ($siap ? '' : '<div class="pesan err">Perbaiki persyaratan yang bertanda merah, lalu muat ulang halaman ini.</div>') . '</div>';
if ($siap) {
  $o .= '<form method="post" autocomplete="off"><input type="hidden" name="t" value="' . h($_SESSION['t']) . '">';
  if ($galat) { $o .= '<div class="pesan err">' . implode('<br>', array_map('h', $galat)) . '</div>'; }
  if ($fallback) $o .= '<div class="kartu"><textarea readonly rows="12" style="font-family:monospace">' . h($fallback) . '</textarea></div>';
  $o .= '<div class="kartu"><h2 class="jb">1. Database</h2><p class="hint">Buat database MySQL kosong lebih dulu lewat panel hosting Anda.</p><div class="grid2"><div><label>Host</label><input type="text" name="host" value="' . h($v['host']) . '" required></div><div><label>Nama database</label><input type="text" name="db" value="' . h($v['db']) . '" required></div><div><label>Pengguna database</label><input type="text" name="user" value="' . h($v['user']) . '" required></div><div><label>Kata sandi database</label><input type="password" name="pass"></div></div></div>';
  $o .= '<div class="kartu"><h2 class="jb">2. Instansi</h2><label>Nama instansi</label><input type="text" name="instansi" maxlength="120" value="' . h($v['instansi']) . '" required><label>Alamat</label><input type="text" name="alamat" maxlength="250" value="' . h($v['alamat']) . '"></div>';
  $o .= '<div class="kartu"><h2 class="jb">3. Akun Super Admin</h2><div class="grid2"><div><label>Nama pengguna</label><input type="text" name="un" value="' . h($v['un']) . '" required></div><div><label>Nama lengkap</label><input type="text" name="nama" value="' . h($v['nama']) . '" required></div><div><label>Kata sandi <small>(min. 8 karakter)</small></label><input type="password" name="pw" autocomplete="new-password" required></div><div><label>Ulangi kata sandi</label><input type="password" name="pw2" autocomplete="new-password" required></div></div><div class="aksi-bawah"><button class="tombol">Pasang SIMBA</button></div></div></form>';
}
halaman('Pemasangan', $o);
