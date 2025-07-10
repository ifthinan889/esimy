// Dashboard Admin JavaScript - Selaras dengan eSIM Portal

// Bottom Navigation Active State Management
document.addEventListener('DOMContentLoaded', function() {
    // Set active nav item based on current page
    const currentPage = window.location.pathname.split('/').pop();
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    // Add click animation to nav items
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Add click effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'translateY(-2px)';
            }, 100);
        });
    });
});

// Simple theme toggle - Default Dark Mode
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    // Set default ke dark jika belum ada setting
    let savedTheme = localStorage.getItem('theme') || 'dark';
    
    // Set theme
    document.documentElement.setAttribute('data-theme', savedTheme);
    localStorage.setItem('theme', savedTheme);
    
    const themeIcon = document.getElementById('themeIcon');
    updateThemeIcon(savedTheme, themeIcon);
    
    // Simple toggle: dark <-> light
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add transition class
        document.body.classList.add('theme-transitioning');
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        updateThemeIcon(newTheme, themeIcon);
        
        // Add animation effect
        this.style.transform = 'scale(0.8) rotate(180deg)';
        setTimeout(() => {
            this.style.transform = 'scale(1) rotate(0deg)';
            document.body.classList.remove('theme-transitioning');
        }, 300);
        
        // Show notification
        showNotification(
            `${newTheme === 'dark' ? '🌙 Dark' : '☀️ Light'} mode activated`, 
            'success', 
            2000
        );
    });
}

function updateThemeIcon(theme, iconElement) {
    if (theme === 'dark') {
        iconElement.innerHTML = '☀️';
        iconElement.setAttribute('title', 'Switch to light mode');
    } else {
        iconElement.innerHTML = '🌙';
        iconElement.setAttribute('title', 'Switch to dark mode');
    }
}

// Initialize theme toggle when DOM is loaded
document.addEventListener('DOMContentLoaded', initThemeToggle);

// Cari elemen <div> yang tadi di HTML
const dashDiv = document.getElementById('dashboard-data');

// Cek kalau ketemu (biar aman, error-safe)
if (dashDiv) {
    // Ambil nilai dari attribute data-exchange-rate
    const exchangeRate = dashDiv.dataset.exchangeRate;
    console.log('Rate dari PHP:', exchangeRate); // Hasil: 16500
    // Sekarang variable exchangeRate bisa kamu pakai di mana saja di JS-mu!
}

// Auto-hide success message
setTimeout(() => {
    const message = document.querySelector('.message.success');
    if (message) {
        message.style.opacity = '0';
        setTimeout(() => {
            message.style.display = 'none';
        }, 300);
    }
}, 5000);

// Enhanced stats animation
function animateStats() {
    const statValues = document.querySelectorAll('.stat-value');
    
    statValues.forEach(stat => {
        const finalValue = parseInt(stat.textContent.replace(/[^0-9]/g, ''));
        if (finalValue > 0) {
            let currentValue = 0;
            const increment = finalValue / 50;
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                stat.textContent = Math.floor(currentValue).toLocaleString();
            }, 30);
        }
    });
}

// Initialize stats animation when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(animateStats, 500);
});

// Enhanced table interactions
function initTableEnhancements() {
    const tables = document.querySelectorAll('.order-table');
    
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
                this.style.boxShadow = 'var(--shadow-md)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
                this.style.boxShadow = 'none';
            });
        });
    });
}

// Initialize table enhancements
document.addEventListener('DOMContentLoaded', initTableEnhancements);

// Form validation
const settingsForm = document.querySelector('.settings-form');
if (settingsForm) {
    settingsForm.addEventListener('submit', function(e) {
        const emailInput = document.getElementById('admin_email');
        const markupInput = document.getElementById('markup_percentage');
        
        // Validate email
        if (emailInput && emailInput.value && !validateEmail(emailInput.value)) {
            e.preventDefault();
            showNotification('Email admin tidak valid', 'error');
            emailInput.focus();
            return;
        }
        
        // Validate markup percentage
        if (markupInput && markupInput.value && 
            (isNaN(markupInput.value) || markupInput.value < 0 || markupInput.value > 100)) {
            e.preventDefault();
            showNotification('Persentase markup harus berupa angka antara 0-100', 'error');
            markupInput.focus();
            return;
        }
    });
}

// Email validation function
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Enhanced notification system
function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-text">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: var(--gradient-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-xl);
        padding: var(--space-lg);
        box-shadow: var(--shadow-xl);
        max-width: 400px;
        animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    `;
    
    // Add type-specific styling
    if (type === 'success') {
        notification.style.borderLeft = '4px solid var(--success-color)';
    } else if (type === 'error') {
        notification.style.borderLeft = '4px solid var(--danger-color)';
    } else if (type === 'warning') {
        notification.style.borderLeft = '4px solid var(--warning-color)';
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => notification.remove(), 400);
        }
    }, duration);
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return '✅';
        case 'error': return '❌';
        case 'warning': return '⚠️';
        default: return 'ℹ️';
    }
}

// Console welcome message
console.log(`
🎉 eSIM Portal Admin Dashboard
🚀 Version: 2.0 - PDO Edition
💻 Powered by Modern CSS & JavaScript
🎨 Design: Gen Z Style with Gradients
📱 Mobile First & Responsive
🌙 Dark Mode Support
⚡ Enhanced Performance
🔒 PDO Security

Features:
• Bottom Navigation (Mobile-First)
• Dark Mode Toggle
• Animated Statistics
• Enhanced Tables
• Form Validation
• Notifications System
`);
