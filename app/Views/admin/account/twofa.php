<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?= admin_url('akun') ?>">Akun Saya</a></li><li class="breadcrumb-item active" aria-current="page">MFA</li></ol></nav>
<h1 class="h3 font-display fw-bold text-pa-primary mb-3">Autentikasi Dua Faktor (MFA)</h1>
<?php if ($secret): ?>
    <div class="card p-4" style="max-width: 44rem">
        <ol class="mb-3">
            <li>Pasang aplikasi authenticator di ponsel (Google Authenticator, Microsoft Authenticator, Aegis, dll.).</li>
            <li>Pindai kode QR di bawah ini, atau masukkan kunci rahasia secara manual.</li>
            <li>Masukkan 6 digit kode yang muncul di aplikasi untuk mengaktifkan.</li>
        </ol>
        <div class="d-flex flex-wrap gap-4 align-items-center mb-3">
            <div id="totpQr" data-uri="<?= esc($uri, 'attr') ?>" style="width: 200px; height: 200px" class="border rounded p-1 bg-white"></div>
            <div><p class="small text-secondary mb-1">Kunci rahasia (manual):</p><code class="fs-6 user-select-all" style="word-break: break-all"><?= esc(trim(chunk_split($secret, 4, ' '))) ?></code></div>
        </div>
        <form action="<?= admin_url('akun/2fa/aktifkan') ?>" method="post" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-auto"><label for="code" class="form-label fw-semibold">Kode verifikasi</label><input type="text" id="code" name="code" class="form-control form-control-lg text-center" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required style="width: 10rem"></div>
            <div class="col-auto"><button class="btn btn-pa-primary btn-lg">Aktifkan MFA</button></div>
        </form>
    </div>
<?php else: ?>
    <div class="card p-4" style="max-width: 40rem">
        <p><span class="badge bg-success">Aktif</span> Akun Anda dilindungi MFA.</p>
        <?php if ($forced): ?>
            <p class="small text-secondary mb-0">MFA diwajibkan untuk semua pengguna. Bila ponsel hilang, minta Super Admin melakukan "Reset MFA".</p>
        <?php else: ?>
            <form action="<?= admin_url('akun/2fa/nonaktifkan') ?>" method="post" class="row g-2 align-items-end" data-confirm="Nonaktifkan MFA? Akun menjadi kurang aman.">
                <?= csrf_field() ?>
                <div class="col-auto"><label for="cp" class="form-label">Password</label><input type="password" id="cp" name="current_password" class="form-control" autocomplete="current-password" required></div>
                <div class="col-auto"><button class="btn btn-outline-danger">Nonaktifkan MFA</button></div>
            </form>
        <?php endif ?>
    </div>
<?php endif ?>
<?= $this->endSection() ?>
