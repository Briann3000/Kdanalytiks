<?php include 'header.php'; ?>

<?php
// Check if independent is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'independent') {
    header('Location: independent-login.php?error=Please login first');
    exit();
}

$independent = R::findOne('independents', ' user_id = ? ', [$_SESSION['user']['id']]);

// Handle survey save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category = $_POST['category'];
        $type = $_POST['type'];
        $json_schema = $_POST['json_schema'];

        // Validate JSON
        $decoded = json_decode($json_schema);
        if ($decoded === null) {
            $error = 'Invalid survey JSON format';
        } else {
            $survey = R::dispense('surveys');
            $survey->independent_id = $independent->id;
            $survey->title = $title;
            $survey->description = $description;
            $survey->category = $category;
            $survey->type = $type;
            $survey->status = 'draft';
            $survey->json_schema = $json_schema;
            $survey->created_by = 'independent';
            R::store($survey);

            $success = 'Survey created successfully! ID: ' . $survey->id;
        }
    }
}
?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-purple"><i class="fa fa-plus-circle"></i> Survey Builder (jQuery Form Builder)</h2>
    <p>Create surveys using drag-and-drop interface</p>

    <?php if (isset($error)): ?>
        <div class="w3-panel w3-red w3-round"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="w3-panel w3-green w3-round"><?php echo $success; ?></div>
        <a href="independent-dashboard.php" class="w3-button w3-blue w3-margin-top">Back to Dashboard</a>
    <?php else: ?>

        <div class="w3-row-padding">
            <!-- Survey Configuration Panel -->
            <div class="w3-col l3 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h3>Survey Details</h3>
                    <form method="post" id="surveyForm">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="json_schema" id="json_schema" value="">

                        <div class="w3-margin-bottom">
                            <label><strong>Survey Title</strong></label>
                            <input class="w3-input w3-border" type="text" name="title" id="title" required>
                        </div>

                        <div class="w3-margin-bottom">
                            <label><strong>Description</strong></label>
                            <textarea class="w3-input w3-border" name="description" id="description" rows="4"></textarea>
                        </div>

                        <div class="w3-margin-bottom">
                            <label><strong>Category</strong></label>
                            <select class="w3-select w3-border" name="category" id="category" required>
                                <option value="">Select Category</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Academic">Academic</option>
                                <option value="Product">Product</option>
                                <option value="Political">Political</option>
                                <option value="Health">Health</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="w3-margin-bottom">
                            <label><strong>Survey Type</strong></label>
                            <select class="w3-select w3-border" name="type" id="type" required>
                                <option value="public">Public</option>
                                <option value="invitation">Invitation Only</option>
                            </select>
                        </div>

                        <button type="submit" class="w3-button w3-green w3-block w3-margin-top">
                            <i class="fa fa-save"></i> Save Survey
                        </button>
                        <a href="independent-dashboard.php" class="w3-button w3-red w3-block w3-margin-top">Cancel</a>
                    </form>
                </div>
            </div>

            <!-- Survey Builder Panel -->
            <div class="w3-col l9">
                <!-- Mode Toggle -->
                <div class="w3-card w3-white w3-padding w3-round w3-margin-bottom">
                    <div class="w3-button-group w3-center" role="group">
                        <button type="button" class="w3-button w3-blue mode-toggle" data-mode="visual"
                            onclick="switchMode('visual')"><i class="fa fa-paint-brush"></i> Visual Builder</button>
                        <button type="button" class="w3-button w3-light-gray mode-toggle" data-mode="json"
                            onclick="switchMode('json')"><i class="fa fa-code"></i> JSON Import</button>
                    </div>
                </div>

                <!-- Visual Builder Mode -->
                <div id="visualMode">
                    <div class="w3-row-padding w3-margin-bottom">
                        <div class="w3-col l6">
                            <div class="w3-card w3-white w3-padding w3-round">
                                <h5><i class="fa fa-wrench"></i> Builder</h5>
                                <div id="surveyCreatorContainer" style="min-height: 500px;"></div>
                            </div>
                        </div>
                        <div class="w3-col l6">
                            <div class="w3-card w3-white w3-padding w3-round">
                                <h5><i class="fa fa-eye"></i> Preview <span class="w3-small w3-text-grey">(Live)</span></h5>
                                <div id="visualPreview" class="w3-border w3-round w3-padding"
                                    style="min-height: 500px; background-color: #f9f9f9; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JSON Import Mode -->
                <div id="jsonMode" style="display: none;">
                    <div class="w3-row-padding w3-margin-bottom">
                        <div class="w3-col l6">
                            <div class="w3-card w3-white w3-padding w3-round">
                                <h5><i class="fa fa-code"></i> JSON Input</h5>
                                <p class="w3-small w3-text-grey">Enter valid JSON that conforms to form-builder format</p>
                                <textarea id="jsonInput" class="w3-input w3-border"
                                    placeholder='Paste JSON here...&#10;&#10;Example:&#10;{&#10;  "id": "form-0",&#10;  "class": "user-edit",&#10;  "name": "user-form",&#10;  "action": "/path/to/file",&#10;  "method": "POST",&#10;  "fields": [&#10;    {&#10;      "type": "text",&#10;      "label": "Question",&#10;      "name": "field-0",&#10;      "placeholder": "Answer here",&#10;      "required": true&#10;    }&#10;  ]&#10;}'
                                    rows="18" style="font-family: monospace;"></textarea>
                                <div class="w3-margin-top">
                                    <button type="button" class="w3-button w3-blue" onclick="validateAndLoadJSON()"><i
                                            class="fa fa-check"></i> Validate & Load</button>
                                    <button type="button" class="w3-button w3-red w3-margin-left" onclick="clearJSON()"><i
                                            class="fa fa-trash"></i> Clear</button>
                                </div>
                                <div id="jsonStatus" class="w3-margin-top"></div>
                            </div>
                        </div>
                        <div class="w3-col l6">
                            <div class="w3-card w3-white w3-padding w3-round">
                                <h5><i class="fa fa-eye"></i> Preview</h5>
                                <div id="jsonPreview" class="w3-border w3-round w3-padding"
                                    style="min-height: 500px; background-color: #f9f9f9; overflow-y: auto;">
                                    <p class="w3-small w3-text-grey w3-center" style="padding-top: 200px;">Preview will
                                        appear here after validation</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- SurveyJS Designer CSS -->
<link href="https://surveycss.azureedge.net/modern-2.4.18/survey.modern.min.css" rel="stylesheet" type="text/css" />

<!-- SurveyJS Creator CSS -->
<link href="https://surveycss.azureedge.net/survey-creator/survey-creator.modern.min.css" rel="stylesheet"
    type="text/css" />

<!-- jQuery Form Builder CSS -->
<link href="https://formbuilder.online/assets/css/form-builder.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://formbuilder.online/assets/css/form-render.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jQuery Form Builder JS -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-builder.min.js"></script>
<script src="https://formbuilder.online/assets/js/form-render.min.js"></script>

<script>
    let formBuilder = null;
    let currentMode = 'visual';

    jQuery(function ($) {
        // 1. Initialize the form builder
        formBuilder = $('#surveyCreatorContainer').formBuilder();

        // 2. Update preview on form builder changes
        $(document).on('change', '#surveyCreatorContainer', function () {
            updateVisualPreview();
        });

        // Update preview on form builder load
        setTimeout(function () {
            updateVisualPreview();
        }, 1000);

        // 3. Intercept the form submission
        $('#surveyForm').on('submit', function (e) {
            // Stop the form from submitting immediately
            e.preventDefault();

            let formDataJSON = '';

            if (currentMode === 'visual') {
                // Extract from visual builder
                formDataJSON = formBuilder.actions.getData('json');
            } else {
                // Use JSON from textarea
                formDataJSON = $('#jsonInput').val();

                // Validate JSON
                try {
                    JSON.parse(formDataJSON);
                } catch (e) {
                    alert('Invalid JSON: ' + e.message);
                    return false;
                }
            }

            // 4. Inject the JSON string into your hidden input field
            $('#json_schema').val(formDataJSON);

            // 5. Release the form so it actually submits to your PHP backend
            this.submit();
        });
    });

    // Update visual builder preview
    function updateVisualPreview() {
        try {
            if (!formBuilder) return;
            const formData = formBuilder.actions.getData('json');
            $('#visualPreview').html('');
            $('#visualPreview').formRender({
                formData: formData,
                dataType: 'json'
            });
        } catch (e) {
            console.log('Preview update error:', e);
        }
    }

    // Mode switching function
    function switchMode(mode) {
        currentMode = mode;

        if (mode === 'visual') {
            $('#visualMode').show();
            $('#jsonMode').hide();
            $('.mode-toggle[data-mode="visual"]').addClass('w3-blue').removeClass('w3-light-gray');
            $('.mode-toggle[data-mode="json"]').removeClass('w3-blue').addClass('w3-light-gray');
            setTimeout(updateVisualPreview, 100);
        } else {
            $('#visualMode').hide();
            $('#jsonMode').show();
            $('.mode-toggle[data-mode="json"]').addClass('w3-blue').removeClass('w3-light-gray');
            $('.mode-toggle[data-mode="visual"]').removeClass('w3-blue').addClass('w3-light-gray');
        }
    }

    // Validate and load JSON function
    function validateAndLoadJSON() {
        const jsonInput = document.getElementById('jsonInput').value.trim();
        const jsonStatus = document.getElementById('jsonStatus');

        if (!jsonInput) {
            jsonStatus.innerHTML = '<div class="w3-panel w3-red w3-round">Please enter JSON code</div>';
            return;
        }

        try {
            const parsed = JSON.parse(jsonInput);
            jsonStatus.innerHTML = '<div class="w3-panel w3-green w3-round"><i class="fa fa-check"></i> JSON is valid and ready to save!</div>';

            // Show preview
            $('#jsonPreview').html('');
            $('#jsonPreview').formRender({
                formData: parsed,
                dataType: 'json'
            });
        } catch (e) {
            jsonStatus.innerHTML = '<div class="w3-panel w3-red w3-round"><strong>Invalid JSON:</strong> ' + e.message + '</div>';
            $('#jsonPreview').html('<p class="w3-small w3-text-grey w3-center" style="padding-top: 200px;">Preview will appear here after validation</p>');
        }
    }

    // Clear JSON textarea
    function clearJSON() {
        document.getElementById('jsonInput').value = '';
        document.getElementById('jsonStatus').innerHTML = '';
        $('#jsonPreview').html('<p class="w3-small w3-text-grey w3-center" style="padding-top: 200px;">Preview will appear here after validation</p>');
    }
</script>

<?php include 'footer.php'; ?>