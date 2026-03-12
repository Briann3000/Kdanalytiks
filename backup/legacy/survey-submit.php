<?php
include 'header.php';

// Handle survey submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surveyId = $_POST['survey_id'];
    $respondentId = $_POST['respondent_id'];
    
    // Create response
    $response = R::dispense('responses');
    $response->survey_id = $surveyId;
    $response->respondent_id = $respondentId;
    R::store($response);
    
    // Save answers
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'question_') === 0) {
            $questionId = str_replace('question_', '', $key);
            
            $answer = R::dispense('answers');
            $answer->response_id = $response->id;
            $answer->question_id = $questionId;
            $answer->value = is_array($value) ? implode(', ', $value) : $value;
            R::store($answer);
        }
    }
    
    header('Location: survey-thankyou.php');
    exit();
}
?>