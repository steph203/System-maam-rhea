<?php
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'player') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';

try {
    $stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE status = 'published'");
    $stmt->execute();
    $quizzes = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT xp FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Fetch data error: " . $e->getMessage());
    $error = 'Failed to fetch data.';
}
?>
<div class="card">
    <h2 style="font-size: 1.8rem; margin-bottom: 20px;">🎯 Adventurer’s Hub</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div style="margin-bottom: 20px;">
        <p style="font-size: 1.2rem; color: #00ff85;">Quest Points: <?php echo $user['xp'] ?? 0; ?> XP</p>
    </div>
    <h3 style="font-size: 1.3rem; margin-bottom: 15px;">Available Quests</h3>
    <div class="grid">
        <?php if (empty($quizzes)): ?>
            <p style="color: #f0f0f0;">No quests available.</p>
        <?php else: ?>
            <?php foreach ($quizzes as $quiz): ?>
                <div class="card">
                    <h4 style="font-size: 1.2rem; color: #00ff85;"><?php echo htmlspecialchars($quiz['title']); ?></h4>
                    <a href="play_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-success" style="margin-top: 10px;">🚀 Embark</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>