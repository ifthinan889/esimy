// Contact Page JavaScript Functions

// Theme Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // Initialize theme from localStorage or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    // Theme toggle event listener
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }
    
    function updateThemeIcon(theme) {
        if (themeIcon) {
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun';
            } else {
                themeIcon.className = 'fas fa-moon';
            }
        }
    }
});

// Contact Support Functions
function openWhatsAppSupport() {
    const message = encodeURIComponent('Hello! I need help with eSIM services. Can you assist me?');
    window.open(`https://wa.me/6281325525646?text=${message}`, '_blank');
}

function openEmailSupport() {
    const subject = encodeURIComponent('eSIM Support Request');
    const body = encodeURIComponent(`Hello eSIM Store Support Team,

I need assistance with:

[Please describe your issue here]

Device Information:
- Device Model: [Your device model]
- Operating System: [iOS/Android version]
- Order Number (if applicable): [Your order number]

Thank you for your help!

Best regards,
[Your Name]`);
    
    window.open(`mailto:support@esimstore.com?subject=${subject}&body=${body}`, '_blank');
}

function openLiveChat() {
    // This would typically integrate with a live chat service
    // For now, we'll redirect to WhatsApp as fallback
    showNotification('Live chat is currently redirecting to WhatsApp for immediate assistance.', 'info');
    setTimeout(() => {
        openWhatsAppSupport();
    }, 2000);
}

function openEmergencyWhatsApp() {
    const message = encodeURIComponent('🚨 URGENT: I need immediate help with my eSIM while traveling. Please assist ASAP!');
    window.open(`https://wa.me/6281325525646?text=${message}`, '_blank');
}

// FAQ Functions
function toggleFAQ(element) {
    const isActive = element.classList.contains('active');
    
    // Close all FAQ items
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked item if it wasn't active
    if (!isActive) {
        element.classList.add('active');
    }
}

// Quick Help Functions
function showCompatibilityCheck() {
    const modal = createModal('Device Compatibility Check', `
        <div class="compatibility-guide">
            <h3>How to Check eSIM Compatibility:</h3>
            <div class="guide-steps">
                <div class="guide-step">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>Dial *#06#</strong>
                        <p>Open your phone dialer and dial *#06#</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <strong>Look for EID</strong>
                        <p>If you see an EID number, your device supports eSIM</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">3</div>
                    <div class="step-text">
                        <strong>Check Settings</strong>
                        <p>Go to Settings > Cellular > Add Cellular Plan</p>
                    </div>
                </div>
            </div>
            <div class="compatible-devices">
                <h4>Compatible Devices Include:</h4>
                <ul>
                    <li>iPhone XS and newer</li>
                    <li>iPad Pro (3rd gen) and newer</li>
                    <li>Samsung Galaxy S20 and newer</li>
                    <li>Google Pixel 3 and newer</li>
                    <li>Most recent Android flagships</li>
                </ul>
            </div>
            <div class="guide-actions">
                <button class="btn-guide-action" onclick="openWhatsAppSupport()">
                    <i class="fab fa-whatsapp"></i> Get Help via WhatsApp
                </button>
            </div>
        </div>
    `);
    showModal(modal);
}

function showInstallationGuide() {
    const modal = createModal('eSIM Installation Guide', `
        <div class="installation-guide">
            <h3>How to Install Your eSIM:</h3>
            <div class="guide-steps">
                <div class="guide-step">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>Receive QR Code</strong>
                        <p>Check your email for the QR code and installation instructions</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">2</div>
                    <div class="step-text">
                        <strong>Scan QR Code</strong>
                        <p>Use your device camera to scan the QR code</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">3</div>
                    <div class="step-text">
                        <strong>Add Cellular Plan</strong>
                        <p>Go to Settings > Cellular > Add Cellular Plan</p>
                    </div>
                </div>
                <div class="guide-step">
                    <div class="step-number">4</div>
                    <div class="step-text">
                        <strong>Complete Setup</strong>
                        <p>Follow the on-screen instructions to complete setup</p>
                    </div>
                </div>
            </div>
            <div class="installation-tips">
                <h4>Important Tips:</h4>
                <ul>
                    <li>Install before traveling but don't activate until needed</li>
                    <li>Ensure you have a stable internet connection during installation</li>
                    <li>Keep your original SIM card as backup</li>
                    <li>Contact support if you encounter any issues</li>
                </ul>
            </div>
        </div>
    `);
    showModal(modal);
}

function showTroubleshooting() {
    const modal = createModal('Troubleshooting Tips', `
        <div class="troubleshooting-guide">
            <h3>Common Issues and Solutions:</h3>
            <div class="troubleshooting-items">
                <div class="troubleshooting-item">
                    <h4><i class="fas fa-exclamation-triangle"></i> eSIM Won't Activate</h4>
                    <ul>
                        <li>Check if you have internet connection</li>
                        <li>Restart your device</li>
                        <li>Try scanning the QR code again</li>
                        <li>Contact support if issue persists</li>
                    </ul>
                </div>
                <div class="troubleshooting-item">
                    <h4><i class="fas fa-signal"></i> No Network Signal</h4>
                    <ul>
                        <li>Check if you're in a covered area</li>
                        <li>Toggle airplane mode on/off</li>
                        <li>Select network manually in settings</li>
                        <li>Wait 5-10 minutes for network registration</li>
                    </ul>
                </div>
                <div class="troubleshooting-item">
                    <h4><i class="fas fa-wifi"></i> Slow Data Speeds</h4>
                    <ul>
                        <li>Check network coverage in your area</li>
                        <li>Move to a different location</li>
                        <li>Restart your device</li>
                        <li>Check if you've exceeded data limits</li>
                    </ul>
                </div>
            </div>
            <div class="emergency-contact">
                <p><strong>Still need help?</strong> Contact our 24/7 support team via WhatsApp for immediate assistance.</p>
                <button class="btn-guide-action" onclick="openWhatsAppSupport()">
                    <i class="fab fa-whatsapp"></i> Contact Support
                </button>
            </div>
        </div>
    `);
    showModal(modal);
}

// Contact Form Functions
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    // Generate CSRF token
    if (!document.querySelector('input[name="csrf_token"]')) {
        const csrfToken = generateCSRFToken();
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = csrfToken;
        if (contactForm) {
            contactForm.appendChild(csrfInput);
        }
    }
    
    // Character count for message textarea
    if (messageTextarea && charCount) {
        messageTextarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCount.textContent = currentLength;
            
            if (currentLength > 1000) {
                charCount.style.color = 'var(--error)';
            } else if (currentLength > 800) {
                charCount.style.color = 'var(--warning)';
            } else {
                charCount.style.color = 'var(--text-muted)';
            }
        });
    }
    
    // Form submission
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission(this);
        });
    }
    
    // Auto-fill form based on inquiry type
    const inquiryTypeSelect = document.getElementById('inquiryType');
    const subjectInput = document.getElementById('subject');
    
    if (inquiryTypeSelect && subjectInput) {
        inquiryTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            const subjectTemplates = {
                'technical': 'Technical Support - ',
                'billing': 'Billing Inquiry - ',
                'refund': 'Refund Request - ',
                'compatibility': 'Device Compatibility Question - ',
                'coverage': 'Coverage Inquiry - ',
                'general': 'General Question - ',
                'partnership': 'Business Partnership Inquiry - ',
                'other': 'Other Inquiry - '
            };
            
            if (subjectTemplates[selectedType] && !subjectInput.value) {
                subjectInput.value = subjectTemplates[selectedType];
                subjectInput.focus();
                subjectInput.setSelectionRange(subjectTemplates[selectedType].length, subjectTemplates[selectedType].length);
            }
        });
    }
});

function generateCSRFToken() {
    const token = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    // Store in session via AJAX call
    fetch('contact_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=generate_csrf&token=' + encodeURIComponent(token)
    });
    return token;
}

function handleFormSubmission(form) {
    const submitBtn = form.querySelector('.form-submit-btn');
    const btnText = submitBtn.querySelector('span');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    
    // Validate form
    if (!validateForm(form)) {
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.style.opacity = '0';
    btnLoader.style.display = 'block';
    form.classList.add('form-loading');
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Submit form via AJAX
    fetch('contact_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset form
            form.reset();
            document.getElementById('charCount').textContent = '0';
            
            // Show success message
            showFormMessage(data.message, 'success');
            
            // Scroll to top of form
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            // Show error message
            showFormMessage(data.message || 'An error occurred. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        showFormMessage('Network error. Please check your connection and try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        btnText.style.opacity = '1';
        btnLoader.style.display = 'none';
        form.classList.remove('form-loading');
    });
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
            
            // Remove error class after animation
            setTimeout(() => {
                field.classList.remove('error');
            }, 500);
        }
    });
    
    // Validate email format
    const emailField = form.querySelector('#email');
    if (emailField && emailField.value && !isValidEmail(emailField.value)) {
        emailField.classList.add('error');
        showFormMessage('Please enter a valid email address.', 'error');
        isValid = false;
    }
    
    if (!isValid) {
        showFormMessage('Please fill in all required fields correctly.', 'error');
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFormMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.form-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `form-message ${type}`;
    messageDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        <span>${message}</span>
    `;
    
    // Insert at top of form
    const form = document.getElementById('contactForm');
    form.insertBefore(messageDiv, form.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Modal Functions
function createModal(title, content) {
    return `
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">${title}</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
        </div>
    `;
}

function showModal(modalHTML) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = modalHTML;
    modal.style.display = 'flex';
    document.body.appendChild(modal);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Add escape key listener
    document.addEventListener('keydown', handleEscapeKey);
}

function closeModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleEscapeKey);
    }
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add scroll-based animations for cards
document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll('.contact-method-card, .sidebar-card, .faq-item, .emergency-card');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(element);
    });
});

// Add interactive effects to contact method cards
document.addEventListener('DOMContentLoaded', function() {
    const methodCards = document.querySelectorAll('.contact-method-card');
    
    methodCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});

// Performance optimization: Reduce motion for users who prefer it
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.style.setProperty('--transition-fast', '0.01ms');
    document.documentElement.style.setProperty('--transition-normal', '0.01ms');
}

// Error handling for missing elements
window.addEventListener('error', function(e) {
    console.log('Contact page script error:', e.error);
});

// Add loading state management
document.addEventListener('DOMContentLoaded', function() {
    // Remove any loading states
    document.body.classList.add('loaded');
    
    // Add loaded class for CSS animations
    const style = document.createElement('style');
    style.textContent = `
        body:not(.loaded) * {
            animation-play-state: paused !important;
        }
        
        .loaded {
            animation-play-state: running !important;
        }
    `;
    document.head.appendChild(style);
});

// Add keyboard navigation for accessibility
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Close any open modals or overlays
        closeModal();
    }
});

// Add CSS for modal styles and other dynamic elements
const contactStyles = `
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: var(--space-lg);
    }
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
    }
    
    .modal-content {
        background: var(--bg-card);
        border-radius: var(--radius-xl);
        padding: var(--space-2xl);
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        z-index: 2001;
        box-shadow: var(--shadow-lg);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-xl);
        padding-bottom: var(--space-lg);
        border-bottom: 1px solid var(--border-light);
    }
    
    .modal-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    
    .modal-close {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-secondary);
        border: none;
        border-radius: var(--radius-full);
        color: var(--text-muted);
        cursor: pointer;
        transition: all var(--transition-fast);
    }
    
    .modal-close:hover {
        background: var(--error);
        color: white;
        transform: scale(1.1);
    }
    
    .guide-steps {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
        margin: var(--space-lg) 0;
    }
    
    .guide-step {
        display: flex;
        align-items: flex-start;
        gap: var(--space-lg);
    }
    
    .guide-step .step-number {
        width: 30px;
        height: 30px;
        background: var(--gradient-primary);
        color: white;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .guide-step .step-text strong {
        display: block;
        margin-bottom: var(--space-xs);
        color: var(--text-primary);
    }
    
    .guide-step .step-text p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .compatible-devices,
    .installation-tips {
        margin-top: var(--space-xl);
        padding: var(--space-lg);
        background: var(--bg-secondary);
        border-radius: var(--radius-lg);
    }
    
    .compatible-devices h4,
    .installation-tips h4 {
        margin-bottom: var(--space-md);
        color: var(--text-primary);
    }
    
    .compatible-devices ul,
    .installation-tips ul {
        margin: 0;
        padding-left: var(--space-lg);
    }
    
    .compatible-devices li,
    .installation-tips li {
        margin-bottom: var(--space-xs);
        color: var(--text-secondary);
    }
    
    .guide-actions {
        display: flex;
        gap: var(--space-md);
        margin-top: var(--space-xl);
        flex-wrap: wrap;
    }
    
    .btn-guide-action {
        background: var(--gradient-primary);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-lg);
        border-radius: var(--radius-lg);
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        font-size: 0.875rem;
        flex: 1;
        justify-content: center;
    }
    
    .btn-guide-action:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-sm);
    }
    
    .troubleshooting-items {
        display: flex;
        flex-direction: column;
        gap: var(--space-lg);
        margin: var(--space-lg) 0;
    }
    
    .troubleshooting-item {
        padding: var(--space-lg);
        background: var(--bg-secondary);
        border-radius: var(--radius-lg);
        border-left: 4px solid var(--text-accent);
    }
    
    .troubleshooting-item h4 {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        margin-bottom: var(--space-md);
        color: var(--text-primary);
    }
    
    .troubleshooting-item h4 i {
        color: var(--text-accent);
    }
    
    .troubleshooting-item ul {
        margin: 0;
        padding-left: var(--space-lg);
    }
    
    .troubleshooting-item li {
        margin-bottom: var(--space-xs);
        color: var(--text-secondary);
    }
    
    .emergency-contact {
        margin-top: var(--space-xl);
        padding: var(--space-lg);
        background: rgba(220, 38, 38, 0.1);
        border: 1px solid var(--error);
        border-radius: var(--radius-lg);
        text-align: center;
    }
    
    .emergency-contact p {
        margin-bottom: var(--space-lg);
        color: var(--text-primary);
    }
    
    .notification {
        position: fixed;
        top: var(--space-lg);
        right: var(--space-lg);
        background: var(--bg-card);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: var(--space-md) var(--space-lg);
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    }
    
    .notification.success {
        border-color: var(--success);
        background: rgba(5, 150, 105, 0.1);
    }
    
    .notification.success i {
        color: var(--success);
    }
    
    .notification.error {
        border-color: var(--error);
        background: rgba(220, 38, 38, 0.1);
    }
    
    .notification.error i {
        color: var(--error);
    }
    
    .notification.info {
        border-color: var(--info);
        background: rgba(2, 132, 199, 0.1);
    }
    
    .notification.info i {
        color: var(--info);
    }
    
    .notification-close {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: var(--space-xs);
        margin-left: auto;
        border-radius: var(--radius-sm);
        transition: all var(--transition-fast);
    }
    
    .notification-close:hover {
        background: var(--bg-secondary);
        color: var(--text-primary);
    }
    
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
    
    @media (max-width: 768px) {
        .modal-content {
            margin: 0;
            width: 100%;
            height: 100vh;
            border-radius: 0;
            max-height: none;
        }
        
        .guide-steps {
            gap: var(--space-md);
        }
        
        .guide-step {
            flex-direction: column;
            text-align: center;
        }
        
        .guide-actions {
            flex-direction: column;
        }
        
        .notification {
            top: var(--space-sm);
            right: var(--space-sm);
            left: var(--space-sm);
            max-width: none;
        }
    }
`;

// Add contact styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = contactStyles;
document.head.appendChild(styleSheet);