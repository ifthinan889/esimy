/* ===== TOPUP CSS - Balanced Approach ===== */
:root {
    --primary-color: #3b82f6;
    --secondary-color: #64748b;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --error-color: #ef4444;
    --pending-color: #f97316;
    --expired-color: #8b5cf6;
    
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #f1f5f9;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    
    --border-radius: 12px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

[data-theme="dark"] {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f8fafc;
    --text-secondary: #cbd5e1;
    --border-color: #475569;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-secondary);
    color: var(--text-primary);
    line-height: 1.6;
    min-height: 100vh;
    transition: var(--transition);
}

.container {
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ===== HEADER ===== */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    margin-bottom: 2rem;
}

.logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-color);
}

.theme-toggle {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.2rem;
    transition: var(--transition);
}

.theme-toggle:hover {
    background: var(--primary-color);
    color: white;
    transform: scale(1.05);
}

/* ===== ALERTS ===== */
.alert {
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
    border: 1px solid;
}

.alert-error {
    background: #fef2f2;
    border-color: var(--error-color);
    color: #991b1b;
}

.alert-warning {
    background: #fffbeb;
    border-color: var(--warning-color);
    color: #92400e;
}

[data-theme="dark"] .alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #fca5a5;
}

[data-theme="dark"] .alert-warning {
    background: rgba(245, 158, 11, 0.1);
    color: #fcd34d;
}

/* ===== USER CARD ===== */
.user-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow-sm);
}

.user-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), var(--success-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.5rem;
}

.user-name {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}

.user-iccid {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

/* ===== FORM SECTION ===== */
.form-section {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.section-header {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.5rem;
    color: var(--text-primary);
}

/* ===== PACKAGE GRID ===== */
.package-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.package-card {
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    cursor: pointer;
    transition: var(--transition);
    background: var(--bg-secondary);
    position: relative;
    overflow: hidden;
}

.package-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.package-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.package-card input[type="radio"]:checked + .package-content {
    color: var(--primary-color);
}

.package-card input[type="radio"]:checked + .package-content::before {
    content: '✓';
    position: absolute;
    top: -10px;
    right: -10px;
    background: var(--success-color);
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
}

.package-content {
    text-align: center;
    position: relative;
}

.package-size {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.package-name {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.package-price {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--success-color);
}

/* ===== PAYMENT METHOD ===== */
.payment-grid {
    margin-bottom: 2rem;
}

.payment-group h3 {
    margin-bottom: 1rem;
    color: var(--text-primary);
}

.payment-methods {
    display: grid;
    gap: 1rem;
}

.payment-method {
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    background: var(--bg-secondary);
    transition: var(--transition);
}

.payment-method.selected {
    border-color: var(--primary-color);
    background: rgba(59, 130, 246, 0.05);
}

.payment-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.payment-icon {
    font-size: 1.5rem;
}

.payment-info {
    flex: 1;
}

.payment-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.payment-fee {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.payment-total {
    font-weight: bold;
    color: var(--primary-color);
    margin-top: 0.25rem;
}

/* ===== BUTTONS ===== */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-secondary);
}

.btn-success {
    background: var(--success-color);
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: var(--error-color);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

/* ===== PAYMENT COMPLETE ===== */
.payment-complete {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.payment-status {
    background: linear-gradient(135deg, var(--pending-color), #fb923c);
    color: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.payment-status.paid {
    background: linear-gradient(135deg, var(--success-color), #34d399);
}

.payment-status.expired {
    background: linear-gradient(135deg, var(--expired-color), #a78bfa);
}

.payment-status::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.status-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.payment-status h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.payment-status p {
    font-size: 1rem;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

/* ===== AUTO CHECK INFO ===== */
.auto-check-info {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    position: relative;
    z-index: 1;
}

.check-status {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.next-check {
    font-size: 0.75rem;
    opacity: 0.8;
}

#countdown {
    font-weight: bold;
    color: #fbbf24;
}

/* ===== ORDER INFO ===== */
.order-info {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.total-amount {
    font-size: 1.25rem;
    color: var(--primary-color);
    font-weight: bold;
}

/* ===== QR CODE ===== */
.payment-method-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    text-align: center;
    box-shadow: var(--shadow-sm);
}

.payment-method-card h3 {
    margin-bottom: 1.5rem;
    color: var(--text-primary);
}

.qr-container {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.qr-image {
    max-width: 240px;
    border-radius: 12px;
    background: white;
    padding: 8px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
}

.qr-info {
    color: var(--text-secondary);
    font-size: 0.875rem;
    line-height: 1.5;
}

.qr-info small {
    font-size: 0.75rem;
    display: block;
    margin-top: 0.5rem;
}

#expiredTimer {
    font-weight: bold;
    color: var(--warning-color);
}

/* ===== PAYMENT ACTIONS ===== */
.payment-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}

/* ===== SUCCESS DETAILS ===== */
.success-details {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.success-card h3 {
    color: var(--success-color);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.success-info .info-row {
    padding: 0.5rem 0;
}

/* ===== ACTION BUTTONS ===== */
.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

/* ===== FOOTER ===== */
.footer {
    text-align: center;
    padding: 2rem 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-top: auto;
}

/* ===== LOADING OVERLAY ===== */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.loading-overlay.show {
    opacity: 1;
}

.loading-spinner {
    background: var(--bg-primary);
    padding: 2rem;
    border-radius: var(--border-radius);
    text-align: center;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--border-color);
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ===== TOAST NOTIFICATIONS ===== */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10001;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.toast {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    box-shadow: var(--shadow-lg);
    max-width: 300px;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast.success {
    border-left: 4px solid var(--success-color);
}

.toast.error {
    border-left: 4px solid var(--error-color);
}

.toast.warning {
    border-left: 4px solid var(--warning-color);
}

.toast.info {
    border-left: 4px solid var(--primary-color);
}

.toast-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.toast-icon {
    font-size: 1.2rem;
    flex-shrink: 0;
}

.toast-message {
    flex: 1;
    font-size: 0.875rem;
    line-height: 1.4;
}

.toast-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 0;
    margin-left: 0.5rem;
}

.toast-close:hover {
    color: var(--text-primary);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 640px) {
    .container {
        padding: 0.5rem;
    }
    
    .package-grid {
        grid-template-columns: 1fr;
    }
    
    .payment-actions,
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .qr-image {
        max-width: 200px;
    }
    
    .toast {
        margin: 0 1rem;
        max-width: calc(100vw - 2rem);
    }
}

/* ===== ANIMATIONS ===== */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.payment-complete > * {
    animation: fadeInUp 0.6s ease forwards;
}

.payment-complete > *:nth-child(2) {
    animation-delay: 0.1s;
}

.payment-complete > *:nth-child(3) {
    animation-delay: 0.2s;
}

.payment-complete > *:nth-child(4) {
    animation-delay: 0.3s;
}

/* ===== ACCESSIBILITY ===== */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ===== FOCUS STYLES ===== */
.btn:focus,
.package-card:focus-within,
.payment-method:focus-within,
.theme-toggle:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* ===== PRINT STYLES ===== */
@media print {
    .theme-toggle,
    .payment-actions,
    .action-buttons,
    .auto-check-info {
        display: none !important;
    }
    
    body {
        background: white !important;
        color: black !important;
    }
    
    .payment-status {
        background: white !important;
        color: black !important;
        border: 2px solid black !important;
    }
}