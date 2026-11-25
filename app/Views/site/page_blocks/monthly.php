<?php
use App\Libraries\PageBlocks;

/** Arsip Bulanan: satu tahun, beberapa baris (mis. SERAPAN 01, SERAPAN 04) × 12 bulan. */
$total = count($b['rows']) * 12;
$done  = 0;
foreach ($b['rows'] as $r) {
    $done += count(array_filter($r['links'], static fn ($u) => $u !== ''));
}
$open  = $b['open'] ?? 'popup';
$label = $b['heading'] !== '' ? $b['heading'] : 'Arsip Terjadwal ' . $b['year'];
?>
<section class="page-block monthly-archive mb-4" aria-label="<?= esc($label . ' ' . $b['year'], 'attr') ?>">
    <header class="monthly-archive__head">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="monthly-archive__year"><?= (int) $b['year'] ?></span>
            <span class="monthly-archive__badge"><?= esc($label) ?></span>
        </div>
        <span class="monthly-archive__count"><?= $done ?> / <?= $total ?> Bulan Terpublikasi</span>
    </header>
    <div class="monthly-archive__body">
        <?php foreach ($b['rows'] as $r): ?>
            <?php if ($r['label'] !== ''): ?><h2 class="monthly-archive__row"><?= esc($r['label']) ?></h2><?php endif ?>
            <ul class="monthly-archive__grid list-unstyled">
                <?php foreach (PageBlocks::MONTHS as $m => $name):
                    $u = (string) ($r['links'][$m - 1] ?? '');
                    $title = trim($r['label'] . ' ' . $name . ' ' . $b['year']); ?>
                    <li>
                        <?php if ($u !== ''):
                            $href = PageBlocks::href($u);
                            $pv   = $open === 'popup' ? (PageBlocks::previewUrl($href) ?? $href) : null; ?>
                            <a class="monthly-card is-done" href="<?= esc(safe_href($href), 'attr') ?>"<?= $pv ? ' data-preview="' . esc($pv, 'attr') . '"' : '' ?><?= $open === 'same' ? '' : ' target="_blank" rel="noopener noreferrer"' ?> data-preview-title="<?= esc($title, 'attr') ?>" aria-label="Lihat <?= esc($title, 'attr') ?>">
                                <span class="monthly-card__name"><?= $name ?></span>
                                <span class="monthly-card__state"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Lihat Dokumen</span>
                            </a>
                        <?php else: ?>
                            <div class="monthly-card is-empty">
                                <span class="monthly-card__name"><?= $name ?></span>
                                <span class="monthly-card__state">Menunggu Publikasi</span>
                            </div>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endforeach ?>
    </div>
</section>
