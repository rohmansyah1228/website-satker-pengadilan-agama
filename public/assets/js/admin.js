/* Panel Admin — tanpa handler inline */
(function () {
    'use strict';

    // ---------- Sidebar mobile ----------
    const sb = document.getElementById('adminSidebar');
    document.querySelectorAll('[data-toggle-sidebar]').forEach((b) => b.addEventListener('click', () => {
        const open = sb.classList.toggle('open');
        b.setAttribute('aria-expanded', open ? 'true' : 'false');
    }));

    // ---------- Editor teks kaya (Quill, lisensi BSD) ----------
    if (window.Quill) {
        document.querySelectorAll('textarea[data-richtext]').forEach((ta) => {
            const holder = document.createElement('div');
            holder.innerHTML = ta.value;
            ta.hidden = true;
            ta.after(holder);
            const quill = new Quill(holder, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
                        [{ align: [] }],
                        ['blockquote', 'link'],
                        ['clean']
                    ]
                }
            });
            if (ta.id) {
                const lbl = document.querySelector('label[for="' + ta.id + '"]');
                quill.root.setAttribute('aria-label', lbl ? lbl.textContent.trim() : 'Isi');
                if (lbl) lbl.addEventListener('click', () => quill.focus());
            }
            const sync = () => {
                const html = quill.getSemanticHTML().replace(/&nbsp;/g, ' ');
                ta.value = quill.getText().trim() === '' ? '' : html;
            };
            quill.on('text-change', sync);
            ta.form && ta.form.addEventListener('submit', sync);
        });
    }

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
    if (dlg) {
        dlg.addEventListener('close', () => {
            if (dlg.returnValue === 'ok' && pendingForm) {
                pendingForm.dataset.confirmed = '1';
                pendingForm.requestSubmit ? pendingForm.requestSubmit() : pendingForm.submit();
            }
            dlg.returnValue = '';
            pendingForm = null;
        });
    }

    // ---------- Tampilkan/sembunyikan field bergantung pilihan ----------
    const syncToggles = () => {
        document.querySelectorAll('[data-show-when]').forEach((el) => {
            const [name, values] = el.dataset.showWhen.split('=');
            const ctl = document.querySelector('[name="' + name + '"]:checked, select[name="' + name + '"]');
            const show = ctl && values.split(',').includes(ctl.value);
            el.hidden = !show;
            el.querySelectorAll('input, select, textarea').forEach((i) => { i.disabled = !show; });
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
