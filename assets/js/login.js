(function () {
    'use strict';

    const form = document.getElementById('loginForm');
    if (!form) return;

    const alertBox = document.getElementById('loginAlert');
    const alertText = document.getElementById('loginAlertText');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('loginButton');
    const spinner = document.getElementById('loginSpinner');
    const toggleBtn = document.getElementById('togglePassword');

    const showError = (message) => {
        alertText.textContent = message;
        alertBox.classList.remove('d-none');
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const clearError = () => {
        alertBox.classList.add('d-none');
        document.getElementById('emailError').textContent = '';
        document.getElementById('passwordError').textContent = '';
        emailInput.classList.remove('is-invalid');
        passwordInput.classList.remove('is-invalid');
    };

    toggleBtn?.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleBtn.innerHTML = `<i class="bi ${isPassword ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
        toggleBtn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });

    document.querySelectorAll('.demo-account').forEach((button) => {
        button.addEventListener('click', () => {
            emailInput.value = button.dataset.email || '';
            passwordInput.value = button.dataset.password || '';
            clearError();
            submitBtn.focus();
            document.querySelectorAll('.demo-account').forEach((item) => item.classList.remove('is-selected'));
            button.classList.add('is-selected');
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();

        const email = emailInput.value.trim();
        const password = passwordInput.value;
        let invalid = false;

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById('emailError').textContent = 'Ingresa un correo electrónico válido.';
            emailInput.classList.add('is-invalid');
            invalid = true;
        }
        if (!password) {
            document.getElementById('passwordError').textContent = 'Ingresa tu contraseña.';
            passwordInput.classList.add('is-invalid');
            invalid = true;
        }
        if (invalid) return;

        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitBtn.querySelector('.btn-label').textContent = 'Verificando…';

        try {
            const data = new FormData(form);
            const result = await App.apiFetch('api/auth.php?action=login', {
                method: 'POST',
                body: data
            });

            submitBtn.querySelector('.btn-label').textContent = '¡Bienvenido!';
            submitBtn.classList.add('is-success');
            window.location.href = result.data?.redirect || 'dashboard.php';
        } catch (error) {
            showError(error.message || 'No se pudo iniciar sesión.');
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
            submitBtn.querySelector('.btn-label').textContent = 'Iniciar sesión';
            passwordInput.select();
        }
    });
})();
