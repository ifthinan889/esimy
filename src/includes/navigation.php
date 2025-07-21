<?php
if (!defined('ALLOWED_ACCESS')) {
    die('Direct access not permitted');
}

// Get current page for active navigation - Fixed for MVC
$currentPage = basename($_SERVER['PHP_SELF']);
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$requestPath = trim($requestUri, '/');
?>

<nav class="main-navigation">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="index.php" class="brand-link">
                <span class="brand-icon">✨</span>
                <span>eSIM Store</span>
            </a>
        </div>
        
        <div class="nav-menu">
            <a href="index.php" class="nav-link <?= ($currentPage == 'index.php' || $requestPath == '') ? 'active' : '' ?>">
                <i class="fas fa-sim-card"></i>
                <span>Browse eSIMs</span>
            </a>
            <a href="about.php" class="nav-link <?= ($currentPage == 'about.php' || $requestPath == 'about') ? 'active' : '' ?>">
                <i class="fas fa-info-circle"></i>
                <span>About</span>
            </a>
            <a href="contact.php" class="nav-link <?= ($currentPage == 'contact.php' || $requestPath == 'contact') ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i>
                <span>Contact</span>
            </a>
            <a href="https://wa.me/6281325525646" target="_blank" class="nav-link whatsapp-link">
                <i class="fab fa-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
        </div>
        
        <button class="nav-toggle" id="navToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</nav>