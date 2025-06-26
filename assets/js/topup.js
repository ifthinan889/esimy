/**
 * eSIM Topup JavaScript - Complete Fixed Version
 */

// Global variables
let selectedPackage = null;
let selectedPayment = null;
let packageData = window.topupData?.packageData || [];
let paymentMethods = window.topupData?.paymentMethods || {};
let exchangeRate = window.topupData?.exchangeRate || 18000
let markupConfig = window.topupData?.markupConfig || [];
let autoRefreshInterval = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing topup page...');
    console.log('Package data:', packageData);
    console.log('Payment methods:', paymentMethods);
    console.log('Current step:', window.topupData?.currentStep);
    
    initializeTheme();
    initializeForm();
    initializePackageSelection();
    initializeOrderSummary();
    initializePaymentStatusChecker();
    
    console.log('✨ Topup page initialized');
});

/**
 * FUNGSI YANG HILANG - Payment Status Checker
 */
function initializePaymentStatusChecker() {
    console.log('Initializing payment status checker...');
    
    // Hanya jalankan jika sedang di halaman payment pending
    if (window.topupData?.currentStep === 'payment_pending') {
        console.log('Starting auto refresh for payment pending...');
        startAutoRefresh();
    } else {
        console.log('Not in payment pending step, skipping auto refresh');
    }
}

function startAutoRefresh() {
    let countdown = 30;
    const timerElement = document.getElementById('refreshTimer');
    const statusElement = document.getElementById('statusText');
    
    console.log('Starting auto refresh timer...');
    
    // Update timer setiap detik
    autoRefreshInterval = setInterval(() => {
        countdown--;
        
        if (timerElement) {
            timerElement.textContent = countdown;
        }
        
        if (countdown <= 0) {
            console.log('Auto checking payment status...');
            checkPaymentStatus();
            countdown = 30; // Reset countdown
        }
    }, 1000);
}

function checkPaymentStatus() {
    const orderId = window.topupData?.orderId;
    
    if (!orderId) {
        console.error('Order ID not found');
        return;
    }
    
    const statusElement = document.getElementById('statusText');
    if (statusElement) {
        statusElement.textContent = 'Checking...';
    }
    
    console.log('Checking payment status for order:', orderId);
    
    // AJAX call ke topup.php yang sama
    fetch('topup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax_check_status=1&order_id=${encodeURIComponent(orderId)}&csrf_token=${encodeURIComponent(window.topupData.csrf_token)}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Payment status response:', data);
        
        if (data.status === 'redirect') {
            console.log('Redirecting to:', data.url);
            // Stop auto refresh before redirect
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
            // Redirect ke status akhir
            window.location.href = data.url;
        } else if (data.status === 'error') {
            if (statusElement) {
                statusElement.textContent = 'Error: ' + data.message;
            }
            showToast('Error checking payment status: ' + data.message, 'error');
        } else {
            if (statusElement) {
                statusElement.textContent = data.message || 'Payment still pending...';
            }
        }
    })
    .catch(error => {
        console.error('Error checking payment status:', error);
        if (statusElement) {
            statusElement.textContent = 'Check failed - will retry in 30s';
        }
        showToast('Failed to check payment status', 'error');
    });
}

/**
 * Theme Management
 */
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Set initial theme
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(currentTheme);
    
    // Theme toggle event
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
    // Update icon di button theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.textContent = theme === 'light' ? '🌙' : '☀️';
    }
    
    // Juga update jika ada elemen dengan class theme-icon
    const themeIcon = document.querySelector('.theme-icon');
    if (themeIcon) {
        themeIcon.textContent = theme === 'light' ? '🌙' : '☀️';
    }
}

/**
 * Form Management
 */
/**
 * Form Management - SUPER SIMPLE VERSION tanpa validasi
 */
function initializeForm() {
    console.log('Initializing form...');
    
    // HAPUS SEMUA VALIDASI - biarkan PHP handle semuanya
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Cuma show loading, ga ada validasi apapun
            showLoadingOverlay('Processing...');
            return true; // Always allow submission
        });
    });

    
    // Legacy support untuk button dengan ID createPaymentBtn
    const createPaymentBtn = document.getElementById('createPaymentBtn');
    if (createPaymentBtn) {
        createPaymentBtn.addEventListener('click', function() {
            if (!validateSelections()) {
                return false;
            }
            createPayment();
        });
    }
}

function createPayment() {
    if (!selectedPackage || !selectedPayment) {
        showToast('Please select package and payment method', 'error');
        return;
    }
    
    const createPaymentBtn = document.getElementById('createPaymentBtn');
    const paymentForm = document.getElementById('paymentForm');
    
    if (!paymentForm) {
        showToast('Form not found', 'error');
        return;
    }
    
    // Set form values
    document.getElementById('hiddenPackageCode').value = selectedPackage;
    document.getElementById('hiddenPaymentMethod').value = selectedPayment;
    
    // Show loading state
    if (createPaymentBtn) {
        createPaymentBtn.classList.add('loading');
        createPaymentBtn.disabled = true;
    }
    
    showLoadingOverlay('Creating payment...');
    
    // Submit form
    paymentForm.submit();
}

function validateSelections() {
    if (!selectedPackage) {
        showToast('Please select a package', 'error');
        return false;
    }
    
    if (!selectedPayment) {
        showToast('Please select a payment method', 'error');
        return false;
    }
    
    return true;
}

/**
 * Package Selection - SIMPLE VERSION tanpa loop
 */
function initializePackageSelection() {
    const packageInputs = document.querySelectorAll('input[name="package_code"]');
    console.log('Package inputs found:', packageInputs.length);
    
    // Gunakan event delegation untuk menghindari multiple listeners
    document.addEventListener('change', function(e) {
        if (e.target.name === 'package_code' && e.target.checked) {
            selectedPackage = e.target.value;
            console.log('Package selected:', selectedPackage);
            
            // Remove selected class from all cards
            document.querySelectorAll('.package-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to current card
            e.target.closest('.package-card').classList.add('selected');
            
            updateOrderSummary();
            validateCreateButton();
            
            // Show toast only once
            if (!e.target.dataset.toastShown) {
                showToast(`Package selected`, 'success');
                e.target.dataset.toastShown = 'true';
                
                // Reset flag after 2 seconds
                setTimeout(() => {
                    delete e.target.dataset.toastShown;
                }, 2000);
            }
        }
    });
}

/**
 * Payment Method Selection - SIMPLE VERSION tanpa loop
 */
function initializePaymentSelection() {
    const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
    console.log('Payment inputs found:', paymentInputs.length);
    
    // Gunakan event delegation untuk menghindari multiple listeners
    document.addEventListener('change', function(e) {
        if (e.target.name === 'payment_method' && e.target.checked) {
            selectedPayment = e.target.value;
            console.log('Payment method selected:', selectedPayment);
            
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(method => {
                method.classList.remove('selected');
            });
            
            // Add selected class to current method
            e.target.closest('.payment-method').classList.add('selected');
            
            updateOrderSummary();
            validateCreateButton();
            
            // Show toast only once
            if (!e.target.dataset.toastShown) {
                showToast(`Payment method selected`, 'success');
                e.target.dataset.toastShown = 'true';
                
                // Reset flag after 2 seconds
                setTimeout(() => {
                    delete e.target.dataset.toastShown;
                }, 2000);
            }
        }
    });
}

/**
 * Order Summary - SIMPLIFIED untuk layout baru
 */
function initializeOrderSummary() {
    console.log('Initializing order summary...');
    // Untuk layout baru, order summary tidak diperlukan karena harga sudah ditampilkan
    // di setiap package card
}

function updateOrderSummary() {
    const summaryContainer = document.getElementById('orderSummary');
    if (!summaryContainer) {
        // Tidak ada container summary di layout baru, skip
        return;
    }
    
    if (!selectedPackage || !selectedPayment) {
        summaryContainer.innerHTML = `
            <div class="summary-placeholder">
                <div class="placeholder-icon">📦</div>
                <p>Select package and payment method to see summary</p>
            </div>
        `;
        return;
    }
    
    // Find package info dari DOM karena packageData mungkin kosong
    const selectedPackageElement = document.querySelector(`input[name="package_code"][value="${selectedPackage}"]`);
    if (!selectedPackageElement) {
        console.log('Selected package element not found');
        return;
    }
    
    const packageCard = selectedPackageElement.closest('.package-card');
    const packageName = packageCard.querySelector('.package-name')?.textContent || 'Unknown Package';
    const packageSize = packageCard.querySelector('.package-size')?.textContent || '0 GB';
    const packagePrice = packageCard.querySelector('.package-price')?.textContent || 'Rp 0';
    
    // Find payment info
    const selectedPaymentElement = document.querySelector(`input[name="payment_method"][value="${selectedPayment}"]`);
    if (!selectedPaymentElement) {
        console.log('Selected payment element not found');
        return;
    }
    
    const paymentCard = selectedPaymentElement.closest('.payment-method');
    const paymentName = paymentCard.querySelector('.payment-name')?.textContent || 'Unknown Payment';
    const paymentFee = paymentCard.querySelector('.payment-fee')?.textContent || 'Fee: Rp 0';
    
    summaryContainer.innerHTML = `
        <div class="summary-content show">
            <div class="summary-row">
                <span class="label">Package</span>
                <span class="value">${packageName}</span>
            </div>
            <div class="summary-row">
                <span class="label">Data</span>
                <span class="value">${packageSize}</span>
            </div>
            <div class="summary-row">
                <span class="label">Payment Method</span>
                <span class="value">${paymentName}</span>
            </div>
            <div class="summary-row">
                <span class="label">Package Price</span>
                <span class="value">${packagePrice}</span>
            </div>
            <div class="summary-row">
                <span class="label">${paymentFee}</span>
                <span class="value"></span>
            </div>
        </div>
    `;
}

/**
 * Button Validation - SIMPLIFIED
 */
function validateCreateButton() {
    const createBtn = document.getElementById('createPaymentBtn');
    if (!createBtn) return;
    
    const packageSelected = document.querySelector('input[name="package_code"]:checked');
    const paymentSelected = document.querySelector('input[name="payment_method"]:checked');
    const isValid = packageSelected && paymentSelected;
    
    createBtn.disabled = !isValid;
    
    if (isValid) {
        createBtn.classList.remove('disabled');
        createBtn.style.opacity = '1';
        createBtn.style.cursor = 'pointer';
    } else {
        createBtn.classList.add('disabled');
        createBtn.style.opacity = '0.5';
        createBtn.style.cursor = 'not-allowed';
    }
    
    console.log('Button validation:', {
        packageSelected: !!packageSelected,
        paymentSelected: !!paymentSelected,
        isValid,
        disabled: createBtn.disabled
    });
}

/**
 * Helper Functions
 */
function calculateFinalPrice(originalPriceUsd, exchangeRate, volumeGB, markupConfig) {
    const priceIdr = originalPriceUsd * exchangeRate;
    const markup = getTieredMarkup(volumeGB, markupConfig);
    return Math.round(priceIdr + markup);
}

function getTieredMarkup(volumeGB, markupConfig) {
    if (!Array.isArray(markupConfig) || markupConfig.length === 0) {
        return 10000;
    }
    
    for (const tier of markupConfig) {
        if (volumeGB <= parseFloat(tier.limit)) {
            return parseFloat(tier.markup);
        }
    }
    
    const lastTier = markupConfig[markupConfig.length - 1];
    return parseFloat(lastTier.markup) || 10000;
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function showLoadingOverlay(message = 'Loading...') {
    // Buat loading overlay jika belum ada
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        overlay.innerHTML = `
            <div class="loading-spinner" style="
                background: white;
                padding: 2rem;
                border-radius: 8px;
                text-align: center;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            ">
                <div style="
                    width: 40px;
                    height: 40px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #3498db;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                "></div>
                <p style="margin: 0; color: #333;">${message}</p>
            </div>
        `;
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(overlay);
    }
    
    const messageEl = overlay.querySelector('.loading-spinner p');
    if (messageEl) {
        messageEl.textContent = message;
    }
    
    overlay.style.display = 'flex';
    setTimeout(() => {
        overlay.style.opacity = '1';
    }, 10);
    document.body.style.overflow = 'hidden';
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
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
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
    `;
    document.body.appendChild(container);
    return container;
}

/**
 * Copy to Clipboard
 */
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
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Cleanup saat page unload
window.addEventListener('beforeunload', cleanup);

// Global functions
window.copyToClipboard = copyToClipboard;
window.checkPaymentStatus = checkPaymentStatus;
window.showToast = showToast;
window.showLoadingOverlay = showLoadingOverlay;
window.hideLoadingOverlay = hideLoadingOverlay;

console.log('📱 Topup JS loaded successfully - Complete version');