<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trivia Quest</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <div class="nav-container">
            <div class="nav-logo">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAJBJREFUWEftl0EOgCAMRLk9/f83L2EC5iWjE0wYxsaPIT8JIYQz3fdRkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiRJkiT5L4kZJq9qUpoAAAAASUVORK5CYII=" alt="Logo">
                <h1>TRIVIA QUEST</h1>
            </div>
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span style="font-family: 'Press Start 2P'; color: #00ff85; margin-right: 15px;">PLAYER: <?php echo strtoupper(htmlspecialchars($_SESSION['username'] ?? 'USER')); ?></span>
                    <?php if ($_SESSION['role'] == 'host'): ?>
                        <a href="host_dashboard.php" class="btn btn-success">🏠 Host HQ</a>
                        <a href="create_quiz.php" class="btn btn-info">⚡ Create</a>
                    <?php else: ?>
                        <a href="player_dashboard.php" class="btn btn-success">🎯 Dashboard</a>
                    <?php endif; ?>
                    <a href="leaderboards.php" class="btn btn-info">🏆 Ranks</a>
                    <a href="completed.php" class="btn btn-info">📜 History</a>
                    <a href="about.php" class="btn btn-info">ℹ️ About</a>
                    <a href="logout.php" class="btn btn-danger">⚡ Logout</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-success">🏠 Home</a>
                    <a href="login.php" class="btn btn-success">🔑 Login</a>
                    <a href="register.php" class="btn btn-info">⭐ Register</a>
                    <a href="about.php" class="btn btn-info">ℹ️ About</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main>