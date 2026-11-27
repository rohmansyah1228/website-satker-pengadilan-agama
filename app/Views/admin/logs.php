<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h1 class="h3 font-display fw-bold text-pa-primary mb-1">Log Aktivitas</h1>
<p class="small text-secondary mb-3"><?= number_format($total, 0, ',', '.') ?> catatan.
    <?= $keep > 0 ? 'Catatan lebih dari ' . ($keep % 12 === 0 ? ($keep / 12) . ' tahun' : $keep . ' bulan') . ' dihapus otomatis.' : 'Catatan disimpan selamanya.' ?>
    <a href="<?= admin_url('pengaturan?tab=keamanan') ?>">Ubah lama penyimpanan</a></p>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card"><div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0 small">
            <thead><tr><th scope="col">Waktu</th><th scope="col">Pengguna</th><th scope="col">Aksi</th><th scope="col">Objek</th><th scope="col">IP</th></tr></thead>
            <tbody><?php foreach ($rows as $r): ?><tr><td class="text-nowrap"><?= esc($r['created_at']) ?></td><td><?= esc($r['username']) ?></td><td><?= esc($r['action']) ?></td><td><?= esc($r['subject']) ?></td><td><code><?= esc($r['ip']) ?></code></td></tr><?php endforeach ?></tbody>
        </table></div></div>
        <div class="mt-3"><?= $pager ?></div>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-header bg-white"><h2 class="h6 fw-bold mb-0">Percobaan Login Gagal Terakhir</h2></div>
            <ul class="list-group list-group-flush small">
                <?php foreach ($logins as $l): ?><li class="list-group-item d-flex justify-content-between"><span><code><?= esc($l['ip']) ?></code> · <?= esc($l['username']) ?></span><span class="text-secondary"><?= esc($l['created_at']) ?></span></li><?php endforeach ?>
                <?php if (! $logins): ?><li class="list-group-item text-secondary">Tidak ada.</li><?php endif ?>
            </ul></div>
    </div>
</div>
<?= $this->endSection() ?>
