<?php if (is_pta()): ?>
    <p class="text-secondary">Daftar panggilan ghaib diumumkan pada website masing-masing Pengadilan Agama tingkat pertama di wilayah hukum kami.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-success"><tr><th scope="col">Nama Pihak</th><th scope="col">Nomor Perkara</th><th scope="col">Tanggal Sidang</th><th scope="col">Dokumen Relaas</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= esc($r['party_name']) ?></td>
                <td><?= esc($r['case_number']) ?></td>
                <td><?= tanggal_id($r['hearing_date']) ?></td>
                <td><?php if ($r['uuid']): ?><a href="<?= site_url('berkas/' . $r['uuid']) ?>" class="btn btn-sm btn-pa-primary" target="_blank" rel="noopener">Lihat Relaas (PDF)<span class="visually-hidden"> perkara <?= esc($r['case_number']) ?></span></a><?php else: ?>-<?php endif ?></td>
            </tr>
        <?php endforeach ?>
        <?php if (! $rows): ?><tr><td colspan="4" class="text-center text-secondary">Tidak ada panggilan ghaib saat ini.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php endif ?>
