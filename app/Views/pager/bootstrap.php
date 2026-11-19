<?php
/** @var \CodeIgniter\Pager\PagerRenderer $pager */
$pager->setSurroundCount(2);
if ($pager->getPageCount() <= 1) {
    return;
}
?>
<nav aria-label="Navigasi halaman">
    <ul class="pagination flex-wrap">
        <?php if ($pager->hasPrevious()): ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getFirst() ?>" aria-label="Halaman pertama">&laquo;</a></li>
            <li class="page-item"><a class="page-link" href="<?= $pager->getPrevious() ?>" aria-label="Sebelumnya">&lsaquo;</a></li>
        <?php endif ?>
        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>"><a class="page-link" href="<?= $link['uri'] ?>"<?= $link['active'] ? ' aria-current="page"' : '' ?>><?= $link['title'] ?></a></li>
        <?php endforeach ?>
        <?php if ($pager->hasNext()): ?>
            <li class="page-item"><a class="page-link" href="<?= $pager->getNext() ?>" aria-label="Berikutnya">&rsaquo;</a></li>
            <li class="page-item"><a class="page-link" href="<?= $pager->getLast() ?>" aria-label="Halaman terakhir">&raquo;</a></li>
        <?php endif ?>
    </ul>
</nav>
