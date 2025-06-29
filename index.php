<?php
// Define allowed access for includes
define('ALLOWED_ACCESS', true);
require_once __DIR__ . '/config.php';
setSecurityHeaders();
// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
try {
    include 'includes/koneksi.php';
    include 'includes/functions.php';
    include 'includes/api.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Get current exchange rate
try {
    $kurs = getCurrentExchangeRate();
} catch (Exception $e) {
    error_log("Error getting exchange rate: " . $e->getMessage());
    $kurs = 17500; // fallback default
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Handle AJAX requests first - BEFORE any HTML output
if (isset($_GET['action'])) {
    // Prevent any HTML output before JSON
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'get_countries':
                handleGetCountries($pdo, $kurs);
                break;
            case 'get_packages_by_region':
                handleGetPackagesByRegion($pdo, $kurs);
                break;
            case 'get_packages_by_country':
                handleGetPackagesByCountry($pdo, $kurs);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        error_log("AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
    }
    exit;
}

function handleGetCountries($pdo, $kurs) {
    $countries = [];
    $regions = [];
    $globals = [];
    
    // Get unique package names by type
    $stmt = $pdo->prepare("
        SELECT 
            name,
            location_name, 
            location_code,
            type,
            COUNT(*) as package_count
        FROM packages 
        WHERE is_active = 1 
        GROUP BY name, type
        ORDER BY 
            CASE 
                WHEN type = 'LOCAL' THEN 1 
                WHEN type = 'REGIONAL' THEN 2 
                WHEN type = 'GLOBAL' THEN 3 
                ELSE 4 
            END,
            name ASC
    ");
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        $packageName = $row['name'];
        $locationName = $row['location_name'];
        $type = $row['type'];
        
        if ($type === 'LOCAL') {
            // Parse country names for LOCAL type
            $parsedCountries = parseCountriesFromLocation($locationName);
            
            foreach ($parsedCountries as $country) {
                $countryKey = strtolower(trim($country));
                if (!isset($countries[$countryKey])) {
                    $countries[$countryKey] = [
                        'name' => trim($country),
                        'type' => $type,
                        'package_count' => 0
                    ];
                }
                $countries[$countryKey]['package_count'] += (int)$row['package_count'];
            }
            
        } else if ($type === 'REGIONAL') {
            // Extract region prefix yang lebih smart
            $regionPrefix = extractRegionPrefix($packageName);
            $regionKey = strtolower(trim($regionPrefix));
            
            if (!isset($regions[$regionKey])) {
                $regions[$regionKey] = [
                    'name' => $regionPrefix,
                    'type' => $type,
                    'package_count' => 0
                ];
            }
            
            $regions[$regionKey]['package_count'] += (int)$row['package_count'];
            
        } else if ($type === 'GLOBAL') {
            // Extract global prefix
            $globalPrefix = extractGlobalPrefix($packageName);
            $globalKey = strtolower(trim($globalPrefix));
            
            if (!isset($globals[$globalKey])) {
                $globals[$globalKey] = [
                    'name' => $globalPrefix,
                    'type' => $type,
                    'package_count' => 0
                ];
            }
            
            $globals[$globalKey]['package_count'] += (int)$row['package_count'];
        }
    }
    
    // Convert to indexed arrays and sort
    $countriesList = array_values($countries);
    $regionsList = array_values($regions);
    $globalsList = array_values($globals);
    
    usort($countriesList, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    usort($regionsList, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    usort($globalsList, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true, 
        'countries' => $countriesList,
        'regions' => $regionsList,
        'globals' => $globalsList
    ]);
}

function handleGetPackagesByRegion($pdo, $kurs) {
    $regionName = isset($_GET['region']) ? htmlspecialchars(trim($_GET['region'])) : '';
    $type = $_GET['type'] ?? 'REGIONAL';
    
    if (empty($regionName)) {
        echo json_encode(['success' => false, 'message' => 'Region parameter required']);
        return;
    }
    
    $packages = [];
    
    // Ambil semua packages yang dimulai dengan region name
    $stmt = $pdo->prepare("
        SELECT * FROM packages 
        WHERE is_active = 1 
        AND type = ?
        AND LOWER(name) LIKE LOWER(?)
        ORDER BY price_usd ASC, volume ASC
    ");
    
    $searchTerm = $regionName . '%';
    $stmt->execute([$type, $searchTerm]);
    
    while ($row = $stmt->fetch()) {
        $row['price_idr'] = round((float)$row['price_usd'] * $kurs / 10000);
        $packages[] = $row;
    }
    
    echo json_encode(['success' => true, 'packages' => $packages]);
}

function handleGetPackagesByCountry($pdo, $kurs) {
    $country = isset($_GET['country']) ? htmlspecialchars(trim($_GET['country'])) : '';
    if (empty($country)) {
        echo json_encode(['success' => false, 'message' => 'Country parameter required']);
        return;
    }
    
    $packages = [];
    
    // Search packages berdasarkan location_name yang mengandung country name
    $stmt = $pdo->prepare("
        SELECT * FROM packages 
        WHERE is_active = 1 
        AND (
            LOWER(location_name) LIKE LOWER(?) 
            OR LOWER(location_code) LIKE LOWER(?)
        )
        ORDER BY 
            CASE 
                WHEN type = 'LOCAL' THEN 1 
                WHEN type = 'REGIONAL' THEN 2 
                WHEN type = 'GLOBAL' THEN 3 
                ELSE 4 
            END,
            price_usd ASC
    ");
    
    $searchTerm = '%' . $country . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    
    while ($row = $stmt->fetch()) {
        $row['price_idr'] = round((float)$row['price_usd'] * $kurs / 10000);
        $packages[] = $row;
    }
    
    echo json_encode(['success' => true, 'packages' => $packages]);
}

// Helper functions yang lebih smart
function extractRegionPrefix($packageName) {
    // Daftar region prefix yang umum berdasarkan data Anda
    $regionPrefixes = [
        'South America',
        'Caribbean',
        'Australia & New Zealand',
        'Asia-20',
        'Middle East',
        'Gulf Region',
        'Balkans',
        'Africa',
        'China (mainland HK Macao)',
        'Europe(30+ areas)',
        'Europe',
        'China mainland & Japan & South Korea',
        'Singapore & Malaysia & Thailand',
        'USA & Canada',
        'North America',
        'Asia (7 areas)',
        'Asia (12 areas)',
        'Central Asia'
    ];
    
    // Cari prefix yang paling cocok (yang paling panjang)
    $bestMatch = '';
    foreach ($regionPrefixes as $prefix) {
        if (stripos($packageName, $prefix) === 0 && strlen($prefix) > strlen($bestMatch)) {
            $bestMatch = $prefix;
        }
    }
    
    if ($bestMatch) {
        return $bestMatch;
    }
    
    // Fallback: ambil sampai angka pertama atau tanda kurung
    if (preg_match('/^([^0-9(]+)/', $packageName, $matches)) {
        return trim($matches[1]);
    }
    
    // Ultimate fallback
    $words = explode(' ', trim($packageName));
    return $words[0];
}

function extractGlobalPrefix($packageName) {
    $globalPrefixes = [
        'Global (120+ areas)',
        'Global139',
        'Global'
    ];
    
    // Cari prefix yang paling cocok
    $bestMatch = '';
    foreach ($globalPrefixes as $prefix) {
        if (stripos($packageName, $prefix) === 0 && strlen($prefix) > strlen($bestMatch)) {
            $bestMatch = $prefix;
        }
    }
    
    if ($bestMatch) {
        return $bestMatch;
    }
    
    // Fallback
    if (preg_match('/^([^0-9(]+)/', $packageName, $matches)) {
        return trim($matches[1]);
    }
    
    $words = explode(' ', trim($packageName));
    return $words[0];
}

function parseCountriesFromLocation($locationName) {
    if (empty($locationName)) return ['Unknown'];
    
    $countries = [];
    $separators = [',', ' + ', ' & ', '/', '|', ';', ' - ', ' and ', ' or '];
    
    $found = false;
    foreach ($separators as $separator) {
        if (strpos($locationName, $separator) !== false) {
            $countries = explode($separator, $locationName);
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $countries = [$locationName];
    }
    
    $countries = array_map('trim', $countries);
    $countries = array_filter($countries, function($country) {
        return !empty($country);
    });
    
    return empty($countries) ? [$locationName] : $countries;
}

// Only output HTML if no AJAX action was processed
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<!-- HEAD section tetap sama seperti sebelumnya -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>âœ¨ eSIM Store - Modern & Trendy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/store.css?v=<?= time() ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Modern eSIM store with trendy Gen Z design">
    <!-- Security headers -->
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    
    <!-- Pass exchange rate to JavaScript -->
    <script>
    window.exchangeRate = <?= $kurs ?>;
    console.log('Exchange rate loaded from DB:', window.exchangeRate);
    </script>
</head>
<body>

<!-- Floating Dark Mode Toggle -->
<button class="theme-toggle-floating" id="themeToggle">
    <span id="themeIcon">ðŸŒ™</span>
</button>

<!-- Main Content -->
<main class="main-content">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <span class="gradient-text">eSIM Store</span>
                <span class="hero-subtitle">Travel Anywhere, Stay Connected</span>
            </h1>
            <p class="hero-description">Find the perfect eSIM package for your travel needs. Instant activation, no physical SIM required.</p>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="search-container">
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" class="search-input" placeholder="Search countries (e.g., Indonesia, Singapore, Malaysia)">
                <button class="search-clear" id="searchClear" style="display: none;"><i class="fas fa-times"></i></button>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="filter-header">
            <h3 class="filter-title">
                <i class="fas fa-sliders-h"></i>
                Filters & Settings
            </h3>
            <button class="filter-toggle-btn" onclick="toggleFilters()">
                <span id="filterToggleIcon"><i class="fas fa-eye"></i></span>
                <span id="filterToggleText">Show Filters</span>
            </button>
        </div>
        
        <div class="filter-content" id="filterContent" style="display: none;">
            <!-- Package Type Filter -->
            <div class="filter-group">
                <h4 class="filter-group-title">
                    <i class="fas fa-box"></i>
                    Package Type
                </h4>
                <div class="filter-buttons">
                    <button class="filter-btn active" id="regularBtn" onclick="setPackageType('regular')">
                        <i class="fas fa-mobile-alt"></i>
                        <span class="filter-btn-text">Regular</span>
                        <span class="filter-btn-count" id="regularCount">0</span>
                        <span class="filter-btn-badge">TopUp</span>
                    </button>
                    <button class="filter-btn" id="unlimitedBtn" onclick="setPackageType('unlimited')">
                        <i class="fas fa-infinity"></i>
                        <span class="filter-btn-text">Unlimited</span>
                        <span class="filter-btn-count" id="unlimitedCount">0</span>
                        <span class="filter-btn-badge">Dayplans</span>
                    </button>
                </div>
            </div>

            <!-- TikTok Filter -->
            <div class="filter-group">
                <h4 class="filter-group-title">
                    <i class="fab fa-tiktok"></i>
                    TikTok Support
                </h4>
                <div class="filter-buttons">
                    <button class="filter-btn active" id="allTikTokBtn" onclick="setTikTokFilter('all')">
                        <i class="fas fa-mobile-alt"></i>
                        <span class="filter-btn-text">All</span>
                        <span class="filter-btn-count" id="allTikTokCount">0</span>
                    </button>
                    <button class="filter-btn" id="tiktokSupportedBtn" onclick="setTikTokFilter('supported')">
                        <i class="fas fa-check"></i>
                        <span class="filter-btn-text">Supported</span>
                        <span class="filter-btn-count" id="tiktokSupportedCount">0</span>
                    </button>
                    <button class="filter-btn" id="tiktokNotSupportedBtn" onclick="setTikTokFilter('not-supported')">
                        <i class="fas fa-times"></i>
                        <span class="filter-btn-text">Not Supported</span>
                        <span class="filter-btn-count" id="tiktokNotSupportedCount">0</span>
                    </button>
                </div>
            </div>

            <!-- Sort Options -->
            <div class="filter-group">
                <h4 class="filter-group-title">
                    <i class="fas fa-sort"></i>
                    Sort Results
                </h4>
                <div class="sort-controls">
                    <select id="sortOrder" class="sort-select">
                        <option value="relevance">ðŸŽ¯ Most Relevant</option>
                        <option value="volume-asc">ðŸ“Š Data: Small â†’ Large</option>
                        <option value="volume-desc">ðŸ“Š Data: Large â†’ Small</option>
                        <option value="price-asc">ðŸ’° Price: Low â†’ High</option>
                        <option value="price-desc">ðŸ’° Price: High â†’ Low</option>
                        <option value="name-asc">ðŸ”¤ Name: A â†’ Z</option>
                        <option value="name-desc">ðŸ”¤ Name: Z â†’ A</option>
                    </select>
                    <button class="reset-btn" onclick="resetAllFilters()">
                        <i class="fas fa-redo"></i>
                        Reset All
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Results Section -->
    <section class="results-section">
        <div id="packagesList" class="packages-grid" style="display: none;">
            <!-- Packages will be populated here by JavaScript -->
        </div>
        
        <div id="noResults" class="empty-state">
            <div class="empty-icon"><i class="fas fa-search"></i></div>
            <h3 class="empty-title">Find Your Perfect eSIM</h3>
            <p class="empty-description">Use the filters above to discover packages that match your needs</p>
            <p class="empty-hint">Start by selecting a location type and typing a country name</p>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section">
        <h2 class="section-title">Why Choose Our eSIM?</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <h3>Instant Activation</h3>
                <p>Get connected immediately with our quick and easy QR code activation process.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-globe"></i></div>
                <h3>Worldwide Coverage</h3>
                <p>Stay connected in over 190+ countries with our global network partners.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Secure Connection</h3>
                <p>Enjoy peace of mind with our secure and encrypted data connections.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-headset"></i></div>
                <h3>24/7 Support</h3>
                <p>Our customer support team is available around the clock to assist you.</p>
            </div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section class="contact-section">
        <h2 class="section-title">Need Help?</h2>
        
        <div class="contact-options">
            <a href="https://wa.me/6281325525646" class="contact-option">
                <i class="fab fa-whatsapp"></i>
                <span>WhatsApp Support</span>
            </a>
            
            <a href="mailto:support@esimstore.com" class="contact-option">
                <i class="fas fa-envelope"></i>
                <span>Email Us</span>
            </a>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-logo">âœ¨ eSIM Store</div>
        <div class="footer-links">
            <a href="#">Terms & Conditions</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Support</a>
        </div>
        <div class="footer-copyright">
            &copy; <?= date('Y') ?> eSIM Store. All rights reserved.
        </div>
    </div>
</footer>

<!-- Country Modal -->
<div id="countryModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('countryModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-globe"></i>
                Available Countries
            </h3>
            <button class="modal-close" onclick="closeModal('countryModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="country-search">
                <input type="text" id="countrySearchInput" class="country-search-input" placeholder="Search countries...">
            </div>
            <div id="countryList" class="country-list">
                <!-- Countries will be populated here -->
            </div>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div id="orderModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('orderModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="orderModalTitle" class="modal-title">
                <i class="fas fa-shopping-cart"></i>
                Order eSIM
            </h3>
            <button class="modal-close" onclick="closeModal('orderModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="orderForm" class="order-form" action="process_order.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="order_esim" id="orderAction">
            <input type="hidden" name="package_code" id="orderPackageCode">
            
            <div class="package-details" id="orderPackageDetails">
                <!-- Package details will be populated here -->
            </div>
            
            <div class="form-group">
                <label for="customerName" class="form-label">
                    <i class="fas fa-user"></i>
                    Customer Name
                </label>
                <input type="text" id="customerName" name="customer_name" class="form-input" required pattern="[A-Za-z0-9_\- ]{3,50}" title="Name must be 3-50 characters and can contain letters, numbers, spaces, hyphens and underscores">
            </div>
            
            <div class="form-group">
                <label for="customerPhone" class="form-label">
                    <i class="fas fa-phone"></i>
                    Phone Number (Optional)
                </label>
                <input type="tel" id="customerPhone" name="phone" class="form-input" pattern="[0-9\+\-\s]{10,15}" title="Enter a valid phone number">
            </div>
            
            <div class="form-group" id="countGroup">
                <label for="orderCount" class="form-label">
                    <i class="fas fa-list-ol"></i>
                    Quantity
                </label>
                <input type="number" id="orderCount" name="count" value="1" min="1" max="10" class="form-input">
                <div class="form-hint" id="countHint" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    Multiple orders will be named: name_1, name_2, etc.
                </div>
            </div>
            
            <div class="form-group" id="periodGroup" style="display: none;">
                <label for="periodNum" class="form-label">
                    <i class="fas fa-calendar-day"></i>
                    Number of Days
                </label>
                <input type="number" id="periodNum" name="period_num" value="1" min="1" max="30" class="form-input">
            </div>
            
            <button type="submit" class="submit-btn" id="orderSubmitBtn">
                <i class="fas fa-shopping-cart"></i>
                <span class="btn-text">Buy Now</span>
            </button>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('successModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-check-circle"></i>
                Order Successful!
            </h3>
            <button class="modal-close" onclick="closeModal('successModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="successMessage" class="success-message">
                <p>Your eSIM has been ordered successfully!</p>
            </div>
            
            <!-- For single link -->
            <div id="singleLinkContainer" class="link-container">
                <input type="text" id="tokenLink" class="link-input" readonly>
                <button onclick="copyTokenLink()" class="copy-btn">
                    <i class="fas fa-copy"></i>
                    Copy Link
                </button>
            </div>

            <!-- For multiple links -->
            <div id="multipleLinkContainer" class="multiple-links" style="display: none;">
                <div class="links-header">
                    <h4><i class="fas fa-link"></i> Order Links</h4>
                    <button onclick="copyAllLinks()" class="copy-all-btn">
                        <i class="fas fa-copy"></i>
                        Copy All Links
                    </button>
                </div>
                <div id="linksList" class="links-list">
                    <!-- Multiple links will be populated here -->
                </div>
            </div>
            
            <div id="provisioningNote" class="provisioning-note" style="display: none;">
                <div class="warning-card">
                    <div class="warning-icon"><i class="fas fa-clock"></i></div>
                    <div class="warning-content">
                        <h4>Processing in Progress</h4>
                        <p>Your eSIM is being processed. Refresh the detail page in a few minutes to see the latest status.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/store.js?v=<?= time() ?>"></script>
</body>
</html>