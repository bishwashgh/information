// Admin Panel JavaScript

// Initialize admin panel
$(document).ready(function() {
    initializeAdminPanel();
});

function initializeAdminPanel() {
    // Initialize tooltips if present
    if (typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
    
    // Handle responsive sidebar
    handleResponsiveSidebar();
    
    // Initialize data tables if present
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            }
        });
    }
}

// Handle responsive sidebar
function handleResponsiveSidebar() {
    function checkWindowSize() {
        if (window.innerWidth <= 768) {
            document.querySelector('.admin-layout').classList.add('mobile-view');
        } else {
            document.querySelector('.admin-layout').classList.remove('mobile-view', 'sidebar-open');
        }
    }
    
    checkWindowSize();
    window.addEventListener('resize', checkWindowSize);
}

// Toggle sidebar on mobile
function toggleSidebar() {
    const layout = document.querySelector('.admin-layout');
    
    if (window.innerWidth <= 768) {
        layout.classList.toggle('sidebar-open');
    } else {
        layout.classList.toggle('sidebar-collapsed');
    }
}

// Confirmation dialogs
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

function confirmAction(message = 'Are you sure you want to perform this action?') {
    return confirm(message);
}

// Generic AJAX form handler
function handleAjaxForm(formSelector, successCallback) {
    $(formSelector).on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalText = $submitBtn.text();
        
        // Show loading state
        $submitBtn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: $form.attr('action') || window.location.href,
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showToast(response.message || 'Operation completed successfully', 'success');
                    if (typeof successCallback === 'function') {
                        successCallback(response);
                    }
                } else {
                    showToast(response.message || 'Operation failed', 'error');
                }
            },
            error: function() {
                showToast('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                // Restore button state
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
}

// Image preview for file uploads
function previewImage(input, previewSelector) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            $(previewSelector).attr('src', e.target.result).show();
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Handle file upload with progress
function handleFileUpload(inputSelector, options = {}) {
    $(inputSelector).on('change', function() {
        const file = this.files[0];
        
        if (!file) return;
        
        // Validate file type
        if (options.allowedTypes && !options.allowedTypes.includes(file.type)) {
            showToast('Invalid file type. Please select a valid file.', 'error');
            return;
        }
        
        // Validate file size (in MB)
        if (options.maxSize && file.size > options.maxSize * 1024 * 1024) {
            showToast(`File size must be less than ${options.maxSize}MB`, 'error');
            return;
        }
        
        // Show preview if it's an image
        if (file.type.startsWith('image/') && options.previewSelector) {
            previewImage(this, options.previewSelector);
        }
    });
}

// Status change handlers
function changeOrderStatus(orderId, status) {
    if (!confirmAction(`Change order status to ${status}?`)) {
        return;
    }
    
    $.ajax({
        url: window.siteUrl + '/admin/api/orders.php',
        method: 'POST',
        data: {
            action: 'change_status',
            order_id: orderId,
            status: status,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Order status updated successfully', 'success');
                location.reload();
            } else {
                showToast(response.message || 'Failed to update order status', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

function changeProductStatus(productId, status) {
    $.ajax({
        url: window.siteUrl + '/admin/api/products.php',
        method: 'POST',
        data: {
            action: 'change_status',
            product_id: productId,
            status: status,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast('Product status updated successfully', 'success');
                location.reload();
            } else {
                showToast(response.message || 'Failed to update product status', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Quick search functionality
function initializeQuickSearch() {
    $('#quickSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        const $rows = $('.searchable-row');
        
        $rows.each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });
}

// Export functionality
function exportData(type, url) {
    showLoading();
    
    window.location.href = url + '?export=' + type + '&csrf_token=' + window.csrfToken;
    
    setTimeout(hideLoading, 2000);
}

// Bulk actions
function handleBulkActions() {
    $('.select-all').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.item-checkbox').prop('checked', isChecked);
        updateBulkActionButtons();
    });
    
    $('.item-checkbox').on('change', function() {
        updateBulkActionButtons();
    });
    
    $('.bulk-action-btn').on('click', function() {
        const action = $(this).data('action');
        const selectedItems = $('.item-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedItems.length === 0) {
            showToast('Please select at least one item', 'warning');
            return;
        }
        
        if (!confirmAction(`Perform ${action} on ${selectedItems.length} selected item(s)?`)) {
            return;
        }
        
        performBulkAction(action, selectedItems);
    });
}

function updateBulkActionButtons() {
    const selectedCount = $('.item-checkbox:checked').length;
    $('.bulk-actions').toggle(selectedCount > 0);
    $('.selected-count').text(selectedCount);
}

function performBulkAction(action, items) {
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: {
            bulk_action: action,
            selected_items: items,
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response.success) {
                showToast(response.message || 'Bulk action completed successfully', 'success');
                location.reload();
            } else {
                showToast(response.message || 'Bulk action failed', 'error');
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
}

// Chart helpers
function createLineChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            ...options
        }
    });
}

function createBarChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            ...options
        }
    });
}

function createDoughnutChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            ...options
        }
    });
}

// Form validation
function validateForm(formSelector, rules) {
    const $form = $(formSelector);
    let isValid = true;
    
    // Clear previous errors
    $form.find('.error-message').remove();
    $form.find('.is-invalid').removeClass('is-invalid');
    
    Object.keys(rules).forEach(function(fieldName) {
        const $field = $form.find(`[name="${fieldName}"]`);
        const fieldRules = rules[fieldName];
        const value = $field.val().trim();
        
        fieldRules.forEach(function(rule) {
            if (rule.required && !value) {
                showFieldError($field, rule.message || 'This field is required');
                isValid = false;
            } else if (rule.minLength && value.length < rule.minLength) {
                showFieldError($field, rule.message || `Minimum ${rule.minLength} characters required`);
                isValid = false;
            } else if (rule.pattern && !rule.pattern.test(value)) {
                showFieldError($field, rule.message || 'Invalid format');
                isValid = false;
            }
        });
    });
    
    return isValid;
}

function showFieldError($field, message) {
    $field.addClass('is-invalid');
    $field.after(`<div class="error-message text-danger">${message}</div>`);
}

// Initialize common admin functionality
$(document).ready(function() {
    // Initialize bulk actions if present
    if ($('.item-checkbox').length) {
        handleBulkActions();
    }
    
    // Initialize quick search if present
    if ($('#quickSearch').length) {
        initializeQuickSearch();
    }
    
    // Handle file uploads
    $('input[type="file"]').each(function() {
        const $input = $(this);
        const options = {
            allowedTypes: $input.data('allowed-types') ? $input.data('allowed-types').split(',') : null,
            maxSize: $input.data('max-size') || null,
            previewSelector: $input.data('preview') || null
        };
        
        handleFileUpload($input, options);
    });
});

// Export functions for global use
window.AdminPanel = {
    toggleSidebar,
    confirmDelete,
    confirmAction,
    handleAjaxForm,
    changeOrderStatus,
    changeProductStatus,
    exportData,
    validateForm,
    createLineChart,
    createBarChart,
    createDoughnutChart
};
