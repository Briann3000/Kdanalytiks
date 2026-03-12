<?php
include 'header.php';

// Check if respondent is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'respondent') {
    header('Location: respondent-login.php?error=Please login first');
    exit();
}

// Get available surveys
$surveys = R::find('surveys', ' type = ? AND status = ? ', ['public', 'active']);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-list"></i> Available Surveys</h2>
    
    <?php if (count($surveys) > 0): ?>
        <div class="w3-row-padding">
            <?php foreach ($surveys as $survey): ?>
                <div class="w3-third w3-margin-bottom">
                    <div class="w3-card w3-white w3-padding w3-round">
                        <h3><?php echo htmlspecialchars($survey->title); ?></h3>
                        <p><?php echo htmlspecialchars($survey->description); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($survey->category); ?></p>
                        <a href="respondent-take-survey.php?id=<?php echo $survey->id; ?>" class="w3-button w3-blue w3-round">Take Survey</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="w3-panel w3-yellow w3-round">
            <p>No surveys available at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>