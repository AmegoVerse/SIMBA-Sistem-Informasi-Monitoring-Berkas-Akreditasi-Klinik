<?php
require __DIR__ . '/inc/mulai.php';
wajib_login(); wajib('bagikan_ep');
$babId = semua() ? (int)($_GET['bab'] ?? $_POST['bab'] ?? 0) : (int)$U['bab_id'];
$bab = $babId ? row("SELECT * FROM bab WHERE id=?", [$babId]) : null;
if (!$bab) {
  kepala('Pembagian EP', 'bagikan');
  echo '<div class="kartu"><h2 class="jb">Pembagian EP</h2>';
  if (semua()) { echo '<p>Pilih bab:</p>'; foreach (rows("SELECT id,nomor,nama FROM bab ORDER BY nomor") as $b) echo '<p><a class="tombol sekunder" href="?bab=' . (int)$b['id'] . '">Bab ' . (int)$b['nomor'] . ' – ' . e($b['nama']) . '</a></p>'; }
  else echo '<p class="hint">Akun Anda belum terhubung ke bab. Hubungi Super Admin.</p>';
  echo '</div>'; kaki(); exit;
}
$self = 'bagikan.php?bab=' . $babId;
$anggota = $bab['pj_id'] ? rows("SELECT u.id,u.nama FROM pengguna u JOIN peran r ON r.id=u.peran_id WHERE u.aktif=1 AND r.cakupan='tugas' AND u.atasan_id=? ORDER BY u.nama", [(int)$bab['pj_id']]) : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  post_wajib();
  try {
    $ids = array_values(array_unique(array_map('intval', (array)($_POST['ep'] ?? []))));
    if (!$ids) throw new Pesan('Pilih minimal satu EP.');
    $eps = rows("SELECT e.id,e.nomor,pn.anggota_id FROM ep e JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id WHERE s.bab_id=? AND e.id IN (" . in_ph(count($ids)) . ") ORDER BY e.urut", array_merge([$babId], $ids));
    if (count($eps) !== count($ids)) throw new Pesan('Ada EP yang bukan bagian dari bab ini.');
    $nomor = implode(', ', array_column($eps, 'nomor'));
    $lg = ['bab_id' => $babId];
    switch ($_POST['aksi'] ?? '') {
      case 'tugaskan':
        $ag = null; foreach ($anggota as $x) if ((int)$x['id'] === (int)($_POST['anggota_id'] ?? 0)) $ag = $x;
        if (!$ag) throw new Pesan('Pilih Anggota Tim di bawah PJ bab ini.');
        $tenggat = tgl_valid($_POST['tenggat'] ?? '');
        $baru = []; $pindah = [];
        foreach ($eps as $x) {
          if ($x['anggota_id'] && (int)$x['anggota_id'] !== (int)$ag['id']) $pindah[] = $x['nomor']; elseif (!$x['anggota_id']) $baru[] = $x['nomor'];
          ex("INSERT INTO penugasan (ep_id,anggota_id,ditugaskan_oleh,tenggat) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE anggota_id=VALUES(anggota_id), ditugaskan_oleh=VALUES(ditugaskan_oleh), tenggat=VALUES(tenggat), dibuat=NOW()", [(int)$x['id'], (int)$ag['id'], (int)$U['id'], $tenggat]);
        }
        if ($baru) log_tulis('tugaskan', $lg + ['info' => 'EP ' . implode(', ', $baru) . ' → ' . $ag['nama'] . ($tenggat ? ', tenggat ' . tgl($tenggat) : '')]);
        if ($pindah) log_tulis('pindah_tugas', $lg + ['info' => 'EP ' . implode(', ', $pindah) . ' → ' . $ag['nama'] . ($tenggat ? ', tenggat ' . tgl($tenggat) : '')]);
        flash('ok', count($eps) . ' EP ditugaskan kepada ' . $ag['nama'] . '.'); break;
      case 'cabut':
        ex("DELETE FROM penugasan WHERE ep_id IN (" . in_ph(count($ids)) . ")", $ids);
        log_tulis('cabut_tugas', $lg + ['info' => 'EP ' . $nomor . '. Dokumen yang sudah diunggah tidak dihapus.']); flash('ok', 'Penugasan dicabut. Dokumen tidak dihapus.'); break;
      case 'tdd':
        $al = trim((string)($_POST['alasan'] ?? '')); if ($al === '') throw new Pesan('Alasan TDD wajib diisi.');
        ex("UPDATE ep SET tdd=1, tdd_alasan=? WHERE id IN (" . in_ph(count($ids)) . ")", array_merge([mb_substr($al, 0, 255)], $ids));
        log_tulis('tdd', $lg + ['catatan' => $al, 'info' => 'EP ' . $nomor]); flash('ok', 'EP ditandai TDD.'); break;
      case 'tdd_batal':
        ex("UPDATE ep SET tdd=0, tdd_alasan=NULL WHERE id IN (" . in_ph(count($ids)) . ")", $ids);
        log_tulis('tdd_batal', $lg + ['info' => 'EP ' . $nomor]); flash('ok', 'Status TDD dibatalkan.'); break;
      default: throw new Pesan('Aksi tidak dikenal.');
    }
  } catch (Pesan $x) { flash('err', $x->getMessage()); }
  redirect($self . (!empty($_GET['std']) ? '&std=' . urlencode($_GET['std']) : ''));
}
$std = rows("SELECT id,nomor,nama FROM standar WHERE bab_id=? ORDER BY id", [$babId]);
$fs = (int)($_GET['std'] ?? 0);
$w = 's.bab_id=?'; $p = [$babId]; if ($fs) { $w .= ' AND s.id=?'; $p[] = $fs; }
$data = rows("SELECT e.id,e.nomor,e.bunyi,e.tdd,pn.tenggat,u.nama anggota," . EPST . " st FROM ep e JOIN standar s ON s.id=e.standar_id LEFT JOIN penugasan pn ON pn.ep_id=e.id LEFT JOIN pengguna u ON u.id=pn.anggota_id WHERE $w ORDER BY e.urut", $p);
kepala('Pembagian EP', 'bagikan');
echo '<div class="kartu"><h2 class="jb">Pembagian EP · Bab ' . (int)$bab['nomor'] . '</h2>';
if (!$bab['pj_id']) echo '<div class="pesan err">Bab ini belum punya PJ. Super Admin perlu menetapkan PJ lebih dulu.</div>';
elseif (!$anggota) echo '<div class="catatan">Belum ada Anggota Tim aktif di bawah PJ bab ini.</div>';
echo '<form method="get" class="baris-form"><input type="hidden" name="bab" value="' . $babId . '"><select name="std"><option value="">Semua standar</option>';
foreach ($std as $s) echo '<option value="' . (int)$s['id'] . '"' . ($fs === (int)$s['id'] ? ' selected' : '') . '>' . e(nama_std($s['nomor'], (string)$s['nama'])) . '</option>';
echo '</select><button class="tombol sekunder">Filter</button></form>';
echo '<form method="post" action="' . e($self . ($fs ? '&std=' . $fs : '')) . '">' . csrf_field() . '<input type="hidden" name="bab" value="' . $babId . '"><div class="gulung"><table><tr><th><input type="checkbox" data-semua=".cek-ep" title="Pilih semua"></th><th>EP</th><th>Bunyi EP</th><th>Pemegang</th><th>Tenggat</th><th>Status</th></tr>';
foreach ($data as $r) echo '<tr' . (!$r['anggota'] && !$r['tdd'] ? ' style="background:#fffbe6"' : '') . '><td><input type="checkbox" class="cek-ep" name="ep[]" value="' . (int)$r['id'] . '"></td><td><b>' . e($r['nomor']) . '</b></td><td>' . e(potong($r['bunyi'], 90)) . '</td><td>' . e($r['anggota'] ?: 'Belum ada') . '</td><td>' . e(tgl($r['tenggat'])) . '</td><td>' . badge_ep($r['st'], $r['tdd']) . '</td></tr>';
if (!$data) echo '<tr><td colspan="6" class="hint">Belum ada EP di bab ini. Impor EP lewat Administrasi.</td></tr>';
echo '</table></div><div class="kotak"><h3>Aksi untuk EP yang dicentang</h3><div class="baris-form"><select name="anggota_id"><option value="">— pilih Anggota Tim —</option>' . opsi($anggota, '') . '</select><input type="date" name="tenggat" title="Tenggat"><button name="aksi" value="tugaskan" class="tombol">Tugaskan</button><button name="aksi" value="cabut" class="tombol sekunder" data-konfirmasi="Cabut penugasan EP yang dicentang?">Cabut penugasan</button></div>';
echo '<div class="baris-form"><input type="text" name="alasan" placeholder="Alasan TDD (tidak dapat diterapkan)"><button name="aksi" value="tdd" class="tombol sekunder" data-konfirmasi="Tandai EP yang dicentang sebagai TDD?">Tandai TDD</button><button name="aksi" value="tdd_batal" class="tombol sekunder">Batalkan TDD</button></div></div></form></div>';
$beban = rows("SELECT u.nama, COUNT(pn.ep_id) total, SUM(y.st=4) valid, SUM(y.st=1) revisi, SUM(y.st=0) belum FROM pengguna u JOIN peran r ON r.id=u.peran_id AND r.cakupan='tugas' LEFT JOIN penugasan pn ON pn.anggota_id=u.id LEFT JOIN (SELECT e.id," . EPST . " st FROM ep e WHERE e.tdd=0) y ON y.id=pn.ep_id WHERE u.aktif=1 AND u.atasan_id=? GROUP BY u.id,u.nama ORDER BY u.nama", [(int)$bab['pj_id']]);
echo '<div class="kartu"><h2 class="jb">Beban kerja Anggota Tim</h2><div class="gulung"><table><tr><th>Nama</th><th>EP dipegang</th><th>Belum diunggah</th><th>Revisi</th><th>Valid</th></tr>';
foreach ($beban as $b) echo '<tr><td>' . e($b['nama']) . '</td><td>' . (int)$b['total'] . '</td><td>' . (int)$b['belum'] . '</td><td>' . (int)$b['revisi'] . '</td><td>' . (int)$b['valid'] . '</td></tr>';
if (!$beban) echo '<tr><td colspan="5" class="hint">Belum ada Anggota Tim.</td></tr>';
echo '</table></div></div>';
kaki();
