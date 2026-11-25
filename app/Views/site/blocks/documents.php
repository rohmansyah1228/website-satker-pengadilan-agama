<?php $docs = $data['docs'] ?? []; ?>
<ul class="list-group list-group-flush<?= $nested ? '' : ' bg-white rounded-3 shadow-sm p-2' ?>">
    <?php foreach ($docs as $d): $url = $d['uuid'] ? site_url('berkas/' . $d['uuid']) : link_url($d['external_url']); ?>
        <li class="list-group-item d-flex justify-content-between align-items-center gap-2 px-0 px-md-2">
            <span><i class="bi bi-file-earmark-pdf text-danger me-1" aria-hidden="true"></i><?= esc($d['title']) ?>
                <small class="d-block text-secondary"><?= esc($d['type_name']) ?> · <?= $d['month'] ? bulan_id((int) $d['month']) . ' ' : '' ?><?= (int) $d['year'] ?></small></span>
            <a href="<?= esc(safe_href($url), 'attr') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success text-nowrap">Lihat<span class="visually-hidden"> <?= esc($d['title']) ?></span></a>
        </li>
    <?php endforeach ?>
    <?php if (! $docs): ?><li class="list-group-item text-secondary small px-0">Belum ada dokumen.</li><?php endif ?>
</ul>
