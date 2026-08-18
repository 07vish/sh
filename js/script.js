console.log('Script loaded successfully.');
let StudentName = "Priyam";
let subject = "WDF";
let semster = 3;
console.log(`Student Name: ${StudentName}, Subject: ${subject}, Semester: ${semster}`);

let college = "Charusat";
let year = 2026;
let isStudent = true;
console.log(`College: ${college}, Year: ${year}, Is Student: ${isStudent}`);

function greetStudent(name) {
    console.log(`Hello, ${name}! Welcome to the ${subject} course.`);
}

greetStudent("Vivek");

function dark_mode(){
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

    // initialize theme toggle after DOM is ready
    try {
        dark_mode();
    } catch (e) {
        console.error('dark_mode init error', e);
    }
});