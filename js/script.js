document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('nav a').forEach(function (link) {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

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
            }

            message.textContent = 'Great! Your information has been recorded for this demo site.';
            message.classList.add('success');
        });
    });
});