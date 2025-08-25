<?php
// Milestone 10: AI & Machine Learning Features
class AIEngine {
    private $conn;
    private $apiKeys;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->apiKeys = [
            'openai' => 'YOUR_OPENAI_API_KEY',
            'google_vision' => 'YOUR_GOOGLE_VISION_API_KEY',
            'azure_cognitive' => 'YOUR_AZURE_COGNITIVE_API_KEY'
        ];
    }
    
    // AI-Powered Product Recommendations
    public function getPersonalizedRecommendations($userId, $limit = 10) {
        try {
            // Get user's purchase history and behavior
            $userProfile = $this->buildUserProfile($userId);
            
            // Get collaborative filtering recommendations
            $collaborativeRecs = $this->getCollaborativeRecommendations($userId, $userProfile);
            
            // Get content-based recommendations
            $contentRecs = $this->getContentBasedRecommendations($userId, $userProfile);
            
            // Combine and rank recommendations
            $combinedRecs = $this->combineRecommendations($collaborativeRecs, $contentRecs);
            
            // Apply business rules and filters
            $finalRecs = $this->applyBusinessRules($combinedRecs, $userId);
            
            // Log recommendation generation
            $this->logRecommendation($userId, array_slice($finalRecs, 0, $limit));
            
            return array_slice($finalRecs, 0, $limit);
            
        } catch (Exception $e) {
            error_log("AI Recommendations error: " . $e->getMessage());
            return $this->getFallbackRecommendations($limit);
        }
    }
    
    private function buildUserProfile($userId) {
        try {
            $profile = [
                'purchase_history' => [],
                'viewed_products' => [],
                'categories_preference' => [],
                'price_range' => [],
                'brand_preference' => [],
                'seasonal_patterns' => []
            ];
            
            // Purchase history
            $stmt = $this->conn->prepare("
                SELECT p.*, oi.quantity, o.created_at as purchase_date
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.user_id = ? AND o.status IN ('completed', 'delivered')
                ORDER BY o.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$userId]);
            $profile['purchase_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Viewed products
            $stmt = $this->conn->prepare("
                SELECT p.*, pv.created_at as view_date, COUNT(*) as view_count
                FROM product_views pv
                JOIN products p ON pv.product_id = p.id
                WHERE pv.user_id = ?
                GROUP BY p.id
                ORDER BY view_count DESC, pv.created_at DESC
                LIMIT 30
            ");
            $stmt->execute([$userId]);
            $profile['viewed_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Category preferences
            $stmt = $this->conn->prepare("
                SELECT c.name, c.id, COUNT(*) as interaction_count,
                       AVG(oi.price) as avg_spent
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.user_id = ? AND o.status IN ('completed', 'delivered')
                GROUP BY c.id
                ORDER BY interaction_count DESC
            ");
            $stmt->execute([$userId]);
            $profile['categories_preference'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $profile;
            
        } catch (Exception $e) {
            error_log("User profile building error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getCollaborativeRecommendations($userId, $userProfile) {
        try {
            // Find similar users based on purchase patterns
            $similarUsers = $this->findSimilarUsers($userId, $userProfile);
            
            $recommendations = [];
            
            foreach ($similarUsers as $similarUser) {
                $stmt = $this->conn->prepare("
                    SELECT p.*, COUNT(*) as purchase_frequency,
                           AVG(oi.price) as avg_price
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.user_id = ? 
                    AND o.status IN ('completed', 'delivered')
                    AND p.id NOT IN (
                        SELECT DISTINCT product_id 
                        FROM order_items oi2 
                        JOIN orders o2 ON oi2.order_id = o2.id 
                        WHERE o2.user_id = ?
                    )
                    GROUP BY p.id
                    ORDER BY purchase_frequency DESC
                    LIMIT 20
                ");
                
                $stmt->execute([$similarUser['user_id'], $userId]);
                $userRecs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($userRecs as $rec) {
                    $productId = $rec['id'];
                    if (!isset($recommendations[$productId])) {
                        $recommendations[$productId] = $rec;
                        $recommendations[$productId]['collaborative_score'] = 0;
                    }
                    $recommendations[$productId]['collaborative_score'] += $similarUser['similarity_score'] * $rec['purchase_frequency'];
                }
            }
            
            // Sort by collaborative score
            uasort($recommendations, function($a, $b) {
                return $b['collaborative_score'] <=> $a['collaborative_score'];
            });
            
            return array_values($recommendations);
            
        } catch (Exception $e) {
            error_log("Collaborative filtering error: " . $e->getMessage());
            return [];
        }
    }
    
    private function findSimilarUsers($userId, $userProfile) {
        try {
            $similarities = [];
            
            // Get users who bought similar products
            $stmt = $this->conn->prepare("
                SELECT DISTINCT o.user_id, COUNT(*) as common_products
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.product_id IN (
                    SELECT DISTINCT product_id 
                    FROM order_items oi2 
                    JOIN orders o2 ON oi2.order_id = o2.id 
                    WHERE o2.user_id = ?
                )
                AND o.user_id != ?
                AND o.status IN ('completed', 'delivered')
                GROUP BY o.user_id
                HAVING common_products >= 2
                ORDER BY common_products DESC
                LIMIT 50
            ");
            
            $stmt->execute([$userId, $userId]);
            $similarUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate similarity scores
            foreach ($similarUsers as &$user) {
                $user['similarity_score'] = $this->calculateUserSimilarity($userId, $user['user_id'], $userProfile);
            }
            
            // Sort by similarity score
            usort($similarUsers, function($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });
            
            return array_slice($similarUsers, 0, 10);
            
        } catch (Exception $e) {
            error_log("Similar users finding error: " . $e->getMessage());
            return [];
        }
    }
    
    private function calculateUserSimilarity($userId1, $userId2, $userProfile) {
        // Simplified similarity calculation based on Jaccard similarity
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT CASE WHEN o1.user_id = ? THEN oi1.product_id END) as user1_products,
                    COUNT(DISTINCT CASE WHEN o2.user_id = ? THEN oi2.product_id END) as user2_products,
                    COUNT(DISTINCT CASE WHEN o1.user_id = ? AND o2.user_id = ? THEN oi1.product_id END) as common_products
                FROM order_items oi1
                JOIN orders o1 ON oi1.order_id = o1.id
                JOIN order_items oi2 ON oi1.product_id = oi2.product_id
                JOIN orders o2 ON oi2.order_id = o2.id
                WHERE (o1.user_id = ? OR o2.user_id = ?)
                AND o1.status IN ('completed', 'delivered')
                AND o2.status IN ('completed', 'delivered')
            ");
            
            $stmt->execute([$userId1, $userId2, $userId1, $userId2, $userId1, $userId2]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $union = $result['user1_products'] + $result['user2_products'] - $result['common_products'];
            if ($union == 0) return 0;
            
            return $result['common_products'] / $union;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getContentBasedRecommendations($userId, $userProfile) {
        try {
            $recommendations = [];
            
            // Get recommendations based on category preferences
            foreach ($userProfile['categories_preference'] as $category) {
                $stmt = $this->conn->prepare("
                    SELECT p.*, 
                           AVG(pr.rating) as avg_rating,
                           COUNT(pr.id) as review_count
                    FROM products p
                    LEFT JOIN product_reviews pr ON p.id = pr.product_id
                    WHERE p.category_id = ?
                    AND p.status = 'active'
                    AND p.id NOT IN (
                        SELECT DISTINCT product_id 
                        FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.id 
                        WHERE o.user_id = ?
                    )
                    GROUP BY p.id
                    HAVING avg_rating >= 4.0 OR avg_rating IS NULL
                    ORDER BY avg_rating DESC, review_count DESC
                    LIMIT 10
                ");
                
                $stmt->execute([$category['id'], $userId]);
                $categoryRecs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($categoryRecs as $rec) {
                    $rec['content_score'] = $category['interaction_count'] * ($rec['avg_rating'] ?? 4.0);
                    $recommendations[] = $rec;
                }
            }
            
            // Sort by content score
            usort($recommendations, function($a, $b) {
                return $b['content_score'] <=> $a['content_score'];
            });
            
            return $recommendations;
            
        } catch (Exception $e) {
            error_log("Content-based recommendations error: " . $e->getMessage());
            return [];
        }
    }
    
    private function combineRecommendations($collaborativeRecs, $contentRecs) {
        $combined = [];
        $weights = ['collaborative' => 0.6, 'content' => 0.4];
        
        // Index recommendations by product ID
        $allRecs = [];
        
        foreach ($collaborativeRecs as $rec) {
            $allRecs[$rec['id']] = $rec;
            $allRecs[$rec['id']]['final_score'] = ($rec['collaborative_score'] ?? 0) * $weights['collaborative'];
        }
        
        foreach ($contentRecs as $rec) {
            if (isset($allRecs[$rec['id']])) {
                $allRecs[$rec['id']]['final_score'] += ($rec['content_score'] ?? 0) * $weights['content'];
            } else {
                $allRecs[$rec['id']] = $rec;
                $allRecs[$rec['id']]['final_score'] = ($rec['content_score'] ?? 0) * $weights['content'];
            }
        }
        
        // Sort by final score
        uasort($allRecs, function($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });
        
        return array_values($allRecs);
    }
    
    private function applyBusinessRules($recommendations, $userId) {
        $filtered = [];
        
        foreach ($recommendations as $rec) {
            // Check stock availability
            if ($rec['stock_quantity'] <= 0) continue;
            
            // Check if product is active
            if ($rec['status'] !== 'active') continue;
            
            // Add business logic (e.g., promote certain categories, exclude certain products)
            $filtered[] = $rec;
        }
        
        return $filtered;
    }
    
    // Intelligent Search with Auto-suggestions
    public function intelligentSearch($query, $userId = null, $limit = 20) {
        try {
            $searchResults = [];
            
            // Basic text search
            $textResults = $this->performTextSearch($query, $limit);
            
            // Semantic search using AI
            $semanticResults = $this->performSemanticSearch($query, $limit);
            
            // If user is logged in, personalize results
            if ($userId) {
                $personalizedResults = $this->personalizeSearchResults($textResults, $userId);
                $searchResults = $personalizedResults;
            } else {
                $searchResults = $textResults;
            }
            
            // Log search query for analytics
            $this->logSearchQuery($query, $userId, count($searchResults));
            
            return [
                'results' => $searchResults,
                'suggestions' => $this->getSearchSuggestions($query),
                'total' => count($searchResults)
            ];
            
        } catch (Exception $e) {
            error_log("Intelligent search error: " . $e->getMessage());
            return ['results' => [], 'suggestions' => [], 'total' => 0];
        }
    }
    
    private function performTextSearch($query, $limit) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, pi.image_url, c.name as category_name,
                       AVG(pr.rating) as avg_rating,
                       COUNT(pr.id) as review_count,
                       MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_reviews pr ON p.id = pr.product_id
                WHERE p.status = 'active'
                AND (
                    MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)
                    OR p.name LIKE ?
                    OR p.description LIKE ?
                    OR c.name LIKE ?
                )
                GROUP BY p.id
                ORDER BY relevance DESC, avg_rating DESC
                LIMIT ?
            ");
            
            $searchTerm = '%' . $query . '%';
            $stmt->execute([$query, $query, $searchTerm, $searchTerm, $searchTerm, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Text search error: " . $e->getMessage());
            return [];
        }
    }
    
    private function performSemanticSearch($query, $limit) {
        // This would integrate with AI services for semantic understanding
        // For now, return enhanced keyword matching
        try {
            $synonyms = $this->getQuerySynonyms($query);
            $expandedQuery = $query . ' ' . implode(' ', $synonyms);
            
            return $this->performTextSearch($expandedQuery, $limit);
            
        } catch (Exception $e) {
            error_log("Semantic search error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getQuerySynonyms($query) {
        // Simple synonym mapping - in production, this would use NLP services
        $synonymMap = [
            'shirt' => ['top', 'blouse', 'tee'],
            'pants' => ['trousers', 'jeans', 'bottoms'],
            'shoes' => ['footwear', 'sneakers', 'boots'],
            'dress' => ['gown', 'frock', 'outfit'],
            'bag' => ['purse', 'handbag', 'tote']
        ];
        
        $words = explode(' ', strtolower($query));
        $synonyms = [];
        
        foreach ($words as $word) {
            if (isset($synonymMap[$word])) {
                $synonyms = array_merge($synonyms, $synonymMap[$word]);
            }
        }
        
        return array_unique($synonyms);
    }
    
    private function personalizeSearchResults($results, $userId) {
        try {
            $userProfile = $this->buildUserProfile($userId);
            
            foreach ($results as &$result) {
                $personalizeScore = 0;
                
                // Boost products from preferred categories
                foreach ($userProfile['categories_preference'] as $category) {
                    if ($result['category_id'] == $category['id']) {
                        $personalizeScore += $category['interaction_count'] * 0.1;
                        break;
                    }
                }
                
                // Boost products in user's price range
                $avgSpent = $this->calculateUserAverageSpent($userId);
                if ($result['price'] <= $avgSpent * 1.2) {
                    $personalizeScore += 0.5;
                }
                
                $result['personalization_score'] = $personalizeScore;
            }
            
            // Re-sort with personalization
            usort($results, function($a, $b) {
                $scoreA = ($a['relevance'] ?? 0) + ($a['personalization_score'] ?? 0);
                $scoreB = ($b['relevance'] ?? 0) + ($b['personalization_score'] ?? 0);
                return $scoreB <=> $scoreA;
            });
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Search personalization error: " . $e->getMessage());
            return $results;
        }
    }
    
    private function calculateUserAverageSpent($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT AVG(oi.price) as avg_spent
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.user_id = ? AND o.status IN ('completed', 'delivered')
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['avg_spent'] ?? 1000; // Default fallback
            
        } catch (Exception $e) {
            return 1000;
        }
    }
    
    public function getSearchSuggestions($query) {
        try {
            $suggestions = [];
            
            // Get suggestions from popular searches
            $stmt = $this->conn->prepare("
                SELECT search_query, COUNT(*) as search_count
                FROM search_logs
                WHERE search_query LIKE ?
                GROUP BY search_query
                ORDER BY search_count DESC
                LIMIT 5
            ");
            $stmt->execute(['%' . $query . '%']);
            $popularSearches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($popularSearches as $search) {
                $suggestions[] = $search['search_query'];
            }
            
            // Get suggestions from product names
            $stmt = $this->conn->prepare("
                SELECT DISTINCT name
                FROM products
                WHERE name LIKE ? AND status = 'active'
                ORDER BY name
                LIMIT 5
            ");
            $stmt->execute(['%' . $query . '%']);
            $productNames = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($productNames as $product) {
                $suggestions[] = $product['name'];
            }
            
            return array_unique($suggestions);
            
        } catch (Exception $e) {
            error_log("Search suggestions error: " . $e->getMessage());
            return [];
        }
    }
    
    // AI Chatbot Integration
    public function processChatbotQuery($message, $userId = null, $context = []) {
        try {
            $intent = $this->detectIntent($message);
            $response = '';
            
            switch ($intent['category']) {
                case 'product_inquiry':
                    $response = $this->handleProductInquiry($message, $intent, $userId);
                    break;
                    
                case 'order_status':
                    $response = $this->handleOrderStatusInquiry($message, $userId);
                    break;
                    
                case 'support_request':
                    $response = $this->handleSupportRequest($message, $userId);
                    break;
                    
                case 'recommendation':
                    $response = $this->handleRecommendationRequest($message, $userId);
                    break;
                    
                default:
                    $response = $this->generateGeneralResponse($message);
            }
            
            // Log chatbot interaction
            $this->logChatbotInteraction($userId, $message, $response, $intent);
            
            return [
                'response' => $response,
                'intent' => $intent,
                'suggestions' => $this->getChatSuggestions($intent['category'])
            ];
            
        } catch (Exception $e) {
            error_log("Chatbot processing error: " . $e->getMessage());
            return [
                'response' => 'I apologize, but I am experiencing technical difficulties. Please try again later or contact our support team.',
                'intent' => ['category' => 'error', 'confidence' => 0],
                'suggestions' => ['Contact Support', 'Try Again']
            ];
        }
    }
    
    private function detectIntent($message) {
        $message = strtolower($message);
        
        // Simple keyword-based intent detection
        // In production, this would use NLP services
        $intents = [
            'product_inquiry' => ['product', 'item', 'buy', 'purchase', 'price', 'cost', 'available', 'stock'],
            'order_status' => ['order', 'delivery', 'shipped', 'status', 'tracking', 'when'],
            'support_request' => ['help', 'problem', 'issue', 'complaint', 'return', 'refund', 'support'],
            'recommendation' => ['recommend', 'suggest', 'similar', 'like', 'what should', 'advice']
        ];
        
        $scores = [];
        foreach ($intents as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $score++;
                }
            }
            $scores[$category] = $score;
        }
        
        $maxScore = max($scores);
        $detectedIntent = array_search($maxScore, $scores);
        
        return [
            'category' => $detectedIntent ?: 'general',
            'confidence' => $maxScore > 0 ? min($maxScore / 3, 1) : 0.1
        ];
    }
    
    private function handleProductInquiry($message, $intent, $userId) {
        // Extract product-related keywords and provide relevant information
        $products = $this->intelligentSearch($message, $userId, 3);
        
        if (!empty($products['results'])) {
            $response = "I found some products that might interest you:\n\n";
            foreach ($products['results'] as $product) {
                $response .= "• {$product['name']} - ₹{$product['price']}\n";
                if ($product['stock_quantity'] > 0) {
                    $response .= "  ✅ In stock\n";
                } else {
                    $response .= "  ❌ Out of stock\n";
                }
            }
            $response .= "\nWould you like more details about any of these products?";
        } else {
            $response = "I couldn't find any products matching your query. Could you please be more specific or try different keywords?";
        }
        
        return $response;
    }
    
    private function handleOrderStatusInquiry($message, $userId) {
        if (!$userId) {
            return "To check your order status, please log in to your account first.";
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT order_number, status, total_amount, created_at
                FROM orders
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 3
            ");
            $stmt->execute([$userId]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($orders)) {
                return "You don't have any recent orders. Would you like to browse our products?";
            }
            
            $response = "Here are your recent orders:\n\n";
            foreach ($orders as $order) {
                $response .= "• Order #{$order['order_number']}\n";
                $response .= "  Status: " . ucfirst($order['status']) . "\n";
                $response .= "  Amount: ₹{$order['total_amount']}\n";
                $response .= "  Date: " . date('M j, Y', strtotime($order['created_at'])) . "\n\n";
            }
            
            return $response;
            
        } catch (Exception $e) {
            return "I'm having trouble accessing your order information. Please try again later.";
        }
    }
    
    private function handleSupportRequest($message, $userId) {
        $response = "I understand you need assistance. I can help with:\n\n";
        $response .= "• Order tracking and delivery information\n";
        $response .= "• Product information and availability\n";
        $response .= "• Return and refund policies\n";
        $response .= "• Account-related questions\n\n";
        $response .= "For complex issues, I can connect you with our human support team. What specific help do you need?";
        
        return $response;
    }
    
    private function handleRecommendationRequest($message, $userId) {
        if ($userId) {
            $recommendations = $this->getPersonalizedRecommendations($userId, 3);
            
            if (!empty($recommendations)) {
                $response = "Based on your preferences, I recommend:\n\n";
                foreach ($recommendations as $product) {
                    $response .= "• {$product['name']} - ₹{$product['price']}\n";
                }
                $response .= "\nWould you like to see more details or get additional recommendations?";
            } else {
                $response = "I'd love to provide personalized recommendations! Could you tell me what type of products you're interested in?";
            }
        } else {
            $response = "I can provide better recommendations if you log in to your account. For now, here are some popular products that customers love!";
        }
        
        return $response;
    }
    
    private function generateGeneralResponse($message) {
        $responses = [
            "I'm here to help! You can ask me about products, orders, recommendations, or any other questions.",
            "How can I assist you today? I can help with product information, order tracking, or recommendations.",
            "I'm your shopping assistant! Feel free to ask about our products, your orders, or anything else I can help with."
        ];
        
        return $responses[array_rand($responses)];
    }
    
    private function getChatSuggestions($intentCategory) {
        $suggestions = [
            'product_inquiry' => ['Show me similar products', 'Check availability', 'Compare prices'],
            'order_status' => ['Track my order', 'Update delivery address', 'Contact delivery team'],
            'support_request' => ['Return policy', 'Refund status', 'Contact human agent'],
            'recommendation' => ['Show more recommendations', 'Different category', 'Price range'],
            'general' => ['View my orders', 'Browse products', 'Get recommendations']
        ];
        
        return $suggestions[$intentCategory] ?? $suggestions['general'];
    }
    
    // Predictive Analytics for Inventory
    public function predictInventoryDemand($productId, $days = 30) {
        try {
            // Get historical sales data
            $salesHistory = $this->getProductSalesHistory($productId, 90);
            
            if (empty($salesHistory)) {
                return ['predicted_demand' => 0, 'confidence' => 'low'];
            }
            
            // Simple moving average prediction
            $recentSales = array_slice($salesHistory, -30);
            $avgDailySales = array_sum($recentSales) / count($recentSales);
            
            // Apply seasonal and trend adjustments
            $seasonalFactor = $this->calculateSeasonalFactor($productId);
            $trendFactor = $this->calculateTrendFactor($salesHistory);
            
            $predictedDemand = $avgDailySales * $days * $seasonalFactor * $trendFactor;
            
            // Calculate confidence based on data consistency
            $confidence = $this->calculatePredictionConfidence($salesHistory);
            
            return [
                'predicted_demand' => round($predictedDemand),
                'confidence' => $confidence,
                'current_stock' => $this->getCurrentStock($productId),
                'recommended_action' => $this->getInventoryRecommendation($productId, $predictedDemand)
            ];
            
        } catch (Exception $e) {
            error_log("Inventory prediction error: " . $e->getMessage());
            return ['predicted_demand' => 0, 'confidence' => 'low'];
        }
    }
    
    private function getProductSalesHistory($productId, $days) {
        try {
            $stmt = $this->conn->prepare("
                SELECT DATE(o.created_at) as sale_date, SUM(oi.quantity) as daily_sales
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.product_id = ?
                AND o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND o.status IN ('completed', 'delivered')
                GROUP BY DATE(o.created_at)
                ORDER BY sale_date
            ");
            $stmt->execute([$productId, $days]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_column($results, 'daily_sales');
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function calculateSeasonalFactor($productId) {
        // Simplified seasonal calculation based on current month
        $currentMonth = date('n');
        $seasonalFactors = [
            1 => 0.8,  // January - post-holiday dip
            2 => 0.9,  // February
            3 => 1.0,  // March
            4 => 1.1,  // April
            5 => 1.2,  // May
            6 => 1.1,  // June
            7 => 1.0,  // July
            8 => 1.0,  // August
            9 => 1.1,  // September
            10 => 1.3, // October - festival season
            11 => 1.4, // November - festival/shopping season
            12 => 1.2  // December - holiday season
        ];
        
        return $seasonalFactors[$currentMonth] ?? 1.0;
    }
    
    private function calculateTrendFactor($salesHistory) {
        if (count($salesHistory) < 14) {
            return 1.0; // Not enough data for trend analysis
        }
        
        $firstHalf = array_slice($salesHistory, 0, count($salesHistory) / 2);
        $secondHalf = array_slice($salesHistory, count($salesHistory) / 2);
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        if ($firstAvg == 0) return 1.0;
        
        return $secondAvg / $firstAvg;
    }
    
    private function calculatePredictionConfidence($salesHistory) {
        if (count($salesHistory) < 7) {
            return 'low';
        }
        
        $mean = array_sum($salesHistory) / count($salesHistory);
        $variance = 0;
        
        foreach ($salesHistory as $sale) {
            $variance += pow($sale - $mean, 2);
        }
        
        $stdDev = sqrt($variance / count($salesHistory));
        $coefficient = $mean > 0 ? $stdDev / $mean : 1;
        
        if ($coefficient < 0.3) return 'high';
        if ($coefficient < 0.6) return 'medium';
        return 'low';
    }
    
    private function getCurrentStock($productId) {
        try {
            $stmt = $this->conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['stock_quantity'] ?? 0;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getInventoryRecommendation($productId, $predictedDemand) {
        $currentStock = $this->getCurrentStock($productId);
        
        if ($currentStock < $predictedDemand * 0.5) {
            return 'urgent_reorder';
        } elseif ($currentStock < $predictedDemand) {
            return 'reorder_soon';
        } elseif ($currentStock > $predictedDemand * 2) {
            return 'excess_stock';
        }
        
        return 'optimal';
    }
    
    // Sentiment Analysis for Reviews
    public function analyzeSentiment($text) {
        try {
            // Simple keyword-based sentiment analysis
            // In production, this would use ML services like Google Cloud Natural Language
            
            $positiveWords = ['good', 'great', 'excellent', 'amazing', 'love', 'perfect', 'awesome', 'fantastic', 'wonderful', 'beautiful'];
            $negativeWords = ['bad', 'terrible', 'awful', 'hate', 'horrible', 'disgusting', 'worst', 'disappointing', 'poor', 'cheap'];
            
            $text = strtolower($text);
            $words = preg_split('/\W+/', $text);
            
            $positiveScore = 0;
            $negativeScore = 0;
            
            foreach ($words as $word) {
                if (in_array($word, $positiveWords)) {
                    $positiveScore++;
                } elseif (in_array($word, $negativeWords)) {
                    $negativeScore++;
                }
            }
            
            $totalWords = count($words);
            $netSentiment = $positiveScore - $negativeScore;
            
            if ($netSentiment > 0) {
                $sentiment = 'positive';
                $confidence = min($netSentiment / $totalWords * 10, 1);
            } elseif ($netSentiment < 0) {
                $sentiment = 'negative';
                $confidence = min(abs($netSentiment) / $totalWords * 10, 1);
            } else {
                $sentiment = 'neutral';
                $confidence = 0.5;
            }
            
            return [
                'sentiment' => $sentiment,
                'confidence' => $confidence,
                'positive_score' => $positiveScore,
                'negative_score' => $negativeScore
            ];
            
        } catch (Exception $e) {
            error_log("Sentiment analysis error: " . $e->getMessage());
            return ['sentiment' => 'neutral', 'confidence' => 0];
        }
    }
    
    // Logging functions
    private function logRecommendation($userId, $recommendations) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ai_recommendation_logs (
                    user_id, recommendations, algorithm_version, created_at
                ) VALUES (?, ?, '1.0', NOW())
            ");
            
            $stmt->execute([$userId, json_encode($recommendations)]);
            
        } catch (Exception $e) {
            error_log("Recommendation logging error: " . $e->getMessage());
        }
    }
    
    private function logSearchQuery($query, $userId, $resultCount) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO search_logs (
                    user_id, search_query, result_count, ip_address, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $query,
                $resultCount,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            error_log("Search logging error: " . $e->getMessage());
        }
    }
    
    private function logChatbotInteraction($userId, $message, $response, $intent) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO chatbot_logs (
                    user_id, message, response, intent_category, intent_confidence, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $message,
                $response,
                $intent['category'],
                $intent['confidence']
            ]);
            
        } catch (Exception $e) {
            error_log("Chatbot logging error: " . $e->getMessage());
        }
    }
    
    private function getFallbackRecommendations($limit) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, pi.image_url, AVG(pr.rating) as avg_rating
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN product_reviews pr ON p.id = pr.product_id
                WHERE p.status = 'active' AND p.stock_quantity > 0
                GROUP BY p.id
                ORDER BY avg_rating DESC, p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
