<?php $s = $data['stat'] ?? null; ?>
<?php if (! $s): ?><p class="<?= $onDark ? 'text-white-75' : 'text-secondary' ?> small mb-0">Statistik perkara belum tersedia.</p><?php return; endif ?>
<p class="<?= $onDark ? 'text-white-75' : 'text-secondary' ?> small mb-3<?= $nested ? '' : ' text-center' ?>">Keadaan perkara bulan <?= bulan_id((int) $s['month']) ?> <?= (int) $s['year'] ?></p>
<div class="row g-3 row-cols-2<?= $nested ? '' : ' row-cols-md-4' ?> text-center">
    <?php foreach (['carried_over' => 'Sisa Bulan Lalu', 'received' => 'Perkara Masuk', 'decided' => 'Perkara Putus', 'remaining' => 'Sisa Perkara'] as $k => $l): ?>
        <div class="col"><div class="<?= $onDark ? 'bg-white bg-opacity-10' : 'bg-light' ?> rounded-3 p-3 h-100">
            <div class="h3 fw-bold mb-0 <?= $onDark ? '' : 'text-pa-primary' ?>"><?= number_format((int) $s[$k], 0, ',', '.') ?></div>
            <div class="small <?= $onDark ? 'text-white-75' : 'text-secondary' ?>"><?= $l ?></div>
        </div></div>
    <?php endforeach ?>
</div>
<?php if (! $nested): ?><div class="text-center mt-4"><a href="<?= site_url('layanan-hukum/statistik-perkara') ?>" class="btn <?= $onDark ? 'btn-outline-light' : 'btn-pa-primary' ?>">Statistik lengkap</a></div><?php endif ?>
