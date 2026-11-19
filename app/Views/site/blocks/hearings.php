<?php $rows = $data['rows'] ?? []; ?>
<div class="table-responsive<?= $nested ? '' : ' bg-white rounded-3 shadow-sm p-3' ?>">
    <table class="table table-sm table-hover align-middle mb-2 small">
        <caption class="small">Agenda persidangan <?= tanggal_id(date('Y-m-d'), true) ?></caption>
        <thead class="table-success"><tr><th scope="col">Jam</th><th scope="col">Nomor Perkara</th><th scope="col">Agenda</th><th scope="col">Ruang</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?><tr><td><?= esc($r['hearing_time'] ?? '') ?></td><td><?= esc($r['case_number']) ?></td><td><?= esc($r['agenda']) ?></td><td><?= esc($r['room']) ?></td></tr><?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="4" class="text-center text-secondary">Tidak ada jadwal persidangan hari ini.</td></tr><?php endif ?>
        </tbody>
    </table>
    <?php if (! $nested): ?><a href="<?= site_url('layanan-hukum/jadwal-sidang') ?>" class="btn btn-sm btn-pa-primary">Jadwal lengkap</a><?php endif ?>
</div>
