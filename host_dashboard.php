<?php
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'host') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = $success = '';

try {
    $stmt = $pdo->prepare("SELECT id, title, status, created_at FROM quizzes WHERE created_by = ?");
    $stmt->execute([$user_id]);
    $quizzes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch quizzes error: " . $e->getMessage());
    $error = 'Failed to fetch quests.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['quiz_id'])) {
        $quiz_id = (int)$_POST['quiz_id'];
        $action = $_POST['action'];

        try {
            if ($action == 'publish' || $action == 'unpublish') {
                $new_status = $action == 'publish' ? 'published' : 'draft';
                $stmt = $pdo->prepare("UPDATE quizzes SET status = ? WHERE id = ? AND created_by = ?");
                $stmt->execute([$new_status, $quiz_id, $user_id]);
                logHistory($pdo, $user_id, $new_status == 'published' ? 'publish_quiz' : 'unpublish_quiz', $quiz_id, 'quiz', "$new_status quiz ID=$quiz_id");
                $success = 'Quiz status updated!';
            } elseif ($action == 'delete') {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ?");
                $stmt->execute([$quiz_id]);
                $question_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if ($question_ids) {
                    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
                    $stmt = $pdo->prepare("DELETE FROM options WHERE question_id IN ($placeholders)");
                    $stmt->execute($question_ids);
                    $stmt = $pdo->prepare("DELETE FROM user_answers WHERE question_id IN ($placeholders)");
                    $stmt->execute($question_ids);
                    $stmt = $pdo->prepare("DELETE FROM questions WHERE id IN ($placeholders)");
                    $stmt->execute($question_ids);
                }

                $stmt = $pdo->prepare("DELETE FROM scores WHERE quiz_id = ?");
                $stmt->execute([$quiz_id]);
                $stmt = $pdo->prepare("DELETE FROM completed WHERE target_id = ? AND target_type = 'quiz'");
                $stmt->execute([$quiz_id]);
                $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ? AND created_by = ?");
                $stmt->execute([$quiz_id, $user_id]);

                $pdo->commit();
                logHistory($pdo, $user_id, 'delete_quiz', $quiz_id, 'quiz', "Deleted quiz ID=$quiz_id");
                $success = 'Quiz deleted successfully!';
                header('Location: host_dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Quiz action error: " . $e->getMessage());
            $error = 'Failed to perform action.';
        }
    }
}
?>
<div class="card">
    <h2 style="font-size: 1.8rem; margin-bottom: 20px;">🏠 Host Command Center</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <div style="margin-bottom: 20px;">
        <a href="create_quiz.php" class="btn btn-success">⚡ Forge New Quest</a>
    </div>
    <div class="grid">
        <?php if (empty($quizzes)): ?>
            <p style="color: #f0f0f0;">No quests created yet.</p>
        <?php else: ?>
            <?php foreach ($quizzes as $quiz): ?>
                <div class="card">
                    <h3 style="font-size: 1.2rem; color: #00ff85;"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                    <p style="font-size: 0.9rem; color: #9b59b6;">Status: <?php echo ucfirst($quiz['status']); ?></p>
                    <p style="font-size: 0.9rem; color: #9b59b6;">Created: <?php echo $quiz['created_at']; ?></p>
                    <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                        <a href="create_question.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-info">🛠️ Add Questions</a>
                        <a href="edit_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-success">✏️ Edit</a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this quiz?');">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger">🗑️ Delete</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                            <input type="hidden" name="action" value="<?php echo $quiz['status'] == 'published' ? 'unpublish' : 'publish'; ?>">
                            <button type="submit" class="btn btn-success"><?php echo $quiz['status'] == 'published' ? '🔒 Unpublish' : '🚀 Publish'; ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>