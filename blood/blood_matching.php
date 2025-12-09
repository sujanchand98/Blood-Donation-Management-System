<?php
class BloodMatching {
    
    // Algorithm to find compatible blood groups
    public static function getCompatibleBloodGroups($required_blood_group) {
        $compatibility = [
            'A+' => ['A+', 'A-', 'O+', 'O-'],
            'A-' => ['A-', 'O-'],
            'B+' => ['B+', 'B-', 'O+', 'O-'],
            'B-' => ['B-', 'O-'],
            'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'AB-' => ['A-', 'B-', 'AB-', 'O-'],
            'O+' => ['O+', 'O-'],
            'O-' => ['O-']
        ];
        
        return isset($compatibility[$required_blood_group]) ? $compatibility[$required_blood_group] : [];
    }
    
    // Algorithm to find potential donors
    public static function findPotentialDonors($db, $required_blood_group, $location = null) {
        $compatible_groups = self::getCompatibleBloodGroups($required_blood_group);
        
        // Check if there are any compatible groups
        if (empty($compatible_groups)) {
            return [];
        }
        
        // Build the query with proper placeholders
        $placeholders = str_repeat('?,', count($compatible_groups) - 1) . '?';
        
        $query = "SELECT u.id, u.full_name, u.blood_group, u.phone, u.address 
                  FROM users u 
                  WHERE u.role = 'donor' 
                  AND u.blood_group IN ($placeholders)";
        
        $params = $compatible_groups;
        
        if ($location && !empty(trim($location))) {
            $query .= " AND u.address LIKE ?";
            $params[] = "%" . trim($location) . "%";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Algorithm to calculate blood urgency score
    public static function calculateUrgencyScore($blood_group, $required_quantity, $available_quantity) {
        $shortage_ratio = ($required_quantity - $available_quantity) / max($required_quantity, 1);
        
        // Blood group rarity factor (approximate percentages)
        $rarity_factors = [
            'A+' => 1.0, 'O+' => 1.0,
            'B+' => 1.1, 'A-' => 1.2,
            'O-' => 1.3, 'B-' => 1.4,
            'AB+' => 1.5, 'AB-' => 1.8
        ];
        
        $rarity = isset($rarity_factors[$blood_group]) ? $rarity_factors[$blood_group] : 1.0;
        
        $urgency_score = $shortage_ratio * $rarity * 100;
        
        return min(max($urgency_score, 0), 100);
    }
    
    // New algorithm: Find nearby donors based on location similarity
    public static function findNearbyDonors($db, $required_blood_group, $user_location, $max_distance_km = 50) {
        $compatible_groups = self::getCompatibleBloodGroups($required_blood_group);
        
        if (empty($compatible_groups)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($compatible_groups) - 1) . '?';
        
        // Simple location-based matching (in a real app, you'd use geolocation APIs)
        $query = "SELECT u.id, u.full_name, u.blood_group, u.phone, u.address 
                  FROM users u 
                  WHERE u.role = 'donor' 
                  AND u.blood_group IN ($placeholders)
                  AND u.address IS NOT NULL 
                  AND u.address != ''";
        
        $params = $compatible_groups;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $all_donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter donors by location similarity (simple string matching)
        if (!empty($user_location)) {
            $user_city = self::extractCityFromAddress($user_location);
            $filtered_donors = [];
            
            foreach ($all_donors as $donor) {
                if (self::isLocationSimilar($user_city, $donor['address'])) {
                    $filtered_donors[] = $donor;
                }
            }
            
            return $filtered_donors;
        }
        
        return $all_donors;
    }
    
    // Helper function to extract city from address
    private static function extractCityFromAddress($address) {
        // Simple city extraction - in real app, use geocoding API
        $parts = explode(',', $address);
        return trim(end($parts)); // Assume city is the last part
    }
    
    // Helper function for simple location matching
    private static function isLocationSimilar($user_city, $donor_address) {
        $donor_city = self::extractCityFromAddress($donor_address);
        return stripos($donor_address, $user_city) !== false || 
               stripos($user_city, $donor_city) !== false;
    }
    
    // Algorithm to calculate donor matching score
    public static function calculateDonorScore($donor, $required_blood_group, $user_location = null) {
        $score = 0;
        
        // Blood group compatibility score (highest priority)
        $compatible_groups = self::getCompatibleBloodGroups($required_blood_group);
        if (in_array($donor['blood_group'], $compatible_groups)) {
            $score += 50;
        }
        
        // Location score
        if ($user_location && !empty($donor['address'])) {
            if (self::isLocationSimilar(self::extractCityFromAddress($user_location), $donor['address'])) {
                $score += 30;
            }
        }
        
        // Availability score (if we had last donation date)
        $score += 20; // Base availability score
        
        return $score;
    }
    
    // Algorithm to sort donors by relevance
    public static function sortDonorsByRelevance($donors, $required_blood_group, $user_location = null) {
        usort($donors, function($a, $b) use ($required_blood_group, $user_location) {
            $scoreA = self::calculateDonorScore($a, $required_blood_group, $user_location);
            $scoreB = self::calculateDonorScore($b, $required_blood_group, $user_location);
            return $scoreB - $scoreA; // Descending order
        });
        
        return $donors;
    }
}
?>