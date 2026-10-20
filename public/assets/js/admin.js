/* Admin UI enhancements. Progressive — every form still works without JS. */
(function () {
    'use strict';

    // Auto-generate slug from title
    const src = document.querySelector('[data-slug-source]');
    const dst = document.querySelector('[data-slug-target]');
    if (src && dst) {
        let userEdited = dst.value.trim() !== '';
        dst.addEventListener('input', () => { userEdited = dst.value.trim() !== ''; });
        src.addEventListener('input', () => {
            if (userEdited) return;
            dst.value = slugify(src.value);
        });
    }

    function slugify(s) {
        return s.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 180);
    }

    // Cover preview
    const coverInput = document.querySelector('#cover_image');
    if (coverInput) {
        coverInput.addEventListener('change', () => {
            const file = coverInput.files && coverInput.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            let preview = document.querySelector('.cover-preview img');
            if (!preview) {
                const wrap = document.createElement('div');
                wrap.className = 'cover-preview';
                wrap.innerHTML = '<img alt="" width="120">';
                coverInput.parentNode.appendChild(wrap);
                preview = wrap.querySelector('img');
            }
            preview.src = url;
            preview.onload = () => URL.revokeObjectURL(url);
        });
    }
})();
/* Media upload client-side pre-checks — server is authoritative. */
(function () {
    'use strict';

    const IMAGE_MAX = 5 * 1024 * 1024;
    const PDF_MAX   = 10 * 1024 * 1024;
    const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function setError(input, message) {
        let err = input.parentNode.querySelector('.field-error--client');
        if (!err) {
            err = document.createElement('p');
            err.className = 'field-error field-error--client';
            input.parentNode.appendChild(err);
        }
        err.textContent = message;
        input.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    function validateImage(input) {
        if (!input.files || !input.files[0]) { setError(input, ''); return true; }
        const f = input.files[0];
        if (!IMAGE_TYPES.includes(f.type)) { setError(input, 'Please choose a JPG, PNG, or WebP image.'); return false; }
        if (f.size > IMAGE_MAX) { setError(input, 'Image must be ≤ 5 MB.'); return false; }
        const nameParts = f.name.toLowerCase().split('.');
        // reject double-extension: photo.php.jpg
        for (let i = 0; i < nameParts.length - 1; i++) {
            if (['php','phtml','phar','jsp','asp','cgi','pl','py','sh','exe','bat','svg','html','htm','js'].includes(nameParts[i])) {
                setError(input, 'File name contains a disallowed segment.'); return false;
            }
        }
        setError(input, '');
        return true;
    }

    function validatePdf(input) {
        if (!input.files || !input.files[0]) { setError(input, ''); return true; }
        const f = input.files[0];
        if (f.type !== 'application/pdf' && !f.name.toLowerCase().endsWith('.pdf')) {
            setError(input, 'Please choose a .pdf file.'); return false;
        }
        if (f.size > PDF_MAX) { setError(input, 'PDF must be ≤ 10 MB.'); return false; }
        setError(input, '');
        return true;
    }

    const cover = document.querySelector('#cover_image');
    if (cover) cover.addEventListener('change', () => validateImage(cover));

    const gallery = document.querySelector('#gallery');
    if (gallery) gallery.addEventListener('change', () => {
        for (const f of gallery.files || []) {
            if (!IMAGE_TYPES.includes(f.type) || f.size > IMAGE_MAX) {
                setError(gallery, 'One or more gallery images are invalid (JPG/PNG/WebP, ≤ 5 MB).');
                return;
            }
        }
        setError(gallery, '');
    });

    const pdf = document.querySelector('#report_pdf');
    if (pdf) pdf.addEventListener('change', () => validatePdf(pdf));
})();