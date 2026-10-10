<?php
if (!is_file(__DIR__ . '/config.php')) { header('Location: pasang.php'); exit; }
require __DIR__ . '/config.php';
date_default_timezone_set('Asia/Makassar');
ini_set('display_errors', '0');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$lokal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
if (PAKSA_HTTPS && !$https && !$lokal) { header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301); exit; }
ini_set('session.use_strict_mode', '1');
session_name('AKRSID');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; script-src 'self'; frame-ancestors 'self'");
require __DIR__ . '/db.php';
require __DIR__ . '/fungsi.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/tampil.php';
set_exception_handler(function ($x) {
  error_log((string)$x);
  http_response_code(500);
  echo 'Terjadi kesalahan pada sistem. Hubungi admin.';
});
