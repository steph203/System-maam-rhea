<?php
require_once 'header.php';

$error = '';

try {
    $stmt = $pdo->query("SELECT u.username, u.xp, COALESCE(SUM(s.score), 0) as total_score, COALESCE(SUM(s.difficulty_points), 0) as total_difficulty_points
                         FROM users u
                         LEFT JOIN scores s ON u.id = s.user_id
                         WHERE u.role = 'player'
                         GROUP BY u.id
                         ORDER BY total_score DESC, total_difficulty_points DESC, u.xp DESC
                         LIMIT 10");
    $leaderboard = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch leaderboard error: " . $e->getMessage());
    $error = 'Failed to fetch leaderboard.';
}
?>
<div class="card">
    <h2 style="font-size: 1.8rem; margin-bottom: 20px;">🏆 Hall of Legends</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php if (empty($leaderboard)): ?>
            <p style="color: #f0f0f0;">No players ranked yet.</p>
        <?php else: ?>
            <?php foreach ($leaderboard as $index => $entry): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; background: #1c2526; padding: 15px; border: 2px solid #9b59b6; border-radius: 5px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="font-size: 1.5rem; color: #ff6b00;"><?php echo $index + 1; ?>.</span>
                        <span style="font-size: 1.2rem; color: #00ff85;"><?php echo htmlspecialchars($entry['username']); ?></span>
                    </div>
                    <div style="text-align: right;">
                        <p style="font-size: 0.9rem; color: #00ff85;">Score: <?php echo $entry['total_score']; ?> XP</p>
                        <p style="font-size: 0.9rem; color: #9b59b6;">Difficulty: <?php echo $entry['total_difficulty_points']; ?> XP</p>
                        <p style="font-size: 0.9rem; color: #ff6b00;">Quest Points: <?php echo $entry['xp']; ?> XP</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>