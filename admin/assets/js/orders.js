// Orders Page JavaScript - Selaras dengan Dashboard & PDO Support

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

// Enhanced notification system
// PERBAIKI: Enhanced notification dengan null checking
function showNotification(message, type = 'info', duration = 5000) {
    // Validasi input
    if (!message || typeof message !== 'string') {
        console.error('Invalid notification message:', message);
        return;
    }
    
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    });
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = getNotificationIcon(type);
    const safeMessage = message.replace(/</g, '&lt;').replace(/>/g, '&gt;'); // Prevent XSS
    
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${icon}</span>
            <span class="notification-text">${safeMessage}</span>
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
    const borderColors = {
        'success': 'var(--success-color)',
        'error': 'var(--danger-color)',
        'warning': 'var(--warning-color)',
        'info': 'var(--primary-color)'
    };
    
    if (borderColors[type]) {
        notification.style.borderLeft = `4px solid ${borderColors[type]}`;
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after duration
    if (duration > 0) {
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                setTimeout(() => {
                    if (notification && notification.parentNode) {
                        notification.remove();
                    }
                }, 400);
            }
        }, duration);
    }
}

function getNotificationIcon(type) {
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    return icons[type] || icons['info'];
}


// Add CSS animations for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }
    
    .notification-icon {
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .notification-text {
        flex: 1;
        color: var(--text-primary);
        font-weight: 500;
    }
    
    .notification-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }
    
    .notification-close:hover {
        background: rgba(0, 0, 0, 0.1);
        color: var(--danger-color);
    }
`;
document.head.appendChild(notificationStyles);

// Copy token function
function copyToken(token) {
    navigator.clipboard.writeText(token).then(() => {
        showNotification('Token berhasil disalin ke clipboard!', 'success', 3000);
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = token;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Token berhasil disalin ke clipboard!', 'success', 3000);
    });
}

// Show loading overlay
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

// Hide loading overlay
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// API call helper function
// PERBAIKI: API call dengan better error handling
async function makeApiCall(action, data = {}) {
    showLoading();
    
    try {
        const formData = new FormData();
        formData.append('action', action);
        
        // Add all data to form dengan validasi
        Object.keys(data).forEach(key => {
            const value = data[key];
            if (value !== undefined && value !== null) {
                formData.append(key, value);
            }
        });
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result && result.status === 'success') {
            if (result.message) {
                showNotification(result.message, 'success');
            }
            return result;
        } else {
            const errorMsg = result?.message || 'Terjadi kesalahan tidak diketahui';
            showNotification(errorMsg, 'error');
            return null;
        }
    } catch (error) {
        console.error('API Error:', error);
        let errorMessage = 'Terjadi kesalahan jaringan';
        
        if (error.message.includes('HTTP error')) {
            errorMessage = 'Server error. Silakan coba lagi.';
        } else if (error.message.includes('JSON')) {
            errorMessage = 'Response tidak valid dari server';
        }
        
        showNotification(errorMessage, 'error');
        return null;
    } finally {
        hideLoading();
    }
}


// Show eSIM details modal
// PERBAIKI: Show eSIM details modal dengan handling yang lebih baik
async function showEsimDetails(order, event) {
    // Prevent event bubbling jika dipanggil dari button
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const modal = document.getElementById('esimModal');
    const content = document.getElementById('esimDetailsContent');
    
    if (!modal || !content) {
        console.error('Modal elements not found');
        return;
    }
    
    // Show loading in modal
    content.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <div class="loading-spinner" style="margin: 0 auto 1rem; width: 40px; height: 40px;"></div>
            <p style="color: var(--text-primary);">Memuat detail eSIM...</p>
        </div>
    `;
    
    modal.style.display = 'block';
    
    try {
        // Get fresh eSIM details from API
        const result = await makeApiCall('get_esim_details', {
            orderNo: order.orderNo,
            iccid: order.iccid || ''
        });
        
        if (result && result.esimData) {
            const esim = result.esimData;
            const status = order.esim_status || order.status || 'UNKNOWN';
            const statusClass = getStatusClass(status);
            const statusText = getStatusIndonesia(status);
            
            // Calculate usage if available
            let usageHtml = '';
            if (esim.totalVolume && esim.orderUsage) {
                const totalGB = (esim.totalVolume / (1024 * 1024 * 1024)).toFixed(2);
                const usedGB = (esim.orderUsage / (1024 * 1024 * 1024)).toFixed(2);
                const percentage = Math.min(100, (esim.orderUsage / esim.totalVolume * 100)).toFixed(1);
                
                usageHtml = `
                    <div class="usage-section">
                        <h4>📊 Penggunaan Data</h4>
                        <div class="usage-bar">
                            <div class="usage-fill" style="width: ${percentage}%; background: ${percentage > 80 ? '#ef4444' : percentage > 50 ? '#f59e0b' : '#10b981'};"></div>
                        </div>
                        <p>${usedGB} GB / ${totalGB} GB (${percentage}%)</p>
                    </div>
                `;
            }
            
            content.innerHTML = `
                <h2 style="color: var(--text-primary); margin-bottom: var(--space-xl);">📱 Detail eSIM</h2>
                
                <div class="esim-info">
                    <div class="info-group">
                        <h3>👤 Informasi Pelanggan</h3>
                        <div class="info-item">
                            <span class="label">Nama:</span>
                            <span class="value">${order.nama}</span>
                            <button class="btn-edit" onclick="editCustomer(${order.id}, '${order.nama.replace(/'/g, "\\'")}', '${(order.phone || '').replace(/'/g, "\\'")}')">Edit</button>
                        </div>
                        ${order.phone ? `
                        <div class="info-item">
                            <span class="label">Telepon:</span>
                            <span class="value">${order.phone}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="info-group">
                        <h3>📋 Informasi Order</h3>
                        <div class="info-item">
                            <span class="label">Order No:</span>
                            <span class="value">${order.orderNo}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Package:</span>
                            <span class="value">${order.packageName || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Status:</span>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Tanggal:</span>
                            <span class="value">${new Date(order.created_at).toLocaleString('id-ID')}</span>
                        </div>
                    </div>
                    
                    ${order.iccid ? `
                    <div class="info-group">
                        <h3>📱 Informasi eSIM</h3>
                        <div class="info-item">
                            <span class="label">ICCID:</span>
                            <span class="value">${order.iccid}</span>
                        </div>
                        ${esim.eid ? `
                        <div class="info-item">
                            <span class="label">EID:</span>
                            <span class="value">${esim.eid}</span>
                        </div>
                        ` : ''}
                        <div class="info-item">
                            <span class="label">eSIM Status:</span>
                            <span class="value">${esim.esimStatus || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">SMDP Status:</span>
                            <span class="value">${esim.smdpStatus || 'N/A'}</span>
                        </div>
                        ${esim.expiredTime ? `
                        <div class="info-item">
                            <span class="label">Expired:</span>
                            <span class="value">${new Date(esim.expiredTime).toLocaleString('id-ID')}</span>
                        </div>
                        ` : ''}
                    </div>
                    ` : ''}
                    
                    ${usageHtml}
                </div>
                
                ${order.iccid && ['PENDING', 'NEW', 'ONBOARD', 'GOT_RESOURCE'].includes(status) ? `
                <div class="action-buttons">
                    <button class="btn-action btn-refresh" onclick="refreshEsimStatus('${order.iccid}')">
                        🔄 Refresh Status
                    </button>
                    <button class="btn-action btn-cancel" onclick="cancelEsim('${order.iccid}')">
                        🚫 Batalkan eSIM
                    </button>
                </div>
                ` : ''}

                ${order.iccid && ['IN_USE', 'ACTIVE', 'USED_UP', 'DEPLETED'].includes(status) ? `
                <div class="action-buttons">
                    <button class="btn-action btn-refresh" onclick="refreshEsimStatus('${order.iccid}')">
                        🔄 Refresh Status
                    </button>
                    <button class="btn-action btn-usage" onclick="refreshUsage('${order.iccid}')">
                        📊 Refresh Usage
                    </button>
                    <button class="btn-action btn-sms" onclick="showSmsModal('${order.iccid}')">
                        💬 Kirim SMS
                    </button>
                    <button class="btn-action btn-topup" onclick="showTopupModal('${order.iccid}')">
                        💰 Top Up
                    </button>
                    <button class="btn-action btn-suspend" onclick="suspendEsim('${order.iccid}')">
                        ⏸️ Suspend
                    </button>
                    <button class="btn-action btn-revoke" onclick="revokeEsim('${order.iccid}')">
                        🚫 Paksa Hapus
                    </button>
                </div>
                ` : ''}

                ${order.iccid && status === 'SUSPENDED' ? `
                <div class="action-buttons">
                    <button class="btn-action btn-refresh" onclick="refreshEsimStatus('${order.iccid}')">
                        🔄 Refresh Status
                    </button>
                    <button class="btn-action btn-unsuspend" onclick="unsuspendEsim('${order.iccid}')">
                        ▶️ Unsuspend
                    </button>
                </div>
                ` : ''}
            `;
        } else {
            content.innerHTML = `
                <h2 style="color: var(--text-primary);">❌ Error</h2>
                <p style="color: var(--text-muted); margin: var(--space-lg) 0;">Gagal memuat detail eSIM. Silakan coba lagi.</p>
                <button class="btn-action btn-refresh" onclick="closeModal()">Tutup</button>
            `;
        }
    } catch (error) {
        console.error('Error loading eSIM details:', error);
        content.innerHTML = `
            <h2 style="color: var(--text-primary);">❌ Error</h2>
            <p style="color: var(--text-muted); margin: var(--space-lg) 0;">Terjadi kesalahan saat memuat detail. Silakan coba lagi.</p>
            <button class="btn-action btn-refresh" onclick="closeModal()">Tutup</button>
        `;
    }
}

// Konfigurasi base URL
const BASE_URL = window.location.origin; // http://localhost
const DETAIL_PATH = '/detail.php'; // path ke file detail

function copyToken(token) {
    if (event) {
        event.stopPropagation();
    }
    
    const detailLink = `${BASE_URL}${DETAIL_PATH}?token=${encodeURIComponent(token)}`;
    
    navigator.clipboard.writeText(detailLink).then(() => {
        showNotification('🔗 Link detail eSIM berhasil disalin!', 'success', 3000);
    }).catch(() => {
        // Fallback
        const textArea = document.createElement('textarea');
        textArea.value = detailLink;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('🔗 Link detail eSIM berhasil disalin!', 'success', 3000);
    });
}

// PERBAIKI: Edit customer dengan escaping yang benar
function editCustomer(id, nama, phone) {
    const modal = document.getElementById('editModal');
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_phone').value = phone || '';
    
    modal.style.display = 'block';
}

// PERBAIKI: Helper functions dengan status dibatalkan
function getStatusClass(status) {
    const statusLower = status.toLowerCase().replace(/_/g, '-');
    
    const statusMap = {
        'in-use': 'active',
        'active': 'active',
        'pending': 'pending',
        'new': 'pending',
        'onboard': 'pending',
        'expired': 'expired',
        'used-up': 'habis',
        'depleted': 'habis',
        'suspended': 'suspended',
        'cancelled': 'cancelled',
        'canceled': 'cancelled',
        'dibatalkan': 'cancelled',
        'cancel': 'cancelled'
    };
    
    return statusMap[statusLower] || 'unknown';
}

function getStatusIndonesia(status) {
    const statusLower = status.toLowerCase();
    
    const statusMap = {
        'in_use': '✅ Aktif',
        'active': '✅ Aktif',
        'pending': '⏳ Pending',
        'new': '⏳ Pending',
        'onboard': '⏳ Pending',
        'expired': '❌ Expired',
        'used_up': '❌ Habis',
        'depleted': '❌ Habis',
        'suspended': '⏸️ Suspended',
        'cancelled': '🚫 Dibatalkan',
        'canceled': '🚫 Dibatalkan',
        'dibatalkan': '🚫 Dibatalkan',
        'cancel': '🚫 Dibatalkan'
    };
    
    return statusMap[statusLower] || '❓ Unknown';
}



// Modal functions
function closeModal() {
    const modal = document.getElementById('esimModal');
    if (modal) modal.style.display = 'none';
}

function closeSmsModal() {
    const modal = document.getElementById('smsModal');
    if (modal) modal.style.display = 'none';
}

function closeTopupModal() {
    const modal = document.getElementById('topupModal');
    if (modal) modal.style.display = 'none';
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) modal.style.display = 'none';
}

// Quick refresh usage function
async function refreshUsageQuick(iccid, button) {
    const originalText = button.innerHTML;
    button.innerHTML = '🔄 Loading...';
    button.disabled = true;
    
    const result = await makeApiCall('get_usage', { iccid });
    
    if (result) {
        // Reload page to show updated data
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    button.innerHTML = originalText;
    button.disabled = false;
}

// eSIM action functions
async function refreshEsimStatus(iccid) {
    await makeApiCall('get_status', { iccid });
    setTimeout(() => window.location.reload(), 1000);
}

async function refreshUsage(iccid) {
    await makeApiCall('get_usage', { iccid });
    setTimeout(() => window.location.reload(), 1000);
}

async function suspendEsim(iccid) {
    if (confirm('Yakin ingin suspend eSIM ini?')) {
        await makeApiCall('suspend_esim', { iccid });
        setTimeout(() => window.location.reload(), 1000);
    }
}

async function unsuspendEsim(iccid) {
    if (confirm('Yakin ingin unsuspend eSIM ini?')) {
        await makeApiCall('unsuspend_esim', { iccid });
        setTimeout(() => window.location.reload(), 1000);
    }
}

// SMS Modal
function showSmsModal(iccid) {
    const modal = document.getElementById('smsModal');
    const content = document.getElementById('smsContent');
    
    content.innerHTML = `
        <h2>💬 Kirim SMS</h2>
        <form id="smsForm">
            <div class="form-group">
                <label for="sms_message">Pesan SMS:</label>
                <textarea id="sms_message" name="sms_message" rows="4" placeholder="Masukkan pesan SMS..." required></textarea>
            </div>
            <div class="form-buttons">
                <button type="button" class="btn-cancel-edit" onclick="closeSmsModal()">Batal</button>
                <button type="submit" class="btn-save">Kirim SMS</button>
            </div>
        </form>
    `;
    
    modal.style.display = 'block';
    
    document.getElementById('smsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const message = document.getElementById('sms_message').value;
        
        const result = await makeApiCall('send_sms', { 
            iccid: iccid,
            sms_message: message 
        });
        
        if (result) {
            closeSmsModal();
        }
    });
}

// Topup Modal
// PERBAIKI: Topup Modal dengan price IDR conversion
async function showTopupModal(iccid) {
    const modal = document.getElementById('topupModal');
    const content = document.getElementById('topupContent');
    
    if (!modal || !content) {
        console.error('Topup modal elements not found');
        return;
    }
    
    content.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <div class="loading-spinner" style="margin: 0 auto 1rem;"></div>
            <p>Memuat paket top-up...</p>
        </div>
    `;
    
    modal.style.display = 'block';
    
    try {
        const result = await makeApiCall('get_topup_packages', { iccid });
        
        if (result && result.packages && Array.isArray(result.packages) && result.packages.length > 0) {
            let packagesHtml = '';
            
            result.packages.forEach(pkg => {
                // PERBAIKI: Validasi data package sebelum digunakan
                const packageName = pkg.packageName || pkg.name || 'Unknown Package';
                const packageCode = pkg.packageCode || pkg.code || '';
                const packagePrice = pkg.price || 0;
                
                // UBAH KE IDR: Convert USD to IDR
                const priceUsd = (packagePrice / 10000); // Original USD price
                const exchangeRate = window.exchangeRate || 16500; // Get from global or default
                const priceIdr = Math.round(priceUsd * exchangeRate);
                const formattedPriceIdr = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(priceIdr);
                
                if (packageCode && packagePrice > 0) {
                    packagesHtml += `
                        <div class="package-option" onclick="selectTopupPackage('${packageCode}', ${packagePrice}, '${packageName.replace(/'/g, "\\'")}', ${priceIdr})">
                            <div class="package-name">${packageName}</div>
                            <div class="package-price">${formattedPriceIdr}</div>
                            <div class="package-price-usd">($${priceUsd.toFixed(2)} USD)</div>
                            <div class="package-code">${packageCode}</div>
                        </div>
                    `;
                }
            });
            
            if (packagesHtml) {
                content.innerHTML = `
                    <h2>💰 Top Up eSIM</h2>
                    <p style="color: var(--text-muted); margin-bottom: var(--space-lg);">Pilih paket top-up untuk ICCID: ${iccid}</p>
                    <div class="topup-packages">
                        ${packagesHtml}
                    </div>
                    <form id="topupForm" style="display: none;">
                        <input type="hidden" id="selected_package" name="topUpCode">
                        <input type="hidden" id="selected_amount" name="amount">
                        <input type="hidden" id="selected_price_idr" name="priceIdr">
                        <div class="form-group">
                            <label>Paket Terpilih:</label>
                            <div id="selected_package_info"></div>
                        </div>
                        <div class="form-buttons">
                            <button type="button" class="btn-cancel-edit" onclick="closeTopupModal()">Batal</button>
                            <button type="submit" class="btn-save">Proses Top Up</button>
                        </div>
                    </form>
                `;
                
                // Setup form handler
                setupTopupFormHandler(iccid);
            } else {
                content.innerHTML = `
                    <h2>💰 Top Up eSIM</h2>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <p>Tidak ada paket top-up yang tersedia untuk eSIM ini</p>
                        <button class="btn-action" onclick="closeTopupModal()">Tutup</button>
                    </div>
                `;
            }
        } else {
            content.innerHTML = `
                <h2>💰 Top Up eSIM</h2>
                <div class="empty-state">
                    <div class="empty-icon">❌</div>
                    <p>Gagal memuat paket top-up. ${result?.message || 'Silakan coba lagi.'}</p>
                    <button class="btn-action" onclick="closeTopupModal()">Tutup</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading topup packages:', error);
        content.innerHTML = `
            <h2>💰 Top Up eSIM</h2>
            <div class="empty-state">
                <div class="empty-icon">❌</div>
                <p>Terjadi kesalahan saat memuat paket top-up</p>
                <button class="btn-action" onclick="closeTopupModal()">Tutup</button>
            </div>
        `;
    }
}

// PERBAIKI: Update selectTopupPackage function untuk handle IDR
function selectTopupPackage(packageCode, price, packageName, priceIdr) {
    // Validasi input parameters
    if (!packageCode || !price || !packageName || !priceIdr) {
        console.error('Invalid package data:', { packageCode, price, packageName, priceIdr });
        showNotification('Data paket tidak valid', 'error');
        return;
    }
    
    const selectedPackageInput = document.getElementById('selected_package');
    const selectedAmountInput = document.getElementById('selected_amount');
    const selectedPriceIdrInput = document.getElementById('selected_price_idr');
    const selectedPackageInfo = document.getElementById('selected_package_info');
    const topupForm = document.getElementById('topupForm');
    const packagesContainer = document.querySelector('.topup-packages');
    
    if (!selectedPackageInput || !selectedAmountInput || !selectedPackageInfo) {
        console.error('Form elements not found');
        return;
    }
    
    selectedPackageInput.value = packageCode;
    selectedAmountInput.value = price; // Original USD amount in cents for API
    if (selectedPriceIdrInput) {
        selectedPriceIdrInput.value = priceIdr; // IDR price for display
    }
    
    const priceUsd = (price / 10000).toFixed(2);
    const formattedPriceIdr = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(priceIdr);
    
    selectedPackageInfo.innerHTML = `
        <div class="selected-package">
            <strong>${packageName}</strong><br>
            <span class="package-code">Code: ${packageCode}</span><br>
            <span class="package-price">Harga: ${formattedPriceIdr}</span><br>
            <span class="package-price-usd">($${priceUsd} USD)</span>
        </div>
    `;
    
    // Show form and hide packages
    if (topupForm) topupForm.style.display = 'block';
    if (packagesContainer) packagesContainer.style.display = 'none';
    
    // Add back button
    const backButton = document.createElement('button');
    backButton.type = 'button';
    backButton.className = 'btn-secondary';
    backButton.innerHTML = '← Pilih Paket Lain';
    backButton.onclick = function() {
        if (topupForm) topupForm.style.display = 'none';
        if (packagesContainer) packagesContainer.style.display = 'grid';
        this.remove();
    };
    
    if (topupForm && !topupForm.querySelector('.btn-secondary')) {
        topupForm.insertBefore(backButton, topupForm.querySelector('.form-buttons'));
    }
}

// PERBAIKI: Setup form handler terpisah
function setupTopupFormHandler(iccid) {
    const form = document.getElementById('topupForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const topUpCode = document.getElementById('selected_package')?.value;
            const amount = document.getElementById('selected_amount')?.value;
            
            if (!topUpCode || !amount) {
                showNotification('Silakan pilih paket terlebih dahulu', 'warning');
                return;
            }
            
            const result = await makeApiCall('topup_esim', { 
                iccid: iccid,
                topUpCode: topUpCode,
                amount: amount
            });
            
            if (result) {
                closeTopupModal();
                setTimeout(() => window.location.reload(), 1000);
            }
        });
    }
}

// // PERBAIKI: Select package function dengan validasi
// function selectTopupPackage(packageCode, price, packageName) {
//     // Validasi input parameters
//     if (!packageCode || !price || !packageName) {
//         console.error('Invalid package data:', { packageCode, price, packageName });
//         showNotification('Data paket tidak valid', 'error');
//         return;
//     }
    
//     const selectedPackageInput = document.getElementById('selected_package');
//     const selectedAmountInput = document.getElementById('selected_amount');
//     const selectedPackageInfo = document.getElementById('selected_package_info');
//     const topupForm = document.getElementById('topupForm');
//     const packagesContainer = document.querySelector('.topup-packages');
    
//     if (!selectedPackageInput || !selectedAmountInput || !selectedPackageInfo) {
//         console.error('Form elements not found');
//         return;
//     }
    
//     selectedPackageInput.value = packageCode;
//     selectedAmountInput.value = price;
    
//     const priceUsd = (price / 10000).toFixed(2);
//     selectedPackageInfo.innerHTML = `
//         <div class="selected-package">
//             <strong>${packageName}</strong><br>
//             <span class="package-code">Code: ${packageCode}</span><br>
//             <span class="package-price">Price: $${priceUsd}</span>
//         </div>
//     `;
    
//     // Show form and hide packages
//     if (topupForm) topupForm.style.display = 'block';
//     if (packagesContainer) packagesContainer.style.display = 'none';
    
//     // Add back button
//     const backButton = document.createElement('button');
//     backButton.type = 'button';
//     backButton.className = 'btn-secondary';
//     backButton.innerHTML = '← Pilih Paket Lain';
//     backButton.onclick = function() {
//         if (topupForm) topupForm.style.display = 'none';
//         if (packagesContainer) packagesContainer.style.display = 'grid';
//         this.remove();
//     };
    
//     if (topupForm && !topupForm.querySelector('.btn-secondary')) {
//         topupForm.insertBefore(backButton, topupForm.querySelector('.form-buttons'));
//     }
// }

// Helper function untuk cek apakah eSIM bisa dibatalkan
function canCancelEsim(status) {
    const cancelableStatuses = ['PENDING', 'NEW', 'ONBOARD', 'SUSPENDED'];
    return cancelableStatuses.includes(status.toUpperCase());
}

// Helper function untuk cek apakah eSIM sudah aktif
function isEsimActive(status) {
    const activeStatuses = ['IN_USE', 'ACTIVE'];
    return activeStatuses.includes(status.toUpperCase());
}


// PERBAIKI: Function cancel dengan validasi status
async function cancelEsim(iccid) {
    // Dapatkan status terbaru dari API dulu
    const statusResult = await makeApiCall('get_status', { iccid });
    
    if (statusResult && statusResult.esimStatus) {
        const currentStatus = statusResult.esimStatus.toUpperCase();
        
        // Cek apakah eSIM sudah aktif/digunakan
        if (['IN_USE', 'ACTIVE'].includes(currentStatus)) {
            showNotification('❌ eSIM yang sudah aktif tidak dapat dibatalkan', 'error');
            return;
        }
        
        // Cek apakah sudah expired/depleted
        if (['EXPIRED', 'USED_UP', 'DEPLETED'].includes(currentStatus)) {
            showNotification('❌ eSIM yang sudah expired tidak dapat dibatalkan', 'error');
            return;
        }
        
        // Cek apakah sudah dibatalkan sebelumnya
        if (['CANCELLED', 'CANCELED'].includes(currentStatus)) {
            showNotification('ℹ️ eSIM ini sudah dibatalkan sebelumnya', 'info');
            return;
        }
    }
    
    const confirmed = confirm(`⚠️ Yakin ingin membatalkan eSIM ini?

📱 ICCID: ${iccid}

⚠️ PERINGATAN:
• eSIM yang dibatalkan tidak dapat digunakan lagi
• Pembatalan hanya bisa dilakukan untuk eSIM yang belum aktif
• Proses ini tidak dapat dikembalikan

Lanjutkan pembatalan?`);
    
    if (confirmed) {
        const result = await makeApiCall('cancel_esim', { iccid });
        if (result) {
            showNotification('🚫 eSIM berhasil dibatalkan', 'success');
            closeModal(); // Tutup modal
            setTimeout(() => window.location.reload(), 1000);
        }
    }
}


// Edit Customer Modal
function editCustomer(id, nama, phone) {
    const modal = document.getElementById('editModal');
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_phone').value = phone || '';
    
    modal.style.display = 'block';
}

// Edit Customer Form Handler
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editCustomerForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_customer');
            
            const result = await makeApiCall('update_customer', {
                id: formData.get('id'),
                nama: formData.get('nama'),
                phone: formData.get('phone')
            });
            
            if (result) {
                closeEditModal();
                setTimeout(() => window.location.reload(), 1000);
            }
        });
    }
});

// Force refresh current page
async function forceRefreshCurrentPage() {
    const orderCards = document.querySelectorAll('.order-card');
    const orderNumbers = [];
    
    orderCards.forEach(card => {
        const orderNoElement = card.querySelector('.info-value');
        if (orderNoElement) {
            orderNumbers.push(orderNoElement.textContent.trim());
        }
    });
    
    if (orderNumbers.length > 0) {
        const result = await makeApiCall('force_refresh_all', {
            order_numbers: JSON.stringify(orderNumbers)
        });
        
        if (result) {
            setTimeout(() => window.location.reload(), 2000);
        }
    } else {
        showNotification('Tidak ada data untuk di-refresh', 'warning');
    }
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const modals = ['esimModal', 'smsModal', 'topupModal', 'editModal'];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal && event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape to close modals
    if (e.key === 'Escape') {
        closeModal();
        closeSmsModal();
        closeTopupModal();
        closeEditModal();
    }
    
    // Ctrl + R for refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        forceRefreshCurrentPage();
    }
});

// Enhanced table interactions
function initTableEnhancements() {
    const orderCards = document.querySelectorAll('.order-card');
    
    orderCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

// Initialize enhancements
document.addEventListener('DOMContentLoaded', initTableEnhancements);

// Auto-refresh functionality (optional)
let autoRefreshInterval;

function startAutoRefresh(intervalMinutes = 5) {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(() => {
        console.log('Auto-refreshing orders data...');
        forceRefreshCurrentPage();
    }, intervalMinutes * 60 * 1000);
    
    showNotification(`Auto-refresh diaktifkan (setiap ${intervalMinutes} menit)`, 'info');
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        showNotification('Auto-refresh dinonaktifkan', 'info');
    }
}

// Console welcome message
console.log(`
📱 eSIM Orders Management
🚀 Version: 2.0 - PDO Edition
💻 Powered by Modern JavaScript
🎨 Design: Gen Z Style with Gradients
📱 Mobile First & Responsive
🌙 Dark Mode Support
⚡ Enhanced Performance
🔒 PDO Security

Features:
• Bottom Navigation (Mobile-First)
• Dark Mode Toggle
• Real-time eSIM Management
• SMS & Top-up Support
• Enhanced Modals
• Auto-refresh Capability
• Keyboard Shortcuts

Keyboard Shortcuts:
• Escape: Close Modals
• Ctrl + R: Force Refresh
`);

// Performance monitoring
window.addEventListener('load', function() {
    const loadTime = performance.now();
    console.log(`⚡ Orders page loaded in ${loadTime.toFixed(2)}ms`);
});
