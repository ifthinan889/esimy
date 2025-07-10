// Terms Page JavaScript Functions

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

// Terms Support Functions
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
            <div class="guide-actions">
                <button class="btn-guide-action" onclick="openWhatsAppSupport()">
                    <i class="fab fa-whatsapp"></i> Get Help via WhatsApp
                </button>
                <button class="btn-guide-action" onclick="openEmailSupport()">
                    <i class="fas fa-envelope"></i> Email Support
                </button>
            </div>
        </div>
    `);
    showModal(modal);
}

function openWhatsAppSupport() {
    const message = encodeURIComponent('Hello! I have questions about eSIM compatibility and terms of service. Can you help me?');
    window.open(`https://wa.me/6281325525646?text=${message}`, '_blank');
}

function openEmailSupport() {
    const subject = encodeURIComponent('Terms & Conditions Inquiry');
    const body = encodeURIComponent(`Hello eSIM Store Support Team,

I have questions about your Terms & Conditions:

[Please describe your questions here]

Thank you for your assistance.

Best regards,
[Your Name]`);
    
    window.open(`mailto:support@esimstore.com?subject=${subject}&body=${body}`, '_blank');
}

function openLegalSupport() {
    const subject = encodeURIComponent('Legal Terms Inquiry');
    const body = encodeURIComponent(`Hello eSIM Store Legal Team,

I have questions regarding your Terms & Conditions:

[Please describe your legal inquiry here]

Thank you for your assistance.

Best regards,
[Your Name]`);
    
    window.open(`mailto:legal@esimstore.com?subject=${subject}&body=${body}`, '_blank');
}

// Terms Navigation Functions
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

function highlightSection(sectionElement) {
    // Remove previous highlights
    document.querySelectorAll('.legal-card.highlighted').forEach(card => {
        card.classList.remove('highlighted');
    });
    
    // Add highlight to current section
    sectionElement.classList.add('highlighted');
    
    // Remove highlight after 3 seconds
    setTimeout(() => {
        sectionElement.classList.remove('highlighted');
    }, 3000);
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

// Terms Acceptance Functions
function acceptTerms() {
    localStorage.setItem('terms-accepted', 'true');
    localStorage.setItem('terms-accepted-date', new Date().toISOString());
    
    showNotification('Terms & Conditions accepted successfully!', 'success');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
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
            
            // Highlight the target section
            if (target.classList.contains('legal-card')) {
                highlightSection(target);
            }
        }
    });
});

// Add scroll-based animations for cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.legal-card, .service-feature, .limitation-item');
    
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

// Add interactive effects to service features
document.addEventListener('DOMContentLoaded', function() {
    const serviceFeatures = document.querySelectorAll('.service-feature');
    
    serviceFeatures.forEach(feature => {
        feature.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        feature.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});

// Add click tracking for terms actions
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('.btn-compatibility, .btn-guide-action');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const actionType = this.textContent.trim();
            console.log('Terms action clicked:', actionType);
            // Here you can add analytics tracking code
        });
    });
});

// Terms Reading Progress
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.createElement('div');
    progressBar.className = 'reading-progress';
    progressBar.innerHTML = '<div class="progress-fill"></div>';
    document.body.appendChild(progressBar);
    
    const progressFill = progressBar.querySelector('.progress-fill');
    
    window.addEventListener('scroll', function() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight - windowHeight;
        const scrollTop = window.pageYOffset;
        const progress = (scrollTop / documentHeight) * 100;
        
        progressFill.style.width = Math.min(progress, 100) + '%';
    });
});

// Print functionality
function printTerms() {
    window.print();
}

// Share terms
function shareTerms() {
    if (navigator.share) {
        navigator.share({
            title: 'eSIM Store Terms & Conditions',
            text: 'Read the Terms & Conditions for eSIM Store services.',
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Terms & Conditions URL copied to clipboard!', 'success');
        }).catch(() => {
            alert('Terms & Conditions URL: ' + window.location.href);
        });
    }
}

// Performance optimization: Reduce motion for users who prefer it
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.style.setProperty('--transition-fast', '0.01ms');
    document.documentElement.style.setProperty('--transition-normal', '0.01ms');
}

// Error handling for missing elements
window.addEventListener('error', function(e) {
    console.log('Terms page script error:', e.error);
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
    
    // Quick navigation shortcuts
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'f':
                e.preventDefault();
                // Focus on search if available
                break;
            case 'p':
                e.preventDefault();
                printTerms();
                break;
        }
    }
});

// Add right-click context menu for terms actions
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
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="openWhatsAppSupport()">WhatsApp Support</div>
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="openEmailSupport()">Email Support</div>
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="openLegalSupport()">Legal Inquiry</div>
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

// Add CSS for modal styles and other dynamic elements
const termsStyles = `
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
    
    .legal-card.highlighted {
        border-color: var(--text-accent);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        transform: translateY(-2px);
    }
    
    .reading-progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: rgba(79, 70, 229, 0.1);
        z-index: 1000;
    }
    
    .progress-fill {
        height: 100%;
        background: var(--gradient-primary);
        width: 0%;
        transition: width 0.3s ease;
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
    }
    
    .notification.success {
        border-color: var(--success);
        background: rgba(5, 150, 105, 0.1);
    }
    
    .notification.success i {
        color: var(--success);
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
        }
    }
`;

// Add terms styles to document
const styleSheet = document.createElement('style');
styleSheet.textContent = termsStyles;
document.head.appendChild(styleSheet);