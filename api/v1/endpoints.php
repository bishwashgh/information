<?php
// API Endpoints for Advanced Integrations
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

include_once '../includes/config.php';
include_once '../includes/api_gateway.php';
include_once '../includes/social_integration.php';
include_once '../includes/sms_integration.php';
include_once '../includes/shipping_integration.php';
include_once '../includes/inventory_management.php';
include_once '../includes/customer_support.php';
include_once '../includes/business_intelligence.php';

$apiGateway = new APIGateway($pdo);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($requestUri, '/'));

// Extract API version and endpoint
$apiVersion = $uriSegments[2] ?? 'v1'; // Default to v1
$endpoint = $uriSegments[3] ?? '';
$action = $uriSegments[4] ?? '';
$id = $uriSegments[5] ?? '';

// Get API key from headers
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
$secretKey = $_SERVER['HTTP_X_SECRET_KEY'] ?? $_POST['secret_key'] ?? '';

// Authenticate request
if (!$apiKey) {
    echo $apiGateway->generateAPIResponse(false, null, 'API key required');
    http_response_code(401);
    exit;
}

$authResult = $apiGateway->authenticate($apiKey, $secretKey);
if (!$authResult['success']) {
    echo $apiGateway->generateAPIResponse(false, null, $authResult['error']);
    http_response_code(401);
    exit;
}

$userData = $authResult['user_data'];

// Parse request body for POST/PUT requests
$requestBody = [];
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $requestBody = json_decode(file_get_contents('php://input'), true) ?? [];
}

// Route requests based on endpoint
try {
    switch ($endpoint) {
        case 'products':
            handleProductsAPI($method, $action, $id, $requestBody, $apiGateway);
            break;
            
        case 'orders':
            handleOrdersAPI($method, $action, $id, $requestBody, $apiGateway, $userData);
            break;
            
        case 'social':
            handleSocialAPI($method, $action, $id, $requestBody, $apiGateway, $pdo);
            break;
            
        case 'sms':
            handleSMSAPI($method, $action, $id, $requestBody, $apiGateway, $pdo);
            break;
            
        case 'shipping':
            handleShippingAPI($method, $action, $id, $requestBody, $apiGateway, $pdo);
            break;
            
        case 'inventory':
            handleInventoryAPI($method, $action, $id, $requestBody, $apiGateway, $pdo);
            break;
            
        case 'support':
            handleSupportAPI($method, $action, $id, $requestBody, $apiGateway, $pdo);
            break;
            
        case 'analytics':
            handleAnalyticsAPI($method, $action, $id, $requestBody, $apiGateway, $pdo);
            break;
            
        case 'webhooks':
            handleWebhooksAPI($method, $action, $id, $requestBody, $apiGateway);
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid endpoint');
            http_response_code(404);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo $apiGateway->generateAPIResponse(false, null, 'Internal server error');
    http_response_code(500);
}

function handleProductsAPI($method, $action, $id, $requestBody, $apiGateway) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $result = $apiGateway->getProduct($id);
            } else {
                $filters = $_GET;
                $result = $apiGateway->getProducts($filters);
            }
            echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null, $result['meta'] ?? null);
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Method not allowed');
            http_response_code(405);
    }
}

function handleOrdersAPI($method, $action, $id, $requestBody, $apiGateway, $userData) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get specific order - implement this method
                echo $apiGateway->generateAPIResponse(false, null, 'Not implemented');
            } else {
                $filters = $_GET;
                $result = $apiGateway->getOrders($userData['user_id'], $filters);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null, $result['meta'] ?? null);
            }
            break;
            
        case 'POST':
            $result = $apiGateway->createOrder($requestBody, $userData['user_id']);
            echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            if ($result['success']) {
                http_response_code(201);
            } else {
                http_response_code(400);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Method not allowed');
            http_response_code(405);
    }
}

function handleSocialAPI($method, $action, $id, $requestBody, $apiGateway, $pdo) {
    $socialAuth = new SocialAuth($pdo);
    
    switch ($action) {
        case 'login':
            if ($method === 'POST') {
                $provider = $requestBody['provider'] ?? '';
                $code = $requestBody['code'] ?? '';
                
                $result = $socialAuth->handleCallback($provider, $code);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'share':
            if ($method === 'POST') {
                $platform = $requestBody['platform'] ?? '';
                $content = $requestBody['content'] ?? '';
                $url = $requestBody['url'] ?? '';
                
                $result = $socialAuth->shareContent($platform, $content, $url);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'stats':
            if ($method === 'GET') {
                $stats = $socialAuth->getStatistics();
                echo $apiGateway->generateAPIResponse(true, $stats);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid social action');
            http_response_code(404);
    }
}

function handleSMSAPI($method, $action, $id, $requestBody, $apiGateway, $pdo) {
    $smsService = new SMSService($pdo);
    
    switch ($action) {
        case 'send':
            if ($method === 'POST') {
                $phone = $requestBody['phone'] ?? '';
                $message = $requestBody['message'] ?? '';
                $type = $requestBody['type'] ?? 'sms'; // sms or whatsapp
                
                if ($type === 'whatsapp') {
                    $result = $smsService->sendWhatsApp($phone, $message);
                } else {
                    $result = $smsService->sendSMS($phone, $message);
                }
                
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'otp':
            if ($method === 'POST') {
                $phone = $requestBody['phone'] ?? '';
                $action_type = $requestBody['action'] ?? 'generate'; // generate or verify
                
                if ($action_type === 'verify') {
                    $otp = $requestBody['otp'] ?? '';
                    $result = $smsService->verifyOTP($phone, $otp);
                } else {
                    $result = $smsService->generateOTP($phone);
                }
                
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'stats':
            if ($method === 'GET') {
                $stats = $smsService->getStatistics();
                echo $apiGateway->generateAPIResponse(true, $stats);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid SMS action');
            http_response_code(404);
    }
}

function handleShippingAPI($method, $action, $id, $requestBody, $apiGateway, $pdo) {
    $shippingService = new ShippingService($pdo);
    
    switch ($action) {
        case 'rates':
            if ($method === 'POST') {
                $from = $requestBody['from'] ?? '';
                $to = $requestBody['to'] ?? '';
                $weight = $requestBody['weight'] ?? 0;
                $dimensions = $requestBody['dimensions'] ?? [];
                
                $result = $shippingService->calculateRates($from, $to, $weight, $dimensions);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'create':
            if ($method === 'POST') {
                $orderData = $requestBody['order'] ?? [];
                $provider = $requestBody['provider'] ?? '';
                
                $result = $shippingService->createShipment($orderData, $provider);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'track':
            if ($method === 'GET') {
                $trackingNumber = $_GET['tracking_number'] ?? $id;
                
                $result = $shippingService->trackShipment($trackingNumber);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'stats':
            if ($method === 'GET') {
                $stats = $shippingService->getStatistics();
                echo $apiGateway->generateAPIResponse(true, $stats);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid shipping action');
            http_response_code(404);
    }
}

function handleInventoryAPI($method, $action, $id, $requestBody, $apiGateway, $pdo) {
    $inventoryManager = new InventoryManager($pdo);
    
    switch ($action) {
        case 'sync':
            if ($method === 'POST') {
                $result = $inventoryManager->syncInventory();
                echo $apiGateway->generateAPIResponse($result, ['synced' => $result]);
            }
            break;
            
        case 'alerts':
            if ($method === 'GET') {
                $alerts = $inventoryManager->getLowStockAlerts();
                echo $apiGateway->generateAPIResponse(true, $alerts);
            }
            break;
            
        case 'reorder':
            if ($method === 'POST') {
                $productId = $requestBody['product_id'] ?? $id;
                $quantity = $requestBody['quantity'] ?? 0;
                
                $result = $inventoryManager->createReorderRequest($productId, $quantity);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'movement':
            if ($method === 'GET') {
                $productId = $_GET['product_id'] ?? $id;
                $movements = $inventoryManager->getStockMovements($productId);
                echo $apiGateway->generateAPIResponse(true, $movements);
            }
            break;
            
        case 'stats':
            if ($method === 'GET') {
                $stats = $inventoryManager->getStatistics();
                echo $apiGateway->generateAPIResponse(true, $stats);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid inventory action');
            http_response_code(404);
    }
}

function handleSupportAPI($method, $action, $id, $requestBody, $apiGateway, $pdo) {
    $supportSystem = new CustomerSupport($pdo);
    
    switch ($action) {
        case 'tickets':
            if ($method === 'GET') {
                $filters = $_GET;
                $tickets = $supportSystem->getTickets($filters);
                echo $apiGateway->generateAPIResponse(true, $tickets);
            } elseif ($method === 'POST') {
                $ticketData = $requestBody;
                $result = $supportSystem->createTicket($ticketData);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'chat':
            if ($method === 'POST') {
                $sessionId = $requestBody['session_id'] ?? '';
                $message = $requestBody['message'] ?? '';
                $userId = $requestBody['user_id'] ?? '';
                
                $result = $supportSystem->sendChatMessage($sessionId, $userId, $message);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        case 'stats':
            if ($method === 'GET') {
                $stats = $supportSystem->getStatistics();
                echo $apiGateway->generateAPIResponse(true, $stats);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid support action');
            http_response_code(404);
    }
}

function handleAnalyticsAPI($method, $action, $id, $requestBody, $apiGateway, $pdo) {
    $businessIntelligence = new BusinessIntelligence($pdo);
    
    switch ($action) {
        case 'sales':
            if ($method === 'GET') {
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                $granularity = $_GET['granularity'] ?? 'daily';
                
                $analytics = $businessIntelligence->getAdvancedSalesAnalytics($startDate, $endDate, $granularity);
                echo $apiGateway->generateAPIResponse(true, $analytics);
            }
            break;
            
        case 'customers':
            if ($method === 'GET') {
                $segmentation = $businessIntelligence->getCustomerSegmentation();
                echo $apiGateway->generateAPIResponse(true, $segmentation);
            }
            break;
            
        case 'products':
            if ($method === 'GET') {
                $days = $_GET['days'] ?? 30;
                $performance = $businessIntelligence->getProductPerformanceAnalysis($days);
                echo $apiGateway->generateAPIResponse(true, $performance);
            }
            break;
            
        case 'revenue':
            if ($method === 'GET') {
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                
                $analysis = $businessIntelligence->getRevenueAnalysis($startDate, $endDate);
                echo $apiGateway->generateAPIResponse(true, $analysis);
            }
            break;
            
        case 'cohort':
            if ($method === 'GET') {
                $months = $_GET['months'] ?? 12;
                $cohort = $businessIntelligence->getCohortAnalysis($months);
                echo $apiGateway->generateAPIResponse(true, $cohort);
            }
            break;
            
        case 'executive':
            if ($method === 'GET') {
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                
                $summary = $businessIntelligence->generateExecutiveSummary($startDate, $endDate);
                echo $apiGateway->generateAPIResponse(true, $summary);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid analytics action');
            http_response_code(404);
    }
}

function handleWebhooksAPI($method, $action, $id, $requestBody, $apiGateway) {
    switch ($action) {
        case 'register':
            if ($method === 'POST') {
                $url = $requestBody['url'] ?? '';
                $events = $requestBody['events'] ?? [];
                $secret = $requestBody['secret'] ?? null;
                
                $result = $apiGateway->registerWebhook($url, $events, $secret);
                echo $apiGateway->generateAPIResponse($result['success'], $result['data'] ?? null, $result['error'] ?? null);
            }
            break;
            
        default:
            echo $apiGateway->generateAPIResponse(false, null, 'Invalid webhook action');
            http_response_code(404);
    }
}
?>
