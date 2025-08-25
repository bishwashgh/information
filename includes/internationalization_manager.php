<?php
// Milestone 12: International & Localization Features
class InternationalizationManager {
    private $conn;
    private $currentLanguage;
    private $currentCurrency;
    private $currentCountry;
    private $exchangeRates;
    private $translations;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->currentLanguage = $_SESSION['language'] ?? 'en';
        $this->currentCurrency = $_SESSION['currency'] ?? 'USD';
        $this->currentCountry = $_SESSION['country'] ?? 'US';
        $this->loadExchangeRates();
        $this->loadTranslations();
    }
    
    // Multi-Language Support
    public function getSupportedLanguages() {
        try {
            $stmt = $this->conn->prepare("
                SELECT code, name, native_name, flag_icon, is_active, is_rtl
                FROM languages 
                WHERE is_active = 1 
                ORDER BY sort_order, name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get supported languages error: " . $e->getMessage());
            return [
                ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag_icon' => '🇺🇸', 'is_active' => 1, 'is_rtl' => 0]
            ];
        }
    }
    
    public function setLanguage($languageCode) {
        try {
            // Validate language code
            $stmt = $this->conn->prepare("
                SELECT code FROM languages WHERE code = ? AND is_active = 1
            ");
            $stmt->execute([$languageCode]);
            
            if ($stmt->fetch()) {
                $_SESSION['language'] = $languageCode;
                $this->currentLanguage = $languageCode;
                $this->loadTranslations();
                
                return [
                    'success' => true,
                    'language' => $languageCode,
                    'message' => 'Language changed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid language code'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Set language error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error changing language'
            ];
        }
    }
    
    public function translate($key, $params = []) {
        $translation = $this->translations[$this->currentLanguage][$key] ?? 
                      $this->translations['en'][$key] ?? 
                      $key;
        
        // Replace parameters in translation
        foreach ($params as $param => $value) {
            $translation = str_replace("{{$param}}", $value, $translation);
        }
        
        return $translation;
    }
    
    private function loadTranslations() {
        try {
            $stmt = $this->conn->prepare("
                SELECT translation_key, translation_value
                FROM translations
                WHERE language_code = ?
            ");
            $stmt->execute([$this->currentLanguage]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->translations[$this->currentLanguage] = [];
            foreach ($results as $row) {
                $this->translations[$this->currentLanguage][$row['translation_key']] = $row['translation_value'];
            }
            
            // Load fallback English translations if current language is not English
            if ($this->currentLanguage !== 'en') {
                $stmt->execute(['en']);
                $englishResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $this->translations['en'] = [];
                foreach ($englishResults as $row) {
                    $this->translations['en'][$row['translation_key']] = $row['translation_value'];
                }
            }
            
        } catch (Exception $e) {
            error_log("Load translations error: " . $e->getMessage());
            $this->translations[$this->currentLanguage] = [];
        }
    }
    
    public function addTranslation($languageCode, $key, $value) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO translations (language_code, translation_key, translation_value, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                translation_value = VALUES(translation_value),
                updated_at = NOW()
            ");
            
            $stmt->execute([$languageCode, $key, $value]);
            
            // Reload translations if it's the current language
            if ($languageCode === $this->currentLanguage) {
                $this->loadTranslations();
            }
            
            return [
                'success' => true,
                'message' => 'Translation added successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Add translation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error adding translation'
            ];
        }
    }
    
    // Multi-Currency Support
    public function getSupportedCurrencies() {
        try {
            $stmt = $this->conn->prepare("
                SELECT code, name, symbol, exchange_rate, decimal_places, is_active
                FROM currencies 
                WHERE is_active = 1 
                ORDER BY sort_order, name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get supported currencies error: " . $e->getMessage());
            return [
                ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.00, 'decimal_places' => 2, 'is_active' => 1]
            ];
        }
    }
    
    public function setCurrency($currencyCode) {
        try {
            // Validate currency code
            $stmt = $this->conn->prepare("
                SELECT code FROM currencies WHERE code = ? AND is_active = 1
            ");
            $stmt->execute([$currencyCode]);
            
            if ($stmt->fetch()) {
                $_SESSION['currency'] = $currencyCode;
                $this->currentCurrency = $currencyCode;
                
                return [
                    'success' => true,
                    'currency' => $currencyCode,
                    'message' => 'Currency changed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid currency code'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Set currency error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error changing currency'
            ];
        }
    }
    
    public function convertPrice($amount, $fromCurrency = 'USD', $toCurrency = null) {
        $toCurrency = $toCurrency ?? $this->currentCurrency;
        
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        
        // Convert to USD first if source currency is not USD
        if ($fromCurrency !== 'USD') {
            $fromRate = $this->exchangeRates[$fromCurrency] ?? 1;
            $amount = $amount / $fromRate;
        }
        
        // Convert from USD to target currency
        if ($toCurrency !== 'USD') {
            $toRate = $this->exchangeRates[$toCurrency] ?? 1;
            $amount = $amount * $toRate;
        }
        
        return round($amount, 2);
    }
    
    public function formatPrice($amount, $currency = null) {
        $currency = $currency ?? $this->currentCurrency;
        
        try {
            $stmt = $this->conn->prepare("
                SELECT symbol, decimal_places FROM currencies WHERE code = ?
            ");
            $stmt->execute([$currency]);
            $currencyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($currencyInfo) {
                $symbol = $currencyInfo['symbol'];
                $decimals = $currencyInfo['decimal_places'];
                return $symbol . number_format($amount, $decimals);
            } else {
                return '$' . number_format($amount, 2);
            }
            
        } catch (Exception $e) {
            return '$' . number_format($amount, 2);
        }
    }
    
    private function loadExchangeRates() {
        try {
            $stmt = $this->conn->prepare("
                SELECT code, exchange_rate FROM currencies WHERE is_active = 1
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->exchangeRates = [];
            foreach ($results as $row) {
                $this->exchangeRates[$row['code']] = $row['exchange_rate'];
            }
            
        } catch (Exception $e) {
            error_log("Load exchange rates error: " . $e->getMessage());
            $this->exchangeRates = ['USD' => 1.00];
        }
    }
    
    public function updateExchangeRates() {
        try {
            // This would integrate with a real exchange rate API
            // For demo purposes, using mock data
            $newRates = $this->fetchExchangeRatesFromAPI();
            
            foreach ($newRates as $currency => $rate) {
                $stmt = $this->conn->prepare("
                    UPDATE currencies 
                    SET exchange_rate = ?, last_updated = NOW()
                    WHERE code = ?
                ");
                $stmt->execute([$rate, $currency]);
            }
            
            $this->loadExchangeRates();
            
            return [
                'success' => true,
                'updated_currencies' => count($newRates),
                'message' => 'Exchange rates updated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Update exchange rates error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error updating exchange rates'
            ];
        }
    }
    
    private function fetchExchangeRatesFromAPI() {
        // Mock exchange rates - in production, integrate with real API
        return [
            'EUR' => 0.85,
            'GBP' => 0.73,
            'JPY' => 110.0,
            'INR' => 83.0,
            'CAD' => 1.25,
            'AUD' => 1.35,
            'CHF' => 0.92,
            'CNY' => 6.45
        ];
    }
    
    // International Shipping
    public function getShippingZones() {
        try {
            $stmt = $this->conn->prepare("
                SELECT sz.*, GROUP_CONCAT(szc.country_code) as countries
                FROM shipping_zones sz
                LEFT JOIN shipping_zone_countries szc ON sz.id = szc.zone_id
                WHERE sz.is_active = 1
                GROUP BY sz.id
                ORDER BY sz.sort_order, sz.name
            ");
            $stmt->execute();
            $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($zones as &$zone) {
                $zone['countries'] = $zone['countries'] ? explode(',', $zone['countries']) : [];
                $zone['shipping_methods'] = $this->getZoneShippingMethods($zone['id']);
            }
            
            return $zones;
            
        } catch (Exception $e) {
            error_log("Get shipping zones error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getZoneShippingMethods($zoneId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM shipping_methods 
                WHERE zone_id = ? AND is_active = 1
                ORDER BY sort_order, name
            ");
            $stmt->execute([$zoneId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function calculateInternationalShipping($countryCode, $weight, $value, $dimensions = []) {
        try {
            // Get shipping zone for country
            $stmt = $this->conn->prepare("
                SELECT sz.* FROM shipping_zones sz
                JOIN shipping_zone_countries szc ON sz.id = szc.zone_id
                WHERE szc.country_code = ? AND sz.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$countryCode]);
            $zone = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$zone) {
                return [
                    'success' => false,
                    'message' => 'Shipping not available to this country'
                ];
            }
            
            // Get shipping methods for the zone
            $shippingMethods = $this->getZoneShippingMethods($zone['id']);
            
            $calculatedMethods = [];
            foreach ($shippingMethods as $method) {
                $cost = $this->calculateShippingCost($method, $weight, $value, $dimensions);
                $deliveryDays = $this->calculateDeliveryTime($method, $countryCode);
                
                $calculatedMethods[] = [
                    'id' => $method['id'],
                    'name' => $method['name'],
                    'description' => $method['description'],
                    'cost' => $cost,
                    'delivery_days' => $deliveryDays,
                    'tracking_available' => $method['tracking_available']
                ];
            }
            
            return [
                'success' => true,
                'zone' => $zone['name'],
                'methods' => $calculatedMethods
            ];
            
        } catch (Exception $e) {
            error_log("Calculate international shipping error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error calculating shipping'
            ];
        }
    }
    
    private function calculateShippingCost($method, $weight, $value, $dimensions) {
        $baseCost = $method['base_cost'];
        $weightCost = $weight * $method['cost_per_kg'];
        $valueCost = $value * ($method['value_percentage'] / 100);
        
        // Calculate dimensional weight if applicable
        if (!empty($dimensions) && $method['use_dimensional_weight']) {
            $dimensionalWeight = ($dimensions['length'] * $dimensions['width'] * $dimensions['height']) / 5000; // Divisor varies by carrier
            $weight = max($weight, $dimensionalWeight);
            $weightCost = $weight * $method['cost_per_kg'];
        }
        
        $totalCost = $baseCost + $weightCost + $valueCost;
        
        // Apply minimum and maximum limits
        if ($method['min_cost'] && $totalCost < $method['min_cost']) {
            $totalCost = $method['min_cost'];
        }
        if ($method['max_cost'] && $totalCost > $method['max_cost']) {
            $totalCost = $method['max_cost'];
        }
        
        return round($totalCost, 2);
    }
    
    private function calculateDeliveryTime($method, $countryCode) {
        $baseDays = $method['delivery_days'];
        
        // Add extra days for certain countries/regions
        $extraDays = 0;
        if (in_array($countryCode, ['AU', 'NZ'])) {
            $extraDays = 2; // Oceania
        } elseif (in_array($countryCode, ['JP', 'KR', 'CN', 'SG', 'MY', 'TH', 'VN', 'PH', 'ID'])) {
            $extraDays = 1; // Asia
        } elseif (in_array($countryCode, ['ZA', 'NG', 'KE', 'EG'])) {
            $extraDays = 3; // Africa
        }
        
        return [
            'min' => $baseDays + $extraDays,
            'max' => $baseDays + $extraDays + 3
        ];
    }
    
    // Regional Compliance and Regulations
    public function getRegionalCompliance($countryCode) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM regional_compliance 
                WHERE country_code = ? OR country_code = 'GLOBAL'
                ORDER BY country_code = ? DESC
            ");
            $stmt->execute([$countryCode, $countryCode]);
            $compliance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [
                'tax_requirements' => [],
                'shipping_restrictions' => [],
                'data_protection' => [],
                'payment_regulations' => [],
                'customs_information' => []
            ];
            
            foreach ($compliance as $rule) {
                switch ($rule['type']) {
                    case 'tax':
                        $result['tax_requirements'][] = $rule;
                        break;
                    case 'shipping':
                        $result['shipping_restrictions'][] = $rule;
                        break;
                    case 'data_protection':
                        $result['data_protection'][] = $rule;
                        break;
                    case 'payment':
                        $result['payment_regulations'][] = $rule;
                        break;
                    case 'customs':
                        $result['customs_information'][] = $rule;
                        break;
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Get regional compliance error: " . $e->getMessage());
            return [];
        }
    }
    
    public function calculateTaxes($amount, $countryCode, $productCategory = null) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM tax_rates 
                WHERE country_code = ? 
                AND (product_category = ? OR product_category IS NULL)
                AND is_active = 1
                ORDER BY product_category IS NOT NULL DESC
                LIMIT 1
            ");
            $stmt->execute([$countryCode, $productCategory]);
            $taxRate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$taxRate) {
                return [
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'tax_name' => 'No Tax',
                    'total_with_tax' => $amount
                ];
            }
            
            $taxAmount = $amount * ($taxRate['rate'] / 100);
            
            return [
                'tax_amount' => round($taxAmount, 2),
                'tax_rate' => $taxRate['rate'],
                'tax_name' => $taxRate['name'],
                'total_with_tax' => round($amount + $taxAmount, 2),
                'tax_id' => $taxRate['id']
            ];
            
        } catch (Exception $e) {
            error_log("Calculate taxes error: " . $e->getMessage());
            return [
                'tax_amount' => 0,
                'tax_rate' => 0,
                'tax_name' => 'Error',
                'total_with_tax' => $amount
            ];
        }
    }
    
    // Geolocation and Country Detection
    public function detectUserCountry($ipAddress = null) {
        try {
            $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // For demo purposes, return a default country
            // In production, this would use a geolocation service
            $detectedCountry = $this->lookupCountryByIP($ipAddress);
            
            if ($detectedCountry) {
                $_SESSION['country'] = $detectedCountry['code'];
                $this->currentCountry = $detectedCountry['code'];
                
                // Auto-set currency and language based on country
                if ($detectedCountry['default_currency']) {
                    $this->setCurrency($detectedCountry['default_currency']);
                }
                if ($detectedCountry['default_language']) {
                    $this->setLanguage($detectedCountry['default_language']);
                }
                
                return $detectedCountry;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Detect user country error: " . $e->getMessage());
            return null;
        }
    }
    
    private function lookupCountryByIP($ipAddress) {
        // Mock geolocation - in production, use services like MaxMind, IPStack, etc.
        $mockData = [
            '127.0.0.1' => ['code' => 'US', 'name' => 'United States', 'default_currency' => 'USD', 'default_language' => 'en'],
            '192.168.1.1' => ['code' => 'US', 'name' => 'United States', 'default_currency' => 'USD', 'default_language' => 'en']
        ];
        
        return $mockData[$ipAddress] ?? ['code' => 'US', 'name' => 'United States', 'default_currency' => 'USD', 'default_language' => 'en'];
    }
    
    public function getCountries() {
        try {
            $stmt = $this->conn->prepare("
                SELECT code, name, dial_code, flag_emoji, default_currency, 
                       default_language, is_active
                FROM countries 
                WHERE is_active = 1 
                ORDER BY name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get countries error: " . $e->getMessage());
            return [];
        }
    }
    
    // Date and Time Localization
    public function formatDate($date, $format = null) {
        $format = $format ?? $this->getDateFormat();
        
        if (is_string($date)) {
            $date = strtotime($date);
        }
        
        return date($format, $date);
    }
    
    public function formatDateTime($datetime, $format = null) {
        $format = $format ?? $this->getDateTimeFormat();
        
        if (is_string($datetime)) {
            $datetime = strtotime($datetime);
        }
        
        return date($format, $datetime);
    }
    
    private function getDateFormat() {
        $formats = [
            'en' => 'M j, Y',
            'es' => 'd/m/Y',
            'fr' => 'd/m/Y',
            'de' => 'd.m.Y',
            'it' => 'd/m/Y',
            'pt' => 'd/m/Y',
            'ja' => 'Y年m月d日',
            'ko' => 'Y년 m월 d일',
            'zh' => 'Y年m月d日'
        ];
        
        return $formats[$this->currentLanguage] ?? $formats['en'];
    }
    
    private function getDateTimeFormat() {
        $formats = [
            'en' => 'M j, Y g:i A',
            'es' => 'd/m/Y H:i',
            'fr' => 'd/m/Y H:i',
            'de' => 'd.m.Y H:i',
            'it' => 'd/m/Y H:i',
            'pt' => 'd/m/Y H:i',
            'ja' => 'Y年m月d日 H:i',
            'ko' => 'Y년 m월 d일 H:i',
            'zh' => 'Y年m月d日 H:i'
        ];
        
        return $formats[$this->currentLanguage] ?? $formats['en'];
    }
    
    // Utility Methods
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }
    
    public function getCurrentCurrency() {
        return $this->currentCurrency;
    }
    
    public function getCurrentCountry() {
        return $this->currentCountry;
    }
    
    public function isRTL() {
        try {
            $stmt = $this->conn->prepare("
                SELECT is_rtl FROM languages WHERE code = ?
            ");
            $stmt->execute([$this->currentLanguage]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (bool)$result['is_rtl'] : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getLocalizationData() {
        return [
            'language' => $this->currentLanguage,
            'currency' => $this->currentCurrency,
            'country' => $this->currentCountry,
            'is_rtl' => $this->isRTL(),
            'supported_languages' => $this->getSupportedLanguages(),
            'supported_currencies' => $this->getSupportedCurrencies(),
            'countries' => $this->getCountries(),
            'date_format' => $this->getDateFormat(),
            'datetime_format' => $this->getDateTimeFormat()
        ];
    }
}
?>
