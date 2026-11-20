<?php
$vals = array_filter([['bi-emoji-smile', 'Indeks Kepuasan Masyarakat (IKM)', setting('ikm')], ['bi-award', 'Indeks Persepsi Kualitas Pelayanan (IPKP)', setting('ipkp')], ['bi-shield-check', 'Indeks Persepsi Anti Korupsi (IPAK)', setting('ipak')]], static fn ($v) => $v[2] !== '');
?>
<?php if (! $vals && ! setting('survey_url')): ?><p class="<?= $onDark ? 'text-white-75 text-center' : 'text-secondary' ?> small mb-0">Nilai survei belum diisi.</p><?php return; endif ?>
<?php if (setting('survey_period')): ?><p class="small <?= $onDark ? 'text-white-75 text-center' : 'text-secondary' ?> mb-3">Periode <?= esc(setting('survey_period')) ?></p><?php endif ?>
<?php if ($vals): ?><div class="row g-4 row-cols-1 <?= $nested ? '' : 'row-cols-md-3' ?> mb-2 justify-content-center">
    <?php foreach ($vals as [$ic, $lbl, $val]): ?>
        <div class="col">
            <div class="<?= $onDark ? 'bg-white bg-opacity-10' : 'bg-light' ?> p-4 rounded-3 text-center border-top border-4 border-pa-secondary h-100">
                <i class="bi <?= $ic ?> display-4 text-pa-secondary" aria-hidden="true"></i>
                <p class="font-display fw-bold fs-6 mt-3 mb-0"><?= $lbl ?></p>
                <p class="display-5 fw-bold my-2<?= $onDark ? '' : ' text-pa-primary' ?>"><?= esc($val) ?></p>
            </div>
        </div>
    <?php endforeach ?>
</div><?php endif ?>
<?php if (setting('survey_url')): ?>
<div class="text-center pt-3">
    <a href="<?= esc(setting('survey_url'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn <?= $onDark ? 'btn-outline-light' : 'btn-outline-success' ?><?= $nested ? '' : ' btn-lg' ?> px-4"><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Isi Survei Pelayanan<span class="visually-hidden"> (membuka tab baru)</span></a>
</div>
<?php endif ?>
