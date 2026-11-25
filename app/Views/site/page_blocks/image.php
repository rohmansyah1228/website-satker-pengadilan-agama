<?php $alt = $b['alt'] !== '' ? $b['alt'] : $b['caption']; $href = $b['link'] !== '' ? \App\Libraries\PageBlocks::href($b['link']) : media_url($b['src']); $int = $b['link'] === '' || \App\Libraries\PageBlocks::isInternal($b['link']); ?>
<figure class="page-block page-figure page-figure--<?= esc($b['size'], 'attr') ?> page-figure--<?= esc($b['align'], 'attr') ?> mb-4">
    <a href="<?= esc(safe_href($href), 'attr') ?>"<?= $int && $b['link'] !== '' ? '' : ' target="_blank" rel="noopener noreferrer"' ?>>
        <span class="img-zoom rounded border shadow-sm"><img src="<?= media_url($b['src']) ?>" alt="<?= esc($alt, 'attr') ?>" class="img-fluid" loading="lazy" decoding="async"></span>
    </a>
    <?php if ($b['caption'] !== ''): ?><figcaption class="small text-secondary mt-2"><?= esc($b['caption']) ?></figcaption><?php endif ?>
</figure>
