<?php
// Popup pembuka (Pengaturan → Popup Pembuka)
if (setting('popup_active') !== '1') return;
if (setting('popup_where', 'home') !== 'all' && ($path ?? '/') !== '/') return;
$img   = setting('popup_image');
$title = setting('popup_title');
$text  = setting('popup_text');
$link  = setting('popup_link') !== '' ? link_url(setting('popup_link')) : '';
$btn   = setting('popup_button');
if ($img === '' && $title === '' && $text === '') return;
$size  = in_array(setting('popup_size'), ['sm', 'md', 'lg'], true) ? setting('popup_size') : 'md';
$key   = substr(md5($img . '|' . $title . '|' . $text . '|' . $link), 0, 10);
$ext   = $link !== '' && is_external($link);
$body  = ($title !== '' && $img === '') || $text !== '' || ($btn !== '' && $link !== '');
?>
<dialog id="sitePopup" class="site-popup site-popup-<?= $size ?>" data-key="<?= $key ?>" data-freq="<?= esc(setting('popup_frequency', 'session'), 'attr') ?>" aria-label="<?= esc($title ?: 'Informasi', 'attr') ?>">
    <button type="button" class="site-popup-close" data-popup-close data-no-speak aria-label="Tutup popup"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    <?php if ($img !== ''): ?>
        <?php if ($link !== ''): ?><a href="<?= esc(safe_href($link), 'attr') ?>"<?= $ext ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?php endif ?>
        <img src="<?= media_url($img) ?>" alt="<?= esc($title ?: 'Informasi ' . setting('site_name'), 'attr') ?>" class="site-popup-img<?= $body ? ' has-body' : '' ?>" decoding="async">
        <?php if ($link !== ''): ?></a><?php endif ?>
    <?php endif ?>
    <?php if ($body): ?>
        <div class="site-popup-body">
            <?php if ($title !== '' && $img === ''): ?><h2 class="h5 font-display fw-bold text-pa-primary mb-2"><?= esc($title) ?></h2><?php endif ?>
            <?php if ($text !== ''): ?><p class="mb-3 small"><?= nl2br(esc($text)) ?></p><?php endif ?>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <?php if ($btn !== '' && $link !== ''): ?><a href="<?= esc(safe_href($link), 'attr') ?>" class="btn btn-pa-secondary btn-sm fw-bold"<?= $ext ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= esc($btn) ?></a><?php endif ?>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-popup-close data-no-speak>Tutup</button>
            </div>
        </div>
    <?php endif ?>
</dialog>
