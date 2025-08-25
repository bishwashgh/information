<?php
// Milestone 13: Advanced Marketing & Automation Features
class MarketingAutomationManager {
    private $conn;
    private $emailService;
    private $smsService;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
    }
    
    // Marketing Campaigns
    public function createCampaign($campaignData) {
        try {
            $this->conn->beginTransaction();
            
            // Validate campaign data
            $validation = $this->validateCampaignData($campaignData);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            // Create campaign
            $stmt = $this->conn->prepare("
                INSERT INTO marketing_campaigns (
                    name, type, description, target_audience, start_date, end_date,
                    budget, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW())
            ");
            
            $stmt->execute([
                $campaignData['name'],
                $campaignData['type'],
                $campaignData['description'],
                json_encode($campaignData['target_audience']),
                $campaignData['start_date'],
                $campaignData['end_date'],
                $campaignData['budget'],
                $campaignData['created_by']
            ]);
            
            $campaignId = $this->conn->lastInsertId();
            
            // Create campaign content
            if (isset($campaignData['content'])) {
                $this->createCampaignContent($campaignId, $campaignData['content']);
            }
            
            // Create campaign rules/triggers
            if (isset($campaignData['triggers'])) {
                $this->createCampaignTriggers($campaignId, $campaignData['triggers']);
            }
            
            // Create campaign segments
            if (isset($campaignData['segments'])) {
                $this->createCampaignSegments($campaignId, $campaignData['segments']);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'message' => 'Campaign created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Create campaign error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function validateCampaignData($data) {
        $required = ['name', 'type', 'target_audience', 'start_date', 'created_by'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'message' => "Field '{$field}' is required"];
            }
        }
        
        // Validate campaign type
        $validTypes = ['email', 'sms', 'push_notification', 'discount', 'loyalty', 'abandoned_cart', 'welcome_series'];
        if (!in_array($data['type'], $validTypes)) {
            return ['valid' => false, 'message' => 'Invalid campaign type'];
        }
        
        // Validate dates
        if (strtotime($data['start_date']) < time()) {
            return ['valid' => false, 'message' => 'Start date cannot be in the past'];
        }
        
        if (isset($data['end_date']) && strtotime($data['end_date']) <= strtotime($data['start_date'])) {
            return ['valid' => false, 'message' => 'End date must be after start date'];
        }
        
        return ['valid' => true, 'message' => 'Valid data'];
    }
    
    private function createCampaignContent($campaignId, $content) {
        foreach ($content as $contentItem) {
            $stmt = $this->conn->prepare("
                INSERT INTO campaign_content (
                    campaign_id, content_type, subject, body, template_id,
                    personalization_data, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $campaignId,
                $contentItem['type'],
                $contentItem['subject'] ?? null,
                $contentItem['body'],
                $contentItem['template_id'] ?? null,
                json_encode($contentItem['personalization'] ?? [])
            ]);
        }
    }
    
    private function createCampaignTriggers($campaignId, $triggers) {
        foreach ($triggers as $trigger) {
            $stmt = $this->conn->prepare("
                INSERT INTO campaign_triggers (
                    campaign_id, trigger_type, trigger_condition, trigger_value,
                    delay_minutes, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $campaignId,
                $trigger['type'],
                $trigger['condition'],
                json_encode($trigger['value']),
                $trigger['delay_minutes'] ?? 0
            ]);
        }
    }
    
    // Email Marketing Workflows
    public function createEmailWorkflow($workflowData) {
        try {
            $this->conn->beginTransaction();
            
            // Create workflow
            $stmt = $this->conn->prepare("
                INSERT INTO email_workflows (
                    name, description, trigger_event, is_active, created_at
                ) VALUES (?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $workflowData['name'],
                $workflowData['description'],
                $workflowData['trigger_event']
            ]);
            
            $workflowId = $this->conn->lastInsertId();
            
            // Create workflow steps
            foreach ($workflowData['steps'] as $index => $step) {
                $stmt = $this->conn->prepare("
                    INSERT INTO workflow_steps (
                        workflow_id, step_order, step_type, delay_days, delay_hours,
                        email_template_id, conditions, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $workflowId,
                    $index + 1,
                    $step['type'],
                    $step['delay_days'] ?? 0,
                    $step['delay_hours'] ?? 0,
                    $step['email_template_id'] ?? null,
                    json_encode($step['conditions'] ?? [])
                ]);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'workflow_id' => $workflowId,
                'message' => 'Email workflow created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Create email workflow error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function triggerWorkflow($triggerEvent, $userId, $eventData = []) {
        try {
            // Get all active workflows for this trigger event
            $stmt = $this->conn->prepare("
                SELECT * FROM email_workflows 
                WHERE trigger_event = ? AND is_active = 1
            ");
            $stmt->execute([$triggerEvent]);
            $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($workflows as $workflow) {
                // Check if user is already in this workflow
                $stmt = $this->conn->prepare("
                    SELECT id FROM workflow_subscribers 
                    WHERE workflow_id = ? AND user_id = ? AND status = 'active'
                ");
                $stmt->execute([$workflow['id'], $userId]);
                
                if (!$stmt->fetch()) {
                    // Add user to workflow
                    $this->addUserToWorkflow($workflow['id'], $userId, $eventData);
                }
            }
            
            return [
                'success' => true,
                'triggered_workflows' => count($workflows)
            ];
            
        } catch (Exception $e) {
            error_log("Trigger workflow error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function addUserToWorkflow($workflowId, $userId, $eventData) {
        $stmt = $this->conn->prepare("
            INSERT INTO workflow_subscribers (
                workflow_id, user_id, status, event_data, created_at
            ) VALUES (?, ?, 'active', ?, NOW())
        ");
        
        $stmt->execute([$workflowId, $userId, json_encode($eventData)]);
        
        // Schedule first step
        $this->scheduleNextWorkflowStep($workflowId, $userId, 1);
    }
    
    private function scheduleNextWorkflowStep($workflowId, $userId, $stepOrder) {
        $stmt = $this->conn->prepare("
            SELECT * FROM workflow_steps 
            WHERE workflow_id = ? AND step_order = ?
        ");
        $stmt->execute([$workflowId, $stepOrder]);
        $step = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($step) {
            $executeAt = date('Y-m-d H:i:s', strtotime("+{$step['delay_days']} days +{$step['delay_hours']} hours"));
            
            $stmt = $this->conn->prepare("
                INSERT INTO workflow_schedule (
                    workflow_id, user_id, step_id, execute_at, status, created_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([$workflowId, $userId, $step['id'], $executeAt]);
        }
    }
    
    // A/B Testing
    public function createABTest($testData) {
        try {
            $this->conn->beginTransaction();
            
            // Create A/B test
            $stmt = $this->conn->prepare("
                INSERT INTO ab_tests (
                    name, description, test_type, start_date, end_date,
                    traffic_split, success_metric, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', NOW())
            ");
            
            $stmt->execute([
                $testData['name'],
                $testData['description'],
                $testData['test_type'],
                $testData['start_date'],
                $testData['end_date'],
                $testData['traffic_split'],
                $testData['success_metric']
            ]);
            
            $testId = $this->conn->lastInsertId();
            
            // Create test variants
            foreach ($testData['variants'] as $variant) {
                $stmt = $this->conn->prepare("
                    INSERT INTO ab_test_variants (
                        test_id, name, description, configuration, traffic_percentage,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $testId,
                    $variant['name'],
                    $variant['description'],
                    json_encode($variant['configuration']),
                    $variant['traffic_percentage']
                ]);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'test_id' => $testId,
                'message' => 'A/B test created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Create A/B test error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function assignUserToABTest($testId, $userId) {
        try {
            // Check if user is already assigned
            $stmt = $this->conn->prepare("
                SELECT variant_id FROM ab_test_assignments 
                WHERE test_id = ? AND user_id = ?
            ");
            $stmt->execute([$testId, $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                return $existing['variant_id'];
            }
            
            // Get test variants
            $stmt = $this->conn->prepare("
                SELECT * FROM ab_test_variants 
                WHERE test_id = ? 
                ORDER BY traffic_percentage DESC
            ");
            $stmt->execute([$testId]);
            $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Randomly assign user to variant based on traffic percentage
            $random = mt_rand(1, 100);
            $cumulative = 0;
            $assignedVariant = null;
            
            foreach ($variants as $variant) {
                $cumulative += $variant['traffic_percentage'];
                if ($random <= $cumulative) {
                    $assignedVariant = $variant;
                    break;
                }
            }
            
            if ($assignedVariant) {
                // Record assignment
                $stmt = $this->conn->prepare("
                    INSERT INTO ab_test_assignments (
                        test_id, user_id, variant_id, assigned_at
                    ) VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$testId, $userId, $assignedVariant['id']]);
                
                return $assignedVariant['id'];
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Assign user to A/B test error: " . $e->getMessage());
            return null;
        }
    }
    
    public function recordABTestConversion($testId, $userId, $conversionValue = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ab_test_conversions (
                    test_id, user_id, conversion_value, created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$testId, $userId, $conversionValue]);
            
            return [
                'success' => true,
                'message' => 'Conversion recorded'
            ];
            
        } catch (Exception $e) {
            error_log("Record A/B test conversion error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Customer Loyalty Programs
    public function createLoyaltyProgram($programData) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO loyalty_programs (
                    name, description, program_type, points_per_dollar,
                    redemption_rate, minimum_points, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $programData['name'],
                $programData['description'],
                $programData['program_type'],
                $programData['points_per_dollar'],
                $programData['redemption_rate'],
                $programData['minimum_points']
            ]);
            
            $programId = $this->conn->lastInsertId();
            
            // Create program tiers
            if (isset($programData['tiers'])) {
                foreach ($programData['tiers'] as $tier) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO loyalty_tiers (
                            program_id, name, minimum_points, points_multiplier,
                            benefits, created_at
                        ) VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $programId,
                        $tier['name'],
                        $tier['minimum_points'],
                        $tier['points_multiplier'],
                        json_encode($tier['benefits'])
                    ]);
                }
            }
            
            // Create earning rules
            if (isset($programData['earning_rules'])) {
                foreach ($programData['earning_rules'] as $rule) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO loyalty_earning_rules (
                            program_id, action_type, points_value, conditions,
                            is_active, created_at
                        ) VALUES (?, ?, ?, ?, 1, NOW())
                    ");
                    
                    $stmt->execute([
                        $programId,
                        $rule['action_type'],
                        $rule['points_value'],
                        json_encode($rule['conditions'] ?? [])
                    ]);
                }
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'program_id' => $programId,
                'message' => 'Loyalty program created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Create loyalty program error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function awardLoyaltyPoints($userId, $actionType, $orderValue = null, $metadata = []) {
        try {
            // Get active loyalty programs
            $stmt = $this->conn->prepare("
                SELECT lp.*, ler.points_value, ler.conditions
                FROM loyalty_programs lp
                JOIN loyalty_earning_rules ler ON lp.id = ler.program_id
                WHERE lp.is_active = 1 AND ler.is_active = 1 AND ler.action_type = ?
            ");
            $stmt->execute([$actionType]);
            $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPointsAwarded = 0;
            
            foreach ($programs as $program) {
                $points = 0;
                $conditions = json_decode($program['conditions'], true) ?? [];
                
                // Check if conditions are met
                if ($this->checkLoyaltyConditions($conditions, $userId, $orderValue, $metadata)) {
                    switch ($actionType) {
                        case 'purchase':
                            $points = floor($orderValue * $program['points_per_dollar']);
                            break;
                        case 'signup':
                        case 'review':
                        case 'referral':
                        case 'social_share':
                            $points = $program['points_value'];
                            break;
                    }
                    
                    if ($points > 0) {
                        // Check user's tier for multiplier
                        $userTier = $this->getUserLoyaltyTier($userId, $program['id']);
                        if ($userTier) {
                            $points = $points * $userTier['points_multiplier'];
                        }
                        
                        // Award points
                        $stmt = $this->conn->prepare("
                            INSERT INTO loyalty_points (
                                user_id, program_id, points, action_type, order_id,
                                description, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ");
                        
                        $stmt->execute([
                            $userId,
                            $program['id'],
                            $points,
                            $actionType,
                            $metadata['order_id'] ?? null,
                            "Points earned for {$actionType}"
                        ]);
                        
                        $totalPointsAwarded += $points;
                        
                        // Update user's total points
                        $this->updateUserLoyaltyBalance($userId, $program['id']);
                    }
                }
            }
            
            return [
                'success' => true,
                'points_awarded' => $totalPointsAwarded,
                'message' => "Awarded {$totalPointsAwarded} loyalty points"
            ];
            
        } catch (Exception $e) {
            error_log("Award loyalty points error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function checkLoyaltyConditions($conditions, $userId, $orderValue, $metadata) {
        foreach ($conditions as $condition => $value) {
            switch ($condition) {
                case 'minimum_order_value':
                    if ($orderValue < $value) return false;
                    break;
                case 'product_category':
                    if (!in_array($metadata['category'] ?? '', $value)) return false;
                    break;
                case 'user_tier':
                    $userTier = $this->getUserLoyaltyTier($userId, $metadata['program_id'] ?? 0);
                    if (!$userTier || !in_array($userTier['name'], $value)) return false;
                    break;
            }
        }
        return true;
    }
    
    private function getUserLoyaltyTier($userId, $programId) {
        $stmt = $this->conn->prepare("
            SELECT lt.* FROM loyalty_tiers lt
            JOIN (
                SELECT SUM(points) as total_points
                FROM loyalty_points
                WHERE user_id = ? AND program_id = ?
            ) user_points
            WHERE lt.program_id = ? AND lt.minimum_points <= user_points.total_points
            ORDER BY lt.minimum_points DESC
            LIMIT 1
        ");
        $stmt->execute([$userId, $programId, $programId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function updateUserLoyaltyBalance($userId, $programId) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_loyalty_balances (user_id, program_id, total_points, available_points, updated_at)
            VALUES (?, ?, 
                (SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE user_id = ? AND program_id = ?),
                (SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE user_id = ? AND program_id = ? AND is_redeemed = 0),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
            total_points = (SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE user_id = ? AND program_id = ?),
            available_points = (SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE user_id = ? AND program_id = ? AND is_redeemed = 0),
            updated_at = NOW()
        ");
        $stmt->execute([$userId, $programId, $userId, $programId, $userId, $programId, $userId, $programId, $userId, $programId]);
    }
    
    // Referral System
    public function createReferralProgram($programData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO referral_programs (
                    name, description, referrer_reward_type, referrer_reward_value,
                    referee_reward_type, referee_reward_value, minimum_purchase,
                    expiry_days, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $programData['name'],
                $programData['description'],
                $programData['referrer_reward_type'],
                $programData['referrer_reward_value'],
                $programData['referee_reward_type'],
                $programData['referee_reward_value'],
                $programData['minimum_purchase'] ?? 0,
                $programData['expiry_days'] ?? 365
            ]);
            
            return [
                'success' => true,
                'program_id' => $this->conn->lastInsertId(),
                'message' => 'Referral program created successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Create referral program error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function generateReferralCode($userId, $programId) {
        try {
            // Check if user already has a referral code for this program
            $stmt = $this->conn->prepare("
                SELECT referral_code FROM user_referrals 
                WHERE user_id = ? AND program_id = ?
            ");
            $stmt->execute([$userId, $programId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                return [
                    'success' => true,
                    'referral_code' => $existing['referral_code'],
                    'message' => 'Existing referral code retrieved'
                ];
            }
            
            // Generate unique referral code
            $referralCode = $this->generateUniqueReferralCode();
            
            $stmt = $this->conn->prepare("
                INSERT INTO user_referrals (
                    user_id, program_id, referral_code, created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$userId, $programId, $referralCode]);
            
            return [
                'success' => true,
                'referral_code' => $referralCode,
                'message' => 'Referral code generated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Generate referral code error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function generateUniqueReferralCode() {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
            $stmt = $this->conn->prepare("SELECT id FROM user_referrals WHERE referral_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        
        return $code;
    }
    
    public function processReferral($referralCode, $newUserId, $orderValue = null) {
        try {
            // Get referral information
            $stmt = $this->conn->prepare("
                SELECT ur.*, rp.* FROM user_referrals ur
                JOIN referral_programs rp ON ur.program_id = rp.id
                WHERE ur.referral_code = ? AND rp.is_active = 1
            ");
            $stmt->execute([$referralCode]);
            $referral = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$referral) {
                return [
                    'success' => false,
                    'message' => 'Invalid referral code'
                ];
            }
            
            // Check if minimum purchase requirement is met
            if ($orderValue && $orderValue < $referral['minimum_purchase']) {
                return [
                    'success' => false,
                    'message' => 'Minimum purchase requirement not met'
                ];
            }
            
            $this->conn->beginTransaction();
            
            // Record referral
            $stmt = $this->conn->prepare("
                INSERT INTO referral_conversions (
                    referrer_id, referee_id, program_id, order_value, status, created_at
                ) VALUES (?, ?, ?, ?, 'completed', NOW())
            ");
            
            $stmt->execute([
                $referral['user_id'],
                $newUserId,
                $referral['program_id'],
                $orderValue
            ]);
            
            // Award rewards
            $this->awardReferralRewards($referral, $newUserId, $orderValue);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Referral processed successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Process referral error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function awardReferralRewards($referral, $newUserId, $orderValue) {
        // Award referrer reward
        $this->awardReferralReward(
            $referral['user_id'],
            $referral['referrer_reward_type'],
            $referral['referrer_reward_value'],
            'referrer',
            $referral['program_id']
        );
        
        // Award referee reward
        $this->awardReferralReward(
            $newUserId,
            $referral['referee_reward_type'],
            $referral['referee_reward_value'],
            'referee',
            $referral['program_id']
        );
    }
    
    private function awardReferralReward($userId, $rewardType, $rewardValue, $role, $programId) {
        switch ($rewardType) {
            case 'discount':
                $this->createDiscountCoupon($userId, $rewardValue, $programId);
                break;
            case 'points':
                $this->awardLoyaltyPoints($userId, 'referral', null, ['program_id' => $programId]);
                break;
            case 'credit':
                $this->addStoreCredit($userId, $rewardValue);
                break;
        }
    }
    
    private function createDiscountCoupon($userId, $discountValue, $programId) {
        $couponCode = 'REF' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        $stmt = $this->conn->prepare("
            INSERT INTO coupons (
                code, type, value, minimum_amount, usage_limit, user_id,
                expires_at, is_active, created_at
            ) VALUES (?, 'percentage', ?, 0, 1, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), 1, NOW())
        ");
        
        $stmt->execute([$couponCode, $discountValue, $userId]);
    }
    
    private function addStoreCredit($userId, $creditAmount) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_store_credits (
                user_id, amount, type, description, expires_at, created_at
            ) VALUES (?, ?, 'referral', 'Referral reward', DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW())
        ");
        
        $stmt->execute([$userId, $creditAmount]);
    }
    
    // Analytics and Reporting
    public function getMarketingAnalytics($startDate, $endDate) {
        try {
            $analytics = [];
            
            // Campaign performance
            $analytics['campaigns'] = $this->getCampaignAnalytics($startDate, $endDate);
            
            // Email marketing metrics
            $analytics['email'] = $this->getEmailMarketingAnalytics($startDate, $endDate);
            
            // A/B test results
            $analytics['ab_tests'] = $this->getABTestAnalytics($startDate, $endDate);
            
            // Loyalty program metrics
            $analytics['loyalty'] = $this->getLoyaltyAnalytics($startDate, $endDate);
            
            // Referral program metrics
            $analytics['referrals'] = $this->getReferralAnalytics($startDate, $endDate);
            
            return $analytics;
            
        } catch (Exception $e) {
            error_log("Get marketing analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getCampaignAnalytics($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT 
                mc.type,
                COUNT(*) as total_campaigns,
                SUM(mc.budget) as total_budget,
                AVG(cs.open_rate) as avg_open_rate,
                AVG(cs.click_rate) as avg_click_rate,
                AVG(cs.conversion_rate) as avg_conversion_rate
            FROM marketing_campaigns mc
            LEFT JOIN campaign_statistics cs ON mc.id = cs.campaign_id
            WHERE mc.created_at BETWEEN ? AND ?
            GROUP BY mc.type
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getEmailMarketingAnalytics($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as emails_sent,
                AVG(open_rate) as avg_open_rate,
                AVG(click_rate) as avg_click_rate,
                AVG(bounce_rate) as avg_bounce_rate,
                SUM(revenue_generated) as total_revenue
            FROM email_campaign_stats
            WHERE sent_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getABTestAnalytics($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_tests,
                AVG(atv.conversion_rate) as avg_conversion_rate,
                SUM(atv.conversions) as total_conversions
            FROM ab_tests at
            LEFT JOIN ab_test_variants atv ON at.id = atv.test_id
            WHERE at.created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getLoyaltyAnalytics($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT user_id) as active_members,
                SUM(points) as total_points_awarded,
                AVG(points) as avg_points_per_transaction
            FROM loyalty_points
            WHERE created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getReferralAnalytics($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_referrals,
                COUNT(DISTINCT referrer_id) as active_referrers,
                AVG(order_value) as avg_referral_value,
                SUM(order_value) as total_referral_revenue
            FROM referral_conversions
            WHERE created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Email Service Class (placeholder)
class EmailService {
    public function sendEmail($to, $subject, $body, $template = null) {
        // Implementation for sending emails
        return true;
    }
}

// SMS Service Class (placeholder)
class SMSService {
    public function sendSMS($to, $message) {
        // Implementation for sending SMS
        return true;
    }
}
?>
