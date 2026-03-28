<?php
require_once __DIR__ . '/env.php';

function updatePharmaciesFromApi($pdo) {
    $apiKey = env_value('COLLECTAPI_KEY', '');

    if ($apiKey === '') {
        return ['success' => false, 'message' => 'CollectAPI key is missing'];
    }

    try {
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

        if ($err) return ['success' => false, 'message' => "cURL Error: $err"];

        $data = json_decode($response, true);
        if (!$data || !isset($data['success']) || !$data['success']) {
            return ['success' => false, 'message' => "API Error: " . ($data['message'] ?? 'Unknown')];
        }

        // Reset previous duty dates (optional, or just leave them as history)
        // We mainly care about setting is_on_duty=0 for everyone first
        $pdo->exec("UPDATE pharmacies SET is_on_duty = 0");

        $count = 0;
        foreach ($data['result'] as $pharmacy) {
            $name = trim($pharmacy['name']);
            $dist = trim($pharmacy['dist']); 
            $address = trim($pharmacy['address']);
            $phone = trim($pharmacy['phone']);
            $loc = isset($pharmacy['loc']) ? explode(',', $pharmacy['loc']) : null;
            
            // Check for Kalkan
            $isKalkan = (stripos($dist, 'Kalkan') !== false) || (stripos($address, 'Kalkan') !== false);
            
            if ($isKalkan) {
                $lat = $loc ? trim($loc[0]) : null;
                $lng = $loc ? trim($loc[1]) : null;

                // Check DB
                $stmt = $pdo->prepare("SELECT id FROM pharmacies WHERE name = ?");
                $stmt->execute([$name]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $upd = $pdo->prepare("UPDATE pharmacies SET is_on_duty = 1, duty_date = CURDATE(), phone = ?, address = ?, latitude = COALESCE(?, latitude), longitude = COALESCE(?, longitude) WHERE id = ?");
                    $upd->execute([$phone, $address, $lat, $lng, $existing['id']]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO pharmacies (name, phone, address, latitude, longitude, is_on_duty, duty_date) VALUES (?, ?, ?, ?, ?, 1, CURDATE())");
                    $ins->execute([$name, $phone, $address, $lat, $lng]);
                }
                $count++;
            }
        }
        
        return ['success' => true, 'count' => $count];

    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
