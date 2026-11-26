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
        const hint = document.getElementById('selectHint');
        if (hint) hint.hidden = !voiceOn;
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
    // halaman tujuan setelah klik menu: umumkan nama halaman yang dibuka
    const arrive = session.get('a11y-arrive');
    if (arrive !== null) {
        try { sessionStorage.removeItem('a11y-arrive'); } catch (e) { /* abaikan */ }
        if (voiceOn) setTimeout(() => say(arrive), 150);
    }
    if (voiceOn && !welcomed && arrive === null) {
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
        if (!el || el.closest('[data-a11y], [data-no-speak]')) { playWelcome(); return; }
        const label = labelOf(el);
        const greeted = playWelcome();
        if (!label) return;
        // tautan pindah halaman di tab yang sama: tunggu ucapan selesai agar tidak terpotong (maks. 2,5 detik)
        const nav = !e.defaultPrevented && el.matches('a[href]') && !el.target && !el.hasAttribute('download') && !el.matches('[data-preview], [data-bs-toggle]')
            && !e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey && e.button === 0
            && el.getAttribute('href') !== '#' && !el.getAttribute('href').startsWith('#') && /^https?:$/.test(el.protocol);
        if (nav) {
            // langsung pindah halaman tanpa menunggu; ucapan dimulai sekarang dan diulang di halaman tujuan
            say(label, null, greeted);
            session.set('a11y-arrive', (greeted ? welcomeText + '. ' : '') + label);
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

    // ---------- Blok (sorot) teks → langsung dibacakan (bila Suara Panduan aktif) ----------
    // teks panjang dipecah per kalimat: Chrome menghentikan satu ucapan yang terlalu panjang
    const sayLong = (text) => {
        const parts = [];
        text.split(/(?<=[.!?;:])\s+/).forEach((s) => {
            while (s.length > 220) {
                const cut = s.lastIndexOf(' ', 220) > 80 ? s.lastIndexOf(' ', 220) : 220;
                parts.push(s.slice(0, cut)); s = s.slice(cut).trim();
            }
            if (s) parts.push(s);
        });
        parts.forEach((p, i) => say(p, null, i > 0));
    };
    let lastSel = '';
    let selTimer = null;
    const readSelection = () => {
        if (!voiceOn || !window.getSelection) return;
        const s = window.getSelection();
        if (!s || s.isCollapsed || !s.rangeCount) { lastSel = ''; return; }
        const n = s.anchorNode && (s.anchorNode.nodeType === 1 ? s.anchorNode : s.anchorNode.parentElement);
        if (n && n.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"], .a11y-widget, [data-no-speak]')) return;
        const text = String(s).replace(/\s+/g, ' ').trim();
        if (text.length < 2 || text === lastSel) return;
        lastSel = text;
        if (!welcomed) { welcomed = true; session.set('a11y-welcomed', '1'); } // jangan potong dengan sambutan
        if (speaking) stopSpeech();
        sayLong(text.slice(0, 5000));
    };
    if (canSpeak) {
        document.addEventListener('selectionchange', () => {
            const s = window.getSelection && window.getSelection();
            if (!s || s.isCollapsed) lastSel = ''; // blok yang sama boleh dibacakan lagi setelah dilepas
            clearTimeout(selTimer);
            selTimer = setTimeout(readSelection, 700); // tunggu sampai selesai memblok (layar sentuh/keyboard)
        });
        document.addEventListener('pointerup', () => { clearTimeout(selTimer); selTimer = setTimeout(readSelection, 150); });
    }

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
    window.addEventListener('pagehide', () => { if (speaking) stopSpeech(); }); // ucapan nama menu dibiarkan selesai

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

    // ---------- Popup pembuka (Pengaturan → Popup Pembuka) ----------
    const pop = document.getElementById('sitePopup');
    if (pop && typeof pop.showModal === 'function') {
        const k = 'popup-' + pop.dataset.key;
        const freq = pop.dataset.freq || 'session';
        const today = new Date().toISOString().slice(0, 10);
        const seen = freq === 'always' ? false : freq === 'day' ? store.get(k) === today : session.get(k) === '1';
        if (!seen) {
            setTimeout(() => {
                if (document.querySelector('dialog[open]')) return; // jangan menumpuk dialog lain
                pop.showModal();
                const c = pop.querySelector('.site-popup-close'); c && c.focus();
            }, 400);
            const mark = () => { if (freq === 'day') store.set(k, today); else if (freq === 'session') session.set(k, '1'); };
            pop.addEventListener('close', mark);
            pop.querySelectorAll('[data-popup-close]').forEach((b) => b.addEventListener('click', () => pop.close()));
            pop.addEventListener('click', (e) => { if (e.target === pop) pop.close(); }); // klik di luar gambar
        }
    }

    // ---------- Tahun berjalan ----------
    document.querySelectorAll('[data-year]').forEach((el) => { el.textContent = new Date().getFullYear(); });

    // ---------- Daftar Unduhan: saring baris sesuai kata yang diketik ----------
    // (berlaku di semua tab; jumlah di tiap tab ikut diperbarui, tab pertama yang berisi hasil dibuka otomatis)
    document.querySelectorAll('input[data-dl-filter]').forEach((inp) => {
        const box = document.getElementById(inp.dataset.dlFilter);
        if (!box) return;
        const panes = [...box.querySelectorAll('[data-dl-pane]')];
        const sec = box.closest('.dl-list');
        const tabs = sec ? [...sec.querySelectorAll('.dl-tabs .nav-link')] : [];
        inp.addEventListener('input', () => {
            const q = inp.value.trim().toLowerCase();
            const hits = panes.map((p, i) => {
                let n = 0;
                p.querySelectorAll('tr[data-dl-row]').forEach((r) => { const hit = !q || r.textContent.toLowerCase().includes(q); r.hidden = !hit; if (hit) n++; });
                const empty = p.querySelector('.dl-empty');
                if (empty) empty.hidden = n > 0;
                const c = tabs[i] && tabs[i].querySelector('[data-dl-count]');
                if (c) c.textContent = n;
                return n;
            });
            const active = tabs.findIndex((t) => t.classList.contains('active'));
            if (q && tabs.length && active >= 0 && !hits[active]) {
                const first = hits.findIndex((n) => n > 0);
                if (first >= 0 && window.bootstrap) bootstrap.Tab.getOrCreateInstance(tabs[first]).show();
            }
        });
    });

    // ---------- Foto dapat diklik untuk dilihat besar (Pengaturan → Tampilan) ----------
    if (document.body.hasAttribute('data-img-zoom') && window.bootstrap) {
        const IMG_FILE = /\.(jpe?g|png|gif|webp|avif)(\?.*)?$/i;
        const main = document.getElementById('main-content');
        const SKIP = 'header, nav, footer, .modal, .no-zoom, [data-no-zoom], .btn, .bi, .navbar, .accordion-button';
        // foto yang layak diperbesar: di konten utama, bukan ikon/logo kecil
        const zoomable = (img) => img && main && main.contains(img) && !img.closest(SKIP)
            && (img.naturalWidth || img.width) >= 200 && (img.naturalHeight || img.height) >= 120;
        const fullSrc = (img) => {
            const a = img.closest('a[href]');
            if (a && IMG_FILE.test(a.getAttribute('href'))) return a.href;       // tautan ke berkas gambar aslinya
            if (img.srcset) {                                                   // ambil yang terbesar
                const best = img.srcset.split(',').map((c) => c.trim().split(/\s+/))
                    .sort((x, y) => parseInt(y[1] || '0', 10) - parseInt(x[1] || '0', 10))[0];
                if (best && best[0]) return new URL(best[0], location.href).href;
            }
            return img.currentSrc || img.src;
        };
        // foto yang ditaut ke halaman lain (bukan berkas gambar) tetap membuka tautannya
        const linkedAway = (img) => { const a = img.closest('a[href]'); return a && !IMG_FILE.test(a.getAttribute('href')); };

        let modal; let pic; let cap; let prev; let next; let group = []; let pos = 0;
        const build = () => {
            const el = document.createElement('div');
            el.className = 'modal fade imgbox';
            el.tabIndex = -1;
            el.setAttribute('aria-label', 'Foto ukuran besar');
            el.setAttribute('aria-modal', 'true');
            el.setAttribute('role', 'dialog');
            el.innerHTML = '<div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down imgbox-dialog"><div class="modal-content">'
                + '<button type="button" class="btn-close btn-close-white imgbox-close" data-bs-dismiss="modal" aria-label="Tutup"></button>'
                + '<button type="button" class="imgbox-nav imgbox-prev" aria-label="Foto sebelumnya"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>'
                + '<img class="imgbox-pic" alt="">'
                + '<button type="button" class="imgbox-nav imgbox-next" aria-label="Foto berikutnya"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>'
                + '<div class="imgbox-cap"></div></div></div>';
            document.body.appendChild(el);
            pic = el.querySelector('.imgbox-pic');
            cap = el.querySelector('.imgbox-cap');
            prev = el.querySelector('.imgbox-prev');
            next = el.querySelector('.imgbox-next');
            prev.addEventListener('click', () => show(pos - 1));
            next.addEventListener('click', () => show(pos + 1));
            pic.addEventListener('click', () => { if (group.length > 1) show(pos + 1); });
            el.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') show(pos - 1);
                if (e.key === 'ArrowRight') show(pos + 1);
            });
            let x0 = null; // geser di HP
            el.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; }, { passive: true });
            el.addEventListener('touchend', (e) => {
                if (x0 === null) return;
                const dx = e.changedTouches[0].clientX - x0; x0 = null;
                if (Math.abs(dx) > 50) show(pos + (dx < 0 ? 1 : -1));
            });
            modal = bootstrap.Modal.getOrCreateInstance(el);
        };
        const show = (i) => {
            if (!group.length) return;
            pos = (i + group.length) % group.length;
            const img = group[pos];
            pic.src = fullSrc(img);
            pic.alt = img.alt || '';
            const fig = img.closest('figure');
            const fc = fig && fig.querySelector('figcaption');
            const text = (fc ? fc.textContent : '') || img.alt || '';
            cap.textContent = text.trim() + (group.length > 1 ? (text.trim() ? ' · ' : '') + (pos + 1) + ' / ' + group.length : '');
            cap.hidden = cap.textContent === '';
            prev.hidden = next.hidden = group.length < 2;
        };
        const open = (img) => {
            if (!modal) build();
            // kelompok: foto lain di wadah yang sama (slider, isi berita/halaman, galeri) agar bisa digeser
            const box = img.closest('.carousel, article, .post-content, .page-content, .gallery, .row, section') || main;
            group = [...box.querySelectorAll('img')].filter((x) => zoomable(x) && !linkedAway(x));
            if (!group.includes(img)) group = [img];
            show(group.indexOf(img));
            modal.show();
        };

        main && main.addEventListener('click', (e) => {
            if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) return;
            let img = e.target.closest('img');
            // slider Beranda: tulisan menutupi foto — klik di area kosong slide memperbesar fotonya
            if (!img) {
                const slide = e.target.closest('.carousel-item.has-img');
                if (slide && !e.target.closest('a, button, input, select, textarea, label')) img = slide.querySelector('img');
            }
            if (!img || !zoomable(img) || linkedAway(img)) return;
            e.preventDefault();
            open(img);
        });
        // penanda kursor (hanya untuk foto yang benar-benar bisa diperbesar)
        const mark = (img) => { if (zoomable(img) && !linkedAway(img)) { img.classList.add('zoomable'); const s = img.closest('.carousel-item.has-img'); if (s) s.classList.add('zoomable'); } };
        main && main.querySelectorAll('img').forEach((img) => (img.complete ? mark(img) : img.addEventListener('load', () => mark(img), { once: true })));
    }
})();
