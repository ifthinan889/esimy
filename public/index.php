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
    include 'includes/functions.php';        // Basic functions (existing)
    include 'includes/order_functions.php';  // NEW - Order related
    include 'includes/payment_functions.php'; // NEW - Payment related
    include 'includes/api.php';              // API functions (existing)
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

// ✅ TAMBAHKAN DI SINI - Handle POST form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'order_esim':
        case 'order_unlimited':
            $result = createMultipleEsimOrders($_POST);
            echo json_encode($result);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

function handleGetCountries($pdo, $kurs) {
    $countries = [];
    $regions = [];
    $globals = [];
    
    // Get unique package names by type - FIXED SQL
    $stmt = $pdo->prepare("
        SELECT 
            name,
            location_name, 
            location_code,
            type,
            COUNT(*) as package_count
        FROM packages 
        WHERE is_active = 1 
        GROUP BY name, location_name, location_code, type
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
        $locationCode = $row['location_code'];
        $type = $row['type'];
        
        if ($type === 'LOCAL') {
            // Parse country names for LOCAL type
            $parsedCountries = parseCountriesFromLocation($locationName);
            
            foreach ($parsedCountries as $country) {
                $countryKey = strtolower(trim($country));
                if (!isset($countries[$countryKey])) {
                    $countries[$countryKey] = [
                        'name' => trim($country),
                        'location_code' => $locationCode,
                        'type' => $type,
                        'package_count' => 0
                    ];
                }
                $countries[$countryKey]['package_count'] += (int)$row['package_count'];
            }
            
        } else if ($type === 'REGIONAL') {
            // ✅ DEBUG: Log setiap regional package
            if (stripos($packageName, 'australia') !== false) {
                error_log("FOUND AUSTRALIA PACKAGE: " . $packageName);
            }
            
            // Extract region prefix yang lebih smart
            $regionPrefix = extractRegionPrefix($packageName);
            $regionKey = strtolower(trim($regionPrefix));
            
            // ✅ DEBUG: Log extraction
            if (stripos($packageName, 'australia') !== false) {
                error_log("EXTRACTED PREFIX: '" . $regionPrefix . "' from '" . $packageName . "'");
                error_log("REGION KEY: '" . $regionKey . "'");
            }
            
            if (!isset($regions[$regionKey])) {
                $regions[$regionKey] = [
                    'name' => $regionPrefix,
                    'location_code' => $locationCode,
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
                    'location_code' => $locationCode,
                    'type' => $type,
                    'package_count' => 0
                ];
            }
            
            $globals[$globalKey]['package_count'] += (int)$row['package_count'];
        }
    }
    
    // ✅ DEBUG: Log final regions before output
    error_log("=== FINAL REGIONS DEBUG ===");
    foreach ($regions as $key => $region) {
        error_log("Region: '" . $region['name'] . "' | Count: " . $region['package_count']);
        if (stripos($region['name'], 'australia') !== false) {
            error_log("*** AUSTRALIA FOUND IN FINAL: " . $region['name']);
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
    $regionName = isset($_GET['region']) ? html_entity_decode(trim($_GET['region']), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    $type = $_GET['type'] ?? 'REGIONAL';
    
    if (empty($regionName)) {
        echo json_encode(['success' => false, 'message' => 'Region parameter required']);
        return;
    }
    
    $packages = [];
    
    // Mobile detection
    $isMobile = isset($_SERVER['HTTP_USER_AGENT']) && 
                preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT']);
    
    $limitClause = $isMobile ? 'LIMIT 50' : '';
    
    // Australia handling
    $australiaPatterns = [
        'australia & new zealand',
        'australia and new zealand',
        'australia + new zealand',
        'australia/new zealand',
        'australia new zealand'
    ];
    
    $isAustraliaNewZealand = false;
    foreach ($australiaPatterns as $pattern) {
        if (stripos($regionName, $pattern) !== false || stripos(strtolower($regionName), $pattern) !== false) {
            $isAustraliaNewZealand = true;
            break;
        }
    }
    
    // PENGURUTAN SANGAT SEDERHANA DAN TEGAS
    if ($isAustraliaNewZealand) {
        $stmt = $pdo->prepare("
            SELECT * FROM packages 
            WHERE is_active = 1 
            AND type = 'REGIONAL'
            AND (
                (LOWER(name) LIKE '%australia%' AND LOWER(name) LIKE '%new zealand%')
                OR (LOWER(location_name) LIKE '%australia%' AND LOWER(location_name) LIKE '%new zealand%')
                OR (
                    FIND_IN_SET('AU', REPLACE(UPPER(location_code), ' ', '')) > 0 
                    AND FIND_IN_SET('NZ', REPLACE(UPPER(location_code), ' ', '')) > 0
                )
            )
            AND NOT (
                (FIND_IN_SET('US', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('CA', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('UK', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('GB', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('DE', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('FR', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('IT', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('ES', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('JP', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('KR', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('CN', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('SG', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('MY', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('TH', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('ID', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('PH', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('VN', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR (FIND_IN_SET('IN', REPLACE(UPPER(location_code), ' ', '')) > 0)
                OR LOWER(name) LIKE '%europe%'
                OR LOWER(name) LIKE '%asia%'
                OR LOWER(name) LIKE '%america%'
                OR LOWER(name) LIKE '%global%'
                OR LOWER(name) LIKE '%worldwide%'
                OR LOWER(name) LIKE '%middle east%'
                OR LOWER(name) LIKE '%africa%'
                OR LOWER(name) LIKE '%caribbean%'
            )
            ORDER BY 
                -- 1. HK IP DULU (nilai 1), Non-HK IP KEDUA (nilai 2)
                CASE WHEN LOWER(TRIM(ip_export)) = 'hk' THEN 1 ELSE 2 END,
                -- 2. REGULAR DULU (nilai 1), DAYPLANS KEDUA (nilai 2)
                CASE WHEN (support_topup_type = 1 AND TRIM(COALESCE(fup_policy, '')) != '') THEN 2 ELSE 1 END,
                -- 3. Volume kecil dulu
                CAST(volume AS UNSIGNED) ASC,
                -- 4. Harga murah dulu
                CAST(price_usd AS DECIMAL(10,2)) ASC,
                -- 5. Nama A-Z
                name ASC
            $limitClause
        ");
        $stmt->execute();
        
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM packages 
            WHERE is_active = 1 
            AND type = ?
            AND (
                LOWER(name) LIKE LOWER(?) 
                OR LOWER(location_name) LIKE LOWER(?)
            )
            ORDER BY 
                -- 1. HK IP DULU (nilai 1), Non-HK IP KEDUA (nilai 2)
                CASE WHEN LOWER(TRIM(ip_export)) = 'hk' THEN 1 ELSE 2 END,
                -- 2. REGULAR DULU (nilai 1), DAYPLANS KEDUA (nilai 2)
                CASE WHEN (support_topup_type = 1 AND TRIM(COALESCE(fup_policy, '')) != '') THEN 2 ELSE 1 END,
                -- 3. Volume kecil dulu
                CAST(volume AS UNSIGNED) ASC,
                -- 4. Harga murah dulu
                CAST(price_usd AS DECIMAL(10,2)) ASC,
                -- 5. Nama A-Z
                name ASC
            $limitClause
        ");
        
        $searchTerm = '%' . $regionName . '%';
        $stmt->execute([$type, $searchTerm, $searchTerm]);
    }
    
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
    
    $isMobile = isset($_SERVER['HTTP_USER_AGENT']) && 
                preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT']);
    
    $limitClause = $isMobile ? 'LIMIT 50' : '';
    
    $stmt = $pdo->prepare("
        SELECT * FROM packages 
        WHERE is_active = 1 
        AND (
            LOWER(location_name) LIKE LOWER(?) 
            OR LOWER(location_code) LIKE LOWER(?)
        )
        ORDER BY 
            -- LOCAL packages dulu untuk countries
            CASE 
                WHEN type = 'LOCAL' THEN 0
                WHEN type = 'REGIONAL' THEN 1000 
                WHEN type = 'GLOBAL' THEN 2000
                ELSE 3000 
            END +
            -- OTOMATIS: HK IP → Non-HK IP → Regular → Dayplans
            CASE WHEN LOWER(ip_export) = 'hk' THEN 0 ELSE 100 END +
            CASE WHEN (support_topup_type = 1 AND TRIM(fup_policy) != '') THEN 10 ELSE 0 END +
            FLOOR(volume / 1000000) +
            FLOOR(price_usd * 1),
            name ASC
        $limitClause
    ");
    
    $searchTerm = '%' . $country . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    
    while ($row = $stmt->fetch()) {
        $row['price_idr'] = round((float)$row['price_usd'] * $kurs / 10000);
        $packages[] = $row;
    }
    
    echo json_encode(['success' => true, 'packages' => $packages]);
}

// Apply same optimization to handleGetPackagesByRegion
function extractRegionPrefix($packageName) {
    // ✅ DEBUG: Log input
    if (stripos($packageName, 'australia') !== false) {
        error_log("extractRegionPrefix input: '" . $packageName . "'");
    }
    
    // Daftar region prefix yang umum - URUTAN PENTING (yang paling spesifik dulu)
    $regionPrefixes = [
        'Australia & New Zealand',
        'Australia and New Zealand',
        'Australia + New Zealand',
        'Australia/New Zealand',
        'Australia New Zealand',
        'South America',
        'Caribbean',
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
    
    // Pattern matching untuk Australia & New Zealand dengan berbagai format
    $australiaPatterns = [
        '/^Australia\s*(&|and|\+|\/)\s*New\s*Zealand/i',
        '/^Australia\s*New\s*Zealand/i'
    ];
    
    foreach ($australiaPatterns as $pattern) {
        if (preg_match($pattern, $packageName)) {
            error_log("Australia pattern matched: " . $packageName);
            return 'Australia & New Zealand';
        }
    }
    
    // Cari prefix yang paling cocok (yang paling panjang)
    $bestMatch = '';
    foreach ($regionPrefixes as $prefix) {
        if (stripos($packageName, $prefix) === 0 && strlen($prefix) > strlen($bestMatch)) {
            $bestMatch = $prefix;
            
            // ✅ DEBUG: Log match
            if (stripos($packageName, 'australia') !== false) {
                error_log("MATCH FOUND: '" . $prefix . "' matches '" . $packageName . "'");
            }
        }
    }
    
    if ($bestMatch) {
        // ✅ DEBUG: Log result
        if (stripos($packageName, 'australia') !== false) {
            error_log("extractRegionPrefix result: '" . $bestMatch . "'");
        }
        return $bestMatch;
    }
    
    // Fallback: ambil sampai angka pertama atau tanda kurung
    if (preg_match('/^([^0-9(]+)/', $packageName, $matches)) {
        $result = trim($matches[1]);
        
        // ✅ DEBUG: Log fallback
        if (stripos($packageName, 'australia') !== false) {
            error_log("extractRegionPrefix fallback: '" . $result . "'");
        }
        
        return $result;
    }
    
    // Ultimate fallback
    $words = explode(' ', trim($packageName));
    $result = $words[0];
    
    // ✅ DEBUG: Log ultimate fallback
    if (stripos($packageName, 'australia') !== false) {
        error_log("extractRegionPrefix ultimate fallback: '" . $result . "'");
    }
    
    return $result;
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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSIM Store - Global Connectivity Solutions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Files - Organized by component -->
    <link rel="stylesheet" href="assets/css/about.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/navigation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/index.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/footer.css?v=<?= time() ?>">
    
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Find the perfect eSIM package for your travel needs. Instant activation, global coverage, 24/7 support.">
    
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

<!-- Theme Toggle -->
<button class="theme-toggle-floating" id="themeToggle" aria-label="Toggle theme">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>

<!-- Navigation -->
<?php include 'includes/navigation.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <span class="gradient-text">eSIM Store</span>
                    <span class="hero-subtitle">Travel Anywhere, Stay Connected</span>
                </h1>
                <p class="hero-description">Find the perfect eSIM package for your travel needs. Instant activation, no physical SIM required.</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">190+</div>
                        <div class="stat-label">Countries</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">2-5min</div>
                        <div class="stat-label">Delivery</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="search-container">
            <div class="search-header">
                <h2 class="search-title">
                    <i class="fas fa-search"></i>
                    Find Your Perfect eSIM
                </h2>
                <p class="search-subtitle">Search by country, region, or browse all available packages</p>
            </div>
            <div class="search-box">
                <span class="search-icon"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" class="search-input" placeholder="Search countries (e.g., Indonesia, Singapore, Malaysia)">
                <button class="search-clear" id="searchClear" style="display: none;"><i class="fas fa-times"></i></button>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="filter-container">
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
                        <button class="filter-btn active" id="allPackageBtn" onclick="setPackageType('all')">
                            <div class="filter-btn-content">
                                <i class="fas fa-th-large filter-btn-icon"></i>
                                <span class="filter-btn-text">All</span>
                            </div>
                            <span class="filter-btn-count" id="allPackageCount">0</span>
                        </button>
                        <button class="filter-btn" id="regularBtn" onclick="setPackageType('regular')">
                            <div class="filter-btn-content">
                                <i class="fas fa-mobile-alt filter-btn-icon"></i>
                                <span class="filter-btn-text">Regular</span>
                            </div>
                            <span class="filter-btn-count" id="regularCount">0</span>
                            <span class="filter-btn-badge">TopUp</span>
                        </button>
                        <button class="filter-btn" id="unlimitedBtn" onclick="setPackageType('unlimited')">
                            <div class="filter-btn-content">
                                <i class="fas fa-infinity filter-btn-icon"></i>
                                <span class="filter-btn-text">Unlimited</span>
                            </div>
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
                            <option value="relevance">🎯 Most Relevant</option>
                            <option value="volume-asc">📊 Data: Small → Large</option>
                            <option value="volume-desc">📊 Data: Large → Small</option>
                            <option value="price-asc">💰 Price: Low → High</option>
                            <option value="price-desc">💰 Price: High → Low</option>
                            <option value="name-asc">🔤 Name: A → Z</option>
                            <option value="name-desc">🔤 Name: Z → A</option>
                        </select>
                        <button class="reset-btn" onclick="resetAllFilters()">
                            <i class="fas fa-redo"></i>
                            Reset All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Results Section -->
    <section class="results-section">
        <div class="results-container">
            <div id="packagesList" class="packages-grid" style="display: none;">
                <!-- Packages will be populated here by JavaScript -->
            </div>
            
            <div id="noResults" class="empty-state">
                <div class="empty-icon"><i class="fas fa-search"></i></div>
                <h3 class="empty-title">Find Your Perfect eSIM</h3>
                <p class="empty-description">Use the search above to discover packages that match your travel needs</p>
                <p class="empty-hint">Start by typing a country name or browse by region</p>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section">
        <div class="features-container">
            <div class="features-header">
                <h2 class="section-title">Why Choose Our eSIM?</h2>
                <p class="section-subtitle">Experience the future of mobile connectivity</p>
            </div>
            
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
        </div>
    </section>
    
    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-container">
            <div class="contact-header">
                <h2 class="section-title">Need Help?</h2>
                <p class="section-subtitle">Our support team is here to assist you</p>
            </div>
            
            <div class="contact-options">
                <a href="https://wa.me/6281325525646" class="contact-option whatsapp" target="_blank">
                    <div class="contact-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="contact-content">
                        <h4>WhatsApp Support</h4>
                        <p>Get instant help via WhatsApp</p>
                        <span class="contact-badge">24/7 Available</span>
                    </div>
                </a>
                
                <a href="mailto:support@esimstore.com" class="contact-option email">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-content">
                        <h4>Email Support</h4>
                        <p>Send us your questions</p>
                        <span class="contact-badge">24h Response</span>
                    </div>
                </a>
                
                <a href="contact.php" class="contact-option form">
                    <div class="contact-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="contact-content">
                        <h4>Contact Form</h4>
                        <p>Detailed inquiry form</p>
                        <span class="contact-badge">Comprehensive</span>
                    </div>
                </a>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

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
        
        <form id="orderForm" class="order-form" method="POST">
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
            
            <!-- Update di index.php - bagian modal order -->
            <div class="form-group" id="countGroup">
                <label for="orderCount" class="form-label">
                    <i class="fas fa-list-ol"></i>
                    Quantity
                </label>
                <input type="number" id="orderCount" name="count" value="1" min="1" max="20" class="form-input">
                <div class="form-hint" id="countHint">
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

<!-- JavaScript Files - Organized by component -->
<script src="assets/js/index.js?v=<?= time() ?>"></script>
<script src="assets/js/footer.js?v=<?= time() ?>"></script>
</body>
</html>