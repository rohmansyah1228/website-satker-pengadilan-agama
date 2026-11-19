<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="<?= admin_url('pengguna') ?>">Pengguna</a></li><li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li></ol></nav>
<h1 class="h3 font-display fw-bold text-pa-primary mb-3"><?= esc($title) ?></h1>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form action="<?= $id ? admin_url('pengguna/' . $id) : admin_url('pengguna/baru') ?>" method="post" class="card p-4" style="max-width: 40rem" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label for="name" class="form-label fw-semibold">Nama Lengkap</label><input type="text" id="name" name="name" value="<?= esc($u['name'] ?? '', 'attr') ?>" class="form-control" autocomplete="off" required></div>
    <div class="mb-3"><label for="username" class="form-label fw-semibold">Nama Pengguna</label><input type="text" id="username" name="username" value="<?= esc($u['username'] ?? '', 'attr') ?>" class="form-control" autocomplete="off" spellcheck="false" autocapitalize="none" required></div>
    <div class="mb-3"><label for="email" class="form-label fw-semibold">Email</label><input type="email" id="email" name="email" value="<?= esc($u['email'] ?? '', 'attr') ?>" class="form-control" autocomplete="off"></div>
    <div class="mb-3"><label for="role" class="form-label fw-semibold">Peran</label>
        <select id="role" name="role" class="form-select"<?= $id && (int) $id === (int) $user['id'] ? ' disabled' : '' ?>>
            <?php foreach ($roles as $k => $l): ?><option value="<?= $k ?>"<?= ($u['role'] ?? '') === $k ? ' selected' : '' ?>><?= esc($l) ?></option><?php endforeach ?>
        </select>
        <?php if ($id && (int) $id === (int) $user['id']): ?><input type="hidden" name="role" value="<?= esc($u['role'], 'attr') ?>"><?php endif ?>
    </div>
    <div class="mb-3"><label for="new-password" class="form-label fw-semibold">Password<?= $id ? ' baru (kosongkan bila tidak diganti)' : '' ?></label>
        <input type="password" id="new-password" name="password" class="form-control" autocomplete="new-password" aria-describedby="pwHelp"<?= $id ? '' : ' required' ?>>
        <div id="pwHelp" class="form-text">Minimal 10 karakter, memuat huruf besar, huruf kecil, dan angka.</div></div>
    <div class="form-check form-switch mb-3"><input type="checkbox" class="form-check-input" role="switch" id="is_active" name="is_active" value="1"<?= ! empty($u['is_active']) ? ' checked' : '' ?>><label class="form-check-label" for="is_active">Akun aktif</label></div>
    <div class="d-flex gap-2"><button class="btn btn-pa-primary">Simpan</button><a href="<?= admin_url('pengguna') ?>" class="btn btn-outline-secondary">Batal</a></div>
</form>
<?= $this->endSection() ?>
