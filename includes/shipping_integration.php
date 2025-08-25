<?php
// Shipping Integration with multiple providers
class ShippingIntegration {
    private $conn;
    private $providers;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->loadProviders();
    }
    
    private function loadProviders() {
        $this->providers = [
            'delhivery' => [
                'api_key' => $_ENV['DELHIVERY_API_KEY'] ?? '',
                'base_url' => 'https://track.delhivery.com/api',
                'enabled' => !empty($_ENV['DELHIVERY_API_KEY'])
            ],
            'bluedart' => [
                'api_key' => $_ENV['BLUEDART_API_KEY'] ?? '',
                'base_url' => 'https://apigateway.bluedart.com',
                'enabled' => !empty($_ENV['BLUEDART_API_KEY'])
            ],
            'dtdc' => [
                'api_key' => $_ENV['DTDC_API_KEY'] ?? '',
                'base_url' => 'https://blktracksvc.dtdc.com',
                'enabled' => !empty($_ENV['DTDC_API_KEY'])
            ],
            'fedex' => [
                'api_key' => $_ENV['FEDEX_API_KEY'] ?? '',
                'secret' => $_ENV['FEDEX_SECRET'] ?? '',
                'base_url' => 'https://apis.fedex.com',
                'enabled' => !empty($_ENV['FEDEX_API_KEY'])
            ]
        ];
    }
    
    public function calculateShippingRates($originPincode, $destinationPincode, $weight, $dimensions) {
        $rates = [];
        
        foreach ($this->providers as $provider => $config) {
            if (!$config['enabled']) continue;
            
            try {
                $rate = $this->getProviderRate($provider, $originPincode, $destinationPincode, $weight, $dimensions);
                if ($rate) {
                    $rates[$provider] = $rate;
                }
            } catch (Exception $e) {
                error_log("Shipping rate error for {$provider}: " . $e->getMessage());
            }
        }
        
        // Sort by price
        uasort($rates, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        return $rates;
    }
    
    private function getProviderRate($provider, $originPincode, $destinationPincode, $weight, $dimensions) {
        switch ($provider) {
            case 'delhivery':
                return $this->getDelhiveryRate($originPincode, $destinationPincode, $weight, $dimensions);
            case 'bluedart':
                return $this->getBluedartRate($originPincode, $destinationPincode, $weight, $dimensions);
            case 'dtdc':
                return $this->getDTDCRate($originPincode, $destinationPincode, $weight, $dimensions);
            case 'fedex':
                return $this->getFedexRate($originPincode, $destinationPincode, $weight, $dimensions);
            default:
                return null;
        }
    }
    
    private function getDelhiveryRate($originPincode, $destinationPincode, $weight, $dimensions) {
        try {
            $config = $this->providers['delhivery'];
            $url = $config['base_url'] . '/cmu/push/json/';
            
            $params = [
                'md' => 'S', // Surface
                'ss' => 'Delivered',
                'o_pin' => $originPincode,
                'd_pin' => $destinationPincode,
                'cgm' => $weight * 1000, // Convert kg to grams
                'pt' => 'Pre-paid',
                'cod' => 0
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Token ' . $config['api_key']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if (isset($data['delivery_codes']) && !empty($data['delivery_codes'])) {
                    $deliveryInfo = reset($data['delivery_codes']);
                    
                    return [
                        'provider' => 'Delhivery',
                        'service_type' => 'Surface',
                        'price' => floatval($deliveryInfo['total_amount']),
                        'estimated_days' => 3,
                        'cod_available' => true,
                        'tracking_available' => true
                    ];
                }
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Delhivery rate error: " . $e->getMessage());
            return null;
        }
    }
    
    private function getBluedartRate($originPincode, $destinationPincode, $weight, $dimensions) {
        // Simplified rate calculation for Bluedart
        // In production, use actual Bluedart API
        
        $baseRate = 50;
        $weightRate = $weight * 25;
        $distanceMultiplier = $this->calculateDistanceMultiplier($originPincode, $destinationPincode);
        
        return [
            'provider' => 'Blue Dart',
            'service_type' => 'Express',
            'price' => $baseRate + $weightRate * $distanceMultiplier,
            'estimated_days' => 2,
            'cod_available' => true,
            'tracking_available' => true
        ];
    }
    
    private function getDTDCRate($originPincode, $destinationPincode, $weight, $dimensions) {
        // Simplified rate calculation for DTDC
        $baseRate = 40;
        $weightRate = $weight * 20;
        $distanceMultiplier = $this->calculateDistanceMultiplier($originPincode, $destinationPincode);
        
        return [
            'provider' => 'DTDC',
            'service_type' => 'Express',
            'price' => $baseRate + $weightRate * $distanceMultiplier,
            'estimated_days' => 3,
            'cod_available' => true,
            'tracking_available' => true
        ];
    }
    
    private function getFedexRate($originPincode, $destinationPincode, $weight, $dimensions) {
        // Simplified rate calculation for FedEx
        $baseRate = 80;
        $weightRate = $weight * 35;
        $distanceMultiplier = $this->calculateDistanceMultiplier($originPincode, $destinationPincode);
        
        return [
            'provider' => 'FedEx',
            'service_type' => 'International Express',
            'price' => $baseRate + $weightRate * $distanceMultiplier,
            'estimated_days' => 1,
            'cod_available' => false,
            'tracking_available' => true
        ];
    }
    
    private function calculateDistanceMultiplier($originPincode, $destinationPincode) {
        // Simplified distance calculation based on pincode
        // In production, use actual distance calculation or zone mapping
        
        $originZone = substr($originPincode, 0, 1);
        $destinationZone = substr($destinationPincode, 0, 1);
        
        if ($originZone === $destinationZone) {
            return 1.0; // Same zone
        } elseif (abs($originZone - $destinationZone) <= 2) {
            return 1.5; // Adjacent zones
        } else {
            return 2.0; // Far zones
        }
    }
    
    public function createShipment($orderId, $provider, $shipmentData) {
        try {
            $method = 'create' . ucfirst($provider) . 'Shipment';
            
            if (method_exists($this, $method)) {
                $result = $this->$method($shipmentData);
                
                if ($result && isset($result['tracking_number'])) {
                    // Store shipment information
                    $this->storeShipmentInfo($orderId, $provider, $result);
                    return $result;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Shipment creation error: " . $e->getMessage());
            return false;
        }
    }
    
    private function createDelhiveryShipment($shipmentData) {
        try {
            $config = $this->providers['delhivery'];
            $url = $config['base_url'] . '/cmu/create.json';
            
            $payload = [
                'shipments' => [
                    [
                        'name' => $shipmentData['receiver_name'],
                        'add' => $shipmentData['receiver_address'],
                        'pin' => $shipmentData['receiver_pincode'],
                        'city' => $shipmentData['receiver_city'],
                        'state' => $shipmentData['receiver_state'],
                        'country' => 'India',
                        'phone' => $shipmentData['receiver_phone'],
                        'order' => $shipmentData['order_number'],
                        'payment_mode' => $shipmentData['payment_mode'],
                        'return_pin' => $shipmentData['sender_pincode'],
                        'return_city' => $shipmentData['sender_city'],
                        'return_name' => $shipmentData['sender_name'],
                        'return_add' => $shipmentData['sender_address'],
                        'return_state' => $shipmentData['sender_state'],
                        'return_country' => 'India',
                        'products_desc' => $shipmentData['product_description'],
                        'hsn_code' => $shipmentData['hsn_code'],
                        'cod_amount' => $shipmentData['cod_amount'],
                        'order_date' => date('Y-m-d H:i:s'),
                        'total_amount' => $shipmentData['total_amount'],
                        'seller_add' => $shipmentData['sender_address'],
                        'seller_name' => $shipmentData['sender_name'],
                        'seller_inv' => $shipmentData['invoice_number'],
                        'quantity' => $shipmentData['quantity'],
                        'waybill' => '',
                        'shipment_width' => $shipmentData['width'],
                        'shipment_height' => $shipmentData['height'],
                        'weight' => $shipmentData['weight'],
                        'seller_gst_tin' => $shipmentData['seller_gst'],
                        'shipping_mode' => 'Surface',
                        'address_type' => 'home'
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'format=json&data=' . json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Token ' . $config['api_key']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if ($data['success'] && !empty($data['packages'])) {
                    $package = reset($data['packages']);
                    
                    return [
                        'tracking_number' => $package['waybill'],
                        'label_url' => $package['label_generated_date'] ?? '',
                        'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
                        'status' => 'shipped'
                    ];
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Delhivery shipment error: " . $e->getMessage());
            return false;
        }
    }
    
    public function trackShipment($trackingNumber, $provider = null) {
        if ($provider) {
            return $this->trackWithProvider($trackingNumber, $provider);
        }
        
        // Try all providers if provider not specified
        foreach ($this->providers as $providerName => $config) {
            if (!$config['enabled']) continue;
            
            $result = $this->trackWithProvider($trackingNumber, $providerName);
            if ($result) {
                return $result;
            }
        }
        
        return false;
    }
    
    private function trackWithProvider($trackingNumber, $provider) {
        switch ($provider) {
            case 'delhivery':
                return $this->trackDelhivery($trackingNumber);
            case 'bluedart':
                return $this->trackBluedart($trackingNumber);
            case 'dtdc':
                return $this->trackDTDC($trackingNumber);
            case 'fedex':
                return $this->trackFedex($trackingNumber);
            default:
                return null;
        }
    }
    
    private function trackDelhivery($trackingNumber) {
        try {
            $config = $this->providers['delhivery'];
            $url = $config['base_url'] . '/v1/packages/json/?waybil=' . $trackingNumber;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Token ' . $config['api_key']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if (!empty($data['ShipmentData'])) {
                    $shipment = reset($data['ShipmentData']);
                    
                    return [
                        'provider' => 'Delhivery',
                        'tracking_number' => $trackingNumber,
                        'status' => $this->mapDelhiveryStatus($shipment['Shipment']['Status']['Status']),
                        'current_location' => $shipment['Shipment']['Origin'] ?? '',
                        'estimated_delivery' => $shipment['Shipment']['ExpectedDeliveryDate'] ?? '',
                        'tracking_history' => $this->formatDelhiveryTracking($shipment['Shipment']['Scans'] ?? []),
                        'last_updated' => date('Y-m-d H:i:s')
                    ];
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Delhivery tracking error: " . $e->getMessage());
            return false;
        }
    }
    
    private function mapDelhiveryStatus($status) {
        $statusMap = [
            'Dispatched' => 'shipped',
            'In Transit' => 'in_transit',
            'Out for Delivery' => 'out_for_delivery',
            'Delivered' => 'delivered',
            'Returned' => 'returned',
            'Cancelled' => 'cancelled'
        ];
        
        return $statusMap[$status] ?? 'unknown';
    }
    
    private function formatDelhiveryTracking($scans) {
        $tracking = [];
        
        foreach ($scans as $scan) {
            $tracking[] = [
                'timestamp' => $scan['ScanDateTime'],
                'location' => $scan['ScannedLocation'],
                'status' => $scan['Instructions'],
                'description' => $scan['Remarks']
            ];
        }
        
        return array_reverse($tracking); // Latest first
    }
    
    private function storeShipmentInfo($orderId, $provider, $shipmentData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO shipments (
                    order_id, provider, tracking_number, status, 
                    estimated_delivery, shipment_data, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                estimated_delivery = VALUES(estimated_delivery),
                shipment_data = VALUES(shipment_data)
            ");
            
            $stmt->execute([
                $orderId,
                $provider,
                $shipmentData['tracking_number'],
                $shipmentData['status'],
                $shipmentData['estimated_delivery'],
                json_encode($shipmentData)
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Shipment storage error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getShipmentInfo($orderId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM shipments 
                WHERE order_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$orderId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get shipment info error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateShipmentStatus($trackingNumber, $newStatus, $location = '') {
        try {
            $stmt = $this->conn->prepare("
                UPDATE shipments 
                SET status = ?, current_location = ?, last_updated = NOW() 
                WHERE tracking_number = ?
            ");
            $stmt->execute([$newStatus, $location, $trackingNumber]);
            
            // Log status change
            $this->logShipmentUpdate($trackingNumber, $newStatus, $location);
            
            return true;
        } catch (Exception $e) {
            error_log("Shipment status update error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logShipmentUpdate($trackingNumber, $status, $location) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO shipment_tracking_history (
                    tracking_number, status, location, created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$trackingNumber, $status, $location]);
        } catch (Exception $e) {
            error_log("Shipment tracking log error: " . $e->getMessage());
        }
    }
    
    public function getTrackingHistory($trackingNumber) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM shipment_tracking_history 
                WHERE tracking_number = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$trackingNumber]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get tracking history error: " . $e->getMessage());
            return [];
        }
    }
}
?>
