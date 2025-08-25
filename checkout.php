<?php
require_once 'includes/config.php';

// Generate CSRF token for forms
generateCSRFToken();

// Redirect to cart if empty
$db = Database::getInstance()->getConnection();

// Get cart items
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([getCurrentUserId()]);
} else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM cart WHERE session_id = ?");
    $stmt->execute([session_id()]);
}

$cartCount = $stmt->fetchColumn();

// Temporarily disable cart check for testing
// if ($cartCount == 0) {
//     header('Location: ' . SITE_URL . '/cart.php');
//     exit;
// }

// Get cart totals
$cartData = getCartData();

$pageTitle = 'Checkout';
$pageDescription = 'Complete your order securely';

// Current step
$currentStep = $_GET['step'] ?? 'address';
$validSteps = ['address', 'payment', 'confirmation'];

if (!in_array($currentStep, $validSteps)) {
    $currentStep = 'address';
}

// Get user addresses if logged in
$userAddresses = [];
if (isLoggedIn()) {
    $stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([getCurrentUserId()]);
    $userAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include 'includes/header.php'; ?>

<script>
// Define showNewAddressForm function in head to ensure it's available immediately
function showNewAddressForm() {
    console.log('=== showNewAddressForm called from head script ===');
    
    try {
        const newAddressForm = document.getElementById('newAddressForm');
        const addNewButton = document.querySelector('.add-new-address');
        
        console.log('Elements found:', {
            newAddressForm: !!newAddressForm,
            addNewButton: !!addNewButton
        });
        
        // Show the form
        if (newAddressForm) {
            newAddressForm.style.display = 'block';
            newAddressForm.style.visibility = 'visible';
            newAddressForm.classList.add('form-visible');
            console.log('New address form shown');
        } else {
            console.error('newAddressForm element not found');
        }
        
        // Hide the add button
        if (addNewButton) {
            addNewButton.style.display = 'none';
            console.log('Add new address button hidden');
        } else {
            console.error('add-new-address button not found');
        }
        
        // Clear selected saved addresses
        const savedAddresses = document.querySelectorAll('input[name="saved_address"]');
        savedAddresses.forEach(radio => {
            radio.checked = false;
        });
        console.log('Cleared', savedAddresses.length, 'saved addresses');
        
        console.log('=== showNewAddressForm completed successfully ===');
        
    } catch (error) {
        console.error('Error in showNewAddressForm:', error);
        alert('Error: ' + error.message);
    }
}

// Test function availability immediately
console.log('showNewAddressForm function defined:', typeof showNewAddressForm);

// Define testPlaceOrder function in head to ensure it's available immediately
function testPlaceOrder() {
    console.log('=== Place Order button clicked ===');
    
    try {
        // Check if a payment method is selected
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!paymentMethod) {
            alert('Please select a payment method first');
            return;
        }
        
        console.log('Selected payment method:', paymentMethod.value);
        
        // For disabled payment methods
        if (paymentMethod.value === 'esewa' || paymentMethod.value === 'khalti') {
            alert('This payment method is coming soon!');
            return;
        }
        
        // Check if required variables are available
        if (!window.siteUrl) {
            console.error('window.siteUrl is not defined');
            alert('Configuration error: Site URL not found');
            return;
        }
        
        if (!window.csrfToken) {
            console.error('window.csrfToken is not defined');
            alert('Configuration error: CSRF token not found');
            return;
        }
        
        console.log('Site URL:', window.siteUrl);
        console.log('CSRF Token:', window.csrfToken);
        
        // Prepare payment data
        const paymentData = {
            payment_method: paymentMethod.value,
            order_notes: document.querySelector('textarea[name="order_notes"]') ? document.querySelector('textarea[name="order_notes"]').value : ''
        };
        
        console.log('Payment data:', paymentData);
        console.log('Sending order data to API...');
        
        const apiUrl = window.siteUrl + '/api/checkout.php';
        console.log('API URL:', apiUrl);
        
        // Send order to API
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'process_payment',
                payment_data: JSON.stringify(paymentData),
                csrf_token: window.csrfToken
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                console.log('Raw response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Parsed API response:', data);
            if (data.success) {
                console.log('Order placed successfully, redirecting...');
                const redirectUrl = window.siteUrl + '/checkout.php?step=confirmation&order_id=' + (data.order_id || 'new');
                console.log('Redirect URL:', redirectUrl);
                window.location.href = redirectUrl;
            } else {
                console.error('Order failed:', data.message);
                alert('Order failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error details:', error);
            console.error('Error stack:', error.stack);
            alert('An error occurred while placing the order: ' + error.message + '. Please check the console for details.');
        });
        
    } catch (error) {
        console.error('Error in testPlaceOrder:', error);
        alert('Error in Place Order function: ' + error.message);
    }
}

// Make function globally available as backup
window.showNewAddressForm = showNewAddressForm;
window.testPlaceOrder = testPlaceOrder;
console.log('Functions attached to window:', {
    showNewAddressForm: typeof window.showNewAddressForm,
    testPlaceOrder: typeof window.testPlaceOrder
});
console.log('Function attached to window:', typeof window.showNewAddressForm);

// Test when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready - showNewAddressForm available:', typeof showNewAddressForm);
});

// Add window.onload test
window.addEventListener('load', function() {
    console.log('Window loaded - showNewAddressForm available:', typeof showNewAddressForm);
});
</script>

<!-- Checkout Section -->
<section class="checkout-section">
    <div class="container">
        <!-- Checkout Progress -->
        <div class="checkout-progress">
            <div class="progress-step <?php echo $currentStep === 'address' ? 'active' : (array_search($currentStep, $validSteps) > 0 ? 'completed' : ''); ?>">
                <div class="step-number">1</div>
                <div class="step-label">Address</div>
            </div>
            <div class="progress-line <?php echo array_search($currentStep, $validSteps) > 0 ? 'completed' : ''; ?>"></div>
            
            <div class="progress-step <?php echo $currentStep === 'payment' ? 'active' : (array_search($currentStep, $validSteps) > 1 ? 'completed' : ''); ?>">
                <div class="step-number">2</div>
                <div class="step-label">Payment</div>
            </div>
            <div class="progress-line <?php echo array_search($currentStep, $validSteps) > 1 ? 'completed' : ''; ?>"></div>
            
            <div class="progress-step <?php echo $currentStep === 'confirmation' ? 'active' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>
        
        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <?php if ($currentStep === 'address'): ?>
                <!-- Step 1: Address -->
                <div class="checkout-step">
                    <h2>Delivery Address</h2>
                    
                    <?php if (isLoggedIn() && !empty($userAddresses)): ?>
                    <!-- Saved Addresses -->
                    <div class="saved-addresses">
                        <h3>Select Saved Address</h3>
                        <div class="address-list">
                            <?php foreach ($userAddresses as $address): ?>
                            <div class="address-card" data-address-id="<?php echo $address['id']; ?>">
                                <input type="radio" name="saved_address" value="<?php echo $address['id']; ?>" 
                                       id="address_<?php echo $address['id']; ?>" 
                                       data-is-default="<?php echo $address['is_default'] ? '1' : '0'; ?>"
                                       <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                <label for="address_<?php echo $address['id']; ?>">
                                    <div class="address-header">
                                        <strong><?php echo $address['first_name'] . ' ' . $address['last_name']; ?></strong>
                                        <?php if ($address['is_default']): ?>
                                        <span class="default-badge">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-details">
                                        <?php echo $address['address_line_1']; ?>
                                        <?php if ($address['address_line_2']): ?>
                                        <br><?php echo $address['address_line_2']; ?>
                                        <?php endif; ?>
                                        <br><?php echo $address['city']; ?>, <?php echo $address['postal_code']; ?>
                                        <br><?php echo $address['country']; ?>
                                        <?php if ($address['phone']): ?>
                                        <br>Phone: <?php echo $address['phone']; ?>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="add-new-address">
                            <button type="button" class="btn btn-outline" onclick="console.log('Button clicked!'); console.log('Function type:', typeof showNewAddressForm); showNewAddressForm();">
                                <i class="fas fa-plus"></i> Add New Address
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- New Address Form -->
                    <div class="new-address-form" id="newAddressForm" <?php echo (isLoggedIn() && !empty($userAddresses)) ? 'style="display: none;"' : ''; ?>>
                        <h3><?php echo (isLoggedIn() && !empty($userAddresses)) ? 'Add New Address' : 'Delivery Address'; ?></h3>
                        
                        <form id="addressForm" class="address-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName" class="form-label">First Name *</label>
                                    <input type="text" id="firstName" name="first_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName" class="form-label">Last Name *</label>
                                    <input type="text" id="lastName" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="addressLine1" class="form-label">Address Line 1 *</label>
                                <input type="text" id="addressLine1" name="address_line_1" class="form-control" 
                                       placeholder="Street address, building name, etc." required>
                            </div>
                            
                            <div class="form-group">
                                <label for="addressLine2" class="form-label">Address Line 2</label>
                                <input type="text" id="addressLine2" name="address_line_2" class="form-control" 
                                       placeholder="Apartment, suite, unit, etc.">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" id="city" name="city" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="postalCode" class="form-label">Postal Code</label>
                                    <input type="text" id="postalCode" name="postal_code" class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="country" class="form-label">Country *</label>
                                <select id="country" name="country" class="form-control" required>
                                    <option value="Nepal" selected>Nepal</option>
                                    <option value="India">India</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <?php if (isLoggedIn()): ?>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="save_address" value="1">
                                    <span class="checkmark"></span>
                                    Save this address for future orders
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="set_default" value="1">
                                    <span class="checkmark"></span>
                                    Set as default address
                                </label>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="step-actions">
                        <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Cart
                        </a>
                        <button type="button" class="btn btn-primary" onclick="testRedirect()">
                            Continue to Payment <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <?php elseif ($currentStep === 'payment'): ?>
                <!-- Step 3: Payment -->
                <div class="checkout-step">
                    <h2>Payment Method</h2>
                    
                    <form id="paymentForm" class="payment-form">
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="cod" name="payment_method" value="cod" checked>
                                <label for="cod" class="payment-option">
                                    <div class="payment-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-details">
                                        <h3>Cash on Delivery</h3>
                                        <p>Pay with cash when your order arrives</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" id="esewa" name="payment_method" value="esewa" disabled>
                                <label for="esewa" class="payment-option disabled">
                                    <div class="payment-icon">
                                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQobMXYNheAlDT6Ukz4smf2K33xv3hZ7ELrqA&s" alt="eSewa" style="width: 40px; height: 40px; object-fit: contain; opacity: 0.5;">
                                    </div>
                                    <div class="payment-details">
                                        <h3>eSewa <span style="color: #999; font-size: 12px;">(Coming Soon)</span></h3>
                                        <p>Pay securely with eSewa digital wallet</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" id="khalti" name="payment_method" value="khalti" disabled>
                                <label for="khalti" class="payment-option disabled">
                                    <div class="payment-icon">
                                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6iuA5afXQAqElwakslhFezUlKGwV2o35NNg&s" alt="Khalti" style="width: 40px; height: 40px; object-fit: contain; opacity: 0.5;">
                                    </div>
                                    <div class="payment-details">
                                        <h3>Khalti <span style="color: #999; font-size: 12px;">(Coming Soon)</span></h3>
                                        <p>Pay with Khalti digital wallet</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Card Payment Form -->
                        <div class="card-payment-form" id="cardPaymentForm" style="display: none;">
                            <h3>Card Details</h3>
                            <div class="form-row">
                                <div class="form-group col-12">
                                    <label for="cardNumber" class="form-label">Card Number</label>
                                    <input type="text" id="cardNumber" name="card_number" class="form-control" 
                                           placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6">
                                    <label for="cardExpiry" class="form-label">Expiry Date</label>
                                    <input type="text" id="cardExpiry" name="card_expiry" class="form-control" 
                                           placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="form-group col-6">
                                    <label for="cardCvv" class="form-label">CVV</label>
                                    <input type="text" id="cardCvv" name="card_cvv" class="form-control" 
                                           placeholder="123" maxlength="4">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="cardName" class="form-label">Cardholder Name</label>
                                <input type="text" id="cardName" name="card_name" class="form-control" 
                                       placeholder="John Doe">
                            </div>
                        </div>
                        
                        <!-- Order Notes -->
                        <div class="order-notes">
                            <h3>Order Notes (Optional)</h3>
                            <textarea name="order_notes" class="form-control" rows="3" 
                                      placeholder="Any special instructions for your order..."></textarea>
                        </div>
                    </form>
                    
                    <div class="step-actions">
                        <a href="<?php echo SITE_URL; ?>/checkout.php?step=address" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Address
                        </a>
                        <button type="button" class="btn btn-primary" onclick="testPlaceOrder()">
                            Place Order <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>
                
                <?php elseif ($currentStep === 'confirmation'): ?>
                <!-- Step 4: Confirmation -->
                <div class="checkout-step">
                    <div class="order-confirmation">
                        <div class="confirmation-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>Order Confirmed!</h2>
                        <p>Thank you for your order. We've received your order and will process it shortly.</p>
                        
                        <div class="order-details">
                            <?php 
                            $orderId = $_GET['order_id'] ?? null;
                            $orderData = null;
                            
                            if ($orderId && $orderId !== 'new') {
                                // Fetch actual order details
                                $stmt = $db->prepare("
                                    SELECT o.*, 
                                           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.product_name) SEPARATOR ', ') as items_summary
                                    FROM orders o
                                    LEFT JOIN order_items oi ON o.id = oi.order_id
                                    WHERE o.id = ?
                                    GROUP BY o.id
                                ");
                                $stmt->execute([$orderId]);
                                $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
                            }
                            ?>
                            
                            <div class="order-number">
                                <strong>Order Number: <?php 
                                    if ($orderData) {
                                        echo '#' . $orderData['order_number'];
                                    } else {
                                        echo '#ORD-' . date('Ymd') . '-' . rand(1000, 9999);
                                    }
                                ?></strong>
                            </div>
                            
                            <?php if ($orderData): ?>
                            <div class="order-info">
                                <div class="order-status">
                                    <span class="status-badge status-<?php echo $orderData['status']; ?>">
                                        <?php echo ucfirst($orderData['status']); ?>
                                    </span>
                                </div>
                                <div class="order-summary-details">
                                    <p><strong>Items:</strong> <?php echo $orderData['items_summary']; ?></p>
                                    <p><strong>Total:</strong> <?php echo formatPrice($orderData['total_amount']); ?></p>
                                    <p><strong>Payment Method:</strong> <?php echo ucfirst($orderData['payment_method']); ?></p>
                                    <p><strong>Order Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($orderData['created_at'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="order-email">
                                A confirmation email has been sent to your email address.
                            </div>
                        </div>
                        
                        <div class="confirmation-actions">
                            <a href="<?php echo SITE_URL; ?>/" class="btn btn-primary">Continue Shopping</a>
                            <?php if (isLoggedIn()): ?>
                            <a href="<?php echo SITE_URL; ?>/user/orders.php" class="btn btn-outline">View Orders</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-card">
                    <h3>Order Summary</h3>
                    
                    <!-- Cart Items -->
                    <div class="summary-items">
                        <?php foreach ($cartData['items'] as $item): ?>
                        <div class="summary-item">
                            <div class="item-image">
                                <?php if ($item['image']): ?>
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1523381210834-895b31b4-3b0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=80&h=80" alt="<?php echo $item['name']; ?>">
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <h4><?php echo $item['name']; ?></h4>
                                <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                <div class="item-price"><?php echo formatPrice($item['total_price']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Order Totals -->
                    <div class="summary-totals">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span><?php echo formatPrice($cartData['subtotal']); ?></span>
                        </div>
                        
                        <?php if ($cartData['discount'] > 0): ?>
                        <div class="total-row discount">
                            <span>Discount:</span>
                            <span>-<?php echo formatPrice($cartData['discount']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($cartData['shipping'] > 0): ?>
                                    <?php echo formatPrice($cartData['shipping']); ?>
                                <?php else: ?>
                                    <span class="free">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="total-row">
                            <span>Tax:</span>
                            <span><?php echo formatPrice($cartData['tax']); ?></span>
                        </div>
                        
                        <div class="total-row total">
                            <span>Total:</span>
                            <span><?php echo formatPrice($cartData['total']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Security -->
                    <div class="security-badges">
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Checkout</span>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-lock"></i>
                            <span>SSL Encrypted</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Enhanced Premium Checkout Styles */
.checkout-section {
    padding: var(--spacing-12) 0;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 50%, var(--gray-50) 100%);
    min-height: 85vh;
}

.checkout-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-6);
}

/* Enhanced Progress Bar */
.checkout-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-12);
    padding: var(--spacing-8);
    background: linear-gradient(135deg, var(--white) 0%, #fafbfc 100%);
    border-radius: var(--border-radius-xl);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    position: relative;
    overflow: hidden;
}

.checkout-progress::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-number {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gray-300), var(--gray-400));
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-3);
    transition: all 0.4s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: 3px solid var(--white);
}

.progress-step.active .step-number {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
}

.progress-step.completed .step-number {
    background: linear-gradient(135deg, var(--success-color), #059669);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.step-title {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: center;
}

.progress-step.active .step-title {
    color: var(--primary-color);
}

.progress-step.completed .step-title {
    color: var(--success-color);
}

.progress-connector {
    flex: 1;
    height: 3px;
    background: var(--gray-200);
    margin: 0 var(--spacing-6);
    margin-top: -30px;
    border-radius: 2px;
    position: relative;
    overflow: hidden;
}

.progress-connector.active {
    background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.progress-connector.active::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
    justify-content: center;
    font-weight: 600;
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-2);
    transition: var(--transition);
}

.progress-step.active .step-number {
    background: var(--primary-color);
}

.progress-step.completed .step-number {
    background: var(--success-color);
}

.step-label {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    font-weight: 500;
}

.progress-step.active .step-label {
    color: var(--primary-color);
}

.progress-line {
    width: 100px;
    height: 2px;
    background: var(--gray-300);
    margin: 0 var(--spacing-4);
    transition: var(--transition);
}

.progress-line.completed {
    background: var(--success-color);
}

/* Checkout Layout */
.checkout-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-8);
    align-items: start;
}

/* Checkout Form */
.checkout-form {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-8);
    box-shadow: var(--shadow);
}

.checkout-step h2 {
    font-size: var(--font-size-2xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-6);
    padding-bottom: var(--spacing-4);
    border-bottom: 1px solid var(--gray-200);
}

/* Address Forms */
.saved-addresses {
    margin-bottom: var(--spacing-6);
}

.address-list {
    display: grid;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.address-card {
    position: relative;
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    transition: var(--transition-fast);
}

.address-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.address-card input[type="radio"]:checked + label {
    border-color: var(--primary-color);
    background: var(--primary-color)/5%;
}

.address-card label {
    display: block;
    padding: var(--spacing-5);
    cursor: pointer;
    border-radius: var(--border-radius-lg);
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-2);
}

.default-badge {
    background: var(--success-color);
    color: var(--white);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 600;
}

.address-details {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    line-height: 1.5;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.form-row .form-group {
    margin-bottom: 0;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    cursor: pointer;
    font-size: var(--font-size-sm);
    color: var(--gray-700);
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary-color);
}

/* Payment Methods */
.payment-methods {
    display: grid;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.payment-method {
    position: relative;
}

.payment-method input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.payment-option {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    padding: var(--spacing-5);
    border: 2px solid var(--gray-200);
    border-radius: var(--border-radius-lg);
    cursor: pointer;
    transition: var(--transition-fast);
}

.payment-method input[type="radio"]:checked + .payment-option {
    border-color: var(--primary-color);
    background: rgba(37, 99, 235, 0.05);
}

.payment-icon {
    width: 50px;
    height: 50px;
    background: var(--gray-100);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-xl);
    color: var(--gray-600);
}

.payment-details h3 {
    font-size: var(--font-size-lg);
    color: var(--gray-900);
    margin: 0 0 var(--spacing-1);
}

.payment-details p {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
    margin: 0;
}

/* Disabled payment methods */
.payment-option.disabled {
    cursor: not-allowed;
    opacity: 0.6;
    background: var(--gray-50);
}

.payment-option.disabled:hover {
    border-color: var(--gray-200);
    background: var(--gray-50);
}

.payment-method input[type="radio"]:disabled + .payment-option {
    cursor: not-allowed;
    opacity: 0.6;
}

.card-payment-form {
    padding: var(--spacing-6);
    background: var(--gray-50);
    border-radius: var(--border-radius-lg);
    margin-top: var(--spacing-4);
}

.card-payment-form h3 {
    margin-bottom: var(--spacing-4);
    color: var(--gray-900);
}

.form-row.col-6 {
    grid-template-columns: 1fr 1fr;
}

.form-row.col-12 {
    grid-template-columns: 1fr;
}

.order-notes {
    margin-top: var(--spacing-6);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

.order-notes h3 {
    margin-bottom: var(--spacing-4);
    color: var(--gray-900);
}

/* Order Confirmation */
.order-confirmation {
    text-align: center;
    padding: var(--spacing-8) var(--spacing-4);
}

.confirmation-icon {
    margin-bottom: var(--spacing-6);
}

.confirmation-icon i {
    font-size: 4rem;
    color: var(--success-color);
}

.order-confirmation h2 {
    color: var(--success-color);
    margin-bottom: var(--spacing-4);
}

.order-confirmation p {
    color: var(--gray-600);
    margin-bottom: var(--spacing-6);
}

.order-details {
    background: var(--gray-50);
    padding: var(--spacing-6);
    border-radius: var(--border-radius-lg);
    margin-bottom: var(--spacing-8);
}

.order-number {
    font-size: var(--font-size-lg);
    color: var(--gray-900);
    margin-bottom: var(--spacing-3);
}

.order-info {
    margin: var(--spacing-4) 0;
}

.order-status {
    margin-bottom: var(--spacing-3);
}

.status-badge {
    padding: var(--spacing-1) var(--spacing-3);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-confirmed {
    background: #d1fae5;
    color: #065f46;
}

.status-processing {
    background: #dbeafe;
    color: #1e40af;
}

.status-shipped {
    background: #e0e7ff;
    color: #3730a3;
}

.status-delivered {
    background: #d1fae5;
    color: #065f46;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.order-summary-details {
    margin-top: var(--spacing-3);
}

.order-summary-details p {
    margin: var(--spacing-2) 0;
    color: var(--gray-700);
    font-size: var(--font-size-sm);
}

.order-email {
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.confirmation-actions {
    display: flex;
    gap: var(--spacing-4);
    justify-content: center;
}

/* Step Actions */
.step-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--spacing-8);
    padding-top: var(--spacing-6);
    border-top: 1px solid var(--gray-200);
}

/* Order Summary */
.order-summary {
    position: sticky;
    top: var(--spacing-8);
}

.summary-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-6);
    box-shadow: var(--shadow);
}

.summary-card h3 {
    font-size: var(--font-size-xl);
    color: var(--gray-900);
    margin-bottom: var(--spacing-5);
    padding-bottom: var(--spacing-3);
    border-bottom: 1px solid var(--gray-200);
}

.summary-items {
    margin-bottom: var(--spacing-5);
}

.summary-item {
    display: flex;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-4);
    padding-bottom: var(--spacing-4);
    border-bottom: 1px solid var(--gray-100);
}

.summary-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.summary-item .item-image img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--border-radius);
}

.summary-item .item-details h4 {
    font-size: var(--font-size-base);
    color: var(--gray-900);
    margin: 0 0 var(--spacing-1);
}

.item-quantity {
    font-size: var(--font-size-sm);
    color: var(--gray-600);
    margin-bottom: var(--spacing-1);
}

.item-price {
    font-weight: 600;
    color: var(--gray-900);
}

.summary-totals {
    margin-bottom: var(--spacing-5);
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-2) 0;
    font-size: var(--font-size-base);
}

.total-row.discount {
    color: var(--success-color);
}

.total-row.total {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--gray-900);
    padding-top: var(--spacing-3);
    border-top: 2px solid var(--gray-200);
}

.total-row .free {
    color: var(--success-color);
    font-weight: 600;
}

.security-badges {
    display: flex;
    justify-content: center;
    gap: var(--spacing-4);
    padding-top: var(--spacing-5);
    border-top: 1px solid var(--gray-200);
}

.security-badge {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    color: var(--gray-600);
    font-size: var(--font-size-sm);
}

.security-badge i {
    color: var(--success-color);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .checkout-layout {
        gap: var(--spacing-6);
    }
    
    .progress-line {
        width: 80px;
    }
}

@media (max-width: 768px) {
    .checkout-layout {
        grid-template-columns: 1fr;
        gap: var(--spacing-6);
    }
    
    .order-summary {
        order: -1;
        position: static;
    }
    
    .checkout-progress {
        padding: var(--spacing-4);
    }
    
    .progress-line {
        width: 60px;
        margin: 0 var(--spacing-2);
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        font-size: var(--font-size-base);
    }
    
    .step-label {
        font-size: var(--font-size-xs);
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .step-actions {
        flex-direction: column;
        gap: var(--spacing-3);
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .checkout-form,
    .summary-card {
        padding: var(--spacing-4);
    }
    
    .progress-step .step-label {
        display: none;
    }
    
    .progress-line {
        width: 40px;
    }
    
    /* Ensure new address form can be shown */
    .new-address-form.form-visible {
        display: block !important;
        visibility: visible !important;
    }
}
</style>

<script>
$(document).ready(function() {
    // Payment method selection
    $('input[name="payment_method"]').on('change', function() {
        // Hide card payment form for all payment methods (eSewa, Khalti, COD)
        $('#cardPaymentForm').slideUp();
    });
    
    // Format card number
    $('#cardNumber').on('input', function() {
        let value = $(this).val().replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        $(this).val(formattedValue);
    });
    
    // Format expiry date
    $('#cardExpiry').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0,2) + '/' + value.substring(2,4);
        }
        $(this).val(value);
    });
    
    // CVV validation
    $('#cardCvv').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
    });
    
    // Initialize address section
    initializeAddressSection();
});

function initializeAddressSection() {
    // Handle saved address selection
    const savedAddressRadios = document.querySelectorAll('input[name="saved_address"]');
    savedAddressRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Address selected:', this.value);
            // Hide new address form when saved address is selected
            const newAddressForm = document.getElementById('newAddressForm');
            if (newAddressForm) {
                newAddressForm.style.display = 'none';
            }
            
            // Show add new address button
            const addNewButton = document.querySelector('.add-new-address');
            if (addNewButton) {
                addNewButton.style.display = 'block';
            }
            
            // Update continue button state
            updateContinueButton();
        });
    });
    
    // Auto-select default address if any
    const defaultAddress = document.querySelector('input[name="saved_address"][data-is-default="1"]');
    if (defaultAddress) {
        defaultAddress.checked = true;
        console.log('Default address auto-selected');
    }
    
    // Update continue button on page load
    updateContinueButton();
}

function updateContinueButton() {
    const continueBtn = document.getElementById('continue-to-payment');
    const hasSelectedAddress = document.querySelector('input[name="saved_address"]:checked');
    const newAddressForm = document.getElementById('newAddressForm');
    const isNewAddressVisible = newAddressForm && newAddressForm.style.display !== 'none';
    
    if (continueBtn) {
        if (hasSelectedAddress || isNewAddressVisible) {
            continueBtn.disabled = false;
            continueBtn.style.opacity = '1';
        } else {
            continueBtn.disabled = true;
            continueBtn.style.opacity = '0.5';
        }
    }
}

// Proceed to payment
function proceedToPayment() {
    alert('Function called - testing');
    console.log('proceedToPayment function called');
    console.log('Site URL:', window.siteUrl);
    console.log('CSRF Token:', window.csrfToken);
    
    // Check if window.siteUrl is defined
    if (!window.siteUrl) {
        alert('Error: siteUrl is not defined');
        return;
    }
    
    // For now, let's bypass the AJAX and just redirect
    // This will help us test if the redirect itself works
    console.log('Redirecting directly to payment...');
    const redirectUrl = window.siteUrl + '/checkout.php?step=payment';
    console.log('Redirect URL:', redirectUrl);
    
    window.location.href = redirectUrl;
    return;
    
    // Validate address form
    const form = document.getElementById('addressForm');
    if (form && !form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Save address data and proceed
    const addressData = {
        type: 'shipping',
        first_name: $('#firstName').val(),
        last_name: $('#lastName').val(),
        phone: $('#phone').val(),
        address_line_1: $('#addressLine1').val(),
        address_line_2: $('#addressLine2').val(),
        city: $('#city').val(),
        postal_code: $('#postalCode').val(),
        country: $('#country').val(),
        save_address: $('input[name="save_address"]:checked').length > 0,
        set_default: $('input[name="set_default"]:checked').length > 0
    };
    
    // Check for saved address selection
    const savedAddressId = $('input[name="saved_address"]:checked').val();
    if (savedAddressId) {
        addressData.saved_address_id = savedAddressId;
    }
    
    $.ajax({
        url: window.siteUrl + '/api/checkout.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'save_address',
            address_data: JSON.stringify(addressData),
            csrf_token: window.csrfToken
        },
        success: function(response) {
            if (response && response.success) {
                window.location.href = window.siteUrl + '/checkout.php?step=payment';
            } else {
                alert('Error: ' + (response ? response.message : 'Failed to save address'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response Text:', xhr.responseText);
            alert('Request failed. Please try again. Status: ' + xhr.status);
        }
    });
}
    
    // Debug logging
    console.log('Site URL:', window.siteUrl);
    console.log('CSRF Token:', window.csrfToken);
    
    // Validate address form
    const form = document.getElementById('addressForm');
    console.log('Address form found:', form);
    
    if (form && !form.checkValidity()) {
        console.log('Form validation failed');
        form.reportValidity();
        return;
    }
    
    console.log('Form validation passed, proceeding with AJAX call');
    
    // Save address data and proceed
    const addressData = {
        type: 'shipping',
        first_name: $('#firstName').val(),
        last_name: $('#lastName').val(),
        phone: $('#phone').val(),
        address_line_1: $('#addressLine1').val(),
        address_line_2: $('#addressLine2').val(),
        city: $('#city').val(),
        postal_code: $('#postalCode').val(),
        country: $('#country').val(),
        save_address: $('input[name="save_address"]:checked').length > 0,
        set_default: $('input[name="set_default"]:checked').length > 0
    };
    
    // Check for saved address selection
    const savedAddressId = $('input[name="saved_address"]:checked').val();
    if (savedAddressId) {
        addressData.saved_address_id = savedAddressId;
    }
    
    console.log('Address data:', addressData);
    
    $.ajax({
        url: window.siteUrl + '/api/checkout.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'save_address',
            address_data: JSON.stringify(addressData),
            csrf_token: window.csrfToken
        },
        success: function(response) {
            console.log('AJAX Success - Raw response:', response);
            
            if (response && response.success) {
                console.log('Redirecting to payment step');
                window.location.href = window.siteUrl + '/checkout.php?step=payment';
            } else {
                console.error('API returned error:', response ? response.message : 'Unknown error');
                alert('Error: ' + (response ? response.message : 'Failed to save address'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error Details:');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            console.error('Status Code:', xhr.status);
            
            alert('Request failed. Check console for details. Status: ' + xhr.status);
        }
    });
}

// Process payment
function processPayment() {
    alert('Place Order button clicked!');
    console.log('processPayment function called');
    
    const paymentMethod = $('input[name="payment_method"]:checked').val();
    console.log('Selected payment method:', paymentMethod);
    
    if (!paymentMethod) {
        alert('Please select a payment method');
        return;
    }
    
    // For now, let's just redirect to confirmation for COD
    if (paymentMethod === 'cod') {
        console.log('Processing COD payment...');
        window.location.href = window.siteUrl + '/checkout.php?step=confirmation';
        return;
    }
    
    // For disabled payment methods
    if (paymentMethod === 'esewa' || paymentMethod === 'khalti') {
        alert('This payment method is coming soon!');
        return;
    }
}
</script>

<?php
// Function to get cart data
function getCartData() {
    global $db;
    
    $cartItems = [];
    $subtotal = 0;
    
    if (isLoggedIn()) {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.price, p.sale_price, p.slug,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ? AND p.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([getCurrentUserId()]);
    } else {
        $stmt = $db->prepare("
            SELECT c.*, p.name, p.price, p.sale_price, p.slug,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.session_id = ? AND p.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([session_id()]);
    }
    
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cartItems as &$item) {
        $price = $item['sale_price'] ?: $item['price'];
        $item['unit_price'] = $price;
        $item['total_price'] = $price * $item['quantity'];
        $subtotal += $item['total_price'];
    }
    
    // Check for applied coupon
    $discount = 0;
    if (isset($_SESSION['applied_coupon'])) {
        $discount = $_SESSION['applied_coupon']['discount'];
    }
    
    $shipping = ($subtotal >= 2000) ? 0 : 100;
    $tax = $subtotal * 0.13; // 13% VAT
    $total = $subtotal + $shipping + $tax - $discount;
    
    return [
        'items' => $cartItems,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $total
    ];
}
?>

<script>
// Define required JavaScript variables for AJAX calls
window.siteUrl = '<?php echo SITE_URL; ?>';
window.csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

// Test redirect function - saves address and redirects to payment
function testRedirect() {
    console.log('Continue to Payment button clicked');
    
    // Check if a saved address is selected
    const savedAddressRadio = document.querySelector('input[name="saved_address"]:checked');
    if (savedAddressRadio) {
        console.log('Saved address selected:', savedAddressRadio.value);
        
        // Use saved address
        fetch(window.siteUrl + '/api/checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'use_saved_address',
                address_id: savedAddressRadio.value,
                csrf_token: window.csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Saved address response:', data);
            if (data && data.success) {
                console.log('Saved address loaded, redirecting to payment...');
                window.location.href = window.siteUrl + '/checkout.php?step=payment';
            } else {
                alert('Error using saved address: ' + (data ? data.message : 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error using saved address:', error);
            alert('An error occurred. Please try again.');
        });
        return;
    }
    
    // Get the address form
    const form = document.getElementById('addressForm');
    if (!form) {
        console.log('No address form found and no saved address selected');
        alert('Please select an address or fill in the address form');
        return;
    }
    
    // Validate the form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Prepare address data
    const addressData = {
        type: 'shipping',
        first_name: document.getElementById('firstName').value,
        last_name: document.getElementById('lastName').value,
        phone: document.getElementById('phone').value,
        address_line_1: document.getElementById('addressLine1').value,
        address_line_2: document.getElementById('addressLine2').value,
        city: document.getElementById('city').value,
        postal_code: document.getElementById('postalCode').value,
        country: document.getElementById('country').value,
        save_address: document.querySelector('input[name="save_address"]:checked') ? true : false,
        set_default: document.querySelector('input[name="set_default"]:checked') ? true : false
    };
    
    console.log('Saving address data:', addressData);
    
    // Save address to session via API
    fetch(window.siteUrl + '/api/checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'save_address',
            address_data: JSON.stringify(addressData),
            csrf_token: window.csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Address save response:', data);
        if (data && data.success) {
            console.log('Address saved successfully, redirecting to payment...');
            window.location.href = window.siteUrl + '/checkout.php?step=payment';
        } else {
            alert('Error saving address: ' + (data ? data.message : 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving address:', error);
        alert('An error occurred while saving the address. Please try again.');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
