// Topup Monitor JavaScript - Enhanced Functionality

// Global variables
let currentOrderId = '';
let refreshInterval = null;
let autoRefreshEnabled = false;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Topup Monitor...');
    
    initializeTheme();
    initializeFilters();
    initializeNavigation();
    initializeAutoRefresh();
    initializeRealTimeUpdates();
    
    console.log('✨ Topup Monitor initialized successfully');
});

/**
 * Theme Management - Same as dashboard
 */
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    // Set default to dark if no setting exists
    let savedTheme = localStorage.getItem('theme') || 'dark';
    
    // Apply theme
    document.documentElement.setAttribute('data-theme', savedTheme);
    localStorage.setItem('theme', savedTheme);
    
    const themeIcon = document.getElementById('themeIcon');
    updateThemeIcon(savedTheme, themeIcon);
    
    // Toggle functionality
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add transition effect
        document.body.classList.add('theme-transitioning');
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        updateThemeIcon(newTheme, themeIcon);
        
        // Animation effect
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

/**
 * Initialize filters functionality
 */
function initializeFilters() {
    const form = document.getElementById('filtersForm');
    if (!form) return;
    
    // Auto-submit on filter change
    const filterInputs = form.querySelectorAll('input, select');
    filterInputs.forEach(input => {
        if (input.type !== 'submit') {
            input.addEventListener('change', function() {
                // Debounce for text inputs
                if (this.type === 'text') {
                    clearTimeout(this.debounceTimeout);
                    this.debounceTimeout = setTimeout(() => {
                        form.submit();
                    }, 1000);
                } else {
                    form.submit();
                }
            });
        }
    });
    
    // Real-time search
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => {
                form.submit();
            }, 1000);
        });
    }
}

/**
 * Navigation Management
 */
function initializeNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Add click effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'translateY(-2px)';
            }, 100);
        });
    });
}

/**
 * Initialize auto-refresh functionality
 */
function initializeAutoRefresh() {
    // Check for pending orders and enable auto-refresh if needed
    const pendingOrders = document.querySelectorAll('.status-badge.warning').length;
    
    if (pendingOrders > 0) {
        enableAutoRefresh();
    }
    
    // Add visibility change listener to pause/resume refresh
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            disableAutoRefresh();
        } else if (pendingOrders > 0) {
            enableAutoRefresh();
        }
    });
}

/**
 * Enable auto-refresh for pending orders
 */
function enableAutoRefresh() {
    if (autoRefreshEnabled) return;
    
    autoRefreshEnabled = true;
    
    // Refresh every 30 seconds
    refreshInterval = setInterval(() => {
        refreshData(false); // Silent refresh
    }, 30000);
    
    showNotification('🔄 Auto-refresh enabled for pending orders', 'info', 3000);
}

/**
 * Disable auto-refresh
 */
function disableAutoRefresh() {
    if (!autoRefreshEnabled) return;
    
    autoRefreshEnabled = false;
    
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

/**
 * Initialize real-time updates
 */
function initializeRealTimeUpdates() {
    // Update relative times every minute
    setInterval(updateRelativeTimes, 60000);
    
    // Check for expired orders
    setInterval(checkExpiredOrders, 10000);
}

/**
 * Update relative times in the interface
 */
function updateRelativeTimes() {
    const timeElements = document.querySelectorAll('.created-time');
    
    timeElements.forEach(element => {
        const row = element.closest('tr');
        if (!row) return;
        
        const createdAt = row.dataset.createdAt;
        if (createdAt) {
            element.textContent = formatRelativeTime(createdAt);
        }
    });
}

/**
 * Check for expired orders and update status
 */
function checkExpiredOrders() {
    const pendingRows = document.querySelectorAll('.status-badge.warning');
    
    pendingRows.forEach(badge => {
        const row = badge.closest('tr');
        if (!row) return;
        
        const expiredAt = row.dataset.expiredAt;
        if (expiredAt && new Date(expiredAt) < new Date()) {
            // Mark as expired
            badge.className = 'status-badge danger';
            badge.innerHTML = '⏰ Expired';
            
            // Remove action buttons for expired orders
            const actionButtons = row.querySelector('.action-buttons');
            if (actionButtons) {
                actionButtons.innerHTML = '<span class="text-muted">Expired</span>';
            }
        }
    });
}

/**
 * Refresh data
 */
function refreshData(showNotification = true) {
    if (showNotification) {
        showLoadingOverlay('Refreshing data...');
    }
    
    // Add a subtle loading indicator
    const refreshBtn = document.querySelector('[onclick="refreshData()"]');
    if (refreshBtn) {
        refreshBtn.classList.add('loading');
        refreshBtn.disabled = true;
    }
    
    // Reload page with current filters
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

/**
 * Clear all filters
 */
function clearFilters() {
    const form = document.getElementById('filtersForm');
    if (!form) return;
    
    // Clear all form inputs
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.type === 'text') {
            input.value = '';
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });
    
    // Redirect to page without query parameters
    window.location.href = window.location.pathname;
}

/**
 * Export data functionality
 */
function exportData() {
    const orders = window.topupData?.orders || [];
    
    if (orders.length === 0) {
        showNotification('❌ No data to export', 'warning');
        return;
    }
    
    // Prepare export data
    const exportData = orders.map(order => ({
        'Order ID': order.order_id,
        'Description': order.description || '',
        'Base Amount': order.base_amount,
        'Unique Code': order.unique_code,
        'Final Amount': order.final_amount,
        'Status': order.status,
        'Topup Processed': order.topup_processed == 1 ? 'Yes' : 'No',
        'Created At': order.created_at,
        'Paid At': order.paid_at || '',
        'Transaction ID': order.esim_transaction_id || ''
    }));
    
    // Convert to CSV
    const csvContent = convertToCSV(exportData);
    
    // Download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `topup_orders_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('📊 Data exported successfully', 'success');
}

/**
 * Convert array of objects to CSV
 */
function convertToCSV(data) {
    if (data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    const csvHeaders = headers.join(',');
    
    const csvRows = data.map(row => {
        return headers.map(header => {
            const value = row[header] || '';
            // Escape quotes and wrap in quotes if contains comma
            return typeof value === 'string' && (value.includes(',') || value.includes('"'))
                ? `"${value.replace(/"/g, '""')}"`
                : value;
        }).join(',');
    });
    
    return [csvHeaders, ...csvRows].join('\n');
}

// Global functions for modals
function showManualSuccessModal(orderId) {
    document.getElementById('manualOrderId').value = orderId;
    document.getElementById('manualSuccessModal').classList.add('show');
    document.getElementById('manualSuccessModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Focus pada textarea
    setTimeout(() => {
        document.getElementById('manualNotes').focus();
    }, 100);
}

function closeManualSuccessModal() {
    document.getElementById('manualSuccessModal').classList.remove('show');
    setTimeout(() => {
        document.getElementById('manualSuccessModal').style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('manualSuccessForm').reset();
    }, 300);
}

// Global functions for HTML
window.showManualSuccessModal = showManualSuccessModal;
window.closeManualSuccessModal = closeManualSuccessModal;

// Auto-hide success messages
setTimeout(() => {
    const message = document.querySelector('.message.success');
    if (message) {
        message.style.opacity = '0';
        setTimeout(() => {
            message.style.display = 'none';
        }, 300);
    }
}, 5000);

/**
 * Show order details modal
 */
function showOrderDetails(orderId) {
    currentOrderId = orderId;
    
    // Find order data
    const orders = window.topupData?.orders || [];
    const order = orders.find(o => o.order_id === orderId);
    
    if (!order) {
        showNotification('❌ Order not found', 'error');
        return;
    }
    
    // Build modal content
    const modalBody = document.getElementById('orderModalBody');
    if (!modalBody) return;
    
    let topupResult = null;
    if (order.topup_result) {
        try {
            topupResult = JSON.parse(order.topup_result);
        } catch (e) {
            console.error('Error parsing topup result:', e);
        }
    }
    
    modalBody.innerHTML = `
        <div class="order-detail-grid">
            <div class="detail-section">
                <h4>📋 Order Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">${escapeHtml(order.order_id)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value">${escapeHtml(order.description || 'No description')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge ${getStatusClass(order.status)}">
                            ${getStatusIcon(order.status)} ${order.status}
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>💰 Payment Details</h4>
                <div class="detail-row">
                    <span class="detail-label">Base Amount:</span>
                    <span class="detail-value">Rp ${numberFormat(order.base_amount)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Unique Code:</span>
                    <span class="detail-value">+${order.unique_code}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Final Amount:</span>
                    <span class="detail-value"><strong>Rp ${numberFormat(order.final_amount)}</strong></span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>⏰ Timeline</h4>
                <div class="detail-row">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value">${formatDateTime(order.created_at)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expired:</span>
                    <span class="detail-value">${formatDateTime(order.expired_at)}</span>
                </div>
                ${order.paid_at ? `
                <div class="detail-row">
                    <span class="detail-label">Paid:</span>
                    <span class="detail-value">${formatDateTime(order.paid_at)}</span>
                </div>
                ` : ''}
            </div>
            
            ${order.topup_processed == 1 ? `
            <div class="detail-section">
                <h4>🔄 Topup Status</h4>
                <div class="detail-row">
                    <span class="detail-label">Processed:</span>
                    <span class="detail-value">
                        ${topupResult && topupResult.success 
                            ? '<span class="topup-badge success">✅ Success</span>'
                            : '<span class="topup-badge error">❌ Failed</span>'
                        }
                    </span>
                </div>
                ${order.esim_transaction_id ? `
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value">${escapeHtml(order.esim_transaction_id)}</span>
                </div>
                ` : ''}
                ${topupResult && !topupResult.success ? `
                <div class="detail-row">
                    <span class="detail-label">Error:</span>
                    <span class="detail-value error-text">${escapeHtml(topupResult.error || topupResult.errorMsg || 'Unknown error')}</span>
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            ${order.qris_code ? `
            <div class="detail-section">
                <h4>💳 QRIS Details</h4>
                <div class="detail-row">
                    <span class="detail-label">QRIS Code:</span>
                    <span class="detail-value qris-code">${escapeHtml(order.qris_code.substring(0, 50))}...</span>
                </div>
                ${order.qr_code_url ? `
                <div class="detail-row">
                    <span class="detail-label">QR Code:</span>
                    <span class="detail-value">
                        <img src="${escapeHtml(order.qr_code_url)}" alt="QR Code" class="qr-preview" style="max-width: 150px; border-radius: 8px;">
                    </span>
                </div>
                ` : ''}
            </div>
            ` : ''}
        </div>
    `;
    
    // Show modal
    showModal();
}

/**
 * Show modal
 */
function showModal() {
    const modal = document.getElementById('orderModal');
    if (!modal) return;
    
    modal.classList.add('show');
    modal.style.display = 'flex';
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

/**
 * Close order modal
 */
function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    if (!modal) return;
    
    modal.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

/**
 * Utility functions
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function numberFormat(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta'
    };
    
    return date.toLocaleDateString('id-ID', options);
}

function formatRelativeTime(dateString) {
    if (!dateString) return '-';
    
    const now = new Date();
    const date = new Date(dateString);
    const diffMs = now - date;
    const diffSeconds = Math.floor(diffMs / 1000);
    const diffMinutes = Math.floor(diffSeconds / 60);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffSeconds < 60) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 30) return `${diffDays}d ago`;
    
    return formatDateTime(dateString);
}

function getStatusClass(status) {
    switch (status) {
        case 'paid': return 'success';
        case 'pending': return 'warning';
        case 'expired': return 'danger';
        case 'cancelled': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusIcon(status) {
    switch (status) {
        case 'paid': return '✅';
        case 'pending': return '⏳';
        case 'expired': return '⏰';
        case 'cancelled': return '❌';
        default: return '❓';
    }
}

/**
 * Enhanced notification system
 */
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
        z-index: 10000;
        background: var(--gradient-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-xl);
        padding: var(--space-lg);
        box-shadow: var(--shadow-xl);
        max-width: 400px;
        animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        color: var(--text-primary);
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

/**
 * Show loading overlay
 */
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
        
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            color: white;
        `;
        
        document.body.appendChild(overlay);
    }
    
const messageEl = overlay.querySelector('.loading-spinner p');
   if (messageEl) {
       messageEl.textContent = message;
   }
   
   overlay.style.display = 'flex';
   document.body.style.overflow = 'hidden';
   
   // Auto-hide after 10 seconds
   setTimeout(() => {
       hideLoadingOverlay();
   }, 10000);
}

/**
* Hide loading overlay
*/
function hideLoadingOverlay() {
   const overlay = document.getElementById('loadingOverlay');
   if (overlay) {
       overlay.style.display = 'none';
       document.body.style.overflow = '';
   }
}

/**
* Keyboard shortcuts
*/
document.addEventListener('keydown', function(e) {
   // ESC to close modal
   if (e.key === 'Escape') {
       const modal = document.getElementById('orderModal');
       if (modal && modal.classList.contains('show')) {
           closeOrderModal();
       }
   }
   
   // Ctrl+R or Cmd+R to refresh (prevent default and use our refresh)
   if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
       e.preventDefault();
       refreshData();
   }
   
   // Ctrl+E or Cmd+E to export
   if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
       e.preventDefault();
       exportData();
   }
   
   // Ctrl+F to focus search
   if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
       e.preventDefault();
       const searchInput = document.getElementById('search');
       if (searchInput) {
           searchInput.focus();
           searchInput.select();
       }
   }
});

/**
* Auto-hide success messages
*/
setTimeout(() => {
   const message = document.querySelector('.message.success');
   if (message) {
       message.style.opacity = '0';
       setTimeout(() => {
           message.style.display = 'none';
       }, 300);
   }
}, 5000);

/**
* Enhanced table interactions
*/
function initializeTableEnhancements() {
   const rows = document.querySelectorAll('.orders-table tbody tr');
   
   rows.forEach(row => {
       // Add hover effects
       row.addEventListener('mouseenter', function() {
           this.style.transform = 'translateX(4px)';
           this.style.boxShadow = 'var(--shadow-md)';
       });
       
       row.addEventListener('mouseleave', function() {
           this.style.transform = 'translateX(0)';
           this.style.boxShadow = 'none';
       });
       
       // Add click to view details
       row.addEventListener('click', function(e) {
           // Don't trigger if clicking on buttons
           if (e.target.closest('button') || e.target.closest('form')) {
               return;
           }
           
           const orderIdElement = this.querySelector('.order-id');
           if (orderIdElement) {
               const orderId = orderIdElement.textContent.trim();
               showOrderDetails(orderId);
           }
       });
       
       // Add cursor pointer
       row.style.cursor = 'pointer';
   });
}

// Initialize table enhancements when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeTableEnhancements);

/**
* Status update animations
*/
function animateStatusUpdate(orderId, newStatus) {
   const rows = document.querySelectorAll('.orders-table tbody tr');
   
   rows.forEach(row => {
       const orderIdElement = row.querySelector('.order-id');
       if (orderIdElement && orderIdElement.textContent.trim() === orderId) {
           const statusBadge = row.querySelector('.status-badge');
           if (statusBadge) {
               // Add animation class
               statusBadge.style.animation = 'pulse 0.5s ease-in-out';
               
               // Update status after animation
               setTimeout(() => {
                   statusBadge.className = `status-badge ${getStatusClass(newStatus)}`;
                   statusBadge.innerHTML = `${getStatusIcon(newStatus)} ${newStatus}`;
                   statusBadge.style.animation = '';
               }, 250);
           }
       }
   });
}

/**
* Copy order ID to clipboard
*/
function copyOrderId(orderId) {
   navigator.clipboard.writeText(orderId).then(() => {
       showNotification(`📋 Copied order ID: ${orderId}`, 'success', 2000);
   }).catch(() => {
       // Fallback for older browsers
       const textArea = document.createElement('textarea');
       textArea.value = orderId;
       document.body.appendChild(textArea);
       textArea.select();
       document.execCommand('copy');
       document.body.removeChild(textArea);
       showNotification(`📋 Copied order ID: ${orderId}`, 'success', 2000);
   });
}

/**
* Bulk actions functionality
*/
function initializeBulkActions() {
   // Add checkboxes to table rows
   const tableBody = document.querySelector('.orders-table tbody');
   if (!tableBody) return;
   
   // Add header checkbox
   const headerRow = document.querySelector('.orders-table thead tr');
   if (headerRow) {
       const headerCheckbox = document.createElement('th');
       headerCheckbox.innerHTML = '<input type="checkbox" id="selectAll" onchange="toggleSelectAll()">';
       headerRow.insertBefore(headerCheckbox, headerRow.firstChild);
   }
   
   // Add row checkboxes
   const rows = tableBody.querySelectorAll('tr');
   rows.forEach(row => {
       const orderIdElement = row.querySelector('.order-id');
       if (orderIdElement) {
           const orderId = orderIdElement.textContent.trim();
           const checkbox = document.createElement('td');
           checkbox.innerHTML = `<input type="checkbox" class="row-checkbox" value="${orderId}" onchange="updateBulkActions()">`;
           row.insertBefore(checkbox, row.firstChild);
       }
   });
   
   // Add bulk actions bar
   const bulkActionsBar = document.createElement('div');
   bulkActionsBar.id = 'bulkActionsBar';
   bulkActionsBar.className = 'bulk-actions-bar';
   bulkActionsBar.style.display = 'none';
   bulkActionsBar.innerHTML = `
       <div class="bulk-actions-content">
           <span class="bulk-selected-count">0 orders selected</span>
           <div class="bulk-actions-buttons">
               <button class="btn-action danger" onclick="bulkCancelOrders()">❌ Cancel Selected</button>
               <button class="btn-action info" onclick="bulkExportOrders()">📊 Export Selected</button>
           </div>
       </div>
   `;
   
   const ordersSection = document.querySelector('.orders-section');
   if (ordersSection) {
       ordersSection.insertBefore(bulkActionsBar, ordersSection.querySelector('.table-container'));
   }
}

/**
* Toggle select all checkboxes
*/
function toggleSelectAll() {
   const selectAllCheckbox = document.getElementById('selectAll');
   const rowCheckboxes = document.querySelectorAll('.row-checkbox');
   
   rowCheckboxes.forEach(checkbox => {
       checkbox.checked = selectAllCheckbox.checked;
   });
   
   updateBulkActions();
}

/**
* Update bulk actions based on selection
*/
function updateBulkActions() {
   const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
   const bulkActionsBar = document.getElementById('bulkActionsBar');
   const selectedCount = document.querySelector('.bulk-selected-count');
   
   if (checkedBoxes.length > 0) {
       bulkActionsBar.style.display = 'block';
       selectedCount.textContent = `${checkedBoxes.length} order${checkedBoxes.length > 1 ? 's' : ''} selected`;
   } else {
       bulkActionsBar.style.display = 'none';
   }
}

/**
* Performance monitoring
*/
function monitorPerformance() {
   // Monitor page load time
   window.addEventListener('load', function() {
       const loadTime = performance.now();
       console.log(`📊 Page loaded in ${Math.round(loadTime)}ms`);
       
       // Show warning if load time is too high
       if (loadTime > 3000) {
           showNotification('⚠️ Page load time is slow. Consider optimizing filters.', 'warning', 5000);
       }
   });
   
   // Monitor memory usage (if available)
   if ('memory' in performance) {
       setInterval(() => {
           const memory = performance.memory;
           const usedMB = Math.round(memory.usedJSHeapSize / 1024 / 1024);
           
           // Warn if memory usage is too high
           if (usedMB > 100) {
               console.warn(`💾 High memory usage: ${usedMB}MB`);
           }
       }, 30000);
   }
}

/**
* Initialize error reporting
*/
function initializeErrorReporting() {
   window.addEventListener('error', function(e) {
       console.error('JavaScript Error:', e.error);
       
       // Show user-friendly error message
       showNotification('⚠️ Something went wrong. Please refresh the page.', 'error', 5000);
   });
   
   window.addEventListener('unhandledrejection', function(e) {
       console.error('Unhandled Promise Rejection:', e.reason);
       
       // Show user-friendly error message
       showNotification('⚠️ Network error occurred. Please check your connection.', 'error', 5000);
   });
}

// Cleanup function
function cleanup() {
   disableAutoRefresh();
   hideLoadingOverlay();
   closeOrderModal();
}

// Event listeners for cleanup
window.addEventListener('beforeunload', cleanup);
window.addEventListener('unload', cleanup);

// Global functions for HTML onclick events
window.refreshData = refreshData;
window.clearFilters = clearFilters;
window.exportData = exportData;
window.showOrderDetails = showOrderDetails;
window.closeOrderModal = closeOrderModal;
window.copyOrderId = copyOrderId;
window.toggleSelectAll = toggleSelectAll;
window.updateBulkActions = updateBulkActions;

// Initialize additional features
document.addEventListener('DOMContentLoaded', function() {
   initializeTableEnhancements();
   monitorPerformance();
   initializeErrorReporting();
   // initializeBulkActions(); // Uncomment if you want bulk actions
});

// Console welcome message
console.log(`
💰 eSIM Portal Topup Monitor
🚀 Version: 2.0 - Real-time Monitoring
💻 Features: Auto-refresh, Export, Search, Filter
🎨 Design: Modern Dark/Light Mode
📱 Mobile Responsive Design
🔒 Secure & Optimized

Keyboard Shortcuts:
- Ctrl/Cmd + R: Refresh data
- Ctrl/Cmd + E: Export data  
- Ctrl/Cmd + F: Focus search
- ESC: Close modal

Features Available:
- Real-time status updates
- Auto-refresh for pending orders
- Advanced filtering & search
- Export to CSV
- Order details modal
- Performance monitoring
- Error reporting
- Theme switching
- Mobile optimized interface

Auto-refresh: ${autoRefreshEnabled ? 'Enabled' : 'Disabled'}
Orders loaded: ${window.topupData?.orders?.length || 0}
`);