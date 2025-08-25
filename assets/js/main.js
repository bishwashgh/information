/**
 * Main JavaScript File
 */

$(document).ready(function() {
    // Initialize components
    initCarousel();
    initMobileMenu();
    initSearchForm();
    initNewsletterForm();
    initCartFunctions();
    initProductActions();
    initLazyLoading();
    
    // Set CSRF token for all AJAX requests
    $.ajaxSetup({
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-CSRF-Token', window.csrfToken);
        }
    });
    
    // Global AJAX error handler to ensure loading is always hidden
    $(document).ajaxError(function(event, xhr, settings) {
        hideLoading();
        console.error('AJAX Error:', xhr.responseText);
    });
    
    // Global AJAX complete handler as backup
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Small delay to ensure loading is hidden
        setTimeout(hideLoading, 100);
    });
    
    // Click handler for loading overlay (emergency dismiss)
    $(document).on('click', '#loadingOverlay', function(e) {
        if (e.target === this) {
            forceHideLoading();
        }
    });
    
    // ESC key to dismiss loading
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC key
            forceHideLoading();
        }
    });
});

/**
 * Carousel Functionality
 */
function initCarousel() {
    let currentSlide = 0;
    const slides = $('.carousel-slide');
    const totalSlides = slides.length;
    
    if (totalSlides === 0) return;
    
    // Create dots
    const dotsContainer = $('.carousel-dots');
    for (let i = 0; i < totalSlides; i++) {
        const dot = $('<button class="carousel-dot"></button>');
        if (i === 0) dot.addClass('active');
        dot.on('click', () => goToSlide(i));
        dotsContainer.append(dot);
    }
    
    function goToSlide(index) {
        currentSlide = index;
        $('.carousel-container').css('transform', `translateX(-${currentSlide * 100}%)`);
        $('.carousel-dot').removeClass('active').eq(currentSlide).addClass('active');
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        goToSlide(currentSlide);
    }
    
    function prevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        goToSlide(currentSlide);
    }
    
    // Event listeners
    $('.carousel-nav.next').on('click', nextSlide);
    $('.carousel-nav.prev').on('click', prevSlide);
    
    // Auto-play
    setInterval(nextSlide, 5000);
    
    // Touch/swipe support
    let startX = 0;
    let endX = 0;
    
    $('.carousel').on('touchstart', function(e) {
        startX = e.originalEvent.touches[0].clientX;
    });
    
    $('.carousel').on('touchend', function(e) {
        endX = e.originalEvent.changedTouches[0].clientX;
        if (startX - endX > 50) {
            nextSlide();
        } else if (endX - startX > 50) {
            prevSlide();
        }
    });
}

/**
 * Mobile Menu
 */
function initMobileMenu() {
    $('#mobileMenuToggle').on('click', function() {
        $('#mobileNav').toggleClass('d-none');
        $(this).find('i').toggleClass('fa-bars fa-times');
    });
    
    // Close mobile menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.header-content').length) {
            $('#mobileNav').addClass('d-none');
            $('#mobileMenuToggle i').removeClass('fa-times').addClass('fa-bars');
        }
    });
}

/**
 * Search Form
 */
function initSearchForm() {
    $('.search-form').on('submit', function(e) {
        const query = $('.search-input').val().trim();
        if (!query) {
            e.preventDefault();
            showToast('Please enter a search term', 'warning');
        }
    });
    
    // Search suggestions (basic implementation)
    let searchTimeout;
    $('.search-input').on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                // Implement search suggestions here
                fetchSearchSuggestions(query);
            }, 300);
        }
    });
}

/**
 * Newsletter Form
 */
function initNewsletterForm() {
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();
        
        const email = $(this).find('input[name="email"]').val();
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Disable button and show loading
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subscribing...');
        
        $.ajax({
            url: window.siteUrl + '/api/newsletter.php',
            method: 'POST',
            data: {
                action: 'subscribe',
                email: email,
                csrf_token: window.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    showToast('Successfully subscribed to newsletter!', 'success');
                    $('#newsletterForm')[0].reset();
                } else {
                    showToast(response.message || 'Subscription failed', 'error');
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Subscribe');
            }
        });
    });
}

/**
 * Cart Functions
 */
function initCartFunctions() {
    // Add to cart
    $(document).on('click', '.add-to-cart', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        const quantity = parseInt($('#quantity').val()) || 1;
        const attributes = getProductAttributes();
        
        addToCart(productId, quantity, attributes);
    });
    
    // Update cart quantity
    $(document).on('change', '.cart-quantity', function() {
        const cartId = $(this).data('cart-id');
        const quantity = parseInt($(this).val());
        
        updateCartQuantity(cartId, quantity);
    });
    
    // Remove from cart
    $(document).on('click', '.remove-from-cart', function(e) {
        e.preventDefault();
        
        const cartId = $(this).data('cart-id');
        removeFromCart(cartId);
    });
}

/**
 * Product Actions
 */
function initProductActions() {
    // Add to wishlist
    $(document).on('click', '.add-to-wishlist', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        addToWishlist(productId);
    });
    
    // Product image gallery
    $(document).on('click', '.product-image-thumb', function() {
        const newSrc = $(this).attr('src');
        $('.product-image-main').attr('src', newSrc);
        $('.product-image-thumb').removeClass('active');
        $(this).addClass('active');
    });
    
    // Product zoom
    $('.product-image-main').on('mousemove', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const xPercent = (x / rect.width) * 100;
        const yPercent = (y / rect.height) * 100;
        
        $(this).css('transform-origin', `${xPercent}% ${yPercent}%`);
    });
    
    $('.product-image-main').on('mouseenter', function() {
        $(this).css('transform', 'scale(2)');
    });
    
    $('.product-image-main').on('mouseleave', function() {
        $(this).css('transform', 'scale(1)');
    });
}

/**
 * Lazy Loading
 */
function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
}

/**
 * Helper Functions
 */

// Show toast notification with smooth animations
function showToast(message, type = 'info', duration = 5000) {
    const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    const iconMap = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    
    const toast = $(`
        <div class="toast ${type}" id="${toastId}">
            <div class="toast-header">
                <div class="toast-icon">
                    <i class="${iconMap[type]}"></i>
                </div>
                <h6 class="toast-title">${getToastTitle(type)}</h6>
                <button class="toast-close" onclick="dismissToast('${toastId}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `);
    
    // Add to container
    $('#toastContainer').append(toast);
    
    // Trigger entrance animation
    requestAnimationFrame(() => {
        toast.addClass('show');
    });
    
    // Auto-dismiss after duration
    const timeoutId = setTimeout(() => {
        dismissToast(toastId);
    }, duration);
    
    // Store timeout ID for potential cancellation
    toast.data('timeoutId', timeoutId);
    
    // Add click to dismiss functionality
    toast.on('click', function(e) {
        if (!$(e.target).closest('.toast-close').length) {
            dismissToast(toastId);
        }
    });
}

// Dismiss toast with smooth animation
function dismissToast(toastId) {
    const toast = $('#' + toastId);
    if (toast.length) {
        // Clear timeout if exists
        const timeoutId = toast.data('timeoutId');
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        
        // Add hiding class for exit animation
        toast.addClass('hiding');
        
        // Remove after animation completes
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

function getToastTitle(type) {
    const titles = {
        'success': 'Success',
        'error': 'Error',
        'warning': 'Warning',
        'info': 'Information'
    };
    return titles[type] || 'Notification';
}

// Show loading overlay with smooth animation
function showLoading(message = 'Processing...') {
    let overlay = $('#loadingOverlay');
    
    // Create overlay if it doesn't exist
    if (!overlay.length) {
        overlay = $(`
            <div id="loadingOverlay" class="loading-overlay">
                <div class="loading-spinner">
                    <div class="spinner-icon"></div>
                    <div class="spinner-dots">
                        <div class="spinner-dot"></div>
                        <div class="spinner-dot"></div>
                        <div class="spinner-dot"></div>
                    </div>
                    <p id="loadingMessage">${message}</p>
                </div>
            </div>
        `);
        $('body').append(overlay);
    } else {
        $('#loadingMessage').text(message);
    }
    
    // Show with animation
    requestAnimationFrame(() => {
        overlay.addClass('show');
    });
    
    // Auto-hide after 30 seconds as failsafe
    setTimeout(() => {
        hideLoading();
    }, 30000);
}

// Hide loading overlay with smooth animation
function hideLoading() {
    const overlay = $('#loadingOverlay');
    if (overlay.length) {
        overlay.removeClass('show');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            overlay.remove();
        }, 300);
    }
}

// Force hide loading (emergency function)
function forceHideLoading() {
    const overlay = $('#loadingOverlay');
    if (overlay.length) {
        overlay.remove();
    }
}

// Developer test function for loading overlay
function testLoading() {
    console.log('Testing loading overlay...');
    showLoading();
    setTimeout(() => {
        console.log('Auto-hiding loading after 3 seconds...');
        hideLoading();
    }, 3000);
}

// Make functions available globally for debugging
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.forceHideLoading = forceHideLoading;
window.testLoading = testLoading;

// User menu toggle
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Close user menu when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('.user-menu').length) {
        $('#userDropdown').removeClass('active');
    }
});

// Add global toggleUserMenu function
window.toggleUserMenu = toggleUserMenu;

// Add to cart
function addToCart(productId, quantity = 1, attributes = {}) {
    showLoading();
    
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'add',
            product_id: productId,
            quantity: quantity,
            attributes: JSON.stringify(attributes),
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Product added to cart!', 'success');
                updateCartCount(response.cart_count);
            } else {
                showToast(response.message || 'Failed to add product to cart', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        },
        complete: function() {
            hideLoading();
        }
    });
}

// Update cart quantity
function updateCartQuantity(cartId, quantity) {
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'update',
            cart_id: cartId,
            quantity: quantity,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                location.reload(); // Reload to update totals
            } else {
                showToast(response.message || 'Failed to update cart', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Remove from cart
function removeFromCart(cartId) {
    if (!confirm('Are you sure you want to remove this item from cart?')) {
        return;
    }
    
    $.ajax({
        url: window.siteUrl + '/api/cart.php',
        method: 'POST',
        data: {
            action: 'remove',
            cart_id: cartId,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Item removed from cart', 'success');
                location.reload();
            } else {
                showToast(response.message || 'Failed to remove item', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Add to wishlist
function addToWishlist(productId) {
    $.ajax({
        url: window.siteUrl + '/api/wishlist.php',
        method: 'POST',
        data: {
            action: 'add',
            product_id: productId,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Product added to wishlist!', 'success');
                $(`.add-to-wishlist[data-product-id="${productId}"]`)
                    .addClass('active')
                    .find('i')
                    .removeClass('far')
                    .addClass('fas');
            } else {
                showToast(response.message || 'Failed to add to wishlist', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Update cart count in header
function updateCartCount(count) {
    const cartIcon = $('.cart-icon');
    const existingCount = cartIcon.find('.cart-count');
    
    if (count > 0) {
        if (existingCount.length) {
            existingCount.text(count);
        } else {
            cartIcon.append(`<span class="cart-count">${count}</span>`);
        }
    } else {
        existingCount.remove();
    }
}

// Get product attributes (sizes, colors, etc.)
function getProductAttributes() {
    const attributes = {};
    
    $('.product-attribute').each(function() {
        const name = $(this).data('attribute');
        const value = $(this).val() || $(this).data('value');
        if (value) {
            attributes[name] = value;
        }
    });
    
    return attributes;
}

// Fetch search suggestions
function fetchSearchSuggestions(query) {
    $.ajax({
        url: window.siteUrl + '/api/search.php',
        method: 'GET',
        data: {
            action: 'suggestions',
            q: query
        },
        success: function(response) {
            if (response.success && response.suggestions.length > 0) {
                showSearchSuggestions(response.suggestions);
            } else {
                hideSearchSuggestions();
            }
        },
        error: function() {
            hideSearchSuggestions();
        }
    });
}

// Show search suggestions
function showSearchSuggestions(suggestions) {
    let suggestionHtml = '<div class="search-suggestions">';
    
    suggestions.forEach(item => {
        suggestionHtml += `
            <div class="search-suggestion" onclick="selectSuggestion('${item.name}')">
                <i class="fas fa-search"></i>
                <span>${item.name}</span>
            </div>
        `;
    });
    
    suggestionHtml += '</div>';
    
    $('.search-bar').append(suggestionHtml);
}

// Hide search suggestions
function hideSearchSuggestions() {
    $('.search-suggestions').remove();
}

// Select search suggestion
function selectSuggestion(suggestion) {
    $('.search-input').val(suggestion);
    $('.search-form').submit();
}

// Format price
function formatPrice(price) {
    return 'Rs. ' + parseFloat(price).toFixed(2);
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Smooth scroll to element
function scrollToElement(element, offset = 0) {
    const elementPosition = $(element).offset().top;
    const offsetPosition = elementPosition - offset;
    
    $('html, body').animate({
        scrollTop: offsetPosition
    }, 500);
}

// Validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate phone number
function isValidPhone(phone) {
    const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy to clipboard', 'error');
    });
}

// Form validation
function validateForm(formSelector) {
    let isValid = true;
    
    $(formSelector + ' [required]').each(function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (!value) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
            
            // Email validation
            if (field.attr('type') === 'email' && !isValidEmail(value)) {
                showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
            
            // Phone validation
            if (field.attr('type') === 'tel' && !isValidPhone(value)) {
                showFieldError(field, 'Please enter a valid phone number');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    field.addClass('error');
    field.next('.form-error').remove();
    field.after(`<div class="form-error">${message}</div>`);
}

// Clear field error
function clearFieldError(field) {
    field.removeClass('error');
    field.next('.form-error').remove();
}

// Get URL parameter
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Update URL parameter
function updateUrlParameter(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    window.history.pushState({}, '', url);
}

// Local storage helpers
function setLocalStorage(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        console.error('Failed to save to localStorage:', e);
    }
}

function getLocalStorage(key, defaultValue = null) {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
    } catch (e) {
        console.error('Failed to read from localStorage:', e);
        return defaultValue;
    }
}

// Session storage helpers
function setSessionStorage(key, value) {
    try {
        sessionStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        console.error('Failed to save to sessionStorage:', e);
    }
}

function getSessionStorage(key, defaultValue = null) {
    try {
        const item = sessionStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
    } catch (e) {
        console.error('Failed to read from sessionStorage:', e);
        return defaultValue;
    }
}
