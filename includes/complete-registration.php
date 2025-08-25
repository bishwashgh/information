<?php
require_once 'config.php';

function completeUserRegistration($registrationData) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Create user account
        $hashedPassword = password_hash($registrationData['password'], PASSWORD_DEFAULT);
        
        // Check what columns exist in the users table
        $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        // Build the INSERT query based on available columns
        $baseColumns = ['first_name', 'last_name', 'email', 'password', 'created_at'];
        $baseValues = [
            $registrationData['firstName'], 
            $registrationData['lastName'], 
            $registrationData['email'], 
            $hashedPassword, 
            date('Y-m-d H:i:s')
        ];
        
        $insertColumns = $baseColumns;
        $insertValues = $baseValues;
        
        // Add optional columns if they exist
        if (in_array('phone', $columns) && !empty($registrationData['phone'])) {
            $insertColumns[] = 'phone';
            $insertValues[] = $registrationData['phone'];
        }
        
        if (in_array('status', $columns)) {
            $insertColumns[] = 'status';
            $insertValues[] = 'active';
        }
        
        if (in_array('role', $columns)) {
            $insertColumns[] = 'role';
            $insertValues[] = 'customer';
        }
        
        if (in_array('newsletter_subscribed', $columns)) {
            $insertColumns[] = 'newsletter_subscribed';
            $insertValues[] = $registrationData['newsletter'] ? 1 : 0;
        }
        
        if (in_array('email_verified', $columns)) {
            $insertColumns[] = 'email_verified';
            $insertValues[] = 1; // Email is verified through OTP
        }
        
        if (in_array('email_verified_at', $columns)) {
            $insertColumns[] = 'email_verified_at';
            $insertValues[] = date('Y-m-d H:i:s');
        }
        
        $sql = "INSERT INTO users (" . implode(', ', $insertColumns) . ") VALUES (" . str_repeat('?,', count($insertColumns) - 1) . "?)";
        $stmt = $db->prepare($sql);
        $stmt->execute($insertValues);
        
        $userId = $db->lastInsertId();
        
        // Newsletter subscription (if table exists)
        if ($registrationData['newsletter']) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO newsletter_subscriptions (email, status, subscribed_at)
                    VALUES (?, 'active', NOW())
                    ON DUPLICATE KEY UPDATE status = 'active', subscribed_at = NOW()
                ");
                $stmt->execute([$registrationData['email']]);
            } catch (Exception $e) {
                // Newsletter table might not exist, continue without it
                error_log("Newsletter subscription failed: " . $e->getMessage());
            }
        }
        
        // Auto-login after registration
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $registrationData['email'];
        $_SESSION['first_name'] = $registrationData['firstName'];
        $_SESSION['last_name'] = $registrationData['lastName'];
        $_SESSION['role'] = 'customer';
        
        // Merge guest cart if exists
        if (isset($_SESSION['guest_cart_items']) && !empty($_SESSION['guest_cart_items'])) {
            try {
                if (function_exists('mergeGuestCartItems')) {
                    mergeGuestCartItems($userId);
                }
            } catch (Exception $e) {
                error_log("Cart merge error: " . $e->getMessage());
                // Don't fail registration if cart merge fails
            }
        }
        
        $db->commit();
        
        // Clean up session
        unset($_SESSION['pending_registration']);
        
        // Send welcome email
        try {
            sendWelcomeEmail($registrationData['email'], $registrationData['firstName']);
        } catch (Exception $e) {
            error_log("Welcome email error: " . $e->getMessage());
            // Don't fail registration if email fails
        }
        
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Registration completed successfully!'
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Registration completion error: " . $e->getMessage());
        error_log("Registration completion error trace: " . $e->getTraceAsString());
        
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage()
        ];
    }
}

function sendWelcomeEmail($email, $firstName) {
    // Create welcome email content
    $subject = 'Welcome to ' . SITE_NAME . '!';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome to ' . SITE_NAME . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0;
                background-color: #f4f4f4;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 20px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                margin-top: 20px;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
                border-radius: 10px 10px 0 0;
                margin: -20px -20px 20px -20px;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
            }
            .content {
                padding: 20px;
            }
            .welcome-message {
                font-size: 18px;
                margin-bottom: 20px;
                color: #4a5568;
            }
            .features {
                background: #f8fafc;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .feature-item {
                margin: 10px 0;
                padding: 10px 0;
                border-bottom: 1px solid #e2e8f0;
            }
            .feature-item:last-child {
                border-bottom: none;
            }
            .cta-button {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: bold;
                text-align: center;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                color: #666;
                font-size: 14px;
                border-top: 1px solid #e2e8f0;
                margin-top: 30px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Welcome to ' . SITE_NAME . '!</h1>
                <p>Your account has been successfully created</p>
            </div>
            
            <div class="content">
                <div class="welcome-message">
                    <p>Hi ' . htmlspecialchars($firstName) . ',</p>
                    <p>Thank you for joining ' . SITE_NAME . '! We\'re excited to have you as part of our community.</p>
                </div>
                
                <div class="features">
                    <h3>What you can do now:</h3>
                    <div class="feature-item">
                        <strong>🛍️ Shop Products</strong> - Browse our extensive collection
                    </div>
                    <div class="feature-item">
                        <strong>📦 Track Orders</strong> - Monitor your purchases in real-time
                    </div>
                    <div class="feature-item">
                        <strong>💫 Exclusive Offers</strong> - Get access to member-only deals
                    </div>
                    <div class="feature-item">
                        <strong>📱 Account Management</strong> - Update preferences anytime
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <a href="' . SITE_URL . '/user/dashboard.php" class="cta-button">
                        Go to Your Dashboard
                    </a>
                </div>
                
                <p>If you have any questions, feel free to contact our support team.</p>
            </div>
            
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                <p>This email was sent to ' . htmlspecialchars($email) . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>',
        'Reply-To: support@' . $_SERVER['HTTP_HOST'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Send email
    return mail($email, $subject, $message, implode("\r\n", $headers));
}
?>
