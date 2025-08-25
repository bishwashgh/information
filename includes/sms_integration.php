<?php
// SMS and WhatsApp Integration for notifications and OTP
class SMSIntegration {
    private $conn;
    private $twilioConfig;
    private $whatsappConfig;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->loadConfigs();
    }
    
    private function loadConfigs() {
        // Twilio SMS configuration
        $this->twilioConfig = [
            'account_sid' => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
            'auth_token' => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
            'from_number' => $_ENV['TWILIO_FROM_NUMBER'] ?? ''
        ];
        
        // WhatsApp Business API configuration
        $this->whatsappConfig = [
            'business_id' => $_ENV['WHATSAPP_BUSINESS_ID'] ?? '',
            'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? '',
            'phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? ''
        ];
    }
    
    public function sendOTP($phoneNumber, $otp) {
        $message = "Your OTP for " . SITE_NAME . " is: {$otp}. Valid for 10 minutes. Do not share with anyone.";
        
        // Try WhatsApp first, fallback to SMS
        $whatsappSent = $this->sendWhatsAppMessage($phoneNumber, $message);
        
        if (!$whatsappSent) {
            return $this->sendSMS($phoneNumber, $message);
        }
        
        return $whatsappSent;
    }
    
    public function sendOrderNotification($phoneNumber, $orderData) {
        $message = $this->generateOrderMessage($orderData);
        
        // Try WhatsApp first for better formatting
        $whatsappSent = $this->sendWhatsAppTemplate($phoneNumber, 'order_confirmation', $orderData);
        
        if (!$whatsappSent) {
            return $this->sendSMS($phoneNumber, $message);
        }
        
        return $whatsappSent;
    }
    
    public function sendShippingUpdate($phoneNumber, $trackingData) {
        $message = $this->generateShippingMessage($trackingData);
        
        // Try WhatsApp first
        $whatsappSent = $this->sendWhatsAppTemplate($phoneNumber, 'shipping_update', $trackingData);
        
        if (!$whatsappSent) {
            return $this->sendSMS($phoneNumber, $message);
        }
        
        return $whatsappSent;
    }
    
    private function sendSMS($phoneNumber, $message) {
        try {
            if (empty($this->twilioConfig['account_sid'])) {
                throw new Exception('Twilio not configured');
            }
            
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioConfig['account_sid']}/Messages.json";
            
            $data = [
                'From' => $this->twilioConfig['from_number'],
                'To' => $phoneNumber,
                'Body' => $message
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->twilioConfig['account_sid'] . ':' . $this->twilioConfig['auth_token']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $responseData = json_decode($response, true);
                $this->logSMSDelivery($phoneNumber, $message, 'sms', 'sent', $responseData['sid'] ?? '');
                return true;
            } else {
                error_log("SMS sending failed: " . $response);
                $this->logSMSDelivery($phoneNumber, $message, 'sms', 'failed', '', $response);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("SMS error: " . $e->getMessage());
            $this->logSMSDelivery($phoneNumber, $message, 'sms', 'error', '', $e->getMessage());
            return false;
        }
    }
    
    private function sendWhatsAppMessage($phoneNumber, $message) {
        try {
            if (empty($this->whatsappConfig['access_token'])) {
                return false; // WhatsApp not configured, fallback to SMS
            }
            
            $url = "https://graph.facebook.com/v18.0/{$this->whatsappConfig['phone_number_id']}/messages";
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->whatsappConfig['access_token']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $responseData = json_decode($response, true);
                $this->logSMSDelivery($phoneNumber, $message, 'whatsapp', 'sent', $responseData['messages'][0]['id'] ?? '');
                return true;
            } else {
                error_log("WhatsApp sending failed: " . $response);
                return false; // Fallback to SMS
            }
            
        } catch (Exception $e) {
            error_log("WhatsApp error: " . $e->getMessage());
            return false; // Fallback to SMS
        }
    }
    
    private function sendWhatsAppTemplate($phoneNumber, $templateName, $parameters) {
        try {
            if (empty($this->whatsappConfig['access_token'])) {
                return false;
            }
            
            $url = "https://graph.facebook.com/v18.0/{$this->whatsappConfig['phone_number_id']}/messages";
            
            $templateData = $this->getWhatsAppTemplate($templateName, $parameters);
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'template',
                'template' => $templateData
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->whatsappConfig['access_token']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $responseData = json_decode($response, true);
                $this->logSMSDelivery($phoneNumber, "Template: {$templateName}", 'whatsapp_template', 'sent', $responseData['messages'][0]['id'] ?? '');
                return true;
            } else {
                error_log("WhatsApp template sending failed: " . $response);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("WhatsApp template error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getWhatsAppTemplate($templateName, $parameters) {
        switch ($templateName) {
            case 'order_confirmation':
                return [
                    'name' => 'order_confirmation',
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $parameters['customer_name']],
                                ['type' => 'text', 'text' => $parameters['order_number']],
                                ['type' => 'text', 'text' => $parameters['total_amount']]
                            ]
                        ]
                    ]
                ];
                
            case 'shipping_update':
                return [
                    'name' => 'shipping_update',
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $parameters['order_number']],
                                ['type' => 'text', 'text' => $parameters['status']],
                                ['type' => 'text', 'text' => $parameters['tracking_number']]
                            ]
                        ]
                    ]
                ];
                
            default:
                return [
                    'name' => 'simple_message',
                    'language' => ['code' => 'en'],
                    'components' => []
                ];
        }
    }
    
    private function generateOrderMessage($orderData) {
        return "Hi {$orderData['customer_name']}, your order #{$orderData['order_number']} for ₹{$orderData['total_amount']} has been confirmed! Track your order at " . SITE_URL . "/track-order?id={$orderData['order_number']}";
    }
    
    private function generateShippingMessage($trackingData) {
        return "Order #{$trackingData['order_number']} update: {$trackingData['status']}. Tracking: {$trackingData['tracking_number']}. Estimated delivery: {$trackingData['estimated_delivery']}";
    }
    
    private function logSMSDelivery($phoneNumber, $message, $type, $status, $messageId = '', $error = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO sms_logs (
                    phone_number, message_content, message_type, status, 
                    message_id, error_message, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $phoneNumber,
                $message,
                $type,
                $status,
                $messageId,
                $error
            ]);
        } catch (Exception $e) {
            error_log("SMS logging error: " . $e->getMessage());
        }
    }
    
    public function generateAndStoreOTP($phoneNumber, $userId = null) {
        try {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store OTP in database
            $stmt = $this->conn->prepare("
                INSERT INTO otp_verifications (
                    phone_number, user_id, otp_code, expires_at, created_at
                ) VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                otp_code = VALUES(otp_code), 
                expires_at = VALUES(expires_at), 
                attempts = 0,
                verified_at = NULL
            ");
            
            $stmt->execute([$phoneNumber, $userId, $otp, $expiresAt]);
            
            return $otp;
        } catch (Exception $e) {
            error_log("OTP generation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function verifyOTP($phoneNumber, $otp) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, user_id, attempts
                FROM otp_verifications 
                WHERE phone_number = ? 
                AND otp_code = ? 
                AND expires_at > NOW() 
                AND verified_at IS NULL
            ");
            $stmt->execute([$phoneNumber, $otp]);
            $otpRecord = $stmt->fetch();
            
            if (!$otpRecord) {
                // Invalid or expired OTP, increment attempts
                $this->incrementOTPAttempts($phoneNumber);
                return false;
            }
            
            // Mark as verified
            $stmt = $this->conn->prepare("
                UPDATE otp_verifications 
                SET verified_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$otpRecord['id']]);
            
            return [
                'verified' => true,
                'user_id' => $otpRecord['user_id']
            ];
            
        } catch (Exception $e) {
            error_log("OTP verification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function incrementOTPAttempts($phoneNumber) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE otp_verifications 
                SET attempts = attempts + 1 
                WHERE phone_number = ? 
                AND verified_at IS NULL
            ");
            $stmt->execute([$phoneNumber]);
        } catch (Exception $e) {
            error_log("OTP attempt increment error: " . $e->getMessage());
        }
    }
    
    public function getDeliveryStats($days = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    message_type,
                    status,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM sms_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY message_type, status, DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("SMS stats error: " . $e->getMessage());
            return [];
        }
    }
}

// OTP Verification helper functions
class OTPManager {
    private $smsIntegration;
    
    public function __construct($smsIntegration) {
        $this->smsIntegration = $smsIntegration;
    }
    
    public function sendVerificationOTP($phoneNumber, $userId = null) {
        $otp = $this->smsIntegration->generateAndStoreOTP($phoneNumber, $userId);
        
        if ($otp) {
            return $this->smsIntegration->sendOTP($phoneNumber, $otp);
        }
        
        return false;
    }
    
    public function verifyOTP($phoneNumber, $otp) {
        return $this->smsIntegration->verifyOTP($phoneNumber, $otp);
    }
    
    public static function formatPhoneNumber($phoneNumber, $countryCode = '+91') {
        // Remove all non-digit characters
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        
        // Add country code if not present
        if (!str_starts_with($phoneNumber, '91') && !str_starts_with($phoneNumber, '+91')) {
            $phoneNumber = '91' . $phoneNumber;
        }
        
        // Add + if not present
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
    
    public static function isValidPhoneNumber($phoneNumber) {
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        
        // Indian phone number validation (10 digits)
        return preg_match('/^[6-9]\d{9}$/', $phoneNumber);
    }
}
?>
