<?php
require_once 'header.php';
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'player') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = null;

try {
    $stmt = $pdo->prepare("
        SELECT q.id, q.title, q.category, q.status, COUNT(ques.id) as question_count
        FROM quizzes q
        LEFT JOIN questions ques ON q.id = ques.quiz_id
        WHERE q.status = 'published'
        GROUP BY q.id
        HAVING question_count > 0
        ORDER BY q.created_at DESC
    ");
    $stmt->execute();
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch quizzes: " . $e->getMessage();
    error_log("Fetch quizzes failed for player $user_id: " . $e->getMessage());
    $quizzes = [];
}

$history = $pdo->prepare("SELECT h.action, h.entity_id, h.entity_type, h.details, h.created_at 
                          FROM history h 
                          WHERE h.user_id = ? 
                          ORDER BY h.created_at DESC 
                          LIMIT 5");
$history->execute([$user_id]);
$history = $history->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="p-6 mx-auto bg-white ">
    <h3 class="text-xl font-semibold mt-6 mb-2">Recent Activity</h3>
    <ul class="space-y-2">
        <?php if (empty($history)): ?>
            <li class="bg-gray-50 p-3 rounded-md">No recent activity.</li>
        <?php else: ?>
            <?php foreach ($history as $entry): ?>
                <li class="bg-gray-50 p-3 rounded-md">
                    <?php echo htmlspecialchars($entry['details']); ?> at <?php echo $entry['created_at']; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>