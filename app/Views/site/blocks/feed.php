<?php $items = $data['items'] ?? []; ?>
<div class="list-group list-group-flush<?= $nested ? '' : ' bg-white rounded-3 shadow-sm p-3' ?>">
    <?php foreach ($items as $n): ?><?= view('partials/external_notice', ['n' => $n]) ?><?php endforeach ?>
    <?php if (! $items): ?><p class="text-secondary small py-3 mb-0">Pengumuman belum dapat dimuat. Lihat langsung di situs <a href="https://badilag.mahkamahagung.go.id/pengumuman-elektronik" target="_blank" rel="noopener">Badilag</a> dan <a href="https://www.mahkamahagung.go.id/id/pengumuman" target="_blank" rel="noopener">Mahkamah Agung</a>.</p><?php endif ?>
    <a href="<?= site_url('pengumuman-badilag-ma') ?>" class="list-group-item list-group-item-action px-0 py-2 small fw-semibold text-pa-primary border-0">Semua pengumuman Badilag &amp; MA <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
</div>
