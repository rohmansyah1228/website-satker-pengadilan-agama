<div class="row g-3 row-cols-1 row-cols-md-3 mb-4">
    <?php foreach ([['IKM', 'Indeks Kepuasan Masyarakat', setting('ikm')], ['IPKP', 'Indeks Persepsi Kualitas Pelayanan', setting('ipkp')], ['IPAK', 'Indeks Persepsi Anti Korupsi', setting('ipak')]] as [$k, $l, $v]): ?>
        <div class="col"><div class="border rounded-3 p-3 text-center h-100 border-top border-4 border-pa-secondary">
            <div class="small text-secondary"><?= $l ?> (<?= $k ?>)</div>
            <div class="display-6 fw-bold text-pa-primary"><?= $v !== '' ? esc($v) : '–' ?></div>
        </div></div>
    <?php endforeach ?>
</div>
<?php if (setting('survey_period')): ?><p class="small text-secondary">Periode survei: <?= esc(setting('survey_period')) ?></p><?php endif ?>
<?php if (setting('survey_url')): ?><a href="<?= esc(setting('survey_url'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-pa-secondary mb-4"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Isi Kuesioner Survei</a><?php endif ?>
<?= view('site/modules/documents', ['groups' => $groups ?? [], 'hideEmpty' => true]) ?>
