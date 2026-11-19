<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-success"><tr><th scope="col">Tahun</th><th scope="col"><?= esc($who) ?></th><th scope="col">Jenis Pelanggaran</th><th scope="col">Deskripsi Singkat</th><th scope="col">Tindak Lanjut / Status</th><th scope="col">Hukuman Disiplin</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= (int) $r['year'] ?></td><td><?= esc($r['initials']) ?></td><td><?= esc($r['violation']) ?></td><td><?= esc($r['description']) ?></td><td><?= esc($r['status']) ?></td><td><?= esc($r['penalty'] ?: '-') ?></td></tr>
        <?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="6" class="text-center text-secondary">Nihil.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
