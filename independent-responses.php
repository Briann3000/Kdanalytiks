<?php
include 'header.php';

// Check if independent is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'independent') {
    header('Location: independent-login.php?error=Please login first');
    exit();
}

$independent = R::findOne('independents', ' user_id = ? ', [$_SESSION['user']['id']]);
$surveyId = $_GET['survey_id'] ?? null;

if (!$surveyId) {
    header('Location: independent-surveys.php');
    exit();
}

$survey = R::load('surveys', $surveyId);
if ($survey->independent_id != $independent->id) {
    header('Location: independent-surveys.php');
    exit();
}

$responses = R::findAll('responses', ' survey_id = ? ORDER BY submitted_at DESC', [$surveyId]);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-purple"><i class="fa fa-list"></i> Responses for: <?php echo htmlspecialchars($survey->title); ?></h2>
    
    <div class="w3-margin-bottom">
        <a href="independent-surveys.php" class="w3-button w3-purple w3-round">
            <i class="fa fa-arrow-left"></i> Back to Surveys
        </a>
    </div>
    
    <?php if (count($responses) > 0): ?>
        <div class="w3-responsive">
            <table class="w3-table w3-bordered w3-striped w3-white w3-card">
                <thead>
                    <tr class="w3-purple">
                        <th>Response ID</th>
                        <th>Respondent</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $response): ?>
                        <tr>
                            <td><?php echo $response->id; ?></td>
                            <td>
                                <?php 
                                $respondent = R::load('users', $response->respondent_id);
                                echo htmlspecialchars($respondent->name);
                                ?>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($response->submitted_at)); ?></td>
                            <td>
                                <a href="#" class="w3-button w3-small w3-purple">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="w3-panel w3-yellow w3-round">
            <p>No responses yet for this survey.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>