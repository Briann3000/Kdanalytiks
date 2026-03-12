<?php include 'header.php'; ?>

<?php
// This is an index/guide page for the SurveyJS integration
$currentUser = $_SESSION['admin_logged_in'] ?? ($_SESSION['user']['role'] ?? 'guest');
?>

<div class="w3-container w3-padding">
    <div class="w3-card w3-blue w3-padding w3-round w3-margin-bottom">
        <h2><i class="fa fa-star"></i> jQuery Form Builder Integration Guide</h2>
        <p>Free drag-and-drop survey builder with JSON export</p>
    </div>

    <?php if ($currentUser === true || $currentUser === 'admin'): ?>
        <!-- Admin View -->
        <div class="w3-row-padding">
            <div class="w3-col l6 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h3 class="w3-text-blue"><i class="fa fa-database"></i> Step 1: Database Migration</h3>
                    <p>Add required columns to the database (one-time setup)</p>
                    <p><strong>Status:</strong> Required before using jQuery Form Builder</p>
                    <a href="migrate-add-json-schema.php" class="w3-button w3-blue w3-margin-top">Run Migration →</a>
                </div>
            </div>

            <div class="w3-col l6 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h3 class="w3-text-blue"><i class="fa fa-pen"></i> Step 2: Create Survey</h3>
                    <p>Use drag-and-drop builder to create professional surveys</p>
                    <p><strong>For:</strong> Public surveys and admin-created surveys</p>
                    <a href="admin-survey-builder-json.php" class="w3-button w3-blue w3-margin-top">Open Builder →</a>
                </div>
            </div>
        </div>

        <div class="w3-row-padding">
            <div class="w3-col l6 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h3 class="w3-text-blue"><i class="fa fa-lightbulb"></i> Step 3: View Examples</h3>
                    <p>See 4 pre-built survey templates with explanations</p>
                    <p><strong>Includes:</strong> Customer, Employee, Product, Market surveys</p>
                    <a href="surveyjs-examples.php" class="w3-button w3-blue w3-margin-top">View Examples →</a>
                </div>
            </div>

            <div class="w3-col l6 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h3 class="w3-text-blue"><i class="fa fa-code"></i> Step 4: API Reference</h3>
                    <p>Integrate with other systems via REST API</p>
                    <p><strong>Endpoints:</strong> Get, save, submit, list surveys</p>
                    <a href="api-survey-json.php?action=list" class="w3-button w3-blue w3-margin-top">View API →</a>
                </div>
            </div>
        </div>

    <?php elseif ($currentUser === 'independent'): ?>
        <!-- Researcher View -->
        <div class="w3-card w3-white w3-padding w3-round w3-margin-bottom">
            <h3 class="w3-text-purple"><i class="fa fa-pen"></i> Create Your Survey</h3>
            <p>Use the professional drag-and-drop builder to create surveys</p>
            <a href="independent-survey-builder-json.php" class="w3-button w3-purple w3-margin-top w3-large">
                Open Survey Builder
            </a>
        </div>

        <div class="w3-row-padding">
            <div class="w3-col l6 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h3 class="w3-text-purple"><i class="fa fa-lightbulb"></i> View Examples</h3>
                    <p>See example surveys and templates to get started</p>
                    <a href="surveyjs-examples.php" class="w3-button w3-purple w3-margin-top">View Examples →</a>
                </div>
            </div>

            <div class="w3-col l6 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h3 class="w3-text-purple"><i class="fa fa-book"></i> Documentation</h3>
                    <p>Read the complete setup and integration guide</p>
                    <a href="SETUP-SURVEYJS.md" class="w3-button w3-purple w3-margin-top">Read Docs →</a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Public/Guest View -->
        <div class="w3-card w3-white w3-padding w3-round w3-margin-bottom">
            <h3 class="w3-text-blue">SurveyJS Integration Documentation</h3>
            <p>This site uses SurveyJS Designer for professional survey creation and management.</p>
            <p><strong>Features:</strong></p>
            <ul>
                <li>✅ Drag-and-drop survey builder</li>
                <li>✅ 20+ question types</li>
                <li>✅ Conditional logic</li>
                <li>✅ Mobile responsive</li>
                <li>✅ JSON export/import</li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Documentation Links -->
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">Documentation & Resources</h3>
        <div class="w3-row-padding">
            <div class="w3-col l3 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h4><i class="fa fa-rocket"></i> Quick Start</h4>
                    <p>Get up and running in 5 minutes</p>
                    <a href="SETUP-SURVEYJS.md" class="w3-button w3-blue w3-small w3-block">Read</a>
                </div>
            </div>

            <div class="w3-col l3 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h4><i class="fa fa-book"></i> Full Guide</h4>
                    <p>Complete integration documentation</p>
                    <a href="SURVEYJS-INTEGRATION.md" class="w3-button w3-blue w3-small w3-block">Read</a>
                </div>
            </div>

            <div class="w3-col l3 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h4><i class="fa fa-cubes"></i> Examples</h4>
                    <p>4 pre-built survey templates</p>
                    <a href="surveyjs-examples.php" class="w3-button w3-blue w3-small w3-block">View</a>
                </div>
            </div>

            <div class="w3-col l3 w3-margin-bottom">
                <div class="w3-card w3-white w3-padding w3-round">
                    <h4><i class="fa fa-cogs"></i> Implementation</h4>
                    <p>Summary of changes made</p>
                    <a href="IMPLEMENTATION-SUMMARY.md" class="w3-button w3-blue w3-small w3-block">Read</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links Table -->
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">All Files & Endpoints</h3>
        <table class="w3-table w3-bordered w3-striped">
            <tr style="background-color: #0066cc; color: white;">
                <th>File/Endpoint</th>
                <th>Purpose</th>
                <th>Access</th>
            </tr>

            <tr style="background-color: #e8f4f8;">
                <td><strong>Setup</strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>migrate-add-json-schema.php</td>
                <td>Database migration (adds json_schema column)</td>
                <td>Admin only</td>
            </tr>

            <tr style="background-color: #e8f4f8;">
                <td><strong>Builders</strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>admin-survey-builder-json.php</td>
                <td>Create public surveys (admin)</td>
                <td>Admin only</td>
            </tr>
            <tr>
                <td>independent-survey-builder-json.php</td>
                <td>Create surveys (researcher)</td>
                <td>Researchers</td>
            </tr>

            <tr style="background-color: #e8f4f8;">
                <td><strong>Rendering & API</strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>survey-render-json.php?id=1</td>
                <td>Render survey from JSON for respondents</td>
                <td>Public</td>
            </tr>
            <tr>
                <td>api-survey-json.php</td>
                <td>REST API for survey operations</td>
                <td>Authenticated</td>
            </tr>

            <tr style="background-color: #e8f4f8;">
                <td><strong>Documentation</strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>SETUP-SURVEYJS.md</td>
                <td>Quick start guide</td>
                <td>Everyone</td>
            </tr>
            <tr>
                <td>SURVEYJS-INTEGRATION.md</td>
                <td>Complete integration guide</td>
                <td>Everyone</td>
            </tr>
            <tr>
                <td>IMPLEMENTATION-SUMMARY.md</td>
                <td>Summary of changes and features</td>
                <td>Everyone</td>
            </tr>
            <tr>
                <td>surveyjs-examples.php</td>
                <td>4 example surveys with templates</td>
                <td>Everyone</td>
            </tr>
        </table>
    </div>

    <!-- Implementation Checklist -->
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">Implementation Checklist</h3>
        <div class="w3-card w3-white w3-padding w3-round">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 30px;">
                        <input type="checkbox" id="check1" disabled>
                    </td>
                    <td>
                        <label for="check1"><strong>1. Run Database Migration</strong></label><br>
                        <small>Visit: <code>migrate-add-json-schema.php</code></small>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" id="check2" disabled></td>
                    <td>
                        <label for="check2"><strong>2. Create Test Survey</strong></label><br>
                        <small>Use: <code>admin-survey-builder-json.php</code></small>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" id="check3" disabled></td>
                    <td>
                        <label for="check3"><strong>3. Activate Survey</strong></label><br>
                        <small>Change status from "draft" to "active"</small>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" id="check4" disabled></td>
                    <td>
                        <label for="check4"><strong>4. Test Taking Survey</strong></label><br>
                        <small>Go to public surveys and test your survey</small>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" id="check5" disabled></td>
                    <td>
                        <label for="check5"><strong>5. Review Documentation</strong></label><br>
                        <small>Read: <code>SETUP-SURVEYJS.md</code> and <code>SURVEYJS-INTEGRATION.md</code></small>
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" id="check6" disabled></td>
                    <td>
                        <label for="check6"><strong>6. Train Team</strong></label><br>
                        <small>Show team members how to use new builder</small>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Key Features -->
    <div class="w3-margin-top">
        <h3 class="w3-text-grey">Key Features</h3>
        <div class="w3-row-padding">
            <div class="w3-col l4 w3-margin-bottom">
                <div class="w3-card w3-padding w3-round" style="border-left: 4px solid #4CAF50;">
                    <h4><i class="fa fa-mouse"></i> Drag-and-Drop</h4>
                    <p>Create surveys without any coding</p>
                </div>
            </div>
            <div class="w3-col l4 w3-margin-bottom">
                <div class="w3-card w3-padding w3-round" style="border-left: 4px solid #2196F3;">
                    <h4><i class="fa fa-questions"></i> 20+ Question Types</h4>
                    <p>Text, rating, ranking, matrix, conditional, and more</p>
                </div>
            </div>
            <div class="w3-col l4 w3-margin-bottom">
                <div class="w3-card w3-padding w3-round" style="border-left: 4px solid #FF9800;">
                    <h4><i class="fa fa-code"></i> JSON Export</h4>
                    <p>Surveys stored as JSON for portability</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="w3-panel w3-blue w3-round w3-margin-top">
        <h4>ℹ️ About SurveyJS</h4>
        <p>
            SurveyJS is a professional, open-source JavaScript survey library used by Fortune 500 companies.
            It provides enterprise-grade survey creation and administration capabilities while remaining
            free and easy to integrate.
        </p>
        <p>
            <strong>Official Website:</strong> <a href="https://surveyjs.io/" target="_blank"
                style="color: white; text-decoration: underline;">https://surveyjs.io/</a>
        </p>
    </div>

</div>

<?php include 'footer.php'; ?>