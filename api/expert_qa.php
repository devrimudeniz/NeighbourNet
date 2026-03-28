<?php
require_once '../includes/db.php';
require_once '../includes/lang.php';
require_once '../includes/security_helper.php';
require_once '../includes/RateLimiter.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// 1. Ask a Question
if ($action === 'ask' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    if (!RateLimiter::check($pdo, 'expert_qa', 5, 60)) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests. Please try again later.']);
        exit;
    }
    
    $expert_id = (int)$_POST['expert_id'];
    $question = trim($_POST['question'] ?? '');
    
    // Check if asking self
    if ($expert_id === $user_id) {
        echo json_encode(['error' => 'You cannot ask yourself a question.']);
        exit;
    }

    // Validation
    if (empty($question) || strlen($question) < 5) {
         echo json_encode(['error' => 'Question is too short.']);
         exit;
    }
    
    // Validate Expert exists and is an expert
    // (Assuming user_badges check or similar, for speed we just check user exists)
    // Ideally: SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_type = 'expert'

    try {
        $stmt = $pdo->prepare("INSERT INTO expert_questions (expert_id, user_id, question) VALUES (?, ?, ?)");
        $stmt->execute([$expert_id, $user_id, $question]);

        // Notify Expert
        $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, content, is_read, created_at) VALUES (?, ?, 'expert_question', 'Someone asked you a question!', 0, NOW())")->execute([$expert_id, $user_id]);

        // Send Email Notification
        require_once '../includes/email-helper.php';
        
        // Fetch Expert Email & Details
        $expert_stmt = $pdo->prepare("SELECT email, full_name, username FROM users WHERE id = ?");
        $expert_stmt->execute([$expert_id]);
        $expert = $expert_stmt->fetch();
        if ($expert && !empty($expert['email'])) {
            $asker_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $asker_stmt->execute([$user_id]);
            $asker = $asker_stmt->fetch();
            $asker_name = $asker['full_name'] ?? 'A user';
            
            $subject = "New Question from $asker_name | Kalkan Social Expert";
            $message = "
            <html>
            <body style='font-family: sans-serif; color: #333;'>
                <h2>Hello {$expert['full_name']},</h2>
                <p><strong>$asker_name</strong> has asked you a question on your expert profile:</p>
                <div style='background: #f3f4f6; padding: 15px; border-left: 4px solid #7c3aed; margin: 20px 0; font-style: italic;'>
                    \"" . htmlspecialchars($question) . "\"
                </div>
                <p>Click below to answer it and help the community:</p>
                <a href='https://kalkansocial.com/profile?username={$expert['username']}&tab=qa' style='background: #7c3aed; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Answer Question</a>
                <p style='color: #888; font-size: 12px; margin-top: 30px;'>Kalkan Social Verified Expert Program</p>
            </body>
            </html>";
            
            sendEmail($expert['email'], $subject, $message);
        }

        echo json_encode(['success' => true, 'message' => 'Question sent to expert!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
} 

// 2. Fetch Q&A for Profile
elseif ($action === 'fetch' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $expert_id = (int)$_GET['expert_id'];
    $is_owner = ($user_id == $expert_id);
    
    // If owner, show ALL questions (answered and unanswered)
    // If visitor, show only answered public questions
    if ($is_owner) {
        $sql = "
            SELECT 
                q.id, q.question, q.answer, q.answered_at, q.created_at, q.is_public,
                u.full_name as asker_name, u.avatar as asker_avatar, u.username as asker_username
            FROM expert_questions q
            JOIN users u ON q.user_id = u.id
            WHERE q.expert_id = ?
            ORDER BY 
                CASE WHEN q.answer IS NULL THEN 0 ELSE 1 END,
                q.created_at DESC
            LIMIT 50
        ";
    } else {
        $sql = "
            SELECT 
                q.id, q.question, q.answer, q.answered_at, q.created_at,
                u.full_name as asker_name, u.avatar as asker_avatar, u.username as asker_username
            FROM expert_questions q
            JOIN users u ON q.user_id = u.id
            WHERE q.expert_id = ? 
            AND q.answer IS NOT NULL 
            AND q.is_public = 1
            ORDER BY q.answered_at DESC
            LIMIT 20
        ";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$expert_id]);
    $qa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $qa_list, 'is_owner' => $is_owner]);
}

// 3. Answer a Question
elseif ($action === 'answer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $question_id = (int)$_POST['question_id'];
    $answer = trim($_POST['answer'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    if (empty($answer) || strlen($answer) < 10) {
        echo json_encode(['error' => 'Answer is too short (min 10 characters)']);
        exit;
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT expert_id, user_id FROM expert_questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();
    
    if (!$question || $question['expert_id'] != $user_id) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE expert_questions SET answer = ?, answered_at = NOW(), is_public = ? WHERE id = ?");
        $stmt->execute([$answer, $is_public, $question_id]);
        
        // Notify the asker
        $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, content, is_read, created_at) VALUES (?, ?, 'expert_answer', 'Your question was answered by an expert!', 0, NOW())")->execute([$question['user_id'], $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Answer posted!']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'An error occurred']);
    }
}

// 4. Delete a Question
elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $question_id = (int)$_POST['question_id'];
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT expert_id FROM expert_questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();
    
    if (!$question || $question['expert_id'] != $user_id) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM expert_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        
        echo json_encode(['success' => true, 'message' => 'Question deleted']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
}

else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
