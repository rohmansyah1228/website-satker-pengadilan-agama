<?php $s = $b['settings']; $u = ! empty($s['url']) ? link_url($s['url']) : null; $tag = 'h' . ($level ?? 3); ?>
<div class="bg-white p-4 rounded-3 shadow-sm h-100 text-center d-flex flex-column card-info">
    <?php if (! empty($s['icon'])): ?><i class="bi <?= esc($s['icon'], 'attr') ?> display-5 text-pa-primary" aria-hidden="true"></i><?php endif ?>
    <?php if ($b['title']): ?><<?= $tag ?> class="font-display fw-bold fs-5 text-pa-primary mt-3"><?= esc($b['title']) ?></<?= $tag ?>><?php endif ?>
    <?php if (! empty($s['text'])): ?><p class="text-secondary small mb-3"><?= nl2br(esc($s['text'])) ?></p><?php endif ?>
    <?php if ($u && $u !== '#' && ! empty($s['button_text'])): ?>
        <a href="<?= esc($u, 'attr') ?>" class="btn btn-sm btn-pa-primary mt-auto align-self-center"<?= is_external($u) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= esc($s['button_text']) ?><span class="visually-hidden"> — <?= esc($b['title']) ?></span></a>
    <?php endif ?>
</div>
