<?php
if (!defined('ALLOWED_ACCESS')) {
    die('Direct access not permitted');
}
?>

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Company Info -->
            <div class="footer-section">
                <div class="footer-brand">
                    <span class="footer-logo">✨ eSIM Store</span>
                    <p class="footer-tagline">Your Global Connectivity Partner</p>
                </div>
                <p class="footer-description">
                    Providing reliable eSIM solutions for travelers worldwide. 
                    Stay connected anywhere with our instant digital SIM cards.
                </p>
                <div class="social-links">
                    <a href="https://wa.me/6281325525646" target="_blank" class="social-link">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:support@esimstore.com" class="social-link">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-section">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Browse eSIMs</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Support</a></li>
                    <li><a href="#" onclick="showCompatibilityCheck()">Device Compatibility</a></li>
                    <li><a href="#" onclick="showInstallationGuide()">Installation Guide</a></li>
                </ul>
            </div>
            
            <!-- Legal -->
            <div class="footer-section">
                <h4 class="footer-title">Legal</h4>
                <ul class="footer-links">
                    <li><a href="terms.php">Terms & Conditions</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="refund.php">Refund Policy</a></li>
                    <li><a href="#" onclick="showDataRetentionPolicy()">Data Retention</a></li>
                </ul>
            </div>
            
            <!-- Support -->
            <div class="footer-section">
                <h4 class="footer-title">Customer Support</h4>
                <div class="support-info">
                    <div class="support-item">
                        <i class="fab fa-whatsapp"></i>
                        <div>
                            <strong>WhatsApp</strong>
                            <span>+62 813-2552-5646</span>
                        </div>
                    </div>
                    <div class="support-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong>
                            <span>support@esimstore.com</span>
                        </div>
                    </div>
                    <div class="support-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Available</strong>
                            <span>24/7 Support</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p class="copyright">
                    &copy; <?= date('Y') ?> eSIM Store. All rights reserved.
                </p>
                <div class="payment-methods">
                    <span class="payment-label">We Accept:</span>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa" title="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fab fa-paypal" title="PayPal"></i>
                        <i class="fas fa-credit-card" title="All Major Cards"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
function showCompatibilityCheck() {
    alert('To check eSIM compatibility:\n\n1. Dial *#06# on your device\n2. If you see an EID number, your device supports eSIM\n3. Contact us if you need help!');
}

function showInstallationGuide() {
    alert('eSIM Installation Steps:\n\n1. Receive QR code via email\n2. Go to Settings > Cellular > Add Cellular Plan\n3. Scan the QR code\n4. Follow setup instructions\n\nNeed help? Contact our 24/7 support!');
}

function showDataRetentionPolicy() {
    alert('Data Retention Policy:\n\n• Order data: 7 years for business records\n• Personal data: Until account deletion\n• Usage logs: 12 months\n• Payment info: As required by law\n\nView full details in our Privacy Policy.');
}
</script>