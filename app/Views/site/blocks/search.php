<?php $sid = 'cari-' . $b['id']; ?>
<form action="<?= site_url('cari') ?>" method="get" role="search" class="d-flex gap-2"<?= $b['title'] ? '' : ' aria-label="Pencarian situs"' ?>>
    <label for="<?= $sid ?>" class="visually-hidden">Kata kunci pencarian</label>
    <input type="search" id="<?= $sid ?>" name="q" class="form-control" minlength="3" required placeholder="Cari informasi…" enterkeyhint="search">
    <button type="submit" class="btn btn-pa-primary" aria-label="Cari"><i class="bi bi-search" aria-hidden="true"></i></button>
</form>
