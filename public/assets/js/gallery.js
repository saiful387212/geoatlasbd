/* Gallery UX enhancements — form still works without JS. */
(function () {
    'use strict';

    const form = document.querySelector('[data-gallery-filters]');
    if (!form) return;

    const submitBtn  = form.querySelector('button[type="submit"]');
    const summary    = document.querySelector('.gallery-summary');

    form.addEventListener('submit', () => {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Loading…';
        }
        if (summary) summary.setAttribute('aria-busy', 'true');
    });

    // Keyboard nicety: "/" focuses the search input.
    const search = form.querySelector('input[type="search"]');
    if (search) {
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement !== search &&
                !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)) {
                e.preventDefault();
                search.focus();
            }
        });
    }
})();