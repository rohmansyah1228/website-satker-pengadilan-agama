<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="<?= site_url('/') ?>">Beranda</a></li><li class="breadcrumb-item"><a href="<?= site_url('berita') ?>">Berita &amp; Publikasi</a></li><li class="breadcrumb-item active" aria-current="page">Pengumuman Badilag &amp; MA</li></ol></nav>
    <h1 class="font-display h2 fw-bold text-pa-primary mb-2">Pengumuman Badilag &amp; Mahkamah Agung</h1>
    <p class="text-secondary mb-4">Pengumuman terbaru dari Direktorat Jenderal Badan Peradilan Agama dan Mahkamah Agung RI, diperbarui otomatis. Klik judul untuk membuka pengumuman dan unduhan surat di situs resminya.</p>

    <?php if (count($sources) > 1): ?>
        <ul class="nav nav-pills mb-3 gap-2" aria-label="Saring sumber">
            <li class="nav-item"><a class="nav-link<?= $source === '' ? ' active' : '' ?>" href="<?= site_url('pengumuman-badilag-ma') ?>"<?= $source === '' ? ' aria-current="page"' : '' ?>>Semua</a></li>
            <?php foreach ($sources as $k => [$label]): ?>
                <li class="nav-item"><a class="nav-link<?= $source === $k ? ' active' : '' ?>" href="<?= site_url('pengumuman-badilag-ma?sumber=' . $k) ?>"<?= $source === $k ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="list-group list-group-flush bg-white rounded-3 shadow-sm px-3">
                <?php foreach ($items as $n): ?><?= view('partials/external_notice', ['n' => $n]) ?><?php endforeach ?>
                <?php if (! $items): ?><p class="text-secondary py-4 mb-0">Pengumuman belum dapat dimuat saat ini. Silakan buka langsung situs sumbernya.</p><?php endif ?>
            </div>
            <div class="mt-3"><?= $pager ?></div>
            <?php if (! empty($status['at'])): ?><p class="small text-secondary mt-2 mb-0"><?= number_format($total, 0, ',', '.') ?> pengumuman · diperbarui <?= tanggal_id(date('Y-m-d H:i:s', $status['at'])) ?>, pukul <?= date('H.i', $status['at']) ?>.</p><?php endif ?>
        </div>
        <div class="col-lg-4">
            <div class="bg-white rounded-3 shadow-sm p-4">
                <h2 class="h6 fw-bold">Situs sumber</h2>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($sources as [$label, $url]): ?>
                        <li class="mb-2"><a href="<?= esc(safe_href($url), 'attr') ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Pengumuman <?= esc($label) ?></a></li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
