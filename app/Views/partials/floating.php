<?php
// Tombol melayang: Akses CCTV Online (kiri atas) & WhatsApp PTSP (kiri bawah) — diatur di Pengaturan
$cctv = setting('cctv_url');
$wa   = preg_replace('/\D+/', '', setting('whatsapp'));
if ($wa !== '') {
    $wa = str_starts_with($wa, '0') ? '62' . substr($wa, 1) : (str_starts_with($wa, '8') ? '62' . $wa : $wa);
}
$waText = setting('whatsapp_text', 'Assalamualaikum, saya ingin bertanya tentang layanan ' . setting('site_name', 'pengadilan') . '.');
?>
<?php if ($cctv !== ''): ?>
    <a href="<?= esc($cctv, 'attr') ?>" target="_blank" rel="noopener noreferrer" class="float-cctv" aria-label="Akses CCTV Online <?= esc(setting('site_name'), 'attr') ?> (membuka tab baru)">
        <?php if (setting('cctv_image')): ?>
            <img src="<?= media_url(setting('cctv_image')) ?>" alt="" class="float-cctv-img">
        <?php else: ?>
            <span class="float-cctv-icon" aria-hidden="true"><i class="bi bi-camera-video-fill"></i><span class="float-cctv-rec"></span></span>
        <?php endif ?>
        <span class="float-cctv-text" aria-hidden="true"><strong>CCTV</strong> Online</span>
    </a>
<?php endif ?>
<?php if ($wa !== ''): ?>
    <a href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode($waText) ?>" target="_blank" rel="noopener noreferrer" class="float-wa" aria-label="Hubungi WhatsApp PTSP <?= esc(setting('whatsapp'), 'attr') ?> (membuka tab baru)">
        <i class="bi bi-whatsapp" aria-hidden="true"></i><span class="float-wa-text" aria-hidden="true">Chat WhatsApp</span>
    </a>
<?php endif ?>
