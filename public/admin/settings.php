<?php
error_reporting(0);
ini_set('display_errors', '0');
define('ALLOWED_ACCESS', true);

require_once __DIR__ . '/../../config.php';

// Include required files
try {
    require_once __DIR__ . '/../../src/includes/koneksi.php'; // Naik satu level, lalu masuk ke src/includes
    require_once __DIR__ . '/../../src/includes/functions.php';
    require_once __DIR__ . '/../../src/includes/api.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
    die("System error. Please try again later.");
}

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    logSecurityEvent("Unauthorized access attempt to settings", 'warning');
    header("Location: login.php");
    exit();
}

// Regenerate session ID
if (!isset($_SESSION['last_session_regenerate']) || (time() - $_SESSION['last_session_regenerate']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_session_regenerate'] = time();
}

$successMessage = "";
$errorMessage = "";

// Get all app settings
$appSettings = [];
try {
    $stmt = $pdo->query("SELECT * FROM app_settings ORDER BY setting_key ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $appSettings[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching app settings: " . $e->getMessage());
    $errorMessage = "Error loading settings: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent("CSRF token validation failed in settings", 'warning');
        $errorMessage = "Invalid session. Please try again.";
    } else {
        $action = $_POST["action"] ?? '';
        
        switch ($action) {
            case "update_setting":
                try {
                    $settingId = (int)$_POST['setting_id'];
                    $settingKey = trim($_POST['setting_key']);
                    $settingValue = trim($_POST['setting_value']);
                    $settingType = trim($_POST['setting_type']);
                    $description = trim($_POST['description']);
                    
                    // Validate input
                    if (empty($settingKey)) {
                        throw new Exception("Setting key cannot be empty");
                    }
                    
                    // Validate value based on type
                    if ($settingType === 'json' && !empty($settingValue)) {
                        $decoded = json_decode($settingValue);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new Exception("Invalid JSON format");
                        }
                    }
                    
                    if ($settingType === 'integer' && !empty($settingValue) && !is_numeric($settingValue)) {
                        throw new Exception("Value must be a number for integer type");
                    }
                    
                    if ($settingType === 'float' && !empty($settingValue) && !is_numeric($settingValue)) {
                        throw new Exception("Value must be a number for float type");
                    }
                    
                    // Update existing setting
                    if ($settingId > 0) {
                        $stmt = $pdo->prepare("UPDATE app_settings SET setting_key = ?, setting_value = ?, setting_type = ?, description = ? WHERE id = ?");
                        $stmt->execute([$settingKey, $settingValue, $settingType, $description, $settingId]);
                        $successMessage = "✅ Setting updated successfully!";
                    } else {
                        // Create new setting
                        $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$settingKey, $settingValue, $settingType, $description]);
                        $successMessage = "✅ New setting created successfully!";
                    }
                    
                    // Refresh settings
                    $stmt = $pdo->query("SELECT * FROM app_settings ORDER BY setting_key ASC");
                    $appSettings = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $appSettings[] = $row;
                    }
                    
                } catch (Exception $e) {
                    error_log("Error updating setting: " . $e->getMessage());
                    $errorMessage = "❌ Error: " . $e->getMessage();
                }
                break;
                
            case "delete_setting":
                try {
                    $settingId = (int)$_POST['setting_id'];
                    $stmt = $pdo->prepare("DELETE FROM app_settings WHERE id = ?");
                    $stmt->execute([$settingId]);
                    $successMessage = "✅ Setting deleted successfully!";
                    
                    // Refresh settings
                    $stmt = $pdo->query("SELECT * FROM app_settings ORDER BY setting_key ASC");
                    $appSettings = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $appSettings[] = $row;
                    }
                    
                } catch (Exception $e) {
                    error_log("Error deleting setting: " . $e->getMessage());
                    $errorMessage = "❌ Error deleting setting: " . $e->getMessage();
                }
                break;
                
            case "refresh_exchange_rate":
                $result = updateCurrencyRatesFromApi();
                if ($result['success']) {
                    $successMessage = "✅ Exchange rate updated: " . number_format($result['rate'], 0, ',', '.') . " IDR per USD";
                } else {
                    $errorMessage = "❌ " . $result['message'];
                }
                break;
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Settings - eSIM Portal Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/settings.css?v=<?= time() ?>">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Admin settings for eSIM Portal">
</head>
<body>

<!-- Dark Mode Toggle -->
<button class="theme-toggle-floating" id="themeToggle">
    <span id="themeIcon">🌙</span>
</button>

<!-- Main Content -->
<main class="main-content">
    <!-- Header -->
    <section class="settings-header">
        <div class="header-content">
            <h1 class="settings-title">⚙️ App Settings</h1>
            <p class="settings-subtitle">Manage application configuration & markup settings</p>
            <div class="header-actions">
                <button class="btn-primary" onclick="showAddSettingModal()">
                    <span class="btn-icon">➕</span>
                    <span class="btn-text">Add Setting</span>
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="refresh_exchange_rate">
                    <button type="submit" class="btn-secondary">
                        <span class="btn-icon">🔄</span>
                        <span class="btn-text">Refresh Rate</span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Messages -->
    <?php if ($successMessage): ?>
    <div class="message success">
        <span class="message-icon">✅</span>
        <span class="message-text"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></span>
        <button class="message-close" onclick="this.parentElement.style.display='none'">×</button>
    </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
    <div class="message error">
        <span class="message-icon">❌</span>
        <span class="message-text"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
        <button class="message-close" onclick="this.parentElement.style.display='none'">×</button>
    </div>
    <?php endif; ?>
<?php
// Get current markup config
$currentMarkup = getSetting('markup_config', []);
if (is_string($currentMarkup)) {
    $currentMarkup = json_decode($currentMarkup, true) ?: [];
}

// Handle markup form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_markup") {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errorMessage = "Invalid session. Please try again.";
    } else {
        try {
            $markupTiers = [];
            
            // Process each tier from form
            if (isset($_POST['tier_limit']) && isset($_POST['tier_markup'])) {
                for ($i = 0; $i < count($_POST['tier_limit']); $i++) {
                    $limit = floatval($_POST['tier_limit'][$i]);
                    $markup = floatval($_POST['tier_markup'][$i]);
                    
                    if ($limit > 0 && $markup >= 0) {
                        $markupTiers[] = [
                            'limit' => $limit,
                            'markup' => $markup
                        ];
                    }
                }
            }
            
            // Sort by limit ascending
            usort($markupTiers, function($a, $b) {
                return $a['limit'] <=> $b['limit'];
            });
            
            // Update in database
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'markup_config'");
            $stmt->execute([json_encode($markupTiers)]);
            
            // If doesn't exist, create it
            if ($stmt->rowCount() === 0) {
                $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
                $stmt->execute(['markup_config', json_encode($markupTiers), 'json', 'Tiered markup configuration for pricing']);
            }
            
            $currentMarkup = $markupTiers;
            $successMessage = "✅ Markup configuration updated successfully!";
            
        } catch (Exception $e) {
            error_log("Error updating markup: " . $e->getMessage());
            $errorMessage = "❌ Error updating markup: " . $e->getMessage();
        }
    }
}
?>
    <!-- Quick Actions Cards -->
    <section class="quick-actions">
        <div class="action-card">
            <div class="action-icon">💱</div>
            <div class="action-content">
                <h3>Exchange Rate</h3>
                <p>Current: <?= number_format(getSetting('exchange_rate_usd_idr', 17500), 0, ',', '.') ?> IDR</p>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">📈</div>
            <div class="action-content">
                <h3>Markup Config</h3>
                <p><?= count(getSetting('markup_config', [])) ?> tiers configured</p>
            </div>
        </div>
        
        <div class="action-card">
            <div class="action-icon">🔧</div>
            <div class="action-content">
                <h3>Total Settings</h3>
                <p><?= count($appSettings) ?> configurations</p>
            </div>
        </div>
    </section>
<!-- Markup Configuration Section -->
    <section class="markup-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">📈</span>
                Markup Configuration
            </h2>
            <button class="btn-primary" onclick="addMarkupTier()">
                <span class="btn-icon">➕</span>
                <span class="btn-text">Add Tier</span>
            </button>
        </div>
        
        <div class="markup-explanation">
            <div class="explanation-card">
                <div class="explanation-icon">💡</div>
                <div class="explanation-content">
                    <h4>How Markup Works</h4>
                    <p>Set different markup amounts based on data volume (GB). Lower volumes get higher markups, larger volumes get lower markups. The system automatically picks the right tier based on package size.</p>
                </div>
            </div>
        </div>
        
        <form method="POST" id="markupForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="update_markup">
            
            <div class="markup-tiers" id="markupTiers">
                <?php if (!empty($currentMarkup)): ?>
                    <?php foreach ($currentMarkup as $index => $tier): ?>
                    <div class="tier-row" data-index="<?= $index ?>">
                        <div class="tier-content">
                            <div class="tier-info">
                                <div class="tier-number"><?= $index + 1 ?></div>
                                <div class="tier-label">
                                    <span class="tier-title">Tier <?= $index + 1 ?></span>
                                    <span class="tier-description">Packages up to <?= $tier['limit'] ?> GB</span>
                                </div>
                            </div>
                            
                            <div class="tier-inputs">
                                <div class="input-group">
                                    <label>Max Volume (GB)</label>
                                    <input type="number" name="tier_limit[]" value="<?= $tier['limit'] ?>" 
                                        step="0.1" min="0.1" placeholder="e.g., 1.0" required>
                                </div>
                                
                                <div class="input-group">
                                    <label>Markup (IDR)</label>
                                    <input type="number" name="tier_markup[]" value="<?= $tier['markup'] ?>" 
                                        step="1000" min="0" placeholder="e.g., 10000" required>
                                </div>
                                
                                <button type="button" class="btn-remove" onclick="removeTier(this)">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        
                        <div class="tier-preview">
                            <span class="preview-text">
                                Packages ≤ <?= $tier['limit'] ?> GB get +Rp <?= number_format($tier['markup'], 0, ',', '.') ?> markup
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default tiers if none exist -->
                    <div class="tier-row" data-index="0">
                        <div class="tier-content">
                            <div class="tier-info">
                                <div class="tier-number">1</div>
                                <div class="tier-label">
                                    <span class="tier-title">Tier 1</span>
                                    <span class="tier-description">Small packages</span>
                                </div>
                            </div>
                            
                            <div class="tier-inputs">
                                <div class="input-group">
                                    <label>Max Volume (GB)</label>
                                    <input type="number" name="tier_limit[]" value="0.5" 
                                        step="0.1" min="0.1" placeholder="e.g., 0.5" required>
                                </div>
                                
                                <div class="input-group">
                                    <label>Markup (IDR)</label>
                                    <input type="number" name="tier_markup[]" value="5000" 
                                        step="1000" min="0" placeholder="e.g., 5000" required>
                                </div>
                                
                                <button type="button" class="btn-remove" onclick="removeTier(this)">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        
                        <div class="tier-preview">
                            <span class="preview-text">
                                Packages ≤ 0.5 GB get +Rp 5,000 markup
                            </span>
                        </div>
                    </div>
                    
                    <div class="tier-row" data-index="1">
                        <div class="tier-content">
                            <div class="tier-info">
                                <div class="tier-number">2</div>
                                <div class="tier-label">
                                    <span class="tier-title">Tier 2</span>
                                    <span class="tier-description">Medium packages</span>
                                </div>
                            </div>
                            
                            <div class="tier-inputs">
                                <div class="input-group">
                                    <label>Max Volume (GB)</label>
                                    <input type="number" name="tier_limit[]" value="1.0" 
                                        step="0.1" min="0.1" placeholder="e.g., 1.0" required>
                                </div>
                                
                                <div class="input-group">
                                    <label>Markup (IDR)</label>
                                    <input type="number" name="tier_markup[]" value="8000" 
                                        step="1000" min="0" placeholder="e.g., 8000" required>
                                </div>
                                
                                <button type="button" class="btn-remove" onclick="removeTier(this)">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        
                        <div class="tier-preview">
                            <span class="preview-text">
                                Packages ≤ 1.0 GB get +Rp 8,000 markup
                            </span>
                        </div>
                    </div>
                    
                    <div class="tier-row" data-index="2">
                        <div class="tier-content">
                            <div class="tier-info">
                                <div class="tier-number">3</div>
                                <div class="tier-label">
                                    <span class="tier-title">Tier 3</span>
                                    <span class="tier-description">Large packages</span>
                                </div>
                            </div>
                            
                            <div class="tier-inputs">
                                <div class="input-group">
                                    <label>Max Volume (GB)</label>
                                    <input type="number" name="tier_limit[]" value="5.0" 
                                        step="0.1" min="0.1" placeholder="e.g., 5.0" required>
                                </div>
                                
                                <div class="input-group">
                                    <label>Markup (IDR)</label>
                                    <input type="number" name="tier_markup[]" value="15000" 
                                        step="1000" min="0" placeholder="e.g., 15000" required>
                                </div>
                                
                                <button type="button" class="btn-remove" onclick="removeTier(this)">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        
                        <div class="tier-preview">
                            <span class="preview-text">
                                Packages ≤ 5.0 GB get +Rp 15,000 markup
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="markup-actions">
                <button type="submit" class="btn-save">
                    <span class="btn-icon">💾</span>
                    <span class="btn-text">Save Markup Configuration</span>
                </button>
                
                <button type="button" class="btn-secondary" onclick="resetToDefaults()">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Reset to Defaults</span>
                </button>
            </div>
        </form>
        
        <div class="markup-preview-section">
            <h4>📊 Markup Preview</h4>
            <div class="preview-grid" id="markupPreview">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </section>
    <!-- Settings Table -->
    <section class="settings-section">
        <div class="section-header">
            <h2 class="section-title">
                <span class="section-icon">📋</span>
                App Settings Configuration
            </h2>
            <div class="section-filters">
                <input type="text" id="searchSettings" placeholder="🔍 Search settings..." class="search-input">
                <select id="filterType" class="filter-select">
                    <option value="">All Types</option>
                    <option value="string">String</option>
                    <option value="integer">Integer</option>
                    <option value="float">Float</option>
                    <option value="boolean">Boolean</option>
                    <option value="json">JSON</option>
                </select>
            </div>
        </div>
        
        <div class="table-container">
            <table class="settings-table">
                <thead>
                    <tr>
                        <th>Setting Key</th>
                        <th>Value</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="settingsTableBody">
                    <?php foreach ($appSettings as $setting): ?>
                    <tr data-type="<?= htmlspecialchars($setting['setting_type'], ENT_QUOTES, 'UTF-8') ?>">
                        <td>
                            <div class="setting-key">
                                <span class="key-name"><?= htmlspecialchars($setting['setting_key'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="key-id">#<?= $setting['id'] ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="setting-value">
                                <?php 
                                $displayValue = $setting['setting_value'];
                                if (strlen($displayValue) > 50) {
                                    $displayValue = substr($displayValue, 0, 50) . '...';
                                }
                                ?>
                                <span class="value-text" title="<?= htmlspecialchars($setting['setting_value'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="type-badge type-<?= $setting['setting_type'] ?>">
                                <?= strtoupper($setting['setting_type']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="description">
                                <?= htmlspecialchars($setting['description'] ?: 'No description', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-edit" onclick="editSetting(<?= htmlspecialchars(json_encode($setting), ENT_QUOTES, 'UTF-8') ?>)">
                                    ✏️
                                </button>
                                <button class="btn-delete" onclick="deleteSetting(<?= $setting['id'] ?>, '<?= htmlspecialchars($setting['setting_key'], ENT_QUOTES, 'UTF-8') ?>')">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Setting Modal -->
<div id="settingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Setting</h3>
            <button class="modal-close" onclick="closeSettingModal()">×</button>
        </div>
        
        <form id="settingForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="update_setting">
            <input type="hidden" name="setting_id" id="settingId" value="0">
            
            <div class="form-group">
                <label for="settingKey">
                    <span class="label-icon">🔑</span>
                    Setting Key
                </label>
                <input type="text" id="settingKey" name="setting_key" required 
                    placeholder="e.g., exchange_rate_usd_idr">
            </div>
            
            <div class="form-group">
                <label for="settingType">
                    <span class="label-icon">🏷️</span>
                    Data Type
                </label>
                <select id="settingType" name="setting_type" required onchange="updateValueField()">
                    <option value="string">String</option>
                    <option value="integer">Integer</option>
                    <option value="float">Float</option>
                    <option value="boolean">Boolean</option>
                    <option value="json">JSON</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="settingValue">
                    <span class="label-icon">💾</span>
                    Value
                </label>
                <textarea id="settingValue" name="setting_value" rows="3" 
                    placeholder="Enter setting value"></textarea>
                <div class="form-help" id="valueHelp">Enter the setting value</div>
            </div>
            
            <div class="form-group">
                <label for="settingDescription">
                    <span class="label-icon">📝</span>
                    Description
                </label>
                <input type="text" id="settingDescription" name="description" 
                    placeholder="Describe what this setting does">
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeSettingModal()">Cancel</button>
                <button type="submit" class="btn-save">
                    <span class="btn-icon">💾</span>
                    <span class="btn-text">Save Setting</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <a href="/admin/dashboard" class="nav-item">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Dashboard</span>
    </a>
    <a href="/admin/orders" class="nav-item">
        <span class="nav-icon">📦</span>
        <span class="nav-label">Orders</span>
    </a>
    <a href="/admin/esim" class="nav-item">
        <span class="nav-icon">📱</span>
        <span class="nav-label">Packages</span>
    </a>
    <a href="/admin/topup" class="nav-item">
        <span class="nav-icon">💰</span>
        <span class="nav-label">DaftarTopup</span>
    </a>
    <a href="/admin/settings" class="nav-item active">
        <span class="nav-icon">⚙️</span>
        <span class="nav-label">Settings</span>
    </a>
    <a href="/admin/logout" class="nav-item">
        <span class="nav-icon">👤</span>
        <span class="nav-label">Logout</span>
    </a>
</nav>
<script src="assets/js/settings.js?v=<?= time() ?>"></script>
</body>
</html>