// Store all packages data
let allPackages = [];

// Current filter states
let currentFilters = {
    locationType: 'country', // 'country', 'regional', 'global'
    packageType: 'regular',  // 'regular', 'unlimited'
    tiktokFilter: 'all',     // 'all', 'supported', 'not-supported'
    searchQuery: '',
    sortOrder: 'relevance'
};

// Pagination variables
let currentPage = 1;
const itemsPerPage = 6; // Tampilkan 20 package per halaman
let isLoading = false;
let filteredPackagesCache = []; // Cache hasil filter
let currentDisplayedPackages = []; // Package yang sedang ditampilkan

// Filter visibility state
let filtersVisible = false; // Default hidden

// Pagination Helper Functions
function resetPagination() {
    currentPage = 1;
    currentDisplayedPackages = [];
    isLoading = false;
    
    // Remove existing load more button
    const existingBtn = document.getElementById('loadMoreBtn');
    if (existingBtn) {
        existingBtn.remove();
    }
}

// Update function showLoadMoreButton
function showLoadMoreButton(totalPackages, currentDisplayed) {
    // Remove existing button first
    const existingBtn = document.getElementById('loadMoreBtn');
    if (existingBtn) {
        existingBtn.remove();
    }
    
    // Only show if there are more packages to load
    if (currentDisplayed < totalPackages) {
        const packagesDiv = document.getElementById('packagesList');
        const loadMoreBtn = document.createElement('button');
        loadMoreBtn.id = 'loadMoreBtn';
        loadMoreBtn.className = 'load-more-btn';
        
        const remaining = totalPackages - currentDisplayed;
        const nextBatch = Math.min(itemsPerPage, remaining);
        
        loadMoreBtn.innerHTML = `
            <span class="load-more-icon">📦</span>
            <span class="load-more-text">Load ${nextBatch} More (${remaining} left)</span>
        `;
        
        loadMoreBtn.onclick = function() {
            loadMorePackages();
        };
        
        // Append ke packages grid (BUKAN ke body)
        packagesDiv.appendChild(loadMoreBtn);
    }
}

// Update hideLoadMoreButton
function hideLoadMoreButton() {
    const existingBtn = document.getElementById('loadMoreBtn');
    if (existingBtn) {
        existingBtn.classList.add('hidden');
        setTimeout(() => {
            existingBtn.remove();
        }, 300);
    }
}

// Update function showLoadingState
function showLoadingState() {
    const existingBtn = document.getElementById('loadMoreBtn');
    if (existingBtn) {
        existingBtn.disabled = true;
        existingBtn.classList.add('loading');
        existingBtn.innerHTML = `
            <span class="loading-spinner"></span>
            <span class="load-more-text">Loading awesome packages...</span>
        `;
    }
}

// Update function loadMorePackages
function loadMorePackages() {
    if (isLoading) return;
    
    isLoading = true;
    showLoadingState();
    
    // Simulate loading delay for smooth UX
    setTimeout(() => {
        currentPage++;
        renderPackagesPage(filteredPackagesCache, false); // false = append, don't clear
        isLoading = false;
        
        // Add success feedback
        const btn = document.getElementById('loadMoreBtn');
        if (btn && !btn.disabled) {
            btn.classList.add('success');
            setTimeout(() => {
                btn.classList.remove('success');
            }, 600);
        }
    }, 800); // Slightly longer delay for better UX
}

function hideLoadMoreButton() {
    const existingBtn = document.getElementById('loadMoreBtn');
    if (existingBtn) {
        existingBtn.remove();
    }
}

function showLoadingState() {
    const existingBtn = document.getElementById('loadMoreBtn');
    if (existingBtn) {
        existingBtn.disabled = true;
        existingBtn.innerHTML = `
            <span class="loading-spinner"></span>
            <span class="load-more-text">Loading...</span>
        `;
    }
}

function loadMorePackages() {
    if (isLoading) return;
    
    isLoading = true;
    showLoadingState();
    
    // Simulate loading delay for smooth UX
    setTimeout(() => {
        currentPage++;
        renderPackagesPage(filteredPackagesCache, false); // false = append, don't clear
        isLoading = false;
    }, 300);
}

function renderPackagesPage(packages, clearFirst = true) {
    const packagesDiv = document.getElementById('packagesList');
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pagePackages = packages.slice(startIndex, endIndex);
    
    console.log(`Rendering page ${currentPage}: ${startIndex} to ${endIndex} of ${packages.length} packages`);
    
    if (clearFirst) {
        // Clear existing content untuk halaman pertama
        packagesDiv.innerHTML = '';
        currentDisplayedPackages = [];
        hideLoadMoreButton();
    }
    
    // Render packages dengan slight delay untuk smooth animation
    pagePackages.forEach((pkg, index) => {
        setTimeout(() => {
            const packageElement = document.createElement('div');
            packageElement.innerHTML = renderPackage(pkg, currentFilters.searchQuery);
            const packageCard = packageElement.firstElementChild;
            
            // Add fade-in animation
            packageCard.style.opacity = '0';
            packageCard.style.transform = 'translateY(20px)';
            packagesDiv.appendChild(packageCard);
            
            // Trigger animation
            setTimeout(() => {
                packageCard.style.transition = 'all 0.3s ease';
                packageCard.style.opacity = '1';
                packageCard.style.transform = 'translateY(0)';
            }, 50);
            
            currentDisplayedPackages.push(pkg);
            
        }, index * 20); // 20ms delay antar item untuk smooth loading
    });
    
    // Show load more button after all items are rendered
    setTimeout(() => {
        showLoadMoreButton(packages.length, currentDisplayedPackages.length);
    }, pagePackages.length * 20 + 100);
}

// Dark mode functionality
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');
    
    html.setAttribute('data-theme', savedTheme);
    themeIcon.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
}

function toggleTheme() {
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    
    // Add a fun animation
    themeIcon.style.transform = 'rotate(360deg) scale(1.2)';
    setTimeout(() => {
        themeIcon.style.transform = '';
    }, 300);
}

// Balance functionality
async function fetchBalance() {
    const balanceDisplay = document.getElementById('balanceDisplay');
    const balanceAmount = document.getElementById('balanceAmount');
    const balanceStatus = document.getElementById('balanceStatus');
    
    try {
        balanceDisplay.classList.add('loading');
        balanceAmount.textContent = 'Loading...';
        balanceStatus.textContent = 'Fetching...';
        
        const response = await fetch('?action=get_balance');
        const data = await response.json();
        
        if (data.success) {
            balanceAmount.textContent = `$${data.balance.toFixed(2)/10000}`;
            balanceStatus.textContent = 'Updated';
            balanceDisplay.classList.remove('loading', 'error');
        } else {
            throw new Error(data.message || 'Failed to fetch balance');
        }
    } catch (error) {
        balanceDisplay.classList.add('error');
        balanceDisplay.classList.remove('loading');
        balanceAmount.textContent = 'Error';
        balanceStatus.textContent = 'Failed';
        console.error('Balance fetch error:', error);
        
        // Fallback to mock data for demo
        setTimeout(() => {
            balanceAmount.textContent = '$12.34';
            balanceStatus.textContent = 'Demo';
            balanceDisplay.classList.remove('error');
        }, 2000);
    }
}

// Fetch packages data via AJAX
async function fetchPackages() {
    try {
        const response = await fetch('?action=get_packages');
        const data = await response.json();
        
        if (data.success && data.packages) {
            allPackages = data.packages;
            updateFilterCounts();
            filterPackages();
        } else {
            throw new Error(data.message || 'Failed to fetch packages');
        }
    } catch (error) {
        console.error('Packages fetch error:', error);
        // Show error state
        const packagesDiv = document.getElementById('packagesList');
        const noResultsDiv = document.getElementById('noResults');
        
        packagesDiv.style.display = 'none';
        noResultsDiv.style.display = 'block';
        noResultsDiv.innerHTML = `
            <div class="empty-icon">❌</div>
            <h3 class="empty-title">Failed to Load Packages</h3>
            <p class="empty-description">Unable to fetch package data. Please refresh the page.</p>
        `;
    }
}

// Function to toggle filter visibility
function toggleFilters() {
    const filterContent = document.getElementById('filterContent');
    const toggleIcon = document.getElementById('filterToggleIcon');
    const toggleText = document.getElementById('filterToggleText');
    
    filtersVisible = !filtersVisible;
    
    if (filtersVisible) {
        filterContent.style.display = 'block';
        filterContent.style.animation = 'slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        toggleIcon.textContent = '👁️';
        toggleText.textContent = 'Hide Filters';
    } else {
        filterContent.style.animation = 'slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => {
            filterContent.style.display = 'none';
        }, 300);
        toggleIcon.textContent = '👀';
        toggleText.textContent = 'Show Filters';
    }
}

// Function to show country modal - COMPLETELY FIXED VERSION
function showCountryModal(packageCode) {
    console.log('showCountryModal called with packageCode:', packageCode);
    
    // Find the package by package_code
    const pkg = allPackages.find(p => p.package_code === packageCode);
    if (!pkg) {
        console.error('Package not found:', packageCode);
        alert('Package not found: ' + packageCode);
        return;
    }
    
    console.log('Found package:', pkg);
    console.log('Location name:', pkg.location_name);
    
    const modal = document.getElementById('countryModal');
    const countryList = document.getElementById('countryList');
    const searchInput = document.getElementById('countrySearchInput');
    
    if (!modal || !countryList || !searchInput) {
        console.error('Modal elements not found');
        alert('Modal elements not found');
        return;
    }
    
    // Parse countries from location_name with comprehensive parsing
    const locationName = pkg.location_name || '';
    let countries = [];
    
    console.log('Raw location_name:', locationName);
    
    if (!locationName) {
        countries = ['Location information not available'];
    } else {
        // Handle different formats of location_name - COMPREHENSIVE PARSING
        if (locationName.includes(',')) {
            // Comma-separated countries
            countries = locationName.split(',').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes(' + ')) {
            // Plus-separated countries (common format)
            countries = locationName.split(' + ').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes(' & ')) {
            // Ampersand-separated countries
            countries = locationName.split(' & ').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes('/')) {
            // Slash-separated countries
            countries = locationName.split('/').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes('|')) {
            // Pipe-separated countries
            countries = locationName.split('|').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes(';')) {
            // Semicolon-separated countries
            countries = locationName.split(';').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes(' - ')) {
            // Dash-separated countries
            countries = locationName.split(' - ').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes(' and ')) {
            // "and" separated countries
            countries = locationName.split(' and ').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes(' or ')) {
            // "or" separated countries
            countries = locationName.split(' or ').map(country => country.trim()).filter(country => country);
        } else if (locationName.includes('  ')) {
            // Double space separated
            countries = locationName.split('  ').map(country => country.trim()).filter(country => country);
        } else {
            // Single location or unrecognized format
            countries = [locationName];
        }
    }
    
    // Remove empty entries and duplicates
    countries = [...new Set(countries.filter(country => country && country.length > 0))];
    
    console.log('Parsed countries:', countries);
    
    // If no countries found after parsing, show the raw location_name
    if (countries.length === 0 && locationName) {
        countries = [locationName];
    }
    
    // If still no countries, show fallback message
    if (countries.length === 0) {
        countries = ['No location information available'];
    }
    
    // Render countries function
    function renderCountries(filteredCountries = countries) {
        if (filteredCountries.length === 0) {
            countryList.innerHTML = '<div class="country-item">No countries found</div>';
            return;
        }
        
        countryList.innerHTML = filteredCountries.map(country => 
            `<div class="country-item">${country}</div>`
        ).join('');
    }
    
    // Search functionality
    searchInput.oninput = function() {
        const query = this.value.toLowerCase();
        const filtered = countries.filter(country => 
            country.toLowerCase().includes(query)
        );
        renderCountries(filtered);
    };
    
    // Initial render
    renderCountries();
    searchInput.value = '';
    
    // Show modal with proper display and animation
    modal.style.display = 'flex';
    modal.style.animation = 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
    
    // Focus search input after a short delay
    setTimeout(() => {
        searchInput.focus();
    }, 100);
    
    console.log('Modal should be visible now');
}

// Function to format bytes
function formatBytes(bytes) {
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    if (bytes === 0) return '0 GB';
    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
}

// Function to check if package supports TikTok (non-HK IP)
function supportsTikTok(pkg) {
    const ipExport = (pkg.ip_export || '').toLowerCase();
    return ipExport !== 'hk'; // TikTok support jika bukan HK IP
}

// Function to check if package is unlimited/dayplans
function isUnlimitedPackage(pkg) {
    const supportTopUpType = parseInt(pkg.support_topup_type);
    const fupPolicy = (pkg.fup_policy || '').trim();
    
    // Unlimited/Dayplans = no topup support (1) AND has FUP policy
    return supportTopUpType === 1 && fupPolicy !== '';
}

// Function to get package location type
function getPackageLocationType(pkg) {
    const type = (pkg.type || '').toUpperCase();
    if (type === 'REGIONAL') return 'regional';
    if (type === 'GLOBAL') return 'global';
    return 'country'; // LOCAL or default
}

// Function to calculate search relevance score
function calculateRelevanceScore(pkg, query) {
    if (!query || query.length === 0) return 0;
    
    const searchQuery = query.toLowerCase();
    let score = 0;
    
    // Get package data
    const name = (pkg.name || '').toLowerCase();
    const description = (pkg.description || '').toLowerCase();
    const locationName = (pkg.location_name || '').toLowerCase();
    const locationCode = (pkg.location_code || '').toLowerCase();
    const locationCodes = locationCode.split(',').map(code => code.trim());
    
    // Exact match in location codes (highest priority)
    if (locationCodes.some(code => code === searchQuery)) {
        score += 1000;
    }
    
    // Exact match in location name
    if (locationName === searchQuery) {
        score += 800;
    }
    
    // Starts with query in location codes
    if (locationCodes.some(code => code.startsWith(searchQuery))) {
        score += 600;
    }
    
    // Starts with query in location name
    if (locationName.startsWith(searchQuery)) {
        score += 500;
    }
    
    // Contains query in location codes
    if (locationCodes.some(code => code.includes(searchQuery))) {
        score += 400;
    }
    
    // Contains query in location name
    if (locationName.includes(searchQuery)) {
        score += 300;
    }
    
    // Starts with query in package name
    if (name.startsWith(searchQuery)) {
        score += 200;
    }
    
    // Contains query in package name
    if (name.includes(searchQuery)) {
        score += 150;
    }
    
    // Contains query in description
    if (description.includes(searchQuery)) {
        score += 100;
    }
    
    // Bonus for shorter names (more specific)
    if (score > 0) {
        const nameLength = name.length;
        score += Math.max(0, 100 - nameLength);
    }
    
    return score;
}

// Function to get TikTok support badge
function getTikTokBadge(pkg) {
    if (supportsTikTok(pkg)) {
        return '<span class="tiktok-badge supported">🎵 TikTok</span>';
    } else {
        return '<span class="tiktok-badge not-supported">❌ HK IP</span>';
    }
}

// Function to get package type badge
function getPackageTypeBadge(pkg) {
    if (isUnlimitedPackage(pkg)) {
        return '<span class="special-badge unlimited">♾️ Unlimited</span>';
    } else {
        const supportTopUpType = parseInt(pkg.support_topup_type);
        if (supportTopUpType === 2) {
            return '<span class="special-badge dayplans">🔄 TopUp</span>';
        } else {
            return '<span class="special-badge dayplans">📱 Regular</span>';
        }
    }
}

// Function to get location type badge
function getLocationTypeBadge(pkg) {
    const locationType = getPackageLocationType(pkg);
    switch(locationType) {
        case 'regional':
            return '<span class="relevance-badge high-relevance">🌏 Regional</span>';
        case 'global':
            return '<span class="relevance-badge exact-match">🌍 Global</span>';
        default:
            return '<span class="relevance-badge medium-relevance">🏳️ Country</span>';
    }
}

// Modern Package Card Renderer with Gen Z Design
function renderPackage(pkg, query = '') {
    const tiktokBadge = getTikTokBadge(pkg);
    const packageTypeBadge = getPackageTypeBadge(pkg);
    const locationTypeBadge = getLocationTypeBadge(pkg);
    const isUnlimited = isUnlimitedPackage(pkg);
    const supportTopUpType = parseInt(pkg.support_topup_type);
    const fupPolicy = (pkg.fup_policy || '').trim();
    const locationType = getPackageLocationType(pkg);
    
    // Add relevance indicator for search results
    let relevanceIndicator = '';
    if (query && query.length > 0) {
        const score = calculateRelevanceScore(pkg, query);
        if (score >= 800) {
            relevanceIndicator = '<span class="relevance-badge exact-match">🎯 Exact</span>';
        } else if (score >= 400) {
            relevanceIndicator = '<span class="relevance-badge high-relevance">⭐ High</span>';
        } else if (score >= 150) {
            relevanceIndicator = '<span class="relevance-badge medium-relevance">✨ Good</span>';
        }
    }
    
    const packageClass = isUnlimited ? 'package-item special-package' : 'package-item';
    
    // Format price with better display
    const priceFormatted = parseInt(pkg.price_idr).toLocaleString('id-ID');
    
    // Location display logic - FIXED with proper onclick handler
    let locationDisplay = '';
    if (locationType === 'regional' || locationType === 'global') {
        locationDisplay = `
            <div class="package-info-item">
                <div class="package-info-content">
                    <button class="country-btn" onclick="showCountryModal('${pkg.package_code}'); return false;" type="button">
                        <span>🌍</span>
                        View Countries
                    </button>
                </div>
            </div>
        `;
    } else {
        const locationText = pkg.location_name || pkg.location_code;
        const shortLocation = locationText.length > 20 ? locationText.substring(0, 20) + '...' : locationText;
        locationDisplay = `
        `;
    }
    
    return `
        <div class="${packageClass}" data-volume="${parseInt(pkg.volume)}" data-price="${parseInt(pkg.price_idr)}" data-tiktok="${supportsTikTok(pkg)}" data-unlimited="${isUnlimited}" data-relevance="${calculateRelevanceScore(pkg, query)}">
            <div class="package-header">
                <h4>${pkg.name}</h4>
            </div>
            <div class="package-body">
                <div class="package-badges">
                    ${relevanceIndicator}
                    ${locationTypeBadge}
                    ${packageTypeBadge}
                    ${tiktokBadge}
                </div>
                
                    <div class="package-info-item">
                        <div class="package-info-icon">⚡</div>
                        <div class="package-info-content">
                            <span class="package-info-label">Speed</span>
                            <span class="package-info-value speed">${pkg.speed}</span>
                        </div>
                    </div>
                    
                    ${locationDisplay}
                    
                    ${isUnlimited && fupPolicy ? `
                    <div class="package-info-item">
                        <div class="package-info-icon">⏱️</div>
                        <div class="package-info-content">
                            <span class="package-info-label">FUP</span>
                            <span class="package-info-value">${fupPolicy}</span>
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="package-actions">
                    <button class="btn-primary" onclick="showOrderModal('${pkg.package_code}', ${isUnlimited})">
                        <span class="btn-icon">${isUnlimited ? '⚡' : '🛒'}</span>
                        <span class="btn-text">${isUnlimited ? 'Order Unlimited' : 'Order Now'}</span>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Function to sort packages with smart relevance
function sortPackages(packages, sortBy, query = '') {
    const sorted = [...packages];
    
    switch(sortBy) {
        case 'relevance':
            if (query && query.length > 0) {
                return sorted.sort((a, b) => {
                    const scoreA = calculateRelevanceScore(a, query);
                    const scoreB = calculateRelevanceScore(b, query);
                    
                    if (scoreA !== scoreB) {
                        return scoreB - scoreA; // Higher score first
                    }
                    
                    // If same relevance score, sort by volume (ascending)
                    return parseInt(a.volume) - parseInt(b.volume);
                });
            } else {
                return sorted.sort((a, b) => parseInt(a.volume) - parseInt(b.volume));
            }
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

// Filter setter functions
function setLocationType(type) {
    currentFilters.locationType = type;
    
    // Update button states
    document.querySelectorAll('[id$="Btn"]').forEach(btn => {
        if (btn.id.includes('country') || btn.id.includes('regional') || btn.id.includes('global')) {
            btn.classList.remove('active');
        }
    });
    document.getElementById(type + 'Btn').classList.add('active');
    
    filterPackages();
}

function setPackageType(type) {
    currentFilters.packageType = type;
    
    // Update button states
    document.getElementById('regularBtn').classList.toggle('active', type === 'regular');
    document.getElementById('unlimitedBtn').classList.toggle('active', type === 'unlimited');
    
    filterPackages();
}

function setTikTokFilter(filter) {
    currentFilters.tiktokFilter = filter;
    
    // Update button states
    document.getElementById('allTikTokBtn').classList.toggle('active', filter === 'all');
    document.getElementById('tiktokSupportedBtn').classList.toggle('active', filter === 'supported');
    document.getElementById('tiktokNotSupportedBtn').classList.toggle('active', filter === 'not-supported');
    
    filterPackages();
}

// Function to show order modal
function showOrderModal(packageCode, isUnlimited = false) {
    const pkg = allPackages.find(p => p.package_code === packageCode);
    if (!pkg) return;

    // Set form action based on package type
    const orderAction = isUnlimited ? 'order_unlimited' : 'order_esim';
    document.getElementById('orderAction').value = orderAction;
    document.getElementById('orderPackageCode').value = packageCode;
    
    // Update modal title
    const modalTitle = isUnlimited ? 'Order Unlimited Package' : 'Order Regular Package';
    document.getElementById('orderModalTitle').innerHTML = `
        <span class="modal-icon">${isUnlimited ? '⚡' : '🛒'}</span>
        ${modalTitle}
    `;
    
    // Update submit button text
    const submitBtn = document.getElementById('orderSubmitBtn');
    submitBtn.innerHTML = `
        <span class="btn-icon">${isUnlimited ? '⚡' : '🛒'}</span>
        <span class="btn-text">${isUnlimited ? 'Order Unlimited' : 'Order Now'}</span>
    `;
    
    // Show/hide appropriate fields
    document.getElementById('countGroup').style.display = isUnlimited ? 'none' : 'block';
    document.getElementById('periodGroup').style.display = isUnlimited ? 'block' : 'none';
    
    const supportTopUpType = parseInt(pkg.support_topup_type);
    const fupPolicy = (pkg.fup_policy || '').trim();
    
    let packageDetails = `
        <h4><span class="package-icon">📦</span> Package Details</h4>
        <p><strong>Package Name:</strong> ${pkg.name}</p>
        <p><strong>Data:</strong> ${formatBytes(parseInt(pkg.volume))}</p>
        <p><strong>Duration:</strong> ${pkg.duration} ${pkg.duration_unit.toLowerCase()}</p>
        <p><strong>Price:</strong> Rp ${parseInt(pkg.price_idr).toLocaleString('id-ID')}</p>
        <p><strong>TikTok Support:</strong> ${supportsTikTok(pkg) ? '✅ Yes' : '❌ No (HK IP)'}</p>
        <p><strong>TopUp Support:</strong> ${supportTopUpType === 2 ? '✅ Yes' : '❌ No'}</p>
    `;
    
    if (fupPolicy) {
        packageDetails += `<p><strong>FUP Policy:</strong> ${fupPolicy}</p>`;
    }
    
    if (isUnlimited) {
        packageDetails += `<p><strong>Type:</strong> Unlimited/Dayplans Package</p>`;
        packageDetails += `<p><strong>Price per Day:</strong> Rp ${Math.round(parseInt(pkg.price_idr)).toLocaleString('id-ID')}</p>`;
    } else if (supportTopUpType === 2) {
        packageDetails += `<p><strong>Type:</strong> Regular Package (TopUp Available)</p>`;
    } else {
        packageDetails += `<p><strong>Type:</strong> Standard Package</p>`;
    }
    
    document.getElementById('orderPackageDetails').innerHTML = packageDetails;

    const modal = document.getElementById('orderModal');
    modal.style.display = 'flex';
    modal.style.animation = 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
    
    // Focus on customer name input
    setTimeout(() => {
        document.getElementById('customerName').focus();
    }, 100);
}

// Enhanced success modal for multiple orders
function showSuccessModal(results, isUnlimited = false) {
    const packageType = isUnlimited ? 'Unlimited Package' : 'Regular Package';
    const isMultiple = Array.isArray(results) && results.length > 1;
    
    if (isMultiple) {
        // Multiple orders
        document.getElementById('successMessage').innerHTML = `
            <p>🎉 ${results.length} ${packageType}s have been ordered successfully!</p>
        `;
        
        // Show multiple links container
        document.getElementById('singleLinkContainer').style.display = 'none';
        document.getElementById('multipleLinkContainer').style.display = 'block';
        
        // Populate links list
        const linksList = document.getElementById('linksList');
        linksList.innerHTML = results.map(result => {
            const tokenLink = `${window.location.origin}/detail.php?token=${result.token}`;
            return `
                <div class="link-item">
                    <div class="link-item-info">
                        <div class="link-item-name">${result.customerName}</div>
                        <div class="link-item-url">${tokenLink}</div>
                    </div>
                    <button class="link-item-copy" onclick="copyIndividualLink('${tokenLink}')">
                        📋 Copy
                    </button>
                </div>
            `;
        }).join('');
        
        // Store all links for copy all functionality
        window.allOrderLinks = results.map(result => 
            `${result.customerName}: ${window.location.origin}/detail.php?token=${result.token}`
        );
        
    } else {
        // Single order (backward compatibility)
        const result = Array.isArray(results) ? results[0] : results;
        const tokenLink = `${window.location.origin}/detail.php?token=${result.token || result}`;
        
        document.getElementById('successMessage').innerHTML = `
            <p>🎉 ${packageType} has been ordered successfully!</p>
        `;
        
        document.getElementById('tokenLink').value = tokenLink;
        document.getElementById('singleLinkContainer').style.display = 'flex';
        document.getElementById('multipleLinkContainer').style.display = 'none';
    }
    
    // Show provisioning note if any order is still provisioning
    const hasProvisioning = Array.isArray(results) ? 
        results.some(r => r.provisioning) : 
        (results.provisioning || false);
    
    document.getElementById('provisioningNote').style.display = hasProvisioning ? 'block' : 'none';
    
    closeModal('orderModal');
    const modal = document.getElementById('successModal');
    modal.style.display = 'flex';
    modal.style.animation = 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
}

// Enhanced Copy Functions with Visual Feedback
function copyTokenLink() {
    const tokenInput = document.getElementById('tokenLink');
    const copyBtn = tokenInput.nextElementSibling;
    const container = copyBtn.closest('.link-container');
    
    tokenInput.select();
    
    try {
        navigator.clipboard.writeText(tokenInput.value).then(() => {
            showCopySuccess(container, 'Link berhasil dicopy! 🎉');
            animateCopyButton(copyBtn);
        });
    } catch (err) {
        document.execCommand('copy');
        showCopySuccess(container, 'Link berhasil dicopy! 🎉');
        animateCopyButton(copyBtn);
    }
}

function copyIndividualLink(link) {
    // Find the button that was clicked
    const clickedBtn = event.target.closest('.link-item-copy');
    const linkItem = clickedBtn.closest('.link-item');
    
    try {
        navigator.clipboard.writeText(link).then(() => {
            showCopySuccess(linkItem, 'Link berhasil dicopy! 📋');
            animateCopyButton(clickedBtn);
        });
    } catch (err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = link;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        showCopySuccess(linkItem, 'Link berhasil dicopy! 📋');
        animateCopyButton(clickedBtn);
    }
}

function copyAllLinks() {
    if (!window.allOrderLinks || window.allOrderLinks.length === 0) return;
    
    const allLinksText = window.allOrderLinks.join('\n');
    const copyAllBtn = document.querySelector('.copy-all-btn');
    const linksHeader = copyAllBtn.closest('.links-header');
    
    try {
        navigator.clipboard.writeText(allLinksText).then(() => {
            showCopySuccess(linksHeader, `${window.allOrderLinks.length} links berhasil dicopy! 🎊`);
            animateCopyButton(copyAllBtn);
        });
    } catch (err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = allLinksText;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        showCopySuccess(linksHeader, `${window.allOrderLinks.length} links berhasil dicopy! 🎊`);
        animateCopyButton(copyAllBtn);
    }
}

// Helper function to show copy success feedback
function showCopySuccess(container, message) {
    // Remove any existing success message
    const existingSuccess = container.querySelector('.copy-success');
    if (existingSuccess) {
        existingSuccess.remove();
    }
    
    // Create and show success message
    const successEl = document.createElement('div');
    successEl.className = 'copy-success';
    successEl.textContent = message;
    
    container.style.position = 'relative';
    container.appendChild(successEl);
    
    // Remove after animation completes
    setTimeout(() => {
        if (successEl.parentNode) {
            successEl.remove();
        }
    }, 2000);
}

// Helper function to animate copy button
function animateCopyButton(button) {
    // Store original content
    const originalHTML = button.innerHTML;
    
    // Add copied class for styling
    button.classList.add('copied');
    
    // Change button content temporarily
    button.innerHTML = `
        <span class="copy-icon">✅</span>
        Copied!
    `;
    
    // Reset after 1.5 seconds
    setTimeout(() => {
        button.classList.remove('copied');
        button.innerHTML = originalHTML;
    }, 1500);
}

// Function to close modals
function closeModal(modalId) {
    const modal = modalId ? document.getElementById(modalId) : 
                   document.getElementById('orderModal');
    
    if (modal) {
        modal.style.animation = 'fadeOut 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
    
    if (!modalId) {
        document.getElementById('successModal').style.display = 'none';
        document.getElementById('countryModal').style.display = 'none';
    }
}

// Function to reset all filters
function resetAllFilters() {
    currentFilters = {
        locationType: 'country',
        packageType: 'regular',
        tiktokFilter: 'all',
        searchQuery: '',
        sortOrder: 'relevance'
    };
    
    document.getElementById('searchInput').value = '';
    document.getElementById('sortOrder').value = 'relevance';
    
    // Reset button states
    setLocationType('country');
    setPackageType('regular');
    setTikTokFilter('all');
    
    // Clear search input
    const searchClear = document.getElementById('searchClear');
    searchClear.style.display = 'none';
    
    // Reset pagination - TAMBAH INI
    resetPagination();
    
    filterPackages();
}

// Di bagian keyboard shortcuts, tambahkan:
document.addEventListener('keydown', function(e) {
    // ESC to close modals
    if (e.key === 'Escape') {
        closeModal();
    }
    
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + D to toggle dark mode
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        toggleTheme();
    }
    
    // TAMBAH INI: Space atau Enter untuk load more (jika tombol visible dan focused)
    if ((e.key === ' ' || e.key === 'Enter') && e.target.id === 'loadMoreBtn') {
        e.preventDefault();
        loadMorePackages();
    }
});

// Main filter function
// Main filter function - MODIFIED untuk pagination
function filterPackages() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const sortOrder = document.getElementById('sortOrder').value;
    
    currentFilters.searchQuery = query;
    currentFilters.sortOrder = sortOrder;
    
    const packagesDiv = document.getElementById('packagesList');
    const noResultsDiv = document.getElementById('noResults');
    const searchClear = document.getElementById('searchClear');

    // Show/hide search clear button
    searchClear.style.display = query.length > 0 ? 'block' : 'none';

    // Auto-switch to relevance sorting when searching
    if (query.length > 0 && sortOrder === 'volume-asc') {
        document.getElementById('sortOrder').value = 'relevance';
        currentFilters.sortOrder = 'relevance';
    }

    // Filter packages based on all criteria
    let filteredPackages = allPackages.filter(pkg => {
        // Location type filter
        const packageLocationType = getPackageLocationType(pkg);
        const matchesLocationType = packageLocationType === currentFilters.locationType;
        
        // Package type filter
        const packageIsUnlimited = isUnlimitedPackage(pkg);
        const matchesPackageType = (currentFilters.packageType === 'unlimited') === packageIsUnlimited;
        
        // Text search filter
        const locationCodes = (pkg.location_code || '').toLowerCase().split(',');
        const locationNames = (pkg.location_name || '').toLowerCase();
        const name = (pkg.name || '').toLowerCase();
        const description = (pkg.description || '').toLowerCase();

        const matchesSearch = query.length === 0 || 
            locationCodes.some(code => code.includes(query)) ||
            locationNames.includes(query) ||
            name.includes(query) ||
            description.includes(query);

        // TikTok filter
        const matchesTikTok = currentFilters.tiktokFilter === 'all' || 
            (currentFilters.tiktokFilter === 'supported' && supportsTikTok(pkg)) ||
            (currentFilters.tiktokFilter === 'not-supported' && !supportsTikTok(pkg));

        return matchesLocationType && matchesPackageType && matchesSearch && matchesTikTok;
    });

    // Sort packages
    filteredPackages = sortPackages(filteredPackages, currentFilters.sortOrder, query);
    
    // Cache filtered results
    filteredPackagesCache = filteredPackages;

    // Display results with pagination
    if (filteredPackages.length > 0) {
        packagesDiv.style.display = 'grid';
        noResultsDiv.style.display = 'none';
        
        // Reset pagination and render first page
        resetPagination();
        renderPackagesPage(filteredPackages, true);
        
    } else {
        packagesDiv.style.display = 'none';
        noResultsDiv.style.display = 'block';
        hideLoadMoreButton();
        
        let noResultsMessage = `<div class="empty-icon">🔍</div><h3 class="empty-title">No packages found</h3>`;
        
        if (query.length > 0) {
            noResultsMessage += `<p class="empty-description">No packages available for "<strong>${query}</strong>"</p>`;
        }
        
        const activeFilters = [];
        activeFilters.push(`Location: ${currentFilters.locationType.toUpperCase()}`);
        activeFilters.push(`Package: ${currentFilters.packageType.toUpperCase()}`);
        
        if (currentFilters.tiktokFilter !== 'all') {
            const filterText = currentFilters.tiktokFilter === 'supported' ? 'with TikTok Support' : 'without TikTok Support';
            activeFilters.push(filterText);
        }
        
        noResultsMessage += `<p class="empty-description">with filters: ${activeFilters.join(', ')}</p>`;
        
        noResultsMessage += `
            <p class="empty-description">💡 Try using:</p>
            <ul style="list-style: none; padding: 0; margin-top: 10px; color: var(--text-muted);">
                <li>• Country codes (e.g., ID, SG, MY)</li>
                <li>• Country names (e.g., Indonesia, Singapore)</li>
                <li>• Change location or package type</li>
                <li>• Reset all filters</li>
            </ul>
        `;
        
        noResultsDiv.innerHTML = noResultsMessage;
    }
    
    // Update filter counts
    updateFilterCounts();
}

// Function to update filter counts
function updateFilterCounts() {
    // Count by location type
    const countryPackages = allPackages.filter(pkg => getPackageLocationType(pkg) === 'country');
    const regionalPackages = allPackages.filter(pkg => getPackageLocationType(pkg) === 'regional');
    const globalPackages = allPackages.filter(pkg => getPackageLocationType(pkg) === 'global');
    
    document.getElementById('countryCount').textContent = countryPackages.length;
    document.getElementById('regionalCount').textContent = regionalPackages.length;
    document.getElementById('globalCount').textContent = globalPackages.length;
    
    // Count by package type
    const currentLocationPackages = allPackages.filter(pkg => {
        const packageLocationType = getPackageLocationType(pkg);
        return packageLocationType === currentFilters.locationType;
    });
    
    const regularPackages = currentLocationPackages.filter(pkg => !isUnlimitedPackage(pkg));
    const unlimitedPackages = currentLocationPackages.filter(pkg => isUnlimitedPackage(pkg));
    
    document.getElementById('regularCount').textContent = regularPackages.length;
    document.getElementById('unlimitedCount').textContent = unlimitedPackages.length;
    
    // Count by TikTok support
    const currentFilteredPackages = allPackages.filter(pkg => {
        const packageLocationType = getPackageLocationType(pkg);
        const packageIsUnlimited = isUnlimitedPackage(pkg);
        const matchesLocationType = packageLocationType === currentFilters.locationType;
        const matchesPackageType = (currentFilters.packageType === 'unlimited') === packageIsUnlimited;
        return matchesLocationType && matchesPackageType;
    });
    
    const allTikTokCount = currentFilteredPackages.length;
    const tiktokSupportedCount = currentFilteredPackages.filter(supportsTikTok).length;
    const tiktokNotSupportedCount = allTikTokCount - tiktokSupportedCount;
    
    document.getElementById('allTikTokCount').textContent = allTikTokCount;
    document.getElementById('tiktokSupportedCount').textContent = tiktokSupportedCount;
    document.getElementById('tiktokNotSupportedCount').textContent = tiktokNotSupportedCount;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme
    initTheme();
    
    // Fetch balance
    fetchBalance();
    
    // Fetch packages data
    fetchPackages();
    
    // Theme toggle event listener
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    // Set default sort order
    const sortOrder = document.getElementById('sortOrder');
    if (sortOrder) {
        sortOrder.value = 'relevance';
    }
    
    // Search input with debouncing
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterPackages();
            }, 300);
        });
    }
    
    // Search clear button
    const searchClear = document.getElementById('searchClear');
    if (searchClear) {
        searchClear.addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            this.style.display = 'none';
            filterPackages();
        });
    }
    
    // Count input change handler for hint display
    const orderCount = document.getElementById('orderCount');
    if (orderCount) {
        orderCount.addEventListener('input', function() {
            const countHint = document.getElementById('countHint');
            const count = parseInt(this.value) || 1;
            
            if (count > 1) {
                countHint.style.display = 'block';
            } else {
                countHint.style.display = 'none';
            }
        });
    }
    
    // Order form submission
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const isUnlimited = formData.get('action') === 'order_unlimited';
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="btn-icon">⏳</span>
                <span class="btn-text">Processing...</span>
            `;
            submitBtn.style.transform = 'scale(0.95)';
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    this.reset();
                    
                    // Handle multiple or single results
                    if (data.results && Array.isArray(data.results)) {
                        showSuccessModal(data.results, isUnlimited);
                    } else {
                        // Backward compatibility for single orders
                        showSuccessModal(data, isUnlimited);
                    }
                } else {
                    throw new Error(data.message || 'An unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Order error:', error);
                alert('Order failed: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                const originalText = isUnlimited ? 'Order Unlimited' : 'Order Now';
                const originalIcon = isUnlimited ? '⚡' : '🛒';
                submitBtn.innerHTML = `
                    <span class="btn-icon">${originalIcon}</span>
                    <span class="btn-text">${originalText}</span>
                `;
                submitBtn.style.transform = '';
            });
        });
    }
    
    // Modal closing
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            closeModal();
        }
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // ESC to close modals
        if (e.key === 'Escape') {
            closeModal();
        }
        
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Ctrl/Cmd + D to toggle dark mode
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            toggleTheme();
        }
    });
});

// Add CSS animation keyframes for fadeOut
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(style);
