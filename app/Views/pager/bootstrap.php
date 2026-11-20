<?php
/** @var \CodeIgniter\Pager\PagerRenderer $pager */
$pager->setSurroundCount(2);
if ($pager->getPageCount() <= 1) {
    return;
}
$cur   = $pager->getCurrentPageNumber();
$last  = $pager->getPageCount();
$links = $pager->links();
$first = $links ? (int) $links[0]['title'] : 1;
$end   = $links ? (int) $links[count($links) - 1]['title'] : $last;
?>
<nav class="pager-nav d-flex flex-column align-items-center gap-2" aria-label="Navigasi halaman">
    <ul class="pagination flex-wrap justify-content-center mb-0">
        <?php if ($pager->hasPreviousPage()): ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getPreviousPage() ?>" rel="prev"><span aria-hidden="true">&lsaquo;</span><span class="d-none d-sm-inline"> Sebelumnya</span><span class="visually-hidden d-sm-none">Sebelumnya</span></a></li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link"><span aria-hidden="true">&lsaquo;</span><span class="d-none d-sm-inline"> Sebelumnya</span><span class="visually-hidden d-sm-none">Sebelumnya</span></span></li>
        <?php endif ?>

        <?php if ($first > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="Halaman 1">1</a></li>
            <?php if ($first > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?>
        <?php endif ?>

        <?php foreach ($links as $link): ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= $link['uri'] ?>" aria-label="Halaman <?= $link['title'] ?>"<?= $link['active'] ? ' aria-current="page"' : '' ?>><?= $link['title'] ?></a>
            </li>
        <?php endforeach ?>

        <?php if ($end < $last): ?>
            <?php if ($end < $last - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Halaman <?= $last ?>"><?= $last ?></a></li>
        <?php endif ?>

        <?php if ($pager->hasNextPage()): ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getNextPage() ?>" rel="next"><span class="d-none d-sm-inline">Berikutnya </span><span class="visually-hidden d-sm-none">Berikutnya</span><span aria-hidden="true">&rsaquo;</span></a></li>
        <?php else: ?>
            <li class="page-item disabled"><span class="page-link"><span class="d-none d-sm-inline">Berikutnya </span><span class="visually-hidden d-sm-none">Berikutnya</span><span aria-hidden="true">&rsaquo;</span></span></li>
        <?php endif ?>
    </ul>
    <?php if ($pager->getTotal()): ?>
        <p class="small text-secondary mb-0">Menampilkan <?= number_format((int) $pager->getPerPageStart(), 0, ',', '.') ?>–<?= number_format(min((int) $pager->getPerPageEnd(), (int) $pager->getTotal()), 0, ',', '.') ?> dari <?= number_format((int) $pager->getTotal(), 0, ',', '.') ?> · Halaman <?= $cur ?> dari <?= $last ?></p>
    <?php endif ?>
</nav>
