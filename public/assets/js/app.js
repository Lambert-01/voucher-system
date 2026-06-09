// N.HONEST Voucher Management System - JavaScript

// Session timeout management
let sessionWarningTimer;
let sessionTimeout = parseInt(document.querySelector('meta[name="session-timeout"]')?.content || '3600') * 1000;
let lastActivity = Date.now();

function resetSessionTimer() {
    lastActivity = Date.now();
    clearTimeout(sessionWarningTimer);
    hideSessionWarning();
}

function showSessionWarning() {
    hideSessionWarning();
}

function hideSessionWarning() {
    const warning = document.getElementById('session-warning');
    if (warning) {
        warning.remove();
    }
}

// Form validation helper
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        const group = field.closest('.form-group');
        const existingError = group ? group.querySelector('.error-message') : null;
        
        if (existingError) {
            existingError.remove();
        }
        if (group) {
            group.classList.remove('error');
        }
        
        if (!field.value.trim()) {
            isValid = false;
            if (group) {
                group.classList.add('error');
            }
            const error = document.createElement('div');
            error.className = 'error-message';
            error.innerHTML = '<i class="fas fa-exclamation-circle"></i> This field is required';
            field.parentNode.appendChild(error);
        }
    });
    
    return isValid;
}

// Loading state helper
function showLoading(element) {
    element.classList.add('loading');
    element.disabled = true;
}

function hideLoading(element) {
    element.classList.remove('loading');
    element.disabled = false;
}

function ensureAppPopup() {
    let popup = document.getElementById('app-popup');
    if (popup) {
        return popup;
    }

    popup = document.createElement('div');
    popup.id = 'app-popup';
    popup.className = 'app-popup';
    popup.innerHTML = `
        <div class="app-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="app-popup-title">
            <div class="app-popup-icon"><i class="fas fa-info-circle"></i></div>
            <div class="app-popup-copy">
                <h3 id="app-popup-title">Notification</h3>
                <p id="app-popup-message"></p>
            </div>
            <button type="button" class="btn btn-primary app-popup-close">OK</button>
        </div>
    `;
    document.body.appendChild(popup);

    popup.querySelector('.app-popup-close').addEventListener('click', function() {
        popup.classList.remove('show');
    });

    popup.addEventListener('click', function(event) {
        if (event.target === popup) {
            popup.classList.remove('show');
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            popup.classList.remove('show');
        }
    });

    return popup;
}

function showAppPopup(type, title, message, options = {}) {
    const popup = ensureAppPopup();
    popup.className = `app-popup app-popup-${type || 'info'}`;
    popup.querySelector('#app-popup-title').textContent = title || 'Notification';
    popup.querySelector('#app-popup-message').textContent = message || '';
    popup.querySelector('.app-popup-icon i').className = options.icon || 'fas fa-info-circle';
    popup.querySelector('.app-popup-close').style.display = options.hideClose ? 'none' : '';
    popup.classList.add('show');
}

function showServerNotifications() {
    document.querySelectorAll('.app-notification').forEach(notification => {
        const type = notification.dataset.notificationType || 'info';
        const message = notification.dataset.notificationMessage || notification.textContent.trim();
        const titles = {
            success: 'Saved Successfully',
            error: 'Could Not Save',
            warning: 'Please Check',
            info: 'Notice'
        };
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-times-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        showAppPopup(type, titles[type] || 'Notification', message, { icon: icons[type] || icons.info });
    });
}

// Set active navigation
function setActiveNavigation() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-menu a');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (currentPath.includes(link.getAttribute('href').replace(/^[^/]*/, ''))) {
            link.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize session management
    if (document.body.classList.contains('logged-in')) {
        resetSessionTimer();
        
        // Reset timer on any user activity
        ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetSessionTimer, { passive: true });
        });
    }
    
    // Set active navigation
    setActiveNavigation();
    showServerNotifications();
    
    // Auto-focus voucher input on scan page
    const voucherInput = document.getElementById('voucher_no');
    if (voucherInput) {
        voucherInput.focus();
        
        // Auto-submit if QR code is scanned (assuming 12+ characters)
        voucherInput.addEventListener('input', function() {
            if (this.value.length >= 12) {
                setTimeout(() => {
                    this.form.submit();
                }, 100);
            }
        });
    }
    
    // Enhanced form handling
    const forms = document.querySelectorAll('form:not(.no-validation)');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showAppPopup('error', 'Please Complete the Form', 'Some required fields are missing. Fill them and submit again.', {
                    icon: 'fas fa-exclamation-circle'
                });
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                showLoading(submitBtn);
            }

            showAppPopup('info', 'Submitting Form', 'Please wait. The system is sending this form to the database.', {
                icon: 'fas fa-spinner fa-spin',
                hideClose: true
            });
        });
    });
    
    // Format number inputs for money
    const moneyInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    moneyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
    
    // Confirm before deleting
    const deleteButtons = document.querySelectorAll('.btn-danger:not(.no-confirm)');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const action = this.textContent.toLowerCase();
            if (!confirm(`Are you sure you want to ${action}? This cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-refresh dashboards every 30 seconds
    if (window.location.pathname.includes('dashboard')) {
        setInterval(() => {
            const statsGrid = document.querySelector('.stats-grid');
            if (statsGrid && !document.hidden) {
                // Only refresh if page is visible
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const newDoc = parser.parseFromString(html, 'text/html');
                        const newStats = newDoc.querySelector('.stats-grid');
                        if (newStats) {
                            statsGrid.innerHTML = newStats.innerHTML;
                        }
                    })
                    .catch(() => {});
            }
        }, 30000);
    }
});

// Format money display
function formatMoney(amount) {
    return new Intl.NumberFormat('en-RW', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount) + ' RWF';
}

// Print function for voucher cards
function printVoucherCard() {
    window.print();
}

// Export data as CSV
function exportTableAsCSV(tableId, filename) {
    const table = document.getElementById(tableId) || document.querySelector('.table');
    if (!table) return;
    
    const rows = [];
    const tableRows = table.querySelectorAll('tr');
    
    tableRows.forEach(row => {
        const cols = [];
        row.querySelectorAll('th, td').forEach(cell => {
            cols.push('"' + cell.textContent.replace(/"/g, '""') + '"');
        });
        rows.push(cols.join(','));
    });
    
    const csvContent = rows.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    
    window.URL.revokeObjectURL(url);
}
