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