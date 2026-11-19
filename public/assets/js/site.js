/* Website Satker Peradilan Agama — skrip publik (tanpa handler inline). */
(function () {
    'use strict';

    // ---------- Penyimpanan preferensi (aman jika localStorage tidak tersedia) ----------
    const store = {
        get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* abaikan */ } },
        del(k) { try { localStorage.removeItem(k); } catch (e) { /* abaikan */ } }
    };

    // ---------- Aksesibilitas: ukuran teks & kontras tinggi (dipertahankan antar halaman) ----------
    const root = document.documentElement;
    let fontSize = parseInt(store.get('a11y-font') || '16', 10);
    const applyFont = () => root.style.setProperty('--base-font-size', fontSize + 'px');
    applyFont();
    if (store.get('a11y-contrast') === '1') document.body.classList.add('high-contrast');

    const panel = document.getElementById('a11yPanel');
    const toggleBtn = document.getElementById('a11yToggle');
    const setPanel = (open) => {
        if (!panel) return;
        panel.classList.toggle('show', open);
        toggleBtn && toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) { const f = panel.querySelector('button'); f && f.focus(); }
    };
    toggleBtn && toggleBtn.addEventListener('click', () => setPanel(!panel.classList.contains('show')));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && panel && panel.classList.contains('show')) { setPanel(false); toggleBtn && toggleBtn.focus(); }
    });

    const status = document.getElementById('a11yStatus');
    const announce = (msg) => { if (status) { status.textContent = ''; setTimeout(() => { status.textContent = msg; }, 50); } };

    let speaking = false;
    const srLabel = document.getElementById('srLabel');
    const stopSpeech = () => {
        if ('speechSynthesis' in window) window.speechSynthesis.cancel();
        speaking = false;
        if (srLabel) srLabel.textContent = 'Aktifkan Pembaca Layar';
    };

    const actions = {
        'font-up': () => { fontSize = Math.min(24, fontSize + 2); applyFont(); store.set('a11y-font', fontSize); announce('Ukuran teks ' + fontSize + ' piksel'); },
        'font-down': () => { fontSize = Math.max(12, fontSize - 2); applyFont(); store.set('a11y-font', fontSize); announce('Ukuran teks ' + fontSize + ' piksel'); },
        'contrast': () => {
            const on = document.body.classList.toggle('high-contrast');
            store.set('a11y-contrast', on ? '1' : '0');
            announce(on ? 'Mode kontras tinggi aktif' : 'Mode kontras tinggi nonaktif');
        },
        'speak': () => {
            if (!('speechSynthesis' in window)) { announce('Peramban tidak mendukung pembaca layar suara'); return; }
            if (speaking) { stopSpeech(); return; }
            const main = document.getElementById('main-content');
            const sel = window.getSelection && String(window.getSelection());
            const text = (sel && sel.trim().length > 2 ? sel : (main ? main.innerText : document.body.innerText)).slice(0, 4000);
            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'id-ID';
            u.onend = stopSpeech;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
            speaking = true;
            if (srLabel) srLabel.textContent = 'Hentikan Pembaca Layar';
        },
        'reset': () => {
            fontSize = 16; applyFont(); store.del('a11y-font'); store.del('a11y-contrast');
            document.body.classList.remove('high-contrast'); stopSpeech(); announce('Tampilan dikembalikan ke standar');
        },
        'close': () => { setPanel(false); toggleBtn && toggleBtn.focus(); }
    };
    document.querySelectorAll('[data-a11y]').forEach((b) => b.addEventListener('click', () => (actions[b.dataset.a11y] || (() => {}))()));
    window.addEventListener('pagehide', stopSpeech);

    // ---------- Dialog pencarian (native <dialog>) ----------
    const dlg = document.getElementById('searchDialog');
    document.querySelectorAll('[data-open-search]').forEach((b) => b.addEventListener('click', () => {
        if (dlg && typeof dlg.showModal === 'function') { dlg.showModal(); }
        else { window.location.href = b.dataset.fallback || '/cari'; }
    }));
    dlg && dlg.querySelectorAll('[data-close-dialog]').forEach((b) => b.addEventListener('click', () => dlg.close()));
    if (dlg) {
        dlg.addEventListener('click', (e) => {
            const r = dlg.getBoundingClientRect();
            if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) dlg.close();
        });
    }

    // ---------- Jadwal sidang: ganti tanggal otomatis kirim ----------
    document.querySelectorAll('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form && el.form.submit()));

    // ---------- Penampil di dalam website: tombol "Lihat" (tabel) & tautan bertarget "tampil" (editor) ----------
    // Google Drive/Docs/YouTube diubah ke alamat tampilan; situs lain ditampilkan apa adanya.
    const viewerSrc = (u) => {
        let p;
        try { p = new URL(u, location.href); } catch (e) { return u; }
        const host = p.hostname.toLowerCase(), path = p.pathname;
        let m;
        if (host === 'drive.google.com') {
            if ((m = path.match(/\/file\/d\/([\w-]+)/))) return 'https://drive.google.com/file/d/' + m[1] + '/preview';
            if ((m = path.match(/\/folders\/([\w-]+)/))) return 'https://drive.google.com/embeddedfolderview?id=' + m[1] + '#list';
            if (p.searchParams.get('id')) return 'https://drive.google.com/file/d/' + p.searchParams.get('id') + '/preview';
        }
        if (host === 'docs.google.com') {
            if ((m = path.match(/^\/forms\/(?:u\/\d+\/)?d\/(e\/)?([\w-]+)/))) return 'https://docs.google.com/forms/d/' + (m[1] || '') + m[2] + '/viewform?embedded=true';
            if ((m = path.match(/^\/(document|spreadsheets|presentation)\/(?:u\/\d+\/)?d\/([\w-]+)/))) return 'https://docs.google.com/' + m[1] + '/d/' + m[2] + '/preview';
        }
        if (/^(www\.|m\.)?youtube\.com$|^youtu\.be$/.test(host)) {
            const id = host === 'youtu.be' ? path.slice(1) : (p.searchParams.get('v') || '');
            if (/^[\w-]{6,20}$/.test(id)) return 'https://www.youtube-nocookie.com/embed/' + id;
        }
        return p.href;
    };
    const pv = document.getElementById('previewDialog');
    if (pv && typeof pv.showModal === 'function') {
        const frame = document.getElementById('previewFrame');
        const open = document.getElementById('previewOpen');
        const title = document.getElementById('previewTitle');
        const hint = document.getElementById('previewHint');
        let opener = null;
        document.addEventListener('click', (e) => {
            const a = e.target.closest('a[data-preview], a[target="tampil"], a[target="tampil-saja"]');
            if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
            if (!/^https?:$/.test(a.protocol)) return;
            e.preventDefault();
            opener = a;
            const row = a.closest('tr');
            const rowLabel = row ? [...row.cells].map((c) => c.textContent.trim()).find((t) => t && !/^\d+\.?$/.test(t) && t !== a.textContent.trim()) : '';
            let label = a.dataset.previewTitle || a.title || a.textContent.trim();
            if (!label || label.length < 3 || /^(lihat|detail|unduh|klik|buka)/i.test(label)) label = rowLabel || a.hostname;
            title.textContent = label;
            frame.title = 'Tampilan: ' + title.textContent;
            open.href = a.href;
            const showNewTab = a.dataset.newtab !== '0' && a.target !== 'tampil-saja';
            open.hidden = !showNewTab;
            if (hint) hint.classList.toggle('d-lg-inline', showNewTab);
            frame.src = a.dataset.preview || viewerSrc(a.href);
            pv.showModal();
        });
        pv.querySelector('[data-close-preview]').addEventListener('click', () => pv.close());
        pv.addEventListener('click', (e) => { if (e.target === pv) pv.close(); });
        pv.addEventListener('close', () => { frame.src = 'about:blank'; opener && opener.focus(); });
    }

    // ---------- Tahun berjalan ----------
    document.querySelectorAll('[data-year]').forEach((el) => { el.textContent = new Date().getFullYear(); });
})();
