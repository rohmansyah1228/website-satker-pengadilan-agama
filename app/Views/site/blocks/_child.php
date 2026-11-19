<?php
/**
 * Kotak tampilan di kolom utama / sidebar, atau sub-tampilan di dalam "Baris Kolom".
 * @var array $c @var int $level
 */
$anchor = $anchor ?? false;
$inSide = $inSide ?? false;
$more = [
    'announcements' => ['berita/kategori/pengumuman', 'Semua'],
    'news'          => [! empty($c['settings']['category']) ? 'berita/kategori/' . $c['settings']['category'] : 'berita', 'Indeks Berita'],
    'hearings'      => ['layanan-hukum/jadwal-sidang', 'Jadwal lengkap'],
    'stats'         => ['layanan-hukum/statistik-perkara', 'Selengkapnya'],
    'survey'        => ['layanan-publik/survei', 'Laporan survei'],
][$c['type']] ?? null;
$accent = ['primary' => ' border-top border-4 border-pa-primary', 'secondary' => ' border-top border-4 border-pa-secondary', 'none' => ''][$c['accent']] ?? '';
$plain  = in_array($c['type'], ['card', 'row'], true) || ($c['type'] === 'image' && $c['accent'] === 'none');
$tag    = 'h' . min(6, $level);
$id     = $anchor ? ' id="blok-' . $c['id'] . '"' : '';
?>
<?php if ($c['type'] === 'row'): ?>
    <section<?= $id ?> class="home-row"<?= $c['title'] ? ' aria-label="' . esc($c['title'], 'attr') . '"' : '' ?>>
        <?php if ($c['title']): ?><<?= $tag ?> class="font-display fw-bold h4 text-pa-primary mb-3"><?= esc($c['title']) ?></<?= $tag ?>><?php endif ?>
        <div class="row g-4">
            <?php foreach ($c['children'] ?? [] as $cc): $w = (int) $cc['width'] ?: 12; ?>
                <div class="col-12<?= $w <= 4 ? ' col-md-6' : '' ?> col-lg-<?= $w ?>">
                    <?= view('site/blocks/_child', ['c' => $cc, 'level' => $c['title'] ? $level + 1 : $level, 'anchor' => false, 'inSide' => false]) ?>
                </div>
            <?php endforeach ?>
        </div>
    </section>
<?php else: ?>
<?php $fill = $inSide ? '' : ' h-100'; // di sidebar: tinggi mengikuti isi ?>
<div<?= $id ?> class="<?= $plain ? trim($fill) : 'bg-white p-4 rounded-3 shadow-sm' . $fill . $accent ?> text-dark home-widget">
    <?php if ($c['title'] && $c['type'] !== 'card'): ?>
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <<?= $tag ?> class="font-display fw-bold h5 text-pa-primary mb-0"><?= esc($c['title']) ?></<?= $tag ?>>
            <?php if ($more): ?><a href="<?= site_url($more[0]) ?>" class="small text-pa-primary fw-semibold text-decoration-none text-nowrap"><?= $more[1] ?> &rarr;<span class="visually-hidden"> <?= esc($c['title']) ?></span></a><?php endif ?>
        </div>
    <?php endif ?>
    <?= view('site/blocks/' . $c['type'], ['b' => $c, 'data' => $c['data'] ?? [], 'nested' => true, 'onDark' => false, 'level' => $level, 'inSide' => $inSide]) ?>
</div>
<?php endif ?>
