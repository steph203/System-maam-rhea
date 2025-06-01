<?php
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$filter = $_GET['filter'] ?? 'all';

try {
    $query = "SELECT action, target_id, target_type, description, created_at FROM completed WHERE user_id = ?";
    $params = [$user_id];
    if ($filter != 'all') {
        $query .= " AND action = ?";
        $params[] = $filter;
    }
    $query .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch history error: " . $e->getMessage());
    $error = 'Failed to fetch history.';
}
?>
<div class="card">
    <h2 style="font-size: 1.8rem; margin-bottom: 20px;">📜 Quest Log</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="form-group" style="margin-bottom: 20px;">
        <label>Filter Actions</label>
        <select onchange="window.location.href='completed.php?filter='+this.value;">
            <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All</option>
            <option value="create_quiz" <?php echo $filter == 'create_quiz' ? 'selected' : ''; ?>>Created Quizzes</option>
            <option value="edit_quiz" <?php echo $filter == 'edit_quiz' ? 'selected' : ''; ?>>Edited Quizzes</option>
            <option value="delete_quiz" <?php echo $filter == 'delete_quiz' ? 'selected' : ''; ?>>Deleted Quizzes</option>
            <option value="publish_quiz" <?php echo $filter == 'publish_quiz' ? 'selected' : ''; ?>>Published Quizzes</option>
            <option value="unpublish_quiz" <?php echo $filter == 'unpublish_quiz' ? 'selected' : ''; ?>>Unpublished Quizzes</option>
            <option value="create_question" <?php echo $filter == 'create_question' ? 'selected' : ''; ?>>Created Questions</option>
            <option value="start_quiz" <?php echo $filter == 'start_quiz' ? 'selected' : ''; ?>>Started Quizzes</option>
            <option value="complete_quiz" <?php echo $filter == 'complete_quiz' ? 'selected' : ''; ?>>Completed Quizzes</option>
            <option value="abandon_quiz" <?php echo $filter == 'abandon_quiz' ? 'selected' : ''; ?>>Abandoned Quizzes</option>
        </select>
    </div>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php if (empty($history)): ?>
            <p style="color: #f0f0f0;">No history recorded yet.</p>
        <?php else: ?>
            <?php foreach ($history as $entry): ?>
                <div style="background: #1c2526; padding: 15px; border: 2px solid #9b59b6; border-radius: 5px;">
                    <p style="font-size: 0.9rem; color: #9b59b6;"><?php echo htmlspecialchars($entry['created_at']); ?></p>
                    <p style="font-size: 1rem; color: #00ff85;"><?php echo htmlspecialchars($entry['description']); ?></p>
                    <p style="font-size: 0.8rem; color: #ff6b00;">Action: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $entry['action']))); ?> | Type: <?php echo htmlspecialchars(ucfirst($entry['target_type'])); ?> | ID: <?php echo htmlspecialchars($entry['target_id']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>