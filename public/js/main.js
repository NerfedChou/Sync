/**
 * Main Application Module
 * Handles global application initialization and utilities
 */

class App {
    constructor() {
        this.isInitialized = false;
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    /**
     * Initialize the application
     */
    async init() {
        try {
            // Set up global error handling
            this.setupErrorHandling();
            
            // Initialize theme
            this.initTheme();
            
            // Set up global event listeners
            this.setupGlobalEventListeners();
            
            // Initialize service worker for PWA capabilities (optional)
            this.initServiceWorker();
            
            this.isInitialized = true;
            console.log('Application initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize application:', error);
            this.handleCriticalError(error);
        }
    }

    /**
     * Setup global error handling
     */
    setupErrorHandling() {
        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            this.showError('An unexpected error occurred');
            event.preventDefault();
        });

        // Handle JavaScript errors
        window.addEventListener('error', (event) => {
            console.error('JavaScript error:', event.error);
            this.showError('An unexpected error occurred');
        });
    }

    /**
     * Initialize theme
     */
    initTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        
        // Listen for system theme changes
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener((e) => {
                if (!localStorage.getItem('theme')) {
                    this.theme = e.matches ? 'dark' : 'light';
                    document.documentElement.setAttribute('data-theme', this.theme);
                }
            });
        }
    }

    /**
     * Setup global event listeners
     */
    setupGlobalEventListeners() {
        // Handle online/offline status
        window.addEventListener('online', () => {
            this.showSuccess('Connection restored');
            this.hideOfflineNotification();
        });

        window.addEventListener('offline', () => {
            this.showOfflineNotification();
            this.showError('Connection lost');
        });

        // Handle visibility change (pause/resume operations)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseBackgroundTasks();
            } else {
                this.resumeBackgroundTasks();
            }
        });

        // Handle keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });

        // Handle navigation
        window.addEventListener('popstate', (e) => {
            this.handleNavigation(e);
        });
    }

    /**
     * Initialize service worker for PWA capabilities
     */
    async initServiceWorker() {
        // Skip service worker registration in development or if sw.js doesn't exist
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            try {
                // Check if sw.js exists before attempting registration
                const response = await fetch('/sw.js', { method: 'HEAD' });
                if (response.ok) {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    console.log('Service Worker registered:', registration);
                }
            } catch (error) {
                // Silently fail - service worker is optional
                console.log('Service Worker not available:', error.message);
            }
        }
    }

    /**
     * Handle keyboard shortcuts
     */
    handleKeyboardShortcuts(e) {
        // Only handle shortcuts when not typing in input fields
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }

        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            this.openSearch();
        }

        // Ctrl/Cmd + / for keyboard shortcuts help
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            this.showKeyboardShortcuts();
        }

        // Escape to close modals
        if (e.key === 'Escape') {
            this.closeModals();
        }
    }

    /**
     * Handle navigation
     */
    handleNavigation(e) {
        // Update active navigation state
        this.updateNavigationState();
    }

    /**
     * Update navigation active state
     */
    updateNavigationState() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const linkPath = link.getAttribute('href');
            if (linkPath === currentPath || 
                (currentPath === '/' && linkPath === 'index.html') ||
                (currentPath.endsWith('/') && linkPath === currentPath.split('/').pop() + '.html')) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Pause background tasks when page is hidden
     */
    pauseBackgroundTasks() {
        // Clear intervals, pause data fetching, etc.
        if (window.dashboard && typeof window.dashboard.pauseAutoRefresh === 'function') {
            window.dashboard.pauseAutoRefresh();
        }
    }

    /**
     * Resume background tasks when page is visible
     */
    resumeBackgroundTasks() {
        // Resume intervals, data fetching, etc.
        if (window.dashboard && typeof window.dashboard.resumeAutoRefresh === 'function') {
            window.dashboard.resumeAutoRefresh();
        }
    }

    /**
     * Show offline notification
     */
    showOfflineNotification() {
        let notification = document.getElementById('offline-notification');
        
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'offline-notification';
            notification.className = 'offline-notification';
            notification.innerHTML = `
                <div class="offline-notification__content">
                    <svg class="offline-notification__icon" viewBox="0 0 24 24">
                        <path d="M1 9l2-2v8a2 2 0 002 2h14a2 2 0 002-2V7l2 2V2h-3l-2 2H6L4 2H1v7zm5 4a3 3 0 106 0 3 3 0 00-6 0zm12 0a3 3 0 106 0 3 3 0 00-6 0z"/>
                    </svg>
                    <span>You are currently offline</span>
                </div>
            `;
            document.body.appendChild(notification);
        }
        
        notification.classList.add('offline-notification--visible');
    }

    /**
     * Hide offline notification
     */
    hideOfflineNotification() {
        const notification = document.getElementById('offline-notification');
        if (notification) {
            notification.classList.remove('offline-notification--visible');
        }
    }

    /**
     * Open search modal
     */
    openSearch() {
        // Implement search modal functionality
        console.log('Opening search modal');
    }

    /**
     * Show keyboard shortcuts help
     */
    showKeyboardShortcuts() {
        // Implement keyboard shortcuts modal
        console.log('Showing keyboard shortcuts');
    }

    /**
     * Close all modals
     */
    closeModals() {
        // Close any open modals
        const modals = document.querySelectorAll('.modal.show, .modal--active');
        modals.forEach(modal => {
            modal.classList.remove('show', 'modal--active');
            modal.style.display = 'none';
        });
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showToast(message, 'error');
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showToast(message, 'success');
    }

    /**
     * Show info message
     */
    showInfo(message) {
        this.showToast(message, 'info');
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Use existing toast container if available (from settings page)
        const toastContainer = document.getElementById('toast-container') || document.body;
        
        // Remove existing toast if any
        const existingToast = toastContainer.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');
        
        const icon = this.getToastIcon(type);
        
        toast.innerHTML = `
            <div class="toast-content">
                ${icon}
                <span>${message}</span>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Auto remove after delay
        const delay = type === 'error' ? 5000 : 3000;
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, delay);
    }

    /**
     * Get toast icon based on type
     */
    getToastIcon(type) {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-exclamation-circle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        return icons[type] || icons.info;
    }

    /**
     * Handle critical errors
     */
    handleCriticalError(error) {
        console.error('Critical error occurred:', error);
        
        // Show user-friendly error message
        document.body.innerHTML = `
            <div class="critical-error">
                <div class="critical-error__content">
                    <h1>Something went wrong</h1>
                    <p>We're sorry, but the application encountered an error and couldn't continue.</p>
                    <button onclick="window.location.reload()" class="btn btn--primary">
                        Reload Page
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Get application version
     */
    getVersion() {
        return '1.0.0';
    }

    /**
     * Get application environment
     */
    getEnvironment() {
        return process.env.NODE_ENV || 'development';
    }

    /**
     * Check if application is online
     */
    isOnline() {
        return navigator.onLine;
    }

    /**
     * Format file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Global utility functions
window.utils = {
    formatCurrency: (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(value);
    },
    
    formatDate: (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    formatDateTime: (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    generateId: () => {
        return Math.random().toString(36).substr(2, 9);
    },
    
    copyToClipboard: async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            console.error('Failed to copy text: ', err);
            return false;
        }
    }
};

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = App;
}