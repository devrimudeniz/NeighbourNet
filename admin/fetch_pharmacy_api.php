<?php
require_once '../includes/db.php';
require_once '../includes/env.php';
require_once 'auth_session.php'; // Ensure admin is logged in (if you have this file, otherwise check session)

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}

/*
 * CollectAPI Configuration
 * Get your key from: https://collectapi.com/tr/api/health/noebetci-eczane-api
 */
$apiKey = env_value('COLLECTAPI_KEY', '');

function getCoordinates($address) {
    // If API sends coords, great. If not, we might need a geocoder or trust the API's 'loc' field if available.
    // CollectAPI 'dutyPharmacy' endpoint usually returns 'loc' (lat,lng).
    return null; 
}

try {
    if ($apiKey === '') {
        throw new Exception("CollectAPI key missing. Set COLLECTAPI_KEY in your .env file.");
    }

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.collectapi.com/health/dutyPharmacy?ilce=Kas&il=Antalya",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "authorization: $apiKey",
        "content-type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        throw new Exception("cURL Error: " . $err);
    }

    $data = json_decode($response, true);

    if (!$data || !isset($data['success']) || !$data['success']) {
        throw new Exception("API Error: " . ($data['message'] ?? 'Unknown error'));
    }

    // Reset current on duty status
    $pdo->exec("UPDATE pharmacies SET is_on_duty = 0");

    $count = 0;
    foreach ($data['result'] as $pharmacy) {
        // CollectAPI result format usually: name, dist, address, phone, loc
        
        $name = trim($pharmacy['name']);
        $dist = trim($pharmacy['dist']); // e.g. "Merkez", "Kalkan"
        $address = trim($pharmacy['address']);
        $phone = trim($pharmacy['phone']);
        $loc = isset($pharmacy['loc']) ? explode(',', $pharmacy['loc']) : null;
        
        // FILTER: Only want Kalkan pharmacies?
        // Check if dist is Kalkan OR address contains Kalkan
        $isKalkan = (stripos($dist, 'Kalkan') !== false) || (stripos($address, 'Kalkan') !== false);
        
        if ($isKalkan) {
            $lat = $loc ? trim($loc[0]) : null;
            $lng = $loc ? trim($loc[1]) : null;

            // Check if exists in DB
            $stmt = $pdo->prepare("SELECT id FROM pharmacies WHERE name = ?");
            $stmt->execute([$name]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update
                $upd = $pdo->prepare("UPDATE pharmacies SET is_on_duty = 1, phone = ?, address = ?, latitude = COALESCE(?, latitude), longitude = COALESCE(?, longitude) WHERE id = ?");
                $upd->execute([$phone, $address, $lat, $lng, $existing['id']]);
            } else {
                // Insert
                $ins = $pdo->prepare("INSERT INTO pharmacies (name, phone, address, latitude, longitude, is_on_duty) VALUES (?, ?, ?, ?, ?, 1)");
                $ins->execute([$name, $phone, $address, $lat, $lng]);
            }
            $count++;
        }
    }

    // Redirect back with message
    header("Location: pharmacies.php?msg=success&count=$count");
    exit();

} catch (Exception $e) {
    die("Hata: " . $e->getMessage());
}
?>
