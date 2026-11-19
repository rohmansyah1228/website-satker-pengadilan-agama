<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-success"><tr><th scope="col">Nama Pejabat</th><th scope="col">Jabatan</th><th scope="col">Tahun Lapor</th><th scope="col">Ikhtisar Kekayaan</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= esc($r['name']) ?></td><td><?= esc($r['position']) ?></td><td><?= (int) $r['year'] ?></td>
                <td><?php if ($r['uuid']): ?><a href="<?= site_url('berkas/' . $r['uuid']) ?>" class="btn btn-sm btn-pa-primary" target="_blank" rel="noopener">Lihat Ikhtisar (PDF)<span class="visually-hidden"> <?= esc($r['name']) ?> <?= (int) $r['year'] ?></span></a><?php else: ?>-<?php endif ?></td></tr>
        <?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="4" class="text-center text-secondary">Data belum tersedia.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
