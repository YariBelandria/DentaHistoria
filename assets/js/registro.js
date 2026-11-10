(function () {
    'use strict';

    const form = document.getElementById('registerForm');
    if (!form) return;

    const alertBox = document.getElementById('registerAlert');
    const alertText = document.getElementById('registerAlertText');
    const submitBtn = document.getElementById('registerButton');
    const spinner = document.getElementById('registerSpinner');
    const nombreInput = document.getElementById('nombre');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const codigoInput = document.getElementById('codigo');

    const fieldMap = {
        nombre: { input: nombreInput, error: 'nombreError' },
        email: { input: emailInput, error: 'emailError' },
        password: { input: passwordInput, error: 'passwordError' },
        password_confirm: { input: passwordConfirmInput, error: 'passwordConfirmError' },
        codigo: { input: codigoInput, error: 'codigoError' },
    };

    const showError = (message) => {
        alertText.textContent = message;
        alertBox.classList.remove('d-none');
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const clearErrors = () => {
        alertBox.classList.add('d-none');
        Object.values(fieldMap).forEach(({ input, error }) => {
            if (input) input.classList.remove('is-invalid');
            const box = document.getElementById(error);
            if (box) box.textContent = '';
        });
    };

    const setFieldError = (field, message) => {
        const target = fieldMap[field];
        if (!target) return;
        if (target.input) target.input.classList.add('is-invalid');
        const box = document.getElementById(target.error);
        if (box) box.textContent = message;
    };

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target || '');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.innerHTML = `<i class="bi ${isPassword ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
            button.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        const nombre = nombreInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const passwordConfirm = passwordConfirmInput.value;
        const codigo = codigoInput ? codigoInput.value.trim() : '';

        let invalid = false;

        if (nombre.length < 3) {
            setFieldError('nombre', 'Escribe tu nombre completo.');
            invalid = true;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setFieldError('email', 'Ingresa un correo electrónico válido.');
            invalid = true;
        }
        if (password.length < 8) {
            setFieldError('password', 'La contraseña debe tener al menos 8 caracteres.');
            invalid = true;
        }
        if (password !== passwordConfirm) {
            setFieldError('password_confirm', 'Las contraseñas no coinciden.');
            invalid = true;
        }
        if (codigoInput && !codigo) {
            setFieldError('codigo', 'Escribe el código de invitación.');
            invalid = true;
        }

        if (invalid) {
            showError('Revisa los datos marcados en el formulario.');
            return;
        }

        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitBtn.querySelector('.btn-label').textContent = 'Creando cuenta…';

        try {
            const data = new FormData(form);
            const result = await App.apiFetch('api/auth.php?action=register', {
                method: 'POST',
                body: data,
            });

            submitBtn.querySelector('.btn-label').textContent = '¡Cuenta creada!';
            submitBtn.classList.add('is-success');
            window.location.href = result.data?.redirect || 'dashboard.php';
        } catch (error) {
            showError(error.message || 'No se pudo crear la cuenta.');
            const errors = error.payload?.errors || {};
            Object.keys(errors).forEach((field) => setFieldError(field, errors[field]));
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
            submitBtn.querySelector('.btn-label').textContent = 'Crear cuenta';
            passwordInput.select();
        }
    });
})();
