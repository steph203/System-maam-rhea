<?php
require_once 'header.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'player';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 3 || strlen($password) < 6) {
        $error = 'Username must be 3+ characters, password 6+ characters.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username already taken.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$username, $hashed_password, $role]);
                $success = 'Account created! Please log in.';
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<div class="card" style="max-width: 400px; margin: 0 auto;">
    <h2 style="font-size: 1.5rem; margin-bottom: 20px;">⭐ Join the Quest</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="player">Player</option>
                <option value="host">Host</option>
            </select>
        </div>
        <div style="text-align: center;">
            <button type="submit" class="btn btn-success">🚀 Register</button>
        </div>
        <p style="text-align: center; font-size: 0.9rem; margin-top: 10px;">
            Have an account? <a href="login.php" style="color: #00ff85;">Login</a>
        </p>
    </form>
</div>
<?php require_once 'footer.php'; ?>