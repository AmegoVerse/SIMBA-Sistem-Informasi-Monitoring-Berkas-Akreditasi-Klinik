<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); if (!admin_()) tolak();
function kunci($s){ return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$s))); }
$lap = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  try {
    $f = $_FILES['csv'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1048576) throw new Pesan('Pilih berkas CSV (maks. 5 MB).');
    $isi = file_get_contents($f['tmp_name']);
    $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
    if (!mb_check_encoding($isi, 'UTF-8')) $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
    $baris = preg_split('/\r\n|\n|\r/', $isi, 2); $sep = substr_count($baris[0], ';') > substr_count($baris[0], ',') ? ';' : ',';
    $fh = fopen('php://temp', 'r+'); fwrite($fh, $isi); rewind($fh);
    $head = fgetcsv($fh, 0, $sep);
    $peta = ['nomor' => ['nomorep', 'noep', 'nomor', 'no', 'epno', 'ep'], 'bunyi' => ['bunyiep', 'bunyi', 'elemenpenilaian', 'uraian', 'bunyielemenpenilaian'], 'bukti' => ['bukti', 'kelengkapanbukti', 'kelengkapan', 'buktiyangdiminta', 'dokumenbukti'], 'skor' => ['skor', 'skor1050', 'nilai'], 'telusur' => ['jenistelusur', 'telusur', 'metode'], 'resmi' => ['nomorresmi'], 'nstd' => ['namastandar', 'judulstandar', 'standar']];
    $kol = [];
    foreach ((array)$head as $i => $h) { $k = kunci($h); foreach ($peta as $n => $alt) if (!isset($kol[$n]) && in_array($k, $alt, true)) $kol[$n] = $i; }
    if (!isset($kol['nomor']) || !isset($kol['bunyi'])) throw new Pesan('Kolom nomor EP dan bunyi EP tidak ditemukan pada baris judul. Judul kolom yang dikenali: nomor_ep / nomor, bunyi_ep / bunyi.');
    $baru = 0; $ubah = 0; $lewat = [];
    while (($r = fgetcsv($fh, 0, $sep)) !== false) {
      if (!array_filter($r, function ($x) { return trim((string)$x) !== ''; })) continue;
      $no = trim((string)($r[$kol['nomor']] ?? '')); $bn = trim((string)($r[$kol['bunyi']] ?? ''));
      if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $no, $m) || $bn === '') { $lewat[] = $no !== '' ? $no : '(kosong)'; continue; }
      $b = (int)$m[1]; $sn = $m[1] . '.' . $m[2];
      if (!val("SELECT id FROM bab WHERE id=?", [$b])) { $lewat[] = $no . ' (bab tidak dikenal)'; continue; }
      $ns = isset($kol['nstd'], $r[$kol['nstd']]) && trim($r[$kol['nstd']]) !== '' ? mb_substr(trim($r[$kol['nstd']]), 0, 255) : null;
      if ($ns !== null) ex("INSERT INTO standar (bab_id,nomor,nama) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nama=VALUES(nama)", [$b, $sn, $ns]);
      else ex("INSERT IGNORE INTO standar (bab_id,nomor,nama) VALUES (?,?,?)", [$b, $sn, 'Standar ' . $sn]);
      $sid = (int)val("SELECT id FROM standar WHERE nomor=?", [$sn]);
      $urut = (int)$m[1] * 1000000 + (int)$m[2] * 1000 + (int)$m[3];
      $g = function ($k) use ($kol, $r) { return isset($kol[$k]) && isset($r[$kol[$k]]) && trim($r[$kol[$k]]) !== '' ? trim($r[$kol[$k]]) : null; };
      $ada = val("SELECT id FROM ep WHERE nomor=?", [$no]);
      ex("INSERT INTO ep (standar_id,nomor,urut,bunyi,bukti,skor,jenis_telusur,nomor_resmi) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE standar_id=VALUES(standar_id), urut=VALUES(urut), bunyi=VALUES(bunyi), bukti=VALUES(bukti), skor=VALUES(skor), jenis_telusur=VALUES(jenis_telusur), nomor_resmi=COALESCE(VALUES(nomor_resmi),nomor_resmi)", [$sid, $no, $urut, $bn, $g('bukti'), $g('skor'), $g('telusur'), $g('resmi')]);
      if ($ada) $ubah++; else $baru++;
    }
    fclose($fh);
    log_tulis('impor_ep', ['admin' => 1, 'info' => $baru . ' EP baru, ' . $ubah . ' diperbarui, ' . count($lewat) . ' dilewati']);
    $lap = [$baru, $ubah, $lewat];
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
}
kepala('Impor EP');
if ($lap) { echo '<div class="pesan ok">Selesai: ' . $lap[0] . ' EP baru, ' . $lap[1] . ' diperbarui, ' . count($lap[2]) . ' baris dilewati.</div>'; if ($lap[2]) echo '<div class="catatan">Dilewati: ' . e(implode(', ', array_slice($lap[2], 0, 30))) . '</div>'; }
echo '<div class="kartu"><h2 class="jb">Impor EP dari CSV</h2><p>Unggah berkas CSV EP (contoh format: <code>data/contoh_ep_klinik.csv</code>). Baris judul dikenali otomatis (nomor_ep atau nomor, bunyi_ep atau bunyi; opsional: standar (nama standar), bukti, skor, jenis_telusur, nomor_resmi). Nomor EP harus berpola <code>1.1.1</code>. Bab dan standar dibuat otomatis dari nomor. Mengimpor ulang memperbarui EP dengan nomor yang sama tanpa menghapus penugasan atau dokumen.</p><form method="post" enctype="multipart/form-data">' . csrf_field() . '<input type="file" name="csv" accept=".csv" required><p><button class="tombol">Impor</button></p></form><p class="hint">EP saat ini: ' . (int)val("SELECT COUNT(*) FROM ep") . '</p></div>';
kaki();
