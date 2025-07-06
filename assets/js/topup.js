/**
 * eSIM Topup JavaScript - Balanced Approach with Smart Batching
 */

// Global variables
let autoCheckInterval = null;
let countdownInterval = null;
let currentCheckDelay = 10; // Start with 10 seconds
let totalElapsed = 0;
let isManualChecking = false;
let expiredTimer = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing balanced topup page...');
    console.log('Current step:', window.topupData?.currentStep);
    
    initializeTheme();
    initializeForm();
    initializePackageSelection();
    initializePaymentStatusChecker();
    initializeExpiredTimer();
    
    console.log('✨ Topup page initialized with balanced approach');
});

/**
 * Theme Management
 */
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(currentTheme);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
            showToast(`Theme changed to ${newTheme} mode`, 'info');
        });
    }
}

function updateThemeIcon(theme) {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.textContent = theme === 'light' ? '🌙' : '☀️';
    }
}

/**
 * Form Management
 */
function initializeForm() {
    console.log('Initializing form...');
    
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            showLoadingOverlay('Processing payment...');
            return true;
        });
    });
}

/**
 * Package Selection with Total Update
 */
function initializePackageSelection() {
    const uniqueCode = window.topupData?.uniqueCode || 0;
    
    // Update total when package selection changes
    document.addEventListener('change', function(e) {
        if (e.target.name === 'package_code' && e.target.checked) {
            updateTotal();
            
            // Visual feedback
            document.querySelectorAll('.package-card').forEach(card => {
                card.classList.remove('selected');
            });
            e.target.closest('.package-card').classList.add('selected');
        }
    });
    
    // Auto-select first package
    const firstPackage = document.querySelector('input[name="package_code"]');
    if (firstPackage) {
        firstPackage.checked = true;
        updateTotal();
        firstPackage.closest('.package-card').classList.add('selected');
    }
}

function updateTotal() {
    const selectedPackage = document.querySelector('input[name="package_code"]:checked');
    const totalElement = document.getElementById('paymentTotal');
    const uniqueCode = window.topupData?.uniqueCode || 0;
    
    if (selectedPackage && totalElement) {
        const packagePrice = parseInt(selectedPackage.getAttribute('data-price'));
        const totalPrice = packagePrice + uniqueCode;
        totalElement.innerHTML = `Total: <strong>Rp ${totalPrice.toLocaleString('id-ID')}</strong>`;
        totalElement.style.color = 'var(--success-color)';
    }
}

/**
 * Smart Payment Status Checker - BALANCED APPROACH
 */
function initializePaymentStatusChecker() {
    if (window.topupData?.currentStep !== 'payment_pending') {
        console.log('Not in payment pending step, skipping status checker');
        return;
    }
    
    console.log('Initializing smart payment status checker...');
    startSmartAutoCheck();
}

function startSmartAutoCheck() {
    console.log('Starting smart auto-check with balanced approach...');
    
    // Clear any existing intervals
    if (autoCheckInterval) clearInterval(autoCheckInterval);
    if (countdownInterval) clearInterval(countdownInterval);
    
    // Start with immediate check
    checkPaymentStatus();
    
    // Set up the smart checking system
    scheduleNextCheck();
}

function scheduleNextCheck() {
    // Dynamic delay based on elapsed time (3-minute window)
    let delay;
    
    if (totalElapsed < 30) {
        // First 30 seconds: Check every 10 seconds
        delay = 10;
    } else if (totalElapsed < 60) {
        // 30s-1m: Check every 15 seconds
        delay = 15;
    } else if (totalElapsed < 120) {
        // 1m-2m: Check every 20 seconds
        delay = 20;
    } else if (totalElapsed < 180) {
        // 2m-3m: Check every 30 seconds
        delay = 30;
    } else {
        // After 3 minutes: Stop auto-checking (should be expired)
        console.log('Payment window expired, stopping auto-check');
        updateCheckStatus('Payment window expired', 'warning');
        return;
    }
    
    currentCheckDelay = delay;
    startCountdownTimer(delay);
}

function startCountdownTimer(seconds) {
    let remainingSeconds = seconds;
    
    // Update countdown display
    updateCountdownDisplay(remainingSeconds);
    
    countdownInterval = setInterval(() => {
        remainingSeconds--;
        updateCountdownDisplay(remainingSeconds);
        
        if (remainingSeconds <= 0) {
            clearInterval(countdownInterval);
            totalElapsed += seconds;
            
            // Perform the check
            checkPaymentStatus();
            
            // Schedule next check
            setTimeout(() => {
                if (window.topupData?.currentStep === 'payment_pending') {
                    scheduleNextCheck();
                }
            }, 1000);
        }
    }, 1000);
}

function updateCountdownDisplay(seconds) {
    const countdownElement = document.getElementById('countdown');
    if (countdownElement) {
        countdownElement.textContent = seconds;
    }
}

function updateCheckStatus(message, type = 'info') {
    const statusElement = document.getElementById('checkStatus');
    if (statusElement) {
        statusElement.textContent = message;
        
        // Add visual feedback based on type
        statusElement.className = 'check-status';
        if (type === 'error') {
            statusElement.style.color = 'var(--error-color)';
        } else if (type === 'success') {
            statusElement.style.color = 'var(--success-color)';
        } else if (type === 'warning') {
            statusElement.style.color = 'var(--warning-color)';
        } else {
            statusElement.style.color = '';
        }
    }
}

function checkPaymentStatus() {
    const orderId = window.topupData?.orderId;
    if (!orderId) {
        console.error('Order ID not found');
        updateCheckStatus('Error: Order ID not found', 'error');
        return;
    }
    
    console.log(`Checking database for order: ${orderId}`);
    updateCheckStatus('Checking database...', 'info');
    
    fetch('topup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax_check_db_only=1&order_id=${encodeURIComponent(orderId)}&csrf_token=${encodeURIComponent(window.topupData.csrf_token)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text(); // ✅ UBAH KE TEXT DULU UNTUK DEBUG
    })
    .then(text => {
        console.log('🔍 Raw response:', text); // ✅ DEBUG RESPONSE
        
        // ✅ CEK APAKAH RESPONSE HTML
        if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html>')) {
            console.error('❌ Got HTML response instead of JSON');
            updateCheckStatus('Server error - got HTML response', 'error');
            return;
        }
        
        try {
            const data = JSON.parse(text);
            console.log('Database check response:', data);
            
            if (data.success) {
                if (data.status === 'paid') {
                    console.log('🎉 Payment confirmed in database!');
                    updateCheckStatus('Payment confirmed! Redirecting...', 'success');
                    showToast('Payment successful! 🎉', 'success');
                    
                    stopAllTimers();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    return;
                } else if (data.status === 'expired') {
                    console.log('Payment expired');
                    updateCheckStatus('Payment expired', 'warning');
                    stopAllTimers();
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    updateCheckStatus('Node.js worker processing payment...', 'info');
                }
            } else {
                updateCheckStatus('Database check failed: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch (jsonError) {
            console.error('❌ JSON parse error:', jsonError);
            console.error('📄 Response text:', text);
            updateCheckStatus('Invalid response format', 'error');
        }
    })
    .catch(error => {
        console.error('Database check error:', error);
        updateCheckStatus('Connection error, will retry...', 'error');
    });
}

/**
 * Manual Check Function
 */
function manualCheck() {
    if (isManualChecking) {
        showToast('Please wait, check in progress...', 'warning');
        return;
    }
    
    console.log('Manual check requested');
    isManualChecking = true;
    
    // Show loading state
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = '🔄 Checking...';
    button.disabled = true;
    
    updateCheckStatus('Manual check in progress...', 'info');
    
    // Perform immediate check
    const orderId = window.topupData?.orderId;
    if (!orderId) {
        updateCheckStatus('Error: Order ID not found', 'error');
        isManualChecking = false;
        button.textContent = originalText;
        button.disabled = false;
        return;
    }
    
    checkSingleOrderStatus(orderId);
    
    // Reset button after 3 seconds
    setTimeout(() => {
        isManualChecking = false;
        button.textContent = originalText;
        button.disabled = false;
    }, 3000);
    
    showToast('Manual check performed', 'info');
}

/**
 * Expired Timer (for QRIS expiration)
 */
function initializeExpiredTimer() {
    if (window.topupData?.currentStep !== 'payment_pending') {
        return;
    }
    
    const expiredAt = window.topupData?.expiredAt;
    if (!expiredAt) {
        console.log('No expiration time found');
        return;
    }
    
    console.log('Initializing expiry timer for:', expiredAt);
    updateExpiredTimer();
    
    expiredTimer = setInterval(updateExpiredTimer, 1000);
}

function updateExpiredTimer() {
    const expiredAt = window.topupData?.expiredAt;
    const timerElement = document.getElementById('expiredTimer');
    
    if (!expiredAt || !timerElement) {
        return;
    }
    
    try {
        const expiredTime = new Date(expiredAt).getTime();
        const now = new Date().getTime();
        const diff = Math.max(0, Math.floor((expiredTime - now) / 1000));
        
        if (diff <= 0) {
            timerElement.textContent = 'EXPIRED';
            timerElement.style.color = 'var(--error-color)';
            stopAllTimers();
            
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            const minutes = Math.floor(diff / 60);
            const seconds = diff % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            // Color coding based on time remaining
            if (diff < 30) {
                timerElement.style.color = 'var(--error-color)';
            } else if (diff < 60) {
                timerElement.style.color = 'var(--warning-color)';
            } else {
                timerElement.style.color = 'var(--success-color)';
            }
        }
    } catch (error) {
        console.error('Error updating expired timer:', error);
        timerElement.textContent = 'Error';
    }
}

/**
 * Utility Functions
 */
function stopAllTimers() {
    if (autoCheckInterval) {
        clearInterval(autoCheckInterval);
        autoCheckInterval = null;
    }
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    if (expiredTimer) {
        clearInterval(expiredTimer);
        expiredTimer = null;
    }
    console.log('All timers stopped');
}

function showLoadingOverlay(message = 'Loading...') {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        
        overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>${message}</p>
            </div>
        `;
        
        document.body.appendChild(overlay);
    }
    
    const messageEl = overlay.querySelector('.loading-spinner p');
    if (messageEl) {
        messageEl.textContent = message;
    }
    
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = getToastIcon(type);
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Show animation
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }, 4000);
}

function getToastIcon(type) {
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || icons.info;
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showToast(`📋 Copied: ${text.length > 20 ? text.substring(0, 20) + '...' : text}`, 'success');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast(`📋 Copied: ${text.length > 20 ? text.substring(0, 20) + '...' : text}`, 'success');
    } catch (err) {
        showToast('Failed to copy to clipboard', 'error');
    }
    
    document.body.removeChild(textArea);
}

// Cleanup function
function cleanup() {
    stopAllTimers();
    hideLoadingOverlay();
}

// Event listeners
window.addEventListener('beforeunload', cleanup);
window.addEventListener('unload', cleanup);

// Global functions
window.manualCheck = manualCheck;
window.copyToClipboard = copyToClipboard;
window.showToast = showToast;
window.showLoadingOverlay = showLoadingOverlay;
window.hideLoadingOverlay = hideLoadingOverlay;

console.log('📱 Topup JS loaded successfully - Balanced Smart Batching version');