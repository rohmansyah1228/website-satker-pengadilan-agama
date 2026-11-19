<?php $alt = $b['alt'] !== '' ? $b['alt'] : $b['caption']; $href = $b['link'] !== '' ? $b['link'] : media_url($b['src']); $int = $b['link'] === '' || \App\Libraries\PageBlocks::isInternal($b['link']); ?>
<figure class="page-block page-figure page-figure--<?= esc($b['size'], 'attr') ?> page-figure--<?= esc($b['align'], 'attr') ?> mb-4">
    <a href="<?= esc($href, 'attr') ?>"<?= $int && $b['link'] !== '' ? '' : ' target="_blank" rel="noopener noreferrer"' ?>>
        <img src="<?= media_url($b['src']) ?>" alt="<?= esc($alt, 'attr') ?>" class="img-fluid rounded border shadow-sm" loading="lazy" decoding="async">
    </a>
    <?php if ($b['caption'] !== ''): ?><figcaption class="small text-secondary mt-2"><?= esc($b['caption']) ?></figcaption><?php endif ?>
</figure>
