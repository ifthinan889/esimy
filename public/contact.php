<?php
// Define allowed access for includes
define('ALLOWED_ACCESS', true);
require_once dirname(__DIR__) . '/config.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle contact form submission
$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errorMessage = "Invalid session. Please try again.";
    } else {
        // Validate form data
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $inquiryType = $_POST['inquiryType'] ?? '';
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($subject) || empty($message)) {
            $errorMessage = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
        } else {
            // Here you would typically send the email or save to database
            // For now, we'll just show a success message
            $successMessage = "Thank you for your message! We'll get back to you within 24 hours.";
            
            // Log the contact form submission
            error_log("Contact form submission: $firstName $lastName ($email) - $subject");
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - eSIM Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Files - Organized by component -->
    <link rel="stylesheet" href="/public/assets/css/about.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/public/assets/css/navigation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/public/assets/css/contact.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/public/assets/css/footer.css?v=<?= time() ?>">
    
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Contact eSIM Store - Get 24/7 support via WhatsApp, email, or our contact form. We're here to help with all your eSIM needs.">
</head>
<body>

<!-- Theme Toggle -->
<button class="theme-toggle-floating" id="themeToggle" aria-label="Toggle theme">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>

<!-- Navigation -->
<?php include dirname(__DIR__) . '/src/includes/navigation.php'; ?>

<main class="main-content">
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="hero-container">
            <h1 class="hero-title">
                <span class="gradient-text">Get in Touch</span>
                <span class="hero-subtitle">We're Here to Help 24/7</span>
            </h1>
            <p class="hero-description">
                Need assistance with your eSIM? Have questions about our services? 
                Our support team is available around the clock to help you stay connected.
            </p>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Support Available</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">&lt;1h</div>
                    <div class="stat-label">Average Response</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Customer Satisfaction</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Methods Section -->
    <section class="contact-methods-section">
        <div class="contact-methods-container">
            <div class="section-header">
                <h2 class="section-title">Choose Your Preferred Contact Method</h2>
                <p class="section-subtitle">Multiple ways to reach us - pick what works best for you</p>
            </div>
            
            <div class="contact-methods-grid">
                <!-- WhatsApp Support -->
                <div class="contact-method-card priority">
                    <div class="method-badge">Most Popular</div>
                    <div class="contact-method-icon whatsapp">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3>WhatsApp Support</h3>
                    <p class="method-description">Get instant help via WhatsApp. Perfect for quick questions and real-time assistance.</p>
                    <div class="contact-details">
                        <strong>+62 813-2552-5646</strong>
                        <span class="availability">Available 24/7</span>
                        <span class="response-time">Typical response: Under 1 hour</span>
                    </div>
                    <div class="method-features">
                        <div class="feature-item">
                            <i class="fas fa-bolt"></i>
                            <span>Instant messaging</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-image"></i>
                            <span>Send screenshots</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-phone"></i>
                            <span>Voice messages</span>
                        </div>
                    </div>
                    <button class="contact-btn primary" onclick="openWhatsAppSupport()">
                        <i class="fab fa-whatsapp"></i>
                        <span>Start WhatsApp Chat</span>
                    </button>
                </div>

                <!-- Email Support -->
                <div class="contact-method-card">
                    <div class="contact-method-icon email">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Support</h3>
                    <p class="method-description">Send detailed inquiries and get comprehensive responses from our support team.</p>
                    <div class="contact-details">
                        <strong>support@esimstore.com</strong>
                        <span class="availability">Monitored 24/7</span>
                        <span class="response-time">Response within 24 hours</span>
                    </div>
                    <div class="method-features">
                        <div class="feature-item">
                            <i class="fas fa-file-alt"></i>
                            <span>Detailed responses</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-paperclip"></i>
                            <span>File attachments</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-history"></i>
                            <span>Email history</span>
                        </div>
                    </div>
                    <button class="contact-btn" onclick="openEmailSupport()">
                        <i class="fas fa-envelope"></i>
                        <span>Send Email</span>
                    </button>
                </div>

                <!-- Live Chat -->
                <div class="contact-method-card">
                    <div class="contact-method-icon chat">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Live Chat</h3>
                    <p class="method-description">Chat directly on our website with our support agents for immediate assistance.</p>
                    <div class="contact-details">
                        <strong>Website Chat</strong>
                        <span class="availability">Available 16 hours/day</span>
                        <span class="response-time">Instant connection</span>
                    </div>
                    <div class="method-features">
                        <div class="feature-item">
                            <i class="fas fa-user-tie"></i>
                            <span>Live agents</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-share-alt"></i>
                            <span>Screen sharing</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-download"></i>
                            <span>Chat transcript</span>
                        </div>
                    </div>
                    <button class="contact-btn" onclick="openLiveChat()">
                        <i class="fas fa-comments"></i>
                        <span>Start Live Chat</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section">
        <div class="contact-form-container">
            <div class="form-header">
                <h2 class="form-title">Send Us a Message</h2>
                <p class="form-subtitle">Fill out the form below and we'll get back to you as soon as possible</p>
            </div>
            
            <!-- Messages -->
            <?php if ($successMessage): ?>
            <div class="form-message success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
            <div class="form-message error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>
            
            <div class="contact-form-wrapper">
                <form class="contact-form" id="contactForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="send_message">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName" class="form-label">
                                <i class="fas fa-user"></i>
                                First Name *
                            </label>
                            <input type="text" id="firstName" name="firstName" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName" class="form-label">
                                <i class="fas fa-user"></i>
                                Last Name *
                            </label>
                            <input type="text" id="lastName" name="lastName" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i>
                                Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-input">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="orderNumber" class="form-label">
                            <i class="fas fa-hashtag"></i>
                            Order Number (if applicable)
                        </label>
                        <input type="text" id="orderNumber" name="orderNumber" class="form-input" placeholder="e.g., ESM-2024-001234">
                    </div>
                    
                    <div class="form-group">
                        <label for="inquiryType" class="form-label">
                            <i class="fas fa-list"></i>
                            Inquiry Type *
                        </label>
                        <select id="inquiryType" name="inquiryType" class="form-select" required>
                            <option value="">Select inquiry type</option>
                            <option value="technical">Technical Support</option>
                            <option value="billing">Billing & Payment</option>
                            <option value="refund">Refund Request</option>
                            <option value="compatibility">Device Compatibility</option>
                            <option value="coverage">Coverage Questions</option>
                            <option value="general">General Inquiry</option>
                            <option value="partnership">Business Partnership</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject" class="form-label">
                            <i class="fas fa-tag"></i>
                            Subject *
                        </label>
                        <input type="text" id="subject" name="subject" class="form-input" required placeholder="Brief description of your inquiry">
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">
                            <i class="fas fa-comment-alt"></i>
                            Message *
                        </label>
                        <textarea id="message" name="message" class="form-textarea" rows="6" required placeholder="Please provide detailed information about your inquiry..."></textarea>
                        <div class="character-count">
                            <span id="charCount">0</span>/1000 characters
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deviceInfo" class="form-label">
                            <i class="fas fa-mobile-alt"></i>
                            Device Information (for technical issues)
                        </label>
                        <input type="text" id="deviceInfo" name="deviceInfo" class="form-input" placeholder="e.g., iPhone 14 Pro, iOS 17.1">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="newsletter" name="newsletter" class="form-checkbox">
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">Subscribe to our newsletter for eSIM tips and special offers</span>
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="privacy" name="privacy" class="form-checkbox" required>
                            <span class="checkbox-custom"></span>
                            <span class="checkbox-text">I agree to the <a href="privacy.php" target="_blank">Privacy Policy</a> and <a href="terms.php" target="_blank">Terms & Conditions</a> *</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="form-submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Message</span>
                        <div class="btn-loader" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </form>
                
                <div class="form-sidebar">
                    <div class="sidebar-card">
                        <h4><i class="fas fa-clock"></i> Response Times</h4>
                        <div class="response-times">
                            <div class="response-item">
                                <span class="method">WhatsApp</span>
                                <span class="time">Under 1 hour</span>
                            </div>
                            <div class="response-item">
                                <span class="method">Email</span>
                                <span class="time">Within 24 hours</span>
                            </div>
                            <div class="response-item">
                                <span class="method">Contact Form</span>
                                <span class="time">Within 48 hours</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-card">
                        <h4><i class="fas fa-question-circle"></i> Quick Help</h4>
                        <div class="quick-help">
                            <a href="#" onclick="showCompatibilityCheck()" class="help-link">
                                <i class="fas fa-mobile-alt"></i>
                                Check Device Compatibility
                            </a>
                            <a href="#" onclick="showInstallationGuide()" class="help-link">
                                <i class="fas fa-download"></i>
                                eSIM Installation Guide
                            </a>
                            <a href="#" onclick="showTroubleshooting()" class="help-link">
                                <i class="fas fa-tools"></i>
                                Troubleshooting Tips
                            </a>
                            <a href="refund.php" class="help-link">
                                <i class="fas fa-undo-alt"></i>
                                Refund Policy
                            </a>
                        </div>
                    </div>
                    
                    <div class="sidebar-card">
                        <h4><i class="fas fa-shield-alt"></i> Security Notice</h4>
                        <p>We never ask for passwords or sensitive payment information via email or chat. Always verify our official contact details.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="faq-container">
            <div class="faq-header">
                <h2 class="faq-title">Frequently Asked Questions</h2>
                <p class="faq-subtitle">Find quick answers to common questions</p>
            </div>
            
            <div class="faq-grid">
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h4>How quickly will I receive my eSIM?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Most eSIMs are delivered within 2-5 minutes after successful payment. Some plans may require up to 24 hours for processing. You'll receive an email with your QR code and installation instructions.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h4>What if my device doesn't support eSIM?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You can check eSIM compatibility by dialing *#06# on your device. If you see an EID number, your device supports eSIM. If not, we offer full refunds for incompatible devices with valid documentation.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h4>Can I get a refund if the service doesn't work?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! We offer refunds for technical issues preventing activation within 24 hours, service unavailability in your destination, or proven device incompatibility. See our refund policy for full details.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h4>How do I install my eSIM?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Installation is simple: 1) Scan the QR code we send you, 2) Go to Settings > Cellular > Add Cellular Plan, 3) Follow the setup instructions. We provide detailed guides for all device types.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h4>What countries do you support?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We support 190+ countries and territories worldwide. Our coverage includes local, regional, and global plans. Check our coverage map or contact support for specific destination availability.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h4>Is my payment information secure?</h4>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Absolutely! We use bank-level encryption and certified payment processors. We never store your payment details on our servers. All transactions are processed securely through PCI DSS compliant systems.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Emergency Contact Section -->
    <section class="emergency-contact-section">
        <div class="emergency-container">
            <div class="emergency-card">
                <div class="emergency-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="emergency-content">
                    <h3>Need Urgent Help?</h3>
                    <p>If you're traveling and experiencing connectivity issues, contact us immediately via WhatsApp for priority support.</p>
                    <button class="emergency-btn" onclick="openEmergencyWhatsApp()">
                        <i class="fab fa-whatsapp"></i>
                        Emergency WhatsApp Support
                    </button>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include dirname(__DIR__) . '/src/includes/footer.php'; ?>

<!-- JavaScript Files - Organized by component -->
<script src="/public/assets/js/contact.js?v=<?= time() ?>"></script>
<script src="/public/assets/js/footer.js?v=<?= time() ?>"></script>

<script>
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
    alert('Live chat is currently redirecting to WhatsApp for immediate assistance.');
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

// Theme Toggle
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

// Character count for message textarea
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
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
});

// Quick help functions
function showCompatibilityCheck() {
    alert('To check eSIM compatibility:\n\n1. Dial *#06# on your device\n2. If you see an EID number, your device supports eSIM\n3. Contact us if you need help!');
}

function showInstallationGuide() {
    alert('eSIM Installation Steps:\n\n1. Receive QR code via email\n2. Go to Settings > Cellular > Add Cellular Plan\n3. Scan the QR code\n4. Follow setup instructions\n\nNeed help? Contact our 24/7 support!');
}

function showTroubleshooting() {
    alert('Common troubleshooting steps:\n\n1. Restart your device\n2. Check network settings\n3. Ensure eSIM is activated\n4. Contact support if issues persist\n\nFor detailed help, use WhatsApp support!');
}
</script>
</body>
</html>