// assets/js/toast.js
// Toast Notification System

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.init();
    }
    
    init() {
        // Create container if not exists
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'fixed bottom-4 right-4 z-50 space-y-2';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }
    
    show(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} animate-slide-in`;
        
        // Icon based on type
        let icon = '';
        switch(type) {
            case 'success':
                icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                break;
            case 'error':
                icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                break;
            case 'warning':
                icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
                break;
            default:
                icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        }
        
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                ${icon}
                <span>${message}</span>
                <button class="toast-close ml-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;
        
        this.container.appendChild(toast);
        this.toasts.push(toast);
        
        // Add close button handler
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => this.remove(toast));
        
        // Auto remove after duration
        setTimeout(() => this.remove(toast), duration);
        
        // Add animation
        setTimeout(() => toast.classList.add('show'), 10);
    }
    
    remove(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.toasts = this.toasts.filter(t => t !== toast);
        }, 300);
    }
    
    success(message) {
        this.show(message, 'success');
    }
    
    error(message) {
        this.show(message, 'error');
    }
    
    warning(message) {
        this.show(message, 'warning');
    }
    
    info(message) {
        this.show(message, 'info');
    }
}

// Initialize global toast instance
window.toast = new ToastManager();

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    .toast {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        min-width: 300px;
        max-width: 400px;
    }
    
    .toast.show {
        transform: translateX(0);
    }
    
    .toast-success {
        border-left: 4px solid #10B981;
    }
    
    .toast-error {
        border-left: 4px solid #EF4444;
    }
    
    .toast-warning {
        border-left: 4px solid #F59E0B;
    }
    
    .toast-info {
        border-left: 4px solid #3B82F6;
    }
    
    .toast svg {
        flex-shrink: 0;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .animate-slide-in {
        animation: slideIn 0.3s ease;
    }
`;

document.head.appendChild(style);

// Dark mode support
if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    const toastStyle = document.createElement('style');
    toastStyle.textContent = `
        .toast {
            background: #1F2937;
            color: #F3F4F6;
        }
    `;
    document.head.appendChild(toastStyle);
}