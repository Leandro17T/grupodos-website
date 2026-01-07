/**
 * REFACTORIZADO: 2025-12-06
 * Módulo: Banner Principal
 */
(function () {
    'use strict';

    function initSlider(root) {
        if (!root || root.classList.contains('gdos-inited')) return;
        root.classList.add('gdos-inited');

        const track = root.querySelector('.gdos-track');
        const prevBtn = root.querySelector('.gdos-prev');
        const nextBtn = root.querySelector('.gdos-next');

        // Helper de Seguridad: Objeto -> Array
        const ensureArray = (data) => {
            if (!data) return [];
            return Array.isArray(data) ? data : Object.values(data);
        };

        // 1. Parsing Robusto
        let dataMap = { desktop: [], tablet: [], mobile: [] };
        try {
            dataMap.desktop = ensureArray(JSON.parse(root.dataset.desktop || '[]'));
            dataMap.tablet  = ensureArray(JSON.parse(root.dataset.tablet  || '[]'));
            dataMap.mobile  = ensureArray(JSON.parse(root.dataset.mobile  || '[]'));
        } catch (e) {
            console.error('[GDOS Banner] Error JSON:', e);
            return;
        }

        const config = {
            autoplay: root.dataset.autoplay === '1',
            interval: parseInt(root.dataset.interval || '5000', 10),
            pause:    root.dataset.pause === '1'
        };

        let currentIndex = 0;
        let slides = [];
        let timer = null;
        let cdInterval = null;

        const getMode = () => {
            if (window.matchMedia('(max-width: 767px)').matches) return 'mobile';
            if (window.matchMedia('(max-width: 1024px)').matches) return 'tablet';
            return 'desktop';
        };

        // --- Render ---
        const render = () => {
            const mode = getMode();
            let items = dataMap[mode];
            if (!items.length) items = dataMap.tablet.length ? dataMap.tablet : dataMap.desktop;
            if (!items.length) items = dataMap.desktop;

            if (!Array.isArray(items)) items = [];

            let html = '';
            items.forEach(slide => {
                const img = `<img src="${slide.src}" alt="${slide.alt || ''}" style="width:100%;height:100%;object-fit:cover;">`;
                const content = slide.link ? `<a href="${slide.link}">${img}</a>` : img;
                
                let cdHTML = '';
                if (slide.countdown) {
                    let endStr = slide.date_to || slide.date_from;
                    // Fix Safari
                    if (endStr && endStr.indexOf('T') === -1) endStr += 'T23:59:59';
                    
                    if (new Date(endStr).getTime() > Date.now()) {
                        // NUEVO: Texto Dinámico
                        const labelText = (slide.countdown_label === 'start') ? 'Empieza en:' : 'Termina en:';

                        cdHTML = `
                        <div class="gdos-countdown" data-end="${endStr}">
                            <div class="gdos-cd-title">${labelText}</div>
                            <div class="gdos-cd-time">
                                <div class="gdos-cd-block"><span class="gdos-cd-seg dd">00</span><span class="gdos-cd-tag">D</span></div>
                                <span class="gdos-cd-sep"></span>
                                <div class="gdos-cd-block"><span class="gdos-cd-seg hh">00</span><span class="gdos-cd-tag">H</span></div>
                                <span class="gdos-cd-sep"></span>
                                <div class="gdos-cd-block"><span class="gdos-cd-seg mm">00</span><span class="gdos-cd-tag">M</span></div>
                                <span class="gdos-cd-sep"></span>
                                <div class="gdos-cd-block"><span class="gdos-cd-seg ss">00</span><span class="gdos-cd-tag">S</span></div>
                            </div>
                        </div>`;
                    }
                }
                html += `<div class="gdos-slide">${content}${cdHTML}</div>`;
            });

            if (track.innerHTML !== html) track.innerHTML = html;
            
            slides = track.querySelectorAll('.gdos-slide');

            const showArrows = slides.length > 1;
            if (prevBtn) prevBtn.style.display = showArrows ? 'flex' : 'none';
            if (nextBtn) nextBtn.style.display = showArrows ? 'flex' : 'none';

            goTo(0, false);
            startAutoplay();
            startCountdowns();
        };

        const goTo = (index, smooth = true) => {
            if (!slides.length) return;
            currentIndex = (index + slides.length) % slides.length;
            const offset = currentIndex * 100;
            track.style.transition = smooth ? 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)' : 'none';
            track.style.transform = `translateX(-${offset}%)`;
        };

        const next = () => goTo(currentIndex + 1);
        const prev = () => goTo(currentIndex - 1);

        const startAutoplay = () => {
            if (timer) clearInterval(timer);
            if (config.autoplay && slides.length > 1) {
                timer = setInterval(next, config.interval);
            }
        };
        const stopAutoplay = () => clearInterval(timer);

        const startCountdowns = () => {
            if (cdInterval) clearInterval(cdInterval);
            const update = () => {
                const now = Date.now();
                root.querySelectorAll('.gdos-countdown').forEach(el => {
                    const end = new Date(el.dataset.end).getTime();
                    const diff = end - now;
                    if (diff <= 0) { el.style.display = 'none'; return; }
                    
                    const d = Math.floor(diff / 86400000);
                    const h = Math.floor((diff % 86400000) / 3600000);
                    const m = Math.floor((diff % 3600000) / 60000);
                    const s = Math.floor((diff % 60000) / 1000);

                    el.querySelector('.dd').innerText = String(d).padStart(2,'0');
                    el.querySelector('.hh').innerText = String(h).padStart(2,'0');
                    el.querySelector('.mm').innerText = String(m).padStart(2,'0');
                    el.querySelector('.ss').innerText = String(s).padStart(2,'0');
                });
            };
            update();
            cdInterval = setInterval(update, 1000);
        };

        if (prevBtn) prevBtn.onclick = (e) => { e.preventDefault(); stopAutoplay(); prev(); startAutoplay(); };
        if (nextBtn) nextBtn.onclick = (e) => { e.preventDefault(); stopAutoplay(); next(); startAutoplay(); };

        if (config.pause) {
            root.onmouseenter = stopAutoplay;
            root.onmouseleave = startAutoplay;
        }

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(render, 300);
        });

        // Swipe Support
        let touchStartX = 0;
        track.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; stopAutoplay(); }, {passive: true});
        track.addEventListener('touchend', e => {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 50) { if (diff > 0) prev(); else next(); }
            startAutoplay();
        }, {passive: true});

        render();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.gdos-slider').forEach(initSlider);
    });
})();