const defaultHeading = 'Welcome to StudentHub';

function dark_mode() {
    const toggleBtn = document.getElementById('themesBtn');
    const currentTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (currentTheme === 'dark' || (!currentTheme && prefersDark)) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }
}

function validateRegistrationField(input, form) {
    const error = document.getElementById(`${input.id}-error`);
    let errorText = '';
    const value = input.value.trim();

    if (input.name === 'full_name' && !/^[A-Za-z ]{2,}$/.test(value)) {
        errorText = 'Enter your full name using at least 2 letters.';
    } else if (input.name === 'email' && !input.validity.valid) {
        errorText = 'Enter a valid email address, for example name@example.com.';
    } else if (input.name === 'password' && input.value.length < 8) {
        errorText = 'Password must contain at least 8 characters.';
    } else if (input.name === 'confirm_password' && input.value !== form.querySelector('[name="password"]').value) {
        errorText = 'Passwords do not match.';
    }

    input.classList.toggle('field-invalid', Boolean(errorText));
    input.classList.toggle('field-valid', !errorText && value.length > 0);
    input.setAttribute('aria-invalid', String(Boolean(errorText)));
    if (error) {
        error.textContent = errorText;
    }
    return !errorText;
}

function validateRegistrationForm(form) {
    const fields = form.querySelectorAll('input[name="full_name"], input[name="email"], input[name="password"], input[name="confirm_password"]');
    let isValid = true;
    fields.forEach(function (field) {
        if (!validateRegistrationField(field, form)) {
            isValid = false;
        }
    });
    return isValid;
}

document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('nav a').forEach(function (link) {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    const heading = document.getElementById('heroHeading');
    const savedHeading = localStorage.getItem('heroHeading');
    if (heading && savedHeading) {
        heading.textContent = savedHeading;
    }

    const changeButton = document.getElementById('changeBtn');
    if (changeButton && heading) {
        changeButton.addEventListener('click', function () {
            const nextHeading = heading.textContent === defaultHeading
                ? 'Learn, Track, Succeed'
                : defaultHeading;
            heading.textContent = nextHeading;
            localStorage.setItem('heroHeading', nextHeading);
        });
    }

    const resetButton = document.getElementById('resetBtn');
    if (resetButton) {
        resetButton.addEventListener('click', function () {
            localStorage.removeItem('heroHeading');
            localStorage.removeItem('student');
            localStorage.removeItem('rememberedUsername');
            localStorage.removeItem('loggedIn');
            localStorage.setItem('theme', 'light');
            if (heading) {
                heading.textContent = defaultHeading;
            }
            document.body.classList.remove('dark-mode');
            window.alert('Saved preferences have been reset.');
        });
    }

    const rememberedUsername = localStorage.getItem('rememberedUsername');
    const usernameInput = document.getElementById('username');
    if (usernameInput && rememberedUsername) {
        usernameInput.value = rememberedUsername;
    }

    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(function (form) {
        if (form.id === 'registerForm') {
            form.querySelectorAll('input').forEach(function (input) {
                input.addEventListener('blur', function () {
                    validateRegistrationField(input, form);
                });
                input.addEventListener('input', function () {
                    validateRegistrationField(input, form);
                    if (input.name === 'password') {
                        validateRegistrationField(form.querySelector('[name="confirm_password"]'), form);
                    }
                });
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const message = form.querySelector('.form-message');
            message.classList.remove('success', 'error');

            if (form.id === 'registerForm' && !validateRegistrationForm(form)) {
                message.textContent = 'Please correct the highlighted fields and try again.';
                message.classList.add('error');
                const firstInvalidField = form.querySelector('.field-invalid');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                return;
            }

            if (form.id !== 'registerForm' && !form.checkValidity()) {
                message.textContent = 'Please complete all required fields before submitting.';
                message.classList.add('error');
                return;
            }

            if (form.id === 'registerForm') {
                const student = {
                    name: form.querySelector('[name="full_name"]').value.trim(),
                    email: form.querySelector('[name="email"]').value.trim()
                };
                localStorage.setItem('student', JSON.stringify(student));
            }

            if (form.id === 'loginForm') {
                const remember = form.querySelector('[name="remember"]').checked;
                const username = form.querySelector('[name="username"]').value.trim();
                if (remember) {
                    localStorage.setItem('rememberedUsername', username);
                } else {
                    localStorage.removeItem('rememberedUsername');
                }
                localStorage.setItem('loggedIn', 'true');
            }

            message.textContent = form.id === 'loginForm'
                ? 'Login successful for this demo site.'
                : 'Registration successful. Your details were saved in this browser.';
            message.classList.add('success');
        });
    });

    // initialize theme toggle after DOM is ready
    try {
        dark_mode();
    } catch (e) {
        console.error('dark_mode init error', e);
    }
});