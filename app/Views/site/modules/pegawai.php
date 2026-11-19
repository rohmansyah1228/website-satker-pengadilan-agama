<?php if (empty($groups)): ?><p class="text-secondary">Data pegawai belum tersedia.</p><?php endif ?>
<?php foreach ($labels as $key => $label): ?>
    <?php if (empty($groups[$key])) continue; ?>
    <h2 class="h5 fw-bold text-dark mt-4 mb-3"><?= esc($label) ?></h2>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <?php foreach ($groups[$key] as $e): ?>
            <div class="col">
                <div class="card h-100 text-center p-3 border-top border-3 border-pa-primary">
                    <?php if ($e['photo']): ?>
                        <img src="<?= media_url($e['photo']) ?>" alt="Foto <?= esc($e['name'], 'attr') ?>" class="rounded-circle mx-auto mb-3 employee-photo" loading="lazy" width="110" height="110">
                    <?php else: ?>
                        <div class="rounded-circle mx-auto mb-3 employee-photo d-flex align-items-center justify-content-center bg-pa-primary text-white fs-2" aria-hidden="true"><i class="bi bi-person"></i></div>
                    <?php endif ?>
                    <h3 class="h6 fw-bold text-pa-primary mb-1"><?= esc($e['name']) ?></h3>
                    <?php if ($e['position']): ?><p class="small text-pa-secondary fw-semibold mb-1" style="color:#b36b00 !important;"><?= esc($e['position']) ?></p><?php endif ?>
                    <?php if ($e['rank']): ?><p class="small text-secondary mb-1"><?= esc($e['rank']) ?></p><?php endif ?>
                    <?php if ($e['tmt']): ?><p class="small text-secondary mb-2">TMT: <?= tanggal_id($e['tmt']) ?></p><?php endif ?>
                    <?php if ($e['education'] || $e['career']): ?>
                        <details class="text-start small mt-auto">
                            <summary class="text-pa-primary fw-semibold">Riwayat</summary>
                            <?php if ($e['education']): ?><p class="fw-semibold mb-1 mt-2">Pendidikan</p><p class="text-secondary mb-2"><?= nl2br(esc($e['education'])) ?></p><?php endif ?>
                            <?php if ($e['career']): ?><p class="fw-semibold mb-1">Jabatan</p><p class="text-secondary mb-0"><?= nl2br(esc($e['career'])) ?></p><?php endif ?>
                        </details>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endforeach ?>
