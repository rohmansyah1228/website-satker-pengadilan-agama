<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label for="tahun" class="form-label fw-semibold small mb-1">Tahun</label>
        <select id="tahun" name="tahun" class="form-select" data-autosubmit>
            <?php foreach ($years as $y): ?><option value="<?= (int) $y ?>"<?= (int) $y === $year ? ' selected' : '' ?>><?= (int) $y ?></option><?php endforeach ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-pa-primary">Tampilkan</button></div>
</form>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle text-center">
        <caption class="small">Keadaan perkara tahun <?= $year ?></caption>
        <thead class="table-success"><tr><th scope="col" class="text-start">Bulan</th><th scope="col">Sisa Bulan Lalu</th><th scope="col">Perkara Masuk</th><th scope="col">Perkara Putus</th><th scope="col">Sisa Perkara</th></tr></thead>
        <tbody>
        <?php $t = ['received' => 0, 'decided' => 0]; foreach ($rows as $r): $t['received'] += $r['received']; $t['decided'] += $r['decided']; ?>
            <tr><th scope="row" class="text-start"><?= bulan_id((int) $r['month']) ?></th><td><?= (int) $r['carried_over'] ?></td><td><?= (int) $r['received'] ?></td><td><?= (int) $r['decided'] ?></td><td><?= (int) $r['remaining'] ?></td></tr>
        <?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="5" class="text-secondary">Data statistik tahun ini belum tersedia.</td></tr><?php else: ?>
            <tr class="fw-bold"><th scope="row" class="text-start">Jumlah</th><td>–</td><td><?= $t['received'] ?></td><td><?= $t['decided'] ?></td><td><?= (int) end($rows)['remaining'] ?></td></tr>
        <?php endif ?>
        </tbody>
    </table>
</div>
