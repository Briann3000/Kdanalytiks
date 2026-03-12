<?php include 'header.php'; ?>

<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php?error=Please login first');
    exit();
}

$surveyId = $_GET['id'] ?? null;
if (!$surveyId) {
    header('Location: admin-dashboard.php');
    exit();
}

$survey = R::load('surveys', $surveyId);
if (!$survey->id) {
    header('Location: admin-dashboard.php');
    exit();
}

// Fetch all responses at the top so we can use them for both Text Lists and JS Charts
$allResponses = R::find('responses', ' survey_id = ? ', [$surveyId]);
$totalResponses = count($allResponses);

// Check if this is a JSON-based survey
$isJsonSurvey = !empty($survey->json_schema);
$questions = [];

if ($isJsonSurvey) {
    $jsonData = json_decode($survey->json_schema, true);

    if ($jsonData && is_array($jsonData)) {
        // Handle direct array structure (most common for jQuery Form Builder)
        foreach ($jsonData as $index => $field) {
            if (is_array($field) && isset($field['type'])) {
                // Skip non-data fields
                if (!in_array($field['type'], ['header', 'paragraph'])) {
                    $question = new stdClass();
                    $question->id = 'json_' . $index;
                    $question->text = $field['label'] ?? $field['placeholder'] ?? 'Question ' . ($index + 1);
                    $question->type = $field['type'] ?? 'text';
                    $question->name = $field['name'] ?? 'field-' . $index;
                    $questions[] = $question;
                }
            }
        }
    }
} else {
    // Traditional question-based survey
    $questions = R::find('questions', ' survey_id = ? ORDER BY position', [$surveyId]);
}
?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-chart-bar"></i> Survey Report:
        <?php echo htmlspecialchars($survey->title); ?>
    </h2>

    <div class="w3-margin-bottom">
        <a href="admin-dashboard.php" class="w3-button w3-blue w3-round">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
        <a href="admin-send-survey.php?id=<?php echo $surveyId; ?>" class="w3-button w3-green w3-round w3-margin-left">
            <i class="fa fa-envelope"></i> Send Survey
        </a>
    </div>

    <div class="w3-row-padding">
        <div class="w3-third">
            <div class="w3-card w3-padding w3-blue w3-center">
                <h3><?php echo $totalResponses; ?></h3>
                <p>Total Responses</p>
            </div>
        </div>
        <div class="w3-third">
            <div class="w3-card w3-padding w3-green w3-center">
                <h3><?php
                $questionableCount = 0;
                foreach ($questions as $q) {
                    if (!in_array($q->type, ['header', 'paragraph'])) {
                        $questionableCount++;
                    }
                }
                echo $questionableCount;
                ?></h3>
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
            <?php
            // Skip rendering for headers and paragraphs (they collect no data)
            if (in_array($question->type, ['header', 'paragraph'])) {
                continue;
            }

            // Decide if this question gets a Chart OR a Text List
            $isChartable = !in_array($question->type, ['text', 'textarea', 'date']);
            ?>

            <div class="w3-card w3-white w3-padding w3-margin-bottom">
                <h4><?php echo htmlspecialchars($question->text); ?></h4>
                <p class="w3-small w3-text-grey">Type: <?php echo htmlspecialchars($question->type); ?></p>

                <?php if ($isChartable): ?>
                    <canvas id="chart-<?php echo $question->id; ?>" width="400" height="150"
                        style="max-height: 300px;"></canvas>
                <?php else: ?>
                    <div class="w3-border w3-round" style="max-height: 250px; overflow-y: auto;">
                        <ul class="w3-ul w3-hoverable">
                            <?php
                            $hasTextAnswers = false;
                            foreach ($allResponses as $res) {
                                // Extract the answer from form_data
                                if (!empty($res->form_data)) {
                                    $data = json_decode($res->form_data, true);
                                    if (isset($data[$question->name]) && !empty(trim($data[$question->name]))) {
                                        echo '<li><i class="fa fa-comment w3-text-blue w3-margin-right"></i> ' . htmlspecialchars($data[$question->name]) . '</li>';
                                        $hasTextAnswers = true;
                                    }
                                }
                            }
                            if (!$hasTextAnswers) {
                                echo '<li class="w3-text-grey w3-center w3-padding-16">No written responses yet.</li>';
                            }
                            ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Build the Chart Data Array in PHP
    <?php
    $allChartData = [];

    if ($isJsonSurvey) {
        foreach ($questions as $question) {
            // Skip text fields and headers so we don't pass empty data to Javascript
            if (in_array($question->type, ['header', 'paragraph', 'text', 'textarea', 'date'])) {
                continue;
            }

            $fieldName = $question->name;
            $answerCounts = [];

            // Tally the answers for charts
            foreach ($allResponses as $response) {
                if (!empty($response->form_data)) {
                    $responseData = json_decode($response->form_data, true);

                    if ($responseData && isset($responseData[$fieldName])) {
                        $value = $responseData[$fieldName];

                        // Handle arrays (checkboxes) vs strings (radio/select)
                        if (is_array($value)) {
                            foreach ($value as $val) {
                                if (!empty(trim((string) $val))) {
                                    $answerCounts[(string) $val] = ($answerCounts[(string) $val] ?? 0) + 1;
                                }
                            }
                        } else {
                            $answerValue = (string) $value;
                            if (!empty(trim($answerValue))) {
                                $answerCounts[$answerValue] = ($answerCounts[$answerValue] ?? 0) + 1;
                            }
                        }
                    }
                }
            }

            $allChartData[] = [
                'canvas_id' => 'chart-' . $question->id,
                'labels' => array_keys($answerCounts),
                'data' => array_values($answerCounts)
            ];
        }
    } else {
        foreach ($questions as $question) {
            $answers = R::getAll('SELECT value, COUNT(*) as count FROM answers WHERE question_id = ? GROUP BY value', [$question->id]);
            $allChartData[] = [
                'canvas_id' => 'chart-' . $question->id,
                'labels' => array_column($answers, 'value'),
                'data' => array_column($answers, 'count')
            ];
        }
    }
    ?>

    // 2. Teleport the PHP array into JavaScript
    const chartConfigs = <?php echo json_encode($allChartData); ?>;

    // 3. Render the charts
    chartConfigs.forEach(config => {
        const canvasElement = document.getElementById(config.canvas_id);
        if (!canvasElement) return;

        const ctx = canvasElement.getContext('2d');

        if (config.labels.length === 0) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['No responses yet'],
                    datasets: [{ label: 'Responses', data: [0], backgroundColor: '#cccccc' }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, max: 1, ticks: { precision: 0 } } }
                }
            });
        } else {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: [{ label: 'Responses', data: config.data, backgroundColor: '#0b66c3' }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    });
</script>

<?php include 'footer.php'; ?>