<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h1 class="h3 font-display fw-bold text-pa-primary mb-3">Akun Saya</h1>
<div class="row g-3">
    <div class="col-lg-6">
        <form action="<?= admin_url('akun/password') ?>" method="post" class="card p-4">
            <?= csrf_field() ?>
            <h2 class="h5 fw-bold">Ganti Password</h2>
            <input type="text" name="username" value="<?= esc($user['username'], 'attr') ?>" autocomplete="username" hidden>
            <div class="mb-3"><label for="current-password" class="form-label">Password saat ini</label><input type="password" id="current-password" name="current_password" class="form-control" autocomplete="current-password" required></div>
            <div class="mb-3"><label for="new-password" class="form-label">Password baru</label><input type="password" id="new-password" name="new_password" class="form-control" autocomplete="new-password" required aria-describedby="npHelp"><div id="npHelp" class="form-text">Minimal 10 karakter, huruf besar, huruf kecil, dan angka.</div></div>
            <div class="mb-3"><label for="confirm-password" class="form-label">Ulangi password baru</label><input type="password" id="confirm-password" name="confirm_password" class="form-control" autocomplete="new-password" required></div>
            <div><button class="btn btn-pa-primary">Simpan Password</button></div>
        </form>
    </div>
    <div class="col-lg-6">
        <div class="card p-4">
            <h2 class="h5 fw-bold">Autentikasi Dua Faktor (MFA)</h2>
            <p class="mb-3"><?= $user['totp_enabled'] ? '<span class="badge bg-success">Aktif</span> Login memerlukan kode dari aplikasi authenticator.' : '<span class="badge bg-warning text-dark">Belum aktif</span>' ?></p>
            <a href="<?= admin_url('akun/2fa') ?>" class="btn btn-outline-success align-self-start">Kelola MFA</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
