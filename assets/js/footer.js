// Footer JavaScript Functions
function showCompatibilityCheck() {
    alert('To check eSIM compatibility:\n\n1. Dial *#06# on your device\n2. If you see an EID number, your device supports eSIM\n3. Contact us if you need help!');
}

function showInstallationGuide() {
    alert('eSIM Installation Steps:\n\n1. Receive QR code via email\n2. Go to Settings > Cellular > Add Cellular Plan\n3. Scan the QR code\n4. Follow setup instructions\n\nNeed help? Contact our 24/7 support!');
}

function showDataRetentionPolicy() {
    alert('Data Retention Policy:\n\n• Order data: 7 years for business records\n• Personal data: Until account deletion\n• Usage logs: 12 months\n• Payment info: As required by law\n\nView full details in our Privacy Policy.');
}

// Footer interaction enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to footer links
    const footerLinks = document.querySelectorAll('.footer-links a, .social-link');
    
    footerLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add click tracking for footer links (for analytics)
    const trackableLinks = document.querySelectorAll('.footer-links a');
    
    trackableLinks.forEach(link => {
        link.addEventListener('click', function() {
            const linkText = this.textContent.trim();
            console.log('Footer link clicked:', linkText);
            // Here you can add analytics tracking code
        });
    });

    // Payment icons animation
    const paymentIcons = document.querySelectorAll('.payment-icons i');
    
    paymentIcons.forEach((icon, index) => {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.2) rotate(5deg)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
    });
});