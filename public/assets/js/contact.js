/* Contact form — client-side UX validation (server re-validates). */
(function () {
    'use strict';

    const form = document.querySelector('[data-contact-form]');
    if (!form) return;

    const status = form.querySelector('[data-form-status]');

    const validators = {
        name:    (v) => v.trim().length >= 1 && v.trim().length <= 100,
        email:   (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) && v.length <= 255,
        subject: (v) => v.trim().length >= 1 && v.trim().length <= 150,
        message: (v) => v.trim().length >= 20 && v.length <= 5000,
    };

    const messages = {
        name:    'Please enter your name.',
        email:   'Please enter a valid email address.',
        subject: 'Please add a short subject.',
        message: 'Message must be at least 20 characters.',
    };

    const fields = ['name', 'email', 'subject', 'message'];

    function setFieldState(name, ok) {
        const input = form.elements[name];
        const errId = 'err-' + name;
        const errEl = document.getElementById(errId);
        if (!input || !errEl) return;
        if (ok) {
            input.removeAttribute('aria-invalid');
            errEl.textContent = '';
        } else {
            input.setAttribute('aria-invalid', 'true');
            if (!errEl.textContent) errEl.textContent = messages[name] || 'Please check this field.';
        }
    }

    function validateField(name) {
        const input = form.elements[name];
        if (!input) return true;
        const ok = validators[name](input.value || '');
        setFieldState(name, ok);
        return ok;
    }

    fields.forEach((name) => {
        const input = form.elements[name];
        if (!input) return;
        input.addEventListener('blur', () => validateField(name));
        input.addEventListener('input', () => {
            if (input.getAttribute('aria-invalid') === 'true') validateField(name);
        });
    });

    form.addEventListener('submit', (e) => {
        let allOk = true;
        fields.forEach((name) => { if (!validateField(name)) allOk = false; });

        if (!allOk) {
            e.preventDefault();
            const firstInvalid = form.querySelector('[aria-invalid="true"]');
            if (firstInvalid) firstInvalid.focus();
            if (status) status.textContent = 'Please fix the highlighted fields.';
            return;
        }

        if (status) status.textContent = 'Sending…';
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending…';
        }
        // Let the form submit normally. Server redirects to /contact either way.
    });
})();