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

$questions = R::find('questions', ' survey_id = ? ORDER BY position', [$surveyId]);
$responses = R::count('responses', ' survey_id = ? ', [$surveyId]);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-chart-bar"></i> Survey Report: <?php echo htmlspecialchars($survey->title); ?></h2>
    
    <div class="w3-margin-bottom">
        <a href="organization-surveys.php" class="w3-button w3-blue w3-round">
            <i class="fa fa-arrow-left"></i> Back to Surveys
        </a>
    </div>
    
    <div class="w3-row-padding">
        <div class="w3-third">
            <div class="w3-card w3-padding w3-blue w3-center">
                <h3><?php echo $responses; ?></h3>
                <p>Total Responses</p>
            </div>
        </div>
        <div class="w3-third">
            <div class="w3-card w3-padding w3-green w3-center">
                <h3><?php echo count($questions); ?></h3>
                <p>Questions</p>
            </div>
        </div>
        <div class="w3-third">
            <div class="w3-card w3-padding w3-orange w3-center">
                <h3><?php echo date('M j, Y', strtotime($survey->created_at)); ?></h3>
                <p>Created</p>
            </div>
        </div>
    </div>
    
    <div class="w3-margin-top">
        <h3 class="w3-text-blue">Question Analysis</h3>
        <?php foreach ($questions as $question): ?>
            <div class="w3-card w3-white w3-padding w3-margin-bottom">
                <h4><?php echo htmlspecialchars($question->text); ?></h4>
                <canvas id="chart-<?php echo $question->id; ?>" width="400" height="200"></canvas>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Generate charts for each question
<?php foreach ($questions as $question): ?>
    <?php
    $answers = R::getAll('SELECT value, COUNT(*) as count FROM answers WHERE question_id = ? GROUP BY value', [$question->id]);
    ?>
    const ctx<?php echo $question->id; ?> = document.getElementById('chart-<?php echo $question->id; ?>').getContext('2d');
    new Chart(ctx<?php echo $question->id; ?>, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($answers, 'value')); ?>,
            datasets: [{
                label: 'Responses',
                data: <?php echo json_encode(array_column($answers, 'count')); ?>,
                backgroundColor: '#0b66c3'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
<?php endforeach; ?>
</script>

<?php include 'footer.php'; ?>