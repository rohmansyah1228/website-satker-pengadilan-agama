<?php
// hanya jenis dokumen yang sudah ada dokumennya yang ditampilkan
$filled = array_filter($groups, static fn ($g) => ! empty($g['years']));
?>
<?php if (! $filled && empty($hideEmpty)): ?>
    <p class="text-secondary">Belum ada dokumen.</p>
<?php endif ?>
<?php foreach ($filled as $g): ?>
    <section class="mb-4" aria-labelledby="dt<?= $g['type']['id'] ?>">
        <h2 id="dt<?= $g['type']['id'] ?>" class="h5 fw-bold text-dark mb-3"><i class="bi bi-folder2-open text-pa-secondary me-2" aria-hidden="true"></i><?= esc($g['type']['name']) ?></h2>
        <?php $first = true; foreach ($g['years'] as $year => $docs): ?>
            <details class="doc-year border-bottom"<?= $first ? ' open' : '' ?>>
                <summary>Tahun <?= (int) $year ?> <span class="badge bg-pa-primary-subtle text-pa-primary ms-1" style="background: var(--pa-primary-light);"><?= count($docs) ?></span></summary>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($docs as $d): ?>
                        <?php $url = $d['uuid'] ? site_url('berkas/' . $d['uuid']) : link_url($d['external_url']); ?>
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 px-0">
                            <span>
                                <i class="bi bi-file-earmark-pdf text-danger me-1" aria-hidden="true"></i>
                                <?= esc($d['title']) ?><?= $d['month'] ? ' — ' . bulan_id((int) $d['month']) . ' ' . (int) $d['year'] : '' ?>
                                <?php if ($d['size']): ?><small class="text-secondary">(PDF, <?= format_bytes((int) $d['size']) ?>)</small><?php endif ?>
                            </span>
                            <a href="<?= esc(safe_href($url), 'attr') ?>" class="btn btn-sm btn-pa-primary" target="_blank" rel="noopener">Lihat Dokumen<span class="visually-hidden"> <?= esc($d['title']) ?></span></a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </details>
        <?php $first = false; endforeach ?>
    </section>
<?php endforeach ?>
