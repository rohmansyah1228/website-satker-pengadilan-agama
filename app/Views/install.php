<?php
/** @var array $state @var bool $done */
$old    ??= [];
$errors ??= [];
$v = static fn (string $k, string $d = '') => esc((string) ($old[$k] ?? $d), 'attr');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pemasangan Website Satker</title>
    <?= favicon_tags() ?>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fonts/fonts.css') ?>">
    <style>
        body { background: #f3f6f5; font-family: Inter, sans-serif; }
        .hdr { background: linear-gradient(90deg, #034d37, #056e4f); color: #fff; border-bottom: 4px solid #ff9900; }
        .card { border: 0; box-shadow: 0 .25rem 1rem rgba(0,0,0,.06); }
        h2 { font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #056e4f; }
        .btn-pa { background: #056e4f; border-color: #056e4f; color: #fff; font-weight: 600; }
        .btn-pa:hover { background: #034d37; color: #fff; }
        .check li { padding: .3rem 0; }
    </style>
</head>
<body>
<header class="hdr py-4 mb-4">
    <div class="container" style="max-width: 820px">
        <h1 class="h4 fw-bold mb-1"><i class="bi bi-gear-wide-connected" aria-hidden="true"></i> Pemasangan Website Satker Peradilan Agama</h1>
        <p class="mb-0 small opacity-75">Sesuai SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026 · halaman ini otomatis tertutup setelah selesai</p>
    </div>
</header>
<main class="container pb-5" style="max-width: 820px">

<?php if ($done): ?>
    <div class="card p-4 text-center">
        <div class="display-5 text-success mb-2"><i class="bi bi-check-circle-fill" aria-hidden="true"></i></div>
        <h2 class="h4 text-dark text-normal" style="text-transform:none;letter-spacing:0">Pemasangan selesai</h2>
        <p class="text-secondary">Tabel, halaman & menu wajib SK, serta akun Super Admin sudah dibuat.</p>
        <p class="mb-4"><strong>Alamat panel admin:</strong><br><a href="<?= esc($adminUrl, 'attr') ?>" class="font-monospace"><?= esc($adminUrl) ?></a><br><span class="small text-secondary">Simpan alamat ini. Saat login pertama Anda akan diminta mengaktifkan verifikasi 2 langkah (MFA).</span></p>
        <div class="d-flex justify-content-center gap-2">
            <a href="<?= esc($adminUrl, 'attr') ?>" class="btn btn-pa"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Masuk Panel Admin</a>
            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">Lihat Website</a>
        </div>
        <ul class="text-start small text-secondary mt-4 mb-0">
            <li>Lengkapi <strong>Pengaturan</strong> (nama satker, alamat, kontak, logo) lalu ganti teks bertanda <strong>[LENGKAPI]</strong>.</li>
            <li>Bila hosting memiliki SSL, aktifkan HTTPS: ubah <code>app.forceGlobalSecureRequests</code> dan <code>cookie.secure</code> menjadi <code>true</code> di berkas .env.</li>
        </ul>
    </div>
<?php else: ?>

    <section class="card p-4 mb-3" aria-labelledby="h-cek">
        <h2 id="h-cek">1. Pemeriksaan server</h2>
        <ul class="list-unstyled check mb-0">
            <?php foreach ($state['checks'] as [$label, $ok]): ?>
                <li><i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> me-2" aria-hidden="true"></i><?= esc($label) ?><span class="visually-hidden"><?= $ok ? ' — terpenuhi' : ' — BELUM terpenuhi' ?></span></li>
            <?php endforeach ?>
        </ul>
        <?php if (! $state['checksOk']): ?>
            <div class="alert alert-danger small mt-3 mb-0">Perbaiki butir bertanda merah dulu (pengaturan PHP di cPanel → <em>Select PHP Version</em>, atau izin folder 755/775), lalu muat ulang halaman ini.</div>
        <?php endif ?>
    </section>

    <?php if ($state['hasEnv'] && ! $state['dbOk']): ?>
        <div class="alert alert-danger"><strong>Berkas .env sudah ada, tetapi database tidak dapat dihubungi.</strong> Periksa isian <code>database.default.*</code> di berkas .env (demi keamanan, halaman ini tidak mengubah .env yang sudah ada).</div>
    <?php endif ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div>
    <?php endif ?>

    <?php if ($state['canInstall']): ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <?php if ($state['needDbForm']): ?>
        <section class="card p-4 mb-3" aria-labelledby="h-db">
            <h2 id="h-db">2. Database</h2>
            <p class="small text-secondary">Buat database &amp; pengguna database lebih dulu (cPanel → <em>MySQL Databases</em> / <em>Database Wizard</em>), beri pengguna hak <em>ALL PRIVILEGES</em> pada database tersebut.</p>
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label fw-semibold" for="db_host">Host database</label><input class="form-control" id="db_host" name="db_host" value="<?= $v('db_host', 'localhost') ?>" required></div>
                <div class="col-md-4"><label class="form-label fw-semibold" for="db_port">Port</label><input class="form-control" id="db_port" name="db_port" value="<?= $v('db_port', '3306') ?>" inputmode="numeric"></div>
                <div class="col-md-12"><label class="form-label fw-semibold" for="db_name">Nama database</label><input class="form-control font-monospace" id="db_name" name="db_name" value="<?= $v('db_name') ?>" required placeholder="mis. akunhosting_satker"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="db_user">Pengguna database</label><input class="form-control font-monospace" id="db_user" name="db_user" value="<?= $v('db_user') ?>" required autocomplete="off"></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="db_pass">Password database</label><input class="form-control" type="password" id="db_pass" name="db_pass" autocomplete="new-password"></div>
            </div>
        </section>
        <?php endif ?>

        <section class="card p-4 mb-3" aria-labelledby="h-site">
            <h2 id="h-site"><?= $state['needDbForm'] ? '3' : '2' ?>. Website &amp; Akun Super Admin</h2>
            <div class="row g-3">
                <div class="col-12"><label class="form-label fw-semibold" for="site_name">Nama satker</label><input class="form-control" id="site_name" name="site_name" value="<?= $v('site_name') ?>" placeholder="mis. Pengadilan Agama Nama Kota"></div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="admin_path">Alamat panel admin</label>
                    <div class="input-group"><span class="input-group-text small"><?= esc(rtrim($state['baseURL'], '/')) ?>/</span><input class="form-control font-monospace" id="admin_path" name="admin_path" value="<?= $v('admin_path', config('App')->adminPath) ?>"<?= $state['needDbForm'] ? '' : ' readonly' ?>></div>
                    <div class="form-text"><?= $state['needDbForm'] ? 'Gunakan nama yang sulit ditebak orang lain (huruf kecil, angka, tanda hubung).' : 'Diatur di berkas .env (app.adminPath).' ?></div>
                </div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="name">Nama lengkap admin</label><input class="form-control" id="name" name="name" value="<?= $v('name', 'Super Admin') ?>" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold" for="email">Email (opsional)</label><input class="form-control" type="email" id="email" name="email" value="<?= $v('email') ?>"></div>
                <div class="col-md-4"><label class="form-label fw-semibold" for="username">Nama pengguna</label><input class="form-control font-monospace" id="username" name="username" value="<?= $v('username', 'admin') ?>" required autocomplete="username"></div>
                <div class="col-md-4"><label class="form-label fw-semibold" for="password">Password</label><input class="form-control" type="password" id="password" name="password" required autocomplete="new-password" minlength="10"></div>
                <div class="col-md-4"><label class="form-label fw-semibold" for="password2">Ulangi password</label><input class="form-control" type="password" id="password2" name="password2" required autocomplete="new-password"></div>
                <div class="col-12 form-text mt-1">Password minimal 10 karakter, berisi huruf dan angka.</div>
            </div>
        </section>

        <div class="d-grid"><button class="btn btn-pa btn-lg"><i class="bi bi-rocket-takeoff" aria-hidden="true"></i> Pasang Website</button></div>
        <p class="small text-secondary text-center mt-2">Alamat situs terdeteksi: <code><?= esc($state['baseURL']) ?></code></p>
    </form>
    <?php endif ?>
<?php endif ?>
</main>
</body>
</html>
