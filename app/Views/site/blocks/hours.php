<?php $h = setting('office_hours'); ?>
<?php if ($h === ''): ?><p class="text-secondary small mb-0">Jam pelayanan belum diisi (Pengaturan → Alamat & Kontak).</p><?php return; endif ?>
<ul class="list-unstyled mb-0">
    <?php foreach (preg_split('/\R/', $h) as $line): if (trim($line) === '') continue; [$d, $t] = array_pad(explode(':', $line, 2), 2, ''); ?>
        <li class="d-flex justify-content-between gap-2 py-2 border-bottom small">
            <span class="fw-semibold"><i class="bi bi-clock text-pa-secondary me-1" aria-hidden="true"></i><?= esc(trim($d)) ?></span>
            <span class="text-secondary text-end"><?= esc(trim($t)) ?></span>
        </li>
    <?php endforeach ?>
</ul>
<?php if (setting('phone')): ?><p class="small text-secondary mt-3 mb-0"><i class="bi bi-telephone me-1" aria-hidden="true"></i><?= esc(setting('phone')) ?></p><?php endif ?>
