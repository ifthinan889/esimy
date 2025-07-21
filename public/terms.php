<?php
// Define allowed access for includes
define('ALLOWED_ACCESS', true);

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - eSIM Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Files - Organized by component -->
    <link rel="stylesheet" href="assets/css/about.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/navigation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/terms.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/footer.css?v=<?= time() ?>">
    
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Terms & Conditions for eSIM Store - Legal terms, service policies, and user agreements">
</head>
<body>

<!-- Theme Toggle -->
<button class="theme-toggle-floating" id="themeToggle" aria-label="Toggle theme">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>

<!-- Navigation -->
<?php include dirname(__DIR__) . '/src/includes/navigation.php'; ?>

<main class="main-content">
    <section class="legal-section">
        <div class="legal-container">
            <div class="legal-header">
                <h1 class="legal-title">
                    <i class="fas fa-file-contract"></i>
                    <span class="gradient-text">Terms & Conditions</span>
                </h1>
                <p class="last-updated">Last updated: <?= date('F d, Y') ?></p>
                <p class="legal-subtitle">Legal terms, service policies, and user agreements for eSIM Store services</p>
            </div>

            <div class="legal-content">
                <!-- Quick Summary -->
                <div class="legal-card highlight terms-summary">
                    <h2><i class="fas fa-info-circle"></i> Terms Summary</h2>
                    <p>By using eSIM Store, you agree to our service terms, usage policies, and refund conditions. We provide digital eSIM services with instant delivery and 24/7 support.</p>
                    <div class="summary-points">
                        <div class="summary-point">
                            <i class="fas fa-check-circle"></i>
                            <span>Instant digital delivery</span>
                        </div>
                        <div class="summary-point">
                            <i class="fas fa-shield-alt"></i>
                            <span>Fair usage policies</span>
                        </div>
                        <div class="summary-point">
                            <i class="fas fa-headset"></i>
                            <span>24/7 customer support</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-handshake"></i> Acceptance of Terms</h2>
                    <p>By accessing and using eSIM Store services, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                    
                    <div class="acceptance-notice">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <h4>Important Notice</h4>
                            <p>These terms constitute a legally binding agreement between you and eSIM Store.</p>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-sim-card"></i> Service Description</h2>
                    <h3>2.1 eSIM Services</h3>
                    <p>eSIM Store provides digital SIM card services that allow you to connect to cellular networks in various countries without the need for a physical SIM card. Our services include:</p>
                    
                    <div class="services-grid">
                        <div class="service-feature">
                            <i class="fas fa-globe"></i>
                            <h4>Global Coverage</h4>
                            <p>Local, regional, and global eSIM data plans</p>
                        </div>
                        <div class="service-feature">
                            <i class="fas fa-qrcode"></i>
                            <h4>Instant Delivery</h4>
                            <p>Digital delivery via QR codes</p>
                        </div>
                        <div class="service-feature">
                            <i class="fas fa-headset"></i>
                            <h4>Customer Support</h4>
                            <p>Activation and usage assistance</p>
                        </div>
                        <div class="service-feature">
                            <i class="fas fa-tachometer-alt"></i>
                            <h4>Account Management</h4>
                            <p>User-friendly dashboard</p>
                        </div>
                    </div>

                    <h3>2.2 Service Availability</h3>
                    <div class="availability-notice">
                        <i class="fas fa-info-circle"></i>
                        <p>Services are subject to coverage availability in your destination country and compatibility with your device. Not all devices support eSIM technology.</p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-mobile-alt"></i> Device Compatibility</h2>
                    <h3>3.1 eSIM Support</h3>
                    <p>Your device must support eSIM technology to use our services. You can check compatibility by dialing *#06# on your device - if an EID number appears, your device supports eSIM.</p>

                    <div class="compatibility-check">
                        <div class="check-steps">
                            <div class="check-step">
                                <div class="step-number">1</div>
                                <div class="step-text">
                                    <strong>Dial *#06#</strong>
                                    <p>Open your phone dialer</p>
                                </div>
                            </div>
                            <div class="check-step">
                                <div class="step-number">2</div>
                                <div class="step-text">
                                    <strong>Look for EID</strong>
                                    <p>Check if EID number appears</p>
                                </div>
                            </div>
                            <div class="check-step">
                                <div class="step-number">3</div>
                                <div class="step-text">
                                    <strong>eSIM Ready</strong>
                                    <p>Your device supports eSIM</p>
                                </div>
                            </div>
                        </div>
                        <button class="btn-compatibility" onclick="showCompatibilityGuide()">
                            <i class="fas fa-mobile-alt"></i> Check Compatibility
                        </button>
                    </div>

                    <h3>3.2 Customer Responsibility</h3>
                    <div class="responsibility-notice">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>It is your responsibility to verify device compatibility before purchase. We are not responsible for purchases made for incompatible devices.</p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-credit-card"></i> Purchase and Payment</h2>
                    <h3>4.1 Pricing</h3>
                    <p>All prices are displayed in Indonesian Rupiah (IDR) and US Dollars (USD). Prices include all applicable taxes and fees.</p>

                    <h3>4.2 Payment Methods</h3>
                    <div class="payment-methods-grid">
                        <div class="payment-method">
                            <i class="fas fa-credit-card"></i>
                            <span>Credit & Debit Cards</span>
                        </div>
                        <div class="payment-method">
                            <i class="fab fa-paypal"></i>
                            <span>PayPal</span>
                        </div>
                        <div class="payment-method">
                            <i class="fas fa-mobile-alt"></i>
                            <span>Digital Wallets</span>
                        </div>
                        <div class="payment-method">
                            <i class="fas fa-university"></i>
                            <span>Bank Transfer</span>
                        </div>
                    </div>

                    <h3>4.3 Payment Processing</h3>
                    <div class="processing-timeline">
                        <div class="timeline-item">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Payment must be completed before eSIM delivery</span>
                        </div>
                        <div class="timeline-item">
                            <i class="fas fa-clock"></i>
                            <span>Processing typically takes 2-5 minutes for successful payments</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-paper-plane"></i> eSIM Delivery and Activation</h2>
                    <h3>5.1 Digital Delivery</h3>
                    <p>eSIM profiles are delivered digitally via email containing QR codes and installation instructions. No physical shipping is involved.</p>

                    <h3>5.2 Activation Timeline</h3>
                    <div class="delivery-timeline">
                        <div class="delivery-option">
                            <i class="fas fa-bolt"></i>
                            <h4>Instant Delivery</h4>
                            <p>Most eSIMs delivered within 2-5 minutes</p>
                            <span class="timeline-badge instant">2-5 min</span>
                        </div>
                        <div class="delivery-option">
                            <i class="fas fa-clock"></i>
                            <h4>Delayed Delivery</h4>
                            <p>Some plans may require up to 24 hours</p>
                            <span class="timeline-badge delayed">Up to 24h</span>
                        </div>
                        <div class="delivery-option">
                            <i class="fas fa-user-cog"></i>
                            <h4>Manual Processing</h4>
                            <p>Complex orders may require manual review</p>
                            <span class="timeline-badge manual">Manual</span>
                        </div>
                    </div>

                    <h3>5.3 Installation Support</h3>
                    <div class="support-features">
                        <div class="support-item">
                            <i class="fas fa-book"></i>
                            <span>Detailed installation guides</span>
                        </div>
                        <div class="support-item">
                            <i class="fas fa-headset"></i>
                            <span>24/7 customer support</span>
                        </div>
                        <div class="support-item">
                            <i class="fas fa-video"></i>
                            <span>Video tutorials</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-shield-alt"></i> Usage Policies</h2>
                    <h3>6.1 Fair Use Policy</h3>
                    <p>Our services are intended for personal use. The following activities are prohibited:</p>
                    
                    <div class="prohibited-activities">
                        <div class="prohibited-item">
                            <i class="fas fa-ban"></i>
                            <span>Commercial resale or redistribution</span>
                        </div>
                        <div class="prohibited-item">
                            <i class="fas fa-ban"></i>
                            <span>Excessive usage that impacts network performance</span>
                        </div>
                        <div class="prohibited-item">
                            <i class="fas fa-ban"></i>
                            <span>Illegal activities or violation of local laws</span>
                        </div>
                        <div class="prohibited-item">
                            <i class="fas fa-ban"></i>
                            <span>Sharing eSIM credentials with multiple users</span>
                        </div>
                    </div>

                    <h3>6.2 Data Limits</h3>
                    <div class="data-policy">
                        <i class="fas fa-database"></i>
                        <p>Data usage is subject to the plan limits you purchase. Unlimited plans may have fair usage policies or speed throttling after certain thresholds.</p>
                    </div>

                    <h3>6.3 Network Priority</h3>
                    <div class="priority-notice">
                        <i class="fas fa-signal"></i>
                        <p>During network congestion, your traffic may be prioritized lower than direct customers of the local operator.</p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-undo-alt"></i> Refund and Cancellation Policy</h2>
                    <h3>7.1 Eligibility for Refunds</h3>
                    <p>Refunds may be provided in the following circumstances:</p>
                    
                    <div class="refund-eligible">
                        <div class="refund-item eligible">
                            <i class="fas fa-check-circle"></i>
                            <span>Technical issues preventing eSIM activation within 24 hours</span>
                        </div>
                        <div class="refund-item eligible">
                            <i class="fas fa-check-circle"></i>
                            <span>Service not available in purchased destination</span>
                        </div>
                        <div class="refund-item eligible">
                            <i class="fas fa-check-circle"></i>
                            <span>Proven device incompatibility (with valid documentation)</span>
                        </div>
                        <div class="refund-item eligible">
                            <i class="fas fa-check-circle"></i>
                            <span>Our error in order processing</span>
                        </div>
                    </div>

                    <h3>7.2 Non-Refundable Situations</h3>
                    <div class="refund-not-eligible">
                        <div class="refund-item not-eligible">
                            <i class="fas fa-times-circle"></i>
                            <span>Successfully activated eSIMs with partial or full data usage</span>
                        </div>
                        <div class="refund-item not-eligible">
                            <i class="fas fa-times-circle"></i>
                            <span>Customer error in destination selection</span>
                        </div>
                        <div class="refund-item not-eligible">
                            <i class="fas fa-times-circle"></i>
                            <span>Network coverage issues beyond our control</span>
                        </div>
                        <div class="refund-item not-eligible">
                            <i class="fas fa-times-circle"></i>
                            <span>Requests made after 30 days from purchase</span>
                        </div>
                    </div>

                    <h3>7.3 Refund Process</h3>
                    <div class="refund-process">
                        <i class="fas fa-clock"></i>
                        <p>Approved refunds will be processed within 5-10 business days to the original payment method.</p>
                        <a href="refund.php" class="refund-link">
                            <i class="fas fa-arrow-right"></i> View Full Refund Policy
                        </a>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-exclamation-triangle"></i> Limitation of Liability</h2>
                    <h3>8.1 Service Limitations</h3>
                    <p>We provide eSIM services "as is" and cannot guarantee:</p>
                    
                    <div class="limitations-grid">
                        <div class="limitation-item">
                            <i class="fas fa-wifi"></i>
                            <h4>Service Availability</h4>
                            <p>Uninterrupted service availability</p>
                        </div>
                        <div class="limitation-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <h4>Connection Speeds</h4>
                            <p>Specific connection speeds</p>
                        </div>
                        <div class="limitation-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <h4>Coverage Areas</h4>
                            <p>Coverage in all areas of a country</p>
                        </div>
                        <div class="limitation-item">
                            <i class="fas fa-mobile-alt"></i>
                            <h4>Device Compatibility</h4>
                            <p>Compatibility with all devices</p>
                        </div>
                    </div>

                    <h3>8.2 Liability Cap</h3>
                    <div class="liability-notice">
                        <i class="fas fa-calculator"></i>
                        <p>Our total liability for any claim shall not exceed the amount you paid for the specific service giving rise to the claim.</p>
                    </div>

                    <h3>8.3 Third-Party Networks</h3>
                    <div class="third-party-notice">
                        <i class="fas fa-network-wired"></i>
                        <p>We are not responsible for issues related to third-party cellular networks, including coverage gaps, speed limitations, or service interruptions.</p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-user-shield"></i> Privacy and Data Protection</h2>
                    <div class="privacy-reference">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <p>Your privacy is important to us. Please review our <a href="privacy.php">Privacy Policy</a> to understand how we collect, use, and protect your personal information.</p>
                            <a href="privacy.php" class="privacy-link">
                                <i class="fas fa-arrow-right"></i> View Privacy Policy
                            </a>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-copyright"></i> Intellectual Property</h2>
                    <h3>10.1 Our Rights</h3>
                    <div class="ip-rights">
                        <i class="fas fa-shield-alt"></i>
                        <p>All content, features, and functionality of our service are owned by eSIM Store and are protected by copyright, trademark, and other intellectual property laws.</p>
                    </div>

                    <h3>10.2 Limited License</h3>
                    <div class="license-terms">
                        <i class="fas fa-key"></i>
                        <p>We grant you a limited, non-exclusive, non-transferable license to use our services for personal purposes only.</p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-balance-scale"></i> Governing Law and Disputes</h2>
                    <h3>11.1 Governing Law</h3>
                    <div class="governing-law">
                        <i class="fas fa-flag"></i>
                        <p>These terms are governed by the laws of Indonesia, without regard to conflict of law provisions.</p>
                    </div>

                    <h3>11.2 Dispute Resolution</h3>
                    <div class="dispute-steps">
                        <div class="dispute-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Direct Negotiation</h4>
                                <p>Contact our customer support team</p>
                            </div>
                        </div>
                        <div class="dispute-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Mediation</h4>
                                <p>Through a mutually agreed mediator</p>
                            </div>
                        </div>
                        <div class="dispute-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Arbitration</h4>
                                <p>In Jakarta, Indonesia (if necessary)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-edit"></i> Modifications to Terms</h2>
                    <p>We reserve the right to modify these terms at any time. We will notify users of material changes by:</p>
                    
                    <div class="modification-methods">
                        <div class="modification-method">
                            <i class="fas fa-globe"></i>
                            <span>Posting updated terms on our website</span>
                        </div>
                        <div class="modification-method">
                            <i class="fas fa-envelope"></i>
                            <span>Sending email notifications to registered users</span>
                        </div>
                        <div class="modification-method">
                            <i class="fas fa-bell"></i>
                            <span>Displaying notices during login or checkout</span>
                        </div>
                    </div>
                    
                    <div class="acceptance-notice">
                        <i class="fas fa-info-circle"></i>
                        <p>Continued use of our services after changes take effect constitutes acceptance of the new terms.</p>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-user-times"></i> Account Termination</h2>
                    <h3>13.1 Termination by User</h3>
                    <div class="termination-user">
                        <i class="fas fa-user-check"></i>
                        <p>You may stop using our services at any time. Contact us to request account deletion.</p>
                    </div>

                    <h3>13.2 Termination by Us</h3>
                    <div class="termination-reasons">
                        <div class="termination-reason">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Violation of these terms</span>
                        </div>
                        <div class="termination-reason">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Fraudulent activity</span>
                        </div>
                        <div class="termination-reason">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Misuse of our services</span>
                        </div>
                    </div>

                    <h3>13.3 Effect of Termination</h3>
                    <div class="termination-effects">
                        <i class="fas fa-stop-circle"></i>
                        <p>Upon termination, your right to use our services ceases immediately. Active eSIM services may continue until expiration.</p>
                    </div>
                </div>

                <div class="legal-card contact-card">
                    <h2><i class="fas fa-envelope"></i> Contact Information</h2>
                    <p>For questions about these Terms & Conditions, please contact us:</p>
                    
                    <div class="contact-methods">
                        <div class="contact-method">
                            <i class="fas fa-gavel"></i>
                            <div>
                                <strong>Legal Inquiries</strong>
                                <a href="mailto:legal@esimstore.com">legal@esimstore.com</a>
                                <small>For terms and legal questions</small>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-headset"></i>
                            <div>
                                <strong>Customer Support</strong>
                                <a href="mailto:support@esimstore.com">support@esimstore.com</a>
                                <small>For service-related questions</small>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="fab fa-whatsapp"></i>
                            <div>
                                <strong>WhatsApp Support</strong>
                                <a href="https://wa.me/6281325525646">+62 813-2552-5646</a>
                                <small>Available 24/7</small>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Business Hours</strong>
                                <span>24/7 Online Support</span>
                                <small>Always available for assistance</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-puzzle-piece"></i> Severability</h2>
                    <div class="severability-notice">
                        <i class="fas fa-balance-scale"></i>
                        <p>If any provision of these terms is found to be unenforceable or invalid, that provision will be limited or eliminated to the minimum extent necessary so that the remaining terms will remain in full force and effect.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include dirname(__DIR__) . '/src/includes/footer.php'; ?>

<!-- JavaScript Files - Organized by component -->
<script src="assets/js/terms.js?v=<?= time() ?>"></script>
<script src="assets/js/footer.js?v=<?= time() ?>"></script>
</body>
</html>