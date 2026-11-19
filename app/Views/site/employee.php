<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<?php
$isHtml = static fn (?string $t): bool => (bool) preg_match('/<(p|ol|ul|li|br|div|h\d|table|strong|em|a)\b/i', (string) $t);
$lines = static fn (?string $t): array => array_values(array_filter(array_map(
    static fn ($l) => trim(preg_replace('/^\s*(\d+[.)]|[-•*])\s+/u', '', $l)), preg_split('/\R/', (string) $t)
), 'strlen'));
$pageUrl  = site_url("{$page['section']}/{$page['slug']}");
// isi riwayat: HTML dari editor (sudah dibersihkan HTMLPurifier) atau teks lama satu per baris
$history = [];
foreach (['Riwayat Pendidikan' => ['bi-mortarboard-fill', 'education'], 'Riwayat Pekerjaan' => ['bi-briefcase-fill', 'career'], 'Riwayat Diklat / Pelatihan' => ['bi-award-fill', 'training']] as $label => [$icon, $col]) {
    $v = (string) ($emp[$col] ?? '');
    if (trim(strip_tags($v)) === '') {
        continue;
    }
    $history[$label] = [$icon, $isHtml($v) ? $v : '<ol>' . implode('', array_map(static fn ($l) => '<li>' . esc($l) . '</li>', $lines($v))) . '</ol>'];
}
$gender   = ['L' => 'Pria', 'P' => 'Wanita'][$emp['gender'] ?? ''] ?? '';
$identity = implode('  |  ', array_filter([$emp['religion'] ?? '', $gender]));
?>
<div class="container-lg py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small mb-2">
            <li class="breadcrumb-item"><a href="<?= site_url('/') ?>" class="text-pa-primary">Beranda</a></li>
            <li class="breadcrumb-item"><?= esc($sectionLabel) ?></li>
            <li class="breadcrumb-item"><a href="<?= $pageUrl ?>" class="text-pa-primary"><?= esc($page['title']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= esc($emp['name']) ?></li>
        </ol>
    </nav>
    <p class="font-display h2 fw-bold text-pa-primary mb-4" aria-hidden="true"><?= esc($sectionLabel) ?></p>
    <div class="row g-4">
        <?= view('site/_page_sidebar', ['sidebar' => $sidebar ?? [], 'sectionLabel' => $sectionLabel, 'activeHref' => $pageUrl]) ?>
        <div class="<?= ! empty($sidebar) ? 'col-lg-9 order-1 order-lg-2' : 'col-12' ?>">
            <article class="emp-profile rounded-3 shadow-sm overflow-hidden" aria-labelledby="empName">
                <div class="emp-profile__bar"><i class="bi bi-person-vcard-fill" aria-hidden="true"></i> Profil <?= esc($emp['position'] ?: ($groupLabel ?: 'Pegawai')) ?></div>
                <?php if (! $emp['is_active']): ?><div class="alert alert-warning small m-3 mb-0">Pratinjau: pegawai ini tidak ditampilkan untuk publik.</div><?php endif ?>
                <div class="p-4 p-md-5">
                    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-end gap-4 pb-4 mb-4 border-bottom">
                        <?php if ($emp['photo']): ?>
                            <img src="<?= media_url($emp['photo']) ?>" alt="Foto <?= esc($emp['name'], 'attr') ?>" class="emp-profile__photo" width="170" height="170">
                        <?php else: ?>
                            <div class="emp-profile__photo emp-profile__photo--empty" aria-hidden="true"><i class="bi bi-person"></i></div>
                        <?php endif ?>
                        <div class="text-center text-md-start">
                            <h1 id="empName" class="h3 fw-bold mb-1"><?= esc($emp['name']) ?></h1>
                            <p class="text-secondary mb-2"><i class="bi bi-geo-alt-fill text-danger" aria-hidden="true"></i> <?= esc(trim(($emp['position'] ? $emp['position'] . ', ' : '') . setting('site_name'))) ?></p>
                            <?php if ($emp['rank']): ?><span class="emp-profile__badge"><i class="bi bi-award-fill" aria-hidden="true"></i> <?= esc($emp['rank']) ?></span><?php endif ?>
                        </div>
                    </div>

                    <?php if ($emp['tmt'] || $identity): ?>
                    <div class="row g-3 mb-4">
                        <?php if ($emp['tmt']): ?>
                        <div class="col-md-6"><div class="emp-fact"><span class="emp-fact__icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
                            <div><div class="emp-fact__label">TMT Jabatan Terakhir</div><div class="fw-bold"><?= tanggal_id($emp['tmt']) ?></div></div></div></div>
                        <?php endif ?>
                        <?php if ($identity): ?>
                        <div class="col-md-6"><div class="emp-fact"><span class="emp-fact__icon emp-fact__icon--alt"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                            <div><div class="emp-fact__label"><?= esc(implode(' / ', array_filter([($emp['religion'] ?? '') ? 'Agama' : '', $gender ? 'Jenis Kelamin' : '']))) ?></div><div class="fw-bold"><?= esc($identity) ?></div></div></div></div>
                        <?php endif ?>
                    </div>
                    <?php endif ?>

                    <?php if (trim(strip_tags((string) ($emp['bio'] ?? ''))) !== ''): ?>
                        <div class="emp-bio emp-rich mb-4"><?= $isHtml($emp['bio']) ? $emp['bio'] : nl2br(esc($emp['bio'])) ?></div>
                    <?php endif ?>

                    <?php if ($history): ?>
                    <div class="row g-3">
                        <?php foreach ($history as $label => [$icon, $items]): ?>
                        <section class="col-md-6" aria-label="<?= esc($label, 'attr') ?>">
                            <div class="emp-history h-100">
                                <h2 class="h6 fw-bold mb-3"><span class="emp-fact__icon emp-fact__icon--sm"><i class="bi <?= $icon ?>" aria-hidden="true"></i></span> <?= esc($label) ?></h2>
                                <div class="emp-rich small text-secondary lh-lg"><?= $items ?></div>
                            </div>
                        </section>
                        <?php endforeach ?>
                    </div>
                    <?php endif ?>

                    <?php if (! empty($emp['profile_url']) && ($ext = \App\Libraries\PageBlocks::cleanUrl((string) $emp['profile_url']))): ?>
                        <p class="mt-4 mb-0"><a href="<?= esc($ext, 'attr') ?>" data-preview="<?= esc(\App\Libraries\PageBlocks::previewUrl($ext) ?? $ext, 'attr') ?>" data-preview-title="<?= esc($emp['name'], 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-pa-primary btn-sm"><i class="bi bi-person-vcard" aria-hidden="true"></i> Profil lengkap (SIMTEPA)</a></p>
                    <?php endif ?>
                    <p class="mt-4 mb-0"><a href="<?= $pageUrl ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke <?= esc($page['title']) ?></a></p>
                </div>
            </article>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
