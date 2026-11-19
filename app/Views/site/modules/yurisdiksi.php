<?php $unit = is_pta() ? 'Kabupaten/Kota' : 'Kecamatan'; ?>
<h2 class="h5 fw-bold text-dark mb-3">Daftar <?= $unit ?> dalam Wilayah Hukum</h2>
<?php if ($items): ?>
    <ol class="list-group list-group-numbered mb-4">
        <?php foreach ($items as $i): ?><li class="list-group-item"><?= esc($i['name']) ?></li><?php endforeach ?>
    </ol>
<?php else: ?>
    <p class="text-secondary">Daftar wilayah belum diisi.</p>
<?php endif ?>
