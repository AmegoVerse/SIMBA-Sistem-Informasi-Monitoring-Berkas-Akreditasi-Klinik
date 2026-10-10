<?php
require __DIR__ . '/inc/mulai.php';
wajib_login();
$v = row("SELECT v.*, d.id did FROM dokumen_versi v JOIN dokumen d ON d.id=v.dokumen_id WHERE v.id=?", [(int)($_GET['v'] ?? 0)]);
if (!$v) tolak('Berkas tidak ditemukan.');
$d = dok_row($v['did']);
if (!$d || !boleh_lihat_dok($d)) tolak('Anda tidak boleh mengunduh berkas ini.');
if ($v['file_dihapus'] || !$v['file_nama'] || !preg_match('/^[a-f0-9]{32}\.bin$/', $v['file_nama']) || !is_file(UPLOAD_DIR . $v['file_nama'])) tolak('File versi ini sudah dihapus atau tidak tersedia.');
$tipe = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
$ext = strtolower(pathinfo($v['file_asli'], PATHINFO_EXTENSION));
$nama = preg_replace('/[^A-Za-z0-9._ -]/', '_', $v['file_asli']);
header('Content-Type: ' . ($tipe[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $nama . '"');
header('Content-Length: ' . filesize(UPLOAD_DIR . $v['file_nama']));
header('X-Content-Type-Options: nosniff');
readfile(UPLOAD_DIR . $v['file_nama']);
