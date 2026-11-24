<?php
/**
 * Dashboard SIPP API Kantor (dibuat semudah mungkin untuk pengguna awam)
 *   Beranda     : status & tombol kirim
 *   Pengaturan  : isi semua pengaturan lewat formulir (tanpa mengubah file)
 *   Otomatis    : biarkan halaman terbuka, data terkirim sendiri berkala
 * Kompatibel PHP 5.2+.
 */
require dirname(__FILE__) . '/lib/init.php';
session_start();

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'ISO-8859-1'); }
function kembali($q) { header('Location: index.php' . $q); exit; }
/** Pengaturan lengkap saat ini (config.php hasil unduhan + yang tersimpan) sebagai dasar perubahan */
function pengaturan_dasar() {
    $dasar = is_array($GLOBALS['CONFIG_DASHBOARD']) ? $GLOBALS['CONFIG_DASHBOARD'] : array();
    return gabung_array($dasar, $GLOBALS['PENGATURAN_TERSIMPAN']);
}

$namaJenis = array(
    'jadwal_sidang'     => array('Jadwal Sidang', 'Jadwal sidang hari ini sampai beberapa hari ke depan'),
    'statistik_perkara' => array('Statistik Perkara', 'Jumlah perkara masuk, putus, dan sisa tahun ini'),
    'panggilan_ghaib'   => array('Panggilan Ghaib', 'Pengumuman perkara yang Tergugat/Termohon-nya ghaib'),
    'antrian'           => array('Antrian', 'Data antrian (perlu pengaturan lanjutan)'),
);

/* ================= PASSWORD PERTAMA KALI ================= */
if (!ada_password($CONFIG)) {
    $err = '';
    if (isset($_POST['pass_baru'])) {
        if (strlen($_POST['pass_baru']) < 6)                 $err = 'Password minimal 6 karakter.';
        elseif ($_POST['pass_baru'] !== $_POST['pass_ulang']) $err = 'Ulangi password tidak sama.';
        else {
            $p = pengaturan_dasar();
            $p['dashboard_garam'] = buat_kunci(8);
            $p['dashboard_hash']  = hash_password($_POST['pass_baru'], $p['dashboard_garam']);
            if (simpan_pengaturan($p)) {
                $_SESSION['sipp_api_login'] = true;
                $_SESSION['sipp_api_csrf']  = buat_kunci(16);
                kembali('?hal=pengaturan&pertama=1');
            }
            $err = 'Gagal menyimpan pengaturan (' . simpan_keterangan() . '). ' . db_error();
        }
    }
    $mode = 'buat_password';
}

/* ================= LOGIN ================= */
$pesanLogin = '';
if (isset($_GET['keluar'])) { $_SESSION = array(); session_destroy(); kembali(''); }
if (!isset($mode) && isset($_POST['password'])) {
    if (password_cocok($CONFIG, $_POST['password'])) {
        $_SESSION['sipp_api_login'] = true;
        $_SESSION['sipp_api_csrf']  = buat_kunci(16);
        kembali('');
    }
    $pesanLogin = 'Password salah.';
    sleep(1);
}
$login = !empty($_SESSION['sipp_api_login']);
$csrf  = isset($_SESSION['sipp_api_csrf']) ? $_SESSION['sipp_api_csrf'] : '';
$csrfOk = isset($_POST['csrf']) && $csrf != '' && $_POST['csrf'] === $csrf;
$hal = isset($_GET['hal']) ? $_GET['hal'] : 'beranda';
if (!in_array($hal, array('beranda', 'pengaturan', 'otomatis'))) $hal = 'beranda';

/* ================= AJAX (mode otomatis) ================= */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    if (!$login || !$csrfOk) { echo json_aman(array('ok' => false, 'login' => false)); exit; }
    if ($_GET['ajax'] == 'ping') { echo json_aman(array('ok' => true)); exit; }
    $hasil = array();
    if (count(cek_konfigurasi($CONFIG)) == 0) {
        foreach (jalankan_pengiriman($CONFIG, '') as $j => $info) {
            $r = pesan_ramah($info['pesan']);
            $hasil[] = array('nama' => $namaJenis[$j][0], 'ok' => $info['ok'], 'jumlah' => $info['jumlah'],
                             'pesan' => $info['ok'] ? 'Terkirim' : $r['judul'] . ($r['saran'] ? ' ' . $r['saran'] : ''));
        }
    }
    echo json_aman(array('ok' => true, 'login' => true, 'waktu' => date('H:i:s'), 'hasil' => $hasil));
    exit;
}

/* ================= AKSI ================= */
$notif = array();   // array(jenis: sukses|gagal|info, teks)
$pratinjau = null; $hasilKirim = null; $hasilUji = null;

if ($login && $csrfOk && isset($_POST['aksi'])) {
    $aksi  = $_POST['aksi'];
    $jenis = isset($_POST['jenis']) && isset($namaJenis[$_POST['jenis']]) ? $_POST['jenis'] : '';

    if ($aksi == 'unduh_config') {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="config.php"');
        echo buat_config_php(pengaturan_dasar());
        exit;
    }

    if ($aksi == 'kirim') {
        $err = cek_konfigurasi($CONFIG);
        if (count($err) > 0) $notif[] = array('gagal', 'Belum bisa mengirim: ' . implode(' ', $err) . ' Lengkapi dulu di menu Pengaturan.');
        else $hasilKirim = jalankan_pengiriman($CONFIG, $jenis);
    } elseif ($aksi == 'lihat' && $jenis != '') {
        $dbOk = pastikan_db($CONFIG);
        $f = daftar_jenis();
        $pratinjau = array('jenis' => $jenis, 'hasil' => call_user_func($f[$jenis], $CONFIG, $dbOk));
    } elseif ($aksi == 'simpan' || $aksi == 'uji_db' || $aksi == 'uji_web' || $aksi == 'kunci_baru') {
        $p = pengaturan_dasar();
        $P = $_POST;
        $p['sipp_db'] = array(
            'host' => trim($P['db_host']) != '' ? trim($P['db_host']) : 'localhost', 'user' => trim($P['db_user']),
            'pass' => ($P['db_pass'] !== '') ? $P['db_pass'] : $CONFIG['sipp_db']['pass'],
            'name' => trim($P['db_name']),
        );
        $dbBaru = ($p['sipp_db']['user'] != '' && $p['sipp_db']['name'] != '');
        if ($dbBaru && ($p['sipp_db'] != $CONFIG['sipp_db'] || !$GLOBALS['DB_LINK'])) {
            $lamaLink = $GLOBALS['DB_LINK']; $lamaJenis = $GLOBALS['DB_JENIS'];
            if (db_connect($p['sipp_db'])) $GLOBALS['DB_SUMBER'] = 'pengaturan';
            else { $GLOBALS['DB_LINK'] = $lamaLink; $GLOBALS['DB_JENIS'] = $lamaJenis; }
        }
        $kunci = $CONFIG['website']['api_key'];
        $rahasia = $CONFIG['website']['secret'];
        if ($aksi == 'kunci_baru' || strlen($kunci) < 16 || strlen($rahasia) < 32) {
            $kunci = buat_kunci(16); $rahasia = buat_kunci(32);
            if ($aksi == 'kunci_baru') $notif[] = array('info', 'Kunci baru sudah dibuat. Jangan lupa salin ulang teks .env ke website satker.');
        }
        $p['website'] = array(
            'url' => url_penerima($P['web_alamat']), 'api_key' => $kunci, 'secret' => $rahasia,
            'verify_ssl' => !empty($P['web_ssl']), 'timeout' => 30,
        );
        $p['aktif'] = array();
        foreach ($namaJenis as $j => $x) $p['aktif'][$j] = !empty($P['aktif_' . $j]);
        $p['jadwal_hari_ke_depan']    = max(1, min(31, (int)$P['jadwal_hari']));
        $p['jadwal_kirim_nama_pihak'] = !empty($P['jadwal_nama']);
        $p['ghaib_kirim_nama']        = !empty($P['ghaib_nama']);
        $p['ghaib_kirim_alamat']      = !empty($P['ghaib_alamat']);
        $p['antrian_sql']             = trim($P['antrian_sql']);
        $p['cadangan_api_jadwal']     = trim($P['cadangan']);
        if (isset($P['pass_baru']) && $P['pass_baru'] !== '') {
            if (strlen($P['pass_baru']) < 6) $notif[] = array('gagal', 'Password baru minimal 6 karakter, password tidak diubah.');
            else {
                $p['dashboard_garam'] = buat_kunci(8);
                $p['dashboard_hash']  = hash_password($P['pass_baru'], $p['dashboard_garam']);
                $p['dashboard_password'] = '';
            }
        }
        if (simpan_pengaturan($p)) {
            $CONFIG = gabung_array($CONFIG, $p);
            $PENGATURAN_TERSIMPAN = $p;
            if ($aksi == 'simpan') $notif[] = array('sukses', 'Pengaturan tersimpan.');
        } else {
            $notif[] = array('gagal', 'Pengaturan GAGAL disimpan (' . simpan_keterangan() . '). ' . db_error());
        }

        if ($aksi == 'uji_db') {
            $dbUji = ($CONFIG['sipp_db']['user'] != '') ? db_connect($CONFIG['sipp_db']) : pastikan_db($CONFIG);
            if ($dbUji) {
                $cek = db_rows("SELECT COUNT(*) AS jml FROM perkara");
                if ($cek !== false) $notif[] = array('sukses', 'Database SIPP terhubung. Ditemukan ' . number_format($cek[0]['jml'], 0, ',', '.') . ' perkara.');
                else { $r = pesan_ramah(db_error()); $notif[] = array('gagal', 'Terhubung, tapi tabel perkara tidak ditemukan. Pastikan "Nama database" adalah database SIPP.'); }
            } else {
                $r = pesan_ramah(db_error());
                $notif[] = array('gagal', $r['judul'] . ' ' . $r['saran'] . ' (' . db_error() . ')');
            }
        }
        if ($aksi == 'uji_web') {
            if ($CONFIG['website']['url'] == '') $notif[] = array('gagal', 'Isi dulu alamat website satker.');
            else {
                $res = kirim_ke_website($CONFIG, 'uji_koneksi', array('sumber' => 'uji', 'jumlah' => 0, 'data' => null));
                $j = json_decode($res['isi'], true);
                $pesanWeb = is_array($j) && isset($j['pesan']) ? $j['pesan'] : $res['pesan'];
                if ($res['kode'] == 422 && strpos($pesanWeb, 'uji_koneksi') !== false) {
                    $notif[] = array('sukses', 'Website satker terhubung dan kunci sudah cocok. Siap mengirim data.');
                } else {
                    $r = pesan_ramah($res['pesan'] . ' ' . $pesanWeb);
                    $notif[] = array('gagal', $r['judul'] . ' ' . $r['saran'] . ' (' . $res['pesan'] . ($pesanWeb != $res['pesan'] ? ': ' . $pesanWeb : '') . ')');
                }
            }
        }
    }
}

$izin = '';
$status    = baca_status();
$errKonfig = cek_konfigurasi($CONFIG);
$envTeks   = "sipp.apiKey = '" . $CONFIG['website']['api_key'] . "'\nsipp.secret = '" . $CONFIG['website']['secret'] . "'";
$alamatWeb = preg_replace('#/api/sipp/terima$#', '', $CONFIG['website']['url']);
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIPP API Kantor</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: "Segoe UI", Arial, sans-serif; background: #eef3f0; margin: 0; color: #2b2b2b; font-size: 15px; }
  header { background: #0f6b3b; color: #fff; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; }
  header h1 { margin: 0; font-size: 20px; } header small { opacity: .85; display: block; }
  nav a { color: #fff; text-decoration: none; padding: 8px 14px; border-radius: 4px; margin-left: 4px; display: inline-block; }
  nav a.aktif, nav a:hover { background: rgba(255,255,255,.18); }
  main { max-width: 1000px; margin: 22px auto; padding: 0 16px; }
  .kartu { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 18px 22px; margin-bottom: 18px; }
  .kartu h2 { margin: 0 0 12px; font-size: 18px; }
  .langkah { display: inline-block; background: #0f6b3b; color: #fff; border-radius: 50%; width: 26px; height: 26px; text-align: center; line-height: 26px; margin-right: 6px; font-size: 14px; }
  .notif { padding: 12px 16px; border-radius: 6px; margin-bottom: 14px; }
  .notif.sukses { background: #d4edda; color: #155724; } .notif.gagal { background: #f8d7da; color: #721c24; }
  .notif.info { background: #d1ecf1; color: #0c5460; }
  .btn { background: #0f6b3b; color: #fff; border: 0; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 15px; text-decoration: none; display: inline-block; }
  .btn.besar { padding: 14px 26px; font-size: 17px; } .btn.abu { background: #6c757d; } .btn.kecil { padding: 6px 12px; font-size: 13px; }
  .btn:hover { opacity: .9; }
  .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; }
  .kotak { border: 1px solid #e1e7e3; border-radius: 8px; padding: 14px; }
  .kotak h3 { margin: 0 0 4px; font-size: 16px; } .kotak p { margin: 4px 0; font-size: 13px; color: #555; }
  .lampu { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 6px; background: #bbb; vertical-align: middle; }
  .lampu.hijau { background: #28a745; } .lampu.merah { background: #dc3545; }
  label.f { display: block; font-weight: 600; margin: 12px 0 4px; }
  input[type=text], input[type=password], input[type=number], textarea { width: 100%; padding: 9px 10px; border: 1px solid #ccd5d0; border-radius: 5px; font-size: 15px; }
  .bantuan { font-size: 13px; color: #666; margin-top: 3px; }
  .cek { display: block; margin: 8px 0; } .cek small { color: #666; display: block; margin-left: 24px; }
  textarea.salin { font-family: Consolas, monospace; font-size: 13px; background: #1e1e1e; color: #b5f5b5; height: 60px; }
  pre { background: #1e1e1e; color: #d4d4d4; padding: 12px; overflow: auto; max-height: 420px; font-size: 12px; border-radius: 6px; }
  table { border-collapse: collapse; width: 100%; } th, td { border-bottom: 1px solid #e3e3e3; padding: 7px; text-align: left; font-size: 14px; }
  details summary { cursor: pointer; font-weight: 600; margin: 8px 0; }
  .login { max-width: 360px; margin: 70px auto; }
  #logOtomatis div { padding: 6px 0; border-bottom: 1px dashed #ddd; font-size: 14px; }
</style>
</head>
<body>
<header>
  <div><h1>SIPP API Kantor</h1><small>Mengirim data SIPP ke website satker</small></div>
  <?php if ($login && !isset($mode)) { ?>
  <nav>
    <a href="index.php" class="<?php echo $hal == 'beranda' ? 'aktif' : ''; ?>">Beranda</a>
    <a href="?hal=otomatis" class="<?php echo $hal == 'otomatis' ? 'aktif' : ''; ?>">Mode Otomatis</a>
    <a href="?hal=pengaturan" class="<?php echo $hal == 'pengaturan' ? 'aktif' : ''; ?>">Pengaturan</a>
    <a href="?keluar=1">Keluar</a>
  </nav>
  <?php } ?>
</header>
<main>
<?php if ($izin != '') { ?>
  <div class="notif info"><b>Perhatian:</b> <?php echo e($izin); ?></div>
<?php } ?>
<?php if ($login && !isset($mode) && perlu_unduh_config()) { ?>
  <div class="notif info">
    <b>Satu langkah lagi agar pengaturan tersimpan permanen:</b>
    klik tombol di bawah, lalu salin file <b>config.php</b> yang terunduh ke folder aplikasi ini
    (timpa file lama), dengan cara yang sama seperti saat menyalin aplikasi ini ke server.
    <form method="post" style="margin-top:10px">
      <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
      <button class="btn" name="aksi" value="unduh_config">&#11015; Unduh config.php</button>
    </form>
  </div>
<?php } ?>

<?php if (isset($mode) && $mode == 'buat_password') { /* ======== PERTAMA KALI ======== */ ?>
  <div class="kartu login">
    <h2>Selamat datang</h2>
    <p>Ini pertama kali aplikasi dibuka. Buat password untuk masuk ke dashboard.</p>
    <?php if ($err != '') echo '<div class="notif gagal">' . e($err) . '</div>'; ?>
    <form method="post">
      <label class="f">Password baru</label><input type="password" name="pass_baru" autofocus>
      <label class="f">Ulangi password</label><input type="password" name="pass_ulang">
      <p><button class="btn" type="submit">Simpan &amp; Lanjut</button></p>
    </form>
  </div>

<?php } elseif (!$login) { /* ======== LOGIN ======== */ ?>
  <div class="kartu login">
    <h2>Masuk</h2>
    <?php if ($pesanLogin != '') echo '<div class="notif gagal">' . e($pesanLogin) . '</div>'; ?>
    <form method="post">
      <label class="f">Password</label><input type="password" name="password" autofocus>
      <p><button class="btn" type="submit">Masuk</button></p>
    </form>
  </div>

<?php } else { foreach ($notif as $n) echo '<div class="notif ' . $n[0] . '">' . e($n[1]) . '</div>'; ?>

<?php if ($hal == 'beranda') { /* ======== BERANDA ======== */ ?>

  <?php if (count($errKonfig) > 0) { ?>
    <div class="notif info"><b>Aplikasi belum siap dipakai.</b> <?php echo e(implode(' ', $errKonfig)); ?>
      <br><br><a class="btn" href="?hal=pengaturan">Buka Pengaturan</a></div>
  <?php } ?>

  <div class="kartu">
    <h2>Kirim data ke website</h2>
    <form method="post" style="margin-bottom:16px">
      <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
      <button class="btn besar" name="aksi" value="kirim">&#8679; Kirim Semua Data Sekarang</button>
    </form>
    <div class="grid">
      <?php foreach ($namaJenis as $j => $nj) {
          $s = isset($status[$j]) ? $status[$j] : null; $aktif = !empty($CONFIG['aktif'][$j]); ?>
      <div class="kotak">
        <h3><span class="lampu <?php echo !$aktif ? '' : ($s ? ($s['ok'] ? 'hijau' : 'merah') : ''); ?>"></span><?php echo e($nj[0]); ?></h3>
        <p><?php echo e($nj[1]); ?></p>
        <?php if (!$aktif) { ?>
          <p><i>Tidak dikirim (bisa diaktifkan di Pengaturan)</i></p>
        <?php } elseif (!$s) { ?>
          <p><i>Belum pernah dikirim</i></p>
        <?php } elseif ($s['ok']) { ?>
          <p><b style="color:#155724">Berhasil</b> &middot; <?php echo e($s['jumlah']); ?> data<br>Terakhir: <?php echo e($s['waktu']); ?></p>
        <?php } else { $r = pesan_ramah($s['pesan']); ?>
          <p><b style="color:#721c24">Gagal:</b> <?php echo e($r['judul']); ?></p>
          <?php if ($r['saran']) echo '<p>&#128161; ' . e($r['saran']) . '</p>'; ?>
          <p>Terakhir dicoba: <?php echo e($s['waktu']); ?><?php if (isset($s['terakhir_sukses'])) echo '<br>Terakhir berhasil: ' . e($s['terakhir_sukses']); ?></p>
        <?php } ?>
        <form method="post" style="margin-top:8px">
          <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
          <input type="hidden" name="jenis" value="<?php echo e($j); ?>">
          <button class="btn kecil abu" name="aksi" value="lihat">Lihat isi</button>
          <?php if ($aktif) { ?><button class="btn kecil" name="aksi" value="kirim">Kirim</button><?php } ?>
        </form>
      </div>
      <?php } ?>
    </div>
  </div>

  <?php if ($hasilKirim !== null) { ?>
  <div class="kartu">
    <h2>Hasil pengiriman</h2>
    <?php foreach ($hasilKirim as $j => $info) { $r = pesan_ramah($info['pesan']); ?>
      <div class="notif <?php echo $info['ok'] ? 'sukses' : 'gagal'; ?>">
        <b><?php echo e($namaJenis[$j][0]); ?>:</b>
        <?php if ($info['ok']) { echo 'Berhasil, ' . e($info['jumlah']) . ' data terkirim.';
                 if (strpos($info['pesan'], 'cadangan') !== false) echo ' (Database SIPP tidak terhubung, memakai data cadangan dari sipp-publik.)'; }
              else { echo e($r['judul']); if ($r['saran']) echo '<br>&#128161; ' . e($r['saran']); } ?>
      </div>
    <?php } if (count($hasilKirim) == 0) echo '<p>Tidak ada data yang diaktifkan.</p>'; ?>
  </div>
  <?php } ?>

  <?php if ($pratinjau !== null) { $h = $pratinjau['hasil']; ?>
  <div class="kartu">
    <h2>Isi data: <?php echo e($namaJenis[$pratinjau['jenis']][0]); ?></h2>
    <?php if ($h['ok']) { ?>
      <p><?php echo e($h['jumlah']); ?> data. Ini yang akan dikirim ke website:</p>
      <pre><?php echo e(json_aman($h['data'])); ?></pre>
    <?php } else { $r = pesan_ramah($h['pesan']); ?>
      <div class="notif gagal"><?php echo e($r['judul']); ?><?php if ($r['saran']) echo '<br>&#128161; ' . e($r['saran']); ?></div>
    <?php } ?>
  </div>
  <?php } ?>

<?php } elseif ($hal == 'otomatis') { /* ======== MODE OTOMATIS ======== */ ?>

  <div class="kartu">
    <h2>Mode Otomatis</h2>
    <p>Data akan dikirim sendiri secara berkala <b>selama halaman ini tetap terbuka</b>.
       Cocok dibuka di komputer kantor yang menyala sepanjang hari. Jangan tutup tab ini.</p>
    <p>Kirim setiap:
      <select id="interval">
        <option value="5">5 menit</option><option value="15" selected>15 menit</option>
        <option value="30">30 menit</option><option value="60">1 jam</option>
      </select>
      <button class="btn" id="tombolMulai" onclick="mulai()">&#9654; Mulai</button>
      <button class="btn abu" id="tombolBerhenti" onclick="berhenti()" style="display:none">&#9632; Berhenti</button>
    </p>
    <p id="infoOtomatis" style="font-weight:600">Belum berjalan.</p>
    <div id="logOtomatis"></div>
    <details>
      <summary>Ingin berjalan otomatis tanpa membuka halaman? (untuk teknisi)</summary>
      <p>Jadwalkan perintah ini di server (cron Linux / Task Scheduler Windows), misalnya tiap 15 menit:</p>
      <pre>php <?php echo e(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'kirim.php'); ?></pre>
      <p>Contoh cron: <code>*/15 * * * * php <?php echo e(dirname(__FILE__) . '/kirim.php'); ?> &gt;/dev/null 2&gt;&amp;1</code></p>
    </details>
  </div>
  <script>
  var CSRF = <?php echo json_aman($csrf); ?>, timer = null, ping = null, sisa = 0;
  function post(ajax, cb) {
    var x = new XMLHttpRequest();
    x.open('POST', 'index.php?ajax=' + ajax, true);
    x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.onreadystatechange = function () {
      if (x.readyState != 4) return;
      var d = null; try { d = JSON.parse(x.responseText); } catch (e) {}
      cb(d);
    };
    x.send('csrf=' + encodeURIComponent(CSRF));
  }
  function tulis(html) {
    var log = document.getElementById('logOtomatis');
    var div = document.createElement('div'); div.innerHTML = html;
    log.insertBefore(div, log.firstChild);
    while (log.children.length > 50) log.removeChild(log.lastChild);
  }
  function kirim() {
    document.getElementById('infoOtomatis').innerHTML = 'Sedang mengirim...';
    post('kirim', function (d) {
      if (!d || !d.login) { berhenti(); tulis('<b style="color:#b00">Sesi login habis. Muat ulang halaman lalu masuk lagi.</b>'); return; }
      var baris = '<b>' + d.waktu + '</b> ';
      if (d.hasil.length == 0) baris += 'Pengaturan belum lengkap, buka menu Pengaturan.';
      for (var i = 0; i < d.hasil.length; i++) {
        var h = d.hasil[i];
        baris += (h.ok ? '&#9989; ' : '&#10060; ') + h.nama + (h.ok ? ' (' + h.jumlah + ')' : ': ' + h.pesan) + ' &nbsp; ';
      }
      tulis(baris);
      sisa = parseInt(document.getElementById('interval').value, 10) * 60;
    });
  }
  function hitung() {
    if (sisa <= 0) { kirim(); sisa = 99999; return; }
    sisa--;
    if (sisa < 99999) {
      var m = Math.floor(sisa / 60), s = sisa % 60;
      document.getElementById('infoOtomatis').innerHTML = '&#9989; Berjalan. Pengiriman berikutnya dalam ' + m + ' menit ' + (s < 10 ? '0' : '') + s + ' detik.';
    }
  }
  function mulai() {
    sisa = 0; timer = setInterval(hitung, 1000);
    ping = setInterval(function () { post('ping', function () {}); }, 5 * 60 * 1000); // jaga sesi login
    document.getElementById('tombolMulai').style.display = 'none';
    document.getElementById('tombolBerhenti').style.display = 'inline-block';
  }
  function berhenti() {
    clearInterval(timer); clearInterval(ping);
    document.getElementById('infoOtomatis').innerHTML = 'Dihentikan.';
    document.getElementById('tombolMulai').style.display = 'inline-block';
    document.getElementById('tombolBerhenti').style.display = 'none';
  }
  </script>

<?php } else { /* ======== PENGATURAN ======== */ ?>

  <?php if (isset($_GET['pertama'])) { ?>
    <div class="notif info">Password tersimpan. Sekarang isi pengaturan di bawah ini langkah demi langkah.</div>
  <?php } ?>

  <form method="post">
  <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">

  <div class="kartu">
    <h2><span class="langkah">1</span>Database SIPP</h2>
    <?php $otomatis = ($GLOBALS['DB_SUMBER'] == 'sipp' || $GLOBALS['DB_SUMBER'] == 'simerak');
          if ($otomatis) { ?>
      <div class="notif sukses">&#9989; Database SIPP sudah terhubung otomatis
        (memakai pengaturan aplikasi <?php echo $GLOBALS['DB_SUMBER'] == 'sipp' ? 'SIPP' : 'SIMERAK'; ?> di server ini).
        Bagian ini <b>tidak perlu diisi</b>.</div>
      <details><summary>Ingin memakai koneksi database lain?</summary>
    <?php } elseif ($GLOBALS['DB_LINK']) { ?>
      <div class="notif sukses">&#9989; Database SIPP terhubung.</div>
    <?php } else { ?>
      <div class="notif info">Database SIPP tidak ditemukan otomatis. Isi data di bawah ini, lalu klik <b>Tes koneksi database</b>.</div>
    <?php } ?>
    <p class="bantuan">Data ini sama dengan yang dipakai untuk membuka database SIPP di SQLyog / phpMyAdmin.</p>
    <label class="f">Alamat server database</label>
    <input type="text" name="db_host" value="<?php echo e($CONFIG['sipp_db']['host']); ?>">
    <div class="bantuan">Biasanya <b>localhost</b> jika database SIPP ada di server ini, atau alamat IP server SIPP (mis. 192.168.10.10).</div>
    <label class="f">Nama pengguna database</label>
    <input type="text" name="db_user" value="<?php echo e($CONFIG['sipp_db']['user']); ?>">
    <label class="f">Password database</label>
    <input type="password" name="db_pass" value="" placeholder="<?php echo $CONFIG['sipp_db']['pass'] != '' ? '(tersimpan, kosongkan jika tidak diubah)' : ''; ?>">
    <label class="f">Nama database SIPP</label>
    <input type="text" name="db_name" value="<?php echo e($CONFIG['sipp_db']['name']); ?>">
    <?php if ($otomatis) echo '</details>'; ?>
    <p><button class="btn abu" name="aksi" value="uji_db">Tes koneksi database</button></p>
  </div>

  <div class="kartu">
    <h2><span class="langkah">2</span>Website satker</h2>
    <label class="f">Alamat website satker</label>
    <input type="text" name="web_alamat" value="<?php echo e($alamatWeb); ?>" placeholder="https://pa-namasatker.go.id">
    <div class="bantuan">Cukup alamat utamanya, mis. <b>https://pa-tulangbawang.go.id</b>.
      Data dikirim ke: <code><?php echo e($CONFIG['website']['url'] != '' ? $CONFIG['website']['url'] : '(belum diisi)'); ?></code></div>
    <label class="cek"><input type="checkbox" name="web_ssl" value="1" <?php echo !empty($CONFIG['website']['verify_ssl']) ? 'checked' : ''; ?>>
      Periksa sertifikat SSL <small>Hilangkan centang hanya jika muncul pesan "Koneksi aman (HTTPS) ke website gagal".</small></label>

    <label class="f">Kunci untuk website (tempel ke file <code>.env</code> website satker)</label>
    <?php if (strlen($CONFIG['website']['api_key']) >= 16) { ?>
      <textarea class="salin" id="teksEnv" readonly><?php echo e($envTeks); ?></textarea>
      <p><button type="button" class="btn kecil" onclick="salin()">&#128203; Salin</button>
         <span id="tersalin" style="color:#155724;display:none">Tersalin!</span>
         &nbsp; <button class="btn kecil abu" name="aksi" value="kunci_baru" onclick="return confirm('Buat kunci baru? Kunci lama tidak berlaku lagi dan teks .env di website harus diganti.')">Buat kunci baru</button></p>
      <div class="bantuan">Berikan teks ini kepada pengelola website satker untuk ditempel di file <b>.env</b>. Jangan dibagikan ke orang lain.</div>
    <?php } else { ?>
      <div class="bantuan">Kunci akan dibuat otomatis saat Anda menekan <b>Simpan</b>.</div>
    <?php } ?>
    <p><button class="btn abu" name="aksi" value="uji_web">Tes koneksi website</button></p>
  </div>

  <div class="kartu">
    <h2><span class="langkah">3</span>Data yang dikirim</h2>
    <?php foreach ($namaJenis as $j => $nj) { ?>
      <label class="cek"><input type="checkbox" name="aktif_<?php echo $j; ?>" value="1" <?php echo !empty($CONFIG['aktif'][$j]) ? 'checked' : ''; ?>>
        <b><?php echo e($nj[0]); ?></b> <small><?php echo e($nj[1]); ?></small></label>
    <?php } ?>
    <label class="f">Jadwal sidang: berapa hari ke depan?</label>
    <input type="number" name="jadwal_hari" min="1" max="31" value="<?php echo (int)$CONFIG['jadwal_hari_ke_depan']; ?>" style="max-width:120px">
    <label class="cek"><input type="checkbox" name="jadwal_nama" value="1" <?php echo !empty($CONFIG['jadwal_kirim_nama_pihak']) ? 'checked' : ''; ?>>
      Tampilkan nama para pihak di jadwal sidang <small>Tidak disarankan untuk perkara perceraian (privasi).</small></label>
    <label class="cek"><input type="checkbox" name="ghaib_nama" value="1" <?php echo !empty($CONFIG['ghaib_kirim_nama']) ? 'checked' : ''; ?>>
      Tampilkan nama Tergugat/Termohon di pengumuman panggilan ghaib</label>
    <label class="cek"><input type="checkbox" name="ghaib_alamat" value="1" <?php echo !empty($CONFIG['ghaib_kirim_alamat']) ? 'checked' : ''; ?>>
      Tampilkan alamat terakhir Tergugat/Termohon di pengumuman panggilan ghaib</label>
  </div>

  <div class="kartu">
    <h2><span class="langkah">4</span>Password dashboard</h2>
    <label class="f">Password baru</label>
    <input type="password" name="pass_baru" placeholder="Kosongkan jika tidak ingin mengganti">
  </div>

  <div class="kartu">
    <details>
      <summary>Pengaturan lanjutan (untuk teknisi)</summary>
      <label class="f">Query antrian (SQL SELECT ke database SIPP)</label>
      <textarea name="antrian_sql" rows="3"><?php echo e($CONFIG['antrian_sql']); ?></textarea>
      <label class="f">API cadangan jadwal sidang (sipp-publik)</label>
      <input type="text" name="cadangan" value="<?php echo e($CONFIG['cadangan_api_jadwal']); ?>">
      <div class="bantuan">Dipakai jika database SIPP tidak bisa dihubungi. Kosongkan untuk menonaktifkan.</div>
    </details>
  </div>

  <p><button class="btn besar" name="aksi" value="simpan">&#128190; Simpan Pengaturan</button></p>
  <p class="bantuan">Pengaturan tersimpan di: <?php echo e(simpan_keterangan()); ?></p>
  </form>
  <script>
  function salin() {
    var t = document.getElementById('teksEnv'); t.select();
    try { document.execCommand('copy'); document.getElementById('tersalin').style.display = 'inline'; } catch (e) {}
  }
  </script>

<?php } } ?>
</main>
</body>
</html>
