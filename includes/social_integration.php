<?php
// Social Authentication Integration
class SocialAuth {
    private $conn;
    private $googleConfig;
    private $facebookConfig;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->loadConfigs();
    }
    
    private function loadConfigs() {
        // Google OAuth configuration
        $this->googleConfig = [
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
            'redirect_uri' => SITE_URL . '/api/social_auth.php?provider=google'
        ];
        
        // Facebook OAuth configuration
        $this->facebookConfig = [
            'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? '',
            'app_secret' => $_ENV['FACEBOOK_APP_SECRET'] ?? '',
            'redirect_uri' => SITE_URL . '/api/social_auth.php?provider=facebook'
        ];
    }
    
    public function getGoogleAuthUrl($state = null) {
        $params = [
            'client_id' => $this->googleConfig['client_id'],
            'redirect_uri' => $this->googleConfig['redirect_uri'],
            'scope' => 'email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        if ($state) {
            $params['state'] = $state;
        }
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    public function getFacebookAuthUrl($state = null) {
        $params = [
            'client_id' => $this->facebookConfig['app_id'],
            'redirect_uri' => $this->facebookConfig['redirect_uri'],
            'scope' => 'email,public_profile',
            'response_type' => 'code'
        ];
        
        if ($state) {
            $params['state'] = $state;
        }
        
        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }
    
    public function handleGoogleCallback($code) {
        try {
            // Exchange code for access token
            $tokenData = $this->getGoogleAccessToken($code);
            
            if (!$tokenData) {
                throw new Exception('Failed to get access token');
            }
            
            // Get user info from Google
            $userInfo = $this->getGoogleUserInfo($tokenData['access_token']);
            
            if (!$userInfo) {
                throw new Exception('Failed to get user info');
            }
            
            // Create or update user account
            return $this->createOrUpdateSocialUser('google', $userInfo);
            
        } catch (Exception $e) {
            error_log("Google OAuth error: " . $e->getMessage());
            return false;
        }
    }
    
    public function handleFacebookCallback($code) {
        try {
            // Exchange code for access token
            $tokenData = $this->getFacebookAccessToken($code);
            
            if (!$tokenData) {
                throw new Exception('Failed to get access token');
            }
            
            // Get user info from Facebook
            $userInfo = $this->getFacebookUserInfo($tokenData['access_token']);
            
            if (!$userInfo) {
                throw new Exception('Failed to get user info');
            }
            
            // Create or update user account
            return $this->createOrUpdateSocialUser('facebook', $userInfo);
            
        } catch (Exception $e) {
            error_log("Facebook OAuth error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getGoogleAccessToken($code) {
        $postData = [
            'client_id' => $this->googleConfig['client_id'],
            'client_secret' => $this->googleConfig['client_secret'],
            'redirect_uri' => $this->googleConfig['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    private function getFacebookAccessToken($code) {
        $params = [
            'client_id' => $this->facebookConfig['app_id'],
            'client_secret' => $this->facebookConfig['app_secret'],
            'redirect_uri' => $this->facebookConfig['redirect_uri'],
            'code' => $code
        ];
        
        $url = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    private function getGoogleUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    private function getFacebookUserInfo($accessToken) {
        $url = 'https://graph.facebook.com/v18.0/me?fields=id,name,email,first_name,last_name,picture&access_token=' . $accessToken;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    private function createOrUpdateSocialUser($provider, $userInfo) {
        try {
            $email = $userInfo['email'] ?? '';
            $socialId = $userInfo['id'] ?? '';
            $name = $userInfo['name'] ?? '';
            $firstName = $userInfo['given_name'] ?? $userInfo['first_name'] ?? '';
            $lastName = $userInfo['family_name'] ?? $userInfo['last_name'] ?? '';
            $picture = $userInfo['picture'] ?? ($userInfo['picture']['data']['url'] ?? '');
            
            if (empty($email) || empty($socialId)) {
                throw new Exception('Missing required user information');
            }
            
            // Check if user exists by email
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Update existing user with social info
                $userId = $existingUser['id'];
                
                $stmt = $this->conn->prepare("
                    UPDATE users 
                    SET {$provider}_id = ?, profile_picture = ?, last_login = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$socialId, $picture, $userId]);
                
                // Log social login
                $this->logSocialLogin($userId, $provider);
                
                return [
                    'user_id' => $userId,
                    'email' => $email,
                    'name' => $name,
                    'is_new_user' => false
                ];
            } else {
                // Create new user
                $stmt = $this->conn->prepare("
                    INSERT INTO users (
                        email, first_name, last_name, {$provider}_id, 
                        profile_picture, email_verified, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, 1, 'active', NOW())
                ");
                
                $stmt->execute([
                    $email, $firstName, $lastName, $socialId, $picture
                ]);
                
                $userId = $this->conn->lastInsertId();
                
                // Log social registration
                $this->logSocialLogin($userId, $provider, true);
                
                return [
                    'user_id' => $userId,
                    'email' => $email,
                    'name' => $name,
                    'is_new_user' => true
                ];
            }
            
        } catch (Exception $e) {
            error_log("Social user creation error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logSocialLogin($userId, $provider, $isRegistration = false) {
        try {
            $action = $isRegistration ? 'social_registration' : 'social_login';
            $stmt = $this->conn->prepare("
                INSERT INTO user_activity_logs (user_id, action, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $details = json_encode([
                'provider' => $provider,
                'is_registration' => $isRegistration
            ]);
            
            $stmt->execute([
                $userId,
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Social login logging error: " . $e->getMessage());
        }
    }
    
    public function unlinkSocialAccount($userId, $provider) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET {$provider}_id = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            return true;
        } catch (Exception $e) {
            error_log("Social unlink error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getSocialConnections($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT google_id, facebook_id 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            return [
                'google' => !empty($user['google_id']),
                'facebook' => !empty($user['facebook_id'])
            ];
        } catch (Exception $e) {
            error_log("Get social connections error: " . $e->getMessage());
            return ['google' => false, 'facebook' => false];
        }
    }
}

// Social Sharing functionality
class SocialShare {
    public static function getShareUrls($url, $title, $description = '', $image = '') {
        $encodedUrl = urlencode($url);
        $encodedTitle = urlencode($title);
        $encodedDescription = urlencode($description);
        $encodedImage = urlencode($image);
        
        return [
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}",
            'twitter' => "https://twitter.com/intent/tweet?url={$encodedUrl}&text={$encodedTitle}",
            'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url={$encodedUrl}",
            'whatsapp' => "https://api.whatsapp.com/send?text={$encodedTitle}%20{$encodedUrl}",
            'telegram' => "https://t.me/share/url?url={$encodedUrl}&text={$encodedTitle}",
            'pinterest' => "https://pinterest.com/pin/create/button/?url={$encodedUrl}&media={$encodedImage}&description={$encodedDescription}",
            'reddit' => "https://reddit.com/submit?url={$encodedUrl}&title={$encodedTitle}",
            'email' => "mailto:?subject={$encodedTitle}&body={$encodedDescription}%20{$encodedUrl}"
        ];
    }
    
    public static function logShare($productId, $platform, $userId = null) {
        try {
            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO social_shares (product_id, user_id, platform, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $productId,
                $userId,
                $platform,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Social share logging error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function getShareStats($productId = null) {
        try {
            $conn = Database::getInstance()->getConnection();
            
            if ($productId) {
                $stmt = $conn->prepare("
                    SELECT platform, COUNT(*) as share_count
                    FROM social_shares 
                    WHERE product_id = ?
                    GROUP BY platform
                    ORDER BY share_count DESC
                ");
                $stmt->execute([$productId]);
            } else {
                $stmt = $conn->prepare("
                    SELECT platform, COUNT(*) as share_count
                    FROM social_shares 
                    GROUP BY platform
                    ORDER BY share_count DESC
                ");
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Social share stats error: " . $e->getMessage());
            return [];
        }
    }
}
?>
