<?php
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'host') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    
    if (empty($title)) {
        $error = 'Please enter a quest title.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO quizzes (title, status, created_by, created_at) VALUES (?, 'draft', ?, NOW())");
            $stmt->execute([$title, $user_id]);
            $quiz_id = $pdo->lastInsertId();
            logHistory($pdo, $user_id, 'create_quiz', $quiz_id, 'quiz', "Created quiz: $title");
            header('Location: create_question.php?quiz_id=' . $quiz_id);
            exit;
        } catch (PDOException $e) {
            error_log("Create quiz error: " . $e->getMessage());
            $error = 'Failed to create quest.';
        }
    }
}
?>
<div class="card">
    <h2 style="font-size: 1.5rem; margin-bottom: 20px;">⚡ Forge a New Quest</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Quest Title</label>
            <input type="text" name="title" placeholder="Enter epic quest name..." required>
        </div>
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button type="submit" class="btn btn-success">🚀 Create Quest</button>
            <a href="host_dashboard.php" class="btn btn-danger">❌ Cancel</a>
        </div>
    </form>
</div>
<?php require_once 'footer.php'; ?>