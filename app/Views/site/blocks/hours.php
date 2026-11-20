<?php
$s        = $b['settings'] ?? [];
$sections = [
    ['office_hours', 'Jam Pelayanan PTSP', 'bi-clock', true],
    ['break_hours', 'Istirahat', 'bi-cup-hot', ($s['show_break'] ?? 1) ? true : false],
    ['hearing_hours', 'Waktu Sidang', 'bi-bank', ($s['show_hearing'] ?? 1) ? true : false],
];
$any = false;
foreach ($sections as [$k, , , $on]) {
    $any = $any || ($on && trim(setting($k)) !== '');
}
?>
<?php if (! $any): ?><p class="<?= $onDark ? 'text-white-75' : 'text-secondary' ?> small mb-0">Jam pelayanan belum diisi (Pengaturan → Alamat & Kontak).</p><?php return; endif ?>
<div class="hours-block">
    <?php foreach ($sections as [$key, $label, $icon, $on]):
        $text = trim(setting($key));
        if (! $on || $text === '') continue; ?>
        <div class="hours-group">
            <h3 class="hours-head"><i class="bi <?= $icon ?>" aria-hidden="true"></i> <?= esc($label) ?></h3>
            <ul class="list-unstyled mb-0">
                <?php foreach (preg_split('/\R/', $text) as $line): if (trim($line) === '') continue; [$d, $t] = array_pad(explode(':', $line, 2), 2, ''); ?>
                    <li class="d-flex justify-content-between gap-2 py-2 small">
                        <span class="fw-semibold"><?= esc(trim($t) === '' ? '' : trim($d)) ?></span>
                        <span class="text-end<?= $onDark ? '' : ' text-secondary' ?>"><?= esc(trim($t) === '' ? trim($d) : trim($t)) ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endforeach ?>
</div>
<?php if (setting('phone')): ?><p class="small <?= $onDark ? 'text-white-75' : 'text-secondary' ?> mt-3 mb-0"><i class="bi bi-telephone me-1" aria-hidden="true"></i><?= esc(setting('phone')) ?></p><?php endif ?>
