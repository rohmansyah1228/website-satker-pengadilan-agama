/* Editor teks lengkap (TinyMCE 6, lisensi MIT) dengan tab Editor / Kode HTML / Pratinjau
 * dan baris tombol sisip (Gambar, Dokumen PDF, Tautan Internal, Tabel, Garis).
 * Pemakaian: <textarea data-richtext> otomatis, atau window.SatkerEditor.init(textarea, {height}). */
(function () {
    'use strict';
    if (!window.tinymce) return;

    const meta = document.querySelector('meta[name="satker-admin"]');
    const cfg = meta ? JSON.parse(meta.content) : {};
    const ASSETS = cfg.assets || '/assets/';
    let seq = 0;

    function csrfPair(form) {
        const inp = (form || document).querySelector('input[name="' + (cfg.csrfName || 'csrf_test_name') + '"]') || document.querySelector('input[name="' + (cfg.csrfName || 'csrf_test_name') + '"]');
        return inp ? [inp.name, inp.value] : null;
    }
    function updateCsrf(c) {
        if (!c) return;
        document.querySelectorAll('input[name="' + c.name + '"]').forEach((i) => { i.value = c.hash; });
    }
    async function upload(kind, file) {
        const fd = new FormData();
        fd.append('file', file, file.name || ('gambar.' + ((file.type || 'image/png').split('/')[1] || 'png')));
        const c = csrfPair();
        if (c) fd.append(c[0], c[1]);
        const r = await fetch(cfg.base + 'editor/' + kind, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        let j = {};
        try { j = await r.json(); } catch (e) { /* abaikan */ }
        updateCsrf(j.csrf);
        if (!r.ok || j.error) throw new Error(j.error || ('Unggah gagal (' + r.status + ')'));
        return j;
    }
    function pickFile(accept) {
        return new Promise((resolve) => {
            const i = document.createElement('input');
            i.type = 'file'; i.accept = accept; i.hidden = true;
            i.addEventListener('change', () => { resolve(i.files[0] || null); i.remove(); });
            document.body.append(i);
            i.click();
        });
    }
    let linkCache = null;
    async function linkList() {
        if (linkCache) return linkCache;
        try {
            const r = await fetch(cfg.base + 'editor/tautan', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const rows = await r.json();
            const groups = {};
            rows.forEach((x) => { (groups[x.group] = groups[x.group] || []).push({ title: x.title, value: x.url }); });
            linkCache = Object.entries(groups).map(([title, menu]) => ({ title, menu }));
        } catch (e) { linkCache = []; }
        return linkCache;
    }
    const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    function settings(target, opts) {
        return {
            target,
            base_url: ASSETS + 'vendor/tinymce',
            suffix: '.min',
            language: 'id',
            language_url: ASSETS + 'vendor/tinymce/langs/id.js',
            promotion: false,
            branding: false,
            height: opts.height || 520,
            min_height: 260,
            resize: true,
            menubar: 'edit view insert format table tools',
            plugins: 'advlist anchor autolink charmap code fullscreen image link lists media preview searchreplace table visualblocks wordcount nonbreaking insertdatetime',
            toolbar: [
                'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify',
                'bullist numlist outdent indent | link anchor image media table | subscript superscript charmap hr nonbreaking insertdatetime | removeformat | searchreplace visualblocks | code preview fullscreen'
            ],
            toolbar_mode: 'wrap',
            // Di HP TinyMCE memakai toolbar geser-samping secara bawaan;
            // paksa tombol turun ke bawah (berbaris) agar semuanya terlihat.
            mobile: {
                menubar: 'edit view insert format table tools',
                toolbar_mode: 'wrap',
                toolbar_sticky: false,
                // Tombol yang sering dipakai saja; sisanya ada di menu Edit/Masukkan/Format/Tabel.
                toolbar: 'undo redo blocks bold italic underline forecolor alignleft aligncenter alignright alignjustify bullist numlist outdent indent link image media table removeformat code fullscreen',
                height: (opts.height || 520) + 220
            },
            toolbar_sticky: opts.sticky !== false,
            toolbar_sticky_offset: (document.querySelector('.admin-topbar') || { offsetHeight: 0 }).offsetHeight,
            block_formats: 'Paragraf=p; Judul 2=h2; Judul 3=h3; Judul 4=h4; Judul 5=h5; Kutipan=blockquote; Praformat=pre',
            font_family_formats: 'Inter=Inter,sans-serif; Montserrat=Montserrat,sans-serif; Arial=arial,helvetica,sans-serif; Tahoma=tahoma,arial,sans-serif; Verdana=verdana,geneva,sans-serif; Georgia=georgia,serif; Times New Roman=times new roman,times,serif; Courier New=courier new,courier,monospace',
            font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 28pt 32pt 36pt',
            color_map: ['056E4F', 'Hijau PA', '034D37', 'Hijau tua', 'FF9900', 'Oranye PA', '000000', 'Hitam', '374151', 'Abu tua', '6B7280', 'Abu', 'FFFFFF', 'Putih', 'DC2626', 'Merah', '1D4ED8', 'Biru', '7C3AED', 'Ungu', 'FACC15', 'Kuning', 'E6F3EF', 'Hijau muda', 'FFF3DC', 'Krem'],
            content_css: [ASSETS + 'vendor/fonts/fonts.css'],
            content_style: 'body{font-family:Inter,sans-serif;font-size:16px;line-height:1.7;color:#1f2937;margin:1rem 1.25rem} h2,h3,h4{font-family:Montserrat,sans-serif;color:#056e4f} table{border-collapse:collapse} td,th{padding:.4rem .6rem} img{max-width:100%;height:auto} blockquote{border-left:4px solid #ff9900;margin-left:0;padding-left:1rem;color:#4b5563}',
            convert_urls: false,
            link_default_target: 'tampil',
            link_assume_external_targets: 'https',
            link_title: false,
            link_target_list: [
                { title: 'Tampilkan di halaman ini (popup)', value: 'tampil' },
                { title: 'Tampilkan di halaman ini, tanpa tombol "Buka di tab baru"', value: 'tampil-saja' },
                { title: 'Pindah ke halaman tujuan', value: '' },
                { title: 'Buka di tab baru', value: '_blank' }
            ],
            link_list: (success) => linkList().then(success),
            image_advtab: true,
            image_caption: false,
            image_dimensions: true,
            image_title: true,
            automatic_uploads: true,
            paste_data_images: true,
            images_file_types: 'jpg,jpeg,png,webp',
            images_upload_handler: (blobInfo) => upload('gambar', blobInfo.blob()).then((j) => j.location),
            file_picker_types: 'file image',
            file_picker_callback: (cb, value, m) => {
                const isImg = m.filetype === 'image';
                pickFile(isImg ? 'image/jpeg,image/png,image/webp' : 'application/pdf').then((f) => {
                    if (!f) return;
                    upload(isImg ? 'gambar' : 'pdf', f).then((j) => cb(isImg ? j.location : j.url, isImg ? { alt: f.name.replace(/\.[^.]+$/, '') } : { text: j.name, title: j.name }))
                        .catch((e) => window.tinymce.activeEditor && window.tinymce.activeEditor.notificationManager.open({ text: e.message, type: 'error', timeout: 5000 }));
                });
            },
            media_live_embeds: true,
            table_default_attributes: { border: '1' },
            table_default_styles: { 'border-collapse': 'collapse', width: '100%' },
            table_header_type: 'sectionCells',
            invalid_elements: 'script,style,object,embed,form,input,button',
            setup: opts.setup
        };
    }

    /** Pasang editor pada textarea. Mengembalikan API {getContent, focus, detach, attach}. */
    function init(ta, opts) {
        opts = opts || {};
        const id = ta.id || (ta.id = 'rte' + (seq++));
        const form = ta.form || ta.closest('form');

        // ---- tab Editor / Kode HTML / Pratinjau
        const tabs = document.createElement('div');
        tabs.className = 'rte-tabs';
        tabs.setAttribute('role', 'tablist');
        tabs.setAttribute('aria-label', 'Mode editor');
        const modes = [['editor', 'Editor', 'bi-pencil-square'], ['code', 'Kode HTML', 'bi-code-slash'], ['preview', 'Pratinjau', 'bi-eye']];
        const btns = {};
        modes.forEach(([k, label, ic]) => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'rte-tab'; b.setAttribute('role', 'tab');
            b.innerHTML = '<i class="bi ' + ic + '" aria-hidden="true"></i> ' + label;
            b.addEventListener('click', () => setMode(k));
            btns[k] = b; tabs.append(b);
        });
        const code = document.createElement('textarea');
        code.className = 'form-control font-monospace rte-code';
        code.spellcheck = false;
        code.hidden = true;
        code.setAttribute('aria-label', 'Kode HTML');
        const prev = document.createElement('iframe');
        prev.className = 'rte-preview';
        prev.title = 'Pratinjau isi';
        prev.hidden = true;
        prev.setAttribute('sandbox', 'allow-same-origin');

        // ---- baris tombol sisip
        const bar = document.createElement('div');
        bar.className = 'rte-insertbar';
        const insertBtns = [
            ['bi-image', 'Gambar', () => ed && ed.execCommand('mceImage')],
            ['bi-file-earmark-pdf', 'Dokumen PDF', () => pickFile('application/pdf').then((f) => {
                if (!f || !ed) return;
                const n = ed.notificationManager.open({ text: 'Mengunggah ' + f.name + '…', type: 'info' });
                upload('pdf', f).then((j) => { n.close(); ed.insertContent('<a href="' + esc(j.url) + '" target="_blank" rel="noopener">' + esc(ed.selection.getContent({ format: 'text' }) || j.name) + '</a>&nbsp;'); })
                    .catch((e) => { n.close(); ed.notificationManager.open({ text: e.message, type: 'error', timeout: 5000 }); });
            })],
            ['bi-link-45deg', 'Tautan Halaman/Berita', () => ed && ed.execCommand('mceLink')],
            ['bi-table', 'Tabel', () => ed && ed.execCommand('mceInsertTable', false, { rows: 3, columns: 3, options: { headerRows: 1 } })],
            ['bi-youtube', 'Video / Google Drive', () => ed && ed.execCommand('mceMedia')],
            ['bi-hr', 'Garis Pemisah', () => ed && ed.execCommand('InsertHorizontalRule')]
        ];
        insertBtns.forEach(([ic, label, fn]) => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'btn btn-sm btn-light border';
            b.innerHTML = '<i class="bi ' + ic + '" aria-hidden="true"></i> ' + label;
            b.addEventListener('click', fn);
            bar.append(b);
        });

        ta.before(tabs);
        ta.after(code, prev, bar);

        let ed = null;
        let mode = 'editor';
        const current = () => (mode === 'code' ? code.value : (ed ? ed.getContent() : ta.value));

        function setMode(k) {
            if (k === mode) return;
            const html = current();
            if (k === 'editor' && ed) { ed.setContent(code.value); }
            if (k === 'code') { code.value = html; code.style.height = Math.max(320, (ed ? ed.getContainer().offsetHeight : 400)) + 'px'; }
            if (k === 'preview') {
                const css = [ASSETS + 'vendor/bootstrap/bootstrap.min.css', ASSETS + 'vendor/fonts/fonts.css', ASSETS + 'css/site.css'].map((h) => '<link rel="stylesheet" href="' + h + '">').join('');
                prev.style.height = Math.max(320, (ed ? ed.getContainer().offsetHeight : 400)) + 'px';
                prev.srcdoc = '<!doctype html><html><head><meta charset="utf-8">' + css + '</head><body class="bg-white"><div class="prose p-4">' + html + '</div></body></html>';
            }
            if (ed) ed.getContainer().hidden = k !== 'editor';
            code.hidden = k !== 'code';
            prev.hidden = k !== 'preview';
            bar.hidden = k !== 'editor';
            mode = k;
            Object.entries(btns).forEach(([m, b]) => { b.classList.toggle('active', m === k); b.setAttribute('aria-selected', m === k ? 'true' : 'false'); });
            if (k === 'code') code.focus();
            if (opts.onChange) opts.onChange();
        }
        code.addEventListener('input', () => { ta.value = code.value; if (opts.onChange) opts.onChange(); });
        btns.editor.classList.add('active');
        btns.editor.setAttribute('aria-selected', 'true');

        function attach() {
            if (!ta.isConnected) { setTimeout(attach, 30); return; }
            window.tinymce.init(settings(ta, {
                height: opts.height,
                sticky: opts.sticky,
                setup: (e) => {
                    ed = e;
                    e.on('init', () => {
                        const lbl = document.querySelector('label[for="' + id + '"]');
                        e.getBody().setAttribute('aria-label', lbl ? lbl.textContent.trim() : 'Isi');
                        if (mode !== 'editor') e.getContainer().hidden = true;
                    });
                    e.on('change input undo redo', () => { e.save(); if (opts.onChange) opts.onChange(); });
                }
            }));
        }
        function detach() {
            if (!ed) return;
            ta.value = current();
            ed.remove();
            ed = null;
        }
        if (form) {
            form.addEventListener('submit', () => {
                if (mode === 'code') { ta.value = code.value; if (ed) ed.setContent(code.value); }
                else if (ed) ed.save();
            }, true);
        }
        attach();
        return {
            getContent: current,
            focus: () => { if (mode === 'code') code.focus(); else if (ed) ed.focus(); else setTimeout(() => ed && ed.focus(), 300); },
            text: () => { const d = document.createElement('div'); d.innerHTML = current(); return d.textContent || ''; },
            detach,
            attach: () => { if (!ed) attach(); }
        };
    }

    window.SatkerEditor = { init };
    document.querySelectorAll('textarea[data-richtext]').forEach((ta) => init(ta, { height: parseInt(ta.dataset.richtextHeight || '', 10) || undefined }));
})();
