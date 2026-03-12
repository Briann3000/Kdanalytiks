<?php
include 'header.php';

// Check if respondent is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'respondent') {
    header('Location: respondent-login.php?error=Please login first');
    exit();
}

// Get available surveys
$publicSurveys = R::find('surveys', ' type = ? AND status = ? ', ['public', 'active']);
$myResponses = R::count('responses', ' respondent_id = ? ', [$_SESSION['user']['id']]);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-tachometer-alt"></i> Respondent Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!</p>
    
    <div class="w3-row-padding w3-margin-top">
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-blue w3-center">
                <h3><?php echo count($publicSurveys); ?></h3>
                <p>Available Surveys</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-green w3-center">
                <h3><?php echo $myResponses; ?></h3>
                <p>My Responses</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-orange w3-center">
                <h3>0</h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-red w3-center">
                <h3>0</h3>
                <p>Completed</p>
            </div>
        </div>
    </div>
    
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">Quick Actions</h3>
        <a href="respondent-surveys.php" class="w3-button w3-blue w3-margin-right">Take Surveys</a>
        <a href="respondent-responses.php" class="w3-button w3-green">My Responses</a>
    </div>
    
    <div class="w3-margin-top">
    <h3 class="w3-text-grey">Available Public Surveys</h3>
    <?php
    $publicSurveys = R::find('surveys', ' status = ? AND type = ? ORDER BY created_at DESC LIMIT 5', ['active', 'public']);
    if (count($publicSurveys) > 0): ?>
        <div class="w3-row-padding">
            <?php foreach ($publicSurveys as $survey): ?>
                <div class="w3-col l4 m6 s12 w3-margin-bottom">
                    <div class="w3-card w3-white w3-padding w3-round">
                        <h4><?php echo htmlspecialchars($survey->title); ?></h4>
                        <p><?php echo substr(htmlspecialchars($survey->description), 0, 80) . '...'; ?></p>
                        <a href="public-take-survey.php?id=<?php echo $survey->id; ?>" class="w3-button w3-blue w3-round">Take Survey</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No public surveys available at the moment.</p>
    <?php endif; ?>
</div>
    
    
    
</div>

<?php include 'footer.php'; ?>