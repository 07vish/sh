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
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const message = form.querySelector('.form-message');
            message.classList.remove('success', 'error');

            if (!form.checkValidity()) {
                message.textContent = 'Please complete all required fields before submitting.';
                message.classList.add('error');
                return;
            }

            if (form.id === 'registerForm') {
                const password = form.querySelector('input[name="password"]').value;
                const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
                if (password !== confirmPassword) {
                    message.textContent = 'Passwords do not match. Please try again.';
                    message.classList.add('error');
                    return;
                }

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