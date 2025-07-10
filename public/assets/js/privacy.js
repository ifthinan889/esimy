// Privacy Page JavaScript Functions

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

// Cookie Settings Modal
function showCookieSettings() {
    const cookiePreferences = {
        necessary: true, // Always required
        analytics: localStorage.getItem('cookie-analytics') === 'true',
        marketing: localStorage.getItem('cookie-marketing') === 'true',
        functional: localStorage.getItem('cookie-functional') === 'true'
    };
    
    const message = `Cookie Preferences:

✓ Necessary Cookies (Required)
  - Essential for website functionality
  - Cannot be disabled

${cookiePreferences.analytics ? '✓' : '✗'} Analytics Cookies
  - Help us understand how you use our site
  - Used to improve our services

${cookiePreferences.functional ? '✓' : '✗'} Functional Cookies
  - Remember your preferences
  - Enhance your experience

${cookiePreferences.marketing ? '✓' : '✗'} Marketing Cookies
  - Personalized content and ads
  - Track effectiveness of campaigns

Would you like to change your preferences?`;

    if (confirm(message)) {
        showDetailedCookieSettings();
    }
}

function showDetailedCookieSettings() {
    const analytics = confirm('Enable Analytics Cookies?\n\nThese help us understand how visitors interact with our website by collecting and reporting information anonymously.');
    const functional = confirm('Enable Functional Cookies?\n\nThese enable the website to provide enhanced functionality and personalization, such as remembering your preferences.');
    const marketing = confirm('Enable Marketing Cookies?\n\nThese are used to track visitors across websites to display relevant and engaging ads.');
    
    // Save preferences
    localStorage.setItem('cookie-analytics', analytics);
    localStorage.setItem('cookie-functional', functional);
    localStorage.setItem('cookie-marketing', marketing);
    
    alert('Cookie preferences saved successfully!\n\nYour choices will be applied on your next visit.');
}

// Privacy Rights Request
function requestDataAccess() {
    const email = prompt('Please enter your email address to request access to your personal data:');
    if (email && isValidEmail(email)) {
        alert(`Data access request submitted for: ${email}\n\nWe will respond within 30 days as required by applicable privacy laws.\n\nYou will receive a confirmation email shortly.`);
        // Here you would typically send this to your backend
        console.log('Data access request for:', email);
    } else if (email) {
        alert('Please enter a valid email address.');
    }
}

function requestDataDeletion() {
    const email = prompt('Please enter your email address to request deletion of your personal data:');
    if (email && isValidEmail(email)) {
        const confirm = window.confirm(`Are you sure you want to delete all personal data associated with ${email}?\n\nThis action cannot be undone and will:\n- Close your account\n- Delete all order history\n- Remove all personal information\n\nActive eSIM services may be affected.`);
        
        if (confirm) {
            alert(`Data deletion request submitted for: ${email}\n\nWe will process your request within 30 days.\n\nYou will receive a confirmation email with next steps.`);
            // Here you would typically send this to your backend
            console.log('Data deletion request for:', email);
        }
    } else if (email) {
        alert('Please enter a valid email address.');
    }
}

function requestDataPortability() {
    const email = prompt('Please enter your email address to request a copy of your personal data:');
    if (email && isValidEmail(email)) {
        alert(`Data portability request submitted for: ${email}\n\nWe will provide your data in a structured, commonly used format within 30 days.\n\nYou will receive download instructions via email.`);
        // Here you would typically send this to your backend
        console.log('Data portability request for:', email);
    } else if (email) {
        alert('Please enter a valid email address.');
    }
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
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
    const cards = document.querySelectorAll('.legal-card, .usage-item, .sharing-item, .right-item');
    
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

// Add interactive effects to contact methods
document.addEventListener('DOMContentLoaded', function() {
    const contactMethods = document.querySelectorAll('.contact-method');
    
    contactMethods.forEach(method => {
        method.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = 'var(--shadow-md)';
        });
        
        method.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
});

// Privacy contact shortcuts
function contactPrivacyTeam() {
    window.open('mailto:privacy@esimstore.com?subject=Privacy Inquiry&body=Hello eSIM Store Privacy Team,%0D%0A%0D%0AI have a question about your privacy practices:%0D%0A%0D%0A[Please describe your inquiry here]%0D%0A%0D%0AThank you!', '_blank');
}

function openWhatsAppSupport() {
    window.open('https://wa.me/6281325525646?text=Hello! I have a privacy-related question.', '_blank');
}

// Add keyboard navigation for accessibility
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Close any open modals or overlays
        const modals = document.querySelectorAll('.modal, .overlay');
        modals.forEach(modal => {
            if (modal.style.display === 'block' || modal.classList.contains('active')) {
                modal.style.display = 'none';
                modal.classList.remove('active');
            }
        });
    }
});

// Performance optimization: Reduce motion for users who prefer it
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.style.setProperty('--transition-fast', '0.01ms');
    document.documentElement.style.setProperty('--transition-normal', '0.01ms');
}

// Error handling for missing elements
window.addEventListener('error', function(e) {
    console.log('Privacy page script error:', e.error);
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
function printPrivacyPolicy() {
    window.print();
}

// Share privacy policy
function sharePrivacyPolicy() {
    if (navigator.share) {
        navigator.share({
            title: 'eSIM Store Privacy Policy',
            text: 'Learn how eSIM Store protects your privacy and personal data.',
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Privacy Policy URL copied to clipboard!');
        }).catch(() => {
            alert('Privacy Policy URL: ' + window.location.href);
        });
    }
}

// Add right-click context menu for privacy actions
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
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="requestDataAccess()">Request My Data</div>
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="requestDataDeletion()">Delete My Data</div>
            <div style="padding: var(--space-sm); cursor: pointer;" onclick="requestDataPortability()">Export My Data</div>
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