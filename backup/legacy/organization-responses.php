<?php
include 'header.php';

// Check if organization is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'organization') {
    header('Location: organization-login.php?error=Please login first');
    exit();
}

$org = R::findOne('organizations', ' user_id = ? ', [$_SESSION['user']['id']]);
$surveyId = $_GET['survey_id'] ?? null;

if (!$surveyId) {
    header('Location: organization-surveys.php');
    exit();
}

$survey = R::load('surveys', $surveyId);
if ($survey->organization_id != $org->id) {
    header('Location: organization-surveys.php');
    exit();
}

$responses = R::findAll('responses', ' survey_id = ? ORDER BY submitted_at DESC', [$surveyId]);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-list"></i> Responses for: <?php echo htmlspecialchars($survey->title); ?></h2>
    
    <div class="w3-margin-bottom">
        <a href="organization-surveys.php" class="w3-button w3-blue w3-round">
            <i class="fa fa-arrow-left"></i> Back to Surveys
        </a>
    </div>
    
    <?php if (count($responses) > 0): ?>
        <div class="w3-responsive">
            <table class="w3-table w3-bordered w3-striped w3-white w3-card">
                <thead>
                    <tr class="w3-blue">
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
                                <a href="#" class="w3-button w3-small w3-blue">View Details</a>
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