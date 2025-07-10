/**
 * eSIM Detail Page JavaScript
 * Modern interactive functionality for detail page
 */

// ===========================================
// GLOBAL VARIABLES & CONFIGURATION
// ===========================================

const DetailApp = {
    // Configuration
    config: {
        toastDuration: 3000,
        animationDuration: 300,
        debounceDelay: 250,
        copyFeedbackDuration: 2000
    },
    
    // State management
    state: {
        isModalOpen: false,
        currentTab: 'ios',
        lastCopyTime: 0,
        touchStartY: 0,
        pullToRefreshThreshold: 100
    },
    
    // DOM elements cache
    elements: {},
    
    // Initialize app
    init() {
        this.cacheElements();
        this.bindEvents();
        this.setupAnimations();
        this.checkDataNotice();
        this.setupPullToRefresh();
        console.log('eSIM Detail App initialized');
    },
    
    // Cache frequently used DOM elements
    cacheElements() {
        this.elements = {
            body: document.body,
            container: document.querySelector('.container'),
            toast: document.getElementById('toast'),
            modal: document.getElementById('dataModal'),
            modalClose: document.querySelector('.modal-close'),
            tabBtns: document.querySelectorAll('.tab-btn'),
            tabPanes: document.querySelectorAll('.tab-pane'),
            copyableElements: document.querySelectorAll('[onclick*="copyToClipboard"]'),
            progressBars: document.querySelectorAll('.progress-fill'),
            actionButtons: document.querySelectorAll('.action-buttons .btn'),
            qrImage: document.querySelector('.qr-image')
        };
    }
};

// ===========================================
// EVENT BINDING
// ===========================================

DetailApp.bindEvents = function() {
    // Tab switching
    this.elements.tabBtns.forEach(btn => {
        btn.addEventListener('click', (e) => this.switchTab(e));
    });
    
    // Modal events
    if (this.elements.modalClose) {
        this.elements.modalClose.addEventListener('click', () => this.closeModal());
    }
    
    if (this.elements.modal) {
        this.elements.modal.addEventListener('click', (e) => {
            if (e.target === this.elements.modal) {
                this.closeModal();
            }
        });
    }
    
    // Copy functionality
    this.elements.copyableElements.forEach(element => {
        element.addEventListener('click', (e) => {
            const textToCopy = this.extractCopyText(element);
            if (textToCopy) {
                this.copyToClipboard(textToCopy);
            }
        });
    });
    
    // Button click animations
    this.elements.actionButtons.forEach(btn => {
        btn.addEventListener('click', (e) => this.animateButtonClick(e));
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => this.handleKeyboard(e));
    
    // Touch events for mobile
    this.setupTouchEvents();
    
    // Page visibility change
    document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
    
    // Online/offline status
    window.addEventListener('online', () => this.handleConnectionChange(true));
    window.addEventListener('offline', () => this.handleConnectionChange(false));
};

// ===========================================
// TAB FUNCTIONALITY
// ===========================================

DetailApp.switchTab = function(event) {
    const button = event.currentTarget;
    const targetTab = button.dataset.tab;
    
    if (this.state.currentTab === targetTab) return;
    
    // Update button states
    this.elements.tabBtns.forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');
    
    // Update tab panes with animation
    this.elements.tabPanes.forEach(pane => {
        pane.classList.remove('active');
        pane.style.opacity = '0';
    });
    
    setTimeout(() => {
        const targetPane = document.getElementById(`${targetTab}-tab`);
        if (targetPane) {
            targetPane.classList.add('active');
            targetPane.style.opacity = '1';
        }
    }, this.config.animationDuration / 2);
    
    this.state.currentTab = targetTab;
    
    // Add haptic feedback if available
    this.hapticFeedback('light');
    
    // Track tab switch
    this.trackEvent('tab_switch', { tab: targetTab });
};

// ===========================================
// COPY TO CLIPBOARD FUNCTIONALITY
// ===========================================

DetailApp.copyToClipboard = function(text) {
    if (!text) return;
    
    // Debounce rapid clicks
    const now = Date.now();
    if (now - this.state.lastCopyTime < this.config.debounceDelay) {
        return;
    }
    this.state.lastCopyTime = now;
    
    // Modern clipboard API
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text)
            .then(() => this.showCopySuccess(text))
            .catch(() => this.fallbackCopy(text));
    } else {
        this.fallbackCopy(text);
    }
};

DetailApp.fallbackCopy = function(text) {
    // Fallback for older browsers
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            this.showCopySuccess(text);
        } else {
            this.showCopyError();
        }
    } catch (err) {
        this.showCopyError();
    }
    
    document.body.removeChild(textArea);
};

DetailApp.extractCopyText = function(element) {
    // Extract text from onclick attribute
    const onclickAttr = element.getAttribute('onclick');
    if (onclickAttr) {
        const match = onclickAttr.match(/copyToClipboard\('([^']+)'\)/);
        if (match) {
            return match[1];
        }
    }
    
    // Fallback to element content
    const codeValue = element.querySelector('.code-value, .iccid-value');
    return codeValue ? codeValue.textContent.trim() : '';
};

DetailApp.showCopySuccess = function(text) {
    // Update toast message based on content type
    let message = 'Berhasil disalin ke clipboard!';
    
    if (text.includes('LPA:1$')) {
        message = 'Berhasil disalin ke clipboard!';
    } else if (text.length > 20 && text.includes('.')) {
        message = 'Berhasil disalin ke clipboard!';
    } else if (text.length > 15) {
        message = 'Berhasil disalin ke clipboard!';
    }
    
    this.showToast(message, 'success');
    this.hapticFeedback('medium');
    this.trackEvent('copy_success', { type: this.getCopyType(text) });
};

DetailApp.showCopyError = function() {
    this.showToast('Gagal menyalin teks', 'error');
    this.hapticFeedback('heavy');
};

DetailApp.getCopyType = function(text) {
    if (text.includes('LPA:1$')) return 'android_code';
    if (text.includes('.')) return 'smdp_address';
    if (text.length > 15) return 'iccid';
    return 'activation_code';
};

// ===========================================
// TOAST NOTIFICATIONS
// ===========================================

DetailApp.showToast = function(message, type = 'success') {
    if (!this.elements.toast) return;
    
    // Update message and icon
    const messageEl = this.elements.toast.querySelector('.toast-message');
    const iconEl = this.elements.toast.querySelector('i');
    
    if (messageEl) messageEl.textContent = message;
    
    if (iconEl) {
        iconEl.className = type === 'success' 
            ? 'fas fa-check-circle' 
            : 'fas fa-exclamation-circle';
    }
    
    // Show toast with animation
    this.elements.toast.classList.add('show');
    
    // Auto hide
    setTimeout(() => {
        this.elements.toast.classList.remove('show');
    }, this.config.toastDuration);
};

// ===========================================
// MODAL FUNCTIONALITY
// ===========================================

DetailApp.openModal = function() {
    if (!this.elements.modal || this.state.isModalOpen) return;
    
    this.state.isModalOpen = true;
    this.elements.modal.classList.add('show');
    this.elements.modal.style.display = 'flex';
    
    // Prevent body scroll
    this.elements.body.style.overflow = 'hidden';
    
    // Focus management
    const firstFocusable = this.elements.modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) firstFocusable.focus();
    
    this.trackEvent('modal_open');
};

DetailApp.closeModal = function() {
    if (!this.elements.modal || !this.state.isModalOpen) return;
    
    this.state.isModalOpen = false;
    this.elements.modal.classList.remove('show');
    
    setTimeout(() => {
        this.elements.modal.style.display = 'none';
        this.elements.body.style.overflow = '';
    }, this.config.animationDuration);
    
    this.trackEvent('modal_close');
};

// ===========================================
// ANIMATIONS & VISUAL EFFECTS
// ===========================================

DetailApp.setupAnimations = function() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe animatable elements
    document.querySelectorAll('.user-card, .usage-card, .qr-section, .activation-section').forEach(el => {
        observer.observe(el);
    });
    
    // Progress bar animations
    this.animateProgressBars();
    
    // Stagger animation for cards
    this.staggerCardAnimations();
};

DetailApp.animateProgressBars = function() {
    this.elements.progressBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.transition = 'width 1s ease-out';
            bar.style.width = width;
        }, 500 + (index * 200));
    });
};

DetailApp.staggerCardAnimations = function() {
    const cards = document.querySelectorAll('.user-card, .usage-card, .qr-section, .activation-section');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
};

DetailApp.animateButtonClick = function(event) {
    const button = event.currentTarget;
    
    // Create ripple effect
    const ripple = document.createElement('span');
    ripple.classList.add('ripple');
    
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${event.clientY - rect.top - size / 2}px`;
    
    button.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
};

// ===========================================
// TOUCH & MOBILE FUNCTIONALITY
// ===========================================

DetailApp.setupTouchEvents = function() {
    // Touch-friendly hover effects
    document.addEventListener('touchstart', (e) => {
        const target = e.target.closest('.iccid-card, .code-field, .btn');
        if (target) {
            target.classList.add('touch-active');
        }
    });
    
    document.addEventListener('touchend', (e) => {
        const target = e.target.closest('.iccid-card, .code-field, .btn');
        if (target) {
            setTimeout(() => {
                target.classList.remove('touch-active');
            }, 150);
        }
    });
    
    // Swipe gestures for tabs
    this.setupSwipeGestures();
};

DetailApp.setupSwipeGestures = function() {
    let startX, startY, distX, distY;
    const tabContent = document.querySelector('.tab-content');
    
    if (!tabContent) return;
    
    tabContent.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    tabContent.addEventListener('touchmove', (e) => {
        if (!startX || !startY) return;
        
        distX = e.touches[0].clientX - startX;
        distY = e.touches[0].clientY - startY;
        
        // Prevent scrolling if horizontal swipe
        if (Math.abs(distX) > Math.abs(distY) && Math.abs(distX) > 30) {
            e.preventDefault();
        }
    });
    
    tabContent.addEventListener('touchend', () => {
        if (!startX || !startY) return;
        
        const threshold = 100;
        
        if (Math.abs(distX) > Math.abs(distY) && Math.abs(distX) > threshold) {
            if (distX > 0 && this.state.currentTab === 'android') {
                // Swipe right: Android -> iOS
                document.querySelector('[data-tab="ios"]').click();
            } else if (distX < 0 && this.state.currentTab === 'ios') {
                // Swipe left: iOS -> Android
                document.querySelector('[data-tab="android"]').click();
            }
        }
        
        startX = startY = distX = distY = null;
    });
};

DetailApp.setupPullToRefresh = function() {
    let startY = 0;
    let currentY = 0;
    let isPulling = false;
    
    this.elements.container.addEventListener('touchstart', (e) => {
        if (window.scrollY === 0) {
            startY = e.touches[0].clientY;
            isPulling = true;
        }
    });
    
    this.elements.container.addEventListener('touchmove', (e) => {
        if (!isPulling) return;
        
        currentY = e.touches[0].clientY;
        const pullDistance = currentY - startY;
        
        if (pullDistance > 0 && window.scrollY === 0) {
            e.preventDefault();
            
            if (pullDistance > this.state.pullToRefreshThreshold) {
                this.elements.container.style.transform = `translateY(${Math.min(pullDistance * 0.5, 50)}px)`;
            }
        }
    });
    
    this.elements.container.addEventListener('touchend', () => {
        if (!isPulling) return;
        
        const pullDistance = currentY - startY;
        
        if (pullDistance > this.state.pullToRefreshThreshold) {
            this.refreshData();
        }
        
        this.elements.container.style.transform = '';
        isPulling = false;
        startY = currentY = 0;
    });
};

// ===========================================
// DATA & STATE MANAGEMENT
// ===========================================

DetailApp.checkDataNotice = function() {
    // Show data notice modal for active eSIMs
    if (window.esimData && !window.esimData.isNew && !localStorage.getItem('dataNoticeShown')) {
        setTimeout(() => {
            this.openModal();
            localStorage.setItem('dataNoticeShown', 'true');
        }, 2000);
    }
};

DetailApp.refreshData = function() {
    // Show loading state
    this.showToast('Memperbarui data...', 'info');
    
    // Reload page to get fresh data
    setTimeout(() => {
        window.location.reload();
    }, 1000);
};

DetailApp.handleVisibilityChange = function() {
    if (document.hidden) {
        // Page hidden - pause animations
        this.elements.progressBars.forEach(bar => {
            bar.style.animationPlayState = 'paused';
        });
    } else {
        // Page visible - resume animations
        this.elements.progressBars.forEach(bar => {
            bar.style.animationPlayState = 'running';
        });
    }
};

DetailApp.handleConnectionChange = function(isOnline) {
    if (isOnline) {
        this.showToast('Koneksi tersambung kembali', 'success');
    } else {
        this.showToast('Tidak ada koneksi internet', 'error');
    }
};

// ===========================================
// UTILITY FUNCTIONS
// ===========================================

DetailApp.handleKeyboard = function(event) {
    // Escape key closes modal
    if (event.key === 'Escape' && this.state.isModalOpen) {
        this.closeModal();
    }
    
    // Arrow keys for tab navigation
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        const activeTab = document.querySelector('.tab-btn.active');
        if (activeTab) {
            const tabs = Array.from(this.elements.tabBtns);
            const currentIndex = tabs.indexOf(activeTab);
            let newIndex;
            
            if (event.key === 'ArrowLeft') {
                newIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
            } else {
                newIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
            }
            
            tabs[newIndex].click();
        }
    }
};

DetailApp.hapticFeedback = function(intensity = 'light') {
    if ('vibrate' in navigator) {
        const patterns = {
            light: [10],
            medium: [30],
            heavy: [50]
        };
        navigator.vibrate(patterns[intensity] || patterns.light);
    }
};

DetailApp.trackEvent = function(eventName, data = {}) {
    // Analytics tracking (implement based on your analytics provider)
    if (typeof gtag !== 'undefined') {
        gtag('event', eventName, {
            custom_parameter: data,
            page_title: document.title
        });
    }
    
    console.log(`Event tracked: ${eventName}`, data);
};

DetailApp.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// ===========================================
// GLOBAL FUNCTIONS (for backward compatibility)
// ===========================================

function copyToClipboard(text) {
    DetailApp.copyToClipboard(text);
}

function closeModal() {
    DetailApp.closeModal();
}

function switchActivationTab(tab) {
    const button = document.querySelector(`[data-tab="${tab}"]`);
    if (button) {
        button.click();
    }
}

// ===========================================
// CSS INJECTION FOR ADDITIONAL STYLES
// ===========================================

DetailApp.injectStyles = function() {
    const styles = `
        <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .animate-in {
            animation: slideUpFade 0.6s ease-out forwards;
        }
        
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .touch-active {
            transform: scale(0.98);
            opacity: 0.8;
        }
        
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', styles);
};

// ===========================================
// INITIALIZATION
// ===========================================

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        DetailApp.injectStyles();
        DetailApp.init();
    });
} else {
    DetailApp.injectStyles();
    DetailApp.init();
}

// Service Worker registration (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DetailApp;
}