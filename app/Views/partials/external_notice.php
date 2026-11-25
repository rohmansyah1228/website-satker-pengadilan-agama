<?php /** @var array $n */ $src = $n['source'] === 'ma' ? ['Mahkamah Agung', 'bg-pa-primary'] : [$n['label'] ?? 'Badilag', 'bg-pa-secondary text-dark']; ?>
<a href="<?= esc(safe_href($n['url']), 'attr') ?>" target="_blank" rel="noopener" class="list-group-item list-group-item-action px-0 py-3 ext-notice">
    <span class="badge <?= $src[1] ?> mb-1"><?= esc($src[0]) ?></span>
    <span class="d-block fw-semibold mb-1 text-dark"><?= esc($n['title']) ?> <i class="bi bi-box-arrow-up-right small text-secondary" aria-hidden="true"></i><span class="visually-hidden">(membuka situs <?= esc($src[0]) ?> di tab baru)</span></span>
    <?php if (! empty($n['date'])): ?><small class="text-secondary"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= tanggal_id($n['date']) ?></small><?php endif ?>
</a>
