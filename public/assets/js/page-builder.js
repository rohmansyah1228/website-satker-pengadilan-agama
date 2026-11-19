/* Penyusun isi halaman berbasis blok: teks, tabel, gambar, sematan. Tanpa handler inline. */
(function () {
    'use strict';

    const list = document.getElementById('pbList');
    if (!list) return;
    const form = list.closest('form');
    const out = document.getElementById('pbJson');
    const cfg = JSON.parse(document.getElementById('pbConfig').textContent);
    const initial = JSON.parse(document.getElementById('pbData').textContent || '[]');
    const media = list.dataset.media || '/';
    const live = document.getElementById('adminLive');
    const MAX_COLS = 12;
    let dirty = false;
    let seq = 0;

    const say = (m) => { if (live) { live.textContent = ''; setTimeout(() => { live.textContent = m; }, 30); } };
    const nid = () => 'k' + Date.now().toString(36) + (seq++).toString(36);
    const isUrl = (v) => /^(https?:\/\/\S+|\/(?!\/)\S*|www\.\S+)$/i.test(v.trim());

    /** h('tag', {attr}, ...anak) */
    function h(tag, attrs, ...kids) {
        const el = document.createElement(tag);
        Object.entries(attrs || {}).forEach(([k, v]) => {
            if (v === null || v === undefined || v === false) return;
            if (k === 'class') el.className = v;
            else if (k === 'text') el.textContent = v;
            else if (k.startsWith('on') && typeof v === 'function') el.addEventListener(k.slice(2), v);
            else el.setAttribute(k, v === true ? '' : v);
        });
        kids.flat().forEach((c) => { if (c !== null && c !== undefined && c !== false) el.append(c.nodeType ? c : document.createTextNode(String(c))); });
        return el;
    }
    const icon = (n) => h('i', { class: 'bi ' + n, 'aria-hidden': 'true' });
    const iconBtn = (ic, label, fn, cls) => h('button', { type: 'button', class: 'btn btn-sm ' + (cls || 'btn-light border'), title: label, 'aria-label': label, onclick: fn }, icon(ic));
    let fieldSeq = 0;
    function field(label, input, help) {
        const id = input.id || (input.id = 'pbf' + (fieldSeq++));
        return h('div', { class: 'mb-2' }, h('label', { for: id, class: 'form-label small fw-semibold mb-1', text: label }), input, help ? h('div', { class: 'form-text', text: help }) : null);
    }
    function select(opts, val) {
        const s = h('select', { class: 'form-select form-select-sm' });
        Object.entries(opts).forEach(([k, l]) => s.append(h('option', { value: k, selected: k === val }, l)));
        return s;
    }

    // ------------------------------------------------------------ pengenal tautan (cermin PageBlocks::previewUrl)
    function preview(u) {
        let p;
        try { p = new URL(u, location.href); } catch (e) { return null; }
        const host = p.hostname.toLowerCase(), path = p.pathname;
        let m;
        if (host === 'drive.google.com') {
            if ((m = path.match(/\/file\/d\/([\w-]+)/))) return { src: 'https://drive.google.com/file/d/' + m[1] + '/preview', kind: 'Berkas Google Drive' };
            if ((m = path.match(/\/folders\/([\w-]+)/))) return { src: 'https://drive.google.com/embeddedfolderview?id=' + m[1] + '#list', kind: 'Folder Google Drive' };
            const id = p.searchParams.get('id');
            return id ? { src: 'https://drive.google.com/file/d/' + id + '/preview', kind: 'Berkas Google Drive' } : null;
        }
        if (host === 'docs.google.com') {
            if ((m = path.match(/^\/forms\/(?:u\/\d+\/)?d\/(e\/)?([\w-]+)/))) return { src: 'https://docs.google.com/forms/d/' + (m[1] || '') + m[2] + '/viewform?embedded=true', kind: 'Google Form' };
            if ((m = path.match(/^\/(document|spreadsheets|presentation)\/(?:u\/\d+\/)?d\/(e\/)?([\w-]+)/))) {
                const names = { document: 'Google Docs', spreadsheets: 'Google Sheets', presentation: 'Google Slides' };
                const src = m[2] ? 'https://docs.google.com/' + m[1] + '/d/e/' + m[3] + '/' + (m[1] === 'presentation' ? 'embed' : 'pubhtml?widget=true') : 'https://docs.google.com/' + m[1] + '/d/' + m[3] + '/preview';
                return { src, kind: names[m[1]] };
            }
            return null;
        }
        if (/^(www\.|m\.)?youtube\.com$|^youtu\.be$/.test(host)) {
            const id = host === 'youtu.be' ? path.slice(1) : (p.searchParams.get('v') || ((m = path.match(/^\/(?:embed|shorts|live)\/([\w-]+)/)) ? m[1] : ''));
            return /^[\w-]{6,20}$/.test(id) ? { src: 'https://www.youtube-nocookie.com/embed/' + id, kind: 'Video YouTube' } : null;
        }
        if (/\.pdf$/i.test(path)) return { src: p.href, kind: 'Berkas PDF' };
        return null;
    }
    function embedInfo(u) {
        const v = u.trim();
        if (!v) return null;
        const full = /^https?:\/\//i.test(v) || v.startsWith('/') ? v : 'https://' + v;
        if (/^https?:\/\/(www\.)?google\.[a-z.]+\/maps\/embed/i.test(full)) return { src: full, kind: 'Google Maps' };
        return preview(full) || { src: full, kind: 'Halaman website' };
    }

    // ------------------------------------------------------------ cangkang blok
    function shell(type, body, api) {
        const t = cfg.types[type];
        const sum = h('span', { class: 'pb-sum text-secondary small text-truncate' });
        const bodyWrap = h('div', { class: 'pb-body' }, body);
        const handle = h('button', { type: 'button', class: 'btn btn-sm pb-handle', title: 'Seret untuk memindah (atau pakai panah)', 'aria-label': 'Pindahkan blok ' + t.label }, icon('bi-grip-vertical'));
        const collapse = iconBtn('bi-chevron-up', 'Ciutkan/bentangkan', () => {
            const c = el.classList.toggle('pb-collapsed');
            collapse.firstChild.className = 'bi ' + (c ? 'bi-chevron-down' : 'bi-chevron-up');
            collapse.setAttribute('aria-expanded', c ? 'false' : 'true');
            refreshSum();
        });
        collapse.setAttribute('aria-expanded', 'true');

        const insertMenu = h('div', { class: 'dropdown d-inline-block' },
            h('button', { type: 'button', class: 'btn btn-sm btn-light border', 'data-bs-toggle': 'dropdown', 'aria-expanded': 'false', title: 'Sisipkan blok di bawah ini', 'aria-label': 'Sisipkan blok di bawah ini' }, icon('bi-plus-lg')),
            h('ul', { class: 'dropdown-menu dropdown-menu-end' }, Object.entries(cfg.types).map(([k, v]) =>
                h('li', {}, h('button', { type: 'button', class: 'dropdown-item', onclick: () => add(k, {}, el) }, icon(v.icon), ' ', v.label)))));

        const el = h('section', { class: 'pb-block', 'data-type': type, 'aria-label': 'Blok ' + t.label },
            h('div', { class: 'pb-head' },
                handle,
                h('span', { class: 'pb-type' }, icon(t.icon), ' ', h('strong', { text: t.label })),
                sum,
                h('div', { class: 'pb-tools' },
                    iconBtn('bi-arrow-up', 'Naikkan', () => move(el, -1)),
                    iconBtn('bi-arrow-down', 'Turunkan', () => move(el, 1)),
                    insertMenu,
                    iconBtn('bi-copy', 'Gandakan', () => { const d = el._pb.get(); delete d.key; if (d.type === 'image') { d.file = null; } add(type, d, el); say('Blok digandakan'); }),
                    collapse,
                    iconBtn('bi-trash', 'Hapus blok', () => remove(el), 'btn-outline-danger'))),
            bodyWrap);
        const refreshSum = () => { sum.textContent = api.summary ? api.summary() : ''; };
        el._pb = api;
        el._refresh = refreshSum;

        // seret-lepas memakai pegangan
        handle.addEventListener('pointerdown', () => {
            el.draggable = true;
            editors('detach'); // editor berbasis iframe harus dilepas sebelum blok dipindah
            window.addEventListener('pointerup', () => setTimeout(() => { if (!list.querySelector('.pb-dragging')) editors('attach'); }, 0), { once: true });
        });
        handle.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') { e.preventDefault(); move(el, e.key === 'ArrowUp' ? -1 : 1); handle.focus(); }
        });
        el.addEventListener('dragstart', (e) => { el.classList.add('pb-dragging'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', ''); } catch (x) { /* abaikan */ } });
        el.addEventListener('dragend', () => { el.draggable = false; el.classList.remove('pb-dragging'); editors('attach'); dirty = true; renumber(); });
        document.addEventListener('pointerup', () => { if (!el.classList.contains('pb-dragging')) el.draggable = false; });
        refreshSum();
        return el;
    }

    list.addEventListener('dragover', (e) => {
        const dragging = list.querySelector('.pb-dragging');
        if (!dragging) return;
        e.preventDefault();
        const after = [...list.querySelectorAll('.pb-block:not(.pb-dragging)')].find((b) => {
            const r = b.getBoundingClientRect();
            return e.clientY < r.top + r.height / 2;
        });
        if (after) { if (dragging.nextElementSibling !== after) list.insertBefore(dragging, after); }
        else if (list.lastElementChild !== dragging) list.append(dragging);
    });

    const editors = (fn) => [...list.children].forEach((b) => b._editor && b._editor[fn]());
    function move(el, dir) {
        const sib = dir < 0 ? el.previousElementSibling : el.nextElementSibling;
        if (!sib) return;
        editors('detach');
        dir < 0 ? list.insertBefore(el, sib) : list.insertBefore(sib, el);
        editors('attach');
        dirty = true;
        renumber();
        el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        say('Blok dipindah ke urutan ' + ([...list.children].indexOf(el) + 1));
    }
    function remove(el) {
        if (!window.confirm('Hapus blok ' + cfg.types[el.dataset.type].label.toLowerCase() + ' ini?')) return;
        const next = el.nextElementSibling || el.previousElementSibling;
        el.remove();
        dirty = true;
        renumber();
        say('Blok dihapus');
        const f = next ? next.querySelector('.pb-handle') : list.parentElement.querySelector('[data-pb-add]');
        f && f.focus();
    }
    function renumber() {
        const blocks = [...list.children];
        blocks.forEach((b, i) => { b.dataset.no = i + 1; });
        list.classList.toggle('pb-empty', blocks.length === 0);
    }

    // ------------------------------------------------------------ TEKS
    function textBlock(d) {
        const ta = h('textarea', { class: 'form-control', rows: 10, 'aria-label': 'Isi teks' });
        ta.value = d.html || '';
        const wrap = h('div', { class: 'pb-text' }, ta);
        let el = null;
        let api;
        if (window.SatkerEditor) {
            api = window.SatkerEditor.init(ta, { height: 420, onChange: () => { dirty = true; el && el._refresh(); } });
        } else {
            ta.addEventListener('input', () => { dirty = true; });
            api = { getContent: () => ta.value, focus: () => ta.focus(), text: () => ta.value.replace(/<[^>]+>/g, ' '), detach() {}, attach() {} };
        }
        el = shell('text', wrap, {
            get: () => { const html = api.getContent(); const t = document.createElement('div'); t.innerHTML = html; return { type: 'text', html: (t.textContent || '').trim() === '' && !/<(img|iframe|table|hr)/i.test(html) ? '' : html }; },
            summary: () => api.text().trim().slice(0, 80)
        });
        el._focus = () => api.focus();
        el._editor = api;
        return el;
    }

    // ------------------------------------------------------------ TABEL
    function parseTsv(text) {
        const rows = [];
        let row = [], cell = '', q = false;
        const t = text.replace(/\r\n?/g, '\n');
        for (let i = 0; i < t.length; i++) {
            const c = t[i];
            if (q) {
                if (c === '"' && t[i + 1] === '"') { cell += '"'; i++; }
                else if (c === '"') q = false;
                else cell += c;
            } else if (c === '"' && cell === '') q = true;
            else if (c === '\t') { row.push(cell); cell = ''; }
            else if (c === '\n') { row.push(cell); rows.push(row); row = []; cell = ''; }
            else cell += c;
        }
        if (cell !== '' || row.length) { row.push(cell); rows.push(row); }
        return rows.map((r) => r.map((v) => v.trim()));
    }

    function tableBlock(d) {
        const st = {
            heading: d.heading || '',
            numbered: d.numbered !== undefined ? !!d.numbered : true,
            cols: (d.cols && d.cols.length ? d.cols : [{ label: 'Uraian', kind: 'text' }, { label: 'Dokumen', kind: 'link', button: 'Lihat' }])
                .map((c) => ({ label: c.label || '', kind: c.kind === 'link' ? 'link' : 'text', button: c.button || 'Lihat', open: c.open || 'popup', newtab: c.newtab !== false })),
            rows: (d.rows && d.rows.length ? d.rows : [['', ''], ['', '']]).map((r) => [...r])
        };
        let el = null;
        const fit = () => st.rows.forEach((r) => { while (r.length < st.cols.length) r.push(''); r.length = st.cols.length; });
        fit();

        const heading = h('input', { type: 'text', class: 'form-control form-control-sm', maxlength: 200, placeholder: 'mis. Profil Pejabat & Pegawai', value: st.heading, oninput: (e) => { st.heading = e.target.value; dirty = true; el._refresh(); } });
        const numbered = h('input', { type: 'checkbox', class: 'form-check-input', id: 'pbn' + nid(), checked: st.numbered, onchange: (e) => { st.numbered = e.target.checked; dirty = true; } });
        const gridWrap = h('div', { class: 'pb-grid-wrap' });

        function markUrl(inp) {
            const v = inp.value.trim();
            inp.classList.toggle('is-invalid', v !== '' && !isUrl(v));
            const pv = v && isUrl(v) ? preview(/^www\./i.test(v) ? 'https://' + v : v) : null;
            inp.title = v === '' ? '' : (pv ? pv.kind + ' — dibuka sebagai pratinjau (popup)' : (isUrl(v) ? 'Tautan biasa' : 'Tautan harus diawali https://'));
        }

        function pasteInto(r0, c0, text) {
            const data = parseTsv(text);
            if (!data.length) return;
            const width = Math.min(MAX_COLS, c0 + Math.max(...data.map((r) => r.length)));
            while (st.cols.length < width) st.cols.push({ label: 'Kolom ' + (st.cols.length + 1), kind: 'text', button: 'Lihat', open: 'popup', newtab: true });
            while (st.rows.length < r0 + data.length) st.rows.push([]);
            fit();
            data.forEach((r, ri) => r.forEach((v, ci) => { if (c0 + ci < st.cols.length) st.rows[r0 + ri][c0 + ci] = v; }));
            autoLinkCols();
            dirty = true;
            render();
            say(data.length + ' baris ditempel');
        }
        function autoLinkCols() {
            st.cols.forEach((c, ci) => {
                const vals = st.rows.map((r) => (r[ci] || '').trim()).filter(Boolean);
                if (c.kind === 'text' && vals.length && vals.every((v) => /^(https?:\/\/|\/(?!\/))\S+$/i.test(v))) { c.kind = 'link'; c.button = c.button || 'Lihat'; }
            });
        }

        function render(focus) {
            fit();
            const thead = h('tr', {},
                h('th', { class: 'pb-rownum', scope: 'col' }, '#'),
                st.cols.map((c, ci) => {
                    const lab = h('input', { type: 'text', class: 'form-control form-control-sm fw-semibold', value: c.label, placeholder: 'Judul kolom', 'aria-label': 'Judul kolom ' + (ci + 1), oninput: (e) => { c.label = e.target.value; dirty = true; } });
                    const kind = select({ text: 'Teks', link: 'Tombol tautan' }, c.kind);
                    kind.setAttribute('aria-label', 'Jenis kolom ' + (ci + 1));
                    kind.addEventListener('change', () => { c.kind = kind.value; dirty = true; render(); });
                    const btn = c.kind === 'link' ? h('input', { type: 'text', class: 'form-control form-control-sm', value: c.button, maxlength: 30, placeholder: 'Lihat', 'aria-label': 'Tulisan tombol kolom ' + (ci + 1), title: 'Tulisan pada tombol', oninput: (e) => { c.button = e.target.value; dirty = true; } }) : null;
                    let openOpts = null;
                    if (c.kind === 'link') {
                        c.open = c.open || 'popup';
                        if (c.newtab === undefined) c.newtab = true;
                        const open = select({ popup: 'Tampilkan di halaman ini (popup)', tab: 'Buka di tab baru', same: 'Pindah ke halaman tujuan' }, c.open);
                        open.setAttribute('aria-label', 'Cara membuka tautan kolom ' + (ci + 1));
                        open.title = 'Cara membuka tautan';
                        const nt = h('input', { type: 'checkbox', class: 'form-check-input', id: 'pbnt' + nid(), checked: c.newtab });
                        nt.addEventListener('change', () => { c.newtab = nt.checked; dirty = true; });
                        const ntWrap = h('div', { class: 'form-check small mb-0', hidden: c.open !== 'popup' }, nt, h('label', { class: 'form-check-label', for: nt.id, text: 'Tampilkan tombol "Buka di tab baru"' }));
                        open.addEventListener('change', () => { c.open = open.value; ntWrap.hidden = c.open !== 'popup'; dirty = true; });
                        openOpts = h('div', { class: 'd-flex flex-column gap-1' }, open, ntWrap);
                    }
                    return h('th', { scope: 'col', class: c.kind === 'link' ? 'pb-col-link' : '' },
                        h('div', { class: 'pb-colhead' }, lab, h('div', { class: 'd-flex gap-1' }, kind, btn), openOpts,
                            h('div', { class: 'pb-coltools' },
                                iconBtn('bi-arrow-left', 'Geser kolom ke kiri', () => { if (ci > 0) { swapCol(ci, ci - 1); } }),
                                iconBtn('bi-arrow-right', 'Geser kolom ke kanan', () => { if (ci < st.cols.length - 1) { swapCol(ci, ci + 1); } }),
                                iconBtn('bi-x-lg', 'Hapus kolom', () => {
                                    if (st.cols.length < 2) return;
                                    if (st.rows.some((r) => (r[ci] || '') !== '') && !window.confirm('Hapus kolom “' + (c.label || ci + 1) + '” beserta isinya?')) return;
                                    st.cols.splice(ci, 1); st.rows.forEach((r) => r.splice(ci, 1)); dirty = true; render();
                                }, 'btn-outline-danger'))));
                }),
                h('th', { class: 'pb-rowtools', scope: 'col' }, h('span', { class: 'visually-hidden', text: 'Aksi baris' })));

            const tbody = h('tbody');
            st.rows.forEach((r, ri) => {
                tbody.append(h('tr', {},
                    h('td', { class: 'pb-rownum' }, String(ri + 1)),
                    st.cols.map((c, ci) => {
                        const inp = c.kind === 'link'
                            ? h('input', { type: 'text', inputmode: 'url', class: 'form-control form-control-sm font-monospace pb-url', value: r[ci] || '', placeholder: 'https://drive.google.com/…', spellcheck: 'false' })
                            : h('textarea', { class: 'form-control form-control-sm', rows: 1, placeholder: '' });
                        if (c.kind !== 'link') inp.value = r[ci] || '';
                        inp.setAttribute('aria-label', (c.label || 'Kolom ' + (ci + 1)) + ', baris ' + (ri + 1));
                        inp.dataset.r = ri; inp.dataset.c = ci;
                        inp.addEventListener('input', () => { r[ci] = inp.value; dirty = true; if (c.kind === 'link') markUrl(inp); });
                        inp.addEventListener('paste', (e) => {
                            const t = (e.clipboardData || window.clipboardData).getData('text');
                            if (/[\t\n]/.test(t.trim())) { e.preventDefault(); pasteInto(ri, ci, t); }
                        });
                        inp.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' && !e.shiftKey && (c.kind === 'link' || !e.altKey)) {
                                if (c.kind === 'link' || e.ctrlKey) {
                                    e.preventDefault();
                                    if (ri === st.rows.length - 1) { st.rows.push(st.cols.map(() => '')); dirty = true; render([ri + 1, ci]); }
                                    else gridWrap.querySelector('[data-r="' + (ri + 1) + '"][data-c="' + ci + '"]').focus();
                                }
                            }
                        });
                        if (c.kind === 'link') markUrl(inp);
                        return h('td', {}, inp);
                    }),
                    h('td', { class: 'pb-rowtools' },
                        iconBtn('bi-arrow-up', 'Naikkan baris ' + (ri + 1), () => { if (ri > 0) { [st.rows[ri - 1], st.rows[ri]] = [st.rows[ri], st.rows[ri - 1]]; dirty = true; render([ri - 1, 0]); } }),
                        iconBtn('bi-arrow-down', 'Turunkan baris ' + (ri + 1), () => { if (ri < st.rows.length - 1) { [st.rows[ri + 1], st.rows[ri]] = [st.rows[ri], st.rows[ri + 1]]; dirty = true; render([ri + 1, 0]); } }),
                        iconBtn('bi-x-lg', 'Hapus baris ' + (ri + 1), () => { st.rows.splice(ri, 1); dirty = true; render(st.rows.length ? [Math.min(ri, st.rows.length - 1), 0] : null); }, 'btn-outline-danger'))));
            });
            gridWrap.replaceChildren(h('table', { class: 'table table-sm table-bordered pb-grid mb-0' }, h('thead', {}, thead), tbody));
            if (focus) { const f = gridWrap.querySelector('[data-r="' + focus[0] + '"][data-c="' + focus[1] + '"]'); f && f.focus(); }
            el && el._refresh();
        }
        function swapCol(a, b) {
            [st.cols[a], st.cols[b]] = [st.cols[b], st.cols[a]];
            st.rows.forEach((r) => { [r[a], r[b]] = [r[b], r[a]]; });
            dirty = true; render();
        }

        // panel tempel dari Excel / Google Sheets
        const pasteArea = h('textarea', { class: 'form-control form-control-sm font-monospace', rows: 5, placeholder: 'Salin sel dari Excel / Google Sheets lalu tempel (Ctrl+V) di sini…' });
        const firstHeader = h('input', { type: 'checkbox', class: 'form-check-input', id: 'pbh' + nid(), checked: true });
        const pastePanel = h('div', { class: 'pb-paste border rounded p-2 mt-2', hidden: true },
            field('Data dari spreadsheet', pasteArea),
            h('div', { class: 'form-check mb-2' }, firstHeader, h('label', { class: 'form-check-label small', for: firstHeader.id, text: 'Baris pertama adalah judul kolom' })),
            h('div', { class: 'd-flex flex-wrap gap-2' },
                h('button', { type: 'button', class: 'btn btn-sm btn-pa-primary', onclick: () => applyPaste(true) }, 'Ganti isi tabel'),
                h('button', { type: 'button', class: 'btn btn-sm btn-outline-success', onclick: () => applyPaste(false) }, 'Tambahkan ke bawah'),
                h('button', { type: 'button', class: 'btn btn-sm btn-link', onclick: () => { pastePanel.hidden = true; } }, 'Batal')));
        function applyPaste(replace) {
            let data = parseTsv(pasteArea.value).filter((r) => r.some((v) => v !== ''));
            if (!data.length) { pasteArea.focus(); return; }
            if (replace) {
                const head = firstHeader.checked ? data.shift() : null;
                const w = Math.min(MAX_COLS, Math.max(...(head ? [head] : []).concat(data).map((r) => r.length)));
                // buang kolom "No" bila penomoran otomatis aktif
                let skip = head && st.numbered && /^no\.?$/i.test(head[0] || '') ? 1 : 0;
                st.cols = Array.from({ length: w - skip }, (_, i) => ({ label: head ? (head[i + skip] || '') : 'Kolom ' + (i + 1), kind: 'text', button: 'Lihat' }));
                st.rows = data.map((r) => r.slice(skip, w));
            } else {
                if (firstHeader.checked) data.shift();
                data.forEach((r) => st.rows.push(r.slice(0, st.cols.length)));
            }
            autoLinkCols();
            pasteArea.value = '';
            pastePanel.hidden = true;
            dirty = true;
            render();
            say('Data tabel ditempel: ' + st.rows.length + ' baris');
        }

        const body = h('div', {},
            h('div', { class: 'row g-2 align-items-end mb-2' },
                h('div', { class: 'col-md-8' }, field('Judul tabel (opsional)', heading)),
                h('div', { class: 'col-md-4' }, h('div', { class: 'form-check mb-2' }, numbered, h('label', { class: 'form-check-label small', for: numbered.id, text: 'Nomor urut otomatis (No.)' })))),
            gridWrap,
            h('div', { class: 'd-flex flex-wrap gap-2 mt-2' },
                h('button', { type: 'button', class: 'btn btn-sm btn-outline-success', onclick: () => { st.rows.push(st.cols.map(() => '')); dirty = true; render([st.rows.length - 1, 0]); } }, icon('bi-plus-lg'), ' Baris'),
                h('button', { type: 'button', class: 'btn btn-sm btn-outline-success', onclick: () => {
                    if (st.cols.length >= MAX_COLS) { say('Maksimal ' + MAX_COLS + ' kolom'); return; }
                    st.cols.push({ label: '', kind: 'text', button: 'Lihat' }); dirty = true; render();
                    const ths = gridWrap.querySelectorAll('thead th input'); ths.length && ths[ths.length - 2] && ths[ths.length - 2].focus();
                } }, icon('bi-plus-lg'), ' Kolom'),
                h('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary', onclick: () => { pastePanel.hidden = !pastePanel.hidden; if (!pastePanel.hidden) pasteArea.focus(); } }, icon('bi-clipboard-plus'), ' Tempel dari Excel'),
                h('span', { class: 'form-text ms-auto' }, 'Kolom "Tombol tautan": isi dengan tautan Google Drive/Docs atau halaman lain. Atur cara membukanya di judul kolom.')),
            pastePanel);

        el = shell('table', body, {
            get: () => ({ type: 'table', heading: st.heading, numbered: st.numbered, cols: st.cols, rows: st.rows }),
            summary: () => (st.heading ? st.heading + ' · ' : '') + st.rows.length + ' baris × ' + st.cols.length + ' kolom'
        });
        render();
        el._focus = () => heading.focus();
        return el;
    }

    // ------------------------------------------------------------ GAMBAR
    function imageBlock(d) {
        const st = { src: d.src || '', key: nid(), alt: d.alt || '', caption: d.caption || '', link: d.link || '', size: d.size || 'full', align: d.align || 'center' };
        const img = h('img', { class: 'pb-img-preview rounded border', alt: '', hidden: !st.src, src: st.src ? media + st.src : null });
        const empty = h('div', { class: 'pb-img-empty', hidden: !!st.src }, icon('bi-image'), h('span', { text: 'Belum ada gambar' }));
        const file = h('input', { type: 'file', name: 'block_file[' + st.key + ']', accept: 'image/jpeg,image/png,image/webp', class: 'form-control form-control-sm' });
        file.addEventListener('change', () => {
            const f = file.files[0];
            if (!f) return;
            if (f.size > 5 * 1048576) say('Ukuran gambar besar; batas server mungkin terlampaui');
            img.src = URL.createObjectURL(f); img.hidden = false; empty.hidden = true; dirty = true; el._refresh();
            if (!caption.value && !alt.value) alt.value = st.alt = f.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
        });
        const bind = (inp, k) => { inp.addEventListener('input', () => { st[k] = inp.value; dirty = true; el._refresh(); }); return inp; };
        const alt = bind(h('input', { type: 'text', class: 'form-control form-control-sm', maxlength: 200, value: st.alt }), 'alt');
        const caption = bind(h('input', { type: 'text', class: 'form-control form-control-sm', maxlength: 300, value: st.caption }), 'caption');
        const link = bind(h('input', { type: 'text', inputmode: 'url', class: 'form-control form-control-sm font-monospace', value: st.link, placeholder: 'https://… (opsional)' }), 'link');
        const size = select(cfg.sizes, st.size); size.addEventListener('change', () => { st.size = size.value; dirty = true; });
        const align = select(cfg.aligns, st.align); align.addEventListener('change', () => { st.align = align.value; dirty = true; });

        const body = h('div', { class: 'row g-3' },
            h('div', { class: 'col-md-4' }, h('div', { class: 'pb-img-box' }, img, empty)),
            h('div', { class: 'col-md-8' },
                field(st.src ? 'Ganti gambar' : 'Pilih gambar', file, 'JPG/PNG/WebP. Otomatis diperkecil & dikonversi ke WebP.'),
                field('Teks alternatif (deskripsi gambar)', alt, 'Dibacakan pembaca layar, mis. "Bagan struktur organisasi".'),
                field('Keterangan di bawah gambar (opsional)', caption),
                field('Tautan saat gambar diklik (opsional)', link),
                h('div', { class: 'row g-2' }, h('div', { class: 'col-6' }, field('Ukuran', size)), h('div', { class: 'col-6' }, field('Posisi', align)))));
        const el = shell('image', body, {
            get: () => ({ type: 'image', src: st.src, key: file.files.length ? st.key : '', alt: st.alt, caption: st.caption, link: st.link, size: st.size, align: st.align }),
            summary: () => st.caption || st.alt || (file.files[0] ? file.files[0].name : '')
        });
        el._focus = () => file.focus();
        return el;
    }

    // ------------------------------------------------------------ SEMATAN
    function embedBlock(d) {
        const st = { url: d.url || '', heading: d.heading || '', height: d.height || 640 };
        const badge = h('span', { class: 'badge text-bg-light border ms-1' });
        const frameBox = h('div', { class: 'pb-embed-preview mt-2', hidden: true });
        const url = h('input', { type: 'text', inputmode: 'url', class: 'form-control font-monospace', value: st.url, placeholder: 'https://drive.google.com/file/d/…  atau  https://situs-lain.go.id/halaman', spellcheck: 'false' });
        const heading = h('input', { type: 'text', class: 'form-control form-control-sm', maxlength: 200, value: st.heading, placeholder: 'mis. Laporan Keuangan 2026' });
        const height = h('input', { type: 'number', class: 'form-control form-control-sm', min: 200, max: 2000, step: 20, value: st.height });
        const pvBtn = h('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary', onclick: () => togglePreview() }, icon('bi-eye'), ' Pratinjau');

        function info() {
            const i = embedInfo(st.url);
            url.classList.toggle('is-invalid', st.url.trim() !== '' && !isUrl(st.url));
            badge.textContent = i && isUrl(st.url) ? i.kind : '';
            badge.hidden = !badge.textContent;
            if (!frameBox.hidden) showPreview();
        }
        function showPreview() {
            const i = embedInfo(st.url);
            if (!i || !isUrl(st.url)) { frameBox.hidden = true; return; }
            const old = frameBox.querySelector('iframe');
            if (old && old.dataset.src === i.src) { old.style.height = Math.min(st.height, 600) + 'px'; return; }
            frameBox.replaceChildren(h('iframe', { src: i.src, 'data-src': i.src, title: 'Pratinjau sematan', loading: 'lazy', style: 'height:' + Math.min(st.height, 600) + 'px', sandbox: 'allow-scripts allow-same-origin allow-forms allow-popups allow-presentation', referrerpolicy: 'strict-origin-when-cross-origin' }),
                h('p', { class: 'form-text mb-0', text: 'Jika kotak di atas kosong atau menampilkan pesan penolakan, situs tersebut tidak mengizinkan ditampilkan di situs lain. Pengunjung tetap mendapat tautan "Buka di tab baru".' }));
        }
        function togglePreview() { frameBox.hidden = !frameBox.hidden; if (!frameBox.hidden) showPreview(); else frameBox.replaceChildren(); }

        url.addEventListener('input', () => { st.url = url.value; dirty = true; info(); el._refresh(); });
        heading.addEventListener('input', () => { st.heading = heading.value; dirty = true; el._refresh(); });
        height.addEventListener('input', () => { st.height = parseInt(height.value, 10) || 640; dirty = true; if (!frameBox.hidden) showPreview(); });

        const urlField = field('Alamat yang disematkan', url, 'Bisa: berkas/folder Google Drive, Google Docs/Sheets/Slides/Form, video YouTube, berkas PDF, Google Maps (alamat "embed"), atau halaman website lain.');
        urlField.querySelector('label').append(badge);
        const body = h('div', {},
            urlField,
            h('div', { class: 'row g-2 align-items-end' },
                h('div', { class: 'col-md-7' }, field('Judul (opsional)', heading)),
                h('div', { class: 'col-6 col-md-3' }, field('Tinggi (piksel)', height)),
                h('div', { class: 'col-6 col-md-2 mb-2' }, pvBtn)),
            frameBox);
        const el = shell('embed', body, {
            get: () => ({ type: 'embed', url: st.url, heading: st.heading, height: st.height }),
            summary: () => st.heading || st.url
        });
        info();
        el._focus = () => url.focus();
        return el;
    }

    // ------------------------------------------------------------ umum
    const makers = { text: textBlock, table: tableBlock, image: imageBlock, embed: embedBlock };
    function add(type, data, after) {
        const el = makers[type](data || {});
        if (after) after.after(el); else list.append(el);
        renumber();
        return el;
    }

    initial.forEach((b) => { if (makers[b.type]) add(b.type, b); });
    if (!initial.length) add('text', {});
    renumber();
    dirty = false;

    document.querySelectorAll('[data-pb-add]').forEach((b) => b.addEventListener('click', () => {
        const el = add(b.dataset.pbAdd, {});
        dirty = true;
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        setTimeout(() => el._focus && el._focus(), 250);
        say('Blok ' + cfg.types[b.dataset.pbAdd].label + ' ditambahkan');
    }));
    list.addEventListener('input', () => { dirty = true; });

    form.addEventListener('submit', () => {
        out.value = JSON.stringify([...list.children].map((b) => b._pb.get()));
        dirty = false;
    });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
