<?php include 'header.php'; ?>

<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php?error=Please login first');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $type = $_POST['type'];
    
    // Create survey (admin survey has both organization_id and independent_id as NULL)
    $survey = R::dispense('surveys');
    $survey->title = $title;
    $survey->description = $description;
    $survey->category = $category;
    $survey->type = $type;
    $survey->status = 'active';
    R::store($survey);
    
    // Add questions
    if (isset($_POST['questions'])) {
        foreach ($_POST['questions'] as $index => $question) {
            if (!empty($question['text'])) {
                $q = R::dispense('questions');
                $q->survey_id = $survey->id;
                $q->text = $question['text'];
                $q->type = $question['type'];
                $q->options = isset($question['options']) ? json_encode(explode(',', $question['options'])) : null;
                $q->required = isset($question['required']) ? 1 : 0;
                $q->position = $index;
                R::store($q);
            }
        }
    }
    
    $message = 'Survey created successfully!';
    $surveyId = $survey->id;
}

include 'header.php';
?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-plus-circle"></i> Create Public Survey</h2>
    
    <?php if ($message): ?>
        <div class="w3-panel w3-green w3-round"><?php echo $message; ?></div>
        <?php if (isset($surveyId)): ?>
            <div class="w3-panel w3-blue w3-round">
                <p><strong>Survey Link:</strong> 
                <a href="public-take-survey.php?id=<?php echo $surveyId; ?>" target="_blank">
                    public-take-survey.php?id=<?php echo $surveyId; ?>
                </a></p>
                <p><strong>Report Link:</strong> 
                <a href="public-survey-report.php?id=<?php echo $surveyId; ?>" target="_blank">
                    public-survey-report.php?id=<?php echo $surveyId; ?>
                </a></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <form method="post" class="w3-card w3-white w3-padding w3-round">
        <div class="w3-row-padding">
            <div class="w3-half">
                <label>Survey Title</label>
                <input class="w3-input w3-border" type="text" name="title" required>
            </div>
            <div class="w3-half">
                <label>Category</label>
                <select class="w3-select w3-border" name="category" required>
                    <option value="">Select Category</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Academic">Academic</option>
                    <option value="Product">Product</option>
                    <option value="Political">Political</option>
                </select>
            </div>
        </div>
        
        <div class="w3-margin-top">
            <label>Description</label>
            <textarea class="w3-input w3-border" name="description" rows="3"></textarea>
        </div>
        
        <div class="w3-margin-top">
            <label>Survey Type</label>
            <select class="w3-select w3-border" name="type" required>
                <option value="public">Public</option>
                <option value="invitation">Invitation Only</option>
            </select>
        </div>
        
        <h3 class="w3-text-blue w3-margin-top">Questions</h3>
        <div id="questions-container">
            <!-- Questions will be added here dynamically -->
        </div>
        
        <button type="button" class="w3-button w3-blue w3-margin-top" onclick="addQuestion()">
            <i class="fa fa-plus"></i> Add Question
        </button>
        
        <div class="w3-margin-top">
            <button type="submit" class="w3-button w3-green w3-round">
                <i class="fa fa-save"></i> Save Survey
            </button>
            <a href="admin-dashboard.php" class="w3-button w3-red w3-round">Cancel</a>
        </div>
    </form>
</div>

<script>
let questionCount = 0;

function addQuestion() {
    questionCount++;
    const container = document.getElementById('questions-container');
    const questionHtml = `
        <div class="w3-card w3-light-grey w3-padding w3-margin-bottom w3-round" id="question-${questionCount}">
            <div class="w3-row-padding">
                <div class="w3-twothird">
                    <input type="text" class="w3-input w3-border" name="questions[${questionCount}][text]" placeholder="Question text" required>
                </div>
                <div class="w3-third">
                    <select class="w3-select w3-border" name="questions[${questionCount}][type]" onchange="toggleOptions(${questionCount})" required>
                        <option value="text">Text</option>
                        <option value="radio">Radio</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="matrix">Matrix</option>
                        <option value="geo">Geolocation</option>
                        <option value="video">Video</option>
                        <option value="audio">Audio</option>
                        <option value="integer">Integer</option>
                        <option value="email">Email</option>
                        <option value="tel">Telephone</option>
                    </select>
                </div>
            </div>
            
            <div class="w3-margin-top" id="options-${questionCount}" style="display:none;">
                <input type="text" class="w3-input w3-border" name="questions[${questionCount}][options]" placeholder="Options (comma separated)">
            </div>
            
            <div class="w3-margin-top">
                <label>
                    <input type="checkbox" name="questions[${questionCount}][required]" value="1">
                    Required
                </label>
            </div>
            
            <button type="button" class="w3-button w3-red w3-small w3-margin-top" onclick="removeQuestion(${questionCount})">
                <i class="fa fa-trash"></i> Remove
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', questionHtml);
}

function removeQuestion(id) {
    const element = document.getElementById(`question-${id}`);
    element.remove();
}

function toggleOptions(id) {
    const select = document.querySelector(`select[name="questions[${id}][type]"]`);
    const optionsDiv = document.getElementById(`options-${id}`);
    
    if (['radio', 'checkbox', 'matrix'].includes(select.value)) {
        optionsDiv.style.display = 'block';
    } else {
        optionsDiv.style.display = 'none';
    }
}

// Add first question by default
addQuestion();
</script>

<?php include 'footer.php'; ?>