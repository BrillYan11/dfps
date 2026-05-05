<?php
// includes/locations_api.php
header('Content-Type: application/json');

// Base URL for PSGC API
// Example: https://psgc.gitlab.io/api/regions/
$base_url = "https://psgc.gitlab.io/api";

$action = $_GET['action'] ?? '';

function fetch_json($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $res) {
            return $res;
        }
    }

    // Fallback to file_get_contents if cURL is not available or fails
    $json = @file_get_contents($url);
    if ($json === false) {
        return json_encode(['error' => 'Failed to fetch data from PSGC API. Ensure allow_url_fopen or cURL is enabled.']);
    }
    return $json;
}

if ($action === 'regions') {
    echo fetch_json("$base_url/regions/");
} elseif ($action === 'provinces' && isset($_GET['region_id'])) {
    $region_id = urlencode($_GET['region_id']);
    echo fetch_json("$base_url/regions/$region_id/provinces/");
} elseif ($action === 'cities' && isset($_GET['province_id'])) {
    $province_id = urlencode($_GET['province_id']);
    echo fetch_json("$base_url/provinces/$province_id/cities-municipalities/");
} elseif ($action === 'barangays' && isset($_GET['city_id'])) {
    $city_id = urlencode($_GET['city_id']);
    // Try city endpoint first
    $res = fetch_json("$base_url/cities/$city_id/barangays/");
    $data = json_decode($res, true);
    
    // If it's an error or empty, try municipality endpoint
    if (isset($data['error']) || empty($data)) {
        $res = fetch_json("$base_url/municipalities/$city_id/barangays/");
    }
    echo $res;
} else {
    echo json_encode(['error' => 'Invalid action or missing parameters']);
}
?>
