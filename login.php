<?php
require_once 'header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: ' . ($user['role'] == 'host' ? 'host_dashboard.php' : 'player_dashboard.php'));
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<div class="card" style="max-width: 400px; margin: 0 auto;">
    <h2 style="font-size: 1.5rem; margin-bottom: 20px;">🔑 Enter the Quest</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
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
        <div style="text-align: center;">
            <button type="submit" class="btn btn-success">🚀 Login</button>
        </div>
        <p style="text-align: center; font-size: 0.9rem; margin-top: 10px;">
            No account? <a href="register.php" style="color: #00ff85;">Register</a>
        </p>
    </form>
</div>
<?php require_once 'footer.php'; ?>