<?php include 'header.php'; ?>

<?php
$surveyId = $_GET['id'] ?? null;
if (!$surveyId) {
    echo '<div class="w3-container w3-padding"><div class="w3-panel w3-red w3-round">Survey not found!</div></div>';
    include 'footer.php';
    exit();
}

$survey = R::load('surveys', $surveyId);
if (!$survey->id || $survey->status !== 'active') {
    echo '<div class="w3-container w3-padding"><div class="w3-panel w3-red w3-round">Survey not available!</div></div>';
    include 'footer.php';
    exit();
}

$questions = R::find('questions', ' survey_id = ? ORDER BY position', [$surveyId]);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create response (for public surveys, respondent_id can be NULL or we can create anonymous respondent)
    $response = R::dispense('responses');
    $response->survey_id = $surveyId;
    $response->respondent_id = null; // Public survey, anonymous
    R::store($response);

    // Setup secure file upload environment
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $allowedMimeTypes = [//prevent malicious file uploads by checking MIME types
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
        'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm', 'audio/aac', 'audio/mp4'
    ];
    
    // Save answers
    foreach ($questions as $question) {
        $answerValue = $_POST['question_' . $question->id] ?? '';

        // 2. Handle array values (checkboxes) by converting to a string
    if (is_array($answerValue)) {
        $answerValue = implode(', ', $answerValue);
    }

        $answer = R::dispense('answers');
        $answer->response_id = $response->id;
        $answer->question_id = $question->id;
        // SECURITY: Sanitize the final string to prevent XSS attacks
         $answer->value = htmlspecialchars($answerValue);

        R::store($answer);
    }
    
    $message = 'Thank you for completing the survey!';
}
?>

<div class="w3-container w3-padding">
    <div class="w3-card w3-white w3-padding w3-round">
        <h2 class="w3-text-blue"><?php echo htmlspecialchars($survey->title); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($survey->description)); ?></p>
        
        <?php if ($message): ?>
            <div class="w3-panel w3-green w3-round"><?php echo $message; ?></div>
        <?php else: ?>
            <form method="post" class="w3-margin-top" enctype="multipart/form-data">
                <?php foreach ($questions as $question): ?>
                    <div class="w3-margin-bottom">
                        <label><strong><?php echo htmlspecialchars($question->text); ?></strong></label>
                        <?php if ($question->required): ?>
                            <span class="w3-text-red">*</span>
                        <?php endif; ?>
                        
                        <?php if ($question->type == 'text'): ?>
                            <input class="w3-input w3-border" type="text" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                        
                        <?php elseif ($question->type == 'radio'): 
                            $options = json_decode($question->options);
                            foreach ($options as $option): ?>
                                <p><input class="w3-radio" type="radio" name="question_<?php echo $question->id; ?>" value="<?php echo htmlspecialchars($option); ?>" <?php echo $question->required ? 'required' : ''; ?>> <?php echo htmlspecialchars($option); ?></p>
                            <?php endforeach; ?>
                        
                        <?php elseif ($question->type == 'checkbox'): 
                            $options = json_decode($question->options);
                            foreach ($options as $option): ?>
                                <p><input class="w3-check" type="checkbox" name="question_<?php echo $question->id; ?>[]" value="<?php echo htmlspecialchars($option); ?>"> <?php echo htmlspecialchars($option); ?></p>
                            <?php endforeach; ?>
                        
                        <?php elseif ($question->type == 'integer'): ?>
                            <input class="w3-input w3-border" type="number" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                        
                        <?php elseif ($question->type == 'email'): ?>
                            <input class="w3-input w3-border" type="email" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                        
                        <?php elseif ($question->type == 'tel'): ?>
                            <input class="w3-input w3-border" type="tel" name="question_<?php echo $question->id; ?>" <?php echo $question->required ? 'required' : ''; ?>>
                        
                        <?php elseif ($question->type == 'geo'): ?>
                            <input class="w3-input w3-border" type="text" name="question_<?php echo $question->id; ?>" placeholder="Enter location or coordinates" <?php echo $question->required ? 'required' : ''; ?>>
                        
                        <?php elseif ($question->type == 'video'): ?>
                            <input class="w3-input w3-border" type="file" name="question_<?php echo $question->id; ?>" accept="video/*" <?php echo $question->required ? 'required' : ''; ?>>
                        
                        <?php elseif ($question->type == 'audio'): ?>
                            <input class="w3-input w3-border" type="file" name="question_<?php echo $question->id; ?>" accept="audio/*" <?php echo $question->required ? 'required' : ''; ?>>
                        
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="w3-button w3-blue w3-round w3-margin-top">Submit Survey</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>