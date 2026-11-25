<?php $s = $b['settings']; if (empty($s['image'])) return; $u = ! empty($s['url']) ? link_url($s['url']) : null; ?>
<?php $img = '<span class="img-zoom d-block rounded-3 shadow-sm"><img src="' . media_url($s['image']) . '" alt="' . esc($s['image_alt'] ?? '', 'attr') . '" class="img-fluid w-100" loading="lazy" decoding="async"></span>'; ?>
<figure class="mb-0 text-center">
    <?php if ($u && $u !== '#'): ?>
        <a href="<?= esc($u, 'attr') ?>"<?= is_external($u) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $img ?></a>
    <?php else: ?>
        <?= $img ?>
    <?php endif ?>
    <?php if (! empty($s['caption']) || ($b['title'] && ($nested ?? false) === false)): ?>
        <figcaption class="mt-2 small">
            <?php foreach (preg_split('/\R/', (string) ($s['caption'] ?? '')) as $i => $line): if (trim($line) === '') continue; ?>
                <span class="d-block<?= $i === 0 ? ' fw-bold text-pa-primary' : ' text-secondary' ?>"><?= esc($line) ?></span>
            <?php endforeach ?>
        </figcaption>
    <?php endif ?>
</figure>
