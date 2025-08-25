<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode('/user/addresses.php'));
    exit;
}

$pageTitle = 'My Addresses';
$pageDescription = 'Manage your shipping and billing addresses';

$db = Database::getInstance()->getConnection();
$userId = getCurrentUserId();

$error = '';
$success = '';

// Get current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure user data is loaded
if (!$user) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        $type = sanitizeInput($_POST['type'] ?? '');
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $company = sanitizeInput($_POST['company'] ?? '');
        $address1 = sanitizeInput($_POST['address_1'] ?? '');
        $address2 = sanitizeInput($_POST['address_2'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $postalCode = sanitizeInput($_POST['postal_code'] ?? '');
        $country = sanitizeInput($_POST['country'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($firstName) || empty($lastName) || empty($address1) || empty($city) || empty($state) || empty($postalCode) || empty($country)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                // If this is set as default, unset all other default addresses of the same type
                if ($isDefault) {
                    $stmt = $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
                    $stmt->execute([$userId, $type]);
                }
                
                $stmt = $db->prepare("
                    INSERT INTO user_addresses 
                    (user_id, type, first_name, last_name, company, address_1, address_2, city, state, postal_code, country, phone, is_default, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $userId, $type, $firstName, $lastName, $company, 
                    $address1, $address2, $city, $state, $postalCode, 
                    $country, $phone, $isDefault
                ]);
                
                $success = 'Address added successfully';
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "doesn't exist") !== false) {
                    $error = 'Database tables are not set up yet. Please run the SQL setup file: sql/user_addresses_and_security.sql';
                } else {
                    $error = 'Failed to add address. Please try again.';
                }
            }
        }
    }
    
    if (isset($_POST['delete_address'])) {
        $addressId = (int)($_POST['address_id'] ?? 0);
        
        try {
            $stmt = $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$addressId, $userId]);
            
            $success = 'Address deleted successfully';
            
        } catch (PDOException $e) {
            $error = 'Failed to delete address. Please try again.';
        }
    }
    
    if (isset($_POST['set_default'])) {
        $addressId = (int)($_POST['address_id'] ?? 0);
        $type = sanitizeInput($_POST['type'] ?? '');
        
        try {
            // Unset all default addresses of this type
            $stmt = $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND type = ?");
            $stmt->execute([$userId, $type]);
            
            // Set this address as default
            $stmt = $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$addressId, $userId]);
            
            $success = 'Default address updated successfully';
            
        } catch (PDOException $e) {
            $error = 'Failed to update default address. Please try again.';
        }
    }
}

// Get user addresses
$addresses = [];
try {
    $stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY type, is_default DESC, created_at DESC");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $error = 'Database tables are not set up yet. Please run the SQL setup file: sql/user_addresses_and_security.sql';
    } else {
        $error = 'Failed to load addresses. Please try again.';
    }
}

include '../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Nunito:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* Addresses Page - Homepage Style */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
    min-height: 100vh;
    color: #333;
}

.addresses-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
    min-height: 100vh;
}

/* Header Styling */
.addresses-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.addresses-header h1 {
    font-family: 'Nunito', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.addresses-header p {
    font-size: 1.1rem;
    color: #666;
    font-weight: 400;
}

/* Navigation */
.profile-nav {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.nav-breadcrumb {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.breadcrumb-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-breadcrumb a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav-breadcrumb a:hover {
    color: #0056b3;
}

.nav-breadcrumb span {
    color: #666;
}

/* Add Address Button */
.add-address-btn {
    background-color: #007bff;
    color: white !important;
    text-decoration: none;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    font-weight: 500;
    transition: background-color 0.3s ease;
}

.add-address-btn:hover {
    background-color: #0056b3;
    color: white !important;
}

/* Main Content */
.addresses-content {
    margin-top: 1rem;
}

.addresses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.address-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
    transition: box-shadow 0.3s ease;
}

.address-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.address-card.default {
    border: 2px solid #007bff;
    background-color: #f8f9ff;
}

.address-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.address-type {
    background-color: #007bff;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
}

.default-badge {
    background-color: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.address-info h3 {
    font-family: 'Nunito', sans-serif;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.address-details {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.address-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-small {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid #007bff;
    color: #007bff;
}

.btn-outline:hover {
    background: #007bff;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

/* Add Address Form */
.add-address-form {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    display: none;
    border: 1px solid #e0e0e0;
}

.add-address-form.show {
    display: block;
}

.form-header {
    margin-bottom: 2rem;
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e0e0e0;
}

.form-header h2 {
    font-family: 'Nunito', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.form-header p {
    color: #666;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 500;
    color: #333;
    font-size: 0.95rem;
}

.form-control, .form-select {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s ease;
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: #007bff;
}

/* Checkbox Styling */
.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 5px;
    margin-bottom: 1rem;
    border: 1px solid #e0e0e0;
}

.checkbox-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

.checkbox-label {
    font-weight: 400;
    color: #333;
    cursor: pointer;
}

/* Button Styling */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: inline-block;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

/* Alert Styling */
.alert {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1.5rem;
    font-weight: 500;
    border: 1px solid transparent;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
    text-align: center;
    padding: 2rem;
    font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .addresses-container {
        padding: 1rem 0.5rem;
    }
    
    .addresses-header h1 {
        font-size: 2rem;
    }
    
    .nav-breadcrumb {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .addresses-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .address-card {
        padding: 1rem;
    }
}
</style>

<div class="addresses-container">
    <!-- Header -->
    <div class="addresses-header">
        <h1>My Addresses</h1>
        <p>Manage your shipping and billing addresses</p>
    </div>

    <!-- Navigation -->
    <div class="profile-nav">
        <div class="nav-breadcrumb">
            <div class="breadcrumb-left">
                <a href="<?php echo SITE_URL; ?>/user/profile.php">← Back to Profile</a>
                <span>•</span>
                <span>My Addresses</span>
            </div>
            <a href="#" class="add-address-btn" onclick="toggleAddForm()">
                <i class="fas fa-plus"></i> Add New Address
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Add Address Form -->
    <div class="add-address-form" id="addAddressForm">
        <div class="form-header">
            <h2>Add New Address</h2>
            <p>Fill in the details for your new address</p>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group full-width">
                <label for="type" class="form-label">Address Type *</label>
                <select id="type" name="type" class="form-select" required>
                    <option value="">Select Address Type</option>
                    <option value="home">Home</option>
                    <option value="work">Work</option>
                    <option value="billing">Billing</option>
                    <option value="shipping">Shipping</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required>
                </div>

                <div class="form-group full-width">
                    <label for="company" class="form-label">Company (Optional)</label>
                    <input type="text" id="company" name="company" class="form-control">
                </div>

                <div class="form-group full-width">
                    <label for="address_1" class="form-label">Address Line 1 *</label>
                    <input type="text" id="address_1" name="address_1" class="form-control" required>
                </div>

                <div class="form-group full-width">
                    <label for="address_2" class="form-label">Address Line 2 (Optional)</label>
                    <input type="text" id="address_2" name="address_2" class="form-control">
                </div>

                <div class="form-group">
                    <label for="city" class="form-label">City *</label>
                    <input type="text" id="city" name="city" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="state" class="form-label">State/Province *</label>
                    <input type="text" id="state" name="state" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="postal_code" class="form-label">Postal Code *</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="country" class="form-label">Country *</label>
                    <select id="country" name="country" class="form-select" required>
                        <option value="">Select Country</option>
                        <option value="Pakistan">Pakistan</option>
                        <option value="India">India</option>
                        <option value="Bangladesh">Bangladesh</option>
                        <option value="United States">United States</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="Canada">Canada</option>
                        <option value="Australia">Australia</option>
                        <option value="Germany">Germany</option>
                        <option value="France">France</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control">
                </div>
            </div>

            <div class="checkbox-item">
                <input type="checkbox" id="is_default" name="is_default" value="1">
                <label for="is_default" class="checkbox-label">
                    Set as default address for this type
                </label>
            </div>

            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" name="add_address" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Address
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleAddForm()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Main Content -->
    <div class="addresses-content">
        <?php if (empty($addresses)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>No addresses found!</strong><br>
                Click "Add New Address" to add your first address.
            </div>
        <?php else: ?>
            <div class="addresses-grid">
                <?php foreach ($addresses as $address): ?>
                    <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                        <div class="address-header">
                            <span class="address-type">
                                <?php echo ucfirst($address['type']); ?>
                            </span>
                            <?php if ($address['is_default']): ?>
                                <span class="default-badge">Default</span>
                            <?php endif; ?>
                        </div>

                        <div class="address-info">
                            <h3><?php echo sanitizeInput($address['first_name'] . ' ' . $address['last_name']); ?></h3>
                            <?php if ($address['company']): ?>
                                <div><strong><?php echo sanitizeInput($address['company']); ?></strong></div>
                            <?php endif; ?>
                            
                            <div class="address-details">
                                <?php if (isset($address['address_1'])): ?>
                                    <?php echo sanitizeInput($address['address_1']); ?><br>
                                <?php endif; ?>
                                <?php if (isset($address['address_2']) && $address['address_2']): ?>
                                    <?php echo sanitizeInput($address['address_2']); ?><br>
                                <?php endif; ?>
                                <?php if (isset($address['city'], $address['state'], $address['postal_code'])): ?>
                                    <?php echo sanitizeInput($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?><br>
                                <?php endif; ?>
                                <?php if (isset($address['country'])): ?>
                                    <?php echo sanitizeInput($address['country']); ?>
                                <?php endif; ?>
                                <?php if (isset($address['phone']) && $address['phone']): ?>
                                    <br><strong>Phone:</strong> <?php echo sanitizeInput($address['phone']); ?>
                                <?php endif; ?>
                            </div>

                            <div class="address-actions">
                                <?php if (!$address['is_default']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <input type="hidden" name="type" value="<?php echo $address['type']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" name="set_default" class="btn-small btn-outline">
                                            <i class="fas fa-star"></i> Set Default
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this address?')">
                                    <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <button type="submit" name="delete_address" class="btn-small btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAddForm() {
    const form = document.getElementById('addAddressForm');
    form.classList.toggle('show');
    
    if (form.classList.contains('show')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-info)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});
</script>

<?php include '../includes/footer.php'; ?>
