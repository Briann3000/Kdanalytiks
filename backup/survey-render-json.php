<?php include 'header.php'; ?>

<?php
$surveyId = $_GET['id'] ?? null;

if (!$surveyId) {
    echo '<div class="w3-container w3-padding"><div class="w3-panel w3-red w3-round">Survey not found!</div></div>';
    include 'footer.php';
    exit();
}

$survey = R::load('surveys', $surveyId);

if (!$survey->id || $survey->status !== 'active') {
    echo '<div class="w3-container w3-padding"><div class="w3-panel w3-red w3-round">Survey not available!</div></div>';
    include 'footer.php';
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Clean up the incoming data (remove the CSRF token so we don't save it)
    $clean_post = $_POST;
    unset($clean_post['csrf_token']);
    
    // 2. Create the response
    $response = R::dispense('responses');
    $response->survey_id = $surveyId;
    $response->respondent_id = null; // Anonymous for public surveys
    
    // 3. Save the ENTIRE form directly to the response row!
    // RedBeanPHP will automatically create this new 'form_data' column in your SQLite DB
    $response->form_data = json_encode($clean_post);
    
    // 4. Store it. (Notice we completely skip the 'answers' table!)
    R::store($response);

    $message = 'Thank you for completing the survey!';
}
?>

<div class="w3-container w3-padding">
    <div class="w3-card w3-white w3-padding w3-round">
        <h2 class="w3-text-blue"><?php echo htmlspecialchars($survey->title); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($survey->description)); ?></p>

        <?php if ($message): ?>
            <div class="w3-panel w3-green w3-round w3-margin-top"><?php echo $message; ?></div>
        <?php else: ?>
            <form method="post" id="surveyForm" class="w3-margin-top">
                <div id="surveyContainer"></div>
                <button type="submit" class="w3-button w3-blue w3-margin-top">Submit Survey</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- jQuery Form Builder CSS -->
<link href="https://formbuilder.online/assets/css/form-render.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jQuery Form Render JS -->
<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>

<script>
    $(document).ready(function () {
        // Load survey schema
        const surveyJson = <?php echo $survey->json_schema; ?>;

        // Render the form
        $('#surveyContainer').formRender({
            dataType: 'json',
            formData: surveyJson
        });
    });
</script>

<?php include 'footer.php'; ?>