/**
 * eSIM Store - Country-First Navigation
 * Load countries first, then packages on selection
 */

// ===========================================
// GLOBAL VARIABLES
// ===========================================

let allCountries = [];
let allRegions = [];
let allGlobals = [];
let currentPackages = [];
let selectedCountry = null;
let selectedRegion = null;
let selectedGlobal = null;
let navigationLevel = 'main'; // 'main', 'packages'
let currentFilters = {
    packageType: 'all',
    tiktokFilter: 'all',
    searchQuery: '',
    sortOrder: 'relevance'
};
let filtersVisible = false;
let currentTab = 'countries'; // Track current tab
let lastActiveTab = 'countries'; // Track last active tab before viewing packages

// ===========================================
// INITIALIZATION
// ===========================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing eSIM Store - Country First Mode...');
    
    initializeTheme();
    fetchCountries();
    setupEventListeners();
    showCountrySelection();
    
    console.log('✨ eSIM Store initialized');
});

// ===========================================
// SAFE DOM UTILITIES
// ===========================================

// Safe element selector function
function safeGetElement(id) {
    const element = document.getElementById(id);
    if (!element) {
        console.warn(`Element with ID '${id}' not found`);
        return null;
    }
    return element;
}

// Safe text content setter
function safeSetTextContent(id, text) {
    const element = safeGetElement(id);
    if (element) {
        element.textContent = text;
        return true;
    }
    return false;
}

// Safe class toggle
function safeToggleClass(id, className, condition) {
    const element = safeGetElement(id);
    if (element) {
        element.classList.toggle(className, condition);
        return true;
    }
    return false;
}

const isMobile = /Android|iPhone|iPad/i.test(navigator.userAgent);

// Ganti function animatePackageItems() yang ada
function animatePackageItems() {
    const packageItems = document.querySelectorAll('.package-item');
    const isMobile = window.innerWidth <= 768 || /Android|iPhone|iPad/i.test(navigator.userAgent);
    
    if (isMobile) {
        // Skip animasi di mobile, langsung tampilkan
        packageItems.forEach(item => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        });
        return;
    }

    // Animasi hanya untuk desktop
    packageItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        setTimeout(() => {
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 30); // Kurangi delay
    });
}


// ===========================================
// THEME MANAGEMENT
// ===========================================

function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');
    
    html.setAttribute('data-theme', savedTheme);
    if (themeIcon) {
        themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
    
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
}

function toggleTheme() {
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    if (themeIcon) {
        themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        
        themeIcon.style.transform = 'rotate(360deg) scale(1.2)';
        setTimeout(() => {
            themeIcon.style.transform = '';
        }, 300);
    }
}

// ===========================================
// DATA FETCHING
// ===========================================

async function fetchCountries() {
    try {
        console.log('🌍 Fetching countries and regions...');
        const response = await fetch('?action=get_countries');
        const data = await response.json();
        
        if (data.success) {
            allCountries = data.countries || [];
            allRegions = data.regions || [];
            allGlobals = data.globals || [];
            
            console.log('📍 Data loaded:', {
                countries: allCountries.length,
                regions: allRegions.length,
                globals: allGlobals.length
            });
            
            renderCountrySelection();
        } else {
            throw new Error(data.message || 'Failed to fetch countries');
        }
    } catch (error) {
        console.error('Countries fetch error:', error);
        showError('Failed to load countries. Please refresh the page.');
    }
}

async function fetchPackagesByRegion(regionName, type = 'REGIONAL') {
    try {
        console.log('📦 Fetching packages for region:', regionName);
        showLoadingState();
        
        const response = await fetch(`?action=get_packages_by_region&region=${encodeURIComponent(regionName)}&type=${type}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response');
        }
        
        const data = await response.json();
        
        if (data.success && data.packages) {
            currentPackages = data.packages;
            
            if (type === 'REGIONAL') {
                selectedRegion = regionName;
                lastActiveTab = 'regions'; // Track that we came from regions tab
            } else {
                selectedGlobal = regionName;
                lastActiveTab = 'global'; // Track that we came from global tab
            }
            
            console.log('📦 Packages loaded:', currentPackages.length);
            
            hideCountrySelection();
            showPackageFilters();
            updateFilterCounts();
            filterPackages();
            
            // Scroll to filters section setelah packages loaded
            setTimeout(() => {
                scrollToFilters();
            }, 500);
            
        } else {
            throw new Error(data.message || 'Failed to fetch packages');
        }
    } catch (error) {
        console.error('Packages fetch error:', error);
        showError('Failed to load packages for ' + regionName + '. Please try again.');
    } finally {
        hideLoadingState();
    }
}

async function fetchPackagesByCountry(country) {
    try {
        console.log('📦 Fetching packages for country:', country);
        showLoadingState();
        
        const response = await fetch(`?action=get_packages_by_country&country=${encodeURIComponent(country)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response');
        }
        
        const data = await response.json();
        
        if (data.success && data.packages) {
            currentPackages = data.packages;
            selectedCountry = country;
            lastActiveTab = 'countries'; // Track that we came from countries tab
            console.log('📦 Packages loaded:', currentPackages.length);
            
            hideCountrySelection();
            showPackageFilters();
            updateFilterCounts();
            filterPackages();
            
            // Scroll to filters section setelah packages loaded
            setTimeout(() => {
                scrollToFilters();
            }, 500);
            
        } else {
            throw new Error(data.message || 'Failed to fetch packages');
        }
    } catch (error) {
        console.error('Packages fetch error:', error);
        showError('Failed to load packages for ' + country + '. Please try again.');
    } finally {
        hideLoadingState();
    }
}

// ===========================================
// COUNTRY SELECTION UI
// ===========================================

function showCountrySelection() {
    console.log('🏠 Showing country selection');
    
    const packagesDiv = document.getElementById('packagesList');
    const noResultsDiv = document.getElementById('noResults');
    const filtersSection = document.querySelector('.filter-section');
    
    if (packagesDiv) packagesDiv.style.display = 'none';
    if (filtersSection) filtersSection.style.display = 'none';
    if (noResultsDiv) noResultsDiv.style.display = 'block';
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.placeholder = 'Search countries (e.g., Indonesia, Singapore, Malaysia)';
        searchInput.value = '';
    }
    
    const searchClear = document.getElementById('searchClear');
    if (searchClear) {
        searchClear.style.display = 'none';
    }
    
    // Remove back button if exists
    const backButton = document.querySelector('.back-button');
    if (backButton) {
        backButton.remove();
    }
}

function hideCountrySelection() {
    const noResultsDiv = document.getElementById('noResults');
    if (noResultsDiv) {
        noResultsDiv.style.display = 'none';
    }
}

function showPackageFilters() {
    console.log('🎛️ Showing package filters');
    
    const filtersSection = document.querySelector('.filter-section');
    if (filtersSection) {
        filtersSection.style.display = 'block';
    }
    
    const searchInput = safeGetElement('searchInput');
    if (searchInput) {
        let placeholder = `Search packages in ${selectedCountry}`;
        if (selectedRegion) {
            placeholder = `Search ${selectedRegion} packages`;
        } else if (selectedGlobal) {
            placeholder = `Search ${selectedGlobal} packages`;
        }
        searchInput.placeholder = placeholder;
        searchInput.value = '';
        currentFilters.searchQuery = '';
    }
    
    // Reset sort order ke default saat masuk ke packages
    currentFilters.sortOrder = 'relevance';
    const sortOrder = safeGetElement('sortOrder');
    if (sortOrder) {
        sortOrder.value = 'relevance';
    }
    
    showBackButton();
}

function showBackButton() {
    // Remove existing back button first
    const existingBackButton = document.querySelector('.back-button');
    if (existingBackButton) {
        existingBackButton.remove();
    }
    
    const filtersSection = document.querySelector('.filter-section');
    if (!filtersSection) return;
    
    const backButton = document.createElement('div');
    backButton.className = 'back-button';
    
    // Determine back text and selection text based on last active tab
    let backText = 'Back to Countries';
    let selectionText = selectedCountry;
    let tabIcon = 'fas fa-flag';
    
    if (lastActiveTab === 'regions') {
        backText = 'Back to Regions';
        selectionText = selectedRegion;
        tabIcon = 'fas fa-globe-asia';
    } else if (lastActiveTab === 'global') {
        backText = 'Back to Global';
        selectionText = selectedGlobal;
        tabIcon = 'fas fa-globe';
    } else if (lastActiveTab === 'countries') {
        backText = 'Back to Countries';
        selectionText = selectedCountry;
        tabIcon = 'fas fa-flag';
    }
    
    backButton.innerHTML = `
        <button class="btn-back" onclick="goBackToCountrySelection()">
            <i class="fas fa-arrow-left"></i>
            <span>${backText}</span>
        </button>
        <div class="current-selection">
            <i class="${tabIcon}"></i>
            <span>Packages for: <strong>${selectionText}</strong></span>
        </div>
    `;
    
    // Insert before filter section
    filtersSection.parentNode.insertBefore(backButton, filtersSection);
}

function renderCountrySelection() {
    console.log('🎨 Rendering main country selection');
    
    navigationLevel = 'main';
    selectedRegion = null;
    selectedGlobal = null;
    selectedCountry = null;
    
    const noResultsDiv = document.getElementById('noResults');
    if (!noResultsDiv) return;
    
    console.log('Data summary:', {
        countries: allCountries.length,
        regions: allRegions.length,
        globals: allGlobals.length
    });
    
    // Use lastActiveTab to determine which tab to show, fallback to countries
    currentTab = lastActiveTab || 'countries';
    
    let html = `
        <div class="country-selection">
            <div class="choose-plan-header">
                <h1 class="choose-plan-title">Choose your plan</h1>
                <p class="choose-plan-subtitle">
                    Please dial <strong>*#06#</strong> to check device compatibility, if EID exist then<br>
                    your device is compatible
                </p>
            </div>
            
            <div class="country-tabs">
                <button class="country-tab ${currentTab === 'countries' ? 'active' : ''}" onclick="switchTab('countries')" id="tabCountries">
                    <i class="fas fa-flag"></i>
                    Countries (${allCountries.length})
                </button>
                <button class="country-tab ${currentTab === 'regions' ? 'active' : ''}" onclick="switchTab('regions')" id="tabRegions">
                    <i class="fas fa-globe-asia"></i>
                    Regions (${allRegions.length})
                </button>
                <button class="country-tab ${currentTab === 'global' ? 'active' : ''}" onclick="switchTab('global')" id="tabGlobal">
                    <i class="fas fa-globe"></i>
                    Global (${allGlobals.length})
                </button>
            </div>
            
            <div class="countries-container" id="countriesContainer">
                ${renderContentByTab()}
            </div>
            
            <div class="view-all-countries">
                <button class="btn-view-all" onclick="showAllDestinations()">
                    <i class="fas fa-list"></i>
                    View all destinations
                </button>
            </div>
        </div>
    `;
    
    noResultsDiv.innerHTML = html;
}

function renderContentByTab() {
    let items = [];
    
    console.log('🎨 Rendering tab:', currentTab);
    
    switch(currentTab) {
        case 'countries':
            items = allCountries.slice(0, 9);
            break;
        case 'regions':
            items = allRegions.slice(0, 9);
            break;
        case 'global':
            items = allGlobals.slice(0, 9);
            break;
    }
    
    if (items.length === 0) {
        return `
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-info-circle"></i></div>
                <h3 class="empty-title">No ${currentTab} available</h3>
                <p class="empty-description">Try switching to another tab</p>
            </div>
        `;
    }
    
    return `
        <div class="countries-grid">
            ${items.map(item => renderDestinationCard(item)).join('')}
        </div>
    `;
}

function renderDestinationCard(item) {
    const flagIcon = getCountryFlag(item.name, item.location_code || '');
    const typeClass = (item.type || 'local').toLowerCase();
    
    // Determine click action based on type
    let clickAction = '';
    if (item.type === 'LOCAL') {
        clickAction = `selectCountry('${item.name.replace(/'/g, "\\'")}')`;
    } else if (item.type === 'REGIONAL') {
        clickAction = `selectRegion('${item.name.replace(/'/g, "\\'")}', 'REGIONAL')`;
    } else if (item.type === 'GLOBAL') {
        clickAction = `selectRegion('${item.name.replace(/'/g, "\\'")}', 'GLOBAL')`;
    } else {
        // For region/global main items (no type field)
        if (currentTab === 'regions') {
            clickAction = `selectRegion('${item.name.replace(/'/g, "\\'")}', 'REGIONAL')`;
        } else if (currentTab === 'global') {
            clickAction = `selectRegion('${item.name.replace(/'/g, "\\'")}', 'GLOBAL')`;
        } else {
            clickAction = `selectCountry('${item.name.replace(/'/g, "\\'")}')`;
        }
    }
    
    return `
        <div class="country-card ${typeClass}" onclick="${clickAction}">
            <div class="country-card-left">
                <div class="country-flag">
                    ${flagIcon}
                </div>
                <div class="country-info">
                    <div class="country-name">${item.name}</div>
                    <div class="country-count">${item.package_count} packages</div>
                </div>
            </div>
            <div class="country-card-right">
                <i class="fas fa-chevron-right"></i>
            </div>
        </div>
    `;
}

function getCountryFlag(countryName, locationCode = '') {
    // Jika ada location_code, gunakan untuk flag dari API
    if (locationCode && locationCode.trim() !== '') {
        const cleanCode = locationCode.trim().toLowerCase();
        // Return image element for API flag
        return `<img src="https://p.qrsim.net/img/flags/${cleanCode}.png" 
                     alt="${countryName}" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius-full);">
                <span class="country-flag-text" style="display: none;">${countryName.substring(0, 2).toUpperCase()}</span>`;
    }
    
    // Fallback untuk emoji flags atau initials
    const countryFlags = {
        // Asia
        'Indonesia': '🇮🇩',
        'Singapore': '🇸🇬',
        'Malaysia': '🇲🇾',
        'Thailand': '🇹🇭',
        'Philippines': '🇵🇭',
        'Vietnam': '🇻🇳',
        'Japan': '🇯🇵',
        'South Korea': '🇰🇷',
        'China': '🇨🇳',
        'Hong Kong': '🇭🇰',
        'Taiwan': '🇹🇼',
        'India': '🇮🇳',
        'Pakistan': '🇵🇰',
        'Bangladesh': '🇧🇩',
        'Sri Lanka': '🇱🇰',
        'Myanmar': '🇲🇲',
        'Cambodia': '🇰🇭',
        'Laos': '🇱🇦',
        'Brunei': '🇧🇳',
        'Macao': '🇲🇴',
        
        // Regional/Global packages
        'Asia': '🌏',
        'Asia-20': '🌏',
        'Asia (7 areas)': '🌏',
        'Asia (12 areas)': '🌏',
        'Central Asia': '🌏',
        'Europe': '🇪🇺',
        'Europe(30+ areas)': '🇪🇺',
        'Americas': '🌎',
        'North America': '🌎',
        'South America': '🌎',
        'USA & Canada': '🌎',
        'Africa': '🌍',
        'Australia & New Zealand': '🇦🇺',
        'Middle East': '🕌',
        'Gulf Region': '🕌',
        'Caribbean': '🏝️',
        'Balkans': '🇪🇺',
        'China (mainland HK Macao)': '🇨🇳',
        'China mainland & Japan & South Korea': '🌏',
        'Singapore & Malaysia & Thailand': '🌏',
        'Global': '🌍',
        'Global (120+ areas)': '🌍',
        'Global139': '🌍',
        'Worldwide': '🌍'
    };
    
    const flag = countryFlags[countryName];
    
    if (flag) {
        return `<span style="font-size: 1.4rem;">${flag}</span>`;
    } else {
        // Fallback: ambil 2 huruf pertama dari nama
        const initials = countryName.substring(0, 2).toUpperCase();
        return `<span class="country-flag-text">${initials}</span>`;
    }
}

// ===========================================
// SCROLL UTILITIES
// ===========================================

function scrollToTop(smooth = true) {
    if (smooth) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    } else {
        window.scrollTo(0, 0);
    }
}

function scrollToElement(elementId, offset = 0) {
    const element = document.getElementById(elementId);
    if (element) {
        const elementPosition = element.offsetTop - offset;
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }
}

function scrollToFilters() {
    const filtersSection = document.querySelector('.filter-section');
    if (filtersSection) {
        const headerHeight = 80; // Approximate header height
        const elementPosition = filtersSection.offsetTop - headerHeight;
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }
}

// ===========================================
// NAVIGATION FUNCTIONS (Updated dengan scroll)
// ===========================================

function selectRegion(regionName, type) {
    console.log('🗺️ Selected region:', regionName, 'Type:', type);
    
    // Scroll to top sebelum fetch
    scrollToTop();
    
    // Set lastActiveTab based on type before fetching
    if (type === 'REGIONAL') {
        lastActiveTab = 'regions';
    } else if (type === 'GLOBAL') {
        lastActiveTab = 'global';
    }
    // Langsung fetch packages untuk region, tidak ada step variants lagi
    fetchPackagesByRegion(regionName, type);
}

function selectCountry(countryName) {
    console.log('✅ Selected final destination:', countryName);
    
    // Scroll to top sebelum fetch
    scrollToTop();
    
    lastActiveTab = 'countries'; // Set before fetching
    fetchPackagesByCountry(countryName);
}

function goBackToCountrySelection() {
    console.log('🔙 Going back to country selection, target tab:', lastActiveTab);
    
    selectedCountry = null;
    selectedRegion = null;
    selectedGlobal = null;
    currentPackages = [];
    navigationLevel = 'main';
    
    // Set currentTab to lastActiveTab so we return to the correct tab
    currentTab = lastActiveTab;
    
    // Remove back button
    const backButton = document.querySelector('.back-button');
    if (backButton) {
        backButton.remove();
    }
    
    // Reset search
    resetSearchState();
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.placeholder = 'Search countries (e.g., Indonesia, Singapore, Malaysia)';
    }
    
    // Hide packages and filters
    const packagesDiv = document.getElementById('packagesList');
    const filtersSection = document.querySelector('.filter-section');
    if (packagesDiv) packagesDiv.style.display = 'none';
    if (filtersSection) filtersSection.style.display = 'none';
    
    // Show country selection with correct tab
    showCountrySelection();
    renderCountrySelection();
    
    // Scroll to top setelah render
    setTimeout(() => {
        scrollToTop();
    }, 100);
}

function switchTab(tab) {
    console.log('🔄 Switching to tab:', tab);
    
    if (navigationLevel !== 'main') {
        return; // Don't allow tab switching when not in main view
    }
    
    currentTab = tab;
    lastActiveTab = tab; // Update lastActiveTab when user manually switches
    
    // Update tab buttons
    document.querySelectorAll('.country-tab').forEach(btn => btn.classList.remove('active'));
    
    let tabId = '';
    switch(tab) {
        case 'countries':
            tabId = 'tabCountries';
            break;
        case 'regions':
            tabId = 'tabRegions';
            break;
        case 'global':
            tabId = 'tabGlobal';
            break;
    }
    
    const targetTab = document.getElementById(tabId);
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    // Re-render content
    const container = document.getElementById('countriesContainer');
    if (container) {
        container.innerHTML = renderContentByTab();
    }
    
    // Scroll to country tabs setelah switch
    setTimeout(() => {
        const tabsSection = document.querySelector('.country-tabs');
        if (tabsSection) {
            const headerHeight = 80;
            const elementPosition = tabsSection.offsetTop - headerHeight;
            window.scrollTo({
                top: elementPosition,
                behavior: 'smooth'
            });
        }
    }, 100);
}

function showAllDestinations() {
    console.log('📋 Showing all destinations');
    
    const noResultsDiv = document.getElementById('noResults');
    if (!noResultsDiv) return;
    
    let html = `
        <div class="country-selection">
            <div class="search-results-header">
                <h2 class="search-results-title">All Available Destinations</h2>
                <p class="search-results-subtitle">Browse all countries, regions, and global packages</p>
                <button class="btn-back" onclick="renderCountrySelection()" style="margin-top: 1rem;">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Categories</span>
                </button>
            </div>
            
            <div class="country-groups">
    `;
    
    if (allCountries.length > 0) {
        html += createDestinationGroup('Countries', 'fas fa-flag', allCountries, 'Available in specific countries only');
    }
    
    if (allRegions.length > 0) {
        html += createDestinationGroup('Regional Packages', 'fas fa-globe-asia', allRegions, 'Available across multiple countries in a region');
    }
    
    if (allGlobals.length > 0) {
        html += createDestinationGroup('Global Packages', 'fas fa-globe', allGlobals, 'Available worldwide or in multiple regions');
    }
    
    html += `</div></div>`;
    noResultsDiv.innerHTML = html;
}

function createDestinationGroup(title, icon, items, subtitle) {
    return `
        <div class="country-group">
            <div class="country-group-header">
                <h3 class="country-group-title">
                    <i class="${icon}"></i>
                    ${title} (${items.length})
                </h3>
                <p class="country-group-subtitle">${subtitle}</p>
            </div>
            <div class="countries-grid">
                ${items.map(item => renderDestinationCard(item)).join('')}
            </div>
        </div>
    `;
}

function filterCountries(query) {
    console.log('🔍 Filtering countries with query:', query);
    
    if (!query) {
        renderCountrySelection();
        return;
    }
    
    // Filter all items based on query
    const filteredCountries = allCountries.filter(item => 
        item.name.toLowerCase().includes(query)
    );
    const filteredRegions = allRegions.filter(item => 
        item.name.toLowerCase().includes(query)
    );
    const filteredGlobals = allGlobals.filter(item => 
        item.name.toLowerCase().includes(query)
    );
    
    const totalResults = filteredCountries.length + filteredRegions.length + filteredGlobals.length;
    
    const noResultsDiv = document.getElementById('noResults');
    if (!noResultsDiv) return;
    
    if (totalResults > 0) {
        let html = `
            <div class="country-selection">
                <div class="search-results-header">
                    <h2 class="search-results-title">Search Results for "${query}"</h2>
                    <p class="search-results-subtitle">Found ${totalResults} destinations</p>
                    <button class="btn-back" onclick="renderCountrySelection()" style="margin-top: 1rem;">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Categories</span>
                    </button>
                </div>
                <div class="country-groups">
        `;
        
        if (filteredCountries.length > 0) {
            html += createDestinationGroup('Countries', 'fas fa-flag', filteredCountries, 'Country-specific packages');
        }
        if (filteredRegions.length > 0) {
            html += createDestinationGroup('Regional Packages', 'fas fa-globe-asia', filteredRegions, 'Multi-country regional packages');
        }
        if (filteredGlobals.length > 0) {
            html += createDestinationGroup('Global Packages', 'fas fa-globe', filteredGlobals, 'Worldwide coverage packages');
        }
        
        html += `</div></div>`;
        noResultsDiv.innerHTML = html;
    } else {
        noResultsDiv.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-search"></i></div>
                <h3 class="empty-title">No destinations found</h3>
                <p class="empty-description">No destinations match "${query}"</p>
                <button class="btn-retry" onclick="renderCountrySelection()" style="margin-top: 1rem;">
                    <i class="fas fa-arrow-left"></i>
                    Back to Categories
                </button>
            </div>
        `;
    }
    
    // Scroll to search results setelah render
    setTimeout(() => {
        const searchSection = document.querySelector('.search-section');
        if (searchSection) {
            const headerHeight = 80;
            const elementPosition = searchSection.offsetTop - headerHeight;
            window.scrollTo({
                top: elementPosition,
                behavior: 'smooth'
            });
        }
    }, 100);
}

// ===========================================
// LOADING & ERROR STATES
// ===========================================

function showLoadingState() {
   const noResultsDiv = document.getElementById('noResults');
   if (!noResultsDiv) return;
   
   noResultsDiv.innerHTML = `
       <div class="loading-state">
           <div class="loading-icon">
               <i class="fas fa-spinner fa-spin"></i>
           </div>
           <h3 class="loading-title">Loading Packages...</h3>
           <p class="loading-description">Please wait while we fetch eSIM packages for your destination</p>
       </div>
   `;
   noResultsDiv.style.display = 'block';
}

function hideLoadingState() {
   // Loading state will be hidden when packages are displayed
}

function showError(message) {
   const noResultsDiv = document.getElementById('noResults');
   if (!noResultsDiv) return;
   
   noResultsDiv.innerHTML = `
       <div class="error-state">
           <div class="error-icon">
               <i class="fas fa-exclamation-triangle"></i>
           </div>
           <h3 class="error-title">Oops! Something went wrong</h3>
           <p class="error-description">${message}</p>
           <button class="btn-retry" onclick="location.reload()">
               <i class="fas fa-redo"></i>
               Try Again
           </button>
       </div>
   `;
   noResultsDiv.style.display = 'block';
}

// ===========================================
// EVENT LISTENERS
// ===========================================

function setupEventListeners() {
    console.log('🎯 Setting up event listeners');
    
    const searchInput = document.getElementById('searchInput');
    const searchBox = searchInput?.closest('.search-box');
    const searchClear = document.getElementById('searchClear');
    
    if (searchInput && searchBox) {
        let searchTimeout;
        
        // Debounce yang lebih agresif untuk mobile
        const isMobile = window.innerWidth <= 768;
        const debounceTime = isMobile ? 500 : 300; // Lebih lama di mobile
        
        function updateSearchBoxState() {
            const hasContent = searchInput.value.length > 0;
            searchBox.classList.toggle('has-content', hasContent);
            if (searchClear) {
                searchClear.style.display = hasContent ? 'block' : 'none';
            }
        }
        
        // Optimized input handler
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            updateSearchBoxState();
            
            searchTimeout = setTimeout(() => {
                if (selectedCountry || selectedRegion || selectedGlobal) {
                    currentFilters.searchQuery = this.value.toLowerCase().trim();
                    filterPackages();
                } else {
                    filterCountries(this.value.toLowerCase().trim());
                }
            }, debounceTime);
        }, { passive: true });
        
        // Passive event listeners untuk scroll performa
        searchInput.addEventListener('focus', function() {
            searchBox.classList.add('focused');
        }, { passive: true });
        
        searchInput.addEventListener('blur', function() {
            searchBox.classList.remove('focused');
        }, { passive: true });
        
        updateSearchBoxState();
    }
    
    if (searchClear) {
        searchClear.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            this.style.display = 'none';
            
            if (selectedCountry || selectedRegion || selectedGlobal) {
                currentFilters.searchQuery = '';
                filterPackages();
            } else {
                renderCountrySelection();
            }
        });
    }
    
    // Rest of the event listeners dengan passive options
    const sortOrder = document.getElementById('sortOrder');
    if (sortOrder) {
        sortOrder.addEventListener('change', function() {
            currentFilters.sortOrder = this.value;
            // Delay untuk mobile
            if (window.innerWidth <= 768) {
                setTimeout(() => filterPackages(), 100);
            } else {
                filterPackages();
            }
        }, { passive: true });
    }
}

// ===========================================
// FILTER FUNCTIONS (untuk packages)
// ===========================================

// Add this to your reset functions
function resetSearchState() {
    const searchInput = document.getElementById('searchInput');
    const searchBox = searchInput?.closest('.search-box');
    const searchClear = document.getElementById('searchClear');
    
    if (searchInput) {
        searchInput.value = '';
        if (searchBox) {
            searchBox.classList.remove('has-content', 'focused');
        }
    }
    
    if (searchClear) {
        searchClear.style.display = 'none';
    }
}

function toggleFilters() {
   const filterContent = document.getElementById('filterContent');
   const toggleIcon = document.getElementById('filterToggleIcon');
   const toggleText = document.getElementById('filterToggleText');
   
   if (!filterContent || !toggleIcon || !toggleText) return;
   
   filtersVisible = !filtersVisible;
   
   if (filtersVisible) {
       filterContent.style.display = 'block';
       filterContent.style.animation = 'slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
       toggleIcon.innerHTML = '<i class="fas fa-eye-slash"></i>';
       toggleText.textContent = 'Hide Filters';
   } else {
       filterContent.style.animation = 'slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
       setTimeout(() => {
           filterContent.style.display = 'none';
       }, 300);
       toggleIcon.innerHTML = '<i class="fas fa-eye"></i>';
       toggleText.textContent = 'Show Filters';
   }
}

// Updated setPackageType function with error handling
function setPackageType(type) {
    currentFilters.packageType = type;
    
    // Update button states with safe operations
    safeToggleClass('allPackageBtn', 'active', type === 'all');
    safeToggleClass('regularBtn', 'active', type === 'regular');
    safeToggleClass('unlimitedBtn', 'active', type === 'unlimited');
    
    filterPackages();
}

function setTikTokFilter(filter) {
   currentFilters.tiktokFilter = filter;
   
   safeToggleClass('allTikTokBtn', 'active', filter === 'all');
   safeToggleClass('tiktokSupportedBtn', 'active', filter === 'supported');
   safeToggleClass('tiktokNotSupportedBtn', 'active', filter === 'not-supported');
   
   filterPackages();
}

// Update filterPackages function
function filterPackages() {
    if (!selectedCountry && !selectedRegion && !selectedGlobal || currentPackages.length === 0) {
        console.log('❌ No destination selected or no packages loaded');
        return;
    }
    
    console.log('🔍 Filtering packages for:', selectedCountry || selectedRegion || selectedGlobal);
    
    const query = currentFilters.searchQuery;
    const packagesDiv = document.getElementById('packagesList');
    const noResultsDiv = document.getElementById('noResults');

    // Filter packages
    let filteredPackages = currentPackages.filter(pkg => {
        let matchesPackageType = true;
        if (currentFilters.packageType !== 'all') {
            const packageIsUnlimited = isUnlimitedPackage(pkg);
            matchesPackageType = (currentFilters.packageType === 'unlimited') === packageIsUnlimited;
        }
        
        const locationCodes = (pkg.location_code || '').toLowerCase();
        const locationNames = (pkg.location_name || '').toLowerCase();
        const name = (pkg.name || '').toLowerCase();
        const description = (pkg.description || '').toLowerCase();

        const matchesSearch = query.length === 0 || 
            locationCodes.includes(query) ||
            locationNames.includes(query) ||
            name.includes(query) ||
            description.includes(query);

        const matchesTikTok = currentFilters.tiktokFilter === 'all' || 
            (currentFilters.tiktokFilter === 'supported' && supportsTikTok(pkg)) ||
            (currentFilters.tiktokFilter === 'not-supported' && !supportsTikTok(pkg));

        return matchesPackageType && matchesSearch && matchesTikTok;
    });

    // Sort packages
    filteredPackages = sortPackages(filteredPackages, currentFilters.sortOrder, query);
    
    // DEBUG: Log urutan packages
    console.log('📋 Package order after sorting:');
    filteredPackages.slice(0, 10).forEach((pkg, index) => {
        const isHkIp = !supportsTikTok(pkg);
        const isUnlimited = isUnlimitedPackage(pkg);
        console.log(`${index + 1}. ${pkg.name} | HK IP: ${isHkIp} | Unlimited: ${isUnlimited} | Volume: ${pkg.volume} | Price: $${pkg.price_usd}`);
    });

    // Render packages...
    if (filteredPackages.length > 0) {
        if (packagesDiv) packagesDiv.style.display = 'grid';
        if (noResultsDiv) noResultsDiv.style.display = 'none';
        
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile && filteredPackages.length > 20) {
            const pageSize = 10;
            const firstBatch = filteredPackages.slice(0, pageSize);
            
            if (packagesDiv) {
                packagesDiv.innerHTML = firstBatch.map(pkg => renderPackage(pkg, query)).join('');
            
                if (filteredPackages.length > pageSize) {
                    const loadMoreBtn = document.createElement('div');
                    loadMoreBtn.className = 'load-more-container';
                    loadMoreBtn.innerHTML = `
                        <button class="btn-load-more" onclick="loadMorePackages(${pageSize})">
                            <i class="fas fa-plus"></i>
                            Load More (${filteredPackages.length - pageSize} remaining)
                        </button>
                    `;
                    packagesDiv.appendChild(loadMoreBtn);
                    
                    window.remainingPackages = filteredPackages.slice(pageSize);
                    window.currentPageSize = pageSize;
                }
            }
        } else {
            if (packagesDiv) {
                packagesDiv.innerHTML = filteredPackages.map(pkg => renderPackage(pkg, query)).join('');
                
                if (!isMobile) {
                    animatePackageItems();
                }
            }
        }
        
    } else {
        // No results...
        if (packagesDiv) packagesDiv.style.display = 'none';
        if (noResultsDiv) noResultsDiv.style.display = 'block';
        
        let selectionName = selectedCountry || selectedRegion || selectedGlobal;
        
        if (noResultsDiv) {
            noResultsDiv.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-search"></i></div>
                    <h3 class="empty-title">No packages found</h3>
                    <p class="empty-description">No packages match your criteria in ${selectionName}</p>
                    <button class="btn-retry" onclick="resetAllFilters()">
                        <i class="fas fa-redo"></i>
                        Reset Filters
                    </button>
                </div>
            `;
        }
    }
}

// Function baru untuk load more
function loadMorePackages(currentSize) {
    if (!window.remainingPackages || window.remainingPackages.length === 0) return;
    
    const packagesDiv = document.getElementById('packagesList');
    if (!packagesDiv) return;
    
    const loadMoreContainer = packagesDiv.querySelector('.load-more-container');
    
    // Remove load more button
    if (loadMoreContainer) {
        loadMoreContainer.remove();
    }
    
    const nextBatch = window.remainingPackages.slice(0, 10);
    const newHTML = nextBatch.map(pkg => renderPackage(pkg, currentFilters.searchQuery)).join('');
    
    // Append new packages
    packagesDiv.insertAdjacentHTML('beforeend', newHTML);
    
    // Update remaining
    window.remainingPackages = window.remainingPackages.slice(10);
    
    // Add load more button if still have packages
    if (window.remainingPackages.length > 0) {
        const loadMoreBtn = document.createElement('div');
        loadMoreBtn.className = 'load-more-container';
        loadMoreBtn.innerHTML = `
            <button class="btn-load-more" onclick="loadMorePackages(10)">
                <i class="fas fa-plus"></i>
                Load More (${window.remainingPackages.length} remaining)
            </button>
        `;
        packagesDiv.appendChild(loadMoreBtn);
    }
}

// Updated updateFilterCounts function with error handling
function updateFilterCounts() {
    if (currentPackages.length === 0) {
        return;
    }
    
    try {
        const regularPackages = currentPackages.filter(pkg => !isUnlimitedPackage(pkg));
        const unlimitedPackages = currentPackages.filter(pkg => isUnlimitedPackage(pkg));
        
        // Update package type counts with safe operations
        safeSetTextContent('allPackageCount', currentPackages.length);
        safeSetTextContent('regularCount', regularPackages.length);
        safeSetTextContent('unlimitedCount', unlimitedPackages.length);
        
        const allTikTokCount = currentPackages.length;
        const tiktokSupportedCount = currentPackages.filter(supportsTikTok).length;
        const tiktokNotSupportedCount = allTikTokCount - tiktokSupportedCount;
        
        // Update TikTok counts with safe operations
        safeSetTextContent('allTikTokCount', allTikTokCount);
        safeSetTextContent('tiktokSupportedCount', tiktokSupportedCount);
        safeSetTextContent('tiktokNotSupportedCount', tiktokNotSupportedCount);
        
        console.log('📊 Filter counts updated:', {
            all: currentPackages.length,
            regular: regularPackages.length,
            unlimited: unlimitedPackages.length,
            tiktokSupported: tiktokSupportedCount,
            tiktokNotSupported: tiktokNotSupportedCount
        });
        
    } catch (error) {
        console.error('Error updating filter counts:', error);
    }
}

function resetAllFilters() {
    currentFilters = {
        packageType: 'all',
        tiktokFilter: 'all',
        searchQuery: '',
        sortOrder: 'relevance'
    };
    
    const searchInput = safeGetElement('searchInput');
    const sortOrder = safeGetElement('sortOrder');
    
    if (searchInput) searchInput.value = '';
    if (sortOrder) sortOrder.value = 'relevance';
    
    resetSearchState();
    
    setPackageType('all');
    setTikTokFilter('all');
    
    filterPackages();
}

// ===========================================
// HELPER FUNCTIONS
// ===========================================

function isUnlimitedPackage(pkg) {
    const supportTopUpType = parseInt(pkg.support_topup_type);
    const fupPolicy = (pkg.fup_policy || '').trim();
    
    // Debug setiap package
    console.log(`📦 ${pkg.name} | support_topup_type: ${supportTopUpType} | fup_policy: "${fupPolicy}" | isUnlimited: ${supportTopUpType === 1 && fupPolicy !== ''}`);
    
    // Dayplans/Unlimited: support_topup_type = 1 DAN ada fup_policy
    return supportTopUpType === 1 && fupPolicy !== '';
}

function supportsTikTok(pkg) {
   const ipExport = (pkg.ip_export || '').toLowerCase();
   return ipExport !== 'hk';
}

function formatBytes(bytes) {
   if (bytes === 0) return '0 Bytes';
   const k = 1024;
   const dm = 2;
   const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
   const i = Math.floor(Math.log(bytes) / Math.log(k));
   return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function sortPackages(packages, sortBy, query = '') {
    const sorted = [...packages];
    
    switch(sortBy) {
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
            // PRIORITAS TEGAS: HK IP → Non-HK IP → Regular → Dayplans
            return sorted.sort((a, b) => {
                // UNTUK COUNTRIES: LOCAL packages dulu
                if (selectedCountry && !selectedRegion && !selectedGlobal) {
                    const typeOrder = {'LOCAL': 1, 'REGIONAL': 2, 'GLOBAL': 3};
                    const aTypeOrder = typeOrder[a.type] || 4;
                    const bTypeOrder = typeOrder[b.type] || 4;
                    
                    if (aTypeOrder !== bTypeOrder) {
                        return aTypeOrder - bTypeOrder;
                    }
                }
                
                // STEP 1: IP Location Priority
                const aIsHkIp = !supportsTikTok(a); // true = HK IP, false = Non-HK
                const bIsHkIp = !supportsTikTok(b);
                
                if (aIsHkIp !== bIsHkIp) {
                    // HK IP dulu: true (1) < false (0), jadi kita balik
                    return aIsHkIp ? -1 : 1; // HK IP (true) dapat nilai -1 (lebih kecil)
                }
                
                // STEP 2: Package Type Priority - REGULAR DULU!
                const aIsUnlimited = isUnlimitedPackage(a); // true = Dayplans, false = Regular
                const bIsUnlimited = isUnlimitedPackage(b);
                
                if (aIsUnlimited !== bIsUnlimited) {
                    // Regular dulu: false (0) < true (1), jadi langsung compare
                    return aIsUnlimited ? 1 : -1; // Regular (false) dapat nilai -1 (lebih kecil)
                }
                
                // STEP 3: Volume Priority (kecil ke besar)
                const aVolume = parseInt(a.volume) || 0;
                const bVolume = parseInt(b.volume) || 0;
                if (aVolume !== bVolume) {
                    return aVolume - bVolume;
                }
                
                // STEP 4: Price Priority (murah ke mahal)
                const aPrice = parseFloat(a.price_usd) || 0;
                const bPrice = parseFloat(b.price_usd) || 0;
                if (aPrice !== bPrice) {
                    return aPrice - bPrice;
                }
                
                // STEP 5: Name (A-Z)
                return a.name.localeCompare(b.name);
            });
    }
}

function renderPackage(pkg, query = '') {
    const tiktokSupported = supportsTikTok(pkg);
    const isUnlimited = isUnlimitedPackage(pkg);
    const supportTopUpType = parseInt(pkg.support_topup_type);
    const fupPolicy = (pkg.fup_policy || '').trim();
    
    // Package type badges
    let packageTypeBadge = '';
    if (isUnlimited) {
        packageTypeBadge = '<span class="special-badge unlimited"><i class="fas fa-infinity"></i> Unlimited</span>';
    } else {
        if (supportTopUpType === 2) {
            packageTypeBadge = '<span class="special-badge dayplans"><i class="fas fa-sync-alt"></i> TopUp</span>';
        } else {
            packageTypeBadge = '<span class="special-badge dayplans"><i class="fas fa-mobile-alt"></i> Regular</span>';
        }
    }
    
    // Type badge based on package type dengan prioritas untuk LOCAL
    let typeBadge = '';
    let isLocalPackage = false;
    switch(pkg.type) {
        case 'LOCAL':
            typeBadge = '<span class="relevance-badge exact-match"><i class="fas fa-flag"></i> Local</span>';
            isLocalPackage = true;
            break;
        case 'REGIONAL':
            typeBadge = '<span class="relevance-badge high-relevance"><i class="fas fa-globe-asia"></i> Regional</span>';
            break;
        case 'GLOBAL':
            typeBadge = '<span class="relevance-badge medium-relevance"><i class="fas fa-globe"></i> Global</span>';
            break;
    }
    
    // TikTok badge dengan prioritas untuk HK IP
    let tiktokBadge = '';
    if (!tiktokSupported) {
        // HK IP - prioritas lebih tinggi secara visual
        tiktokBadge = '<span class="tiktok-badge hk-ip priority"><i class="fas fa-star"></i> HK IP</span>';
    } else {
        // Non-HK IP (TikTok supported)
        tiktokBadge = '<span class="tiktok-badge supported"><i class="fab fa-tiktok"></i> TikTok ✓</span>';
    }
    
    const priceFormatted = parseInt(pkg.price_idr).toLocaleString('id-ID');
    
    // Location display
    let locationDisplay = '';
    if (pkg.type === 'REGIONAL' || pkg.type === 'GLOBAL') {
        locationDisplay = `
            <div class="package-info-item">
                <div class="package-info-icon"><i class="fas fa-globe"></i></div>
                <div class="package-info-content">
                    <span class="package-info-label">Coverage</span>
                    <button class="country-btn" onclick="showCountryModal('${pkg.package_code}'); return false;" type="button">
                        <i class="fas fa-list"></i>
                        View Countries
                    </button>
                </div>
            </div>
        `;
    } else {
        const locationText = pkg.location_name || pkg.location_code;
        const shortLocation = locationText.length > 20 ? locationText.substring(0, 20) + '...' : locationText;
        locationDisplay = `
            <div class="package-info-item">
                <div class="package-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="package-info-content">
                    <span class="package-info-label">Location</span>
                    <span class="package-info-value">${shortLocation}</span>
                </div>
            </div>
        `;
    }
    
    // Package class dengan prioritas untuk LOCAL dan HK IP
    let packageClass = 'package-item';
    if (isUnlimited) {
        packageClass += ' special-package';
    }
    if (isLocalPackage && selectedCountry && !selectedRegion && !selectedGlobal) {
        packageClass += ' local-priority'; // LOCAL packages untuk countries
    }
    if (!tiktokSupported) {
        packageClass += ' hk-ip-priority'; // HK IP mendapat prioritas
    }
    
    return `
        <div class="${packageClass}">
            <div class="package-header">
                <h4>${pkg.name}</h4>
                <div class="package-indicators">
                    ${isLocalPackage && selectedCountry && !selectedRegion && !selectedGlobal ? '<div class="local-indicator"><i class="fas fa-flag"></i></div>' : ''}
                    ${!tiktokSupported ? '<div class="hk-ip-indicator"><i class="fas fa-star"></i></div>' : ''}
                </div>
            </div>
            <div class="package-body">
                <div class="package-badges">
                    ${typeBadge}
                    ${tiktokBadge}
                    ${packageTypeBadge}
                </div>
                
                <div class="package-info">
                    <div class="package-info-item highlight-volume">
                        <div class="package-info-icon"><i class="fas fa-database"></i></div>
                        <div class="package-info-content">
                            <span class="package-info-label">Data</span>
                            <span class="package-info-value volume-highlight">${formatBytes(parseInt(pkg.volume))}</span>
                        </div>
                    </div>
                    
                    <div class="package-info-item">
                        <div class="package-info-icon"><i class="fas fa-clock"></i></div>
                        <div class="package-info-content">
                            <span class="package-info-label">Duration</span>
                            <span class="package-info-value">${pkg.duration} ${pkg.duration_unit}</span>
                        </div>
                    </div>
                    
                    ${locationDisplay}
                    
                    <div class="package-info-item ip-info">
                        <div class="package-info-icon"><i class="fas fa-network-wired"></i></div>
                        <div class="package-info-content">
                            <span class="package-info-label">IP Location</span>
                            <span class="package-info-value ${!tiktokSupported ? 'hk-ip-text' : 'non-hk-ip-text'}">
                                ${!tiktokSupported ? 'Hong Kong IP' : 'Non-HK IP'}
                            </span>
                        </div>
                    </div>
                    
                    ${isUnlimited && fupPolicy ? `
                    <div class="package-info-item">
                        <div class="package-info-icon"><i class="fas fa-info-circle"></i></div>
                        <div class="package-info-content">
                            <span class="package-info-label">FUP</span>
                            <span class="package-info-value">${fupPolicy}</span>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="package-info-item">
                        <div class="package-info-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="package-info-content">
                            <span class="package-info-label">Price</span>
                            <span class="package-info-value price">Rp ${priceFormatted}</span>
                        </div>
                    </div>
                </div>
                
                <div class="package-actions">
                    <button class="btn-primary" onclick="showOrderModal('${pkg.package_code}', ${isUnlimited})">
                        <i class="${isUnlimited ? 'fas fa-infinity' : 'fas fa-shopping-cart'}"></i>
                        <span class="btn-text">${isUnlimited ? 'Buy Unlimited' : 'Buy Now'}</span>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// ===========================================
// MODAL FUNCTIONS
// ===========================================

function showCountryModal(packageCode) {
   const pkg = currentPackages.find(p => p.package_code === packageCode);
   if (!pkg) {
       console.error('Package not found:', packageCode);
       return;
   }
   
   const modal = document.getElementById('countryModal');
   const countryList = document.getElementById('countryList');
   const searchInput = document.getElementById('countrySearchInput');
   
   if (!modal || !countryList || !searchInput) return;
   
   const locationName = pkg.location_name || '';
   let countries = [];
   
   if (!locationName) {
       countries = ['Location information not available'];
   } else {
       const separators = [',', ' + ', ' & ', '/', '|', ';', ' - ', ' and ', ' or '];
       
       let found = false;
       for (const separator of separators) {
           if (locationName.includes(separator)) {
               countries = locationName.split(separator).map(country => country.trim()).filter(country => country);
               found = true;
               break;
           }
       }
       
       if (!found) {
           countries = [locationName];
       }
   }
   
   countries = [...new Set(countries.filter(country => country && country.length > 0))];
   
   if (countries.length === 0) {
       countries = ['No location information available'];
   }
   
   function renderCountries(filteredCountries = countries) {
       if (filteredCountries.length === 0) {
           countryList.innerHTML = '<div class="country-item">No countries found</div>';
           return;
       }
       
       countryList.innerHTML = filteredCountries.map(country => 
           `<div class="country-item">${country}</div>`
       ).join('');
   }
   
   searchInput.oninput = function() {
       const query = this.value.toLowerCase();
       const filtered = countries.filter(country => 
           country.toLowerCase().includes(query)
       );
       renderCountries(filtered);
   };
   
   renderCountries();
   searchInput.value = '';
   modal.style.display = 'flex';
   
   setTimeout(() => {
       searchInput.focus();
   }, 100);
}

// Update di index.js - function showOrderModal
function showOrderModal(packageCode, isUnlimited = false) {
    const pkg = currentPackages.find(p => p.package_code === packageCode);
    if (!pkg) {
        console.error('Package not found:', packageCode);
        return;
    }

    const orderAction = isUnlimited ? 'order_unlimited' : 'order_esim';
    const orderActionInput = document.getElementById('orderAction');
    const orderPackageCodeInput = document.getElementById('orderPackageCode');
    
    if (orderActionInput) orderActionInput.value = orderAction;
    if (orderPackageCodeInput) orderPackageCodeInput.value = packageCode;
    
    const modalTitle = isUnlimited ? 'Order Unlimited Package' : 'Order Regular Package';
    const orderModalTitle = document.getElementById('orderModalTitle');
    if (orderModalTitle) {
        orderModalTitle.innerHTML = `
            <i class="${isUnlimited ? 'fas fa-infinity' : 'fas fa-shopping-cart'}"></i>
            ${modalTitle}
        `;
    }
    
    const submitBtn = document.getElementById('orderSubmitBtn');
    if (submitBtn) {
        submitBtn.innerHTML = `
            <i class="${isUnlimited ? 'fas fa-infinity' : 'fas fa-shopping-cart'}"></i>
            <span class="btn-text">${isUnlimited ? 'Buy Unlimited' : 'Buy Now'}</span>
        `;
    }
    
    const countGroup = document.getElementById('countGroup');
    const periodGroup = document.getElementById('periodGroup');
    const countHint = document.getElementById('countHint');
    
    if (isUnlimited) {
        if (countGroup) countGroup.style.display = 'none';
        if (periodGroup) periodGroup.style.display = 'block';
        if (countHint) countHint.style.display = 'none';
    } else {
        if (countGroup) countGroup.style.display = 'block';
        if (periodGroup) periodGroup.style.display = 'none';
        if (countHint) countHint.style.display = 'block';
    }
    
    // Rest of existing code for package details...
    const supportTopUpType = parseInt(pkg.support_topup_type);
    const fupPolicy = (pkg.fup_policy || '').trim();
    
    let packageDetails = `
        <h4>Package Details</h4>
        <p><strong>Package Name:</strong> ${pkg.name}</p>
        <p><strong>Data:</strong> ${formatBytes(parseInt(pkg.volume))}</p>
        <p><strong>Duration:</strong> ${pkg.duration} ${pkg.duration_unit.toLowerCase()}</p>
        <p><strong>Price:</strong> Rp ${parseInt(pkg.price_idr).toLocaleString('id-ID')}</p>
        <p><strong>TikTok Support:</strong> ${supportsTikTok(pkg) ? 'Yes' : 'No (HK IP)'}</p>
    `;
    
    if (fupPolicy) {
        packageDetails += `<p><strong>FUP Policy:</strong> ${fupPolicy}</p>`;
    }
    
    if (isUnlimited) {
        packageDetails += `<p><strong>Type:</strong> Unlimited/Dayplans Package</p>`;
        packageDetails += `<p><strong>Price per Day:</strong> Rp ${Math.round(parseInt(pkg.price_idr)).toLocaleString('id-ID')}</p>`;
    }
    
    const orderPackageDetails = document.getElementById('orderPackageDetails');
    if (orderPackageDetails) {
        orderPackageDetails.innerHTML = packageDetails;
    }

    const modal = document.getElementById('orderModal');
    if (modal) {
        modal.style.display = 'flex';
        
        setTimeout(() => {
            const customerName = document.getElementById('customerName');
            if (customerName) customerName.focus();
        }, 100);
    }
}

function showSuccessModal(results, isUnlimited = false) {
   const packageType = isUnlimited ? 'Unlimited Package' : 'Regular Package';
   const isMultiple = Array.isArray(results) && results.length > 1;
   
   const successMessage = document.getElementById('successMessage');
   const singleLinkContainer = document.getElementById('singleLinkContainer');
   const multipleLinkContainer = document.getElementById('multipleLinkContainer');
   
   if (!successMessage || !singleLinkContainer || !multipleLinkContainer) return;
   
   if (isMultiple) {
       // Multiple orders
       successMessage.innerHTML = `
           <p>🎉 ${results.length} ${packageType}s have been ordered successfully!</p>
       `;
       
       // Show multiple links container
       singleLinkContainer.style.display = 'none';
       multipleLinkContainer.style.display = 'block';
       
       // Populate links list
       const linksList = document.getElementById('linksList');
       if (linksList) {
           linksList.innerHTML = results.map(result => {
               const tokenLink = `${window.location.origin}/detail.php?token=${result.token}`;
               return `
                   <div class="link-item">
                       <div class="link-item-info">
                           <div class="link-item-name">${result.customerName}</div>
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
       window.allOrderLinks = results.map(result => 
           `${result.customerName}: ${window.location.origin}/detail.php?token=${result.token}`
       );
       
   } else {
       // Single order (backward compatibility)
       const result = Array.isArray(results) ? results[0] : results;
       const tokenLink = `${window.location.origin}/detail.php?token=${result.token || result}`;
       
       successMessage.innerHTML = `
           <p>🎉 ${packageType} has been ordered successfully!</p>
       `;
       
       const tokenLinkInput = document.getElementById('tokenLink');
       if (tokenLinkInput) tokenLinkInput.value = tokenLink;
       singleLinkContainer.style.display = 'flex';
       multipleLinkContainer.style.display = 'none';
   }
   
   // Show provisioning note if any order is still provisioning
   const hasProvisioning = Array.isArray(results) ? 
       results.some(r => r.provisioning) : 
       (results.provisioning || false);
   
   const provisioningNote = document.getElementById('provisioningNote');
   if (provisioningNote) {
       provisioningNote.style.display = hasProvisioning ? 'block' : 'none';
   }
   
   closeModal('orderModal');
   const modal = document.getElementById('successModal');
   if (modal) modal.style.display = 'flex';
}

function closeModal(modalId) {
   const modal = document.getElementById(modalId);
   if (modal) {
       modal.style.display = 'none';
   }
}

function closeAllModals() {
   const modals = document.querySelectorAll('.modal');
   modals.forEach(modal => {
       modal.style.display = 'none';
   });
}

// ===========================================
// COPY FUNCTIONS
// ===========================================

function copyTokenLink() {
   const tokenInput = document.getElementById('tokenLink');
   if (!tokenInput) return;
   
   const copyBtn = tokenInput.nextElementSibling;
   const container = copyBtn?.closest('.link-container');
   
   tokenInput.select();
   
   try {
       navigator.clipboard.writeText(tokenInput.value).then(() => {
           showCopySuccess(container, 'Link copied successfully! 🎉');
           animateCopyButton(copyBtn);
       });
   } catch (err) {
       document.execCommand('copy');
       showCopySuccess(container, 'Link copied successfully! 🎉');
       animateCopyButton(copyBtn);
   }
}

function copyIndividualLink(link) {
   const clickedBtn = event.target.closest('.link-item-copy');
   const linkItem = clickedBtn?.closest('.link-item');
   
   try {
       navigator.clipboard.writeText(link).then(() => {
           showCopySuccess(linkItem, 'Link copied successfully! 📋');
           animateCopyButton(clickedBtn);
       });
   } catch (err) {
       const textArea = document.createElement('textarea');
       textArea.value = link;
       document.body.appendChild(textArea);
       textArea.select();
       document.execCommand('copy');
       document.body.removeChild(textArea);
       
       showCopySuccess(linkItem, 'Link copied successfully! 📋');
       animateCopyButton(clickedBtn);
   }
}

function copyAllLinks() {
   if (!window.allOrderLinks || window.allOrderLinks.length === 0) return;
   
   const allLinksText = window.allOrderLinks.join('\n');
   const copyAllBtn = document.querySelector('.copy-all-btn');
   const linksHeader = copyAllBtn?.closest('.links-header');
   
   try {
       navigator.clipboard.writeText(allLinksText).then(() => {
           showCopySuccess(linksHeader, `${window.allOrderLinks.length} links copied successfully! 🎊`);
           animateCopyButton(copyAllBtn);
       });
   } catch (err) {
       const textArea = document.createElement('textarea');
       textArea.value = allLinksText;
       document.body.appendChild(textArea);
       textArea.select();
       document.execCommand('copy');
       document.body.removeChild(textArea);
       
       showCopySuccess(linksHeader, `${window.allOrderLinks.length} links copied successfully! 🎊`);
       animateCopyButton(copyAllBtn);
   }
}

function showCopySuccess(container, message) {
   if (!container) return;
   
   const existingSuccess = container.querySelector('.copy-success');
   if (existingSuccess) {
       existingSuccess.remove();
   }
   
   const successEl = document.createElement('div');
   successEl.className = 'copy-success';
   successEl.textContent = message;
   
   container.style.position = 'relative';
   container.appendChild(successEl);
   
   setTimeout(() => {
       if (successEl.parentNode) {
           successEl.remove();
       }
   }, 2000);
}

function animateCopyButton(button) {
   if (!button) return;
   
   const originalHTML = button.innerHTML;
   
   button.classList.add('copied');
   button.innerHTML = `<i class="fas fa-check"></i> Copied!`;
   
   setTimeout(() => {
       button.classList.remove('copied');
       button.innerHTML = originalHTML;
   }, 1500);
}

// ===========================================
// ADDITIONAL FUNCTIONS
// ===========================================

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Close any open modals
        closeAllModals();
    }
});

// Performance optimization: Reduce motion for users who prefer it
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.style.setProperty('--transition-fast', '0.01ms');
    document.documentElement.style.setProperty('--transition-normal', '0.01ms');
}

// Error handling
window.addEventListener('error', function(e) {
    console.log('Index page script error:', e.error);
});

// Add loading state management
document.addEventListener('DOMContentLoaded', function() {
    // Remove any loading states
    document.body.classList.add('loaded');
    
    // Add loaded class for CSS animations
    const style = document.createElement('style');
    style.textContent = `
        body:not(.loaded) * {
            animation-play-state: paused !important;
        }
        
        .loaded {
            animation-play-state: running !important;
        }
    `;
    document.head.appendChild(style);
    // Setup form submission handler
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('orderSubmitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('', { // Submit ke current page (index.php)
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log('Order result:', result);
                
                if (result.success) {
                    closeModal('orderModal');
                    
                    if (result.is_multiple) {
                        // Multiple orders
                        showSuccessModal(result.orders, formData.get('action') === 'order_unlimited');
                    } else {
                        // Single order
                        showSuccessModal(result.orders[0], formData.get('action') === 'order_unlimited');
                    }
                } else {
                    alert('Order failed: ' + result.message);
                }
                
            } catch (error) {
                console.error('Order error:', error);
                alert('Order failed: Network error');
            } finally {
                // Reset button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
});