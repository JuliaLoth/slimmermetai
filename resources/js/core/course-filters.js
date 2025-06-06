// Course Filtering Functionality
console.log('=== COURSE FILTERS JS LOADED ===');
console.log('File loaded at:', new Date().toISOString());
console.log('Current URL:', window.location.href);
console.log('Document ready state:', document.readyState);
console.log('[DEBUG] Course filters module execution started');

function initializeCourseFilters() {
    console.log('=== COURSE FILTERS DEBUG START ===');
    console.log('[DEBUG] initializeCourseFilters function called');
    
    const filterButtons = document.querySelectorAll('.filter-btn');
    const courseCards = document.querySelectorAll('.course-card');
    const resultsCount = document.getElementById('results-count');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const noResults = document.getElementById('no-results');
    const coursesGrid = document.getElementById('courses-grid');
    
    console.log('Course filtering initialized');
    console.log('Document ready state:', document.readyState);
    console.log('Found', courseCards.length, 'course cards');
    console.log('Found', filterButtons.length, 'filter buttons');
    console.log('Results count element:', resultsCount ? 'found' : 'NOT FOUND');
    console.log('Clear filters button:', clearFiltersBtn ? 'found' : 'NOT FOUND');
    
    if (courseCards.length === 0) {
        console.log('[INFO] No course cards found on this page - course filtering not needed');
        return;
    }
    
    if (filterButtons.length === 0) {
        console.log('[INFO] No filter buttons found on this page - course filtering not available');
        return;
    }
    
    console.log('[DEBUG] About to add CSS styles for filtering');
    
    // Voeg CSS voor filtering toe als het nog niet bestaat
    if (!document.getElementById('course-filter-styles')) {
        const style = document.createElement('style');
        style.id = 'course-filter-styles';
        style.textContent = `
            .course-card.filtered-hidden {
                opacity: 0 !important;
                pointer-events: none !important;
                transform: scale(0.8) !important;
                transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            }
            .course-card.filtered-visible {
                opacity: 1 !important;
                pointer-events: auto !important;
                transform: scale(1) !important;
                transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            }
        `;
        document.head.appendChild(style);
        console.log('[DEBUG] CSS styles added for filtering');
    } else {
        console.log('[DEBUG] CSS styles already exist');
    }
    
    let activeFilters = {
        level: 'all',
        type: 'all'
    };
    
    console.log('[DEBUG] Active filters initialized:', activeFilters);
    
    // EERST: Definieer clearAllFilters functie VOORDAT deze wordt gebruikt
    console.log('[DEBUG] Defining clearAllFilters function EARLY');
    
    function clearAllFilters() {
        console.log('Clearing all filters');
        activeFilters = { level: 'all', type: 'all' };
        
        filterButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.value === 'all') {
                btn.classList.add('active');
            }
        });
        
        applyFilters();
    }
    
    // Maak functie ook beschikbaar op window object
    window.clearAllFilters = clearAllFilters;
    console.log('[DEBUG] clearAllFilters function defined EARLY - both locally and on window');
    
    // Log initial course types
    courseCards.forEach((card, index) => {
        console.log(`Course ${index + 1}:`, {
            level: card.dataset.level,
            type: card.dataset.type,
            title: card.querySelector('h3')?.textContent
        });
    });
    
    console.log('[DEBUG] About to attach filter button event listeners');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterType = this.dataset.filter;
            const filterValue = this.dataset.value;
            
            console.log('Filter clicked:', filterType, '=', filterValue);
            
            // Update active filter
            activeFilters[filterType] = filterValue;
            
            // Update button states for this filter group
            const groupButtons = document.querySelectorAll(`[data-filter="${filterType}"]`);
            groupButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Apply filters
            applyFilters();
        });
    });
    
    console.log('[DEBUG] Filter button event listeners attached');
    
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
        console.log('[DEBUG] Clear filters button event listener attached');
    } else {
        console.log('[DEBUG] No clear filters button found');
    }
    
    function applyFilters() {
        let visibleCount = 0;
        
        console.log('Applying filters:', activeFilters);
        
        courseCards.forEach((card, index) => {
            const cardLevel = card.dataset.level;
            const cardType = card.dataset.type;
            const cardTitle = card.querySelector('h3')?.textContent;
            
            const levelMatch = activeFilters.level === 'all' || cardLevel === activeFilters.level;
            const typeMatch = activeFilters.type === 'all' || cardType === activeFilters.type;
            
            const isVisible = levelMatch && typeMatch;
            
            console.log(`Course ${index + 1} (${cardTitle}):`, {
                level: cardLevel,
                type: cardType,
                levelMatch,
                typeMatch,
                visible: isVisible
            });
            
            // Check add-to-cart button state
            const addToCartBtn = card.querySelector('.add-to-cart-btn');
            if (addToCartBtn) {
                console.log(`[FILTER DEBUG] Course ${index + 1} add-to-cart button:`, {
                    exists: true,
                    pointerEvents: getComputedStyle(addToCartBtn).pointerEvents,
                    opacity: getComputedStyle(addToCartBtn).opacity,
                    visibility: getComputedStyle(addToCartBtn).visibility
                });
            } else {
                console.log(`[FILTER DEBUG] Course ${index + 1} has NO add-to-cart button!`);
            }
            
            // NIEUWE APPROACH: Gebruik CSS classes voor filtering
            if (isVisible) {
                card.classList.remove('filtered-hidden');
                card.classList.add('filtered-visible');
                visibleCount++;
                console.log(`[FILTER DEBUG] Course ${index + 1} set to VISIBLE`);
            } else {
                card.classList.remove('filtered-visible');
                card.classList.add('filtered-hidden');
                console.log(`[FILTER DEBUG] Course ${index + 1} set to HIDDEN`);
            }
            
            // Debug: Check final computed styles
            setTimeout(() => {
                if (addToCartBtn) {
                    const computedStyle = getComputedStyle(addToCartBtn);
                    console.log(`[FILTER DEBUG] Course ${index + 1} button final styles:`, {
                        pointerEvents: computedStyle.pointerEvents,
                        opacity: computedStyle.opacity,
                        visibility: computedStyle.visibility,
                        display: computedStyle.display
                    });
                }
            }, 100);
        });
        
        console.log('Visible courses:', visibleCount);
        console.log('[CART DEBUG] Course cards na filtering nog steeds in DOM voor cart events');
        
        // Update results count
        if (resultsCount) {
            resultsCount.textContent = `${visibleCount} cursus${visibleCount !== 1 ? 'sen' : ''} gevonden`;
        }
        
        // Show/hide clear filters button
        const hasActiveFilters = activeFilters.level !== 'all' || activeFilters.type !== 'all';
        if (clearFiltersBtn) {
            clearFiltersBtn.style.display = hasActiveFilters ? 'inline-block' : 'none';
        }
        
        // Show/hide no results message
        if (visibleCount === 0) {
            if (noResults) noResults.style.display = 'block';
            if (coursesGrid) coursesGrid.style.display = 'none';
        } else {
            if (noResults) noResults.style.display = 'none';
            if (coursesGrid) coursesGrid.style.display = 'grid';
        }
    }
    
    // Test filtering on page load
    console.log('Running initial filter test...');
    applyFilters();
    console.log('=== COURSE FILTERS DEBUG END ===');
}

console.log('[DEBUG] About to set up initialization handlers');

// Robuuste manier om de initialisatie uit te voeren
if (document.readyState === 'loading') {
    console.log('[DEBUG] Document still loading, adding DOMContentLoaded listener');
    document.addEventListener('DOMContentLoaded', initializeCourseFilters);
} else {
    // DOM is al geladen
    console.log('[DEBUG] Document already loaded, calling initializeCourseFilters immediately');
    initializeCourseFilters();
}

// Backup initialisatie na 1 seconde voor edge cases
setTimeout(function() {
    console.log('[DEBUG] Backup initialization check triggered');
    if (typeof window.clearAllFilters === 'undefined') {
        console.log('Backup initialization triggered - clearAllFilters not found');
        initializeCourseFilters();
    } else {
        console.log('clearAllFilters already defined, no backup needed');
    }
}, 1000);

console.log('=== COURSE FILTERS JS FILE END ===');
console.log('[DEBUG] Course filters module execution completed'); 