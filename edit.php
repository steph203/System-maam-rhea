<?php
require_once 'header.php';
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'host' || !isset($_GET['quiz_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = (int)$_GET['quiz_id'];
$error = null;
$success = null;

// Fetch quiz details
try {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND host_id = ?");
    $stmt->execute([$quiz_id, $user_id]);
    $quiz = $stmt->fetch();
    if (!$quiz) {
        header('Location: host_dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Failed to fetch quiz: " . $e->getMessage();
    error_log("Fetch quiz failed: " . $e->getMessage());
}

// Initialize form variables
$title = $_POST['title'] ?? $quiz['title'];
$description = $_POST['description'] ?? $quiz['description'];
$category = $_POST['category'] ?? $quiz['category'];
$status = $_POST['status'] ?? $quiz['status'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    if (empty($title)) {
        $error = "Quiz title is required.";
    } elseif (!in_array($status, ['draft', 'published'])) {
        $error = "Invalid status.";
    }

    if (!isset($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, description = ?, category = ?, status = ? WHERE id = ? AND host_id = ?");
            $stmt->execute([$title, $description, $category, $status, $quiz_id, $user_id]);
            logHistory($pdo, $user_id, 'edit_quiz', $quiz_id, 'quiz', "Edited quiz ID $quiz_id: $title");
            $success = "Quiz updated successfully.";
            // Refresh quiz data
            $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND host_id = ?");
            $stmt->execute([$quiz_id, $user_id]);
            $quiz = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Failed to update quiz: " . $e->getMessage();
            error_log("Update quiz failed: " . $e->getMessage());
        }
    }
}
?>
<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4">Edit Quiz</h2>
        <?php if ($error): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <a href="host_dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 mb-4 inline-block">Back to Dashboard</a>
        <form method="POST" class="mt-4">
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">Quiz Title</label>
                <input type="text" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea class="mt-1 block w-full p-2 border border-gray-300 rounded-md" id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="mb-4">
                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                <input type="text" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>">
            </div>
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select class="mt-1 block w-full p-2 border border-gray-300 rounded-md" id="status" name="status" required>
                    <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo $status == 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Save Changes</button>
        </form>
    </div>
</div>
<?php require_once 'footer.php'; ?>