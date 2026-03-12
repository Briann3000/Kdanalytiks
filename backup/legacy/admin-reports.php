<?php
include 'header.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php?error=Please login first');
    exit();
}
/**
// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: admin-login.php?error=Please login first');
    exit();
}
*/
// Get statistics
$totalSurveys = R::count('surveys');
$totalResponses = R::count('responses');
$totalOrganizations = R::count('organizations');
$totalRespondents = R::count('users', ' role = ? ', ['respondent']);

// Survey distribution by category
$categoryStats = R::getAll('SELECT category, COUNT(*) as count FROM surveys GROUP BY category');

// Response trends (last 7 days)
$responseTrends = R::getAll('SELECT DATE(submitted_at) as date, COUNT(*) as count FROM responses WHERE submitted_at >= DATE("now", "-7 days") GROUP BY DATE(submitted_at)');


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-chart-bar"></i> Platform Reports</h2>
    
    <div class="w3-row-padding w3-margin-top">
        <div class="w3-third">
            <div class="w3-card w3-padding w3-blue">
                <h3>Surveys by Category</h3>
                <canvas id="categoryChart" width="400" height="300"></canvas>
            </div>
        </div>
        <div class="w3-third">
            <div class="w3-card w3-padding w3-green">
                <h3>Response Trends (Last 7 Days)</h3>
                <canvas id="trendChart" width="400" height="300"></canvas>
            </div>
        </div>
        <div class="w3-third">
            <div class="w3-card w3-padding w3-orange">
                <h3>Platform Statistics</h3>
                <ul class="w3-ul">
                    <li>Total Surveys: <?php echo $totalSurveys; ?></li>
                    <li>Total Responses: <?php echo $totalResponses; ?></li>
                    <li>Organizations: <?php echo $totalOrganizations; ?></li>
                    <li>Respondents: <?php echo $totalRespondents; ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($categoryStats, 'category')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($categoryStats, 'count')); ?>,
            backgroundColor: ['#0b66c3', '#4CAF50', '#FF9800', '#9C27B0']
        }]
    },
    options: {
        responsive: true
    }
});

// Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($responseTrends, 'date')); ?>,
        datasets: [{
            label: 'Responses',
            data: <?php echo json_encode(array_column($responseTrends, 'count')); ?>,
            borderColor: '#4CAF50',
            fill: false
        }]
    },
    options: {
        responsive: true
    }
});
</script>

<?php include 'footer.php'; ?>