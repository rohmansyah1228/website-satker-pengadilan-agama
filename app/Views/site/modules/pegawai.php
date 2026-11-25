<?php
use App\Libraries\PageBlocks;
$base = site_url("{$page['section']}/{$page['slug']}");
/** Tautan profil: tautan SIMTEPA (dibuka di penampil popup) atau halaman profil internal. */
$profile = static function (array $e) use ($base): array {
    $ext = ! empty($e['profile_url']) ? PageBlocks::cleanUrl((string) $e['profile_url']) : null;

    return $ext
        ? ['href' => $ext, 'attrs' => ' data-preview="' . esc(PageBlocks::previewUrl($ext) ?? $ext, 'attr') . '" data-preview-title="' . esc($e['name'], 'attr') . '" target="_blank" rel="noopener noreferrer"']
        : ['href' => $base . '/' . $e['id'], 'attrs' => ''];
};
$mode = ($page['module_param'] ?? '') === 'tabel' ? 'tabel' : 'kartu';
?>
<?php if (empty($groups)): ?><p class="text-secondary">Data pegawai belum tersedia.</p><?php endif ?>
<?php if ($mode === 'tabel'): $no = 0; ?>
    <?php if (! empty($groups)): ?>
    <div class="table-responsive page-table-wrap">
        <table class="table table-hover align-middle mb-0 page-table">
            <thead><tr><th scope="col" class="text-center page-table__no">No.</th><th scope="col">Nama</th><th scope="col">Jabatan</th><th scope="col" class="d-none d-md-table-cell">Pangkat/Golongan</th><th scope="col" class="text-center">Profil</th></tr></thead>
            <?php foreach ($labels as $key => $label): if (empty($groups[$key])) continue; ?>
                <tbody>
                    <tr class="emp-group-row"><th scope="rowgroup" colspan="5"><?= esc($label) ?></th></tr>
                    <?php foreach ($groups[$key] as $e): $no++; ?>
                        <tr>
                            <td class="text-center"><?= $no ?>.</td>
                            <td class="fw-semibold"><?= esc($e['name']) ?></td>
                            <td><?= esc($e['position']) ?></td>
                            <td class="d-none d-md-table-cell"><?= esc($e['rank']) ?></td>
                            <td class="text-center"><?php $pl = $profile($e); ?><a href="<?= esc(safe_href($pl['href']), 'attr') ?>"<?= $pl['attrs'] ?> class="btn btn-sm btn-pa-primary page-link-btn" aria-label="Detail profil <?= esc($e['name'], 'attr') ?>"><i class="bi bi-person-vcard" aria-hidden="true"></i> Detail</a></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            <?php endforeach ?>
        </table>
    </div>
    <?php endif ?>
<?php else: ?>
<?php foreach ($labels as $key => $label): ?>
    <?php if (empty($groups[$key])) continue; ?>
    <h2 class="h5 fw-bold text-dark mt-4 mb-3"><?= esc($label) ?></h2>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <?php foreach ($groups[$key] as $e): ?>
            <div class="col">
                <div class="card h-100 text-center p-3 border-top border-3 border-pa-primary emp-card">
                    <?php if ($e['photo']): ?>
                        <img src="<?= media_url($e['photo']) ?>" alt="Foto <?= esc($e['name'], 'attr') ?>" class="rounded-circle mx-auto mb-3 employee-photo" loading="lazy" width="110" height="110">
                    <?php else: ?>
                        <div class="rounded-circle mx-auto mb-3 employee-photo d-flex align-items-center justify-content-center bg-pa-primary text-white fs-2" aria-hidden="true"><i class="bi bi-person"></i></div>
                    <?php endif ?>
                    <h3 class="h6 fw-bold text-pa-primary mb-1"><?php $pl = $profile($e); ?><a href="<?= esc(safe_href($pl['href']), 'attr') ?>"<?= $pl['attrs'] ?> class="stretched-link text-reset text-decoration-none"><?= esc($e['name']) ?></a></h3>
                    <?php if ($e['position']): ?><p class="small fw-semibold mb-1" style="color:#b36b00;"><?= esc($e['position']) ?></p><?php endif ?>
                    <?php if ($e['rank']): ?><p class="small text-secondary mb-2"><?= esc($e['rank']) ?></p><?php endif ?>
                    <span class="mt-auto small fw-semibold text-pa-primary" aria-hidden="true">Lihat profil <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endforeach ?>
<?php endif ?>
