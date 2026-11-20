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
        if (srLabel) srLabel.textContent = 'Bacakan Isi Halaman';
    };

    // ---------- Suara Panduan: sambutan otomatis + membacakan yang diklik (aktif secara bawaan) ----------
    const canSpeak = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
    let voiceOn = canSpeak && store.get('a11y-voice') !== '0';
    const voiceLabel = document.getElementById('voiceLabel');
    const voiceBtn = document.querySelector('[data-a11y="voice"]');
    const syncVoice = () => {
        if (voiceLabel) voiceLabel.textContent = 'Suara Panduan: ' + (voiceOn ? 'Aktif' : 'Nonaktif');
        if (voiceBtn) voiceBtn.setAttribute('aria-pressed', voiceOn ? 'true' : 'false');
        if (!canSpeak && voiceBtn) voiceBtn.hidden = true;
    };
    syncVoice();
    let idVoice = null;
    const voiceId = () => {
        if (idVoice || !canSpeak) return idVoice;
        const vs = window.speechSynthesis.getVoices();
        idVoice = vs.find((v) => /^id(-|_|$)/i.test(v.lang)) || vs.find((v) => /indonesia/i.test(v.name)) || null;
        return idVoice;
    };
    if (canSpeak) { voiceId(); window.speechSynthesis.addEventListener && window.speechSynthesis.addEventListener('voiceschanged', () => { idVoice = null; voiceId(); }); }
    // ucapkan teks (tanpa antre: yang baru menggantikan yang lama, kecuali queue=true)
    const say = (text, onDone, queue) => {
        if (!canSpeak || !text) { onDone && onDone(); return null; }
        if (!queue) window.speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(text);
        u.lang = 'id-ID';
        if (voiceId()) u.voice = voiceId();
        u.rate = 1;
        let done = false;
        const fin = () => { if (!done) { done = true; onDone && onDone(); } };
        u.onend = fin;
        u.onerror = fin;
        window.speechSynthesis.speak(u);
        return u;
    };
    const widget = document.querySelector('.a11y-widget');
    const siteName = (widget && widget.dataset.siteName) || document.title.split('—').pop().trim();
    const welcomeText = 'Selamat datang di website ' + siteName;
    const session = { get(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } }, set(k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* abaikan */ } } };
    let welcomed = session.get('a11y-welcomed') === '1';
    const playWelcome = (queueAfter) => {
        if (welcomed || !voiceOn) return false;
        welcomed = true;
        session.set('a11y-welcomed', '1'); // sekali per kunjungan (tidak diulang di setiap halaman)
        say(welcomeText, null, false);
        return true;
    };
    if (voiceOn && !welcomed) {
        // coba langsung; peramban yang mewajibkan interaksi akan menolak → diputar pada interaksi pertama
        const u = say(welcomeText);
        if (u) {
            u.onstart = () => { welcomed = true; session.set('a11y-welcomed', '1'); };
        }
    }
    // label yang dibacakan untuk elemen yang diklik
    const labelOf = (el) => {
        const head = el.querySelector('.font-display, h1, h2, h3, h4, h5, .shortcut-button-label, .video-title');
        const t = el.getAttribute('aria-label') || el.dataset.previewTitle || el.getAttribute('title')
            || (head && head.innerText.replace(/\s+/g, ' ').trim())
            || (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim()
            || (el.querySelector('img[alt]') || {}).alt || el.value || '';
        return t.replace(/\(membuka tab baru\)/i, '').replace(/[→›»←‹«]/g, '').trim().slice(0, 160);
    };
    const CLICKABLE = 'a[href], button, [role="button"], [role="tab"], [role="menuitem"], summary, input[type="submit"], input[type="button"], label[for]';
    document.addEventListener('click', (e) => {
        if (!voiceOn) return;
        const el = e.target.closest(CLICKABLE);
        if (!el || el.closest('[data-a11y]')) { playWelcome(); return; }
        const label = labelOf(el);
        const greeted = playWelcome();
        if (!label) return;
        // tautan pindah halaman di tab yang sama: tunggu ucapan selesai agar tidak terpotong (maks. 2,5 detik)
        const nav = !e.defaultPrevented && el.matches('a[href]') && !el.target && !el.hasAttribute('download') && !el.matches('[data-preview], [data-bs-toggle]')
            && !e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey && e.button === 0
            && el.getAttribute('href') !== '#' && !el.getAttribute('href').startsWith('#') && /^https?:$/.test(el.protocol);
        if (nav) {
            e.preventDefault();
            let gone = false;
            const go = () => { if (!gone) { gone = true; window.location.href = el.href; } };
            say(label, go, greeted);
            setTimeout(go, Math.min(2500, 700 + label.length * 60) + (greeted ? 4000 : 0));
        } else {
            say(label, null, greeted);
        }
    }, true);
    // pengguna keyboard: bacakan elemen yang mendapat fokus dengan Tab
    document.addEventListener('keydown', (e) => { if (voiceOn && !welcomed && e.key !== 'Tab') playWelcome(); });
    document.addEventListener('focusin', (e) => {
        if (!voiceOn) return;
        const el = e.target.closest && e.target.closest(CLICKABLE + ', input, select, textarea');
        if (!el || !el.matches(':focus-visible')) return;
        playWelcome() || say(labelOf(el) || (el.labels && el.labels[0] ? el.labels[0].innerText : ''));
    });

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
            if (voiceId()) u.voice = voiceId();
            u.onend = stopSpeech;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
            speaking = true;
            if (srLabel) srLabel.textContent = 'Hentikan Pembacaan';
        },
        'voice': () => {
            voiceOn = !voiceOn;
            store.set('a11y-voice', voiceOn ? '1' : '0');
            syncVoice();
            if (voiceOn) { say('Suara panduan diaktifkan'); } else { window.speechSynthesis && window.speechSynthesis.cancel(); announce('Suara panduan dinonaktifkan'); }
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
