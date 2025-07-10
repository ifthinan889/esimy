// ===== LOGIN.JS - Modern Admin Login Script =====

class LoginManager {
    constructor() {
        this.init();
        this.setupEventListeners();
        this.setupTheme();
        this.setupFormValidation();
        this.setupSecurityFeatures();
    }

    init() {
        // Initialize components
        this.loginForm = document.getElementById('loginForm');
        this.loginButton = document.getElementById('loginButton');
        this.usernameInput = document.getElementById('username');
        this.passwordInput = document.getElementById('password');
        this.passwordToggle = document.getElementById('passwordToggle');
        this.themeToggle = document.getElementById('themeToggle');
        this.loginContainer = document.getElementById('loginContainer');
        
        // State management
        this.isLoading = false;
        this.attemptCount = 0;
        this.maxAttempts = 5;
        
        console.log('🚀 Login Manager initialized');
    }

    setupEventListeners() {
        // Form submission
        if (this.loginForm) {
            this.loginForm.addEventListener('submit', this.handleLogin.bind(this));
        }

        // Password toggle
        if (this.passwordToggle) {
            this.passwordToggle.addEventListener('click', this.togglePassword.bind(this));
        }

        // Theme toggle
        if (this.themeToggle) {
            this.themeToggle.addEventListener('click', this.toggleTheme.bind(this));
        }

        // Input events
        if (this.usernameInput) {
            this.usernameInput.addEventListener('input', this.validateInput.bind(this));
            this.usernameInput.addEventListener('focus', this.handleInputFocus.bind(this));
            this.usernameInput.addEventListener('blur', this.handleInputBlur.bind(this));
        }

        if (this.passwordInput) {
            this.passwordInput.addEventListener('input', this.validateInput.bind(this));
            this.passwordInput.addEventListener('focus', this.handleInputFocus.bind(this));
            this.passwordInput.addEventListener('blur', this.handleInputBlur.bind(this));
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', this.handleKeyboard.bind(this));

        // Auto-close alerts
        this.setupAlertAutoClose();

        // Prevent context menu for security
        document.addEventListener('contextmenu', (e) => {
            if (e.target.type === 'password') {
                e.preventDefault();
            }
        });

        // Window events
        window.addEventListener('load', this.handleWindowLoad.bind(this));
        window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
    }

    setupTheme() {
        // Load saved theme or detect system preference
        const savedTheme = localStorage.getItem('admin-theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const theme = savedTheme || systemTheme;
        
        this.setTheme(theme);
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('admin-theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('admin-theme', theme);
        
        if (this.themeToggle) {
            const icon = this.themeToggle.querySelector('.theme-icon');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun theme-icon' : 'fas fa-moon theme-icon';
            }
        }
        
        console.log(`🎨 Theme set to: ${theme}`);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
        
        // Add animation effect
        if (this.themeToggle) {
            this.themeToggle.style.transform = 'scale(0.8) rotate(180deg)';
            setTimeout(() => {
                this.themeToggle.style.transform = '';
            }, 200);
        }
    }

    setupFormValidation() {
        // Real-time validation
        this.validationRules = {
            username: {
                required: true,
                minLength: 3,
                pattern: /^[a-zA-Z0-9_]+$/,
                message: 'Username harus minimal 3 karakter dan hanya boleh mengandung huruf, angka, dan underscore'
            },
            password: {
                required: true,
                minLength: 6,
                message: 'Password harus minimal 6 karakter'
            }
        };
    }

    validateInput(event) {
        const input = event.target;
        const fieldName = input.name;
        const value = input.value.trim();
        const rules = this.validationRules[fieldName];

        if (!rules) return;

        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (rules.required && !value) {
            isValid = false;
            errorMessage = `${fieldName} harus diisi`;
        }

        // Length validation
        if (isValid && rules.minLength && value.length < rules.minLength) {
            isValid = false;
            errorMessage = `${fieldName} minimal ${rules.minLength} karakter`;
        }

        // Pattern validation
        if (isValid && rules.pattern && !rules.pattern.test(value)) {
            isValid = false;
            errorMessage = rules.message;
        }

        // Update UI
        this.updateInputValidation(input, isValid, errorMessage);
        this.updateSubmitButton();
    }

    updateInputValidation(input, isValid, errorMessage) {
        const container = input.closest('.input-container');
        const existingError = container.querySelector('.input-error');

        // Remove existing error
        if (existingError) {
            existingError.remove();
        }

        // Add/remove error class
        if (isValid) {
            input.classList.remove('error');
            container.classList.remove('error');
        } else {
            input.classList.add('error');
            container.classList.add('error');
            
            // Add error message
            const errorElement = document.createElement('div');
            errorElement.className = 'input-error';
            errorElement.textContent = errorMessage;
            container.appendChild(errorElement);
        }
    }

    updateSubmitButton() {
        if (!this.loginButton) return;

        const username = this.usernameInput?.value.trim() || '';
        const password = this.passwordInput?.value.trim() || '';
        
        const isFormValid = username.length >= 3 && password.length >= 6;
        
        this.loginButton.disabled = !isFormValid || this.isLoading;
        
        if (isFormValid && !this.isLoading) {
            this.loginButton.classList.add('ready');
        } else {
            this.loginButton.classList.remove('ready');
        }
    }

    handleInputFocus(event) {
        const container = event.target.closest('.input-container');
        if (container) {
            container.classList.add('focused');
        }
    }

    handleInputBlur(event) {
        const container = event.target.closest('.input-container');
        if (container) {
            container.classList.remove('focused');
        }
    }

    async handleLogin(event) {
        event.preventDefault();
        
        if (this.isLoading) return;
        
        // Validate form
        if (!this.validateForm()) {
            this.showAlert('Mohon lengkapi form dengan benar', 'error');
            return;
        }

        // Check attempt limit
        if (this.attemptCount >= this.maxAttempts) {
            this.showAlert('Terlalu banyak percobaan login. Silakan tunggu beberapa menit.', 'error');
            return;
        }

        this.setLoading(true);
        
        try {
            // Simulate network delay for UX
            await this.delay(800);
            
            // Submit form
            const formData = new FormData(this.loginForm);
            const response = await fetch(this.loginForm.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                // Check if it's a redirect response
                if (response.redirected || response.url.includes('dashboard')) {
                    this.handleLoginSuccess();
                } else {
                    // Check response text for errors
                    const text = await response.text();
                    if (text.includes('error-message') || text.includes('Username atau password salah')) {
                        this.handleLoginError('Username atau password salah!');
                    } else {
                        // Form submission successful, let PHP handle the redirect
                        this.loginForm.submit();
                    }
                }
            } else {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

        } catch (error) {
            console.error('Login error:', error);
            this.handleLoginError('Terjadi kesalahan sistem. Silakan coba lagi.');
        } finally {
            this.setLoading(false);
        }
    }

    validateForm() {
        const username = this.usernameInput?.value.trim() || '';
        const password = this.passwordInput?.value.trim() || '';

        if (!username || !password) {
            return false;
        }

        if (username.length < 3) {
            this.focusInput(this.usernameInput);
            return false;
        }

        if (password.length < 6) {
            this.focusInput(this.passwordInput);
            return false;
        }

        return true;
    }

    handleLoginSuccess() {
        this.showAlert('Login berhasil! Mengalihkan ke dashboard...', 'success');
        
        // Add success animation
        if (this.loginContainer) {
            this.loginContainer.classList.add('login-success');
        }

        // Reset attempts
        this.attemptCount = 0;
        
        // Redirect after animation
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 1500);
    }

    handleLoginError(message) {
        this.attemptCount++;
        this.showAlert(message, 'error');
        
        // Add shake animation
        if (this.loginContainer) {
            this.loginContainer.classList.add('form-error');
            setTimeout(() => {
                this.loginContainer.classList.remove('form-error');
            }, 500);
        }

        // Clear password for security
        if (this.passwordInput) {
            this.passwordInput.value = '';
            this.passwordInput.focus();
        }

        // Show remaining attempts
        const remaining = this.maxAttempts - this.attemptCount;
        if (remaining > 0 && remaining <= 3) {
            setTimeout(() => {
                this.showAlert(`Sisa ${remaining} percobaan lagi`, 'warning');
            }, 2000);
        }
    }

    setLoading(loading) {
        this.isLoading = loading;
        
        if (this.loginButton) {
            if (loading) {
                this.loginButton.classList.add('loading');
                this.loginButton.disabled = true;
            } else {
                this.loginButton.classList.remove('loading');
                this.updateSubmitButton();
            }
        }

        if (this.loginForm) {
            if (loading) {
                this.loginForm.classList.add('form-loading');
            } else {
                this.loginForm.classList.remove('form-loading');
            }
        }
    }

    togglePassword() {
        if (!this.passwordInput || !this.passwordToggle) return;

        const isPassword = this.passwordInput.type === 'password';
        const icon = this.passwordToggle.querySelector('i');

        this.passwordInput.type = isPassword ? 'text' : 'password';
        
        if (icon) {
            icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        }

        // Focus back to input
        this.passwordInput.focus();
        
        // Auto-hide after 3 seconds
        if (isPassword) {
            setTimeout(() => {
                if (this.passwordInput.type === 'text') {
                    this.togglePassword();
                }
            }, 3000);
        }
    }

    showAlert(message, type = 'info', duration = 5000) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <div class="alert-content">
                <i class="fas fa-${this.getAlertIcon(type)} alert-icon"></i>
                <span class="alert-message">${message}</span>
                <button class="alert-close" onclick="closeAlert('${alert.id}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        alert.id = 'alert-' + Date.now();

        // Insert before form
        const form = document.querySelector('.login-form');
        if (form) {
            form.parentNode.insertBefore(alert, form);
        }

        // Auto-remove
        if (duration > 0) {
            setTimeout(() => {
                this.closeAlert(alert.id);
            }, duration);
        }
    }

    getAlertIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    closeAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.style.transform = 'translateY(-100%)';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
    }

    setupAlertAutoClose() {
        // Auto-close existing alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.alert-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeAlert(alert.id);
                });
            }
        });
    }

    setupSecurityFeatures() {
        // Disable right-click on password fields
        // Disable right-click on password fields
       document.addEventListener('contextmenu', (e) => {
           if (e.target.type === 'password') {
               e.preventDefault();
           }
       });

       // Disable F12 and other dev tools shortcuts in production
       if (location.hostname !== 'localhost' && !location.hostname.includes('127.0.0.1')) {
           document.addEventListener('keydown', (e) => {
               if (e.key === 'F12' || 
                   (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                   (e.ctrlKey && e.shiftKey && e.key === 'C') ||
                   (e.ctrlKey && e.key === 'U')) {
                   e.preventDefault();
                   this.showAlert('Developer tools disabled for security', 'warning', 2000);
               }
           });
       }

       // Clear sensitive data on page unload
       window.addEventListener('beforeunload', () => {
           if (this.passwordInput) {
               this.passwordInput.value = '';
           }
       });

       // Prevent password managers from auto-filling wrong fields
       setTimeout(() => {
           this.preventAutoFillLeakage();
       }, 1000);
   }

   preventAutoFillLeakage() {
       // Create hidden honeypot fields to confuse bots
       const honeypot = document.createElement('input');
       honeypot.type = 'text';
       honeypot.name = 'email';
       honeypot.style.position = 'absolute';
       honeypot.style.left = '-9999px';
       honeypot.style.opacity = '0';
       honeypot.setAttribute('tabindex', '-1');
       honeypot.setAttribute('autocomplete', 'off');
       
       if (this.loginForm) {
           this.loginForm.appendChild(honeypot);
       }
   }

   handleKeyboard(event) {
       // Enter key submission
       if (event.key === 'Enter' && !event.shiftKey) {
           if (document.activeElement === this.usernameInput) {
               event.preventDefault();
               this.passwordInput?.focus();
           } else if (document.activeElement === this.passwordInput) {
               event.preventDefault();
               this.handleLogin(new Event('submit'));
           }
       }

       // Escape key - clear form
       if (event.key === 'Escape') {
           this.clearForm();
       }

       // Alt + T - toggle theme
       if (event.altKey && event.key === 't') {
           event.preventDefault();
           this.toggleTheme();
       }

       // Alt + S - show security info
       if (event.altKey && event.key === 's') {
           event.preventDefault();
           this.showSecurityModal();
       }
   }

   clearForm() {
       if (this.usernameInput) this.usernameInput.value = '';
       if (this.passwordInput) this.passwordInput.value = '';
       
       // Remove all validation errors
       document.querySelectorAll('.input-error').forEach(error => error.remove());
       document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
       
       // Focus first input
       this.usernameInput?.focus();
       
       this.showAlert('Form cleared', 'info', 2000);
   }

   focusInput(input) {
       if (input) {
           input.focus();
           input.select();
       }
   }

   handleWindowLoad() {
       // Add loaded class for animations
       document.body.classList.add('loaded');
       
       // Focus first empty input
       setTimeout(() => {
           if (!this.usernameInput?.value) {
               this.usernameInput?.focus();
           } else if (!this.passwordInput?.value) {
               this.passwordInput?.focus();
           }
       }, 500);

       // Check for saved username (if remember me was used)
       this.loadRememberedCredentials();
   }

   handleBeforeUnload() {
       // Clear sensitive data
       if (this.passwordInput) {
           this.passwordInput.value = '';
       }
       
       // Save username if remember me is checked
       this.saveRememberedCredentials();
   }

   loadRememberedCredentials() {
       const rememberMe = localStorage.getItem('admin-remember-me');
       const savedUsername = localStorage.getItem('admin-username');
       
       if (rememberMe === 'true' && savedUsername && this.usernameInput) {
           this.usernameInput.value = savedUsername;
           const rememberCheckbox = document.getElementById('rememberMe');
           if (rememberCheckbox) {
               rememberCheckbox.checked = true;
           }
           // Focus password field
           this.passwordInput?.focus();
       }
   }

   saveRememberedCredentials() {
       const rememberCheckbox = document.getElementById('rememberMe');
       const username = this.usernameInput?.value.trim();
       
       if (rememberCheckbox?.checked && username) {
           localStorage.setItem('admin-remember-me', 'true');
           localStorage.setItem('admin-username', username);
       } else {
           localStorage.removeItem('admin-remember-me');
           localStorage.removeItem('admin-username');
       }
   }

   showSecurityModal() {
       const modal = document.getElementById('securityModal');
       if (modal) {
           modal.classList.add('show');
           
           // Focus close button for accessibility
           const closeBtn = modal.querySelector('.modal-close');
           closeBtn?.focus();
       }
   }

   closeModal(modalId) {
       const modal = document.getElementById(modalId);
       if (modal) {
           modal.classList.remove('show');
       }
   }

   // Utility functions
   delay(ms) {
       return new Promise(resolve => setTimeout(resolve, ms));
   }

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

   // Public API methods
   getTheme() {
       return document.documentElement.getAttribute('data-theme');
   }

   getFormData() {
       return {
           username: this.usernameInput?.value.trim() || '',
           password: this.passwordInput?.value || '',
           rememberMe: document.getElementById('rememberMe')?.checked || false
       };
   }

   setFormData(data) {
       if (data.username && this.usernameInput) {
           this.usernameInput.value = data.username;
       }
       if (data.password && this.passwordInput) {
           this.passwordInput.value = data.password;
       }
       if (data.rememberMe !== undefined) {
           const checkbox = document.getElementById('rememberMe');
           if (checkbox) {
               checkbox.checked = data.rememberMe;
           }
       }
   }

   resetAttempts() {
       this.attemptCount = 0;
   }

   destroy() {
       // Cleanup event listeners
       this.loginForm?.removeEventListener('submit', this.handleLogin);
       this.passwordToggle?.removeEventListener('click', this.togglePassword);
       this.themeToggle?.removeEventListener('click', this.toggleTheme);
       document.removeEventListener('keydown', this.handleKeyboard);
       window.removeEventListener('load', this.handleWindowLoad);
       window.removeEventListener('beforeunload', this.handleBeforeUnload);
       
       console.log('🧹 Login Manager destroyed');
   }
}

// Global functions for HTML onclick handlers
function closeAlert(alertId) {
   if (window.loginManager) {
       window.loginManager.closeAlert(alertId);
   }
}

function closeModal(modalId) {
   if (window.loginManager) {
       window.loginManager.closeModal(modalId);
   }
}

function showForgotPassword() {
   if (window.loginManager) {
       window.loginManager.showAlert(
           'Silakan hubungi administrator sistem untuk reset password', 
           'info', 
           5000
       );
   }
}

function showSecurityInfo() {
   if (window.loginManager) {
       window.loginManager.showSecurityModal();
   }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
   window.loginManager = new LoginManager();
   
   // Add some easter eggs for developers
   console.log(`
   🔐 eSIM Portal Admin Login
   ━━━━━━━━━━━━━━━━━━━━━━━━━━
   
   🎨 Theme: Press Alt+T to toggle
   🔒 Security: Press Alt+S for info
   ⌨️  Shortcuts: Enter to navigate, Esc to clear
   
   Built with ❤️ for modern admins
   `);
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
   if (document.hidden) {
       // Page is hidden - pause animations
       document.body.classList.add('paused');
   } else {
       // Page is visible - resume animations
       document.body.classList.remove('paused');
       
       // Refresh CSRF token if needed
       if (window.loginManager && document.visibilityState === 'visible') {
           // Could implement CSRF token refresh here
       }
   }
});

// Handle online/offline status
window.addEventListener('online', () => {
   if (window.loginManager) {
       window.loginManager.showAlert('Connection restored', 'success', 2000);
   }
});

window.addEventListener('offline', () => {
   if (window.loginManager) {
       window.loginManager.showAlert('No internet connection', 'warning', 0);
   }
});

// Performance monitoring
if ('performance' in window) {
   window.addEventListener('load', () => {
       setTimeout(() => {
           const perfData = performance.getEntriesByType('navigation')[0];
           const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
           
           if (loadTime > 3000) {
               console.warn('🐌 Slow page load detected:', loadTime + 'ms');
           } else {
               console.log('⚡ Page loaded in:', loadTime + 'ms');
           }
       }, 0);
   });
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
   module.exports = LoginManager;
}

// AMD support
if (typeof define === 'function' && define.amd) {
   define('LoginManager', [], () => LoginManager);
}