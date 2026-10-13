<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AJAX / Fetch API Event Management | StudentHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .toast-container-custom {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1080;
        }
        .event-card-item {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">StudentHub Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="events.html">Public Events</a></li>
                <li class="nav-item"><a class="nav-link active" href="events_ajax.php">AJAX CRUD</a></li>
                <li class="nav-item"><a class="nav-link" href="students_manage.php">Students</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin Panel</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Toast notification area -->
<div class="toast-container-custom" id="toastArea"></div>

<main class="site-container my-4">
    <div class="card p-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h3 class="page-title text-primary mb-1">PHP JSON API & AJAX CRUD</h3>
                <p class="text-muted mb-0">Practical 13: Asynchronous Create, Read, Update, and Delete operations using Fetch API without page reload.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="openAddModalBtn">+ Add Event (AJAX)</button>
        </div>

        <!-- Real-time Controls -->
        <div class="row g-3 align-items-end mb-4 p-3 bg-light rounded border">
            <div class="col-md-5">
                <label for="ajaxSearch" class="form-label small fw-semibold">Instant Search</label>
                <input type="search" id="ajaxSearch" class="form-control form-control-sm" placeholder="Type keyword to filter instantly without reload...">
            </div>
            <div class="col-md-4">
                <label for="ajaxCategory" class="form-label small fw-semibold">Category Filter</label>
                <select id="ajaxCategory" class="form-select form-select-sm">
                    <option value="all">All Categories</option>
                    <option value="Technical">Technical</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Cultural">Cultural</option>
                    <option value="Sports">Sports</option>
                    <option value="Placement">Placement</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="reloadApiBtn">Reload from API</button>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading data...</span>
            </div>
            <p class="text-muted mt-2">Connecting to <code>api/events_api.php</code> via Fetch API...</p>
        </div>

        <!-- Live Cards Grid -->
        <div id="eventsContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" style="display: none;"></div>

        <!-- Empty state -->
        <div id="emptyState" class="alert alert-warning text-center py-4 my-3" style="display: none;">
            No campus events found matching your search.
        </div>
    </div>
</main>

<!-- Modal: Add Event (AJAX) -->
<div class="modal fade" id="ajaxAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="ajaxAddForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Event via JSON API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Event Title <span class="text-danger">*</span></label>
                    <input type="text" id="add_title" class="form-control" required placeholder="e.g. NextGen Web Conference">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Category</label>
                    <select id="add_category" class="form-select">
                        <option value="Technical" selected>Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Sports">Sports</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Event Date</label>
                    <input type="date" id="add_date" class="form-control" required value="2026-11-20">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Location / Venue</label>
                    <input type="text" id="add_location" class="form-control" required placeholder="e.g. CSPIT Auditorium">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea id="add_description" class="form-control" rows="3" required placeholder="Event agenda, requirements, and schedule details..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveAddBtn">Save Event (POST)</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Event (AJAX) -->
<div class="modal fade" id="ajaxEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="ajaxEditForm" class="modal-content">
            <input type="hidden" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Event via JSON API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Event Title</label>
                    <input type="text" id="edit_title" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Category</label>
                    <select id="edit_category" class="form-select">
                        <option value="Technical">Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Sports">Sports</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Event Date</label>
                    <input type="date" id="edit_date" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Location</label>
                    <input type="text" id="edit_location" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea id="edit_description" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveEditBtn">Update Event (PUT)</button>
            </div>
        </form>
    </div>
</div>

<footer class="bg-light text-center text-muted py-3 mt-5">
    © 2026 StudentHub Portal | ITUE203 Practical 13 Asynchronous JSON API
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let eventsList = [];
    const addModal = new bootstrap.Modal(document.getElementById('ajaxAddModal'));
    const editModal = new bootstrap.Modal(document.getElementById('ajaxEditModal'));

    function showToast(message, type = 'success') {
        const toastArea = document.getElementById('toastArea');
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} shadow alert-dismissible fade show`;
        alertDiv.innerHTML = `<strong>${type === 'success' ? 'Success!' : 'Notice:'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        toastArea.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 4000);
    }

    async function loadEventsFromApi() {
        const spinner = document.getElementById('loadingSpinner');
        const container = document.getElementById('eventsContainer');
        const emptyState = document.getElementById('emptyState');

        spinner.style.display = 'block';
        container.style.display = 'none';
        emptyState.style.display = 'none';

        try {
            const res = await fetch('api/events_api.php');
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                eventsList = data.data;
                renderEventCards(eventsList);
            } else {
                showToast(data.message || 'Error fetching events', 'danger');
            }
        } catch (err) {
            showToast('API network failure: ' + err.message, 'danger');
        } finally {
            spinner.style.display = 'none';
        }
    }

    function renderEventCards(items) {
        const container = document.getElementById('eventsContainer');
        const emptyState = document.getElementById('emptyState');

        if (items.length === 0) {
            container.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';
        container.style.display = 'flex';
        container.innerHTML = items.map(ev => {
            return `
            <div class="col event-card-item" id="event-card-${ev.id}">
                <div class="card h-100 shadow-sm border">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary-subtle text-primary border">${ev.category}</span>
                            <small class="text-muted">${ev.event_date}</small>
                        </div>
                        <h5 class="card-title text-primary">${ev.title}</h5>
                        <p class="card-text text-secondary small flex-grow-1">${ev.description}</p>
                        <p class="small text-muted mb-3"><strong>Venue:</strong> ${ev.location}</p>
                        <div class="d-flex gap-2 pt-2 border-top">
                            <button class="btn btn-outline-primary btn-sm flex-fill" onclick="openEditModal(${ev.id})">Edit</button>
                            <button class="btn btn-outline-danger btn-sm flex-fill" onclick="deleteEventAjax(${ev.id}, '${ev.title.replace(/'/g, "\\'")}')">Delete</button>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // Live search and filter
    function applyFilter() {
        const query = document.getElementById('ajaxSearch').value.trim().toLowerCase();
        const category = document.getElementById('ajaxCategory').value;

        const filtered = eventsList.filter(ev => {
            const matchesText = `${ev.title} ${ev.description} ${ev.location}`.toLowerCase().includes(query);
            const matchesCat = category === 'all' || ev.category === category;
            return matchesText && matchesCat;
        });

        renderEventCards(filtered);
    }

    document.getElementById('ajaxSearch').addEventListener('input', applyFilter);
    document.getElementById('ajaxCategory').addEventListener('change', applyFilter);
    document.getElementById('reloadApiBtn').addEventListener('click', loadEventsFromApi);
    document.getElementById('openAddModalBtn').addEventListener('click', () => {
        document.getElementById('ajaxAddForm').reset();
        addModal.show();
    });

    // Add Event via POST Fetch
    document.getElementById('ajaxAddForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const payload = {
            title: document.getElementById('add_title').value.trim(),
            category: document.getElementById('add_category').value,
            event_date: document.getElementById('add_date').value,
            location: document.getElementById('add_location').value.trim(),
            description: document.getElementById('add_description').value.trim()
        };

        const saveBtn = document.getElementById('saveAddBtn');
        saveBtn.disabled = true;

        try {
            const res = await fetch('api/events_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                showToast(result.message, 'success');
                addModal.hide();
                loadEventsFromApi(); // reload live state
            } else {
                showToast(result.message || 'Validation error', 'danger');
            }
        } catch (err) {
            showToast('Fetch error: ' + err.message, 'danger');
        } finally {
            saveBtn.disabled = false;
        }
    });

    // Open Edit Modal
    window.openEditModal = function (id) {
        const ev = eventsList.find(item => item.id == id);
        if (!ev) return;

        document.getElementById('edit_id').value = ev.id;
        document.getElementById('edit_title').value = ev.title;
        document.getElementById('edit_category').value = ev.category;
        document.getElementById('edit_date').value = ev.event_date;
        document.getElementById('edit_location').value = ev.location;
        document.getElementById('edit_description').value = ev.description;
        editModal.show();
    };

    // Update Event via PUT Fetch
    document.getElementById('ajaxEditForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const payload = {
            id: document.getElementById('edit_id').value,
            title: document.getElementById('edit_title').value.trim(),
            category: document.getElementById('edit_category').value,
            event_date: document.getElementById('edit_date').value,
            location: document.getElementById('edit_location').value.trim(),
            description: document.getElementById('edit_description').value.trim()
        };

        const saveBtn = document.getElementById('saveEditBtn');
        saveBtn.disabled = true;

        try {
            const res = await fetch('api/events_api.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.success) {
                showToast(result.message, 'success');
                editModal.hide();
                loadEventsFromApi();
            } else {
                showToast(result.message || 'Update failed', 'danger');
            }
        } catch (err) {
            showToast('Network error: ' + err.message, 'danger');
        } finally {
            saveBtn.disabled = false;
        }
    });

    // Delete Event via DELETE Fetch
    window.deleteEventAjax = async function (id, title) {
        if (!confirm(`Are you sure you want to delete '${title}' without reloading the page?`)) {
            return;
        }

        try {
            const res = await fetch(`api/events_api.php?id=${id}`, {
                method: 'DELETE'
            });
            const result = await res.json();
            if (result.success) {
                showToast(result.message, 'success');
                const cardEl = document.getElementById(`event-card-${id}`);
                if (cardEl) {
                    cardEl.style.opacity = '0';
                    setTimeout(() => cardEl.remove(), 300);
                }
                eventsList = eventsList.filter(item => item.id != id);
                if (eventsList.length === 0) {
                    document.getElementById('emptyState').style.display = 'block';
                }
            } else {
                showToast(result.message || 'Deletion error', 'danger');
            }
        } catch (err) {
            showToast('Network error during delete: ' + err.message, 'danger');
        }
    };

    document.addEventListener('DOMContentLoaded', loadEventsFromApi);
</script>
</body>
</html>
