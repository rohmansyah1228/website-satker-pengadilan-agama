<!-- Widget Aksesibilitas Disabilitas / WCAG (Lampiran II huruf B) -->
<div class="a11y-widget">
    <div id="a11yPanel" class="a11y-panel card shadow-lg border-2 border-pa-primary mb-2" role="region" aria-labelledby="a11yTitle">
        <div class="card-header bg-pa-primary text-white fw-bold d-flex justify-content-between align-items-center">
            <span id="a11yTitle"><i class="bi bi-universal-access me-1" aria-hidden="true"></i> Sarana Disabilitas</span>
            <button type="button" class="btn-close btn-close-white btn-sm" data-a11y="close" aria-label="Tutup Panel Disabilitas"></button>
        </div>
        <div class="card-body d-grid gap-2">
            <button type="button" class="btn btn-outline-success btn-sm text-start fw-semibold" data-a11y="font-up"><i class="bi bi-zoom-in me-2" aria-hidden="true"></i>Perbesar Teks (Zoom In)</button>
            <button type="button" class="btn btn-outline-success btn-sm text-start fw-semibold" data-a11y="font-down"><i class="bi bi-zoom-out me-2" aria-hidden="true"></i>Perkecil Teks (Zoom Out)</button>
            <button type="button" class="btn btn-outline-dark btn-sm text-start fw-semibold" data-a11y="contrast"><i class="bi bi-circle-half me-2" aria-hidden="true"></i>Mode Kontras Tinggi</button>
            <button type="button" class="btn btn-pa-secondary btn-sm text-start fw-bold" data-a11y="speak"><i class="bi bi-volume-up-fill me-2" aria-hidden="true"></i><span id="srLabel">Aktifkan Pembaca Layar</span></button>
            <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none" data-a11y="reset">Reset Tampilan Standar</button>
            <p class="visually-hidden" role="status" id="a11yStatus"></p>
        </div>
    </div>
    <button type="button" id="a11yToggle" class="btn btn-pa-secondary rounded-pill shadow-lg px-3 py-2 fw-bold float-end border border-2 border-dark" aria-controls="a11yPanel" aria-expanded="false" aria-label="Menu Aksesibilitas Penyandang Disabilitas">
        <i class="bi bi-universal-access fs-5 me-1" aria-hidden="true"></i><span class="a11y-label">Disabilitas</span>
    </button>
</div>
