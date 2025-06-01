<?php
session_start();
ob_start();

try {
    $pdo = new PDO('mysql:host=localhost;dbname=quiz_game;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

function logHistory($pdo, $user_id, $action, $target_id, $target_type, $description) {
    try {
        $stmt = $pdo->prepare("INSERT INTO completed (user_id, action, target_id, target_type, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $target_id, $target_type, $description]);
    } catch (PDOException $e) {
        error_log("History logging failed: " . $e->getMessage());
    }
}
?>