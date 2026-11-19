/* Editor grid Tampilan Beranda: seret kotak ke mana saja dan ubah lebar/tinggi. Tanpa pustaka luar. */
(function () {
    'use strict';
    const grid = document.getElementById('gbGrid');
    if (!grid) return;

    const form = document.getElementById('hbForm');
    const saveBtn = document.getElementById('hbSave');
    const undoBtn = document.getElementById('hbUndo');
    const dirtyEl = document.getElementById('hbDirty');
    const live = document.getElementById('hbLive');
    const preview = grid.querySelector('.gb-preview');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const els = [...grid.querySelectorAll('.gb-item')];
    const byId = new Map(els.map((el) => [el.dataset.id, el]));
    const state = new Map(els.map((el) => [el.dataset.id, { x: +el.dataset.x, y: +el.dataset.y, w: +el.dataset.w, h: +el.dataset.h }]));
    const history = [];
    let dirty = false;

    const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
    const hit = (a, b) => a.x < b.x + b.w && b.x < a.x + a.w && a.y < b.y + b.h && b.y < a.y + a.h;
    const name = (id) => byId.get(id).dataset.name;
    const announce = (msg) => {
        live.textContent = '';
        clearTimeout(announce.t);
        announce.t = setTimeout(() => { live.textContent = msg; }, 350);
    };
    const snapshot = () => {
        history.push(JSON.stringify([...state]));
        if (history.length > 50) history.shift();
        undoBtn.disabled = false;
    };
    const markDirty = () => { dirty = true; saveBtn.disabled = false; dirtyEl.hidden = false; };
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    const bottom = () => Math.max(1, ...[...state.values()].map((s) => s.y + s.h - 1));
    const setRows = (extra) => grid.style.setProperty('--rows', Math.max(bottom(), extra || 0) + 2);

    // Terapkan posisi ke DOM; animasikan perpindahan (FLIP) kecuali pengguna memilih gerak dikurangi.
    const apply = (except) => {
        const before = new Map(els.map((el) => [el, el.getBoundingClientRect()]));
        for (const [id, s] of state) {
            const el = byId.get(id);
            el.style.gridColumn = s.x + ' / span ' + s.w;
            el.style.gridRow = s.y + ' / span ' + s.h;
            el.querySelector('.gb-size').textContent = s.w + '×' + s.h;
        }
        setRows();
        if (reduceMotion) return;
        for (const el of els) {
            if (el === except || !el.animate) continue;
            const a = before.get(el), b = el.getBoundingClientRect();
            const dx = a.left - b.left, dy = a.top - b.top;
            if (dx || dy) el.animate([{ translate: dx + 'px ' + dy + 'px' }, { translate: '0 0' }], { duration: 180, easing: 'ease-out' });
        }
    };

    // Kotak lain yang tertimpa didorong ke bawah (tidak ada tumpang tindih).
    const resolve = (id) => {
        const fixed = [state.get(id)];
        const others = [...state].filter(([k]) => k !== id).sort((a, b) => a[1].y - b[1].y || a[1].x - b[1].x);
        for (const [, o] of others) {
            while (fixed.some((f) => hit(o, f))) o.y++;
            fixed.push(o);
        }
    };
    // Rapikan: naikkan semua kotak sejauh mungkin (menghapus baris kosong).
    const compact = () => {
        const placed = [];
        for (const [, s] of [...state].sort((a, b) => a[1].y - b[1].y || a[1].x - b[1].x)) {
            while (s.y > 1 && !placed.some((p) => hit({ ...s, y: s.y - 1 }, p))) s.y--;
            placed.push(s);
        }
    };

    const geo = () => {
        const r = grid.getBoundingClientRect();
        const cs = getComputedStyle(grid);
        const gx = parseFloat(cs.columnGap) || 8, gy = parseFloat(cs.rowGap) || 8;
        const colW = (r.width - gx * 11) / 12;
        const rowH = parseFloat(cs.getPropertyValue('--row')) || 72;
        return { r, sx: colW + gx, sy: rowH + gy };
    };
    const cellAt = (px, py) => {
        const g = geo();
        return { col: Math.floor((px - g.r.left) / g.sx) + 1, row: Math.floor((py - g.r.top) / g.sy) + 1 };
    };
    const showPreview = (s) => {
        preview.hidden = false;
        preview.style.gridColumn = s.x + ' / span ' + s.w;
        preview.style.gridRow = s.y + ' / span ' + s.h;
        setRows(s.y + s.h - 1);
    };

    // ---------------------------------------------------------- seret (pindah) & tarik sudut (ukuran)
    let op = null;
    let raf = 0;
    const autoScroll = () => {
        if (!op) return;
        const edge = 70;
        let v = 0;
        if (op.py < edge) v = -Math.ceil((edge - op.py) / 4);
        else if (op.py > innerHeight - edge) v = Math.ceil((op.py - (innerHeight - edge)) / 4);
        if (v) { scrollBy(0, v); track(op.px, op.py); }
        raf = requestAnimationFrame(autoScroll);
    };
    const track = (px, py) => {
        op.px = px; op.py = py;
        const s = state.get(op.id);
        const c = cellAt(px, py);
        if (op.kind === 'move') {
            op.el.style.translate = (px - op.x0) + 'px ' + (py - op.y0 + scrollY - op.scroll0) + 'px';
            op.target = { x: clamp(c.col - op.dc, 1, 13 - s.w), y: Math.max(1, c.row - op.dr), w: s.w, h: s.h };
        } else {
            op.target = { x: s.x, y: s.y, w: clamp(c.col - s.x + 1, 1, 13 - s.x), h: clamp(c.row - s.y + 1, 1, 12) };
            op.el.style.gridColumn = s.x + ' / span ' + op.target.w;
            op.el.style.gridRow = s.y + ' / span ' + op.target.h;
            op.el.querySelector('.gb-size').textContent = op.target.w + '×' + op.target.h;
        }
        showPreview(op.target);
    };
    const finish = (commit) => {
        if (!op) return;
        cancelAnimationFrame(raf);
        const { id, el, kind, target, handle } = op;
        op = null;
        el.classList.remove('gb-dragging', 'gb-resizing');
        el.style.translate = '';
        document.documentElement.classList.remove('gb-grabbing', 'gb-sizing');
        preview.hidden = true;
        const s = state.get(id);
        if (commit && target && (target.x !== s.x || target.y !== s.y || target.w !== s.w || target.h !== s.h)) {
            snapshot();
            Object.assign(s, target);
            resolve(id);
            apply(kind === 'move' ? null : el);
            markDirty();
            announce(name(id) + (kind === 'move' ? ' dipindah ke kolom ' + s.x + ', baris ' + s.y : ' berukuran ' + s.w + ' kolom × ' + s.h + ' baris'));
        } else {
            apply(el);
            if (!commit) announce('Dibatalkan');
        }
        handle.focus({ preventScroll: true });
    };
    grid.addEventListener('pointerdown', (e) => {
        if (e.button !== 0 || op) return;
        const mv = e.target.closest('.gb-move');
        const rz = e.target.closest('.gb-resize');
        if (!mv && !rz) return;
        e.preventDefault();
        const el = (mv || rz).closest('.gb-item');
        const id = el.dataset.id;
        const s = state.get(id);
        const c = cellAt(e.clientX, e.clientY);
        (mv || rz).setPointerCapture(e.pointerId);
        op = {
            kind: mv ? 'move' : 'size', id, el, handle: el.querySelector('.gb-move'), pointerId: e.pointerId,
            x0: e.clientX, y0: e.clientY, scroll0: scrollY, px: e.clientX, py: e.clientY,
            dc: clamp(c.col - s.x, 0, s.w - 1), dr: clamp(c.row - s.y, 0, s.h - 1), target: null,
        };
        el.classList.add(mv ? 'gb-dragging' : 'gb-resizing');
        document.documentElement.classList.add(mv ? 'gb-grabbing' : 'gb-sizing');
        raf = requestAnimationFrame(autoScroll);
    });
    document.addEventListener('pointermove', (e) => { if (op && e.pointerId === op.pointerId) track(e.clientX, e.clientY); });
    document.addEventListener('pointerup', (e) => { if (op && e.pointerId === op.pointerId) finish(true); });
    document.addEventListener('pointercancel', (e) => { if (op && e.pointerId === op.pointerId) finish(false); });
    grid.addEventListener('lostpointercapture', (e) => { if (op && e.pointerId === op.pointerId) finish(!!op.target); }, true);

    // ---------------------------------------------------------- keyboard: panah = pindah, Shift+panah = ukuran
    grid.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && op) { e.preventDefault(); finish(false); return; }
        const mv = e.target.closest('.gb-move');
        if (!mv || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;
        e.preventDefault();
        const id = mv.closest('.gb-item').dataset.id;
        const s = state.get(id);
        const t = { ...s };
        if (e.shiftKey) {
            if (e.key === 'ArrowRight') t.w = clamp(s.w + 1, 1, 13 - s.x);
            if (e.key === 'ArrowLeft') t.w = clamp(s.w - 1, 1, 12);
            if (e.key === 'ArrowDown') t.h = clamp(s.h + 1, 1, 12);
            if (e.key === 'ArrowUp') t.h = clamp(s.h - 1, 1, 12);
        } else {
            if (e.key === 'ArrowRight') t.x = clamp(s.x + 1, 1, 13 - s.w);
            if (e.key === 'ArrowLeft') t.x = clamp(s.x - 1, 1, 12);
            if (e.key === 'ArrowDown') t.y = s.y + 1;
            if (e.key === 'ArrowUp') t.y = Math.max(1, s.y - 1);
        }
        if (t.x === s.x && t.y === s.y && t.w === s.w && t.h === s.h) { announce('Sudah di batas'); return; }
        snapshot();
        Object.assign(s, t);
        resolve(id);
        apply();
        markDirty();
        mv.closest('.gb-item').scrollIntoView({ block: 'nearest' });
        announce(name(id) + ': kolom ' + s.x + ', baris ' + s.y + ', ' + s.w + ' kolom × ' + s.h + ' baris');
    });

    // ---------------------------------------------------------- batalkan, rapikan, simpan
    undoBtn.addEventListener('click', () => {
        const last = history.pop();
        if (!last) return;
        for (const [id, s] of JSON.parse(last)) Object.assign(state.get(id), s);
        apply();
        markDirty();
        undoBtn.disabled = history.length === 0;
        announce('Perubahan terakhir dibatalkan');
    });
    const tidy = document.getElementById('hbTidy');
    if (tidy) {
        tidy.addEventListener('click', () => {
            snapshot();
            compact();
            apply();
            markDirty();
            announce('Baris kosong dirapikan');
        });
    }
    form.addEventListener('submit', () => {
        document.getElementById('hbLayout').value = JSON.stringify([...state].map(([id, s]) => ({ id: +id, x: s.x, y: s.y, w: s.w, h: s.h })));
        dirty = false;
    });
    setRows();
})();
