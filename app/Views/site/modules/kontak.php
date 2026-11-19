<div class="row g-4">
    <div class="col-md-6">
        <ul class="list-unstyled lh-lg text-secondary">
            <li><strong>Jalan:</strong> <?= esc(setting('address_street')) ?></li>
            <li><strong>Desa/Kelurahan:</strong> <?= esc(setting('address_village')) ?></li>
            <li><strong>Kecamatan:</strong> <?= esc(setting('address_district')) ?></li>
            <?php if (setting('address_city')): ?><li><strong>Kabupaten/Kota:</strong> <?= esc(setting('address_city')) ?></li><?php endif ?>
            <li><strong>Provinsi:</strong> <?= esc(setting('address_province')) ?></li>
            <li><strong>Kode Pos:</strong> <?= esc(setting('postal_code')) ?></li>
            <li><strong>Telepon:</strong> <?= esc(setting('phone')) ?></li>
            <?php if (setting('whatsapp')): ?><li><strong>WhatsApp PTSP:</strong> <?= esc(setting('whatsapp')) ?></li><?php endif ?>
            <li><strong>Email Resmi:</strong> <a href="mailto:<?= esc(setting('email'), 'attr') ?>"><?= esc(setting('email')) ?></a></li>
            <li><strong>Website Resmi:</strong> <?= esc(setting('website_url', base_url())) ?></li>
        </ul>
        <?php if (setting('office_hours')): ?>
            <h2 class="h6 fw-bold text-dark">Jam Pelayanan</h2>
            <p class="text-secondary"><?= nl2br(esc(setting('office_hours'))) ?></p>
        <?php endif ?>
        <?php if (setting('map_url')): ?>
            <a href="<?= esc(setting('map_url'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-pa-primary"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Lihat di Peta</a>
        <?php endif ?>
    </div>
    <div class="col-md-6">
        <?php if (setting('building_photo')): ?>
            <img src="<?= media_url(setting('building_photo')) ?>" alt="Foto Gedung Kantor <?= esc(setting('site_name'), 'attr') ?>" class="img-fluid rounded shadow-sm" loading="lazy">
        <?php else: ?>
            <div class="bg-light rounded border p-5 text-center text-secondary small">Foto gedung kantor belum diunggah.</div>
        <?php endif ?>
    </div>
</div>
