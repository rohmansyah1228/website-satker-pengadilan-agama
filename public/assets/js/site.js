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

    // ---------- Tombol "Lihat" pada tabel: pratinjau Google Drive/Docs/PDF di popup ----------
    const pv = document.getElementById('previewDialog');
    if (pv && typeof pv.showModal === 'function') {
        const frame = document.getElementById('previewFrame');
        const open = document.getElementById('previewOpen');
        const title = document.getElementById('previewTitle');
        let opener = null;
        document.addEventListener('click', (e) => {
            const a = e.target.closest('a[data-preview]');
            if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            opener = a;
            title.textContent = a.dataset.previewTitle || 'Pratinjau';
            frame.title = 'Pratinjau: ' + title.textContent;
            open.href = a.href;
            frame.src = a.dataset.preview;
            pv.showModal();
        });
        pv.querySelector('[data-close-preview]').addEventListener('click', () => pv.close());
        pv.addEventListener('click', (e) => { if (e.target === pv) pv.close(); });
        pv.addEventListener('close', () => { frame.src = 'about:blank'; opener && opener.focus(); });
    }

    // ---------- Tahun berjalan ----------
    document.querySelectorAll('[data-year]').forEach((el) => { el.textContent = new Date().getFullYear(); });
})();
