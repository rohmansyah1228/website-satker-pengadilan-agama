<div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
        <thead class="table-success"><tr><th scope="col" class="text-start">Periode</th><th scope="col">Permohonan Diterima</th><th scope="col">Rata-rata Waktu Pemenuhan (hari)</th><th scope="col">Dikabulkan Seluruhnya</th><th scope="col">Dikabulkan Sebagian</th><th scope="col">Ditolak</th><th scope="col" class="text-start">Alasan Penolakan</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><th scope="row" class="text-start"><?= bulan_id((int) $r['month']) ?> <?= (int) $r['year'] ?></th><td><?= (int) $r['received'] ?></td><td><?= esc(rtrim(rtrim((string) $r['avg_days'], '0'), '.')) ?: '0' ?></td><td><?= (int) $r['granted_full'] ?></td><td><?= (int) $r['granted_partial'] ?></td><td><?= (int) $r['rejected'] ?></td><td class="text-start"><?= esc($r['reject_reasons'] ?: '-') ?></td></tr>
        <?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="7" class="text-secondary">Laporan belum tersedia.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php if (setting('ppid_form_url')): ?><a href="<?= esc(safe_href(setting('ppid_form_url')), 'attr') ?>" class="btn btn-pa-secondary" target="_blank" rel="noopener noreferrer">Formulir Permohonan Informasi</a><?php endif ?>
