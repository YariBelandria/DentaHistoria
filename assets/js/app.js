/* DentaHistoria · utilidades globales */
(function () {
    'use strict';

    const App = {};

    const THEME_KEY = 'dentahistoria-theme';

    App.getTheme = () => {
        const current = document.documentElement.getAttribute('data-theme');
        if (current === 'light' || current === 'dark') return current;
        try {
            const stored = localStorage.getItem(THEME_KEY);
            if (stored === 'light' || stored === 'dark') return stored;
        } catch (error) {
            /* almacenamiento no disponible */
        }
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    App.applyTheme = (theme, persist = true) => {
        const next = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        document.documentElement.style.colorScheme = next;

        if (persist) {
            try {
                localStorage.setItem(THEME_KEY, next);
            } catch (error) {
                /* almacenamiento no disponible */
            }
        }

        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', next === 'dark' ? '#101b2d' : '#1a5ab0');

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const icon = button.querySelector('[data-theme-icon]');
            const label = next === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
            if (icon) icon.className = next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
        });

        document.dispatchEvent(new CustomEvent('dh:themechange', { detail: { theme: next } }));
    };

    App.toggleTheme = () => {
        App.applyTheme(App.getTheme() === 'dark' ? 'light' : 'dark');
    };

    App.initTheme = () => {
        App.applyTheme(App.getTheme(), false);

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-theme-toggle]');
            if (!button) return;
            event.preventDefault();
            App.toggleTheme();
        });

        if (window.matchMedia) {
            const media = window.matchMedia('(prefers-color-scheme: dark)');
            const onChange = (event) => {
                let stored = null;
                try {
                    stored = localStorage.getItem(THEME_KEY);
                } catch (error) {
                    stored = null;
                }
                if (!stored) App.applyTheme(event.matches ? 'dark' : 'light', false);
            };
            if (typeof media.addEventListener === 'function') media.addEventListener('change', onChange);
            else if (typeof media.addListener === 'function') media.addListener(onChange);
        }

        window.addEventListener('storage', (event) => {
            if (event.key === THEME_KEY && (event.newValue === 'light' || event.newValue === 'dark')) {
                App.applyTheme(event.newValue, false);
            }
        });
    };

    App.csrf = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    App.escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value === null || value === undefined ? '' : String(value);
        return div.innerHTML;
    };

    App.apiFetch = async (url, options = {}) => {
        const config = { ...options };
        config.headers = new Headers(options.headers || {});
        config.headers.set('Accept', 'application/json');
        config.headers.set('X-Requested-With', 'XMLHttpRequest');

        if ((config.method || 'GET').toUpperCase() !== 'GET') {
            config.headers.set('X-CSRF-Token', App.csrf());
        }

        let response;
        try {
            response = await fetch(url, config);
        } catch (error) {
            throw new Error('No se pudo conectar con el servidor. Revisa tu conexión.');
        }

        let payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('El servidor devolvió una respuesta inesperada.');
        }

        if (response.status === 401) {
            App.toast('Tu sesión expiró. Redirigiendo al inicio de sesión…', 'warning');
            setTimeout(() => { window.location.href = 'index.php'; }, 1200);
            const authError = new Error(payload.message || 'Sesión expirada.');
            authError.payload = payload;
            throw authError;
        }

        if (!response.ok || payload.ok === false) {
            const apiError = new Error(payload.message || 'No se pudo completar la operación.');
            apiError.payload = payload;
            apiError.status = response.status;
            throw apiError;
        }

        return payload;
    };

    App.debounce = (fn, wait = 300) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn(...args), wait);
        };
    };

    App.formatDate = (dateString, fallback = '—') => {
        if (!dateString) return fallback;
        let value = String(dateString).replace(' ', 'T');
        // Una fecha sin hora (YYYY-MM-DD) se interpreta como UTC; forzamos hora local.
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) value += 'T00:00:00';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return fallback;
        return new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }).format(date).replace('.', '');
    };

    App.formatDateTime = (dateString, fallback = '—') => {
        if (!dateString) return fallback;
        const date = new Date(String(dateString).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return fallback;
        return new Intl.DateTimeFormat('es-VE', {
            day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
        }).format(date).replace('.', '');
    };

    App.age = (birthdate) => {
        if (!birthdate) return null;
        const birth = new Date(birthdate + 'T00:00:00');
        if (Number.isNaN(birth.getTime())) return null;
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const month = today.getMonth() - birth.getMonth();
        if (month < 0 || (month === 0 && today.getDate() < birth.getDate())) age--;
        return age >= 0 ? age : null;
    };

    App.initials = (name) => {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return 'DP';
        return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
    };

    App.toast = (message, type = 'success') => {
        const container = document.getElementById('toastContainer');
        if (!container || !window.bootstrap) {
            console.log(message);
            return;
        }

        const icons = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-octagon-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };
        const tone = type === 'error' ? 'danger' : type;
        const wrapper = document.createElement('div');
        wrapper.className = `toast app-toast toast-${tone}`;
        wrapper.setAttribute('role', 'alert');
        wrapper.setAttribute('aria-live', 'assertive');
        wrapper.setAttribute('aria-atomic', 'true');
        wrapper.innerHTML = `
            <div class="toast-body">
                <span class="toast-icon"><i class="bi ${icons[tone] || icons.success}"></i></span>
                <span class="toast-message">${App.escapeHtml(message)}</span>
                <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>`;
        container.appendChild(wrapper);
        const toast = new bootstrap.Toast(wrapper, { delay: 4200 });
        toast.show();
        wrapper.addEventListener('hidden.bs.toast', () => wrapper.remove());
    };

    App.setLoading = (button, loading, label) => {
        if (!button) return;
        const spinner = button.querySelector('.spinner-border');
        const labelEl = button.querySelector('.btn-label');
        button.disabled = loading;
        if (spinner) spinner.classList.toggle('d-none', !loading);
        if (labelEl) {
            if (loading) {
                labelEl.dataset.original = labelEl.textContent;
                labelEl.textContent = label || 'Guardando…';
            } else if (labelEl.dataset.original) {
                labelEl.textContent = labelEl.dataset.original;
            }
        }
    };

    App.clearFormErrors = (form) => {
        if (!form) return;
        form.querySelectorAll('.field-error').forEach((el) => { el.textContent = ''; });
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        const alert = form.querySelector('.alert-danger');
        if (alert && alert.id) {
            alert.classList.add('d-none');
            alert.textContent = '';
        }
    };

    App.showFormErrors = (form, errors = {}, message = 'Revisa los campos marcados.') => {
        if (!form) return;
        App.clearFormErrors(form);
        Object.entries(errors || {}).forEach(([field, error]) => {
            const errorEl = form.querySelector(`[data-error="${field}"]`);
            if (errorEl) errorEl.textContent = error;
            const input = form.querySelector(`[name="${field}"]`);
            if (input) input.classList.add('is-invalid');
        });
        const alert = form.querySelector('.alert-danger');
        if (alert) {
            alert.textContent = message;
            alert.classList.remove('d-none');
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    App.modal = (id) => window.bootstrap ? bootstrap.Modal.getOrCreateInstance(document.getElementById(id)) : null;

    App.hideModal = (id) => {
        const element = document.getElementById(id);
        if (!element || !window.bootstrap) return;
        const instance = bootstrap.Modal.getInstance(element);
        if (instance) instance.hide();
    };

    App.animateNumber = (element, target) => {
        if (!element) return;
        const value = Number(target) || 0;
        element.classList.remove('skeleton');
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || value > 9999) {
            element.textContent = String(value);
            return;
        }
        const duration = 550;
        const start = performance.now();
        const tick = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = String(Math.round(value * eased));
            if (progress < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };

    App.emptyState = (icon, title, text = '') => `
        <div class="empty-state">
            <span class="empty-icon"><i class="bi ${icon}"></i></span>
            <h4>${App.escapeHtml(title)}</h4>
            ${text ? `<p>${App.escapeHtml(text)}</p>` : ''}
        </div>`;

    App.statusClass = (status) => {
        if (status === 'pendiente') return 'estado-pendiente';
        if (status === 'seguimiento') return 'estado-seguimiento';
        return 'estado-realizado';
    };

    App.statusLabel = (status) => {
        if (status === 'pendiente') return 'Pendiente';
        if (status === 'seguimiento') return 'En seguimiento';
        return 'Realizado';
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', App.initTheme);
    } else {
        App.initTheme();
    }

    window.App = App;
})();
