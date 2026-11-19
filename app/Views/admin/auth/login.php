<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
    <title>Masuk — Panel Admin <?= esc(setting('site_name')) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fonts/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body>
<main class="login-wrap">
    <div class="card login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="<?= esc(setting('logo') ? media_url(setting('logo')) : base_url('assets/img/logo-default.svg'), 'attr') ?>" alt="" width="64" height="64" style="object-fit:contain">
            <h1 class="h4 font-display fw-bold text-pa-primary mt-3 mb-1">Masuk Panel Admin</h1>
            <p class="text-secondary small mb-0"><?= esc(setting('site_name')) ?></p>
        </div>
        <?= view('admin/_flash') ?>
        <?php if ($locked): ?>
            <div class="alert alert-danger small" role="alert">Terlalu banyak percobaan gagal dari alamat Anda. Coba lagi dalam beberapa menit.</div>
        <?php endif ?>
        <form action="<?= admin_url('login') ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <div class="hp-field" aria-hidden="true"><label for="website">Website</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Nama pengguna</label>
                <input type="text" id="username" name="username" class="form-control form-control-lg" autocomplete="username" spellcheck="false" autocapitalize="none" autocorrect="off" required autofocus value="<?= esc(old('username'), 'attr') ?>" enterkeyhint="next">
            </div>
            <div class="mb-3">
                <label for="current-password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <input type="password" id="current-password" name="password" class="form-control form-control-lg" autocomplete="current-password" required enterkeyhint="done">
                    <button class="btn btn-outline-secondary" type="button" id="togglePw" aria-controls="current-password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i><span class="visually-hidden">Tampilkan password</span></button>
                </div>
            </div>
            <button type="submit" class="btn btn-pa-primary btn-lg w-100"<?= $locked ? ' disabled' : '' ?>>Masuk</button>
        </form>
        <p class="small text-secondary mt-4 mb-0 text-center"><i class="bi bi-shield-lock" aria-hidden="true"></i> Dilindungi pembatasan percobaan & MFA. Lupa password? Hubungi Super Admin.</p>
    </div>
</main>
<script src="<?= asset('assets/js/login.js') ?>" defer></script>
</body>
</html>
