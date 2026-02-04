/**
 * Main JavaScript File
 * Common functions and utilities
 */

// Show alert message and auto-hide after 5 seconds
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.main-content') || document.body;
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }

    return true;
}

// Password strength indicator
function checkPasswordStrength(password) {
    let strength = 0;
    const feedback = [];

    if (password.length >= 8) strength++;
    else feedback.push('At least 8 characters');

    if (/[a-z]/.test(password)) strength++;
    else feedback.push('Lowercase letter');

    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('Uppercase letter');

    if (/[0-9]/.test(password)) strength++;
    else feedback.push('Number');

    if (/[^A-Za-z0-9]/.test(password)) strength++;
    else feedback.push('Special character');

    return { strength, feedback };
}

// Display password strength
function displayPasswordStrength(passwordInput) {
    const password = passwordInput.value;
    const strengthDiv = passwordInput.parentElement.querySelector('.password-strength');

    if (!strengthDiv) return;

    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }

    const { strength, feedback } = checkPasswordStrength(password);
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthClass = ['danger', 'danger', 'warning', 'info', 'success'];

    strengthDiv.innerHTML = `
        <small class="text-${strengthClass[strength - 1]}">
            Password Strength: ${strengthText[strength - 1]}
        </small>
        ${feedback.length > 0 ? `<br><small class="text-muted">Missing: ${feedback.join(', ')}</small>` : ''}
    `;
}

// Load file preview
function previewFile(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);

    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function (e) {
            if (file.type.startsWith('image/')) {
                preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`;
            } else {
                preview.innerHTML = `<p class="text-muted">${file.name}</p>`;
            }
        };
        reader.readAsDataURL(file);
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Add CSRF token to all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const csrfInput = form.querySelector('input[name="csrf_token"]');
        if (!csrfInput) {
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = token.getAttribute('content');
                form.appendChild(input);
            }
        }
    });
});

// Loading spinner
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.id = 'loading-spinner';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    document.body.appendChild(spinner);
}

function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

// Dashboard-specific animations and interactions
document.addEventListener('DOMContentLoaded', function () {
    // Animate stat cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const animateOnScroll = new IntersectionObserver(function (entries) {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, observerOptions);

    // Observe stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        animateOnScroll.observe(card);
    });

    // Animate admin cards
    const adminCards = document.querySelectorAll('.admin-card');
    adminCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';

        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, (statCards.length * 100) + (index * 150));
    });

    // Add hover effects to quick action items
    const quickActionItems = document.querySelectorAll('.quick-action-item');
    quickActionItems.forEach(item => {
        item.addEventListener('mouseenter', function () {
            this.style.transform = 'translateX(4px)';
        });

        item.addEventListener('mouseleave', function () {
            this.style.transform = 'translateX(0)';
        });
    });

    // Add number counting animation to stat values
    const statValues = document.querySelectorAll('.stat-card-value');
    statValues.forEach(statValue => {
        const targetValue = parseInt(statValue.textContent.replace(/,/g, ''));
        if (!isNaN(targetValue) && targetValue > 0) {
            let currentValue = 0;
            const increment = targetValue / 50;
            const duration = 1500;
            const stepTime = duration / 50;

            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= targetValue) {
                    statValue.textContent = targetValue.toLocaleString();
                    clearInterval(timer);
                } else {
                    statValue.textContent = Math.floor(currentValue).toLocaleString();
                }
            }, stepTime);
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Sidebar toggle functionality
    const sidebarToggle = document.getElementById('adminSidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('Sidebar toggle button clicked');
            if (window.innerWidth >= 992) {
                console.log('Desktop toggle: ' + !document.body.classList.contains('sidebar-collapsed'));
                document.body.classList.toggle('sidebar-collapsed');
            } else {
                console.log('Mobile toggle');
                if (sidebar) sidebar.classList.toggle('show');
                if (sidebarOverlay) sidebarOverlay.classList.toggle('show');
            }
        });
    }

    // Close sidebar on overlay click
    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // Auto-close sidebar on mobile link click
    if (sidebar && window.innerWidth < 992) {
        const sidebarLinks = sidebar.querySelectorAll('.nav-link-item');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function () {
                sidebar.classList.remove('show');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('show');
                }
            });
        });
    }

    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn, .quick-action-item');
    buttons.forEach(button => {
        button.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');

            this.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});

