<?php
/**
 * Rincian pengunjung (jumlah per kelompok).
 * @var array $d      hasil VisitorStats::details()
 * @var array $kinds  kelompok yang ditampilkan
 */
$labels = ['perangkat' => ['Jenis Perangkat', 'bi-phone'], 'browser' => ['Browser', 'bi-globe2'], 'os' => ['Sistem Operasi', 'bi-windows'],
    'provinsi' => ['Asal Provinsi (perkiraan)', 'bi-geo-alt'], 'kota' => ['Asal Kota/Kabupaten (perkiraan)', 'bi-pin-map']];
$geo = array_intersect($kinds, ['provinsi', 'kota']) !== [];
?>
<?php if (! $d['total']): ?>
    <p class="text-secondary">Belum ada data untuk periode ini.</p>
<?php else: ?>
    <p class="small text-secondary mb-3">Dari <?= number_format($d['total'], 0, ',', '.') ?> pengunjung unik harian. Hanya jumlah yang disimpan, bukan data per orang.</p>
    <div class="row g-4">
    <?php foreach ($kinds as $k): [$label, $icon] = $labels[$k];
        $list = $d['groups'][$k] ?? []; if (! $list) continue;
        $sum  = array_sum(array_column($list, 'visitors')); $top = array_slice($list, 0, $limit ?? 10); $rest = $sum - array_sum(array_column($top, 'visitors')); ?>
        <div class="col-md-6">
            <h3 class="h6 fw-bold"><i class="bi <?= $icon ?> text-pa-primary" aria-hidden="true"></i> <?= esc($label) ?></h3>
            <ul class="list-unstyled small mb-0">
            <?php foreach ($top as $r): $pct = $sum ? round($r['visitors'] / $sum * 100, 1) : 0; ?>
                <li class="mb-2">
                    <div class="d-flex justify-content-between gap-2"><span><?= esc($r['name']) ?></span><span class="text-secondary text-nowrap"><?= number_format($r['visitors'], 0, ',', '.') ?> · <?= str_replace('.', ',', (string) $pct) ?>%</span></div>
                    <div class="progress" style="height:.45rem" role="progressbar" aria-label="<?= esc($r['name'], 'attr') ?>" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-pa-primary" style="width:<?= $pct ?>%"></div></div>
                </li>
            <?php endforeach ?>
            <?php if ($rest > 0): ?><li class="text-secondary">Lainnya: <?= number_format($rest, 0, ',', '.') ?></li><?php endif ?>
            </ul>
        </div>
    <?php endforeach ?>
    </div>
    <?php if ($geo): ?>
        <p class="small text-secondary mt-3 mb-0">Asal daerah adalah perkiraan dari alamat IP (pengguna jaringan seluler sering tercatat di kota lain). <a href="https://db-ip.com" target="_blank" rel="noopener">IP Geolocation by DB-IP</a></p>
    <?php endif ?>
<?php endif ?>
