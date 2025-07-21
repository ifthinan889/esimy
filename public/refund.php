<?php
// Define allowed access for includes
define('ALLOWED_ACCESS', true);
require_once dirname(__DIR__) . '/config.php';
setSecurityHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Policy - eSIM Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Files - Organized by component -->
    <link rel="stylesheet" href="assets/css/about.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/navigation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/refund.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/footer.css?v=<?= time() ?>">
    
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Refund Policy for eSIM Store - Learn about our refund eligibility, process, and terms">
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
                    <i class="fas fa-undo-alt"></i>
                    <span class="gradient-text">Refund Policy</span>
                </h1>
                <p class="last-updated">Last updated: <?= date('F d, Y') ?></p>
                <p class="legal-subtitle">Clear guidelines for refunds, eligibility, and our commitment to customer satisfaction</p>
            </div>

            <div class="legal-content">
                <!-- Quick Summary -->
                <div class="legal-card highlight policy-summary">
                    <h2><i class="fas fa-info-circle"></i> Policy Summary</h2>
                    <p>We offer refunds for technical issues, service unavailability, and our errors. Once an eSIM is successfully activated and data is used, refunds are generally not available except in special circumstances.</p>
                    <div class="summary-points">
                        <div class="summary-point">
                            <i class="fas fa-check-circle"></i>
                            <span>24-hour technical support</span>
                        </div>
                        <div class="summary-point">
                            <i class="fas fa-shield-alt"></i>
                            <span>Fair refund process</span>
                        </div>
                        <div class="summary-point">
                            <i class="fas fa-clock"></i>
                            <span>5-10 day processing</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-check-circle"></i> Eligibility for Refunds</h2>
                    <h3>1.1 Full Refund Scenarios</h3>
                    <ul class="refund-list eligible">
                        <li><i class="fas fa-check-circle"></i> Technical issues preventing eSIM activation within 24 hours of purchase</li>
                        <li><i class="fas fa-check-circle"></i> Service not available in the purchased destination</li>
                        <li><i class="fas fa-check-circle"></i> Proven device incompatibility with valid documentation</li>
                        <li><i class="fas fa-check-circle"></i> Our error in order processing or delivery</li>
                        <li><i class="fas fa-check-circle"></i> Duplicate orders placed by mistake</li>
                    </ul>

                    <h3>1.2 Partial Refund Scenarios</h3>
                    <ul class="refund-list partial">
                        <li><i class="fas fa-exclamation-triangle"></i> Network coverage significantly different from advertised</li>
                        <li><i class="fas fa-exclamation-triangle"></i> Service disruption for more than 48 hours due to our technical issues</li>
                        <li><i class="fas fa-exclamation-triangle"></i> Unused portions of multi-day unlimited plans (case-by-case basis)</li>
                    </ul>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-times-circle"></i> Non-Refundable Situations</h2>
                    <ul class="refund-list not-eligible">
                        <li><i class="fas fa-times-circle"></i> Successfully activated eSIMs with any data usage</li>
                        <li><i class="fas fa-times-circle"></i> Customer error in destination selection</li>
                        <li><i class="fas fa-times-circle"></i> Network coverage issues in remote areas</li>
                        <li><i class="fas fa-times-circle"></i> Speed limitations due to network congestion</li>
                        <li><i class="fas fa-times-circle"></i> Change of travel plans after purchase</li>
                        <li><i class="fas fa-times-circle"></i> Requests made after 30 days from purchase date</li>
                        <li><i class="fas fa-times-circle"></i> Plans that have expired (past validity period)</li>
                    </ul>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-route"></i> Refund Process</h2>
                    <div class="process-timeline">
                        <div class="timeline-step">
                            <div class="step-icon">
                                <i class="fas fa-headset"></i>
                                <span class="step-number">1</span>
                            </div>
                            <div class="step-content">
                                <h4>Contact Support</h4>
                                <p>Reach out via WhatsApp (+62 813-2552-5646) or email (support@esimstore.com) with your order details and issue description.</p>
                                <div class="step-actions">
                                    <button class="btn-action" onclick="openWhatsAppRefund()">
                                        <i class="fab fa-whatsapp"></i> WhatsApp Support
                                    </button>
                                    <button class="btn-action" onclick="openEmailRefund()">
                                        <i class="fas fa-envelope"></i> Email Support
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-step">
                            <div class="step-icon">
                                <i class="fas fa-search"></i>
                                <span class="step-number">2</span>
                            </div>
                            <div class="step-content">
                                <h4>Investigation</h4>
                                <p>Our team will investigate your case within 24-48 hours. We may request additional information or screenshots.</p>
                                <div class="step-timeline">
                                    <span class="timeline-badge">24-48 hours</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-step">
                            <div class="step-icon">
                                <i class="fas fa-gavel"></i>
                                <span class="step-number">3</span>
                            </div>
                            <div class="step-content">
                                <h4>Decision</h4>
                                <p>You'll receive our decision via email. If approved, refund processing begins immediately.</p>
                                <div class="step-timeline">
                                    <span class="timeline-badge success">Email notification</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-step">
                            <div class="step-icon">
                                <i class="fas fa-credit-card"></i>
                                <span class="step-number">4</span>
                            </div>
                            <div class="step-content">
                                <h4>Processing</h4>
                                <p>Refunds are processed within 5-10 business days to your original payment method.</p>
                                <div class="step-timeline">
                                    <span class="timeline-badge">5-10 business days</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-clipboard-list"></i> Required Information for Refund Requests</h2>
                    <div class="required-info">
                        <h3>Please provide the following:</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <i class="fas fa-hashtag"></i>
                                <div>
                                    <strong>Order Number</strong>
                                    <p>Your eSIM order reference number</p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>Purchase Email</strong>
                                    <p>Email address used for the order</p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <strong>Issue Description</strong>
                                    <p>Detailed explanation of the problem</p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-mobile-alt"></i>
                                <div>
                                    <strong>Device Information</strong>
                                    <p>Phone model and OS version</p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-camera"></i>
                                <div>
                                    <strong>Screenshots</strong>
                                    <p>Error messages or relevant screen captures</p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <strong>Location</strong>
                                    <p>Where you attempted to use the eSIM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-credit-card"></i> Refund Methods and Timeframes</h2>
                    <div class="refund-methods">
                        <div class="method-item">
                            <div class="method-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="method-content">
                                <h4>Credit/Debit Cards</h4>
                                <p class="method-time">5-10 business days</p>
                                <small>Refunded to original card</small>
                            </div>
                        </div>
                        
                        <div class="method-item">
                            <div class="method-icon">
                                <i class="fab fa-paypal"></i>
                            </div>
                            <div class="method-content">
                                <h4>PayPal</h4>
                                <p class="method-time">3-5 business days</p>
                                <small>Instant to PayPal balance</small>
                            </div>
                        </div>
                        
                        <div class="method-item">
                            <div class="method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="method-content">
                                <h4>Bank Transfer</h4>
                                <p class="method-time">3-7 business days</p>
                                <small>For large amounts only</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-exclamation-triangle"></i> Special Circumstances</h2>
                    <h3>6.1 Force Majeure Events</h3>
                    <p>In cases of natural disasters, political unrest, or other force majeure events that affect network availability, we may offer:</p>
                    <div class="special-offers">
                        <div class="offer-item">
                            <i class="fas fa-undo"></i>
                            <span>Full refunds for unused services</span>
                        </div>
                        <div class="offer-item">
                            <i class="fas fa-gift"></i>
                            <span>Service credits for future use</span>
                        </div>
                        <div class="offer-item">
                            <i class="fas fa-clock"></i>
                            <span>Plan extensions at no additional cost</span>
                        </div>
                    </div>

                    <h3>6.2 Goodwill Gestures</h3>
                    <p>While not required, we may offer goodwill refunds or credits for:</p>
                    <div class="goodwill-reasons">
                        <div class="reason-item">
                            <i class="fas fa-heart"></i>
                            <span>Long-term customer loyalty</span>
                        </div>
                        <div class="reason-item">
                            <i class="fas fa-star"></i>
                            <span>Exceptional circumstances</span>
                        </div>
                        <div class="reason-item">
                            <i class="fas fa-thumbs-up"></i>
                            <span>Service improvements and customer satisfaction</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-balance-scale"></i> Dispute Resolution</h2>
                    <h3>7.1 Internal Process</h3>
                    <p>If you disagree with our refund decision:</p>
                    <div class="dispute-steps">
                        <div class="dispute-step">
                            <span class="step-num">1</span>
                            <span>Request escalation to our customer service manager</span>
                        </div>
                        <div class="dispute-step">
                            <span class="step-num">2</span>
                            <span>Provide additional evidence or documentation</span>
                        </div>
                        <div class="dispute-step">
                            <span class="step-num">3</span>
                            <span>Allow 48-72 hours for management review</span>
                        </div>
                    </div>

                    <h3>7.2 External Resolution</h3>
                    <p>For unresolved disputes, you may:</p>
                    <div class="external-options">
                        <div class="option-item">
                            <i class="fas fa-credit-card"></i>
                            <span>Contact your payment provider for chargeback protection</span>
                        </div>
                        <div class="option-item">
                            <i class="fas fa-handshake"></i>
                            <span>Seek mediation through consumer protection agencies</span>
                        </div>
                        <div class="option-item">
                            <i class="fas fa-gavel"></i>
                            <span>Pursue legal action in accordance with our Terms & Conditions</span>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-lightbulb"></i> Prevention Tips</h2>
                    <div class="prevention-tips">
                        <h3>To avoid issues requiring refunds:</h3>
                        <div class="tips-grid">
                            <div class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <h4>Check Compatibility</h4>
                                <p>Dial *#06# to verify your device supports eSIM before purchasing</p>
                                <button class="tip-action" onclick="showCompatibilityGuide()">
                                    <i class="fas fa-info-circle"></i> Learn More
                                </button>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <h4>Verify Coverage</h4>
                                <p>Check our coverage maps and read plan descriptions carefully</p>
                                <button class="tip-action" onclick="showCoverageMap()">
                                    <i class="fas fa-map"></i> View Coverage
                                </button>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h4>Plan Timing</h4>
                                <p>Purchase eSIMs close to your travel date for best experience</p>
                                <button class="tip-action" onclick="showTimingTips()">
                                    <i class="fas fa-clock"></i> Timing Guide
                                </button>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon">
                                    <i class="fas fa-question-circle"></i>
                                </div>
                                <h4>Ask Questions</h4>
                                <p>Contact support before purchasing if you have any doubts</p>
                                <button class="tip-action" onclick="openPreSalesSupport()">
                                    <i class="fas fa-headset"></i> Contact Support
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card contact-card">
                    <h2><i class="fas fa-envelope"></i> Contact Information for Refunds</h2>
                    <div class="refund-contact">
                        <div class="contact-priority">
                            <h3>Priority Support (Fastest Response)</h3>
                            <div class="contact-method priority">
                                <i class="fab fa-whatsapp"></i>
                                <div>
                                    <strong>WhatsApp Support</strong>
                                    <a href="https://wa.me/6281325525646">+62 813-2552-5646</a>
                                    <small>Available 24/7 - Typical response: Under 1 hour</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-standard">
                            <h3>Standard Support</h3>
                            <div class="contact-method">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>Email Support</strong>
                                    <a href="mailto:refunds@esimstore.com">refunds@esimstore.com</a>
                                    <small>Response within 24 hours</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="refund-email-template">
                            <h4>Email Template for Refund Requests:</h4>
                            <div class="email-template">
                                <div class="template-header">
                                    <strong>Subject:</strong> Refund Request - Order #[YOUR_ORDER_NUMBER]
                                </div>
                                <div class="template-body">
                                    <strong>Body:</strong><br>
                                    - Order Number: [ORDER_NUMBER]<br>
                                    - Purchase Email: [EMAIL]<br>
                                    - Issue Description: [DETAILED_ISSUE]<br>
                                    - Device Model: [DEVICE_INFO]<br>
                                    - Location Attempted: [LOCATION]<br>
                                    - Attachments: [SCREENSHOTS_IF_ANY]
                                </div>
                                <button class="copy-template-btn" onclick="copyEmailTemplate()">
                                    <i class="fas fa-copy"></i> Copy Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="legal-card">
                    <h2><i class="fas fa-sync-alt"></i> Policy Updates</h2>
                    <p>This refund policy may be updated periodically to reflect changes in our services or legal requirements. Significant changes will be communicated via:</p>
                    <div class="notification-methods">
                        <div class="notification-method">
                            <i class="fas fa-envelope"></i>
                            <span>Email notifications to registered customers</span>
                        </div>
                        <div class="notification-method">
                            <i class="fas fa-globe"></i>
                            <span>Website announcements and notices</span>
                        </div>
                        <div class="notification-method">
                            <i class="fas fa-share-alt"></i>
                            <span>Social media updates</span>
                        </div>
                    </div>
                    <p class="policy-acceptance">The current policy applies to all orders placed after the "last updated" date shown at the top of this page.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include dirname(__DIR__) . '/src/includes/footer.php'; ?>

<!-- JavaScript Files - Organized by component -->
<script src="assets/js/refund.js?v=<?= time() ?>"></script>
<script src="assets/js/footer.js?v=<?= time() ?>"></script>
</body>
</html>