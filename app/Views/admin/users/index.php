<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 font-display fw-bold text-pa-primary mb-0">Pengguna</h1>
    <a href="<?= admin_url('pengguna/baru') ?>" class="btn btn-pa-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Pengguna Baru</a>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th scope="col">Nama</th><th scope="col">Nama Pengguna</th><th scope="col">Peran</th><th scope="col">MFA</th><th scope="col">Status</th><th scope="col">Login Terakhir</th><th scope="col" class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
            <tr>
                <td class="fw-semibold"><?= esc($u['name']) ?></td><td><code><?= esc($u['username']) ?></code></td>
                <td><?= esc($roles[$u['role']] ?? $u['role']) ?></td>
                <td><?= $u['totp_enabled'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-warning text-dark">Belum</span>' ?></td>
                <td><?= $u['is_active'] ? 'Aktif' : '<span class="text-danger">Nonaktif</span>' ?></td>
                <td class="small text-secondary"><?= esc($u['last_login_at'] ?? '–') ?></td>
                <td class="text-end text-nowrap">
                    <a href="<?= admin_url('pengguna/' . $u['id']) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil" aria-hidden="true"></i> Ubah</a>
                    <?php if ($u['totp_enabled']): ?>
                        <form action="<?= admin_url('pengguna/' . $u['id'] . '/reset-2fa') ?>" method="post" class="d-inline" data-confirm="Reset MFA untuk <?= esc($u['name'], 'attr') ?>? Pengguna harus memindai QR baru."><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning">Reset MFA</button></form>
                    <?php endif ?>
                    <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                        <form action="<?= admin_url('pengguna/' . $u['id'] . '/hapus') ?>" method="post" class="d-inline" data-confirm="Hapus pengguna <?= esc($u['name'], 'attr') ?>?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Hapus</span></button></form>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div></div>
<div class="card p-3 mt-3 small"><strong>Hak akses peran:</strong>
<ul class="mb-0"><li><strong>Super Admin</strong> — semua modul, pengguna, keamanan, log.</li><li><strong>Admin Konten</strong> — semua konten, menu, dan pengaturan (kecuali keamanan & pengguna).</li><li><strong>Operator Kepaniteraan</strong> — halaman Layanan Hukum, jadwal sidang, panggilan ghaib, statistik, dokumen kepaniteraan.</li><li><strong>Operator Kesekretariatan</strong> — halaman Profil & Transparansi, pegawai, LHKPN, wilayah, dokumen SAKIP/keuangan.</li><li><strong>Operator Humas</strong> — berita dan slider.</li></ul></div>
<?= $this->endSection() ?>
