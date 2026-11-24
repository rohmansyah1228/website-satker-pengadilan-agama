<?php if (is_pta()): ?>
    <p class="text-secondary">Daftar panggilan ghaib diumumkan pada website masing-masing Pengadilan Agama tingkat pertama di wilayah hukum kami.</p>
<?php else: $putus = $putus ?? []; ?>
<?php $relaas = static function (array $r): string {
    return $r['uuid']
        ? '<a href="' . site_url('berkas/' . $r['uuid']) . '" class="btn btn-sm btn-pa-primary" target="_blank" rel="noopener">Lihat Relaas (PDF)<span class="visually-hidden"> perkara ' . esc($r['case_number']) . '</span></a>'
        : '-';
}; ?>
<?php if ($putus): ?><h3 class="h5 fw-bold mt-2 mb-3">Panggilan Ghaib</h3><?php endif ?>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <caption class="visually-hidden">Daftar panggilan ghaib perkara yang belum diputus</caption>
        <thead class="table-success"><tr><th scope="col">Nama Pihak</th><th scope="col">Nomor Perkara</th><th scope="col">Tanggal Sidang</th><th scope="col">Dokumen Relaas</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= esc($r['party_name']) ?></td>
                <td><?= esc($r['case_number']) ?></td>
                <td><?= tanggal_id($r['hearing_date']) ?></td>
                <td><?= $relaas($r) ?></td>
            </tr>
        <?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="4" class="text-center text-secondary">Tidak ada panggilan ghaib saat ini.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php if ($putus): ?>
<h3 class="h5 fw-bold mt-4 mb-1">Perkara Ghaib yang Sudah Putus</h3>
<p class="small text-secondary mb-3">Perkara di bawah ini sudah diputus sehingga tidak ada lagi panggilan sidang. Ditampilkan selama 1 tahun sejak tanggal putus.</p>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <caption class="visually-hidden">Daftar perkara ghaib yang sudah diputus</caption>
        <thead class="table-light"><tr><th scope="col">Nama Pihak</th><th scope="col">Nomor Perkara</th><th scope="col">Tanggal Putus</th><th scope="col">Dokumen Relaas</th></tr></thead>
        <tbody>
        <?php foreach ($putus as $r): ?>
            <tr>
                <td><?= esc($r['party_name']) ?></td>
                <td><?= esc($r['case_number']) ?></td>
                <td><?= tanggal_id($r['decided_at']) ?></td>
                <td><?= $relaas($r) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>
<?php endif ?>
