<?php
/**
 * Blok "Ucapan & Belasungkawa": dua kolom berdampingan, masing-masing
 * memutar banner terbaru (Bootstrap carousel). Data dari HomeBlocks::data().
 * @var array $data  ['selamat'=>[], 'belasungkawa'=>[]]
 * @var array $b     blok + settings
 */
$show = $b['settings']['show'] ?? 'both';
$cols = [];
if ($show !== 'belasungkawa') {
    $cols['selamat'] = ['Ucapan Selamat', $data['selamat'] ?? []];
}
if ($show !== 'selamat') {
    $cols['belasungkawa'] = ['Belasungkawa', $data['belasungkawa'] ?? []];
}
// buang kolom yang kosong; bila semua kosong, jangan render apa-apa
$cols = array_filter($cols, static fn ($c) => ! empty($c[1]));
if (! $cols) {
    echo '<p class="text-secondary small mb-0">Belum ada banner untuk ditampilkan.</p>';

    return;
}
// judul kolom di dalam blok hanya bila 2 kolom tampil, atau blok tidak memiliki judul sendiri
$showHead = count($cols) > 1 || trim((string) ($b['title'] ?? '')) === '';
$colCls = count($cols) > 1 ? 'col-12 col-md-6' : 'col-12';

// gaya ringkas, cukup ditulis sekali per halaman
static $cssDone = false;
if (! $cssDone):
    $cssDone = true; ?>
<style>
.greet-col .greet-head{font-family:var(--font-display,inherit);font-weight:700;color:var(--pa-primary,#0a5c36);text-transform:uppercase;letter-spacing:.02em;font-size:1.05rem;margin-bottom:.5rem}
.greet-inner{max-width:420px;margin-inline:auto}
.greet-frame{aspect-ratio:1/1;border-radius:.5rem;overflow:hidden;display:flex;align-items:center;justify-content:center}
.greet-frame img{width:100%;height:100%;object-fit:contain}
.greet-dots{display:flex;gap:.35rem;margin-bottom:.5rem}
.greet-dots button{width:14px;height:14px;padding:0;border:0;border-radius:3px;background:#cfd4da;opacity:1;text-indent:-9999px;transition:background .2s}
.greet-dots button.active{background:var(--pa-primary,#0a5c36)}
</style>
<?php endif ?>
<div class="row g-4">
    <?php foreach ($cols as $kind => [$heading, $items]): $cid = 'greet-' . $kind . '-' . $b['id']; ?>
        <div class="<?= $colCls ?> greet-col">
          <div class="greet-inner">
            <?php if ($showHead): ?><div class="greet-head"><?= esc($heading) ?></div><?php endif ?>
            <div id="<?= $cid ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000"
                 aria-roledescription="carousel" aria-label="<?= esc($heading, 'attr') ?>">
                <?php if (count($items) > 1): ?>
                    <div class="carousel-indicators greet-dots">
                        <?php foreach ($items as $i => $g): ?>
                            <button type="button" data-bs-target="#<?= $cid ?>" data-bs-slide-to="<?= $i ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="<?= esc($heading, 'attr') ?> <?= $i + 1 ?>"><?= $i + 1 ?></button>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
                <div class="carousel-inner">
                    <?php foreach ($items as $i => $g): ?>
                        <?php
                        $alt = $g['image_alt'] ?: ($g['title'] ?: $heading);
                        $u   = link_url($g['link_url'] ?? '');
                        $img = '<div class="greet-frame"><img src="' . esc(media_url($g['image']), 'attr') . '" alt="' . esc($alt, 'attr') . '" '
                             . ($i === 0 ? 'loading="eager"' : 'loading="lazy"') . ' decoding="async"></div>';
                        ?>
                        <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                            <?php if ($u !== '#'): ?>
                                <a href="<?= esc(safe_href($u), 'attr') ?>"<?= is_external($u) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $img ?></a>
                            <?php else: ?>
                                <?= $img ?>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
                <?php if (count($items) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#<?= $cid ?>" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true" style="filter:invert(1)"></span><span class="visually-hidden">Sebelumnya</span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#<?= $cid ?>" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true" style="filter:invert(1)"></span><span class="visually-hidden">Berikutnya</span></button>
                <?php endif ?>
            </div>
          </div>
        </div>
    <?php endforeach ?>
</div>
