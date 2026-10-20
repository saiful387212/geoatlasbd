/* GeoAtlas Bangladesh — public UI script */

(function () {
    'use strict';

    const toggle = document.querySelector('[data-nav-toggle]');
    const nav    = document.getElementById('primary-navigation');

    if (toggle && nav) {
        const setExpanded = (open) => {
            toggle.setAttribute('aria-expanded', String(open));
            nav.classList.toggle('is-open', open);
        };

        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') === 'true';
            setExpanded(!open);
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                setExpanded(false);
                toggle.focus();
            }
        });

        // Close on outside click (mobile only)
        document.addEventListener('click', (e) => {
            if (!nav.classList.contains('is-open')) return;
            if (nav.contains(e.target) || toggle.contains(e.target)) return;
            setExpanded(false);
        });

        // Close when a nav link is followed (SPA-less but reduces flicker)
        nav.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link) setExpanded(false);
        });

        // Reset state when viewport grows past breakpoint
        const mq = window.matchMedia('(min-width: 860px)');
        const onChange = () => { if (mq.matches) setExpanded(false); };
        if (mq.addEventListener) mq.addEventListener('change', onChange);
        else mq.addListener(onChange);
    }
})();
/* Lightbox Functionality */
(function () {
    'use strict';

    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');
    const lightboxClose = document.querySelector('.lightbox__close');
    const images = document.querySelectorAll('[data-lightbox="project-gallery"]');

    if (!lightbox || !lightboxImg || !lightboxTitle || !lightboxClose || images.length === 0) return;

    let lastFocusedImage = null;
    let clearImageTimer = null;

    function openLightbox(img) {
        window.clearTimeout(clearImageTimer);
        lastFocusedImage = img;
        lightboxImg.src = img.currentSrc || img.src;
        lightboxImg.alt = img.alt;
        lightboxTitle.textContent = img.dataset.title || img.alt || '';

        lightbox.classList.add('is-active');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lightbox-open');
        lightboxClose.focus();
    }

    function closeLightbox() {
        lightbox.classList.remove('is-active');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('lightbox-open');
        if (lastFocusedImage) lastFocusedImage.focus();
        clearImageTimer = window.setTimeout(() => { lightboxImg.src = ''; }, 300);
    }

    images.forEach(img => {
        img.addEventListener('click', (e) => {
            e.preventDefault();
            openLightbox(img);
        });

        img.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openLightbox(img);
            }
        });
    });

    lightboxClose.addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });

    lightbox.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            e.preventDefault();
            lightboxClose.focus();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('is-active')) {
            closeLightbox();
        }
    });
})();