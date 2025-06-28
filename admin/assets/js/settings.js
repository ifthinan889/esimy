// Settings Page JavaScript - Enhanced Functionality

// Global variables
let currentEditingId = 0;
let settingsData = [];
let tierCounter = 0;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Settings page with markup management...');
    
    initializeTheme();
    initializeSearch();
    initializeFilters();
    initializeNavigation();
    initializeFormValidation();
    initializeMarkupManagement();
    loadSettingsData();
    
    console.log('✨ Settings page with markup management initialized successfully');
});

/**
 * Theme Management - Same as dashboard
 */
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    // Set default to dark if no setting exists
    let savedTheme = localStorage.getItem('theme') || 'dark';
    
    // Apply theme
    document.documentElement.setAttribute('data-theme', savedTheme);
    localStorage.setItem('theme', savedTheme);
    
    const themeIcon = document.getElementById('themeIcon');
    updateThemeIcon(savedTheme, themeIcon);
    
    // Toggle functionality
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add transition effect
        document.body.classList.add('theme-transitioning');
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        updateThemeIcon(newTheme, themeIcon);
        
        // Animation effect
        this.style.transform = 'scale(0.8) rotate(180deg)';
        setTimeout(() => {
            this.style.transform = 'scale(1) rotate(0deg)';
            document.body.classList.remove('theme-transitioning');
        }, 300);
        
        // Show notification
        showNotification(
            `${newTheme === 'dark' ? '🌙 Dark' : '☀️ Light'} mode activated`, 
            'success', 
            2000
        );
    });
}

function updateThemeIcon(theme, iconElement) {
    if (theme === 'dark') {
        iconElement.innerHTML = '☀️';
        iconElement.setAttribute('title', 'Switch to light mode');
    } else {
        iconElement.innerHTML = '🌙';
        iconElement.setAttribute('title', 'Switch to dark mode');
    }
}

/**
 * Search Functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchSettings');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        filterTable(searchTerm);
    });
    
    // Add search shortcut (Ctrl+F or Cmd+F)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });
}

/**
 * Filter Functionality
 */
function initializeFilters() {
    const filterSelect = document.getElementById('filterType');
    if (!filterSelect) return;
    
    filterSelect.addEventListener('change', function() {
        const selectedType = this.value;
        filterTableByType(selectedType);
    });
}

/**
 * Filter table by search term
 */
function filterTable(searchTerm) {
    const rows = document.querySelectorAll('#settingsTableBody tr');
    
    rows.forEach(row => {
        const keyName = row.querySelector('.key-name')?.textContent.toLowerCase() || '';
        const valueText = row.querySelector('.value-text')?.textContent.toLowerCase() || '';
        const description = row.querySelector('.description')?.textContent.toLowerCase() || '';
        
        const matches = keyName.includes(searchTerm) || 
                       valueText.includes(searchTerm) || 
                       description.includes(searchTerm);
        
        if (matches) {
            row.style.display = '';
            row.style.animation = 'fadeIn 0.3s ease-in';
        } else {
            row.style.display = 'none';
        }
    });
    
    updateTableStats();
}

/**
 * Filter table by type
 */
function filterTableByType(selectedType) {
    const rows = document.querySelectorAll('#settingsTableBody tr');
    
    rows.forEach(row => {
        const rowType = row.getAttribute('data-type');
        
        if (!selectedType || rowType === selectedType) {
            row.style.display = '';
            row.style.animation = 'fadeIn 0.3s ease-in';
        } else {
            row.style.display = 'none';
        }
    });
    
    updateTableStats();
}

/**
 * Update table statistics
 */
function updateTableStats() {
    const totalRows = document.querySelectorAll('#settingsTableBody tr').length;
    const visibleRows = document.querySelectorAll('#settingsTableBody tr[style=""]').length;
    
    console.log(`Showing ${visibleRows} of ${totalRows} settings`);
}

/**
 * Navigation Management
 */
function initializeNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Add click effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'translateY(-2px)';
            }, 100);
        });
    });
}

/**
 * Form Validation
 */
function initializeFormValidation() {
    const settingForm = document.getElementById('settingForm');
    if (!settingForm) return;
    
    settingForm.addEventListener('submit', function(e) {
        const isValid = validateSettingForm();
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }
    });
}

/**
 * Validate setting form
 */
function validateSettingForm() {
    const settingKey = document.getElementById('settingKey').value.trim();
    const settingValue = document.getElementById('settingValue').value.trim();
    const settingType = document.getElementById('settingType').value;
    
    // Validate setting key
    if (!settingKey) {
        showNotification('Setting key is required', 'error');
        document.getElementById('settingKey').focus();
        return false;
    }
    
    // Validate setting key format
    if (!/^[a-z0-9_]+$/.test(settingKey)) {
        showNotification('Setting key can only contain lowercase letters, numbers, and underscores', 'error');
        document.getElementById('settingKey').focus();
        return false;
    }
    
    // Validate value based on type
    if (settingValue) {
        switch (settingType) {
            case 'integer':
                if (!Number.isInteger(Number(settingValue))) {
                    showNotification('Value must be a valid integer', 'error');
                    document.getElementById('settingValue').focus();
                    return false;
                }
                break;
                
            case 'float':
                if (isNaN(parseFloat(settingValue))) {
                    showNotification('Value must be a valid number', 'error');
                    document.getElementById('settingValue').focus();
                    return false;
                }
                break;
                
            case 'boolean':
                if (!['0', '1', 'true', 'false', 'yes', 'no'].includes(settingValue.toLowerCase())) {
                    showNotification('Boolean value must be: 0, 1, true, false, yes, or no', 'error');
                    document.getElementById('settingValue').focus();
                    return false;
                }
                break;
                
            case 'json':
                try {
                    JSON.parse(settingValue);
                } catch (e) {
                    showNotification('Value must be valid JSON', 'error');
                    document.getElementById('settingValue').focus();
                    return false;
                }
                break;
        }
    }
    
    return true;
}

/**
 * Load settings data for client-side operations
 */
function loadSettingsData() {
    const rows = document.querySelectorAll('#settingsTableBody tr');
    settingsData = [];
    
    rows.forEach(row => {
        const keyName = row.querySelector('.key-name')?.textContent || '';
        const valueText = row.querySelector('.value-text')?.getAttribute('title') || '';
        const typeElement = row.querySelector('.type-badge');
        const type = typeElement ? typeElement.textContent.toLowerCase() : 'string';
        const description = row.querySelector('.description')?.textContent || '';
        
        settingsData.push({
            key: keyName,
            value: valueText,
            type: type,
            description: description
        });
    });
    
    console.log(`Loaded ${settingsData.length} settings`);
}

/**
 * Show Add Setting Modal
 */
function showAddSettingModal() {
    currentEditingId = 0;
    document.getElementById('modalTitle').textContent = 'Add New Setting';
    document.getElementById('settingId').value = '0';
    document.getElementById('settingKey').value = '';
    document.getElementById('settingValue').value = '';
    document.getElementById('settingType').value = 'string';
    document.getElementById('settingDescription').value = '';
    
    updateValueField();
    showModal();
}

/**
 * Edit Setting
 */
function editSetting(settingData) {
    currentEditingId = settingData.id;
    document.getElementById('modalTitle').textContent = 'Edit Setting';
    document.getElementById('settingId').value = settingData.id;
    document.getElementById('settingKey').value = settingData.setting_key;
    document.getElementById('settingValue').value = settingData.setting_value;
    document.getElementById('settingType').value = settingData.setting_type;
    document.getElementById('settingDescription').value = settingData.description || '';
    
    updateValueField();
    showModal();
}

/**
 * Delete Setting
 */
function deleteSetting(settingId, settingKey) {
    if (!confirm(`Are you sure you want to delete setting "${settingKey}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    // Create and submit delete form
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="${csrfToken}">
        <input type="hidden" name="action" value="delete_setting">
        <input type="hidden" name="setting_id" value="${settingId}">
    `;
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * Show Modal
 */
function showModal() {
    const modal = document.getElementById('settingModal');
    modal.classList.add('show');
    modal.style.display = 'flex';
    
    // Focus on first input
    setTimeout(() => {
        document.getElementById('settingKey').focus();
    }, 100);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

/**
 * Close Modal
 */
function closeSettingModal() {
    const modal = document.getElementById('settingModal');
    modal.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

/**
 * Update value field based on type
 */
function updateValueField() {
    const typeSelect = document.getElementById('settingType');
    const valueField = document.getElementById('settingValue');
    const helpText = document.getElementById('valueHelp');
    
    const type = typeSelect.value;
    
    switch (type) {
        case 'string':
            valueField.placeholder = 'Enter text value';
            helpText.textContent = 'Enter any text value';
            break;
            
        case 'integer':
            valueField.placeholder = 'Enter number (e.g., 123)';
            helpText.textContent = 'Enter a whole number';
            break;
            
        case 'float':
            valueField.placeholder = 'Enter decimal (e.g., 123.45)';
            helpText.textContent = 'Enter a decimal number';
            break;
            
        case 'boolean':
            valueField.placeholder = 'Enter: 1, 0, true, false';
            helpText.textContent = 'Enter: 1, 0, true, false, yes, or no';
            break;
            
        case 'json':
            valueField.placeholder = '{"key": "value"}';
            helpText.textContent = 'Enter valid JSON format';
            break;
            
        default:
            valueField.placeholder = 'Enter setting value';
            helpText.textContent = 'Enter the setting value';
    }
}

// ===== MARKUP MANAGEMENT FUNCTIONS =====

/**
 * Initialize markup management when page loads
 */
function initializeMarkupManagement() {
    // Add event listeners to existing tier inputs
    document.querySelectorAll('input[name="tier_limit[]"], input[name="tier_markup[]"]').forEach(input => {
        input.addEventListener('change', function() {
            updateTierPreview(this);
        });
        
        input.addEventListener('input', function() {
            // Real-time preview update with debounce
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => {
                updateTierPreview(this);
            }, 500);
        });
    });
    
    // Add form validation for markup form
    const markupForm = document.getElementById('markupForm');
    if (markupForm) {
        markupForm.addEventListener('submit', function(e) {
            if (!validateMarkupForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }
    
    // Initial preview update
    updateMarkupPreview();
    initializeTableEnhancements();
}

/**
 * Add new markup tier
 */
function addMarkupTier() {
    const tiersContainer = document.getElementById('markupTiers');
    if (!tiersContainer) return;
    
    const currentTiers = tiersContainer.querySelectorAll('.tier-row');
    tierCounter = currentTiers.length;
    
    const newTierHtml = `
        <div class="tier-row" data-index="${tierCounter}">
            <div class="tier-content">
                <div class="tier-info">
                    <div class="tier-number">${tierCounter + 1}</div>
                    <div class="tier-label">
                        <span class="tier-title">Tier ${tierCounter + 1}</span>
                        <span class="tier-description">New tier</span>
                    </div>
                </div>
                
                <div class="tier-inputs">
                    <div class="input-group">
                        <label>Max Volume (GB)</label>
                        <input type="number" name="tier_limit[]" value="" 
                            step="0.1" min="0.1" placeholder="e.g., 2.0" required
                            onchange="updateTierPreview(this)">
                    </div>
                    
                    <div class="input-group">
                        <label>Markup (IDR)</label>
                        <input type="number" name="tier_markup[]" value="" 
                            step="1000" min="0" placeholder="e.g., 12000" required
                            onchange="updateTierPreview(this)">
                    </div>
                    
                    <button type="button" class="btn-remove" onclick="removeTier(this)">
                        🗑️
                    </button>
                </div>
            </div>
            
            <div class="tier-preview">
                <span class="preview-text">
                    Configure tier limits and markup
                </span>
            </div>
        </div>
    `;
    
    tiersContainer.insertAdjacentHTML('beforeend', newTierHtml);
    
    // Focus on the first input of new tier
    const newTier = tiersContainer.lastElementChild;
    const firstInput = newTier.querySelector('input[name="tier_limit[]"]');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Renumber all tiers
    renumberTiers();
    updateMarkupPreview();
    
    showNotification('📈 New markup tier added', 'success', 2000);
}

/**
 * Remove markup tier
 */
function removeTier(button) {
    const tierRow = button.closest('.tier-row');
    const tiersContainer = document.getElementById('markupTiers');
    const allTiers = tiersContainer.querySelectorAll('.tier-row');
    
    if (allTiers.length <= 1) {
        showNotification('⚠️ At least one tier is required', 'warning');
        return;
    }
    
    if (confirm('Remove this markup tier?')) {
        tierRow.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            tierRow.remove();
            renumberTiers();
            updateMarkupPreview();
            showNotification('🗑️ Tier removed', 'success', 2000);
        }, 300);
    }
}

/**
 * Renumber all tiers
 */
function renumberTiers() {
    const tiers = document.querySelectorAll('.tier-row');
    
    tiers.forEach((tier, index) => {
        tier.setAttribute('data-index', index);
        
        const tierNumber = tier.querySelector('.tier-number');
        const tierTitle = tier.querySelector('.tier-title');
        
        if (tierNumber) tierNumber.textContent = index + 1;
        if (tierTitle) tierTitle.textContent = `Tier ${index + 1}`;
        
        // Update description based on volume
        const volumeInput = tier.querySelector('input[name="tier_limit[]"]');
        const tierDescription = tier.querySelector('.tier-description');
        
        if (volumeInput && tierDescription && volumeInput.value) {
            const volume = parseFloat(volumeInput.value);
            if (volume <= 1) {
                tierDescription.textContent = 'Small packages';
            } else if (volume <= 3) {
                tierDescription.textContent = 'Medium packages';
            } else {
                tierDescription.textContent = 'Large packages';
            }
        }
    });
}

/**
 * Update tier preview when inputs change
 */
function updateTierPreview(input) {
    const tierRow = input.closest('.tier-row');
    const volumeInput = tierRow.querySelector('input[name="tier_limit[]"]');
    const markupInput = tierRow.querySelector('input[name="tier_markup[]"]');
    const previewText = tierRow.querySelector('.preview-text');
    
    const volume = parseFloat(volumeInput.value) || 0;
    const markup = parseFloat(markupInput.value) || 0;
    
    if (volume > 0 && markup >= 0) {
        previewText.textContent = `Packages ≤ ${volume} GB get +Rp ${markup.toLocaleString('id-ID')} markup`;
    } else {
        previewText.textContent = 'Configure tier limits and markup';
    }
    
    // Update tier description
    const tierDescription = tierRow.querySelector('.tier-description');
    if (volume > 0) {
        if (volume <= 1) {
            tierDescription.textContent = 'Small packages';
        } else if (volume <= 3) {
            tierDescription.textContent = 'Medium packages';
        } else {
            tierDescription.textContent = 'Large packages';
        }
    }
    
    updateMarkupPreview();
}

/**
 * Update markup preview section
 */
function updateMarkupPreview() {
    const previewContainer = document.getElementById('markupPreview');
    if (!previewContainer) return;
    
    const tiers = document.querySelectorAll('.tier-row');
    const sampleVolumes = [0.5, 1.0, 2.0, 3.0, 5.0, 10.0];
    
    let previewHtml = '';
    
    sampleVolumes.forEach(volume => {
        let applicableMarkup = 0;
        let tierName = 'No tier';
        
        // Find applicable tier
        tiers.forEach((tier, index) => {
            const volumeInput = tier.querySelector('input[name="tier_limit[]"]');
            const markupInput = tier.querySelector('input[name="tier_markup[]"]');
            
            const tierLimit = parseFloat(volumeInput.value) || 0;
            const tierMarkup = parseFloat(markupInput.value) || 0;
            
            if (volume <= tierLimit && tierLimit > 0) {
                applicableMarkup = tierMarkup;
                tierName = `Tier ${index + 1}`;
                return;
            }
        });
        
        // If no tier found, use highest tier
        if (applicableMarkup === 0 && tiers.length > 0) {
            const lastTier = tiers[tiers.length - 1];
            const lastMarkupInput = lastTier.querySelector('input[name="tier_markup[]"]');
            applicableMarkup = parseFloat(lastMarkupInput.value) || 0;
            tierName = `Tier ${tiers.length}`;
        }
        
        previewHtml += `
            <div class="preview-item">
                <div class="preview-volume">${volume} GB</div>
                <div class="preview-markup">+Rp ${applicableMarkup.toLocaleString('id-ID')}</div>
                <div class="preview-tier">${tierName}</div>
            </div>
        `;
    });
    
    previewContainer.innerHTML = previewHtml;
}

/**
 * Reset to default markup configuration
 */
function resetToDefaults() {
    if (!confirm('Reset markup configuration to defaults?\n\nThis will replace all current tiers with default values.')) {
        return;
    }
    
    const tiersContainer = document.getElementById('markupTiers');
    if (!tiersContainer) return;
    
    const defaultTiers = [
        { limit: 0.5, markup: 5000, description: 'Small packages' },
        { limit: 1.0, markup: 8000, description: 'Medium packages' },
        { limit: 5.0, markup: 15000, description: 'Large packages' }
    ];
    
    let tiersHtml = '';
    
    defaultTiers.forEach((tier, index) => {
        tiersHtml += `
            <div class="tier-row" data-index="${index}">
                <div class="tier-content">
                    <div class="tier-info">
                        <div class="tier-number">${index + 1}</div>
                        <div class="tier-label">
                            <span class="tier-title">Tier ${index + 1}</span>
                            <span class="tier-description">${tier.description}</span>
                        </div>
                    </div>
                    
                    <div class="tier-inputs">
                        <div class="input-group">
                            <label>Max Volume (GB)</label>
                            <input type="number" name="tier_limit[]" value="${tier.limit}" 
                                step="0.1" min="0.1" placeholder="e.g., ${tier.limit}" required
                                onchange="updateTierPreview(this)">
                        </div>
                        
                        <div class="input-group">
                            <label>Markup (IDR)</label>
                            <input type="number" name="tier_markup[]" value="${tier.markup}" 
                                step="1000" min="0" placeholder="e.g., ${tier.markup}" required
                                onchange="updateTierPreview(this)">
                        </div>
                        
                        <button type="button" class="btn-remove" onclick="removeTier(this)">
                            🗑️
                        </button>
                    </div>
                </div>
                
                <div class="tier-preview">
                    <span class="preview-text">
                        Packages ≤ ${tier.limit} GB get +Rp ${tier.markup.toLocaleString('id-ID')} markup
                    </span>
                </div>
            </div>
        `;
    });
    
    tiersContainer.innerHTML = tiersHtml;
    updateMarkupPreview();
    
    showNotification('🔄 Reset to default markup configuration', 'success');
}

/**
 * Validate markup form before submission
 */
function validateMarkupForm() {
    const tiers = document.querySelectorAll('.tier-row');
    const tierData = [];
    
    // Collect all tier data
    for (let i = 0; i < tiers.length; i++) {
        const tier = tiers[i];
        const volumeInput = tier.querySelector('input[name="tier_limit[]"]');
        const markupInput = tier.querySelector('input[name="tier_markup[]"]');
        
        const volume = parseFloat(volumeInput.value);
        const markup = parseFloat(markupInput.value);
        
        if (isNaN(volume) || volume <= 0) {
            showNotification(`❌ Tier ${i + 1}: Volume must be greater than 0`, 'error');
            volumeInput.focus();
            return false;
        }
        
        if (isNaN(markup) || markup < 0) {
            showNotification(`❌ Tier ${i + 1}: Markup must be 0 or greater`, 'error');
            markupInput.focus();
            return false;
        }
        
        tierData.push({ volume, markup, index: i });
    }
    
    // Check for duplicate volumes
    const volumes = tierData.map(t => t.volume);
    const duplicateVolumes = volumes.filter((v, i) => volumes.indexOf(v) !== i);
    
    if (duplicateVolumes.length > 0) {
        showNotification(`❌ Duplicate volumes found: ${duplicateVolumes.join(', ')} GB`, 'error');
        return false;
    }
    
    return true;
}

/**
 * Enhanced table interactions
 */
function initializeTableEnhancements() {
    const rows = document.querySelectorAll('#settingsTableBody tr');
    
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
            this.style.boxShadow = 'var(--shadow-md)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
            this.style.boxShadow = 'none';
        });
    });
}

/**
 * Enhanced notification system
 */
function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-text">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        background: var(--gradient-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-xl);
        padding: var(--space-lg);
        box-shadow: var(--shadow-xl);
        max-width: 400px;
        animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        color: var(--text-primary);
    `;
    
    // Add type-specific styling
    if (type === 'success') {
        notification.style.borderLeft = '4px solid var(--success-color)';
    } else if (type === 'error') {
        notification.style.borderLeft = '4px solid var(--danger-color)';
    } else if (type === 'warning') {
        notification.style.borderLeft = '4px solid var(--warning-color)';
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => notification.remove(), 400);
        }
    }, duration);
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return '✅';
        case 'error': return '❌';
        case 'warning': return '⚠️';
        default: return 'ℹ️';
    }
}

/**
 * Copy setting key to clipboard
 */
function copySettingKey(settingKey) {
    navigator.clipboard.writeText(settingKey).then(() => {
        showNotification(`📋 Copied: ${settingKey}`, 'success', 2000);
    }).catch(() => {
        showNotification('Failed to copy to clipboard', 'error');
    });
}

/**
 * Export settings as JSON
 */
function exportSettings() {
    const exportData = settingsData.map(setting => ({
        key: setting.key,
        value: setting.value,
        type: setting.type,
        description: setting.description
    }));
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = `app_settings_${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
showNotification('📄 Settings exported successfully', 'success');
}

/**
* Keyboard shortcuts
*/
document.addEventListener('keydown', function(e) {
   // ESC to close modal
   if (e.key === 'Escape') {
       const modal = document.getElementById('settingModal');
       if (modal && modal.classList.contains('show')) {
           closeSettingModal();
       }
   }
   
   // Ctrl+N or Cmd+N to add new setting
   if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
       e.preventDefault();
       showAddSettingModal();
   }
});

/**
* Auto-hide success messages
*/
setTimeout(() => {
   const message = document.querySelector('.message.success');
   if (message) {
       message.style.opacity = '0';
       setTimeout(() => {
           message.style.display = 'none';
       }, 300);
   }
}, 5000);

// Global functions for HTML onclick events
window.showAddSettingModal = showAddSettingModal;
window.editSetting = editSetting;
window.deleteSetting = deleteSetting;
window.closeSettingModal = closeSettingModal;
window.updateValueField = updateValueField;
window.copySettingKey = copySettingKey;
window.exportSettings = exportSettings;
window.addMarkupTier = addMarkupTier;
window.removeTier = removeTier;
window.updateTierPreview = updateTierPreview;
window.resetToDefaults = resetToDefaults;

// Console welcome message
console.log(`
⚙️ eSIM Portal Settings Panel
🚀 Version: 2.0 - Advanced Configuration  
💻 Features: CRUD Operations, Search, Filter, Markup Management
🎨 Design: Modern Dark/Light Mode
📱 Mobile Responsive Design
🔒 Secure Form Validation

Keyboard Shortcuts:
- Ctrl/Cmd + F: Focus search
- Ctrl/Cmd + N: Add new setting  
- ESC: Close modal

Functions Available:
- Real-time search & filter
- Type-based validation
- JSON format validation
- Export settings data
- Copy setting keys
- Theme switching
- User-friendly markup configuration
- Real-time markup preview
- Tier management with validation

Markup Features:
- Visual tier builder
- Real-time preview calculations
- Smart validation & error checking
- Reset to defaults
- Mobile-responsive design
`);