<?php $cats = config('SiteStructure')->postCategories; $ghaib = $data['ghaib'] ?? []; $notices = $data['notices'] ?? []; ?>
<div class="list-group list-group-flush<?= $nested ? '' : ' bg-white rounded-3 shadow-sm p-3' ?>">
    <?php foreach ($ghaib as $g): ?>
        <a href="<?= site_url('layanan-hukum/panggilan-ghaib') ?>" class="list-group-item list-group-item-action px-0 py-3">
            <span class="badge bg-pa-secondary text-dark mb-1">Relaas Panggilan Ghaib</span>
            <span class="d-block fw-semibold mb-1 text-dark">Panggilan Sidang Perkara Nomor <?= esc($g['case_number']) ?></span>
            <small class="text-secondary"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Jadwal Sidang: <?= tanggal_id($g['hearing_date']) ?></small>
        </a>
    <?php endforeach ?>
    <?php foreach ($notices as $n): ?>
        <a href="<?= site_url('berita/' . esc($n['slug'], 'url')) ?>" class="list-group-item list-group-item-action px-0 py-3">
            <span class="badge <?= $n['category'] === 'serta-merta' ? 'bg-secondary' : 'bg-pa-primary' ?> mb-1"><?= esc($cats[$n['category']] ?? 'Pengumuman') ?></span>
            <span class="d-block fw-semibold mb-1 text-dark"><?= esc($n['title']) ?></span>
            <small class="text-secondary"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= tanggal_id($n['published_at']) ?></small>
        </a>
    <?php endforeach ?>
    <?php if (! $ghaib && ! $notices): ?><p class="text-secondary small py-3 mb-0">Belum ada pengumuman.</p><?php endif ?>
</div>
