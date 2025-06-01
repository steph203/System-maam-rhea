<?php
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'host' || !isset($_GET['quiz_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = (int)$_GET['quiz_id'];
$error = $success = '';

try {
    $stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ? AND created_by = ?");
    $stmt->execute([$quiz_id, $user_id]);
    $quiz = $stmt->fetch();
    if (!$quiz) {
        header('Location: host_dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch quiz error: " . $e->getMessage());
    $error = 'Failed to fetch quiz.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = trim($_POST['question_text'] ?? '');
    $type = $_POST['question_type'] ?? 'multiple_choice';
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $points = !empty($_POST['points']) ? (int)$_POST['points'] : null;
    $time_limit = !empty($_POST['time_limit']) ? (int)$_POST['time_limit'] : null;
    $options = $_POST['options'] ?? [];
    $correct_option = $_POST['correct_option'] ?? '';
    $true_false_answer = $_POST['true_false_answer'] ?? '';
    $identification_answer = trim($_POST['identification_answer'] ?? '');

    if (empty($question_text)) {
        $error = 'Please enter a question.';
    } elseif ($type === 'multiple_choice' && (count(array_filter($options)) < 2 || empty($correct_option))) {
        $error = 'Provide at least two options and select a correct one.';
    } elseif ($type === 'true_false' && !in_array($true_false_answer, ['True', 'False'])) {
        $error = 'Select True or False as the correct answer.';
    } elseif ($type === 'identification' && empty($identification_answer)) {
        $error = 'Provide the correct answer for the identification question.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, question_type, difficulty, points, time_limit, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$quiz_id, $question_text, $type, $difficulty, $points, $time_limit]);
            $question_id = $pdo->lastInsertId();

            if ($type === 'multiple_choice') {
                foreach ($options as $index => $option) {
                    if (!empty(trim($option))) {
                        $is_correct = ($index + 1) == $correct_option ? 1 : 0;
                        $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, trim($option), $is_correct]);
                    }
                }
            } elseif ($type === 'true_false') {
                $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, 'True', $true_false_answer === 'True' ? 1 : 0]);
                $stmt->execute([$question_id, 'False', $true_false_answer === 'False' ? 1 : 0]);
            } elseif ($type === 'identification') {
                $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, 1)");
                $stmt->execute([$question_id, $identification_answer]);
            }
            $pdo->commit();
            $success = 'Question added successfully!';
            logHistory($pdo, $user_id, 'create_question', $question_id, 'question', "Added question to quiz ID=$quiz_id");
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Create question error: " . $e->getMessage());
            $error = 'Failed to add question.';
        }
    }
}
?>
<div class="card">
    <h2 style="font-size: 1.5rem; margin-bottom: 20px;">🛠️ Craft Question for <?php echo htmlspecialchars($quiz['title']); ?></h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Question</label>
            <textarea name="question_text" rows="4" style="resize: vertical;" required><?php echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label>Type</label>
            <select name="question_type" id="questionType">
                <option value="multiple_choice" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'multiple_choice') ? 'selected' : ''; ?>>Multiple Choice</option>
                <option value="true_false" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'true_false') ? 'selected' : ''; ?>>True/False</option>
                <option value="identification" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'identification') ? 'selected' : ''; ?>>Identification</option>
            </select>
        </div>
        <div class="form-group">
            <label>Difficulty</label>
            <select name="difficulty">
                <option value="easy" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'easy') ? 'selected' : ''; ?>>Easy</option>
                <option value="medium" <?php echo (!isset($_POST['difficulty']) || $_POST['difficulty'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                <option value="hard" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'hard') ? 'selected' : ''; ?>>Hard</option>
            </select>
        </div>
        <div class="form-group">
            <label>Points (optional)</label>
            <input type="number" name="points" placeholder="Default: 10/20/30" value="<?php echo isset($_POST['points']) ? htmlspecialchars($_POST['points']) : ''; ?>">
        </div>
        <div class="form-group">
            <label>Time Limit (seconds, optional)</label>
            <input type="number" name="time_limit" placeholder="Default: 30/50/60" value="<?php echo isset($_POST['time_limit']) ? htmlspecialchars($_POST['time_limit']) : ''; ?>">
        </div>
        <div id="multipleChoiceOptions" class="form-group" style="display: <?php echo (!isset($_POST['question_type']) || $_POST['question_type'] === 'multiple_choice') ? 'block' : 'none'; ?>;">
            <label>Options (Multiple Choice)</label>
            <div id="optionsContainer">
                <?php
                $options = isset($_POST['options']) ? $_POST['options'] : ['', ''];
                $correct_option = isset($_POST['correct_option']) ? $_POST['correct_option'] : '';
                foreach ($options as $index => $option):
                ?>
                    <div class="option" style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="text" name="options[]" placeholder="Option <?php echo $index + 1; ?>" style="flex: 1; margin-right: 10px;" value="<?php echo htmlspecialchars($option); ?>">
                        <input type="radio" name="correct_option" value="<?php echo $index + 1; ?>" <?php echo ($correct_option == ($index + 1)) ? 'checked' : ''; ?> <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'multiple_choice') ? 'required' : ''; ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="addOption" class="btn btn-info" style="margin-bottom: 15px;">+ Add Option</button>
        </div>
        <div id="trueFalseOptions" class="form-group" style="display: <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'true_false') ? 'block' : 'none'; ?>;">
            <label>Correct Answer (True/False)</label>
            <div style="display: flex; gap: 20px;">
                <label>
                    <input type="radio" name="true_false_answer" value="True" <?php echo (isset($_POST['true_false_answer']) && $_POST['true_false_answer'] === 'True') ? 'checked' : ''; ?> required> True
                </label>
                <label>
                    <input type="radio" name="true_false_answer" value="False" <?php echo (isset($_POST['true_false_answer']) && $_POST['true_false_answer'] === 'False') ? 'checked' : ''; ?>> False
                </label>
            </div>
        </div>
        <div id="identificationOptions" class="form-group" style="display: <?php echo (isset($_POST['question_type']) && $_POST['question_type'] === 'identification') ? 'block' : 'none'; ?>;">
            <label>Correct Answer (Identification)</label>
            <input type="text" name="identification_answer" placeholder="Enter correct answer" value="<?php echo isset($_POST['identification_answer']) ? htmlspecialchars($_POST['identification_answer']) : ''; ?>" required>
        </div>
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button type="submit" class="btn btn-success">🚀 Add Question</button>
            <a href="host_dashboard.php" class="btn btn-danger">❌ Finish</a>
        </div>
    </form>
</div>
<script>
    const typeSelect = document.getElementById('questionType');
    const multipleChoiceOptions = document.getElementById('multipleChoiceOptions');
    const trueFalseOptions = document.getElementById('trueFalseOptions');
    const identificationOptions = document.getElementById('identificationOptions');
    const addOptionBtn = document.getElementById('addOption');
    const optionsContainer = document.getElementById('optionsContainer');

    function updateFormDisplay() {
        const type = typeSelect.value;
        multipleChoiceOptions.style.display = type === 'multiple_choice' ? 'block' : 'none';
        trueFalseOptions.style.display = type === 'true_false' ? 'block' : 'none';
        identificationOptions.style.display = type === 'identification' ? 'block' : 'none';
        const correctRadios = optionsContainer.querySelectorAll('input[name="correct_option"]');
        correctRadios.forEach(radio => radio.required = type === 'multiple_choice');
        const identificationInput = document.querySelector('input[name="identification_answer"]');
        if (identificationInput) identificationInput.required = type === 'identification';
    }

    typeSelect.addEventListener('change', updateFormDisplay);
    updateFormDisplay();

    addOptionBtn.addEventListener('click', () => {
        const index = optionsContainer.querySelectorAll('.option').length + 1;
        const div = document.createElement('div');
        div.className = 'option';
        div.style.cssText = 'display: flex; align-items: center; margin-bottom: 10px;';
        div.innerHTML = `
            <input type="text" name="options[]" placeholder="Option ${index}" style="flex: 1; margin-right: 10px;">
            <input type="radio" name="correct_option" value="${index}">
        `;
        optionsContainer.appendChild(div);
    });
</script>
<?php require_once 'footer.php'; ?>