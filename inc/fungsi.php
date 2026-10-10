<?php
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('EPST', "(SELECT COALESCE(MIN(CASE d.status WHEN 'revisi' THEN 1 WHEN 'menunggu' THEN 2 WHEN 'terverifikasi' THEN 3 WHEN 'valid' THEN 4 END),0) FROM dokumen_ep de JOIN dokumen d ON d.id=de.dokumen_id AND d.diarsipkan=0 WHERE de.ep_id=e.id AND de.aktif=1)");
const DAFTAR_HAK = [
  'lihat_berkas' => 'Lihat berkas',
  'unggah' => 'Unggah dokumen',
  'edit' => 'Edit, ganti versi, arsip, hapus dokumen',
  'tautkan' => 'Tautkan dokumen yang sudah ada',
  'bagikan_ep' => 'Bagikan EP dan tandai TDD',
  'verifikasi' => 'Verifikasi',
  'kembalikan' => 'Kembalikan untuk revisi',
  'validasi' => 'Validasi dan ubah nomor dokumen',
  'batal_verifikasi' => 'Batalkan verifikasi',
  'batal_validasi' => 'Batalkan validasi',
  'lihat_riwayat' => 'Lihat riwayat dan catatan revisi',
  'lihat_log' => 'Lihat Log Aktivitas',
  'lihat_daftar_dokumen' => 'Lihat Daftar Dokumen',
  'nilai' => 'Isi penilaian mandiri',
  'lihat_temuan' => 'Lihat temuan',
  'catat_temuan' => 'Catat, ubah, dan tutup temuan',
  'mode_surveior' => 'Mode Surveior (baca-saja, hanya dokumen Valid)',
];
const SUMBER_TEMUAN = ['audit_internal' => 'Audit internal', 'simulasi' => 'Simulasi survei', 'surveior' => 'Surveior', 'lainnya' => 'Lainnya'];
const STATUS_TEMUAN = ['terbuka' => ['Terbuka', 'b-merah'], 'dikerjakan' => ['Dikerjakan', 'b-kuning'], 'selesai' => ['Selesai, menunggu penutupan', 'b-biru'], 'ditutup' => ['Ditutup', 'b-hijau']];
const LABEL_AKSI = [
  'unggah' => 'Unggah dokumen', 'versi_baru' => 'Versi baru', 'edit_dokumen' => 'Edit dokumen',
  'tautkan' => 'Tautkan dokumen', 'lepas_tautan' => 'Lepas tautan', 'verifikasi' => 'Verifikasi',
  'revisi' => 'Kembalikan revisi', 'validasi' => 'Validasi', 'batal_verifikasi' => 'Batal verifikasi',
  'batal_validasi' => 'Batal validasi', 'ubah_nomor' => 'Ubah nomor dokumen', 'arsip' => 'Arsipkan',
  'pulihkan' => 'Pulihkan', 'hapus_dokumen' => 'Hapus permanen', 'pindah_pemilik' => 'Pindah kepemilikan',
  'tugaskan' => 'Tugaskan EP', 'pindah_tugas' => 'Pindahkan penugasan', 'cabut_tugas' => 'Cabut penugasan',
  'tdd' => 'Tandai TDD', 'tdd_batal' => 'Batalkan TDD', 'pengguna_baru' => 'Pengguna baru',
  'pengguna_ubah' => 'Ubah pengguna', 'peran_ubah' => 'Ubah peran', 'jenis_ubah' => 'Ubah jenis dokumen',
  'impor_ep' => 'Impor EP', 'backup' => 'Unduh backup',
  'lapor' => 'Laporkan masalah', 'laporan_selesai' => 'Laporan selesai', 'nilai' => 'Penilaian mandiri',
  'temuan_baru' => 'Temuan baru', 'temuan_ubah' => 'Ubah temuan', 'temuan_kerja' => 'Tindak lanjut temuan', 'temuan_tutup' => 'Tutup temuan', 'temuan_buka' => 'Buka kembali temuan', 'pengaturan_ubah' => 'Ubah pengaturan',
];

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect($u){ header('Location: ' . $u); exit; }
function flash($t, $m){ $_SESSION['flash'][] = [$t, $m]; }
function tampil_flash(){
  if (empty($_SESSION['flash'])) return;
  foreach ($_SESSION['flash'] as $f) echo '<div class="pesan ' . ($f[0] === 'err' ? 'err' : 'ok') . '">' . e($f[1]) . '</div>';
  unset($_SESSION['flash']);
}
function csrf(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(){ return '<input type="hidden" name="_t" value="' . csrf() . '">'; }
function post_wajib(){
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf(), (string)($_POST['_t'] ?? ''))) tolak('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
}
function tgl($s, $jam = false){
  if (!$s) return '-';
  $t = strtotime($s);
  return $t ? date($jam ? 'd/m/Y H:i' : 'd/m/Y', $t) : '-';
}
function tgl_valid($s){
  $s = trim((string)$s);
  if ($s === '') return null;
  $d = DateTime::createFromFormat('Y-m-d', $s);
  if (!$d || $d->format('Y-m-d') !== $s) throw new Pesan('Format tanggal tidak valid.');
  return $s;
}
function pct($a, $b){ return $b > 0 ? (int)round(100 * $a / $b) : 0; }
function bar($p){ return '<div class="bar"><span style="width:' . (int)$p . '%"></span></div>'; }
function potong($s, $n){ $s = (string)$s; return mb_strlen($s) > $n ? mb_substr($s, 0, $n) . '…' : $s; }
function opsi($rows, $sel, $kosong = null){
  $o = $kosong !== null ? '<option value="">' . e($kosong) . '</option>' : '';
  foreach ($rows as $r) $o .= '<option value="' . e($r['id']) . '"' . ((string)$r['id'] === (string)$sel ? ' selected' : '') . '>' . e($r['nama']) . '</option>';
  return $o;
}
function badge_dok($s){
  $m = ['menunggu' => ['Menunggu verifikasi', 'b-kuning'], 'terverifikasi' => ['Terverifikasi PJ', 'b-biru'], 'valid' => ['Valid', 'b-hijau'], 'revisi' => ['Revisi', 'b-merah']];
  $x = $m[$s] ?? [$s, 'b-abu'];
  return '<span class="badge ' . $x[1] . '">' . e($x[0]) . '</span>';
}
function badge_ep($st, $tdd){
  if ($tdd) return '<span class="badge b-abu">TDD</span>';
  $m = [0 => ['Belum diunggah', 'b-abu'], 1 => ['Revisi', 'b-merah'], 2 => ['Menunggu verifikasi', 'b-kuning'], 3 => ['Terverifikasi PJ', 'b-biru'], 4 => ['Valid', 'b-hijau']];
  $x = $m[(int)$st];
  return '<span class="badge ' . $x[1] . '">' . e($x[0]) . '</span>';
}
function pager($total, $per){
  $h = hal(); $n = max(1, (int)ceil($total / $per));
  if ($n < 2) return '';
  $q = $_GET; $o = '<div class="pager">';
  for ($i = 1; $i <= $n; $i++) { $q['h'] = $i; $o .= $i === $h ? '<b>' . $i . '</b>' : '<a href="?' . e(http_build_query($q)) . '">' . $i . '</a>'; }
  return $o . '</div>';
}
function hal(){ return max(1, (int)($_GET['h'] ?? 1)); }

function log_tulis($aksi, $o = []){
  global $U;
  $o = array_merge(['bab_id' => null, 'ep_id' => null, 'dokumen_id' => null, 'judul' => null, 'nomor' => null, 'catatan' => null, 'info' => null, 'admin' => 0], $o);
  val("SELECT GET_LOCK('akr_log', 5)");
  try {
    $prev = (string)val("SELECT hash FROM log ORDER BY id DESC LIMIT 1");
    $waktu = date('Y-m-d H:i:s');
    $uid = isset($U['id']) ? (int)$U['id'] : null;
    $nama = $U['nama'] ?? '(sistem)';
    $h = hash_hmac('sha256', implode('|', [$prev, $waktu, $uid, $nama, $aksi, $o['bab_id'], $o['ep_id'], $o['dokumen_id'], $o['judul'], $o['nomor'], $o['catatan'], $o['info'], $o['admin']]), KUNCI_LOG);
    ins("INSERT INTO log (waktu,pelaku_id,pelaku_nama,aksi,bab_id,ep_id,dokumen_id,dokumen_judul,dokumen_nomor,catatan,info,admin_only,prev_hash,hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
      [$waktu, $uid, $nama, $aksi, $o['bab_id'], $o['ep_id'], $o['dokumen_id'], $o['judul'], $o['nomor'], $o['catatan'], $o['info'], (int)$o['admin'], $prev, $h]);
  } finally {
    val("SELECT RELEASE_LOCK('akr_log')");
  }
}
function log_cek(){
  $prev = ''; $n = 0;
  foreach (rows("SELECT * FROM log ORDER BY id") as $r) {
    $h = hash_hmac('sha256', implode('|', [$r['prev_hash'], $r['waktu'], $r['pelaku_id'], $r['pelaku_nama'], $r['aksi'], $r['bab_id'], $r['ep_id'], $r['dokumen_id'], $r['dokumen_judul'], $r['dokumen_nomor'], $r['catatan'], $r['info'], $r['admin_only']]), KUNCI_LOG);
    if ($r['prev_hash'] !== $prev || !hash_equals($r['hash'], $h)) return [false, (int)$r['id'], $n];
    $prev = $r['hash']; $n++;
  }
  return [true, 0, $n];
}

function simpan_upload($f){
  if (!is_array($f) || !isset($f['error']) || $f['error'] !== UPLOAD_ERR_OK) throw new Pesan('Berkas gagal diunggah. Pilih berkas dan periksa ukuran maksimalnya.');
  if ($f['size'] > MAKS_UPLOAD) throw new Pesan('Ukuran berkas melebihi batas ' . (MAKS_UPLOAD / 1048576) . ' MB.');
  $asli = basename((string)$f['name']);
  if (preg_match('/\.(php\d?|phtml|phar|html?|svg|js|exe|sh|bat|cgi|pl|py|htaccess)(\.|$)/i', $asli)) throw new Pesan('Nama berkas tidak diizinkan.');
  $ext = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
  $ole = ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/octet-stream'];
  $zip = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
  $ok = ['pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
    'doc' => $ole, 'xls' => $ole, 'ppt' => $ole, 'docx' => $zip, 'xlsx' => $zip, 'pptx' => $zip];
  if (!isset($ok[$ext])) throw new Pesan('Jenis berkas tidak diizinkan. Gunakan PDF, Word, Excel, PowerPoint, JPG, atau PNG.');
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  if (!in_array($mime, $ok[$ext], true)) throw new Pesan('Isi berkas tidak sesuai dengan ekstensinya.');
  $nama = bin2hex(random_bytes(16)) . '.bin';
  if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $nama)) throw new Pesan('Berkas gagal disimpan di server.');
  return ['nama' => $nama, 'asli' => mb_substr($asli, 0, 255), 'ukuran' => (int)$f['size'], 'hash' => hash_file('sha256', UPLOAD_DIR . $nama)];
}
function hapus_file($nama){ if ($nama && preg_match('/^[a-f0-9]{32}\.bin$/', $nama)) @unlink(UPLOAD_DIR . $nama); }

function ep_terdampak($did){
  $r = array_column(rows("SELECT e.nomor FROM dokumen_ep de JOIN ep e ON e.id=de.ep_id WHERE de.dokumen_id=? AND de.aktif=1 ORDER BY e.urut", [$did]), 'nomor');
  return $r ? 'EP ' . implode(', ', $r) : 'tidak ada EP tertaut';
}
function lingkup_sql($a = 'd'){
  global $U;
  if ($U['cakupan'] === 'semua') return ['1=1', []];
  if ($U['cakupan'] === 'bab') return $U['bab_id'] ? [$a . '.bab_id=?', [(int)$U['bab_id']]] : ['1=0', []];
  return [$a . '.pemilik_id=?', [(int)$U['id']]];
}
function pengaturan($k, $d = ''){
  static $c = null;
  if ($c === null) { $c = []; try { foreach (rows("SELECT kunci,nilai FROM pengaturan") as $r) $c[$r['kunci']] = $r['nilai']; } catch (Throwable $x) {} }
  return isset($c[$k]) && $c[$k] !== '' ? $c[$k] : $d;
}
function badge_temuan($s){ $x = STATUS_TEMUAN[$s] ?? [$s, 'b-abu']; return '<span class="badge ' . $x[1] . '">' . e($x[0]) . '</span>'; }
function trow($url, $teks, $sub = '', $badge = ''){ return ['url' => $url, 'teks' => $teks, 'sub' => $sub, 'badge' => $badge]; }
function tindakan_daftar(){
  global $U;
  $h = [];
  if (surveior()) return $h;
  $me = (int)$U['id']; $mybab = (int)$U['bab_id'];
  list($w, $p) = lingkup_sql('d');
  $dok = function ($st) use ($w, $p) {
    $r = [];
    foreach (rows("SELECT d.id,d.judul,u.nama pemilik,d.status FROM dokumen d JOIN pengguna u ON u.id=d.pemilik_id WHERE d.diarsipkan=0 AND $w AND d.status=? ORDER BY d.id DESC LIMIT 100", array_merge($p, [$st])) as $x) $r[] = trow('dokumen.php?id=' . $x['id'], $x['judul'], $x['pemilik'], badge_dok($x['status']));
    return $r;
  };
  if (punya('unggah')) $h['Dokumen perlu direvisi'] = $dok('revisi');
  if (punya('verifikasi')) $h['Menunggu verifikasi Anda'] = $dok('menunggu');
  if (punya('validasi')) $h['Siap divalidasi'] = $dok('terverifikasi');
  if (punya('verifikasi') || punya('validasi')) {
    $hari = (int)pengaturan('hari_menggantung', 7); $r = [];
    foreach (rows("SELECT d.id,d.judul,u.nama pemilik,d.status FROM dokumen d JOIN pengguna u ON u.id=d.pemilik_id LEFT JOIN dokumen_versi vr ON vr.id=d.versi_aktif_id WHERE d.diarsipkan=0 AND $w AND ((d.status='menunggu' AND vr.dibuat < DATE_SUB(NOW(), INTERVAL ? DAY)) OR (d.status='terverifikasi' AND d.verifikasi_pada < DATE_SUB(NOW(), INTERVAL ? DAY))) ORDER BY d.id LIMIT 100", array_merge($p, [$hari, $hari])) as $x) $r[] = trow('dokumen.php?id=' . $x['id'], $x['judul'], $x['pemilik'], badge_dok($x['status']));
    $h['Menggantung lebih dari ' . $hari . ' hari'] = $r;
  }
  if (punya('bagikan_ep')) {
    $we = semua() ? '1=1' : 's.bab_id=' . $mybab; $r = [];
    foreach (rows("SELECT e.id,e.nomor,s.bab_id,LEFT(e.bunyi,100) judul FROM ep e JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id WHERE e.tdd=0 AND pn.ep_id IS NULL AND $we ORDER BY e.urut LIMIT 100") as $x) $r[] = trow('bagikan.php?bab=' . $x['bab_id'], 'EP ' . $x['nomor'], $x['judul']);
    $h['EP belum punya pemegang'] = $r;
  }
  $wt = $U['cakupan'] === 'semua' ? (punya('bagikan_ep') ? '1=1' : '1=0') : ($U['cakupan'] === 'bab' ? 's.bab_id=' . $mybab : 'pn.anggota_id=' . $me); $r = [];
  foreach (rows("SELECT e.id,e.nomor,pn.tenggat,u.nama FROM penugasan pn JOIN ep e ON e.id=pn.ep_id JOIN standar s ON s.id=e.standar_id JOIN pengguna u ON u.id=pn.anggota_id WHERE e.tdd=0 AND pn.tenggat < CURDATE() AND " . EPST . " < 4 AND $wt ORDER BY pn.tenggat LIMIT 100") as $x) $r[] = trow('epdetail.php?id=' . $x['id'], 'EP ' . $x['nomor'], $x['nama'], '<span class="badge b-merah">Tenggat ' . e(tgl($x['tenggat'])) . '</span>');
  $h['Tenggat penugasan terlewat'] = $r;
  if (punya('lihat_berkas')) {
    $wl = admin_() ? '1=1' : '(d.pemilik_id=' . $me . ($U['cakupan'] === 'bab' && $mybab ? ' OR d.bab_id=' . $mybab : '') . ')'; $r = [];
    foreach (rows("SELECT d.id,d.judul,u.nama pelapor,LEFT(l.pesan,90) pesan FROM laporan l JOIN dokumen d ON d.id=l.dokumen_id JOIN pengguna u ON u.id=l.pelapor_id WHERE l.status='terbuka' AND $wl ORDER BY l.id DESC LIMIT 100") as $x) $r[] = trow('dokumen.php?id=' . $x['id'], $x['judul'], $x['pelapor'] . ': ' . $x['pesan']);
    $h['Laporan masalah terbuka'] = $r;
    $wk = $U['cakupan'] === 'bab' && $mybab ? '(d.pemilik_id=' . $me . ' OR d.bab_id=' . $mybab . ')' : 'd.pemilik_id=' . $me; $r = [];
    foreach (rows("SELECT d.id,d.judul,MAX(k.dibuat) t FROM komentar k JOIN dokumen d ON d.id=k.dokumen_id WHERE k.dibuat > DATE_SUB(NOW(), INTERVAL 3 DAY) AND k.pengirim_id<>? AND d.diarsipkan=0 AND $wk GROUP BY d.id,d.judul ORDER BY t DESC LIMIT 50", [$me]) as $x) $r[] = trow('dokumen.php?id=' . $x['id'], $x['judul'], 'Komentar ' . tgl($x['t'], true));
    $h['Komentar baru (3 hari terakhir)'] = $r;
  }
  if (punya('unggah') || punya('edit')) {
    $r = [];
    foreach (rows("SELECT d.id,d.judul,d.berlaku_sampai FROM dokumen d WHERE d.diarsipkan=0 AND d.berlaku_sampai IS NOT NULL AND d.berlaku_sampai <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND $w ORDER BY d.berlaku_sampai LIMIT 100", $p) as $x) $r[] = trow('dokumen.php?id=' . $x['id'], $x['judul'], '', '<span class="badge ' . ($x['berlaku_sampai'] < date('Y-m-d') ? 'b-merah' : 'b-kuning') . '">Berlaku s.d. ' . e(tgl($x['berlaku_sampai'])) . '</span>');
    $h['Masa berlaku dokumen (30 hari)'] = $r;
  }
  if (punya('lihat_temuan')) {
    $r = [];
    foreach (rows("SELECT t.id,t.uraian,t.tenggat,t.status FROM temuan t WHERE t.pic_id=? AND t.status IN ('terbuka','dikerjakan') ORDER BY t.tenggat IS NULL, t.tenggat LIMIT 100", [$me]) as $x) $r[] = trow('temuan_detail.php?id=' . $x['id'], potong($x['uraian'], 100), $x['tenggat'] ? 'Tenggat ' . tgl($x['tenggat']) : '', badge_temuan($x['status']));
    $h['Temuan untuk Anda'] = $r;
    if (punya('catat_temuan')) {
      $wt2 = semua() ? '1=1' : 't.bab_id=' . $mybab; $r = [];
      foreach (rows("SELECT t.id,t.uraian FROM temuan t WHERE t.status='selesai' AND $wt2 ORDER BY t.id LIMIT 100") as $x) $r[] = trow('temuan_detail.php?id=' . $x['id'], potong($x['uraian'], 100), 'Menunggu penutupan');
      $h['Temuan menunggu penutupan'] = $r;
    }
  }
  return $h;
}
function tindakan_total(){
  if (!empty($_SESSION['tn']) && time() - $_SESSION['tn'][0] < 60) return $_SESSION['tn'][1];
  $n = 0; foreach (tindakan_daftar() as $l) $n += count($l);
  $_SESSION['tn'] = [time(), $n];
  return $n;
}

// ===== Tampilan: identitas instansi, logo, latar, warna tema =====
function nama_instansi(){ return pengaturan('nama_instansi', ''); }
function alamat_instansi(){ return pengaturan('alamat_instansi', ''); }
function instansi_html(){
  $n = nama_instansi(); $a = alamat_instansi();
  return ($n !== '' ? '<p>' . e($n) . '</p>' : '') . ($a !== '' ? '<p class="alamat">' . e($a) . '</p>' : '');
}
function hex_ok($h){ return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', (string)$h); }
function warna_campur($hex, $ke, $f){
  $o = '#';
  foreach ([1, 3, 5] as $i) { $c = hexdec(substr($hex, $i, 2)); $o .= sprintf('%02x', max(0, min(255, (int)round($c + ($ke - $c) * $f)))); }
  return $o;
}
function path_media($kunci, $def){
  $v = pengaturan($kunci, '');
  return preg_match('/^media\/[a-f0-9]{20}\.(png|jpg|webp)$/', $v) && is_file(dirname(__DIR__) . '/' . $v) ? $v : $def;
}
function tema_css(){
  $u = pengaturan('warna_utama', '#06539f'); $w = pengaturan('warna_sekunder', '#0a6f4a');
  if (!hex_ok($u)) $u = '#06539f';
  if (!hex_ok($w)) $w = '#0a6f4a';
  $css = ':root{--biru:' . $u . ';--biru-tua:' . warna_campur($u, 0, .22) . ';--h1:' . warna_campur($u, 0, .17) . ';--h2:' . warna_campur($u, 255, .06) . ';--h3:' . $w . '}';
  $bg = path_media('background', '');
  if ($bg !== '') $css .= "body{background:linear-gradient(rgba(242,248,250,.86),rgba(234,245,243,.9)),url('" . $bg . "') center/cover fixed no-repeat}";
  return $css;
}
function simpan_gambar($f, $maks){
  if (!is_array($f) || !isset($f['error']) || $f['error'] !== UPLOAD_ERR_OK) throw new Pesan('Gambar gagal diunggah.');
  if ($f['size'] > $maks) throw new Pesan('Ukuran gambar melebihi ' . round($maks / 1048576, 1) . ' MB.');
  $map = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  if (!isset($map[$mime]) || !@getimagesize($f['tmp_name'])) throw new Pesan('Gunakan gambar PNG, JPG, atau WEBP.');
  $nama = 'media/' . bin2hex(random_bytes(10)) . '.' . $map[$mime];
  if (!move_uploaded_file($f['tmp_name'], dirname(__DIR__) . '/' . $nama)) throw new Pesan('Gambar gagal disimpan. Pastikan folder media dapat ditulis.');
  return $nama;
}
function hapus_media($rel){ if (preg_match('/^media\/[a-f0-9]{20}\.(png|jpg|webp)$/', (string)$rel)) @unlink(dirname(__DIR__) . '/' . $rel); }

function nama_std($nomor, $nama = ''){ return 'Standar ' . $nomor . ($nama !== '' && strpos($nama, 'Standar ') !== 0 ? ' – ' . $nama : ''); }
