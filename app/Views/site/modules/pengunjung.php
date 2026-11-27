<div class="row g-3 row-cols-2 row-cols-md-4 mb-4">
    <?php foreach (['today' => 'Hari Ini', 'month' => 'Bulan Ini', 'year' => 'Tahun Ini', 'total' => 'Total'] as $k => $l): ?>
        <div class="col"><div class="border rounded-3 p-3 text-center h-100">
            <div class="small text-secondary">Pengunjung <?= $l ?></div>
            <div class="h3 fw-bold text-pa-primary mb-0"><?= number_format($$k ?? 0, 0, ',', '.') ?></div>
        </div></div>
    <?php endforeach ?>
</div>
<h2 class="h5 fw-bold text-dark">Kunjungan 12 Bulan Terakhir</h2>
<div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
        <thead class="table-success"><tr><th scope="col" class="text-start">Bulan</th><th scope="col">Pengunjung Unik Harian</th><th scope="col">Halaman Dilihat</th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($monthly, true) as $ym => $m): ?>
            <tr><th scope="row" class="text-start"><?= bulan_id((int) substr($ym, 5, 2)) ?> <?= substr($ym, 0, 4) ?></th><td><?= number_format($m['visitors'], 0, ',', '.') ?></td><td><?= number_format($m['pageviews'], 0, ',', '.') ?></td></tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php $d = $details ?? ['days' => 0, 'total' => 0, 'groups' => []]; if ($d['days'] > 0 && setting('visitor_detail_public', '1') === '1'): ?>
<h2 class="h5 fw-bold text-dark mt-4">Rincian Pengunjung <?= $d['days'] === 1 ? 'Hari Ini' : $d['days'] . ' Hari Terakhir' ?></h2>
<?= view('partials/visitor_details', ['d' => $d, 'kinds' => ['perangkat', 'browser', 'os']]) ?>
<?php endif ?>
