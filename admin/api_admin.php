<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['badge'], ['founder', 'moderator'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'update_event_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB Error']);
        }
        exit;

    } elseif ($action === 'delete_event') {
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB Error']);
        }
        exit;
    }

    if ($action === 'approve') {
        $request_id = $_POST['request_id'] ?? 0;
        $request_type = $_POST['request_type'] ?? 'business';
        
        // Get user_id from request
        $stmt = $pdo->prepare("SELECT user_id FROM verification_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $user_id = $stmt->fetchColumn();

        if ($user_id) {
            $pdo->beginTransaction();
            
            // 1. Update Request
            $stmt = $pdo->prepare("UPDATE verification_requests SET status = 'approved' WHERE id = ?");
            $stmt->execute([$request_id]);
            
            // 2. Update User Badge based on request type
            $badge = 'verified_business';
            if ($request_type === 'captain') {
                $badge = 'captain';
            } elseif ($request_type === 'real_estate') {
                $badge = 'real_estate';
            }
            
            $stmt = $pdo->prepare("UPDATE users SET badge = ? WHERE id = ? AND (badge IS NULL OR badge NOT IN ('founder', 'moderator'))");
            $stmt->execute([$badge, $user_id]);
            
            // 3. Log Action
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type, details) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$admin_id, 'Approved Verification', $user_id, 'user', "User verified as $request_type via request ID: $request_id"]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'debug' => 'Badge updated to ' . $badge . ' for user ' . $user_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Request not found']);
        }
    }

    elseif ($action === 'reject') {
        $request_id = $_POST['request_id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE verification_requests SET status = 'rejected' WHERE id = ?");
        if ($stmt->execute([$request_id])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        }
    }

    elseif ($action === 'ban_user') {
        $target_user_id = $_POST['user_id'] ?? 0;
        
        // Cannot ban other admins/founders
        $stmt = $pdo->prepare("SELECT badge FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target_badge = $stmt->fetchColumn();
        
        if (in_array($target_badge, ['founder', 'moderator'])) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot ban other administrators.']);
            exit();
        }

        $stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
        if ($stmt->execute([$target_user_id])) {
            // Log Action
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, 'Banned User', $target_user_id, 'user']);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ban failed']);
        }
    }

    elseif ($action === 'unban_user') {
        $target_user_id = $_POST['user_id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        if ($stmt->execute([$target_user_id])) {
            // Log Action
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, 'Unbanned User', $target_user_id, 'user']);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Unban failed']);
        }
    }

    elseif ($action === 'delete_post') {
        $post_id = $_POST['post_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        if ($stmt->execute([$post_id])) {
            // Log Action
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, 'Deleted Post', $post_id, 'post']);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
        }
    }

    elseif ($action === 'change_badge') {
        $target_user_id = $_POST['user_id'] ?? 0;
        $new_badge = $_POST['new_badge'] ?? 'user';
        $expert_badges = $_POST['expert_badges'] ?? [];

        // Security check (simplified)
        if (!isset($_SESSION['user_id'])) exit(json_encode(['status' => 'error']));

        $pdo->beginTransaction();

        try {
            // 1. Update Primary Badge
            $stmt = $pdo->prepare("UPDATE users SET badge = ? WHERE id = ?");
            $stmt->execute([$new_badge, $target_user_id]);

            // 2. Update Expert Badges
            // First, remove existing manual badges (keep automated ones if logic dictates, but for now full reset manual ones)
            // Ideally we should discriminate between manual and automated, but for this feature we treat them as manual overrides
            $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?");
            $stmt->execute([$target_user_id]);

            if (!empty($expert_badges)) {
                $insert_stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_type) VALUES (?, ?)");
                foreach ($expert_badges as $eb) {
                    if (!$insert_stmt->execute([$target_user_id, $eb])) {
                        throw new Exception("Expert badge insert failed: " . implode(" ", $insert_stmt->errorInfo()));
                    }
                }
            }

            // 3. Recalculate Trust Score
            require_once '../includes/trust_score_helper.php';
            calculateTrustScore($target_user_id);

            // 4. Log
            $details = "Badge: $new_badge";
            if (!empty($expert_badges)) $details .= ", Expert: " . implode(',', $expert_badges);
            
            $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type, details) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->execute([$admin_id, 'Changed Badge', $target_user_id, 'user', $details]);

            $pdo->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    elseif ($action === 'manage_trip') {
        $trip_id = $_POST['trip_id'] ?? 0;
        $sub_action = $_POST['sub_action'] ?? '';

        if ($sub_action === 'approve') {
            $stmt = $pdo->prepare("UPDATE boat_trips SET status = 'approved', is_active = 1 WHERE id = ?");
            $log_action = 'Approved Trip';
        } elseif ($sub_action === 'reject') {
            $stmt = $pdo->prepare("UPDATE boat_trips SET status = 'rejected', is_active = 0 WHERE id = ?");
            $log_action = 'Rejected Trip';
        } elseif ($sub_action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM boat_trips WHERE id = ?");
            $log_action = 'Deleted Trip';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            exit();
        }

        if ($stmt->execute([$trip_id])) {
            // Log Action
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, $log_action, $trip_id, 'boat_trip']);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Action failed']);
        }
    }

    elseif ($action === 'add_cat') {
        require_once '../includes/optimize_upload.php';

        $name = $_POST['name'] ?? '';
        $location = $_POST['location'] ?? '';
        $rarity = $_POST['rarity'] ?? 'common';
        $desc = $_POST['description'] ?? '';
        $likes = $_POST['likes'] ?? '';
        $dislikes = $_POST['dislikes'] ?? '';

        $master_photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $upload_dir = '../uploads/cats/';
            $res = gorselOptimizeEt($_FILES['photo'], $upload_dir, 90);
            if(isset($res['success'])) {
                $master_photo = 'uploads/cats/' . $res['filename'];
            } else {
                echo json_encode(['status' => 'error', 'message' => $res['error']]);
                exit;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO cats (name, location, description, rarity, master_photo, likes, dislikes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $location, $desc, $rarity, $master_photo, $likes, $dislikes])) {
            $last_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type, details) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$admin_id, 'Added Cat', $last_id, 'cat', "Added cat: $name"]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DB Insert Failed']);
        }
    }

    elseif ($action === 'edit_cat') {
        require_once '../includes/optimize_upload.php';

        $cat_id = $_POST['cat_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $location = $_POST['location'] ?? '';
        $rarity = $_POST['rarity'] ?? 'common';
        $desc = $_POST['description'] ?? '';
        $likes = $_POST['likes'] ?? '';
        $dislikes = $_POST['dislikes'] ?? '';

        // Handle Photo
        $master_photo_sql = "";
        $params = [$name, $location, $desc, $rarity, $likes, $dislikes, $cat_id];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $upload_dir = '../uploads/cats/';
            $res = gorselOptimizeEt($_FILES['photo'], $upload_dir, 90);
            if(isset($res['success'])) {
                $master_photo_sql = ", master_photo = ?";
                // Insert photo param before cat_id (which is last)
                array_splice($params, 6, 0, 'uploads/cats/' . $res['filename']); 
            } else {
                echo json_encode(['status' => 'error', 'message' => $res['error']]);
                exit;
            }
        }

        $sql = "UPDATE cats SET name=?, location=?, description=?, rarity=?, likes=?, dislikes=? $master_photo_sql WHERE id=?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
             $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type, details) VALUES (?, ?, ?, ?, ?)");
             $stmt->execute([$admin_id, 'Edited Cat', $cat_id, 'cat', "Updated cat: $name"]);
             echo json_encode(['status' => 'success']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Update Failed']);
        }
    }

    elseif ($action === 'delete_cat') {
        $cat_id = $_POST['cat_id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM cats WHERE id = ?");
        if ($stmt->execute([$cat_id])) {
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, 'Deleted Cat', $cat_id, 'cat']);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete Failed']);
        }
    }

    elseif ($action === 'handle_cat_photo') {
        $coll_id = $_POST['collection_id'] ?? 0;
        $decision = $_POST['decision'] ?? '';

        if ($decision === 'approve') {
            $stmt = $pdo->prepare("UPDATE user_cat_collection SET status = 'approved' WHERE id = ?");
            $log_desc = 'Approved Cat Photo';
        } elseif ($decision === 'reject') {
            $stmt = $pdo->prepare("UPDATE user_cat_collection SET status = 'rejected' WHERE id = ?");
            $log_desc = 'Rejected Cat Photo';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid decision']);
            exit;
        }

        if ($stmt->execute([$coll_id])) {
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_id, target_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, $log_desc, $coll_id, 'cat_collection']);
            echo json_encode(['status' => 'success']);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'DB Error']);
        }
    }

    elseif ($action === 'get_badges') {
        $target_user_id = $_GET['user_id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT badge_type FROM user_badges WHERE user_id = ?");
        $stmt->execute([$target_user_id]);
        $badges = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['status' => 'success', 'badges' => $badges]);
    }

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
