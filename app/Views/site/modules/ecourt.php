<div class="d-flex flex-wrap gap-2 mt-3">
    <a href="<?= esc(setting('ecourt_url', 'https://ecourt.mahkamahagung.go.id'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-pa-secondary">Portal e-Court MA RI <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i></a>
    <a href="https://putusan3.mahkamahagung.go.id" target="_blank" rel="noopener noreferrer" class="btn btn-pa-primary">Direktori Putusan MA RI</a>
    <?php if (setting('direktori_url')): ?>
        <a href="<?= esc(setting('direktori_url'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success">Direktori Putusan <?= esc(setting('site_name')) ?></a>
    <?php endif ?>
    <?php if (is_pta()): ?>
        <a href="https://banding.mahkamahagung.go.id/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success">SIPP Tingkat Banding</a>
    <?php endif ?>
</div>
