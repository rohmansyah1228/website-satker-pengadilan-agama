<div class="alert alert-warning border-start border-4 border-pa-secondary">
    <strong>Sistem Informasi Pengawasan Mahkamah Agung RI (SIWAS):</strong> laporkan dugaan pelanggaran perilaku aparatur pengadilan secara rahasia melalui
    <a href="<?= esc(setting('siwas_url', 'https://siwas.mahkamahagung.go.id'), 'attr') ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-dark"><?= esc(preg_replace('#^https?://#', '', setting('siwas_url', 'https://siwas.mahkamahagung.go.id'))) ?></a>.
</div>
<h2 class="h5 fw-bold text-dark mt-4">Rekapitulasi Pengaduan</h2>
<?= view('site/modules/_complaints_table', ['rows' => $rows, 'who' => 'Inisial Pelapor']) ?>
