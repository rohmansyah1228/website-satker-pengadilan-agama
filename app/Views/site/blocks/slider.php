<?php $sliders = $data['sliders'] ?? []; if (! $sliders) return; $cid = 'hero-carousel'; ?>
<!-- Hero Slider Ekonomis (maks. 5 slide — Bab III huruf C) -->
<section id="<?= $cid ?>" class="carousel slide" data-bs-ride="carousel" aria-roledescription="carousel" aria-label="Informasi utama">
    <?php if (count($sliders) > 1): ?>
    <div class="carousel-indicators">
        <?php foreach ($sliders as $i => $s): ?>
            <button type="button" data-bs-target="#<?= $cid ?>" data-bs-slide-to="<?= $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endforeach ?>
    </div>
    <?php endif ?>
    <div class="carousel-inner">
        <?php foreach ($sliders as $i => $s): ?>
            <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                <?php if ($s['image']): ?>
                    <img src="<?= media_url($s['image']) ?>" alt="<?= esc($s['image_alt'] ?: $s['title'], 'attr') ?>" class="hero-img"
                         <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" width="1600" height="700">
                <?php endif ?>
                <div class="container-lg py-5 d-flex flex-column justify-content-center text-white hero-content" style="min-height: 55vh;">
                    <?php if ($s['badge']): ?><span class="badge bg-pa-secondary text-dark fw-bold align-self-start mb-3 px-3 py-2 text-wrap text-start"><?= esc($s['badge']) ?></span><?php endif ?>
                    <?php if ($i === 0): ?>
                        <h1 class="font-display display-5 fw-bold"><?= esc($s['title']) ?></h1>
                    <?php else: ?>
                        <h2 class="font-display display-5 fw-bold"><?= esc($s['title']) ?></h2>
                    <?php endif ?>
                    <?php if ($s['subtitle']): ?><p class="mt-3 fs-5 text-white-75" style="max-width: 42rem;"><?= esc($s['subtitle']) ?></p><?php endif ?>
                    <div class="mt-4 d-flex flex-wrap gap-3">
                        <?php foreach ([['button_text', 'button_url', 'btn-pa-secondary'], ['button2_text', 'button2_url', 'btn-outline-light']] as [$tk, $uk, $cls]): ?>
                            <?php $u = link_url($s[$uk]); if (! $s[$tk] || $u === '#') continue; ?>
                            <a href="<?= esc($u, 'attr') ?>" class="btn <?= $cls ?> btn-lg px-4"<?= is_external($u) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= esc($s[$tk]) ?><?= $tk === 'button_text' ? ' <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>' : '' ?></a>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <?php if (count($sliders) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#<?= $cid ?>" data-bs-slide="prev" style="z-index: 11;"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Slide Sebelumnya</span></button>
    <button class="carousel-control-next" type="button" data-bs-target="#<?= $cid ?>" data-bs-slide="next" style="z-index: 11;"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Slide Berikutnya</span></button>
    <?php endif ?>
</section>
