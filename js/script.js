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
    } else if (input.name === 'student_id' && !/^[A-Za-z0-9]{5,12}$/.test(value)) {
        errorText = 'Enter a valid Student ID (5-12 alphanumeric characters).';
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

// ==========================================
// Practical 6: Fetch API Notice Board Logic
// ==========================================
function initializeNotices() {
    const noticeList = document.getElementById('noticeList');
    const searchInput = document.getElementById('noticeSearch');
    const filterSelect = document.getElementById('noticeFilter');
    const sortSelect = document.getElementById('noticeSort');
    const status = document.getElementById('noticeStatus');
    const countBadge = document.getElementById('noticeCountBadge');
    const resetBtn = document.getElementById('noticeResetBtn');
    const paginationEl = document.getElementById('noticePagination');
    const paginationInfo = document.getElementById('noticePaginationInfo');

    if (!noticeList || !searchInput || !filterSelect || !status) {
        return;
    }

    let notices = [];
    let currentPage = 1;
    const itemsPerPage = 6;

    function renderNotices() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const selectedCategory = filterSelect.value;
        const sortMode = sortSelect ? sortSelect.value : 'newest';

        let filtered = notices.filter(function (notice) {
            const text = `${notice.title} ${notice.content || notice.body || ''}`.toLowerCase();
            const matchSearch = text.includes(searchTerm);
            const matchCat = (selectedCategory === 'all' || notice.category === selectedCategory || String(notice.userId) === selectedCategory);
            return matchSearch && matchCat;
        });

        // Sorting
        filtered.sort((a, b) => {
            if (sortMode === 'newest') return (b.date || '').localeCompare(a.date || '');
            if (sortMode === 'oldest') return (a.date || '').localeCompare(b.date || '');
            if (sortMode === 'title') return a.title.localeCompare(b.title);
            return 0;
        });

        const totalItems = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const pageItems = filtered.slice(startIndex, startIndex + itemsPerPage);

        if (countBadge) {
            countBadge.textContent = `${totalItems} Notices Total`;
        }

        if (totalItems === 0) {
            noticeList.innerHTML = `<div class="col-12"><div class="alert alert-warning">No circulars match your search or filter criteria.</div></div>`;
            status.textContent = '0 circulars found.';
            status.className = 'notice-status mb-3 alert alert-secondary py-2';
            if (paginationEl) paginationEl.innerHTML = '';
            if (paginationInfo) paginationInfo.textContent = 'Showing 0 of 0';
            return;
        }

        status.textContent = `Displaying ${pageItems.length} of ${totalItems} notices (Page ${currentPage} of ${totalPages})`;
        status.className = 'notice-status mb-3 alert alert-success py-2';

        noticeList.innerHTML = pageItems.map(function (n) {
            const priorityClass = n.priority === 'Urgent' ? 'bg-danger' : (n.priority === 'High' ? 'bg-warning text-dark' : 'bg-primary');
            return `<article class="notice-item card shadow-sm p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="notice-category badge bg-light text-primary border">${n.category || 'General'}</span>
                    <span class="badge ${priorityClass}">${n.priority || 'Normal'}</span>
                </div>
                <h5 class="fw-bold mb-1">${n.title}</h5>
                <p class="text-muted small mb-2"><i class="bi bi-calendar"></i> Posted: ${n.date || 'Recent'}</p>
                <p class="mb-0">${n.content || n.body || ''}</p>
            </article>`;
        }).join('');

        // Render Pagination
        if (paginationEl) {
            let paginationHtml = '';
            paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button class="page-link" type="button" data-page="${currentPage - 1}">Previous</button></li>`;

            for (let p = 1; p <= totalPages; p++) {
                paginationHtml += `<li class="page-item ${currentPage === p ? 'active' : ''}">
                    <button class="page-link" type="button" data-page="${p}">${p}</button></li>`;
            }

            paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <button class="page-link" type="button" data-page="${currentPage + 1}">Next</button></li>`;

            paginationEl.innerHTML = paginationHtml;
            paginationEl.querySelectorAll('button.page-link').forEach(btn => {
                btn.addEventListener('click', function () {
                    const targetPage = parseInt(this.getAttribute('data-page'), 10);
                    if (targetPage >= 1 && targetPage <= totalPages) {
                        currentPage = targetPage;
                        renderNotices();
                    }
                });
            });
        }

        if (paginationInfo) {
            paginationInfo.textContent = `Showing ${startIndex + 1} to ${Math.min(startIndex + itemsPerPage, totalItems)} of ${totalItems} entries`;
        }
    }

    searchInput.addEventListener('input', () => { currentPage = 1; renderNotices(); });
    filterSelect.addEventListener('change', () => { currentPage = 1; renderNotices(); });
    if (sortSelect) sortSelect.addEventListener('change', () => { currentPage = 1; renderNotices(); });
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterSelect.value = 'all';
            if (sortSelect) sortSelect.value = 'newest';
            currentPage = 1;
            renderNotices();
        });
    }

    // Fetch notices with caching fallback
    fetch('data/notices.json')
        .then(res => {
            if (!res.ok) throw new Error('Failed to load data/notices.json');
            return res.json();
        })
        .then(data => {
            notices = data;
            try { localStorage.setItem('cached_notices', JSON.stringify(data)); } catch (e) {}
            populateCategories();
            renderNotices();
        })
        .catch(err => {
            const cached = localStorage.getItem('cached_notices');
            if (cached) {
                notices = JSON.parse(cached);
                populateCategories();
                renderNotices();
                status.textContent = 'Loaded circulars from offline cache.';
            } else {
                status.textContent = 'Unable to fetch notices. Please check data/notices.json.';
                status.className = 'notice-status mb-3 alert alert-danger py-2';
            }
        });

    function populateCategories() {
        const categories = [...new Set(notices.map(n => n.category || 'General'))].filter(Boolean).sort();
        filterSelect.innerHTML = '<option value="all">All Categories</option>';
        categories.forEach(cat => {
            filterSelect.insertAdjacentHTML('beforeend', `<option value="${cat}">${cat}</option>`);
        });
    }
}

// ==========================================
// Practical 6: Fetch API Events Directory
// ==========================================
function initializeEventsDirectory() {
    const eventGrid = document.getElementById('eventGrid');
    const searchInput = document.getElementById('eventSearch');
    const filterSelect = document.getElementById('eventCategoryFilter');
    const sortSelect = document.getElementById('eventSortSelect');
    const resetBtn = document.getElementById('eventResetBtn');
    const status = document.getElementById('eventStatus');
    const badge = document.getElementById('eventTotalBadge');
    const paginationControls = document.getElementById('paginationControls');
    const paginationInfo = document.getElementById('paginationInfo');

    if (!eventGrid || !searchInput || !filterSelect) return;

    let events = [];
    let currentPage = 1;
    const itemsPerPage = 6;

    function renderEvents() {
        const query = searchInput.value.trim().toLowerCase();
        const category = filterSelect.value;
        const sort = sortSelect ? sortSelect.value : 'date-asc';

        let filtered = events.filter(ev => {
            const matchSearch = `${ev.title} ${ev.description} ${ev.venue} ${ev.organizer}`.toLowerCase().includes(query);
            const matchCat = category === 'all' || ev.category === category;
            return matchSearch && matchCat;
        });

        // Sorting
        filtered.sort((a, b) => {
            if (sort === 'date-asc') return (a.date || '').localeCompare(b.date || '');
            if (sort === 'date-desc') return (b.date || '').localeCompare(a.date || '');
            if (sort === 'title-asc') return a.title.localeCompare(b.title);
            return 0;
        });

        const totalItems = filtered.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const currentBatch = filtered.slice(startIndex, startIndex + itemsPerPage);

        if (badge) badge.textContent = `${totalItems} Events Listed`;

        if (totalItems === 0) {
            eventGrid.innerHTML = `<div class="col-12"><div class="alert alert-warning text-center">No events found matching your criteria.</div></div>`;
            status.textContent = 'No records matched.';
            status.className = 'alert alert-secondary py-2';
            if (paginationControls) paginationControls.innerHTML = '';
            if (paginationInfo) paginationInfo.textContent = 'Showing 0 of 0';
            return;
        }

        status.textContent = `Showing ${currentBatch.length} of ${totalItems} events (Page ${currentPage} of ${totalPages})`;
        status.className = 'alert alert-success py-2';

        eventGrid.innerHTML = currentBatch.map(ev => {
            return `<div class="col">
                <div class="card h-100 shadow-sm border-0 event-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">${ev.category}</span>
                            <small class="text-muted fw-semibold">${ev.date}</small>
                        </div>
                        <h5 class="card-title text-primary">${ev.title}</h5>
                        <p class="card-text text-secondary small flex-grow-1">${ev.description}</p>
                        <div class="pt-2 border-top text-muted small">
                            <p class="mb-1"><strong>Venue:</strong> ${ev.venue}</p>
                            <p class="mb-2"><strong>Organized by:</strong> ${ev.organizer}</p>
                        </div>
                        <a href="contact.html?subject=${encodeURIComponent(ev.title)}" class="btn btn-outline-primary btn-sm mt-auto">Register Interest</a>
                    </div>
                </div>
            </div>`;
        }).join('');

        if (paginationControls) {
            let html = '';
            html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button class="page-link" type="button" data-page="${currentPage - 1}">Previous</button></li>`;
            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item ${currentPage === i ? 'active' : ''}">
                    <button class="page-link" type="button" data-page="${i}">${i}</button></li>`;
            }
            html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <button class="page-link" type="button" data-page="${currentPage + 1}">Next</button></li>`;

            paginationControls.innerHTML = html;
            paginationControls.querySelectorAll('button.page-link').forEach(btn => {
                btn.addEventListener('click', function () {
                    const p = parseInt(this.getAttribute('data-page'), 10);
                    if (p >= 1 && p <= totalPages) {
                        currentPage = p;
                        renderEvents();
                    }
                });
            });
        }

        if (paginationInfo) {
            paginationInfo.textContent = `Showing ${startIndex + 1} to ${Math.min(startIndex + itemsPerPage, totalItems)} of ${totalItems}`;
        }
    }

    searchInput.addEventListener('input', () => { currentPage = 1; renderEvents(); });
    filterSelect.addEventListener('change', () => { currentPage = 1; renderEvents(); });
    if (sortSelect) sortSelect.addEventListener('change', () => { currentPage = 1; renderEvents(); });
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterSelect.value = 'all';
            if (sortSelect) sortSelect.value = 'date-asc';
            currentPage = 1;
            renderEvents();
        });
    }

    fetch('data/events.json')
        .then(res => {
            if (!res.ok) throw new Error('HTTP error ' + res.status);
            return res.json();
        })
        .then(data => {
            events = data;
            try { localStorage.setItem('cached_events', JSON.stringify(data)); } catch (e) {}
            const categories = [...new Set(events.map(e => e.category))].sort();
            filterSelect.innerHTML = '<option value="all">All Categories</option>';
            categories.forEach(cat => {
                filterSelect.insertAdjacentHTML('beforeend', `<option value="${cat}">${cat}</option>`);
            });
            renderEvents();
        })
        .catch(err => {
            const cached = localStorage.getItem('cached_events');
            if (cached) {
                events = JSON.parse(cached);
                renderEvents();
                status.textContent = 'Loaded events from offline storage.';
            } else {
                status.textContent = 'Error loading events data: ' + err.message;
                status.className = 'alert alert-danger py-2';
            }
        });
}

document.addEventListener('DOMContentLoaded', function () {
    initializeNotices();
    initializeEventsDirectory();

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

    // Form validation handling
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
    });

    try {
        dark_mode();
    } catch (e) {
        console.error('dark_mode init error', e);
    }
});