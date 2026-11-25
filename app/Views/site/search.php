<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5">
    <h1 class="font-display h2 fw-bold text-pa-primary mb-4">Pencarian Informasi</h1>
    <form action="<?= site_url('cari') ?>" method="get" role="search" class="bg-white p-4 rounded-3 shadow-sm mb-4">
        <label for="q" class="form-label fw-semibold">Kata kunci</label>
        <div class="d-flex gap-2">
            <input type="search" id="q" name="q" value="<?= esc($q, 'attr') ?>" class="form-control form-control-lg" minlength="3" required enterkeyhint="search" placeholder="misal: jadwal sidang, panjar, DIPA, LHKPN">
            <button type="submit" class="btn btn-pa-secondary px-4">Cari</button>
        </div>
    </form>
    <?php if ($q !== ''): ?>
        <p class="text-secondary" role="status"><?= number_format($total ?? count($results), 0, ',', '.') ?> hasil untuk "<strong><?= esc($q) ?></strong>"</p>
        <?php if (mb_strlen($q) < 3): ?><p class="text-secondary">Masukkan minimal 3 karakter.</p><?php endif ?>
        <div class="list-group">
            <?php foreach ($results as $r): ?>
                <a href="<?= esc(safe_href($r['url']), 'attr') ?>" class="list-group-item list-group-item-action p-3">
                    <span class="badge bg-pa-primary mb-1"><?= esc($r['type']) ?></span>
                    <span class="d-block fw-bold text-pa-primary"><?= esc($r['title']) ?></span>
                    <small class="text-secondary"><?= esc($r['snippet']) ?></small>
                </a>
            <?php endforeach ?>
        </div>
        <?php if (! empty($pager)): ?><div class="mt-4"><?= $pager ?></div><?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
