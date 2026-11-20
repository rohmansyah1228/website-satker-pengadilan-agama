<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
    <title>Verifikasi MFA — Panel Admin</title>
    <?= favicon_tags() ?>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fonts/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body>
<main class="login-wrap">
    <div class="card login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <i class="bi bi-shield-check display-4 text-pa-primary" aria-hidden="true"></i>
            <h1 class="h4 font-display fw-bold text-pa-primary mt-2">Verifikasi Dua Langkah</h1>
            <p class="text-secondary small mb-0">Masukkan 6 digit kode dari aplikasi authenticator di ponsel Anda.</p>
        </div>
        <?= view('admin/_flash') ?>
        <form action="<?= admin_url('login/mfa') ?>" method="post">
            <?= csrf_field() ?>
            <label for="code" class="form-label fw-semibold">Kode verifikasi</label>
            <input type="text" id="code" name="code" class="form-control form-control-lg text-center fs-3 mb-3" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" required autofocus enterkeyhint="done">
            <button type="submit" class="btn btn-pa-primary btn-lg w-100">Verifikasi</button>
        </form>
        <a href="<?= admin_url('login') ?>" class="d-block text-center small mt-3">Kembali ke halaman masuk</a>
    </div>
</main>
</body>
</html>
