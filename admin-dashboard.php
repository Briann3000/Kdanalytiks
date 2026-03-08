<?php include 'header.php'; ?>

<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php?error=Please login first');
    exit();
}

// Get statistics
$totalUsers = R::count('users');
$totalOrganizations = R::count('organizations');
$totalIndependents = R::count('independents');
$totalSurveys = R::count('surveys');
$totalResponses = R::count('responses');

// Get public surveys statistics
$publicSurveys = R::count('surveys', ' type = ? ', ['public']);
$publicSurveyResponses = R::getCol('SELECT COUNT(r.id) FROM responses r JOIN surveys s ON r.survey_id = s.id WHERE s.type = ? GROUP BY s.id', ['public']);
$totalPublicResponses = array_sum($publicSurveyResponses);
?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-tachometer-alt"></i> Admin Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
    
    <!-- Admin Info Panel -->
    <div class="w3-panel w3-blue w3-round w3-margin-bottom">
        <h4>Admin Information:</h4>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['admin_email']); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['admin_role']); ?></p>
        <p><strong>Logged in at:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></p>
    </div>
    
    <div class="w3-row-padding w3-margin-top">
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-blue w3-center">
                <h3><?php echo $totalUsers; ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-green w3-center">
                <h3><?php echo $totalOrganizations; ?></h3>
                <p>Organizations</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-purple w3-center">
                <h3><?php echo $totalIndependents; ?></h3>
                <p>Researchers</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-orange w3-center">
                <h3><?php echo $totalSurveys; ?></h3>
                <p>Surveys</p>
            </div>
        </div>
    </div>
    
    <div class="w3-row-padding w3-margin-top">
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-red w3-center">
                <h3><?php echo $totalResponses; ?></h3>
                <p>Total Responses</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-yellow w3-center">
                <h3>0</h3>
                <p>Pending Users</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-indigo w3-center">
                <h3>0</h3>
                <p>Payments</p>
            </div>
        </div>
        <div class="w3-quarter">
            <div class="w3-card w3-padding w3-teal w3-center">
                <h3>0</h3>
                <p>Reports</p>
            </div>
        </div>
    </div>
    
    <!-- Public Surveys Statistics Section -->
    <div class="w3-margin-top">
        <h3 class="w3-text-blue">Public Surveys Overview</h3>
        <div class="w3-row-padding">
            <div class="w3-third">
                <div class="w3-card w3-padding w3-purple w3-center">
                    <h3><?php echo $publicSurveys; ?></h3>
                    <p>Public Surveys</p>
                </div>
            </div>
            <div class="w3-third">
                <div class="w3-card w3-padding w3-green w3-center">
                    <h3><?php echo $totalPublicResponses; ?></h3>
                    <p>Public Responses</p>
                </div>
            </div>
            <div class="w3-third">
                <div class="w3-card w3-padding w3-orange w3-center">
                    <h3><?php echo $publicSurveys > 0 ? round($totalPublicResponses / $publicSurveys, 1) : 0; ?></h3>
                <p>Avg Responses</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">Quick Actions</h3>
        <a href="admin-users.php" class="w3-button w3-blue w3-margin-right">Manage Users</a>
        <a href="admin-surveys.php" class="w3-button w3-green w3-margin-right">Manage Surveys</a>
        <a href="admin-survey-builder.php" class="w3-button w3-purple w3-margin-right">Create Public Survey</a>
        <a href="admin-reports.php" class="w3-button w3-orange">View Reports</a>
    </div>
    
    <!-- Recent Public Surveys Section -->
    <div class="w3-margin-top">
        <h3 class="w3-text-blue">Recent Public Surveys</h3>
        <?php
        $recentPublicSurveys = R::find('surveys', ' type = ? ORDER BY created_at DESC LIMIT 3', ['public']);
        if (count($recentPublicSurveys) > 0):
        ?>
            <div class="w3-row-padding">
                <?php foreach ($recentPublicSurveys as $survey): ?>
                    <div class="w3-third w3-margin-bottom">
                        <div class="w3-card w3-white w3-padding w3-round">
                            <h4><?php echo htmlspecialchars($survey->title); ?></h4>
                            <p><?php echo substr(htmlspecialchars($survey->description), 0, 80) . '...'; ?></p>
                            <div class="w3-margin-top">
                                <span class="w3-tag w3-blue"><?php echo htmlspecialchars($survey->category); ?></span>
                                <span class="w3-tag w3-green w3-right"><?php echo R::count('responses', ' survey_id = ? ', [$survey->id]); ?> responses</span>
                            </div>
                            <div class="w3-margin-top">
                                <a href="public-survey-report.php?id=<?php echo $survey->id; ?>" class="w3-button w3-blue w3-round w3-small">
                                    <i class="fa fa-chart-bar"></i> View Report
                                </a>
                                <a href="admin-send-survey.php?id=<?php echo $survey->id; ?>" class="w3-button w3-green w3-round w3-small w3-margin-left">
                                    <i class="fa fa-envelope"></i> Send Survey
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="w3-panel w3-yellow w3-round">
                <p>No public surveys created yet. <a href="admin-survey-builder.php" class="w3-text-blue">Create your first public survey</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Links Section -->
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">Quick Links</h3>
        <div class="w3-row-padding">
            <div class="w3-third">
                <a href="public-list-surveys.php" class="w3-button w3-blue w3-block w3-round">
                    <i class="fa fa-list"></i> View Public Surveys
                </a>
            </div>
            <div class="w3-third">
                <a href="admin-survey-builder.php" class="w3-button w3-purple w3-block w3-round">
                    <i class="fa fa-plus"></i> Create New Survey
                </a>
            </div>
            <div class="w3-third">
                <a href="admin-reports.php" class="w3-button w3-orange w3-block w3-round">
                    <i class="fa fa-chart-line"></i> Analytics Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <div class="w3-margin-top">
        <a href="admin-logout.php" class="w3-button w3-red">
            <i class="fa fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>