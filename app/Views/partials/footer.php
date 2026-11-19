<?php
$siteName = setting('site_name', 'Pengadilan Agama');
$logo     = setting('logo') ? media_url(setting('logo')) : base_url('assets/img/logo-default.svg');
$addr     = implode(', ', array_filter([setting('address_street'), setting('address_village'), setting('address_district'), setting('address_city'), setting('address_province')]));
?>
<?php /* Indeks survei kini menjadi blok "Indeks Survei Pelayanan" di Tampilan Beranda */ ?>
<footer class="bg-dark text-white pt-5 pb-4">
    <div class="container-lg">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= esc($logo, 'attr') ?>" alt="Logo Mahkamah Agung RI" class="bg-white p-1 rounded-circle" width="48" height="48" style="object-fit: contain;" loading="lazy">
                    <div>
                        <h2 class="font-display fw-bold fs-5 mb-0 text-white"><?= esc($siteName) ?></h2>
                        <small class="text-white-50"><?= esc(setting('site_tagline', 'Mahkamah Agung Republik Indonesia')) ?></small>
                    </div>
                </div>
                <p class="text-white-50 small mb-2"><?= esc($addr) ?><?= setting('postal_code') ? ' ' . esc(setting('postal_code')) : '' ?>.<br>
                    <?php if (setting('phone')): ?>Telepon: <?= esc(setting('phone')) ?><?php endif ?>
                    <?php if (setting('email')): ?> | Email: <a class="text-white-50" href="mailto:<?= esc(setting('email'), 'attr') ?>"><?= esc(setting('email')) ?></a><?php endif ?></p>
                <?php if (setting('office_hours')): ?><p class="text-white-50 small mb-0"><?= nl2br(esc(setting('office_hours'))) ?></p><?php endif ?>
            </div>
            <div class="col-md-6 col-lg-4">
                <h2 class="font-display fw-bold fs-6 text-pa-secondary mb-3">Tautan Ekosistem Peradilan</h2>
                <ul class="list-unstyled small d-grid gap-2">
                    <li><a href="https://www.mahkamahagung.go.id" target="_blank" rel="noopener noreferrer" class="text-white-50 text-decoration-none">Mahkamah Agung RI</a></li>
                    <li><a href="https://badilag.mahkamahagung.go.id" target="_blank" rel="noopener noreferrer" class="text-white-50 text-decoration-none">Ditjen Badan Peradilan Agama</a></li>
                    <li><a href="https://jdih.mahkamahagung.go.id" target="_blank" rel="noopener noreferrer" class="text-white-50 text-decoration-none">JDIH Mahkamah Agung RI</a></li>
                    <li><a href="<?= esc(setting('siwas_url', 'https://siwas.mahkamahagung.go.id'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="text-white-50 text-decoration-none">SIWAS Mahkamah Agung RI</a></li>
                </ul>
            </div>
            <div class="col-md-6 col-lg-3">
                <h2 class="font-display fw-bold fs-6 text-pa-secondary mb-3">Statistik Kunjungan Website</h2>
                <ul class="list-unstyled small text-white-50 d-grid gap-2 bg-white bg-opacity-10 p-3 rounded">
                    <li class="d-flex justify-content-between"><span>Pengunjung Hari Ini:</span> <strong class="text-white"><?= number_format($visitors['today'] ?? 0, 0, ',', '.') ?></strong></li>
                    <li class="d-flex justify-content-between"><span>Bulan Ini:</span> <strong class="text-white"><?= number_format($visitors['month'] ?? 0, 0, ',', '.') ?></strong></li>
                    <li class="d-flex justify-content-between"><span>Total Kunjungan:</span> <strong class="text-pa-secondary"><?= number_format($visitors['total'] ?? 0, 0, ',', '.') ?></strong></li>
                </ul>
                <a href="<?= site_url('layanan-publik/statistik-pengunjung') ?>" class="small text-white-50">Statistik lengkap &rarr;</a>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="text-center small text-white-50">
            &copy; <span data-year><?= date('Y') ?></span> <?= esc($siteName) ?>. <?= esc(setting('footer_note', 'Standar Tata Kelola Website SK Dirjen Badilag No. 2722/DJA/SK.TI1/X/2026.')) ?>
        </div>
    </div>
</footer>
