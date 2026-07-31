/**
 * public/js/main.js
 * Core JS for Univ E-Learning UI interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Form Validation logic
    const forms = document.querySelectorAll('.needs-validation:not(#loginForm)');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (togglePassword && passwordInput && toggleIcon) {
        togglePassword.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('bi-eye-slash', !isPassword);
            toggleIcon.classList.toggle('bi-eye', isPassword);
        });
    }

    // Interactive Login Form Handler
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(event) {
            if (!loginForm.checkValidity()) {
                loginForm.classList.add('was-validated');
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            loginForm.classList.add('was-validated');

            const submitBtn = document.getElementById('loginSubmitBtn');
            const spinner = document.getElementById('loginBtnSpinner');
            const btnText = document.getElementById('loginBtnText');
            const alertContainer = document.getElementById('loginAlertContainer');

            if (submitBtn) submitBtn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
            if (btnText) btnText.textContent = 'Signing in...';

            try {
                const formData = new FormData(loginForm);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    loginForm.submit();
                    return;
                }

                if (data && data.success) {
                    if (btnText) btnText.textContent = 'Redirecting...';
                    window.location.href = data.redirect || 'index.php?page=dashboard';
                } else {
                    if (alertContainer) {
                        let verificationLink = '';
                        if (data && data.verification_email) {
                            verificationLink = `<div class="mt-2"><a class="alert-link" href="?page=verify-email&email=${encodeURIComponent(data.verification_email)}">Enter or resend verification OTP</a></div>`;
                        }
                        const errorMsg = (data && data.error) ? data.error : 'Invalid email/username or password.';
                        alertContainer.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center animate__animated animate__headShake" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                                <div>
                                    ${errorMsg}
                                    ${verificationLink}
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                    }
                    if (submitBtn) submitBtn.disabled = false;
                    if (spinner) spinner.classList.add('d-none');
                    if (btnText) btnText.textContent = 'Sign In';
                }
            } catch (err) {
                loginForm.submit();
            }
        });
    }

    // Dynamic search filter for course catalog
    const searchInput = document.getElementById('courseSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            const courseCards = document.querySelectorAll('.course-card-container');
            let visibleCount = 0;
            
            courseCards.forEach(card => {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const desc = card.querySelector('.card-text').textContent.toLowerCase();
                if (title.includes(term) || desc.includes(term)) {
                    card.style.display = 'block';
                    // add animation class for smooth re-entry
                    card.classList.add('animate__animated', 'animate__fadeIn');
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                    card.classList.remove('animate__animated', 'animate__fadeIn');
                }
            });

            // Handle empty state
            const emptyState = document.getElementById('emptySearchState');
            if (emptyState) {
                if (visibleCount === 0 && courseCards.length > 0) {
                    emptyState.style.display = 'block';
                } else {
                    emptyState.style.display = 'none';
                }
            }
        });
    }

    // Quiz Option selection highlighting
    const quizOptions = document.querySelectorAll('.quiz-option');
    quizOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Find parent question group
            const parent = this.closest('.question-group');
            // Remove selected class from all siblings
            const siblings = parent.querySelectorAll('.quiz-option');
            siblings.forEach(sib => sib.classList.remove('selected'));
            
            // Add to current
            this.classList.add('selected');
            
            // check the radio input
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        });
    });

    // Sidebar toggler
    const toggleBtn = document.getElementById('menu-toggle');
    const closeBtn = document.getElementById('sidebar-close');
    const wrapper = document.getElementById('wrapper');
    const sidebar = document.getElementById('sidebar-wrapper');
    const sidebarLinks = document.querySelectorAll('#sidebar-wrapper a');

    const isMobileSidebar = () => window.matchMedia('(max-width: 991.98px)').matches;

    const syncToggleButton = () => {
        if (!toggleBtn || !wrapper) return;

        const isToggled = wrapper.classList.contains('toggled');
        const isExpanded = isMobileSidebar() ? isToggled : !isToggled;
        toggleBtn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        toggleBtn.setAttribute('aria-label', isExpanded ? 'Collapse navigation' : 'Expand navigation');
    };

    const setSidebarToggled = (isToggled) => {
        if (!wrapper) return;
        wrapper.classList.toggle('toggled', isToggled);
        syncToggleButton();
    };

    if (toggleBtn && wrapper) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            setSidebarToggled(!wrapper.classList.contains('toggled'));
        });
        syncToggleButton();
    }

    if (closeBtn && wrapper) {
        closeBtn.addEventListener('click', () => {
            setSidebarToggled(false);
        });
    }

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (isMobileSidebar()) {
                setSidebarToggled(false);
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            setSidebarToggled(false);
        }
    });

    document.addEventListener('click', (e) => {
        if (!wrapper || !sidebar || !isMobileSidebar() || !wrapper.classList.contains('toggled')) {
            return;
        }

        if (sidebar.contains(e.target) || (toggleBtn && toggleBtn.contains(e.target))) {
            return;
        }

        setSidebarToggled(false);
    });

    window.addEventListener('resize', () => {
        if (!isMobileSidebar()) {
            setSidebarToggled(false);
        } else {
            syncToggleButton();
        }
    });

    window.addEventListener('orientationchange', () => {
        setSidebarToggled(false);
    });

    // Student feedback modal
    const feedbackWidget = document.getElementById('studentFeedbackWidget');
    if (feedbackWidget) {
        const hiddenKey = feedbackWidget.getAttribute('data-hidden-key') || 'studentFeedbackHidden';
        const hideBtn = document.getElementById('studentFeedbackHide');
        const restoreBtn = document.getElementById('studentFeedbackRestore');

        try {
            if (localStorage.getItem(hiddenKey) === '1') {
                feedbackWidget.classList.add('is-hidden');
            }
        } catch (e) {}

        if (hideBtn) {
            hideBtn.addEventListener('click', () => {
                feedbackWidget.classList.add('is-hidden');
                try {
                    localStorage.setItem(hiddenKey, '1');
                } catch (e) {}
            });
        }

        if (restoreBtn) {
            restoreBtn.addEventListener('click', () => {
                feedbackWidget.classList.remove('is-hidden');
                try {
                    localStorage.setItem(hiddenKey, '0');
                } catch (e) {}
            });
        }
    }

    const feedbackForm = document.getElementById('studentFeedbackForm');
    if (feedbackForm) {
        const modalEl = document.getElementById('studentFeedbackModal');
        const status = document.getElementById('studentFeedbackStatus');
        const submitBtn = document.getElementById('studentFeedbackSubmit');
        const modal = modalEl && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

        feedbackForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!feedbackForm.checkValidity()) {
                feedbackForm.classList.add('was-validated');
                return;
            }

            if (status) {
                status.className = 'student-feedback-status small text-muted';
                status.textContent = 'Sending feedback...';
            }
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            try {
                const response = await fetch(feedbackForm.getAttribute('action'), {
                    method: 'POST',
                    body: new FormData(feedbackForm),
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Feedback could not be sent.');
                }

                if (status) {
                    status.className = 'student-feedback-status small text-success';
                    status.textContent = data.message || 'Feedback sent successfully.';
                }

                feedbackForm.reset();
                feedbackForm.classList.remove('was-validated');
                window.setTimeout(() => {
                    if (modal) {
                        modal.hide();
                    }
                    if (status) {
                        status.textContent = '';
                    }
                }, 1200);
            } catch (error) {
                if (status) {
                    status.className = 'student-feedback-status small text-danger';
                    status.textContent = error.message || 'Feedback could not be sent right now.';
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
    }
});

// Helper for displaying SweetAlert notifications
window.showNotification = function(title, text, icon = 'success') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: {
                popup: 'colored-toast'
            }
        });
    } else {
        alert(title + ": " + text);
    }
};

// Confirmation modal helper
window.confirmAction = function(e, title = "Are you sure?", text = "You won't be able to revert this!") {
    e.preventDefault();
    const target = e.currentTarget;
    const href = target.getAttribute('href');
    const form = target.closest('form');
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                if (form) {
                    form.submit();
                } else if (href) {
                    window.location.href = href;
                }
            }
        });
    } else {
        if (confirm(title + '\n' + text)) {
            if (form) {
                form.submit();
            } else if (href) {
                window.location.href = href;
            }
        }
    }
};
