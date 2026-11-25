<?php use App\Libraries\PageBlocks; ?>
<div class="page-block mb-4">
    <?php if ($b['heading'] !== ''): ?><h2 class="h5 fw-bold text-pa-primary mb-3"><?= esc($b['heading']) ?></h2><?php endif ?>
    <div class="table-responsive page-table-wrap">
        <table class="table table-hover align-middle mb-0 page-table">
            <thead>
                <tr>
                    <?php if ($b['numbered']): ?><th scope="col" class="text-center page-table__no">No.</th><?php endif ?>
                    <?php foreach ($b['cols'] as $c): ?><th scope="col"<?= $c['kind'] === 'link' ? ' class="text-center"' : '' ?>><?= esc($c['label']) ?></th><?php endforeach ?>
                </tr>
            </thead>
            <tbody>
                <?php if (! $b['rows']): ?>
                    <tr><td colspan="<?= count($b['cols']) + ($b['numbered'] ? 1 : 0) ?>" class="text-center text-secondary py-4">Belum ada data.</td></tr>
                <?php endif ?>
                <?php foreach ($b['rows'] as $i => $r):
                    $rowLabel = '';
                    foreach ($b['cols'] as $ci => $c) {
                        if ($c['kind'] === 'text' && ($r[$ci] ?? '') !== '') { $rowLabel = $r[$ci]; break; }
                    } ?>
                    <tr>
                        <?php if ($b['numbered']): ?><td class="text-center"><?= $i + 1 ?>.</td><?php endif ?>
                        <?php foreach ($b['cols'] as $ci => $c): $v = (string) ($r[$ci] ?? ''); ?>
                            <?php if ($c['kind'] === 'link'): ?>
                                <td class="text-center text-nowrap">
                                    <?php if ($v === ''): ?><span class="text-secondary" aria-label="tidak ada">—</span>
                                    <?php else:
                                        $v    = PageBlocks::href($v);
                                        $open = $c['open'] ?? 'popup';
                                        $pv   = $open === 'popup' ? (PageBlocks::previewUrl($v) ?? $v) : null;
                                        $icon = ['popup' => 'bi-eye', 'tab' => 'bi-box-arrow-up-right', 'same' => 'bi-arrow-right-circle'][$open] ?? 'bi-eye'; ?>
                                        <a href="<?= esc(safe_href($v), 'attr') ?>" class="btn btn-sm btn-pa-primary page-link-btn"<?= $pv ? ' data-preview="' . esc($pv, 'attr') . '"' . (($c['newtab'] ?? true) ? '' : ' data-newtab="0"') : '' ?><?= $open === 'same' ? '' : ' target="_blank" rel="noopener noreferrer"' ?> aria-label="<?= esc(trim($c['button'] . ' ' . ($c['label'] !== '' ? $c['label'] . ' ' : '') . $rowLabel), 'attr') ?>" data-preview-title="<?= esc($rowLabel ?: $c['label'], 'attr') ?>">
                                            <i class="bi <?= $icon ?>" aria-hidden="true"></i> <?= esc($c['button']) ?>
                                        </a>
                                    <?php endif ?>
                                </td>
                            <?php else: ?>
                                <td><?= nl2br(esc($v)) ?></td>
                            <?php endif ?>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
