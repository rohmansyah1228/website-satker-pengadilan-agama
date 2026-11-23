/* Impor berita dari website lama: periksa jumlah, lalu impor bertahap (3 berita per permintaan). */
(function () {
    'use strict';
    const form = document.getElementById('importForm');
    if (!form) return;
    const $ = (id) => document.getElementById(id);
    const rows = $('impRows').querySelector('tbody');
    const base = location.pathname.replace(/\/impor\/?$/, '/impor');
    let stop = false, busy = false;

    const csrfInput = () => form.querySelector('input[name^="csrf"]');
    async function post(path, data) {
        const fd = new FormData();
        const c = csrfInput();
        if (c) fd.append(c.name, c.value);
        ['site', 'since', 'status'].forEach((k) => fd.append(k, form.elements[k].value));
        if ($('impImages').checked) fd.append('images', '1');
        if ($('impInsecure').checked) fd.append('insecure', '1');
        Object.entries(data).forEach(([k, v]) => Array.isArray(v) ? v.forEach((x) => fd.append(k + '[]', x)) : fd.append(k, v));
        let r;
        for (let tries = 0; ; tries++) {
            try {
                r = await fetch(base + '/' + path, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                break;
            } catch (e) {
                if (tries >= 2) throw new Error('Koneksi ke server terputus.');
                await new Promise((ok) => setTimeout(ok, 2000));
            }
        }
        const text = await r.text();
        let j;
        try { j = JSON.parse(text); } catch (e) {
            throw new Error(r.status === 403 ? 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.' : 'Server mengembalikan galat (HTTP ' + r.status + ').');
        }
        if (j.csrf && c) c.value = j.csrf;
        if (j.error) throw new Error(j.error);
        if (!r.ok) throw new Error((j.message || 'Server mengembalikan galat') + ' (HTTP ' + r.status + '). Muat ulang halaman lalu coba lagi.');
        return j;
    }

    const sources = () => [...rows.querySelectorAll('tr')].filter((tr) => tr.querySelector('[data-imp=on]').checked && tr.querySelector('[data-imp=url]').value.trim())
        .map((tr) => ({ tr, url: tr.querySelector('[data-imp=url]').value.trim(), cat: tr.querySelector('[data-imp=cat]').value, catLabel: tr.querySelector('[data-imp=cat]').selectedOptions[0].text }));

    async function listAll() {
        const out = [];
        for (const s of sources()) {
            const cell = s.tr.querySelector('[data-imp=count]');
            cell.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
            phase('Membaca daftar berita: ' + s.url);
            try {
                const j = await post('daftar', { category_url: s.url });
                cell.textContent = j.total.toLocaleString('id-ID') + (j.existing ? ' (' + j.existing.toLocaleString('id-ID') + ' sudah)' : '');
                out.push(Object.assign(s, { items: j.items }));
            } catch (e) {
                cell.innerHTML = '<span class="text-danger">gagal</span>';
                throw e;
            }
        }
        if (!out.length) throw new Error('Centang minimal satu kategori dan isi alamatnya.');
        return out;
    }

    function phase(t) { $('impPhase').textContent = t; }
    const n = { ok: 0, skip: 0, error: 0 };
    function count() {
        $('impOk').textContent = n.ok.toLocaleString('id-ID') + ' dipindah';
        $('impSkip').textContent = n.skip.toLocaleString('id-ID') + ' dilewati';
        $('impErr').textContent = n.error.toLocaleString('id-ID') + ' gagal';
    }
    function log(r) {
        const tr = document.createElement('tr');
        const badge = { ok: ['success', 'Dipindah'], skip: ['secondary', 'Dilewati'], error: ['danger', 'Gagal'] }[r.status] || ['secondary', r.status];
        const title = r.title || r.url;
        tr.innerHTML = '<td><span class="badge text-bg-' + badge[0] + '"></span></td><td></td><td class="text-nowrap"></td><td></td>';
        tr.cells[0].firstChild.textContent = badge[1];
        if (r.view) {
            const a = document.createElement('a'); a.href = r.view; a.target = '_blank'; a.rel = 'noopener'; a.textContent = title; tr.cells[1].append(a);
            const e = document.createElement('a'); e.href = r.edit; e.className = 'ms-2'; e.title = 'Ubah'; e.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Ubah</span>'; tr.cells[1].append(e);
        } else {
            const a = document.createElement('a'); a.href = r.url; a.target = '_blank'; a.rel = 'noopener'; a.className = 'text-body'; a.textContent = title; tr.cells[1].append(a);
        }
        tr.cells[2].textContent = r.date || '';
        tr.cells[3].textContent = r.message || '';
        const body = $('impLog');
        body.prepend(tr);
        while (body.rows.length > 500) body.lastElementChild.remove();
    }

    function lock(on) {
        busy = on;
        ['impCheck', 'impStart'].forEach((id) => { $(id).disabled = on; });
        $('impStop').classList.toggle('d-none', !on);
        form.querySelectorAll('input, select, #impAddRow, [data-imp-del]').forEach((el) => { if (el.type !== 'hidden') el.disabled = on; });
        window.onbeforeunload = on ? () => 'Impor sedang berjalan.' : null;
    }
    function fail(e) { phase('Berhenti: ' + e.message); $('impPhase').classList.add('text-danger'); }

    $('impCheck').addEventListener('click', async () => {
        if (busy || !form.elements.site.reportValidity()) return;
        $('impProgress').classList.remove('d-none'); $('impPhase').classList.remove('text-danger');
        lock(true);
        try {
            const list = await listAll();
            const total = list.reduce((a, s) => a + s.items.length, 0), left = list.reduce((a, s) => a + s.items.filter((i) => !i.done).length, 0);
            phase(total.toLocaleString('id-ID') + ' berita ditemukan, ' + left.toLocaleString('id-ID') + ' belum dipindah. Klik Mulai Impor.');
        } catch (e) { fail(e); }
        lock(false);
    });

    $('impStart').addEventListener('click', async () => {
        if (busy || !form.elements.site.reportValidity()) return;
        $('impProgress').classList.remove('d-none'); $('impPhase').classList.remove('text-danger');
        stop = false; n.ok = n.skip = n.error = 0; count(); $('impLog').innerHTML = '';
        lock(true);
        try {
            const list = await listAll();
            const jobs = [];
            list.forEach((s) => s.items.filter((i) => !i.done).forEach((i) => jobs.push({ url: i.url, cat: s.cat, label: s.catLabel })));
            n.skip = list.reduce((a, s) => a + s.items.filter((i) => i.done).length, 0); count();
            const total = jobs.length;
            if (!total) { phase('Semua berita sudah dipindah sebelumnya.'); $('impBar').style.width = '100%'; }
            const t0 = Date.now();
            for (let i = 0; i < total && !stop; i += 3) {
                const chunk = jobs.slice(i, i + 3).filter((j) => j.cat === jobs[i].cat);
                const step = chunk.length;
                const done = i;
                const eta = done ? Math.round((Date.now() - t0) / done * (total - done) / 60000) : null;
                phase('Memindahkan ' + (done + 1).toLocaleString('id-ID') + '–' + (done + step).toLocaleString('id-ID') + ' dari ' + total.toLocaleString('id-ID') + ' (' + chunk[0].label + ')' + (eta !== null ? ' · perkiraan sisa ' + (eta < 1 ? '< 1' : eta) + ' menit' : ''));
                const j = await post('jalankan', { category: chunk[0].cat, urls: chunk.map((c) => c.url) });
                j.results.forEach((r) => { n[r.status] = (n[r.status] || 0) + 1; log(r); });
                count();
                const pct = Math.round((done + step) / total * 100);
                $('impBar').style.width = pct + '%';
                $('impBar').parentElement.setAttribute('aria-valuenow', pct);
                if (step < 3) i -= 3 - step; // potongan berikutnya mulai tepat setelah yang terakhir
            }
            if (total) phase(stop ? 'Dihentikan. Klik Mulai Impor untuk melanjutkan — berita yang sudah dipindah akan dilewati.' : 'Selesai. ' + n.ok.toLocaleString('id-ID') + ' berita dipindah.' + (n.error ? ' Yang gagal dapat dicoba lagi dengan Mulai Impor.' : ''));
        } catch (e) { fail(e); }
        lock(false);
    });

    $('impStop').addEventListener('click', () => { stop = true; phase('Menghentikan setelah potongan ini selesai…'); });

    $('impAddRow').addEventListener('click', () => {
        const tr = rows.querySelector('tr').cloneNode(true);
        tr.querySelector('[data-imp=url]').value = '';
        tr.querySelector('[data-imp=on]').checked = true;
        tr.querySelector('[data-imp=count]').textContent = '–';
        rows.append(tr);
        tr.querySelector('[data-imp=url]').focus();
    });
    rows.addEventListener('click', (e) => {
        const b = e.target.closest('[data-imp-del]');
        if (b && rows.rows.length > 1) b.closest('tr').remove();
    });

    // ---- Impor satu berita (tempel alamat beritanya) ----
    const oneBtn = $('impOneBtn');
    if (oneBtn) oneBtn.addEventListener('click', async () => {
        if (busy || !form.elements.site.reportValidity()) return;
        const urls = $('impOneUrls').value.trim();
        const out = $('impOneResult');
        if (!urls) { out.innerHTML = '<span class="text-danger">Tempel dulu alamat beritanya.</span>'; return; }
        oneBtn.disabled = true; out.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Memindahkan…';
        try {
            const j = await post('satu', { urls, category: $('impOneCat').value, overwrite: $('impOverwrite').checked ? '1' : '' });
            out.innerHTML = '';
            (j.results || []).forEach((r) => {
                const badge = { ok: ['success', 'Dipindah'], skip: ['secondary', 'Dilewati'], error: ['danger', 'Gagal'] }[r.status] || ['secondary', r.status];
                const d = document.createElement('div'); d.className = 'mb-1';
                const b = document.createElement('span'); b.className = 'badge text-bg-' + badge[0] + ' me-1'; b.textContent = badge[1]; d.append(b);
                if (r.view) { const a = document.createElement('a'); a.href = r.view; a.target = '_blank'; a.rel = 'noopener'; a.textContent = r.title || r.url; d.append(a); const e = document.createElement('a'); e.href = r.edit; e.className = 'ms-2'; e.textContent = 'Ubah'; d.append(e); }
                else { d.append(document.createTextNode((r.title || r.url || '') + ' — ' + (r.message || ''))); }
                if (r.status === 'skip' && !$('impOverwrite').checked) d.append(document.createTextNode(' (centang "Timpa" untuk menggantinya)'));
                out.append(d);
            });
            if (!(j.results || []).length) out.innerHTML = '<span class="text-secondary">Tidak ada yang diproses.</span>';
        } catch (e) {
            out.innerHTML = '<span class="text-danger">' + (e.message || 'Gagal') + '</span>';
        }
        oneBtn.disabled = false;
    });
})();
