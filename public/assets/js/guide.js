/* Panduan penggunaan: pencarian, penanda bab aktif di daftar isi, cetak. */
(function () {
    'use strict';
    const secs = [...document.querySelectorAll('.guide-sec')];
    const links = new Map([...document.querySelectorAll('[data-toc]')].map((a) => [a.dataset.toc, a]));
    const none = document.getElementById('guideNoResult');

    // pencarian: sembunyikan bab yang tidak memuat kata kunci, tandai kecocokan
    const search = document.getElementById('guideSearch');
    let t;
    search && search.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => {
            const q = search.value.trim().toLowerCase();
            let shown = 0;
            secs.forEach((s) => {
                const hit = !q || s.innerText.toLowerCase().includes(q);
                s.hidden = !hit;
                const l = links.get(s.id);
                if (l) l.parentElement.hidden = !hit;
                if (hit) shown++;
            });
            if (none) none.classList.toggle('d-none', shown > 0);
        }, 150);
    });

    // bab aktif saat menggulir
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((es) => {
            es.forEach((e) => {
                if (!e.isIntersecting) return;
                links.forEach((a) => a.classList.remove('active'));
                const a = links.get(e.target.id);
                if (a) a.classList.add('active');
            });
        }, { rootMargin: '-15% 0px -75% 0px' });
        secs.forEach((s) => io.observe(s));
    }

    // buka langsung ke bab (#halaman) dengan sorotan singkat
    const flash = () => {
        const s = location.hash && document.getElementById(location.hash.slice(1));
        if (!s || !s.classList.contains('guide-sec')) return;
        s.classList.add('guide-flash');
        setTimeout(() => s.classList.remove('guide-flash'), 1800);
    };
    window.addEventListener('hashchange', flash);
    flash();

    const pr = document.querySelector('[data-guide-print]');
    pr && pr.addEventListener('click', () => {
        if (search && search.value) { search.value = ''; search.dispatchEvent(new Event('input')); }
        setTimeout(() => window.print(), 200);
    });
})();
