<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$logFile = __DIR__ . '/step_log.txt';
file_put_contents($logFile, "START\n");

function logStep($msg) {
    global $logFile;
    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
}

try {
    logStep("Step 1: Including DB");
    require_once '../includes/db.php';
    logStep("Step 2: DB Included");
    
    $user_id = 1; 
    $partner_id = 6;
    $after_id = 0;
    
    logStep("Step 3: Running UPDATE");
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")->execute([$partner_id, $user_id]);
    logStep("Step 4: UPDATE Finished");

    logStep("Step 5: Running SELECT");
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
    $stmt->execute([$user_id, $user_id, $partner_id, $partner_id, $user_id, $after_id]);
    logStep("Step 6: SELECT Finished");
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logStep("Step 7: Fetched " . count($messages) . " messages");
    
} catch (Exception $e) {
    logStep("EXCEPTION: " . $e->getMessage());
} catch (Throwable $t) {
    logStep("FATAL: " . $t->getMessage());
}
?>
