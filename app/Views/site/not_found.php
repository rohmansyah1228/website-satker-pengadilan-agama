<?= $this->extend('layouts/site') ?>
<?= $this->section('content') ?>
<div class="container-lg py-5 text-center">
    <div class="bg-white p-5 rounded-3 shadow-sm mx-auto" style="max-width: 40rem;">
        <i class="bi bi-signpost-split display-1 text-pa-secondary" aria-hidden="true"></i>
        <h1 class="font-display h2 fw-bold text-pa-primary mt-3">Halaman tidak ditemukan</h1>
        <p class="text-secondary">Halaman yang Anda cari mungkin telah dipindahkan. Gunakan menu di atas atau fitur pencarian.</p>
        <form action="<?= site_url('cari') ?>" method="get" role="search" class="d-flex gap-2 mt-4">
            <label for="nfq" class="visually-hidden">Kata kunci</label>
            <input type="search" id="nfq" name="q" class="form-control" minlength="3" required placeholder="Cari informasi...">
            <button class="btn btn-pa-secondary" type="submit">Cari</button>
        </form>
        <a href="<?= site_url('/') ?>" class="btn btn-pa-primary mt-4">Kembali ke Beranda</a>
    </div>
</div>
<?= $this->endSection() ?>
