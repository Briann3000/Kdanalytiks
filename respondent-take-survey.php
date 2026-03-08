<?php
include 'header.php';

// Check if respondent is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'respondent') {
    header('Location: respondent-login.php?error=Please login first');
    exit();
}

$surveyId = $_GET['id'] ?? null;
if (!$surveyId) {
    header('Location: respondent-surveys.php');
    exit();
}

$survey = R::load('surveys', $surveyId);
if (!$survey->id || $survey->status !== 'active') {
    header('Location: respondent-surveys.php');
    exit();
}

$questions = R::find('questions', ' survey_id = ? ORDER BY position', [$surveyId]);

// Check if already completed
$completed = R::count('responses', ' survey_id = ? AND respondent_id = ? ', [$surveyId, $_SESSION['user']['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create response
    $response = R::dispense('responses');
    $response->survey_id = $surveyId;
    $response->respondent_id = $_SESSION['user']['id'];
    R::store($response);
    
    // Save answers
    foreach ($questions as $question) {
        $answerValue = $_POST['question_' . $question->id] ?? '';
        
        $answer = R::dispense('answers');
        $answer->response_id = $response->id;
        $answer->question_id = $question->id;
        $answer->value = is_array($answerValue) ? implode(', ', $answerValue) : $answerValue;
        R::store($answer);
    }
    
    header('Location: respondent-surveys.php?completed=1');
    exit();
}


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><?php echo htmlspecialchars($survey->title); ?></h2>
    <p><?php echo htmlspecialchars($survey->description); ?></p>
    
    <?php if ($completed > 0): ?>
        <div class="w3-panel w3-yellow w3-round">
            <p>You have already completed this survey.</p>
        </div>
    <?php else: ?>
        <form method="post" class="w3-card w3-white w3-padding w3-round">
            <?php foreach ($questions as $question): ?>
                <div class="w3-margin-bottom">
                    <label><strong><?php echo htmlspecialchars($question->text); ?></strong></label>
                    <?php if ($question->required): ?>
                        <span class="w3-text-red">*</span>
                    <?php endif; ?>
                    
                    <?php if ($question->type == 'text'): ?>
                        <input type="text" class="w3-input w3-border" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                    
                    <?php elseif ($question->type == 'radio'): ?>
                        <?php 
                        $options = json_decode($question->options);
                        foreach ($options as $option): ?>
                            <label class="w3-margin-right">
                                <input type="radio" name="question_<?php echo $question->id; ?>" value="<?php echo htmlspecialchars($option); ?>" <?php echo $question->required ? 'required' : ''; ?>>
                                <?php echo htmlspecialchars($option); ?>
                            </label>
                        <?php endforeach; ?>
                    
                    <?php elseif ($question->type == 'checkbox'): ?>
                        <?php 
                        $options = json_decode($question->options);
                        foreach ($options as $option): ?>
                            <label class="w3-margin-right">
                                <input type="checkbox" name="question_<?php echo $question->id; ?>[]" value="<?php echo htmlspecialchars($option); ?>">
                                <?php echo htmlspecialchars($option); ?>
                            </label>
                        <?php endforeach; ?>
                    
                    <?php elseif ($question->type == 'integer'): ?>
                        <input type="number" class="w3-input w3-border" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                    
                    <?php elseif ($question->type == 'email'): ?>
                        <input type="email" class="w3-input w3-border" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                    
                    <?php elseif ($question->type == 'tel'): ?>
                        <input type="tel" class="w3-input w3-border" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                    
                    <?php elseif ($question->type == 'geo'): ?>
                        <input type="text" class="w3-input w3-border" name="question_<?php echo $question->id; ?>" placeholder="Enter location or coordinates" <?php echo $question->required ? 'required' : ''; ?>>
                    
                    <?php elseif ($question->type == 'video'): ?>
                        <input type="file" class="w3-input w3-border" name="question_<?php echo $question->id; ?>" accept="video/*" <?php echo $question->required ? 'required' : ''; ?>>
                    
                    <?php elseif ($question->type == 'audio'): ?>
                        <input type="file" class="w3-input w3-border" name="question_<?php echo $question->id; ?>" accept="audio/*" <?php echo $question->required ? 'required' : ''; ?>>
                    
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="w3-button w3-green w3-round">Submit Survey</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>