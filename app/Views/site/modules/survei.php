<div class="row g-3 row-cols-1 row-cols-md-3 mb-4">
    <?php foreach ([['IKM', 'Indeks Kepuasan Masyarakat', setting('ikm')], ['IPKP', 'Indeks Persepsi Kualitas Pelayanan', setting('ipkp')], ['IPAK', 'Indeks Persepsi Anti Korupsi', setting('ipak')]] as [$k, $l, $v]): ?>
        <div class="col"><div class="border rounded-3 p-3 text-center h-100 border-top border-4 border-pa-secondary">
            <div class="small text-secondary"><?= $l ?> (<?= $k ?>)</div>
            <div class="display-6 fw-bold text-pa-primary"><?= $v !== '' ? esc($v) : '–' ?></div>
            <?php if ($v !== '' && ($cat = survey_category($v, $k))): ?><span class="badge survey-badge"><?= esc($cat) ?></span><?php endif ?>
        </div></div>
    <?php endforeach ?>
</div>
<?php if (setting('survey_period')): ?><p class="small text-secondary">Periode survei: <?= esc(setting('survey_period')) ?></p><?php endif ?>
<?php if (setting('survey_url')): ?><a href="<?= esc(safe_href(setting('survey_url')), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-pa-secondary fw-bold mb-4"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Isi Survei Pelayanan Publik</a><?php endif ?>
<?= view('site/modules/documents', ['groups' => $groups ?? [], 'hideEmpty' => true]) ?>
