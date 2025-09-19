/**
 * Handles hamburger animation and offcanvas events
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Header navigation script loaded');
    
    // Get elements
    const toggler = document.querySelector('.navbar-toggler');
    const offcanvas = document.querySelector('#bdNavbar');
    const body = document.body;
    
    if (!toggler) {
        console.error('Navbar toggler not found');
        return;
    }
    
    if (!offcanvas) {
        console.error('Offcanvas element not found');
        return;
    }
    
    console.log('Elements found:', { toggler, offcanvas });
    
    // Function to activate hamburger (turn into X)
    function activateHamburger() {
        toggler.classList.add('active');
        console.log('Hamburger activated');
    }
    
    // Function to deactivate hamburger (turn back to lines)
    function deactivateHamburger() {
        toggler.classList.remove('active');
        console.log('Hamburger deactivated');
    }
    
    // Listen for offcanvas show event
    offcanvas.addEventListener('show.bs.offcanvas', function(e) {
        console.log('Offcanvas show event fired');
        activateHamburger();
    });
    
    // Listen for offcanvas shown event (fully visible)
    offcanvas.addEventListener('shown.bs.offcanvas', function(e) {
        console.log('Offcanvas fully shown');
        activateHamburger();
    });
    
    // Listen for offcanvas hide event
    offcanvas.addEventListener('hide.bs.offcanvas', function(e) {
        console.log('Offcanvas hide event fired');
        deactivateHamburger();
    });
    
    // Listen for offcanvas hidden event (fully hidden)
    offcanvas.addEventListener('hidden.bs.offcanvas', function(e) {
        console.log('Offcanvas fully hidden');
        deactivateHamburger();
    });
    
    // Immediate visual feedback on toggler click
    toggler.addEventListener('click', function(e) {
        console.log('Toggler clicked');
        
        // Small delay to let Bootstrap process the click
        setTimeout(function() {
            // Check if offcanvas is shown or will be shown
            if (offcanvas.classList.contains('show') || offcanvas.classList.contains('showing')) {
                activateHamburger();
            } else {
                deactivateHamburger();
            }
        }, 50);
    });
    
    // Handle clicking outside offcanvas (Bootstrap's backdrop)
    document.addEventListener('click', function(e) {
        // If clicking on backdrop or close button
        if (e.target.classList.contains('offcanvas-backdrop') || 
            e.target.classList.contains('btn-close')) {
            setTimeout(deactivateHamburger, 100);
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && offcanvas.classList.contains('show')) {
            setTimeout(deactivateHamburger, 100);
        }
    });
    
    // Mutation observer as backup to catch any state changes we might miss
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                if (offcanvas.classList.contains('show')) {
                    activateHamburger();
                } else if (!offcanvas.classList.contains('show') && 
                          !offcanvas.classList.contains('showing')) {
                    deactivateHamburger();
                }
            }
        });
    });
    
    // Start observing
    observer.observe(offcanvas, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    console.log('Header navigation script fully initialized');
});

// Additional fallback function that can be called manually if needed
window.toggleHamburger = function(force) {
    const toggler = document.querySelector('.navbar-toggler');
    if (!toggler) return;
    
    if (force === true) {
        toggler.classList.add('active');
    } else if (force === false) {
        toggler.classList.remove('active');
    } else {
        toggler.classList.toggle('active');
    }
};