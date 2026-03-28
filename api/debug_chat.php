<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting Debug...\n";

try {
    echo "Including DB...\n";
    require_once '../includes/db.php';
    echo "DB Included.\n";
    
    if(!isset($pdo)) {
        die("PDO is missing after include.\n");
    }

    $user_id = 2; // Test user
    $partner_id = 6; // Test partner from user request
    $after_id = 0;
    
    echo "Executing SQL for User $user_id, Partner $partner_id...\n";

    $sql = "
        SELECT m.*, ml.title as listing_title, ml.image_url as listing_image,
        (SELECT reaction_type FROM message_reactions WHERE message_id = m.id AND user_id = ?) as my_reaction,
        (SELECT GROUP_CONCAT(reaction_type) FROM message_reactions WHERE message_id = m.id) as all_reactions
        FROM messages m 
        LEFT JOIN marketplace_listings ml ON m.listing_id = ml.id
        WHERE (
            (sender_id = ? AND receiver_id = ?) OR 
            (sender_id = ? AND receiver_id = ?)
        ) 
        AND m.id > ?
        ORDER BY created_at ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    if(!$stmt) {
        $ei = $pdo->errorInfo();
        die("Prepare failed: " . print_r($ei, true));
    }

    $params = [$user_id, $user_id, $partner_id, $partner_id, $user_id, $after_id];
    echo "Params: " . json_encode($params) . "\n";
    
    $res = $stmt->execute($params);
    
    if(!$res) {
        $ei = $stmt->errorInfo();
        die("Execute failed: " . print_r($ei, true));
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Query Success. Count: " . count($messages) . "\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} catch (Throwable $t) {
    echo "FATAL ERROR: " . $t->getMessage() . "\n";
    echo $t->getTraceAsString();
}
?>
