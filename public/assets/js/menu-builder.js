/* Menu Navigasi: susun menu dengan seret & lepas (mouse, touchpad, layar sentuh).
 * - Lepas di atas/bawah menu lain  → taruh sebelum/sesudahnya (saudara).
 * - Lepas di tengah menu lain      → jadikan sub-menunya.
 * Susunan langsung disimpan (POST JSON ke data-save-url). Tombol ↑/↓ tetap tersedia untuk keyboard. */
(function () {
    'use strict';
    const root = document.querySelector('[data-menu-tree]');
    const tree = root && root.querySelector('#menuTree');
    if (!tree) return;

    const MAX = parseInt(root.dataset.maxDepth, 10) || 4;
    const statusEl = root.querySelector('.menu-status');
    const IGNORE = 'a, button, input, select, textarea, label';
    let drag = null;

    const idOf = (li) => li.dataset.id;
    const label = (li) => (li.querySelector(':scope > .menu-item strong') || {}).textContent || 'Menu';
    const parentLi = (li) => li.parentElement.closest('li[data-id]');
    const depthOf = (li) => { let d = 1, p = parentLi(li); while (p) { d++; p = parentLi(p); } return d; };
    const childList = (li) => li.querySelector(':scope > ul.menu-tree');
    function heightOf(li) {
        const ul = childList(li);
        if (!ul) return 1;
        let h = 0;
        ul.querySelectorAll(':scope > li').forEach((c) => { h = Math.max(h, heightOf(c)); });
        return 1 + h;
    }
    function say(msg, kind) {
        if (!statusEl) return;
        statusEl.className = 'menu-status small mb-2' + (msg ? ' alert py-2 px-3 alert-' + (kind || 'info') : '');
        statusEl.textContent = msg || '';
    }

    /* ---------- mulai seret ---------- */
    root.addEventListener('pointerdown', (e) => {
        if (e.button !== 0 || drag) return;
        const item = e.target.closest('.menu-item');
        if (!item || !tree.contains(item)) return;
        if (e.target.closest(IGNORE) && !e.target.closest('.menu-handle')) return;
        drag = { li: item.parentElement, item, x0: e.clientX, y0: e.clientY, pid: e.pointerId, active: false, target: null, mode: null, ok: false };
        if (e.target.closest('.menu-handle')) e.preventDefault(); // cegah seleksi teks / gulir di layar sentuh
    });

    function begin(e) {
        drag.active = true;
        const r = drag.item.getBoundingClientRect();
        drag.dx = e.clientX - r.left;
        drag.dy = e.clientY - r.top;
        drag.ghost = drag.item.cloneNode(true);
        drag.ghost.classList.add('menu-ghost');
        drag.ghost.removeAttribute('id');
        drag.ghost.style.width = r.width + 'px';
        document.body.appendChild(drag.ghost);
        drag.li.classList.add('dragging');
        document.body.classList.add('menu-dragging');
        drag.origin = { parent: drag.li.parentElement, owner: drag.li.parentElement.parentElement, next: drag.li.nextElementSibling };
        say('Menyeret “' + label(drag.li) + '”… lepas di tengah menu lain untuk menjadikannya sub-menu.', 'info');
    }

    document.addEventListener('pointermove', (e) => {
        if (!drag || e.pointerId !== drag.pid) return;
        if (!drag.active) {
            if (Math.hypot(e.clientX - drag.x0, e.clientY - drag.y0) < 6) return;
            begin(e);
        }
        e.preventDefault();
        drag.ghost.style.left = (e.clientX - drag.dx) + 'px';
        drag.ghost.style.top = (e.clientY - drag.dy) + 'px';
        drag.lx = e.clientX; drag.ly = e.clientY;
        autoScroll(e.clientY);
        locate(e.clientX, e.clientY);
    }, { passive: false });

    let scrollTimer = null;
    function autoScroll(y) {
        clearInterval(scrollTimer);
        const edge = 70, h = window.innerHeight;
        const speed = y < edge ? -(edge - y) / 3 : y > h - edge ? (y - (h - edge)) / 3 : 0;
        if (speed) scrollTimer = setInterval(() => {
            window.scrollBy(0, speed);
            if (drag && drag.active) locate(drag.lx, drag.ly); // tanda tujuan ikut bergeser saat halaman menggulir
        }, 16);
    }

    function clearMarks() {
        tree.querySelectorAll('.drop-before, .drop-after, .drop-inside, .drop-invalid').forEach((el) => el.classList.remove('drop-before', 'drop-after', 'drop-inside', 'drop-invalid'));
    }

    /* ---------- tentukan tujuan & cek aturan ---------- */
    function locate(x, y) {
        clearMarks();
        drag.target = null;
        const el = document.elementFromPoint(x, y);
        const item = el && el.closest('.menu-item');
        if (!item || !tree.contains(item) || item === drag.item) return;
        if (drag.li.contains(item)) {
            item.classList.add('drop-invalid');
            drag.target = item.parentElement; drag.ok = false;
            drag.why = 'Tidak bisa: menu tidak bisa dimasukkan ke dalam sub-menunya sendiri.';
            say(drag.why, 'danger');
            return;
        }
        const tli = item.parentElement;
        const r = item.getBoundingClientRect();
        const rel = (y - r.top) / r.height;
        const mode = rel < 0.28 ? 'before' : rel > 0.72 ? 'after' : 'inside';
        const why = check(tli, mode);
        drag.target = tli;
        drag.mode = mode;
        drag.ok = !why;
        item.classList.add(why ? 'drop-invalid' : 'drop-' + mode);
        drag.why = why;
        if (why) say(why, 'danger');
        else say(mode === 'inside' ? 'Lepas: jadikan sub-menu dari “' + label(tli) + '”.' : 'Lepas: taruh ' + (mode === 'before' ? 'sebelum' : 'sesudah') + ' “' + label(tli) + '”.', 'success');
    }

    function check(tli, mode) {
        const newDepth = mode === 'inside' ? depthOf(tli) + 1 : depthOf(tli);
        if (newDepth + heightOf(drag.li) - 1 > MAX) {
            return 'Tidak bisa: menu paling banyak ' + MAX + ' tingkat.';
        }
        const newTop = newDepth === 1 ? idOf(drag.li) : tli.dataset.top;
        const req = [drag.li, ...drag.li.querySelectorAll('li[data-id]')].filter((li) => li.hasAttribute('data-required'));
        for (const r of req) {
            const wasTop = r.dataset.top === idOf(r);
            if (r === drag.li && wasTop && newDepth !== 1) return '“' + label(r) + '” adalah menu utama wajib SK, harus tetap di tingkat 1.';
            if (r.dataset.top !== newTop) {
                const top = tree.querySelector('li[data-id="' + r.dataset.top + '"]');
                return '“' + label(r) + '” adalah menu wajib SK, harus tetap di bawah “' + (top ? label(top) : 'menu utamanya') + '”.';
            }
        }
        return '';
    }

    /* ---------- lepas ---------- */
    function finish(cancelled) {
        if (!drag) return;
        clearInterval(scrollTimer);
        const d = drag;
        drag = null;
        if (!d.active) return;
        d.ghost.remove();
        d.li.classList.remove('dragging');
        document.body.classList.remove('menu-dragging');
        clearMarks();
        if (cancelled || !d.target || !d.ok) {
            const why = d.target && !d.ok ? (d.why || '') : '';
            const r = why.replace(/^Tidak bisa: /, '');
            say(why ? 'Tidak dipindahkan. ' + r.charAt(0).toUpperCase() + r.slice(1) : '', 'warning');
            return;
        }
        const t = d.target;
        if (d.mode === 'inside') {
            let ul = childList(t);
            if (!ul) { ul = document.createElement('ul'); ul.className = 'menu-tree'; t.appendChild(ul); }
            ul.appendChild(d.li);
        } else {
            t.parentElement.insertBefore(d.li, d.mode === 'before' ? t : t.nextElementSibling);
        }
        if (d.li.parentElement === d.origin.parent && d.li.nextElementSibling === d.origin.next) { say(''); return; }
        refresh();
        save(d);
    }
    document.addEventListener('pointerup', (e) => { if (drag && e.pointerId === drag.pid) finish(false); });
    document.addEventListener('pointercancel', (e) => { if (drag && e.pointerId === drag.pid) finish(true); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && drag) finish(true); });

    /* ---------- perbarui tampilan setelah pindah ---------- */
    function refresh() {
        tree.querySelectorAll('ul.menu-tree').forEach((ul) => { if (!ul.children.length && ul !== tree) ul.remove(); });
        (function walk(ul, top) {
            const kids = [...ul.children].filter((li) => li.matches('li[data-id]'));
            kids.forEach((li, i) => {
                li.dataset.top = top || idOf(li);
                const item = li.querySelector(':scope > .menu-item');
                const up = item.querySelector('[data-dir="up"]'), down = item.querySelector('[data-dir="down"]');
                if (up) up.disabled = i === 0;
                if (down) down.disabled = i === kids.length - 1;
                const sub = item.querySelector('[data-submenu]');
                if (sub) sub.hidden = depthOf(li) >= MAX;
                const ul2 = childList(li);
                if (ul2) walk(ul2, top || idOf(li));
            });
        })(tree, null);
    }

    function serialize() {
        const out = [];
        (function walk(ul, parent) {
            [...ul.children].filter((li) => li.matches('li[data-id]')).forEach((li, i) => {
                out.push({ id: +idOf(li), parent: parent, sort: i + 1 });
                const c = childList(li);
                if (c) walk(c, +idOf(li));
            });
        })(tree, null);
        return out;
    }

    function csrf() {
        const i = root.querySelector('input[name^="csrf"]') || document.querySelector('input[name^="csrf"]');
        return i ? { name: i.name, hash: i.value } : null;
    }
    function updateCsrf(c) {
        if (c) document.querySelectorAll('input[name="' + c.name + '"]').forEach((i) => { i.value = c.hash; });
    }

    async function save(d) {
        say('Menyimpan susunan…', 'info');
        const c = csrf();
        let j = {};
        let ok = false;
        try {
            const r = await fetch(root.dataset.saveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: Object.assign({ 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, c ? { 'X-CSRF-TOKEN': c.hash } : {}),
                body: JSON.stringify({ items: serialize() }),
            });
            try { j = await r.json(); } catch (e) { /* abaikan */ }
            ok = r.ok && j.ok;
        } catch (e) { j = { error: 'Koneksi terputus.' }; }
        updateCsrf(j.csrf);
        if (ok) {
            const item = d.li.querySelector(':scope > .menu-item');
            item.classList.add('saved');
            setTimeout(() => item.classList.remove('saved'), 1600);
            say('Tersimpan: “' + label(d.li) + '” sudah dipindahkan. Menu di situs langsung ikut berubah.', 'success');
        } else {
            // kembalikan ke posisi semula
            if (!d.origin.parent.isConnected) d.origin.owner.appendChild(d.origin.parent);
            d.origin.parent.insertBefore(d.li, d.origin.next && d.origin.next.isConnected ? d.origin.next : null);
            refresh();
            say((j.error || 'Gagal menyimpan susunan.') + ' Menu dikembalikan ke posisi semula.', 'danger');
            if (!j.csrf) setTimeout(() => location.reload(), 2500); // token kedaluwarsa → muat ulang
        }
    }
})();
