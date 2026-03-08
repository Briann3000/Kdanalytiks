<?php
include 'header.php';

// Check if independent is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'independent') {
    header('Location: independent-login.php?error=Please login first');
    exit();
}

$independent = R::findOne('independents', ' user_id = ? ', [$_SESSION['user']['id']]);
$totalSurveys = R::count('surveys', ' independent_id = ? ', [$independent->id]);
$totalResponses = R::count('responses', ' survey_id IN (SELECT id FROM surveys WHERE independent_id = ?) ', [$independent->id]);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-purple"><i class="fa fa-tachometer-alt"></i> Researcher Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($independent->name); ?>!</p>
    <p><strong>Institution:</strong> <?php echo htmlspecialchars($independent->institution); ?></p>
    <p><strong>Research Area:</strong> <?php echo htmlspecialchars($independent->research_area); ?></p>
    
    <div class="w3-row-padding w3-margin-top">
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-purple w3-center">
                <h3><?php echo $totalSurveys; ?></h3>
                <p>Surveys Created</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-green w3-center">
                <h3><?php echo $totalResponses; ?></h3>
                <p>Total Responses</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-orange w3-center">
                <h3>0</h3>
                <p>Pending Surveys</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-red w3-center">
                <h3>0</h3>
                <p>Reports</p>
            </div>
        </div>
    </div>
    
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">Quick Actions</h3>
        <a href="independent-survey-builder.php" class="w3-button w3-purple w3-margin-right">Create Survey</a>
        <a href="independent-surveys.php" class="w3-button w3-green w3-margin-right">Manage Surveys</a>
        <a href="independent-responses.php" class="w3-button w3-orange w3-margin-right">View Responses</a>
        <a href="independent-reports.php" class="w3-button w3-red">Generate Reports</a>
    </div>
</div>

<?php include 'footer.php'; ?>