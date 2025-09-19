/* public/assets/js/minicart.js
   Enhanced script to toggle the mini-cart overlay and handle cart interactions
*/
(function () {
  'use strict';

  // DOM elements
  const toggle = document.getElementById('minicart-toggle');
  const panel = document.getElementById('minicart-panel');
  const overlay = document.getElementById('minicart-overlay');
  const closeBtn = document.getElementById('minicart-close');
  const countEl = document.getElementById('mini-cart-count'); // badge on nav icon
  const minicartCount = document.getElementById('minicart-count'); // count in panel header
  const minicartSubtotal = document.getElementById('minicart-subtotal'); // subtotal in panel footer

  // Exit if essential elements don't exist
  if (!toggle || !panel) {
    console.warn('Minicart elements not found');
    return;
  }

  // Toggle minicart visibility
  function showMinicart() {
    if (panel) {
      panel.setAttribute('aria-hidden', 'false');
      panel.style.display = 'block';
    }
    if (overlay) {
      overlay.setAttribute('aria-hidden', 'false');
      overlay.style.display = 'block';
    }
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  }

  function hideMinicart() {
    if (panel) {
      panel.setAttribute('aria-hidden', 'true');
      panel.style.display = 'none';
    }
    if (overlay) {
      overlay.setAttribute('aria-hidden', 'true');
      overlay.style.display = 'none';
    }
    document.body.style.overflow = ''; // Restore scrolling
  }

  // Event listeners
  toggle.addEventListener('click', function (ev) {
    ev.preventDefault();
    ev.stopPropagation();
    
    const isVisible = panel.getAttribute('aria-hidden') === 'false';
    if (isVisible) {
      hideMinicart();
    } else {
      showMinicart();
    }
  });

  // Close button
  if (closeBtn) {
    closeBtn.addEventListener('click', function (ev) {
      ev.preventDefault();
      hideMinicart();
    });
  }

  // Overlay click closes minicart
  if (overlay) {
    overlay.addEventListener('click', function (ev) {
      ev.preventDefault();
      hideMinicart();
    });
  }

  // Click outside closes minicart (alternative to overlay)
  document.addEventListener('click', function (ev) {
    if (!panel.contains(ev.target) && !toggle.contains(ev.target)) {
      hideMinicart();
    }
  });

  // Escape key closes minicart
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && panel.getAttribute('aria-hidden') === 'false') {
      hideMinicart();
    }
  });

  // Prevent minicart panel clicks from bubbling up
  if (panel) {
    panel.addEventListener('click', function (ev) {
      ev.stopPropagation();
    });
  }

  // Function to update minicart display (for AJAX cart operations)
  function updateMinicartDisplay(data) {
    if (!data) return;
    
    // Update counts
    if (countEl && data.count !== undefined) {
      countEl.textContent = data.count;
      // Show/hide badge based on count
      if (data.count > 0) {
        countEl.style.display = '';
      } else {
        countEl.style.display = 'none';
      }
    }
    
    if (minicartCount && data.count !== undefined) {
      minicartCount.textContent = data.count;
    }
    
    // Update subtotal
    if (minicartSubtotal && data.subtotal !== undefined) {
      minicartSubtotal.textContent = parseFloat(data.subtotal).toFixed(2);
    }
  }

  // Function to refresh minicart via AJAX (if you want to implement this)
  function refreshMinicart() {
    // You can implement this to call an endpoint like /api/minicart
    // that returns updated cart data without a full page reload
    
    // Example implementation:
    // fetch('/api/minicart')
    //   .then(response => response.json())
    //   .then(data => {
    //     updateMinicartDisplay(data);
    //     // Optionally update the entire minicart content
    //   })
    //   .catch(error => console.error('Error refreshing minicart:', error));
  }

  // Add to cart functionality (if you want to handle this via AJAX)
  function addToCartAjax(bookId, quantity = 1) {
    const formData = new FormData();
    formData.append('quantity', quantity.toString());
    
    fetch(`/cart/add/${bookId}`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      if (response.ok) {
        return response.json();
      }
      throw new Error('Network response was not ok');
    })
    .then(data => {
      updateMinicartDisplay(data);
      showMinicart(); // Show minicart after adding item
    })
    .catch(error => {
      console.error('Error adding to cart:', error);
      // Fallback to regular form submission
      window.location.href = `/cart/add/${bookId}?quantity=${quantity}`;
    });
  }

  // Expose functions globally if needed
  window.MinicartJS = {
    show: showMinicart,
    hide: hideMinicart,
    refresh: refreshMinicart,
    addToCart: addToCartAjax,
    updateDisplay: updateMinicartDisplay
  };

  // Auto-hide minicart on page navigation (for SPAs)
  window.addEventListener('beforeunload', function () {
    hideMinicart();
  });

})();