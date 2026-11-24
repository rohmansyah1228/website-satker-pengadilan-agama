/* Panel Admin — tanpa handler inline */
(function () {
    'use strict';

    // ---------- Sidebar mobile (laci: buka/tutup, latar gelap, Esc, tutup saat pilih menu) ----------
    const sb = document.getElementById('adminSidebar');
    const bd = document.querySelector('.admin-backdrop');
    const toggles = document.querySelectorAll('[data-toggle-sidebar]');
    const setOpen = (open) => {
        if (!sb) return;
        sb.classList.toggle('open', open);
        toggles.forEach((b) => b.setAttribute('aria-expanded', open ? 'true' : 'false'));
        if (bd) {
            if (open) { bd.hidden = false; requestAnimationFrame(() => { bd.style.opacity = '1'; }); }
            else { bd.style.opacity = '0'; setTimeout(() => { bd.hidden = true; }, 200); }
        }
        document.body.style.overflow = open ? 'hidden' : '';
    };
    toggles.forEach((b) => b.addEventListener('click', () => setOpen(!sb.classList.contains('open'))));
    document.querySelectorAll('[data-close-sidebar]').forEach((b) => b.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sb && sb.classList.contains('open')) setOpen(false); });
    // memilih menu navigasi langsung menutup laci (tampilan HP)
    sb && sb.querySelectorAll('a[href]').forEach((a) => a.addEventListener('click', () => { if (window.matchMedia('(max-width: 991.98px)').matches) setOpen(false); }));
    // kembali ke layar lebar: pastikan laci & latar bersih
    window.matchMedia('(min-width: 992px)').addEventListener('change', (e) => { if (e.matches) setOpen(false); });

    // ---------- Tombol Kembali: ke halaman induk menurut breadcrumb (bila ada) ----------
    const back = document.getElementById('adminBack');
    if (back) {
        const crumbs = document.querySelectorAll('#adminMain .breadcrumb a[href]');
        const parent = crumbs[crumbs.length - 1];
        if (parent && !back.dataset.fixed) {
            back.href = parent.href;
            back.querySelector('[data-back-label]').textContent = 'Kembali ke ' + parent.textContent.trim();
        }
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 'ArrowLeft' && !e.target.closest('input, textarea, select, [contenteditable]')) { e.preventDefault(); back.click(); }
        });
    }

    // ---------- Form data: "Tampil di halaman" mengikuti jenis dokumen yang dipilih ----------
    const shown = document.querySelector('[data-shown-on]');
    const typeSel = document.getElementById('f_doc_type_id');
    if (shown && typeSel && shown.querySelector('[data-types]')) {
        const sync = () => {
            let n = 0;
            shown.querySelectorAll('li[data-types]').forEach((li) => {
                const on = li.dataset.types.split(',').includes(typeSel.value);
                li.hidden = !on;
                if (on) n++;
            });
            shown.querySelector('[data-shown-none]').hidden = n > 0 || !typeSel.value;
            shown.querySelector('h2').hidden = !typeSel.value;
        };
        typeSel.addEventListener('change', sync);
        sync();
    }

    // Editor teks kaya: lihat editor.js (TinyMCE)

    // ---------- Dialog konfirmasi hapus (native <dialog>) ----------
    const dlg = document.getElementById('confirmDialog');
    let pendingForm = null;
    document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
        if (f.dataset.confirmed === '1' || !dlg || typeof dlg.showModal !== 'function') return;
        e.preventDefault();
        pendingForm = f;
        dlg.querySelector('[data-confirm-text]').textContent = f.dataset.confirm;
        dlg.returnValue = '';
        dlg.showModal();
    }));
    // tombol hapus di dalam formulir lain (mis. data modul di Ubah Halaman)
    let pendingAction = null;
    document.querySelectorAll('[data-delete-url]').forEach((btn) => btn.addEventListener('click', () => {
        const run = () => {
            const f = document.createElement('form');
            f.method = 'post'; f.action = btn.dataset.deleteUrl; f.hidden = true;
            const tok = document.querySelector('input[name="csrf_test_name"], input[name^="csrf"]');
            if (tok) { const t = document.createElement('input'); t.type = 'hidden'; t.name = tok.name; t.value = tok.value; f.append(t); }
            const k = document.createElement('input'); k.type = 'hidden'; k.name = 'kembali'; k.value = btn.dataset.kembali || ''; f.append(k);
            document.body.append(f);
            f.submit();
        };
        if (!dlg || typeof dlg.showModal !== 'function') { if (window.confirm(btn.dataset.confirmMsg)) run(); return; }
        pendingAction = run;
        dlg.querySelector('[data-confirm-text]').textContent = btn.dataset.confirmMsg;
        dlg.returnValue = '';
        dlg.showModal();
    }));
    if (dlg) {
        dlg.addEventListener('close', () => {
            if (dlg.returnValue === 'ok' && pendingAction) { const a = pendingAction; pendingAction = null; a(); return; }
            pendingAction = null;
            if (dlg.returnValue === 'ok' && pendingForm) {
                pendingForm.dataset.confirmed = '1';
                pendingForm.requestSubmit ? pendingForm.requestSubmit() : pendingForm.submit();
            }
            dlg.returnValue = '';
            pendingForm = null;
        });
    }

    // ---------- Unduhan yang mengubah keadaan (mis. paket SIPP API Kantor): muat ulang agar status terbaru tampil ----------
    document.querySelectorAll('a[data-reload-after]').forEach((a) => a.addEventListener('click', () => {
        setTimeout(() => location.reload(), parseInt(a.dataset.reloadAfter, 10) || 2000);
    }));

    // ---------- Tombol proses lama (mis. Update Website): kunci & tampilkan status ----------
    document.querySelectorAll('form[data-loading]').forEach((f) => f.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return; // masih menunggu konfirmasi
        const b = f.querySelector('button[type="submit"], button:not([type])');
        if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' + f.dataset.loading; }
        document.body.style.cursor = 'progress';
    }));

    // ---------- Tampilkan/sembunyikan field bergantung pilihan ----------
    const syncToggles = () => {
        document.querySelectorAll('[data-show-when]').forEach((el) => {
            const [name, values] = el.dataset.showWhen.split('=');
            const ctl = document.querySelector('[name="' + name + '"]:checked, select[name="' + name + '"]');
            const show = ctl && values.split(',').includes(ctl.value);
            el.hidden = !show;
            // isian bertanda data-locked (mis. jenis dokumen bawaan SK) tetap terkunci
            el.querySelectorAll('input, select, textarea').forEach((i) => { i.disabled = !show || i.hasAttribute('data-locked'); });
        });
    };
    document.querySelectorAll('select, input[type=radio]').forEach((el) => el.addEventListener('change', syncToggles));
    syncToggles();

    // ---------- Pratinjau slug ----------
    const title = document.querySelector('[data-slug-source]');
    const slug = document.querySelector('[data-slug-target]');
    if (title && slug && !slug.readOnly) {
        let touched = slug.value !== '';
        slug.addEventListener('input', () => { touched = true; });
        title.addEventListener('input', () => {
            if (touched) return;
            slug.value = title.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120);
        });
    }

    // ---------- QR code untuk MFA ----------
    const qr = document.getElementById('totpQr');
    if (qr && window.qrcode) {
        const q = window.qrcode(0, 'M');
        q.addData(qr.dataset.uri);
        q.make();
        qr.innerHTML = q.createSvgTag({ cellSize: 5, margin: 2, scalable: true, alt: 'Kode QR MFA', title: 'Pindai dengan aplikasi authenticator' });
    }

    // ---------- Salin URL (Dashboard SINOVA) ----------
    const live = document.getElementById('adminLive');
    document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(b.dataset.copy);
            const old = b.innerHTML;
            b.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i> Disalin';
            if (live) live.textContent = 'URL disalin ke clipboard';
            setTimeout(() => { b.innerHTML = old; }, 1500);
        } catch (e) { window.prompt('Salin URL berikut:', b.dataset.copy); }
    }));

    // ---------- Filter otomatis ----------
    document.querySelectorAll('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form && el.form.submit()));

    // ---------- Fokus kembali ke tombol setelah memindah menu ----------
    const movedItem = document.querySelector('.menu-item.moved');
    if (movedItem) {
        const dir = document.body.dataset.movedDir || 'up';
        let btn = movedItem.querySelector('[data-dir="' + dir + '"]');
        // tombol yang dipakai menjadi nonaktif di ujung daftar → pindahkan fokus ke tombol arah sebaliknya
        if (!btn || btn.disabled) btn = movedItem.querySelector('[data-dir="' + (dir === 'up' ? 'down' : 'up') + '"]');
        if (btn && !btn.disabled) {
            // tunggu pemuatan selesai (navigasi ke #fragmen dapat mengatur ulang fokus)
            const restore = () => { movedItem.scrollIntoView({ block: 'nearest' }); btn.focus({ preventScroll: true }); };
            document.readyState === 'complete' ? setTimeout(restore, 0) : window.addEventListener('load', () => setTimeout(restore, 0), { once: true });
        }
        if (live) {
            const label = movedItem.querySelector('strong');
            live.textContent = '';
            setTimeout(() => { live.textContent = (label ? label.textContent : 'Menu') + ' dipindah ' + (dir === 'up' ? 'ke atas' : 'ke bawah'); }, 500);
        }
    }
})();

/* ---------- Pemilih ikon Bootstrap Icons (isian jenis "icon") ---------- */
(function () {
    'use strict';
    const buttons = document.querySelectorAll('[data-icon-pick]');
    if (!buttons.length) return;
    const meta = document.querySelector('meta[name="satker-admin"]');
    const cfg = meta ? JSON.parse(meta.content) : {};
    const clean = (v) => (v || '').trim().toLowerCase().replace(/^bi\s+/, '').replace(/^(bi-)?/, 'bi-').replace(/[^a-z0-9-]/g, '');

    // pratinjau langsung saat mengetik
    document.querySelectorAll('[data-icon-input]').forEach((inp) => {
        const prev = inp.closest('.icon-field').querySelector('.icon-field-preview i');
        inp.addEventListener('input', () => { prev.className = 'bi ' + (inp.value.trim() ? clean(inp.value) : 'bi-question-square'); });
    });

    // ikon yang sering dipakai situs pengadilan
    const POPULAR = ['search', 'bank', 'bank2', 'building', 'buildings', 'house-door', 'laptop', 'pc-display', 'phone', 'shield-check', 'shield-exclamation',
        'shield-lock', 'megaphone', 'megaphone-fill', 'chat-dots', 'envelope', 'telephone', 'whatsapp', 'calendar-week', 'calendar-check', 'clock', 'hourglass-split',
        'journal-bookmark-fill', 'journal-text', 'book', 'book-half', 'file-earmark-text', 'file-earmark-pdf', 'files', 'folder2-open', 'archive', 'clipboard-check',
        'clipboard-data', 'bar-chart', 'graph-up', 'pie-chart', 'cash-stack', 'cash-coin', 'wallet2', 'receipt', 'people', 'people-fill', 'person-badge',
        'person-vcard', 'person-wheelchair', 'universal-access', 'heart-pulse', 'award', 'patch-check', 'star', 'hand-thumbs-up', 'emoji-smile', 'qr-code',
        'link-45deg', 'box-arrow-up-right', 'globe', 'geo-alt', 'map', 'info-circle', 'question-circle', 'exclamation-triangle', 'gear', 'tools',
        'camera-video', 'youtube', 'facebook', 'instagram', 'newspaper', 'images', 'download', 'upload', 'printer', 'key', 'lock', 'fingerprint', 'briefcase',
        'mortarboard', 'signpost-split', 'truck', 'car-front', 'scale' /* dibuang bila tak ada */];
    // kata kunci bahasa Indonesia → nama ikon (Inggris)
    const ID = {cari: 'search', pencarian: 'search', rumah: 'house', beranda: 'house', gedung: 'building', kantor: 'building', pengadilan: 'bank|building', bank: 'bank',
        dokumen: 'file|journal|clipboard', berkas: 'file', file: 'file', folder: 'folder', arsip: 'archive', buku: 'book', jurnal: 'journal', uang: 'cash|coin|currency|wallet|piggy-bank', biaya: 'cash|coin|currency|receipt',
        dompet: 'wallet', bayar: 'credit-card', orang: 'person|people', pegawai: 'person', pengguna: 'person', kelompok: 'people', masyarakat: 'people',
        kalender: 'calendar', jadwal: 'calendar|clock', tanggal: 'calendar', jam: 'clock', waktu: 'clock', pengumuman: 'megaphone', pengaduan: 'megaphone|chat|exclamation',
        berita: 'newspaper', video: 'camera-video', foto: 'image|camera', gambar: 'image', galeri: 'images', telepon: 'telephone|phone', hp: 'phone', email: 'envelope',
        surat: 'envelope', pesan: 'chat', obrolan: 'chat', lokasi: 'geo', peta: 'map', perisai: 'shield', keamanan: 'shield', kunci: 'lock', gembok: 'lock',
        grafik: 'graph', statistik: 'bar-chart', diagram: 'pie-chart', bintang: 'star', penghargaan: 'award', piala: 'trophy', unduh: 'download', unggah: 'upload',
        cetak: 'printer', tautan: 'link', dunia: 'globe', internet: 'globe', info: 'info', informasi: 'info', tanya: 'question', peringatan: 'exclamation',
        pengaturan: 'gear', alat: 'tools', disabilitas: 'wheelchair', aksesibilitas: 'universal-access', senyum: 'emoji-smile', suka: 'hand-thumbs-up',
        centang: 'check', daftar: 'list', tabel: 'table', komputer: 'pc-display', laptop: 'laptop', mobil: 'car', truk: 'truck', sekolah: 'mortarboard',
        kerja: 'briefcase', tas: 'briefcase', hati: 'heart', kesehatan: 'heart-pulse', hapus: 'trash', tambah: 'plus', panah: 'arrow', video_youtube: 'youtube'};

    let all = null, dlg, grid, search, info, target = null;
    async function load() {
        if (all) return all;
        try { all = await (await fetch((cfg.assets || '/assets/') + 'vendor/bootstrap-icons/icons.json')).json(); } catch (e) { all = POPULAR; }
        return all;
    }
    function build() {
        dlg = document.createElement('dialog');
        dlg.className = 'icon-picker';
        dlg.setAttribute('aria-labelledby', 'iconPickerTitle');
        dlg.innerHTML = '<div class="icon-picker-head"><h2 id="iconPickerTitle" class="h5 fw-bold mb-0">Pilih Ikon</h2>'
            + '<button type="button" class="btn-close" aria-label="Tutup"></button></div>'
            + '<input type="search" class="form-control mb-2" placeholder="Cari ikon… (mis. dokumen, uang, calendar, people)" aria-label="Cari ikon">'
            + '<p class="small text-secondary mb-2 icon-picker-info" aria-live="polite"></p>'
            + '<div class="icon-picker-grid" role="listbox" aria-label="Daftar ikon"></div>'
            + '<div class="icon-picker-foot"><button type="button" class="btn btn-outline-secondary btn-sm" data-icon-clear>Tanpa ikon</button>'
            + '<a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener" class="small">Lihat semua di icons.getbootstrap.com</a></div>';
        document.body.appendChild(dlg);
        grid = dlg.querySelector('.icon-picker-grid');
        search = dlg.querySelector('input[type=search]');
        info = dlg.querySelector('.icon-picker-info');
        dlg.querySelector('.btn-close').addEventListener('click', () => dlg.close());
        dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
        dlg.querySelector('[data-icon-clear]').addEventListener('click', () => choose(''));
        let t;
        search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(render, 120); });
        grid.addEventListener('click', (e) => { const b = e.target.closest('[data-icon]'); if (b) choose(b.dataset.icon); });
        search.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); const f = grid.querySelector('[data-icon]'); if (f) choose(f.dataset.icon); } });
    }
    function render() {
        const q = search.value.trim().toLowerCase().replace(/^bi-/, '');
        let list;
        if (!q) {
            list = POPULAR.filter((n) => all.includes(n));
            info.textContent = 'Ikon yang sering dipakai. Ketik untuk mencari dari ' + all.length.toLocaleString('id-ID') + ' ikon (bisa bahasa Indonesia).';
        } else {
            const terms = q.split(/\s+/).map((w) => [w, ...(ID[w] || '').split('|')].filter(Boolean));
            list = all.filter((n) => terms.every((alts) => alts.some((a) => n.includes(a))));
            info.textContent = list.length ? list.length + ' ikon ditemukan' + (list.length > 400 ? ' (400 pertama ditampilkan, perjelas pencarian)' : '') + '.' : 'Tidak ditemukan. Coba kata lain (mis. bahasa Inggris).';
            list = list.slice(0, 400);
        }
        const cur = target ? clean(target.value) : '';
        grid.innerHTML = list.map((n) => '<button type="button" class="icon-picker-item' + ('bi-' + n === cur ? ' active' : '') + '" data-icon="bi-' + n + '" role="option" title="bi-' + n + '">'
            + '<i class="bi bi-' + n + '" aria-hidden="true"></i><span>' + n + '</span></button>').join('');
    }
    function choose(icon) {
        if (target) {
            target.value = icon;
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.focus();
        }
        dlg.close();
    }
    buttons.forEach((btn) => btn.addEventListener('click', async () => {
        target = document.getElementById(btn.dataset.iconPick);
        if (!dlg) build();
        await load();
        search.value = '';
        render();
        dlg.showModal();
        search.focus();
    }));
})();
