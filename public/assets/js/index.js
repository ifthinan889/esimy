// Index Page JavaScript - MVC Compatible

// Global variables
let allCountries = [];
let allRegions = [];
let allGlobals = [];
let currentPackages = [];
let currentFilters = {
    packageType: 'all',
    tiktokFilter: 'all',
    searchQuery: '',
    sortOrder: 'relevance'
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Index page...');
    
    initializeTheme();
    initializeSearch();
    initializeFilters();
    loadCountriesData();
    
    console.log('✨ Index page initialized successfully');
});

/**
 * Theme Management
 */
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // Initialize theme from localStorage or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme, themeIcon);
    
    // Theme toggle event listener
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme, themeIcon);
            
            // Add animation effect
            this.style.transform = 'scale(0.8) rotate(180deg)';
            setTimeout(() => {
                this.style.transform = 'scale(1) rotate(0deg)';
            }, 300);
        });
    }
}

function updateThemeIcon(theme, iconElement) {
    if (iconElement) {
        if (theme === 'dark') {
            iconElement.className = 'fas fa-sun';
        } else {
            iconElement.className = 'fas fa-moon';
        }
    }
}

/**
 * Search Functionality
 */
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');
    
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Show/hide clear button
            if (searchClear) {
                searchClear.style.display = query.length > 0 ? 'block' : 'none';
            }
            
            // Debounced search
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.searchQuery = query;
                if (query.length > 0) {
                    searchPackages(query);
                } else {
                    showCountrySelection();
                }
            }, 300);
        });
    }
    
    if (searchClear) {
        searchClear.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                this.style.display = 'none';
                currentFilters.searchQuery = '';
                showCountrySelection();
            }
        });
    }
}

/**
 * Filter Management
 */
function initializeFilters() {
    // Sort order change
    const sortOrder = document.getElementById('sortOrder');
    if (sortOrder) {
        sortOrder.addEventListener('change', function() {
            currentFilters.sortOrder = this.value;
            if (currentPackages.length > 0) {
                displayPackages(currentPackages);
            }
        });
    }
}

function toggleFilters() {
    const filterContent = document.getElementById('filterContent');
    const toggleIcon = document.getElementById('filterToggleIcon');
    const toggleText = document.getElementById('filterToggleText');
    
    if (filterContent) {
        const isVisible = filterContent.style.display === 'block';
        
        if (isVisible) {
            filterContent.style.display = 'none';
            if (toggleIcon) toggleIcon.innerHTML = '<i class="fas fa-eye"></i>';
            if (toggleText) toggleText.textContent = 'Show Filters';
        } else {
            filterContent.style.display = 'block';
            filterContent.style.animation = 'slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            if (toggleIcon) toggleIcon.innerHTML = '<i class="fas fa-eye-slash"></i>';
            if (toggleText) toggleText.textContent = 'Hide Filters';
        }
    }
}

function setPackageType(type) {
    currentFilters.packageType = type;
    
    // Update button states
    document.querySelectorAll('[id$="PackageBtn"], [id$="Btn"]').forEach(btn => {
        if (btn.id.includes('Package') || btn.id.includes('regular') || btn.id.includes('unlimited')) {
            btn.classList.remove('active');
        }
    });
    
    const activeBtn = document.getElementById(type + 'PackageBtn') || document.getElementById(type + 'Btn');
    if (activeBtn) {
        activeBtn.classList.add('active');
    }
    
    if (currentPackages.length > 0) {
        displayPackages(currentPackages);
    }
}

function setTikTokFilter(filter) {
    currentFilters.tiktokFilter = filter;
    
    // Update button states
    document.getElementById('allTikTokBtn').classList.toggle('active', filter === 'all');
    document.getElementById('tiktokSupportedBtn').classList.toggle('active', filter === 'supported');
    document.getElementById('tiktokNotSupportedBtn').classList.toggle('active', filter === 'not-supported');
    
    if (currentPackages.length > 0) {
        displayPackages(currentPackages);
    }
}

function resetAllFilters() {
    currentFilters = {
        packageType: 'all',
        tiktokFilter: 'all',
        searchQuery: '',
        sortOrder: 'relevance'
    };
    
    // Reset UI
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
    }
    
    const searchClear = document.getElementById('searchClear');
    if (searchClear) {
        searchClear.style.display = 'none';
    }
    
    const sortOrder = document.getElementById('sortOrder');
    if (sortOrder) {
        sortOrder.value = 'relevance';
    }
    
    // Reset button states
    setPackageType('all');
    setTikTokFilter('all');
    
    // Show country selection
    showCountrySelection();
}

/**
 * Data Loading
 */
async function loadCountriesData() {
    try {
        showLoading('Loading countries...');
        
        const response = await fetch('?action=get_countries');
        const data = await response.json();
        
        if (data.success) {
            allCountries = data.countries || [];
            allRegions = data.regions || [];
            allGlobals = data.globals || [];
            
            console.log('Countries loaded:', allCountries.length);
            console.log('Regions loaded:', allRegions.length);
            console.log('Globals loaded:', allGlobals.length);
            
            showCountrySelection();
            updateFilterCounts();
        } else {
            throw new Error(data.message || 'Failed to load countries');
        }
    } catch (error) {
        console.error('Error loading countries:', error);
        showError('Failed to load countries. Please refresh the page.');
    } finally {
        hideLoading();
    }
}

async function searchPackages(query) {
    if (!query || query.length < 2) {
        showCountrySelection();
        return;
    }
    
    try {
        showLoading('Searching packages...');
        
        // Search in countries first
        const matchingCountries = allCountries.filter(country => 
            country.name.toLowerCase().includes(query.toLowerCase()) ||
            country.location_code.toLowerCase().includes(query.toLowerCase())
        );
        
        if (matchingCountries.length > 0) {
            // Load packages for first matching country
            await loadPackagesByCountry(matchingCountries[0].name);
        } else {
            // Search in regions
            const matchingRegions = allRegions.filter(region => 
                region.name.toLowerCase().includes(query.toLowerCase())
            );
            
            if (matchingRegions.length > 0) {
                await loadPackagesByRegion(matchingRegions[0].name, 'REGIONAL');
            } else {
                showNoResults(query);
            }
        }
    } catch (error) {
        console.error('Search error:', error);
        showError('Search failed. Please try again.');
    } finally {
        hideLoading();
    }
}

async function loadPackagesByCountry(countryName) {
    try {
        showLoading('Loading packages...');
        
        const response = await fetch(`?action=get_packages_by_country&country=${encodeURIComponent(countryName)}`);
        const data = await response.json();
        
        if (data.success) {
            currentPackages = data.packages || [];
            displayPackages(currentPackages);
            updateFilterCounts();
        } else {
            throw new Error(data.message || 'Failed to load packages');
        }
    } catch (error) {
        console.error('Error loading packages:', error);
        showError('Failed to load packages for ' + countryName);
    } finally {
        hideLoading();
    }
}

async function loadPackagesByRegion(regionName, type = 'REGIONAL') {
    try {
        showLoading('Loading packages...');
        
        const response = await fetch(`?action=get_packages_by_region&region=${encodeURIComponent(regionName)}&type=${encodeURIComponent(type)}`);
        const data = await response.json();
        
        if (data.success) {
            currentPackages = data.packages || [];
            displayPackages(currentPackages);
            updateFilterCounts();
        } else {
            throw new Error(data.message || 'Failed to load packages');
        }
    } catch (error) {
        console.error('Error loading packages:', error);
        showError('Failed to load packages for ' + regionName);
    } finally {
        hideLoading();
    }
}

/**
 * Display Functions
 */
function showCountrySelection() {
    const packagesList = document.getElementById('packagesList');
    const noResults = document.getElementById('noResults');
    
    if (packagesList) packagesList.style.display = 'none';
    if (noResults) noResults.style.display = 'block';
    
    currentPackages = [];
    updateFilterCounts();
}

function displayPackages(packages) {
    const packagesList = document.getElementById('packagesList');
    const noResults = document.getElementById('noResults');
    
    if (!packagesList || !noResults) return;
    
    // Filter packages
    const filteredPackages = filterPackages(packages);
    
    if (filteredPackages.length === 0) {
        packagesList.style.display = 'none';
        noResults.style.display = 'block';
        noResults.innerHTML = `
            <div class="empty-icon"><i class="fas fa-search"></i></div>
            <h3 class="empty-title">No packages found</h3>
            <p class="empty-description">Try adjusting your filters or search terms</p>
        `;
        return;
    }
    
    // Sort packages
    const sortedPackages = sortPackages(filteredPackages);
    
    // Render packages
    packagesList.innerHTML = '';
    sortedPackages.forEach(pkg => {
        const packageElement = createPackageElement(pkg);
        packagesList.appendChild(packageElement);
    });
    
    packagesList.style.display = 'grid';
    noResults.style.display = 'none';
    
    updateFilterCounts();
}

function filterPackages(packages) {
    return packages.filter(pkg => {
        // Package type filter
        if (currentFilters.packageType !== 'all') {
            const isUnlimited = isUnlimitedPackage(pkg);
            if (currentFilters.packageType === 'unlimited' && !isUnlimited) return false;
            if (currentFilters.packageType === 'regular' && isUnlimited) return false;
        }
        
        // TikTok filter
        if (currentFilters.tiktokFilter !== 'all') {
            const supportsTikTok = supportsTikTokFunction(pkg);
            if (currentFilters.tiktokFilter === 'supported' && !supportsTikTok) return false;
            if (currentFilters.tiktokFilter === 'not-supported' && supportsTikTok) return false;
        }
        
        return true;
    });
}

function sortPackages(packages) {
    const sorted = [...packages];
    
    switch (currentFilters.sortOrder) {
        case 'volume-asc':
            return sorted.sort((a, b) => parseInt(a.volume) - parseInt(b.volume));
        case 'volume-desc':
            return sorted.sort((a, b) => parseInt(b.volume) - parseInt(a.volume));
        case 'price-asc':
            return sorted.sort((a, b) => parseFloat(a.price_usd) - parseFloat(b.price_usd));
        case 'price-desc':
            return sorted.sort((a, b) => parseFloat(b.price_usd) - parseFloat(a.price_usd));
        case 'name-asc':
            return sorted.sort((a, b) => a.name.localeCompare(b.name));
        case 'name-desc':
            return sorted.sort((a, b) => b.name.localeCompare(a.name));
        default:
            return sorted.sort((a, b) => parseInt(a.volume) - parseInt(b.volume));
    }
}

function createPackageElement(pkg) {
    const div = document.createElement('div');
    div.className = 'package-item';
    
    const isUnlimited = isUnlimitedPackage(pkg);
    const supportsTikTok = supportsTikTokFunction(pkg);
    const volumeGB = (parseInt(pkg.volume) / (1024 * 1024 * 1024)).toFixed(1);
    
    div.innerHTML = `
        <div class="package-header">
            <h4>${escapeHtml(pkg.name)}</h4>
        </div>
        <div class="package-body">
            <div class="package-badges">
                ${isUnlimited ? '<span class="special-badge unlimited">♾️ Unlimited</span>' : '<span class="special-badge regular">📱 Regular</span>'}
                ${supportsTikTok ? '<span class="tiktok-badge supported">🎵 TikTok</span>' : '<span class="tiktok-badge not-supported">❌ HK IP</span>'}
            </div>
            
            <div class="package-info">
                <div class="package-info-item">
                    <i class="package-info-icon fas fa-database"></i>
                    <div class="package-info-content">
                        <span class="package-info-label">Data</span>
                        <span class="package-info-value">${volumeGB} GB</span>
                    </div>
                </div>
                <div class="package-info-item">
                    <i class="package-info-icon fas fa-clock"></i>
                    <div class="package-info-content">
                        <span class="package-info-label">Duration</span>
                        <span class="package-info-value">${pkg.duration} ${pkg.duration_unit.toLowerCase()}</span>
                    </div>
                </div>
                <div class="package-info-item">
                    <i class="package-info-icon fas fa-dollar-sign"></i>
                    <div class="package-info-content">
                        <span class="package-info-label">Price</span>
                        <span class="package-info-value">Rp ${parseInt(pkg.price_idr).toLocaleString('id-ID')}</span>
                    </div>
                </div>
            </div>
            
            <div class="package-actions">
                <button class="btn-primary" onclick="showOrderModal('${pkg.package_code}', ${isUnlimited})">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Order Now</span>
                </button>
            </div>
        </div>
    `;
    
    return div;
}

/**
 * Helper Functions
 */
function isUnlimitedPackage(pkg) {
    const supportTopUpType = parseInt(pkg.support_topup_type);
    const fupPolicy = (pkg.fup_policy || '').trim();
    return supportTopUpType === 1 && fupPolicy !== '';
}

function supportsTikTokFunction(pkg) {
    const ipExport = (pkg.ip_export || '').toLowerCase();
    return ipExport !== 'hk';
}

function updateFilterCounts() {
    // Update package type counts
    const allCount = currentPackages.length;
    const regularCount = currentPackages.filter(pkg => !isUnlimitedPackage(pkg)).length;
    const unlimitedCount = currentPackages.filter(pkg => isUnlimitedPackage(pkg)).length;
    
    updateElementText('allPackageCount', allCount);
    updateElementText('regularCount', regularCount);
    updateElementText('unlimitedCount', unlimitedCount);
    
    // Update TikTok counts
    const tiktokSupportedCount = currentPackages.filter(pkg => supportsTikTokFunction(pkg)).length;
    const tiktokNotSupportedCount = allCount - tiktokSupportedCount;
    
    updateElementText('allTikTokCount', allCount);
    updateElementText('tiktokSupportedCount', tiktokSupportedCount);
    updateElementText('tiktokNotSupportedCount', tiktokNotSupportedCount);
}

function updateElementText(id, text) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = text;
    }
}

/**
 * Modal Functions
 */
function showOrderModal(packageCode, isUnlimited = false) {
    const modal = document.getElementById('orderModal');
    const modalTitle = document.getElementById('orderModalTitle');
    const orderAction = document.getElementById('orderAction');
    const orderPackageCode = document.getElementById('orderPackageCode');
    const countGroup = document.getElementById('countGroup');
    const periodGroup = document.getElementById('periodGroup');
    
    if (!modal) return;
    
    // Find package details
    const pkg = currentPackages.find(p => p.package_code === packageCode);
    if (!pkg) return;
    
    // Set form action
    if (orderAction) {
        orderAction.value = isUnlimited ? 'order_unlimited' : 'order_esim';
    }
    
    if (orderPackageCode) {
        orderPackageCode.value = packageCode;
    }
    
    // Update modal title
    if (modalTitle) {
        modalTitle.innerHTML = `
            <i class="fas fa-shopping-cart"></i>
            ${isUnlimited ? 'Order Unlimited Package' : 'Order eSIM Package'}
        `;
    }
    
    // Show/hide appropriate fields
    if (countGroup) {
        countGroup.style.display = isUnlimited ? 'none' : 'block';
    }
    if (periodGroup) {
        periodGroup.style.display = isUnlimited ? 'block' : 'none';
    }
    
    // Update package details
    const packageDetails = document.getElementById('orderPackageDetails');
    if (packageDetails) {
        const volumeGB = (parseInt(pkg.volume) / (1024 * 1024 * 1024)).toFixed(1);
        const supportsTikTok = supportsTikTokFunction(pkg);
        
        packageDetails.innerHTML = `
            <h4><i class="fas fa-box"></i> Package Details</h4>
            <p><strong>Name:</strong> ${escapeHtml(pkg.name)}</p>
            <p><strong>Data:</strong> ${volumeGB} GB</p>
            <p><strong>Duration:</strong> ${pkg.duration} ${pkg.duration_unit.toLowerCase()}</p>
            <p><strong>Price:</strong> Rp ${parseInt(pkg.price_idr).toLocaleString('id-ID')}</p>
            <p><strong>TikTok Support:</strong> ${supportsTikTok ? '✅ Yes' : '❌ No (HK IP)'}</p>
            ${isUnlimited ? '<p><strong>Type:</strong> Unlimited/Dayplans Package</p>' : ''}
        `;
    }
    
    // Show modal
    modal.style.display = 'flex';
    modal.style.animation = 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
    
    // Focus on customer name input
    setTimeout(() => {
        const customerName = document.getElementById('customerName');
        if (customerName) customerName.focus();
    }, 100);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

/**
 * Order Form Handling
 */
document.addEventListener('DOMContentLoaded', function() {
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleOrderSubmission(this);
        });
    }
    
    // Count input change handler
    const orderCount = document.getElementById('orderCount');
    if (orderCount) {
        orderCount.addEventListener('input', function() {
            const countHint = document.getElementById('countHint');
            const count = parseInt(this.value) || 1;
            
            if (countHint) {
                countHint.style.display = count > 1 ? 'block' : 'none';
            }
        });
    }
});

async function handleOrderSubmission(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    try {
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        const formData = new FormData(form);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        
        if (data.success) {
            form.reset();
            showSuccessModal(data);
        } else {
            throw new Error(data.message || 'An unknown error occurred');
        }
    } catch (error) {
        console.error('Order error:', error);
        alert('Order failed: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

function showSuccessModal(data) {
    const successModal = document.getElementById('successModal');
    const successMessage = document.getElementById('successMessage');
    const singleLinkContainer = document.getElementById('singleLinkContainer');
    const multipleLinkContainer = document.getElementById('multipleLinkContainer');
    
    if (!successModal) return;
    
    const isMultiple = data.is_multiple && data.orders && data.orders.length > 1;
    
    if (successMessage) {
        successMessage.innerHTML = `
            <p>🎉 ${isMultiple ? data.count + ' eSIM packages' : 'eSIM package'} ordered successfully!</p>
        `;
    }
    
    if (isMultiple) {
        // Multiple orders
        if (singleLinkContainer) singleLinkContainer.style.display = 'none';
        if (multipleLinkContainer) {
            multipleLinkContainer.style.display = 'block';
            
            const linksList = document.getElementById('linksList');
            if (linksList) {
                linksList.innerHTML = data.orders.map(order => {
                    const tokenLink = `${window.location.origin}/detail.php?token=${order.token}`;
                    return `
                        <div class="link-item">
                            <div class="link-item-info">
                                <div class="link-item-name">${escapeHtml(order.customerName)}</div>
                                <div class="link-item-url">${tokenLink}</div>
                            </div>
                            <button class="link-item-copy" onclick="copyIndividualLink('${tokenLink}')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    `;
                }).join('');
            }
            
            // Store all links for copy all functionality
            window.allOrderLinks = data.orders.map(order => 
                `${order.customerName}: ${window.location.origin}/detail.php?token=${order.token}`
            );
        }
    } else {
        // Single order
        const order = data.orders && data.orders[0];
        if (order) {
            const tokenLink = `${window.location.origin}/detail.php?token=${order.token}`;
            
            if (multipleLinkContainer) multipleLinkContainer.style.display = 'none';
            if (singleLinkContainer) {
                singleLinkContainer.style.display = 'flex';
                const tokenInput = document.getElementById('tokenLink');
                if (tokenInput) {
                    tokenInput.value = tokenLink;
                }
            }
        }
    }
    
    closeModal('orderModal');
    successModal.style.display = 'flex';
    successModal.style.animation = 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
}

/**
 * Copy Functions
 */
function copyTokenLink() {
    const tokenInput = document.getElementById('tokenLink');
    if (tokenInput) {
        copyToClipboard(tokenInput.value);
    }
}

function copyIndividualLink(link) {
    copyToClipboard(link);
}

function copyAllLinks() {
    if (window.allOrderLinks && window.allOrderLinks.length > 0) {
        const allLinksText = window.allOrderLinks.join('\n');
        copyToClipboard(allLinksText);
    }
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('📋 Copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('📋 Copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy to clipboard', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * UI Helper Functions
 */
function showLoading(message = 'Loading...') {
    const packagesList = document.getElementById('packagesList');
    const noResults = document.getElementById('noResults');
    
    if (packagesList) packagesList.style.display = 'none';
    if (noResults) {
        noResults.style.display = 'block';
        noResults.innerHTML = `
            <div class="empty-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <h3 class="empty-title">${message}</h3>
            <p class="empty-description">Please wait...</p>
        `;
    }
}

function hideLoading() {
    // Loading will be hidden when content is displayed
}

function showError(message) {
    const packagesList = document.getElementById('packagesList');
    const noResults = document.getElementById('noResults');
    
    if (packagesList) packagesList.style.display = 'none';
    if (noResults) {
        noResults.style.display = 'block';
        noResults.innerHTML = `
            <div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 class="empty-title">Error</h3>
            <p class="empty-description">${escapeHtml(message)}</p>
            <button class="btn-primary" onclick="window.location.reload()">
                <i class="fas fa-redo"></i> Retry
            </button>
        `;
    }
}

function showNoResults(query) {
    const packagesList = document.getElementById('packagesList');
    const noResults = document.getElementById('noResults');
    
    if (packagesList) packagesList.style.display = 'none';
    if (noResults) {
        noResults.style.display = 'block';
        noResults.innerHTML = `
            <div class="empty-icon"><i class="fas fa-search"></i></div>
            <h3 class="empty-title">No results found</h3>
            <p class="empty-description">No packages found for "${escapeHtml(query)}"</p>
            <p class="empty-hint">Try searching for a different country or region</p>
        `;
    }
}

function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${getToastIcon(type)}</span>
            <span class="toast-message">${escapeHtml(message)}</span>
        </div>
    `;
    
    // Style the toast
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        background: var(--bg-card);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: var(--space-md) var(--space-lg);
        box-shadow: var(--shadow-lg);
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        max-width: 300px;
    `;
    
    if (type === 'success') {
        toast.style.borderLeft = '4px solid var(--success)';
    } else if (type === 'error') {
        toast.style.borderLeft = '4px solid var(--error)';
    }
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }, 3000);
}

function getToastIcon(type) {
    switch (type) {
        case 'success': return '✅';
        case 'error': return '❌';
        case 'warning': return '⚠️';
        default: return 'ℹ️';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Event Listeners
 */
document.addEventListener('keydown', function(e) {
    // ESC to close modals
    if (e.key === 'Escape') {
        closeModal('orderModal');
        closeModal('successModal');
    }
    
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        const modal = event.target.closest('.modal');
        if (modal) {
            closeModal(modal.id);
        }
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Global functions for HTML onclick handlers
window.toggleFilters = toggleFilters;
window.setPackageType = setPackageType;
window.setTikTokFilter = setTikTokFilter;
window.resetAllFilters = resetAllFilters;
window.showOrderModal = showOrderModal;
window.closeModal = closeModal;
window.copyTokenLink = copyTokenLink;
window.copyIndividualLink = copyIndividualLink;
window.copyAllLinks = copyAllLinks;

console.log('📱 Index JS loaded successfully - MVC Compatible version');