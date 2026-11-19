<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label for="tanggal" class="form-label fw-semibold small mb-1">Tanggal sidang</label>
        <input type="date" id="tanggal" name="tanggal" value="<?= esc($date, 'attr') ?>" class="form-control" data-autosubmit>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-pa-primary">Tampilkan</button></div>
</form>
<h2 class="h5 fw-bold text-dark">Agenda Sidang <?= tanggal_id($date, true) ?></h2>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <caption class="small">Jadwal persidangan <?= tanggal_id($date) ?></caption>
        <thead class="table-success"><tr><th scope="col">No</th><th scope="col">Jam</th><th scope="col">Nomor Perkara</th><th scope="col">Agenda Sidang</th><th scope="col">Ruang Sidang</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
            <tr><td><?= $i + 1 ?></td><td><?= esc($r['hearing_time'] ?? '') ?></td><td><?= esc($r['case_number']) ?></td><td><?= esc($r['agenda']) ?></td><td><?= esc($r['room']) ?></td></tr>
        <?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="5" class="text-center text-secondary">Tidak ada jadwal persidangan pada tanggal ini.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php if (setting('sipp_url')): ?>
    <a href="<?= esc(setting('sipp_url'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-pa-primary mt-2">Buka Jadwal Lengkap di SIPP <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i></a>
<?php endif ?>
