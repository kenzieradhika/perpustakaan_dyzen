/**
 * PERPUSTAKAAN DYZEN - Main JavaScript
 * Includes: Animations, Form validations, Notifications, etc.
 */

// ============================================
// DOM Ready
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
    initializeAnimations();
    initializeFormValidations();
    initializeNotifications();
});

// ============================================
// Initialize All Components
// ============================================
function initializeComponents() {
    // Mobile menu handling
    initMobileMenu();
    
    // Tooltips
    initTooltips();
    
    // Modals
    initModals();
    
    // Dropdowns
    initDropdowns();
}

// ============================================
// Mobile Menu
// ============================================
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const closeDrawer = document.getElementById('closeDrawer');
    const drawerOverlay = document.getElementById('drawerOverlay');
    
    if (mobileMenuBtn && mobileDrawer) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileDrawer.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (closeDrawer) {
        closeDrawer.addEventListener('click', () => {
            mobileDrawer.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }
    
    if (drawerOverlay) {
        drawerOverlay.addEventListener('click', () => {
            mobileDrawer.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }
}

// ============================================
// Tooltips
// ============================================
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', (e) => {
            const tooltipText = el.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            tooltip.style.cssText = `
                position: absolute;
                background: #0A2A33;
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 1000;
                white-space: nowrap;
            `;
            const rect = el.getBoundingClientRect();
            tooltip.style.top = (rect.top - 30) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            document.body.appendChild(tooltip);
            
            el.addEventListener('mouseleave', () => {
                tooltip.remove();
            });
        });
    });
}

// ============================================
// Modal Handling
// ============================================
function initModals() {
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.modal-close, .modal-cancel');
    
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) modal.classList.remove('active');
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
}

// ============================================
// Dropdowns
// ============================================
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (trigger && menu) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = menu.classList.contains('show');
                closeAllDropdowns();
                if (!isOpen) {
                    menu.classList.add('show');
                }
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        closeAllDropdowns();
    });
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
    });
}

// ============================================
// Form Validations
// ============================================
function initializeFormValidations() {
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
    });
    
    // Password strength
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }
    
    // Confirm password
    const confirmPassword = document.getElementById('confirmPassword');
    if (confirmPassword && passwordInput) {
        confirmPassword.addEventListener('input', function() {
            validatePasswordMatch(passwordInput.value, this.value);
        });
    }
}

function validateEmail(input) {
    const email = input.value;
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValid = regex.test(email);
    
    if (!isValid && email.length > 0) {
        showFieldError(input, 'Email tidak valid');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

function checkPasswordStrength(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    if (!strengthDiv) return;
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    
    strengthDiv.className = 'password-strength';
    if (password.length === 0) {
        strengthDiv.style.width = '0';
        return;
    }
    
    if (strength <= 2) {
        strengthDiv.classList.add('strength-weak');
    } else if (strength === 3) {
        strengthDiv.classList.add('strength-medium');
    } else {
        strengthDiv.classList.add('strength-strong');
    }
}

function validatePasswordMatch(password, confirm) {
    const matchP = document.getElementById('passwordMatch');
    if (!matchP) return;
    
    if (confirm.length > 0) {
        if (password === confirm) {
            matchP.innerHTML = '✓ Password cocok';
            matchP.style.color = 'var(--color-success)';
            return true;
        } else {
            matchP.innerHTML = '✗ Password tidak cocok';
            matchP.style.color = 'var(--color-danger)';
            return false;
        }
    } else {
        matchP.innerHTML = '';
        return true;
    }
}

function showFieldError(input, message) {
    input.classList.add('error');
    let errorDiv = input.parentElement.querySelector('.field-error');
    if (!errorDiv) {
        errorDiv = document.createElement('p');
        errorDiv.className = 'field-error text-danger text-xs mt-1';
        input.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function clearFieldError(input) {
    input.classList.remove('error');
    const errorDiv = input.parentElement.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// ============================================
// Notifications
// ============================================
function initializeNotifications() {
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.alert, .toast');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => {
                if (msg.parentElement) msg.remove();
            }, 300);
        }, 5000);
    });
}

function showNotification(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' : ''}
                ${type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' : ''}
                ${type === 'warning' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>' : ''}
            </svg>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// ============================================
// Animations
// ============================================
function initializeAnimations() {
    // Smooth scroll for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Fade-in animation on scroll
    const fadeElements = document.querySelectorAll('.fade-in');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    fadeElements.forEach(el => observer.observe(el));
}

// ============================================
// AJAX Helper
// ============================================
async function fetchAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        showNotification('Terjadi kesalahan. Silakan coba lagi.', 'error');
        return null;
    }
}

// ============================================
// Table Search & Filter
// ============================================
function filterTable(searchTerm, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

// ============================================
// Print Function
// ============================================
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print - PERPUSTAKAAN DYZEN</title>
                <link rel="stylesheet" href="../assets/css/style.css">
                <style>
                    body { padding: 20px; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                ${element.innerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// ============================================
// Export to CSV
// ============================================
function exportToCSV(data, filename = 'export.csv') {
    const headers = Object.keys(data[0]);
    const csvRows = [];
    
    csvRows.push(headers.join(','));
    
    for (const row of data) {
        const values = headers.map(header => {
            const value = row[header] || '';
            return `"${String(value).replace(/"/g, '""')}"`;
        });
        csvRows.push(values.join(','));
    }
    
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// ============================================
// Copy to Clipboard
// ============================================
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Berhasil disalin ke clipboard!', 'success');
    }).catch(() => {
        showNotification('Gagal menyalin ke clipboard', 'error');
    });
}