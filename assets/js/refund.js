// Refund Page JavaScript Functions

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

// Refund Support Functions
function openWhatsAppRefund() {
    const message = encodeURIComponent('Hello! I need help with a refund request. Here are my details:\n\nOrder Number: [Your Order Number]\nIssue: [Describe your issue]\nDevice: [Your device model]\n\nThank you!');
    window.open(`https://wa.me/6281325525646?text=${message}`, '_blank');
}

function openEmailRefund() {
    const subject = encodeURIComponent('Refund Request - Order #[YOUR_ORDER_NUMBER]');
    const body = encodeURIComponent(`Hello eSIM Store Support Team,

I am requesting a refund for my recent order. Please find the details below:

- Order Number: [YOUR_ORDER_NUMBER]
- Purchase Email: [YOUR_EMAIL]
- Issue Description: [DETAILED_ISSUE_DESCRIPTION]
- Device Model: [YOUR_DEVICE_MODEL]
- Location Attempted: [WHERE_YOU_TRIED_TO_USE_ESIM]
- Date of Issue: [WHEN_THE_ISSUE_OCCURRED]

I have attached relevant screenshots if applicable.

Thank you for your assistance.

Best regards,
[Your Name]`);
    
    window.open(`mailto:refunds@esimstore.com?subject=${subject}&body=${body}`, '_blank');
}

// Prevention Tips Functions
function showCompatibilityGuide() {
    const modal = createModal('Device Compatibility Guide', `
        <div class="compatibility-guide">
            <h3>How to Check eSIM Compatibility:</h3>
            <div class="guide-steps">
                <div class="guide-step">
                    <div class="step-number">1</div>
                    <div class="step-text">
                        <strong>Dial *#06#</strong>
                        <p>This will show your device information</p>
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
        </div>
    `);
    showModal(modal);
}

function showCoverageMap() {
    alert('Coverage Map:\n\nOur eSIMs work in 190+ countries worldwide.\n\nFor detailed coverage information:\n• Visit our website coverage page\n• Contact support for specific locations\n• Check with local operators for remote areas\n\nNote: Coverage may vary in rural or mountainous regions.');
}

function showTimingTips() {
    const modal = createModal('Optimal Purchase Timing', `
        <div class="timing-tips">
            <h3>Best Practices for eSIM Purchase:</h3>
            <div class="timing-grid">
                <div class="timing-item">
                    <i class="fas fa-calendar-check"></i>
                    <h4>1-3 Days Before Travel</h4>
                    <p>Ideal timing for most destinations</p>
                </div>
                <div class="timing-item">
                    <i class="fas fa-plane-departure"></i>
                    <h4>At the Airport</h4>
                    <p>Perfect for last-minute purchases</p>
                </div>
                <div class="timing-item">
                    <i class="fas fa-clock"></i>
                    <h4>Avoid Early Purchase</h4>
                    <p>Don't buy more than 1 week in advance</p>
                </div>
                <div class="timing-item">
                    <i class="fas fa-wifi"></i>
                    <h4>Test Before Travel</h4>
                    <p>Install but don't activate until needed</p>
                </div>
            </div>
        </div>
    `);
    showModal(modal);
}

function openPreSalesSupport() {
    const message = encodeURIComponent('Hello! I have some questions before purchasing an eSIM. Can you help me with:\n\n1. [Your question here]\n2. [Another question]\n\nThank you!');
    window.open(`https://wa.me/6281325525646?text=${message}`, '_blank');
}

// Email Template Functions
function copyEmailTemplate() {
    const template = `Subject: Refund Request - Order #[YOUR_ORDER_NUMBER]

Body:
- Order Number: [ORDER_NUMBER]
- Purchase Email: [EMAIL]
- Issue Description: [DETAILED_ISSUE]
- Device Model: [DEVICE_INFO]
- Location Attempted: [LOCATION]
- Attachments: [SCREENSHOTS_IF_ANY]`;

    navigator.clipboard.writeText(template).then(() => {
        showCopySuccess('Email template copied to clipboard!');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = template;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showCopySuccess('Email template copied to clipboard!');
    });
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

// Copy Success Feedback
function showCopySuccess(message) {
    const feedback = document.createElement('div');
    feedback.className = 'copy-success';
    feedback.textContent = message;
    document.body.appendChild(feedback);
    
    setTimeout(() => {
        feedback.remove();
    }, 2000);
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
    const cards = document.querySelectorAll('.legal-card, .tip-item, .method-item');
    
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
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});

// Add interactive effects to timeline steps
document.addEventListener('DOMContentLoaded', function() {
    const timelineSteps = document.querySelectorAll('.timeline-step');
    
    timelineSteps.forEach((step, index) => {
        step.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(10px)';
        });
        
        step.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});

// Add click tracking for refund actions
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('.btn-action, .tip-action');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const actionType = this.textContent.trim();
            console.log('Refund action clicked:', actionType);
            // Here you can add analytics tracking code
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
    console.log('Refund page script error:', e.error);
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

// Print functionality
function printRefundPolicy() {
    window.print();
}

// Share refund policy
function shareRefundPolicy() {
    if (navigator.share) {
        navigator.share({
            title: 'eSIM Store Refund Policy',
            text: 'Learn about eSIM Store refund policy and process.',
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showCopySuccess('Refund Policy URL copied to clipboard!');
        }).catch(() => {
            alert('Refund Policy URL: ' + window.location.href);
        });
    }
}

// Add keyboard navigation for accessibility
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Close any open modals or overlays
        closeModal();
    }
});

// Add right-click context menu for refund actions
document.addEventListener('contextmenu', function(e) {
    if (e.target.closest('.contact-method')) {
        e.preventDefault();
        
        const contextMenu = document.createElement('div');
        contextMenu.className = 'context-menu';
        contextMenu.style.cssText = `
            position: fixed;
            top: ${e.clientY}px;
            left: ${e.clientX}px;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: var(--space-sm);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
        `;
        
        contextMenu.innerHTML = `
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="openWhatsAppRefund()">WhatsApp Refund</div>
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="openEmailRefund()">Email Refund</div>
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="copyEmailTemplate()">Copy Template</div>
        `;
        
        document.body.appendChild(contextMenu);
        
        // Remove context menu on click outside
        setTimeout(() => {
            document.addEventListener('click', function removeMenu() {
                contextMenu.remove();
                document.removeEventListener('click', removeMenu);
            });
        }, 100);
    }
});

// Add CSS for modal styles
const modalStyles = `
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
    
    .step-number {
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
    
    .step-text strong {
        display: block;
        margin-bottom: var(--space-xs);
        color: var(--text-primary);
    }
    
    .step-text p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .compatible-devices {
        margin-top: var(--space-xl);
        padding: var(--space-lg);
        background: var(--bg-secondary);
        border-radius: var(--radius-lg);
    }
    
    .compatible-devices h4 {
        margin-bottom: var(--space-md);
        color: var(--text-primary);
    }
    
    .compatible-devices ul {
        margin: 0;
        padding-left: var(--space-lg);
    }
    
    .compatible-devices li {
        margin-bottom: var(--space-xs);
        color: var(--text-secondary);
    }
    
    .timing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-lg);
        margin: var(--space-lg) 0;
    }
    
    .timing-item {
        text-align: center;
        padding: var(--space-lg);
        background: var(--bg-secondary);
        border-radius: var(--radius-lg);
    }
    
    .timing-item i {
        font-size: 2rem;
        color: var(--text-accent);
        margin-bottom: var(--space-md);
    }
    
    .timing-item h4 {
        margin-bottom: var(--space-sm);
        color: var(--text-primary);
    }
    
    .timing-item p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .copy-success {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: var(--success);
        color: white;
        padding: var(--space-md) var(--space-xl);
        border-radius: var(--radius-lg);
        font-weight: 600;
        z-index: 10000;
        pointer-events: none;
        opacity: 0;
        animation: copyFeedback 2s ease-out forwards;
    }
    
    @keyframes copyFeedback {
        0% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8);
        }
        15% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1.1);
        }
        85% {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        100% {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.9);
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
        
        .timing-grid {
            grid-template-columns: 1fr;
        }
        
        .guide-step {
            flex-direction: column;
            text-align: center;
        }
    }
`;

// Add modal styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = modalStyles;
document.head.appendChild(styleSheet);