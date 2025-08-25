<?php
// Milestone 14: Enterprise Features
class EnterpriseManager {
    private $conn;
    private $logger;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->logger = new Logger('enterprise');
    }
    
    // B2B Functionality
    public function createBusinessAccount($businessData) {
        try {
            $this->conn->beginTransaction();
            
            // Validate business data
            $validation = $this->validateBusinessData($businessData);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            // Create business account
            $stmt = $this->conn->prepare("
                INSERT INTO business_accounts (
                    company_name, business_type, tax_id, registration_number,
                    industry, employee_count, annual_revenue, contact_person,
                    email, phone, address, city, state, country, postal_code,
                    credit_limit, payment_terms, discount_tier, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval', NOW())
            ");
            
            $stmt->execute([
                $businessData['company_name'],
                $businessData['business_type'],
                $businessData['tax_id'],
                $businessData['registration_number'],
                $businessData['industry'],
                $businessData['employee_count'],
                $businessData['annual_revenue'],
                $businessData['contact_person'],
                $businessData['email'],
                $businessData['phone'],
                $businessData['address'],
                $businessData['city'],
                $businessData['state'],
                $businessData['country'],
                $businessData['postal_code'],
                $businessData['credit_limit'] ?? 10000,
                $businessData['payment_terms'] ?? 'net_30',
                $this->calculateDiscountTier($businessData['annual_revenue']),
            ]);
            
            $businessId = $this->conn->lastInsertId();
            
            // Create primary contact user
            $userId = $this->createBusinessUser($businessId, $businessData);
            
            // Create default business settings
            $this->createBusinessSettings($businessId);
            
            // Create approval workflow
            $this->initiateBusinessApproval($businessId);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'business_id' => $businessId,
                'user_id' => $userId,
                'message' => 'Business account created successfully. Pending approval.'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Create business account error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function validateBusinessData($data) {
        $required = ['company_name', 'business_type', 'tax_id', 'contact_person', 'email', 'phone', 'address', 'city', 'state', 'country'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'message' => "Field '{$field}' is required"];
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if business already exists
        $stmt = $this->conn->prepare("SELECT id FROM business_accounts WHERE tax_id = ? OR registration_number = ?");
        $stmt->execute([$data['tax_id'], $data['registration_number'] ?? '']);
        if ($stmt->fetch()) {
            return ['valid' => false, 'message' => 'Business already registered'];
        }
        
        return ['valid' => true, 'message' => 'Valid data'];
    }
    
    private function calculateDiscountTier($annualRevenue) {
        if ($annualRevenue >= 10000000) return 'enterprise'; // $10M+
        if ($annualRevenue >= 5000000) return 'premium';     // $5M+
        if ($annualRevenue >= 1000000) return 'standard';    // $1M+
        return 'basic';
    }
    
    private function createBusinessUser($businessId, $businessData) {
        $stmt = $this->conn->prepare("
            INSERT INTO users (
                business_id, name, email, phone, role, permissions,
                status, created_at
            ) VALUES (?, ?, ?, ?, 'business_admin', ?, 'active', NOW())
        ");
        
        $adminPermissions = json_encode([
            'manage_users', 'manage_orders', 'view_reports', 'manage_billing',
            'manage_addresses', 'manage_payment_methods'
        ]);
        
        $stmt->execute([
            $businessId,
            $businessData['contact_person'],
            $businessData['email'],
            $businessData['phone'],
            $adminPermissions
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    private function createBusinessSettings($businessId) {
        $stmt = $this->conn->prepare("
            INSERT INTO business_settings (
                business_id, approval_workflow, purchase_order_required,
                multi_level_approval, spending_limits, auto_reorder,
                bulk_discount_enabled, created_at
            ) VALUES (?, 1, 1, 1, ?, 0, 1, NOW())
        ");
        
        $defaultSpendingLimits = json_encode([
            'employee' => 1000,
            'manager' => 5000,
            'admin' => 0 // unlimited
        ]);
        
        $stmt->execute([$businessId, $defaultSpendingLimits]);
    }
    
    // Enterprise User Management
    public function createEnterpriseUser($businessId, $userData) {
        try {
            // Validate user has permission to create users
            if (!$this->hasPermission($userData['created_by'], 'manage_users')) {
                throw new Exception('Insufficient permissions');
            }
            
            // Validate user data
            $validation = $this->validateEnterpriseUserData($userData);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO users (
                    business_id, name, email, phone, department, role,
                    permissions, spending_limit, manager_id, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            
            $stmt->execute([
                $businessId,
                $userData['name'],
                $userData['email'],
                $userData['phone'],
                $userData['department'],
                $userData['role'],
                json_encode($userData['permissions']),
                $userData['spending_limit'],
                $userData['manager_id']
            ]);
            
            $userId = $this->conn->lastInsertId();
            
            // Send welcome email
            $this->sendEnterpriseWelcomeEmail($userId, $userData['email']);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Enterprise user created successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Create enterprise user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function validateEnterpriseUserData($data) {
        $required = ['name', 'email', 'role', 'permissions'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'message' => "Field '{$field}' is required"];
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['valid' => false, 'message' => 'Email already exists'];
        }
        
        return ['valid' => true, 'message' => 'Valid data'];
    }
    
    public function assignUserRole($userId, $role, $permissions, $assignedBy) {
        try {
            // Validate assigner has permission
            if (!$this->hasPermission($assignedBy, 'manage_users')) {
                throw new Exception('Insufficient permissions');
            }
            
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET role = ?, permissions = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $role,
                json_encode($permissions),
                $userId
            ]);
            
            // Log role change
            $this->logUserActivity($userId, 'role_changed', [
                'new_role' => $role,
                'new_permissions' => $permissions,
                'assigned_by' => $assignedBy
            ]);
            
            return [
                'success' => true,
                'message' => 'User role updated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Assign user role error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Purchase Order System
    public function createPurchaseOrder($poData) {
        try {
            $this->conn->beginTransaction();
            
            // Validate PO data
            $validation = $this->validatePurchaseOrderData($poData);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            // Generate PO number
            $poNumber = $this->generatePONumber($poData['business_id']);
            
            // Create purchase order
            $stmt = $this->conn->prepare("
                INSERT INTO purchase_orders (
                    business_id, po_number, requested_by, approver_id,
                    department, cost_center, total_amount, currency,
                    delivery_date, notes, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval', NOW())
            ");
            
            $stmt->execute([
                $poData['business_id'],
                $poNumber,
                $poData['requested_by'],
                $poData['approver_id'],
                $poData['department'],
                $poData['cost_center'],
                $poData['total_amount'],
                $poData['currency'],
                $poData['delivery_date'],
                $poData['notes']
            ]);
            
            $poId = $this->conn->lastInsertId();
            
            // Add PO items
            foreach ($poData['items'] as $item) {
                $this->addPurchaseOrderItem($poId, $item);
            }
            
            // Initiate approval workflow
            $this->initiateApprovalWorkflow($poId, $poData['total_amount']);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'po_id' => $poId,
                'po_number' => $poNumber,
                'message' => 'Purchase order created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Create purchase order error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function validatePurchaseOrderData($data) {
        $required = ['business_id', 'requested_by', 'items', 'total_amount'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'message' => "Field '{$field}' is required"];
            }
        }
        
        // Validate spending limit
        if (!$this->validateSpendingLimit($data['requested_by'], $data['total_amount'])) {
            return ['valid' => false, 'message' => 'Amount exceeds spending limit'];
        }
        
        return ['valid' => true, 'message' => 'Valid data'];
    }
    
    private function generatePONumber($businessId) {
        $prefix = 'PO' . date('Y') . str_pad($businessId, 3, '0', STR_PAD_LEFT);
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) + 1 as next_number
            FROM purchase_orders
            WHERE business_id = ? AND YEAR(created_at) = YEAR(NOW())
        ");
        $stmt->execute([$businessId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $prefix . str_pad($result['next_number'], 4, '0', STR_PAD_LEFT);
    }
    
    private function addPurchaseOrderItem($poId, $item) {
        $stmt = $this->conn->prepare("
            INSERT INTO purchase_order_items (
                po_id, product_id, quantity, unit_price, total_price,
                specifications, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $poId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['total_price'],
            json_encode($item['specifications'] ?? [])
        ]);
    }
    
    // Approval Workflows
    public function initiateApprovalWorkflow($poId, $amount) {
        try {
            // Get approval rules based on amount
            $approvalLevel = $this->getRequiredApprovalLevel($amount);
            
            $stmt = $this->conn->prepare("
                INSERT INTO approval_workflows (
                    po_id, approval_level, current_step, total_steps,
                    status, created_at
                ) VALUES (?, ?, 1, ?, 'pending', NOW())
            ");
            
            $stmt->execute([$poId, $approvalLevel, $approvalLevel]);
            
            $workflowId = $this->conn->lastInsertId();
            
            // Create approval steps
            $this->createApprovalSteps($workflowId, $poId, $approvalLevel);
            
            // Notify first approver
            $this->notifyNextApprover($workflowId);
            
            return $workflowId;
            
        } catch (Exception $e) {
            error_log("Initiate approval workflow error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getRequiredApprovalLevel($amount) {
        if ($amount > 50000) return 3; // CEO approval required
        if ($amount > 10000) return 2; // Manager + Director approval
        if ($amount > 1000) return 1;  // Manager approval only
        return 0; // Auto-approved
    }
    
    private function createApprovalSteps($workflowId, $poId, $approvalLevel) {
        $approvers = $this->getApprovers($poId, $approvalLevel);
        
        foreach ($approvers as $index => $approver) {
            $stmt = $this->conn->prepare("
                INSERT INTO approval_steps (
                    workflow_id, step_number, approver_id, approver_role,
                    status, created_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $workflowId,
                $index + 1,
                $approver['user_id'],
                $approver['role']
            ]);
        }
    }
    
    private function getApprovers($poId, $approvalLevel) {
        // Get PO details
        $stmt = $this->conn->prepare("
            SELECT po.*, u.manager_id, u.department
            FROM purchase_orders po
            JOIN users u ON po.requested_by = u.id
            WHERE po.id = ?
        ");
        $stmt->execute([$poId]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $approvers = [];
        
        // Level 1: Direct manager
        if ($approvalLevel >= 1 && $po['manager_id']) {
            $approvers[] = ['user_id' => $po['manager_id'], 'role' => 'manager'];
        }
        
        // Level 2: Department director
        if ($approvalLevel >= 2) {
            $director = $this->getDepartmentDirector($po['department']);
            if ($director) {
                $approvers[] = ['user_id' => $director['id'], 'role' => 'director'];
            }
        }
        
        // Level 3: CEO/CFO
        if ($approvalLevel >= 3) {
            $executive = $this->getExecutiveApprover($po['business_id']);
            if ($executive) {
                $approvers[] = ['user_id' => $executive['id'], 'role' => 'executive'];
            }
        }
        
        return $approvers;
    }
    
    public function approvePurchaseOrder($poId, $approverId, $notes = '') {
        try {
            $this->conn->beginTransaction();
            
            // Get current approval step
            $stmt = $this->conn->prepare("
                SELECT aw.*, ast.id as step_id
                FROM approval_workflows aw
                JOIN approval_steps ast ON aw.id = ast.workflow_id
                WHERE aw.po_id = ? AND ast.approver_id = ? AND ast.status = 'pending'
                ORDER BY ast.step_number
                LIMIT 1
            ");
            $stmt->execute([$poId, $approverId]);
            $approval = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$approval) {
                throw new Exception('No pending approval found for this user');
            }
            
            // Update approval step
            $stmt = $this->conn->prepare("
                UPDATE approval_steps
                SET status = 'approved', approved_at = NOW(), notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$notes, $approval['step_id']]);
            
            // Check if all approvals are complete
            $this->checkApprovalCompletion($approval['id'], $poId);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Purchase order approved successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Approve purchase order error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function checkApprovalCompletion($workflowId, $poId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as pending_count
            FROM approval_steps
            WHERE workflow_id = ? AND status = 'pending'
        ");
        $stmt->execute([$workflowId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['pending_count'] == 0) {
            // All approvals complete
            $stmt = $this->conn->prepare("
                UPDATE approval_workflows
                SET status = 'approved', completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$workflowId]);
            
            $stmt = $this->conn->prepare("
                UPDATE purchase_orders
                SET status = 'approved', approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$poId]);
            
            // Convert to order
            $this->convertPOToOrder($poId);
        } else {
            // Notify next approver
            $this->notifyNextApprover($workflowId);
        }
    }
    
    // Multi-Store Management
    public function createStore($storeData) {
        try {
            $this->conn->beginTransaction();
            
            $stmt = $this->conn->prepare("
                INSERT INTO stores (
                    business_id, name, code, description, address, city, state,
                    country, postal_code, phone, email, manager_id, timezone,
                    currency, tax_rate, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $storeData['business_id'],
                $storeData['name'],
                $storeData['code'],
                $storeData['description'],
                $storeData['address'],
                $storeData['city'],
                $storeData['state'],
                $storeData['country'],
                $storeData['postal_code'],
                $storeData['phone'],
                $storeData['email'],
                $storeData['manager_id'],
                $storeData['timezone'],
                $storeData['currency'],
                $storeData['tax_rate']
            ]);
            
            $storeId = $this->conn->lastInsertId();
            
            // Create store settings
            $this->createStoreSettings($storeId);
            
            // Create store inventory
            $this->initializeStoreInventory($storeId);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'store_id' => $storeId,
                'message' => 'Store created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Create store error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function createStoreSettings($storeId) {
        $stmt = $this->conn->prepare("
            INSERT INTO store_settings (
                store_id, pos_enabled, inventory_tracking, auto_reorder,
                loyalty_program, payment_methods, operating_hours, created_at
            ) VALUES (?, 1, 1, 0, 1, ?, ?, NOW())
        ");
        
        $defaultPaymentMethods = json_encode(['cash', 'card', 'digital_wallet']);
        $defaultHours = json_encode([
            'monday' => ['open' => '09:00', 'close' => '18:00'],
            'tuesday' => ['open' => '09:00', 'close' => '18:00'],
            'wednesday' => ['open' => '09:00', 'close' => '18:00'],
            'thursday' => ['open' => '09:00', 'close' => '18:00'],
            'friday' => ['open' => '09:00', 'close' => '18:00'],
            'saturday' => ['open' => '10:00', 'close' => '16:00'],
            'sunday' => ['closed' => true]
        ]);
        
        $stmt->execute([$storeId, $defaultPaymentMethods, $defaultHours]);
    }
    
    private function initializeStoreInventory($storeId) {
        // Copy products from main catalog with zero inventory
        $stmt = $this->conn->prepare("
            INSERT INTO store_inventory (store_id, product_id, quantity, reserved_quantity, reorder_point, reorder_quantity, created_at)
            SELECT ?, id, 0, 0, 10, 50, NOW()
            FROM products
            WHERE status = 'active'
        ");
        $stmt->execute([$storeId]);
    }
    
    // Enterprise Analytics
    public function getEnterpriseAnalytics($businessId, $period = '30_days') {
        try {
            $dateCondition = $this->getDateCondition($period);
            
            $analytics = [
                'overview' => $this->getBusinessOverview($businessId, $dateCondition),
                'spending' => $this->getSpendingAnalytics($businessId, $dateCondition),
                'users' => $this->getUserAnalytics($businessId, $dateCondition),
                'departments' => $this->getDepartmentAnalytics($businessId, $dateCondition),
                'purchase_orders' => $this->getPOAnalytics($businessId, $dateCondition),
                'stores' => $this->getStoreAnalytics($businessId, $dateCondition)
            ];
            
            return $analytics;
            
        } catch (Exception $e) {
            error_log("Get enterprise analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getBusinessOverview($businessId, $dateCondition) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_spent,
                COUNT(DISTINCT u.id) as active_users,
                AVG(o.total_amount) as avg_order_value
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.business_id = ?
            AND o.created_at >= $dateCondition
            AND o.status IN ('completed', 'delivered')
        ");
        $stmt->execute([$businessId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getSpendingAnalytics($businessId, $dateCondition) {
        $stmt = $this->conn->prepare("
            SELECT 
                DATE(o.created_at) as date,
                SUM(o.total_amount) as daily_spending,
                COUNT(o.id) as daily_orders
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE u.business_id = ?
            AND o.created_at >= $dateCondition
            AND o.status IN ('completed', 'delivered')
            GROUP BY DATE(o.created_at)
            ORDER BY date
        ");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserAnalytics($businessId, $dateCondition) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.department,
                COUNT(DISTINCT u.id) as user_count,
                COUNT(DISTINCT o.id) as order_count,
                SUM(o.total_amount) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.created_at >= $dateCondition
            WHERE u.business_id = ?
            GROUP BY u.department
        ");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getDepartmentAnalytics($businessId, $dateCondition) {
        $stmt = $this->conn->prepare("
            SELECT 
                po.department,
                COUNT(*) as po_count,
                SUM(po.total_amount) as total_amount,
                AVG(po.total_amount) as avg_amount
            FROM purchase_orders po
            WHERE po.business_id = ?
            AND po.created_at >= $dateCondition
            GROUP BY po.department
        ");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPOAnalytics($businessId, $dateCondition) {
        $stmt = $this->conn->prepare("
            SELECT 
                po.status,
                COUNT(*) as count,
                SUM(po.total_amount) as total_amount
            FROM purchase_orders po
            WHERE po.business_id = ?
            AND po.created_at >= $dateCondition
            GROUP BY po.status
        ");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getStoreAnalytics($businessId, $dateCondition) {
        $stmt = $this->conn->prepare("
            SELECT 
                s.name,
                COUNT(DISTINCT o.id) as order_count,
                SUM(o.total_amount) as revenue
            FROM stores s
            LEFT JOIN orders o ON s.id = o.store_id AND o.created_at >= $dateCondition
            WHERE s.business_id = ?
            GROUP BY s.id
        ");
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Utility Functions
    private function hasPermission($userId, $permission) {
        $stmt = $this->conn->prepare("SELECT permissions FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return false;
        
        $permissions = json_decode($user['permissions'], true) ?? [];
        return in_array($permission, $permissions);
    }
    
    private function validateSpendingLimit($userId, $amount) {
        $stmt = $this->conn->prepare("SELECT spending_limit FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return false;
        
        // 0 means unlimited
        return $user['spending_limit'] == 0 || $amount <= $user['spending_limit'];
    }
    
    private function getDepartmentDirector($department) {
        $stmt = $this->conn->prepare("
            SELECT id FROM users 
            WHERE department = ? AND role IN ('director', 'head')
            LIMIT 1
        ");
        $stmt->execute([$department]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getExecutiveApprover($businessId) {
        $stmt = $this->conn->prepare("
            SELECT id FROM users 
            WHERE business_id = ? AND role IN ('ceo', 'cfo', 'president')
            LIMIT 1
        ");
        $stmt->execute([$businessId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function notifyNextApprover($workflowId) {
        // Implementation for notifying approvers
        error_log("Notification sent for workflow: {$workflowId}");
    }
    
    private function convertPOToOrder($poId) {
        // Implementation for converting PO to actual order
        error_log("Converting PO {$poId} to order");
    }
    
    private function initiateBusinessApproval($businessId) {
        // Implementation for business approval workflow
        error_log("Business approval initiated for: {$businessId}");
    }
    
    private function sendEnterpriseWelcomeEmail($userId, $email) {
        // Implementation for sending welcome email
        error_log("Welcome email sent to: {$email}");
    }
    
    private function logUserActivity($userId, $action, $data) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_activity_logs (
                user_id, action, data, ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $action,
            json_encode($data),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    private function getDateCondition($period) {
        switch ($period) {
            case '7_days':
                return "DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30_days':
                return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90_days':
                return "DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1_year':
                return "DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }
}

// Logger Class (placeholder)
class Logger {
    private $context;
    
    public function __construct($context) {
        $this->context = $context;
    }
    
    public function log($level, $message, $data = []) {
        error_log("[{$this->context}] {$level}: {$message} " . json_encode($data));
    }
}
?>
