<?php
// Customer Support Integration - Live Chat & Helpdesk
class CustomerSupport {
    private $conn;
    private $smsIntegration;
    
    public function __construct($database, $smsIntegration = null) {
        $this->conn = $database;
        $this->smsIntegration = $smsIntegration;
    }
    
    public function createTicket($customerData, $subject, $message, $priority = 'medium', $category = 'general') {
        try {
            $ticketNumber = $this->generateTicketNumber();
            
            $stmt = $this->conn->prepare("
                INSERT INTO support_tickets (
                    ticket_number, customer_id, customer_name, customer_email, 
                    customer_phone, subject, message, priority, category, 
                    status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            
            $stmt->execute([
                $ticketNumber,
                $customerData['customer_id'] ?? null,
                $customerData['name'],
                $customerData['email'],
                $customerData['phone'] ?? null,
                $subject,
                $message,
                $priority,
                $category
            ]);
            
            $ticketId = $this->conn->lastInsertId();
            
            // Send confirmation email
            $this->sendTicketConfirmation($ticketNumber, $customerData['email'], $subject);
            
            // Create first message in the ticket
            $this->addTicketMessage($ticketId, $message, 'customer', $customerData['customer_id']);
            
            // Notify support team
            $this->notifySupportTeam($ticketId, $ticketNumber, $subject, $priority);
            
            return [
                'success' => true,
                'ticket_id' => $ticketId,
                'ticket_number' => $ticketNumber
            ];
            
        } catch (Exception $e) {
            error_log("Create ticket error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function addTicketMessage($ticketId, $message, $senderType, $senderId, $attachments = []) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ticket_messages (
                    ticket_id, message, sender_type, sender_id, 
                    attachments, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $ticketId,
                $message,
                $senderType,
                $senderId,
                json_encode($attachments)
            ]);
            
            $messageId = $this->conn->lastInsertId();
            
            // Update ticket last activity
            $this->updateTicketActivity($ticketId);
            
            // Send notification to relevant parties
            $this->notifyTicketUpdate($ticketId, $messageId, $senderType);
            
            return [
                'success' => true,
                'message_id' => $messageId
            ];
            
        } catch (Exception $e) {
            error_log("Add ticket message error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getTicket($ticketId, $includeMessages = true) {
        try {
            $stmt = $this->conn->prepare("
                SELECT st.*, u.first_name, u.last_name
                FROM support_tickets st
                LEFT JOIN users u ON st.customer_id = u.id
                WHERE st.id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return null;
            }
            
            if ($includeMessages) {
                $ticket['messages'] = $this->getTicketMessages($ticketId);
            }
            
            return $ticket;
        } catch (Exception $e) {
            error_log("Get ticket error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getTicketMessages($ticketId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    tm.*,
                    CASE 
                        WHEN tm.sender_type = 'customer' THEN u.first_name
                        WHEN tm.sender_type = 'admin' THEN a.first_name
                        ELSE 'System'
                    END as sender_name
                FROM ticket_messages tm
                LEFT JOIN users u ON tm.sender_id = u.id AND tm.sender_type = 'customer'
                LEFT JOIN users a ON tm.sender_id = a.id AND tm.sender_type = 'admin'
                WHERE tm.ticket_id = ?
                ORDER BY tm.created_at ASC
            ");
            $stmt->execute([$ticketId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get ticket messages error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateTicketStatus($ticketId, $status, $adminId, $reason = '') {
        try {
            $stmt = $this->conn->prepare("
                UPDATE support_tickets 
                SET status = ?, assigned_to = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $adminId, $ticketId]);
            
            // Log status change
            $this->logTicketStatusChange($ticketId, $status, $adminId, $reason);
            
            // Notify customer of status change
            $this->notifyCustomerStatusChange($ticketId, $status);
            
            return true;
        } catch (Exception $e) {
            error_log("Update ticket status error: " . $e->getMessage());
            return false;
        }
    }
    
    public function assignTicket($ticketId, $adminId, $assignedBy) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE support_tickets 
                SET assigned_to = ?, status = 'assigned', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $ticketId]);
            
            // Log assignment
            $this->logTicketAssignment($ticketId, $adminId, $assignedBy);
            
            // Notify assigned admin
            $this->notifyTicketAssignment($ticketId, $adminId);
            
            return true;
        } catch (Exception $e) {
            error_log("Assign ticket error: " . $e->getMessage());
            return false;
        }
    }
    
    public function searchTickets($filters = [], $page = 1, $limit = 20) {
        try {
            $conditions = [];
            $params = [];
            
            if (!empty($filters['status'])) {
                $conditions[] = "st.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['priority'])) {
                $conditions[] = "st.priority = ?";
                $params[] = $filters['priority'];
            }
            
            if (!empty($filters['category'])) {
                $conditions[] = "st.category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['assigned_to'])) {
                $conditions[] = "st.assigned_to = ?";
                $params[] = $filters['assigned_to'];
            }
            
            if (!empty($filters['customer_id'])) {
                $conditions[] = "st.customer_id = ?";
                $params[] = $filters['customer_id'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(st.subject LIKE ? OR st.customer_name LIKE ? OR st.customer_email LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM support_tickets st $whereClause";
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Get tickets
            $offset = ($page - 1) * $limit;
            $sql = "
                SELECT 
                    st.*,
                    u.first_name as customer_first_name,
                    u.last_name as customer_last_name,
                    a.first_name as admin_first_name,
                    a.last_name as admin_last_name
                FROM support_tickets st
                LEFT JOIN users u ON st.customer_id = u.id
                LEFT JOIN users a ON st.assigned_to = a.id
                $whereClause
                ORDER BY st.priority DESC, st.created_at DESC
                LIMIT $offset, $limit
            ";
            
            // Remove limit and offset from params
            // $params[] = $limit;
            // $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'tickets' => $tickets,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Search tickets error: " . $e->getMessage());
            return [
                'tickets' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }
    
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Open tickets
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM support_tickets WHERE status = 'open'");
            $stmt->execute();
            $stats['open_tickets'] = $stmt->fetch()['count'];
            
            // Assigned tickets
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM support_tickets WHERE status = 'assigned'");
            $stmt->execute();
            $stats['assigned_tickets'] = $stmt->fetch()['count'];
            
            // Pending tickets
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM support_tickets WHERE status = 'pending'");
            $stmt->execute();
            $stats['pending_tickets'] = $stmt->fetch()['count'];
            
            // Resolved tickets (last 30 days)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM support_tickets 
                WHERE status = 'resolved' 
                AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['resolved_tickets'] = $stmt->fetch()['count'];
            
            // Average response time (in hours)
            $stmt = $this->conn->prepare("
                SELECT 
                    AVG(TIMESTAMPDIFF(HOUR, st.created_at, tm.created_at)) as avg_response_time
                FROM support_tickets st
                JOIN ticket_messages tm ON st.id = tm.ticket_id
                WHERE tm.sender_type = 'admin'
                AND tm.id = (
                    SELECT MIN(id) FROM ticket_messages 
                    WHERE ticket_id = st.id AND sender_type = 'admin'
                )
                AND st.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['avg_response_time'] = round($stmt->fetch()['avg_response_time'] ?? 0, 1);
            
            // Tickets by priority
            $stmt = $this->conn->prepare("
                SELECT priority, COUNT(*) as count
                FROM support_tickets 
                WHERE status NOT IN ('resolved', 'closed')
                GROUP BY priority
            ");
            $stmt->execute();
            $priorityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats['by_priority'] = [];
            foreach ($priorityData as $row) {
                $stats['by_priority'][$row['priority']] = $row['count'];
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Support dashboard stats error: " . $e->getMessage());
            return [];
        }
    }
    
    // Live Chat functionality
    public function startChatSession($customerId, $customerName, $customerEmail) {
        try {
            $sessionId = uniqid('chat_', true);
            
            $stmt = $this->conn->prepare("
                INSERT INTO chat_sessions (
                    session_id, customer_id, customer_name, customer_email,
                    status, created_at
                ) VALUES (?, ?, ?, ?, 'waiting', NOW())
            ");
            
            $stmt->execute([$sessionId, $customerId, $customerName, $customerEmail]);
            
            $chatId = $this->conn->lastInsertId();
            
            // Notify available agents
            $this->notifyAvailableAgents($chatId, $sessionId);
            
            return [
                'success' => true,
                'chat_id' => $chatId,
                'session_id' => $sessionId
            ];
        } catch (Exception $e) {
            error_log("Start chat session error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function sendChatMessage($sessionId, $message, $senderType, $senderId) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO chat_messages (
                    session_id, message, sender_type, sender_id, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$sessionId, $message, $senderType, $senderId]);
            
            $messageId = $this->conn->lastInsertId();
            
            // Update session activity
            $this->updateChatActivity($sessionId);
            
            return [
                'success' => true,
                'message_id' => $messageId,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Send chat message error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getChatMessages($sessionId, $lastMessageId = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    cm.*,
                    CASE 
                        WHEN cm.sender_type = 'customer' THEN u.first_name
                        WHEN cm.sender_type = 'agent' THEN a.first_name
                        ELSE 'System'
                    END as sender_name
                FROM chat_messages cm
                LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'customer'
                LEFT JOIN users a ON cm.sender_id = a.id AND cm.sender_type = 'agent'
                WHERE cm.session_id = ? AND cm.id > ?
                ORDER BY cm.created_at ASC
            ");
            $stmt->execute([$sessionId, $lastMessageId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get chat messages error: " . $e->getMessage());
            return [];
        }
    }
    
    private function generateTicketNumber() {
        return 'TKT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    private function updateTicketActivity($ticketId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE support_tickets 
                SET last_activity = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$ticketId]);
        } catch (Exception $e) {
            error_log("Update ticket activity error: " . $e->getMessage());
        }
    }
    
    private function updateChatActivity($sessionId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE chat_sessions 
                SET last_activity = NOW() 
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
        } catch (Exception $e) {
            error_log("Update chat activity error: " . $e->getMessage());
        }
    }
    
    private function sendTicketConfirmation($ticketNumber, $email, $subject) {
        $emailSubject = "Ticket Created: {$ticketNumber}";
        $emailBody = "
            <h3>Support Ticket Created</h3>
            <p>Your support ticket has been created successfully.</p>
            <p><strong>Ticket Number:</strong> {$ticketNumber}</p>
            <p><strong>Subject:</strong> {$subject}</p>
            <p>We will respond to your inquiry as soon as possible.</p>
            <p>You can track your ticket status at: " . SITE_URL . "/support/ticket/{$ticketNumber}</p>
        ";
        
        sendEmail($email, $emailSubject, $emailBody);
    }
    
    private function notifySupportTeam($ticketId, $ticketNumber, $subject, $priority) {
        // Create notification for support team
        $stmt = $this->conn->prepare("
            INSERT INTO admin_notifications (
                type, title, message, severity, data, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $title = "New Support Ticket";
        $message = "New {$priority} priority ticket: {$subject}";
        $severity = $priority === 'high' ? 'high' : 'medium';
        $data = json_encode(['ticket_id' => $ticketId, 'ticket_number' => $ticketNumber]);
        
        $stmt->execute(['support_ticket', $title, $message, $severity, $data]);
    }
    
    private function notifyTicketUpdate($ticketId, $messageId, $senderType) {
        // Implementation for real-time notifications
        // This could integrate with WebSockets, Push notifications, etc.
    }
    
    private function notifyCustomerStatusChange($ticketId, $status) {
        // Get ticket and customer info
        $ticket = $this->getTicket($ticketId, false);
        
        if ($ticket && $ticket['customer_email']) {
            $subject = "Ticket Update: {$ticket['ticket_number']}";
            $message = "Your support ticket status has been updated to: " . ucfirst($status);
            
            sendEmail($ticket['customer_email'], $subject, $message);
        }
    }
    
    private function logTicketStatusChange($ticketId, $status, $adminId, $reason) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ticket_activity_logs (
                    ticket_id, activity_type, description, user_id, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $description = "Status changed to: {$status}" . ($reason ? " - {$reason}" : "");
            $stmt->execute([$ticketId, 'status_change', $description, $adminId]);
        } catch (Exception $e) {
            error_log("Log ticket status change error: " . $e->getMessage());
        }
    }
    
    private function logTicketAssignment($ticketId, $adminId, $assignedBy) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ticket_activity_logs (
                    ticket_id, activity_type, description, user_id, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $description = "Ticket assigned to admin ID: {$adminId}";
            $stmt->execute([$ticketId, 'assignment', $description, $assignedBy]);
        } catch (Exception $e) {
            error_log("Log ticket assignment error: " . $e->getMessage());
        }
    }
    
    private function notifyTicketAssignment($ticketId, $adminId) {
        // Notify the assigned admin
        $stmt = $this->conn->prepare("
            INSERT INTO admin_notifications (
                type, title, message, severity, data, user_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ticket = $this->getTicket($ticketId, false);
        $title = "Ticket Assigned";
        $message = "Ticket {$ticket['ticket_number']} has been assigned to you";
        $data = json_encode(['ticket_id' => $ticketId]);
        
        $stmt->execute(['ticket_assignment', $title, $message, 'medium', $data, $adminId]);
    }
    
    private function notifyAvailableAgents($chatId, $sessionId) {
        // Notify available chat agents
        $stmt = $this->conn->prepare("
            INSERT INTO admin_notifications (
                type, title, message, severity, data, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $title = "New Chat Request";
        $message = "A customer has requested live chat support";
        $data = json_encode(['chat_id' => $chatId, 'session_id' => $sessionId]);
        
        $stmt->execute(['chat_request', $title, $message, 'medium', $data]);
    }
}
?>
