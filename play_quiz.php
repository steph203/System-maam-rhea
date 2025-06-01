<?php
require_once 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'player' || !isset($_GET['quiz_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = (int)$_GET['quiz_id'];
$error = $feedback = '';
$question_index = $_SESSION['question_index'] ?? 0;
$score = $_SESSION['score'] ?? 0;
$difficulty_points = $_SESSION['difficulty_points'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE id = ? AND status = 'published'");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    if (!$quiz) {
        header('Location: player_dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Fetch quiz error: " . $e->getMessage());
    $error = 'Failed to fetch quiz.';
}

if (!$error) {
    try {
        $stmt = $pdo->prepare("SELECT id, question_text, question_type, difficulty, points, time_limit FROM questions WHERE quiz_id = ? ORDER BY RAND()");
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll();
        if (empty($questions)) {
            $error = 'No questions available. Contact the quest master.';
        }
    } catch (PDOException $e) {
        error_log("Fetch questions error: " . $e->getMessage());
        $error = 'Database error.';
    }
}

if (!$error && !isset($_SESSION['question_index'])) {
    $_SESSION['question_index'] = 0;
    $_SESSION['score'] = 0;
    $_SESSION['difficulty_points'] = 0;
    $_SESSION['start_time'] = time();
    logHistory($pdo, $user_id, 'start_quiz', $quiz_id, 'quiz', "Started quiz ID=$quiz_id");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    if (isset($_POST['quit'])) {
        if ($question_index > 0) {
            $time_taken = time() - $_SESSION['start_time'];
            try {
                $stmt = $pdo->prepare("INSERT INTO scores (user_id, quiz_id, score, difficulty_points, time_taken, attempts, status, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $quiz_id, $score, $difficulty_points, $time_taken, 1, 'abandoned']);
                logHistory($pdo, $user_id, 'abandon_quiz', $quiz_id, 'quiz', "Abandoned quiz ID=$quiz_id with score $score");
            } catch (PDOException $e) {
                error_log("Save score error: " . $e->getMessage());
                $error = 'Failed to save score.';
            }
        }
        unset($_SESSION['question_index'], $_SESSION['score'], $_SESSION['difficulty_points'], $_SESSION['start_time']);
        header('Location: player_dashboard.php');
        exit;
    }

    if (!isset($_POST['answer']) || empty(trim($_POST['answer']))) {
        $error = 'Please select or enter an answer.';
    } else {
        $answer = trim($_POST['answer']);
        $current_question = $questions[$question_index];
        $question_id = $current_question['id'];
        $difficulty = $current_question['difficulty'];
        $points = $current_question['points'] ?? ($difficulty == 'easy' ? 10 : ($difficulty == 'medium' ? 20 : 30));

        try {
            $stmt = $pdo->prepare("SELECT option_text FROM options WHERE question_id = ? AND is_correct = 1");
            $stmt->execute([$question_id]);
            $correct_option = $stmt->fetch();
            $correct_answer = $correct_option ? $correct_option['option_text'] : '';
        } catch (PDOException $e) {
            error_log("Fetch answer error: " . $e->getMessage());
            $error = 'Failed to fetch answer.';
        }

        $is_correct = (strtolower($answer) == strtolower($correct_answer));
        $points_earned = $is_correct ? $points : 0;

        try {
            $stmt = $pdo->prepare("INSERT INTO user_answers (user_id, quiz_id, question_id, selected_answer, is_correct, points_earned, answered_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $quiz_id, $question_id, $answer, $is_correct ? 1 : 0, $points_earned]);
        } catch (PDOException $e) {
            error_log("Save answer error: " . $e->getMessage());
        }

        if ($is_correct) {
            $_SESSION['score'] += $points_earned;
            $_SESSION['difficulty_points'] += $points_earned;
            $score = $_SESSION['score'];
            $difficulty_points = $_SESSION['difficulty_points'];
            $xp = ($difficulty == 'easy' ? 50 : ($difficulty == 'medium' ? 100 : 150));
            try {
                $stmt = $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
                $stmt->execute([$xp, $user_id]);
            } catch (PDOException $e) {
                error_log("Update XP error: " . $e->getMessage());
            }
            $feedback = "<span style='color: #00ff85;'>Correct!</span> Earned $points_earned XP and $xp Quest Points!";
        } else {
            $feedback = "<span style='color: #ff6b00;'>Incorrect.</span> Correct answer: " . htmlspecialchars($correct_answer);
        }

        $question_index++;
        $_SESSION['question_index'] = $question_index;

        if ($question_index >= count($questions)) {
            $time_taken = time() - $_SESSION['start_time'];
            try {
                $stmt = $pdo->prepare("INSERT INTO scores (user_id, quiz_id, score, difficulty_points, time_taken, attempts, status, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $quiz_id, $score, $difficulty_points, $time_taken, 1, 'completed']);
                logHistory($pdo, $user_id, 'complete_quiz', $quiz_id, 'quiz', "Completed quiz ID=$quiz_id with score $score");
            } catch (PDOException $e) {
                error_log("Save score error: " . $e->getMessage());
                $error = 'Failed to save score.';
            }
            $final_score = $score;
            unset($_SESSION['question_index'], $_SESSION['score'], $_SESSION['difficulty_points'], $_SESSION['start_time']);
        }
    }
}
?>
<div class="card">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <a href="player_dashboard.php" class="btn btn-success">🏠 Return to Base</a>
    <?php elseif (isset($final_score)): ?>
        <div class="card" style="background: linear-gradient(to right, #00ff85, #9b59b6); color: #0a0f2d; text-align: center;">
            <h2 style="font-size: 1.8rem; margin-bottom: 15px;">🏆 Quest Complete!</h2>
            <p style="font-size: 1.2rem;">Score: <?php echo $final_score; ?> XP</p>
            <p style="font-size: 1.2rem;">Difficulty Points: <?php echo $difficulty_points; ?> XP</p>
            <a href="player_dashboard.php" class="btn btn-success" style="margin-top: 15px;">🏠 Return to Base</a>
        </div>
    <?php else: ?>
        <?php
        $current_question = $questions[$question_index];
        $question_id = $current_question['id'];
        $difficulty = $current_question['difficulty'];
        $time_limit = $current_question['time_limit'] ?? ($difficulty == 'easy' ? 30 : ($difficulty == 'medium' ? 50 : 60));
        $points = $current_question['points'] ?? ($difficulty == 'easy' ? 10 : ($difficulty == 'medium' ? 20 : 30));

        try {
            $stmt = $pdo->prepare("SELECT id, option_text FROM options WHERE question_id = ?");
            $stmt->execute([$question_id]);
            $options = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Fetch options error: " . $e->getMessage());
            $error = 'Failed to fetch options.';
        }
        ?>
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; background: #1c2526; padding: 10px; border: 2px solid #00ff85; border-radius: 5px;">
                <div style="font-size: 1.2rem; color: #00ff85;">⚡ Score: <?php echo $score; ?> XP</div>
                <div style="font-size: 0.9rem;">Level <?php echo $question_index + 1; ?>/<?php echo count($questions); ?></div>
                <div style="font-size: 1.5rem; color: #ff6b00;" id="timer"><?php echo $time_limit; ?>s</div>
            </div>
            <div class="progress-bar" style="margin-top: 10px;">
                <div class="progress-bar-fill" style="width: <?php echo ($question_index / count($questions)) * 100; ?>%;"></div>
            </div>
        </div>
        <h3 style="font-size: 1.3rem; margin-bottom: 15px;"><?php echo htmlspecialchars($current_question['question_text']); ?></h3>
        <p style="font-size: 0.9rem; color: #9b59b6; margin-bottom: 15px;">Difficulty: <?php echo ucfirst($difficulty); ?> | Reward: <?php echo $points; ?> XP</p>
        <?php if ($feedback): ?>
            <div style="background: #1c2526; padding: 10px; border: 2px solid #9b59b6; border-radius: 5px; margin-bottom: 15px;"><?php echo $feedback; ?></div>
        <?php endif; ?>
        <form method="POST" id="quizForm">
            <?php if ($current_question['question_type'] == 'multiple_choice' && !empty($options)): ?>
                <?php foreach ($options as $option): ?>
                    <label style="display: block; background: #0a0f2d; padding: 10px; margin-bottom: 10px; border: 2px solid #00ff85; border-radius: 5px; cursor: pointer;">
                        <input type="radio" name="answer" value="<?php echo htmlspecialchars($option['option_text']); ?>" required style="margin-right: 10px;">
                        <?php echo htmlspecialchars($option['option_text']); ?>
                    </label>
                <?php endforeach; ?>
            <?php elseif ($current_question['question_type'] == 'true_false'): ?>
                <label style="display: block; background: #0a0f2d; padding: 10px; margin-bottom: 10px; border: 2px solid #00ff85; border-radius: 5px; cursor: pointer;">
                    <input type="radio" name="answer" value="True" required style="margin-right: 10px;"> ✅ True
                </label>
                <label style="display: block; background: #0a0f2d; padding: 10px; margin-bottom: 10px; border: 2px solid #00ff85; border-radius: 5px; cursor: pointer;">
                    <input type="radio" name="answer" value="False" required style="margin-right: 10px;"> ❌ False
                </label>
            <?php else: ?>
                <input type="text" name="answer" required style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <?php endif; ?>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button type="submit" class="btn btn-success">🚀 Submit</button>
                <button type="submit" name="quit" class="btn btn-danger">❌ Abandon Quest</button>
            </div>
        </form>
        <script>
            let timeLeft = <?php echo $time_limit; ?>;
            const timerElement = document.getElementById('timer');
            const quizForm = document.getElementById('quizForm');
            const interval = setInterval(() => {
                timeLeft--;
                timerElement.textContent = `${timeLeft}s`;
                if (timeLeft <= 5) timerElement.style.color = '#ff6b00';
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    quizForm.submit();
                }
            }, 1000);
        </script>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>