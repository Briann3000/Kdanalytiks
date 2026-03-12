<?php
include 'header.php';

// Check if organization is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'organization') {
    header('Location: organization-login.php?error=Please login first');
    exit();
}

$org = R::findOne('organizations', ' user_id = ? ', [$_SESSION['user']['id']]);
$surveys = R::find('surveys', ' organization_id = ? ORDER BY created_at DESC', [$org->id]);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-list"></i> My Surveys</h2>
    
    <div class="w3-margin-bottom">
        <a href="organization-survey-builder.php" class="w3-button w3-blue w3-round">
            <i class="fa fa-plus"></i> Create New Survey
        </a>
    </div>
    
    <?php if (count($surveys) > 0): ?>
        <div class="w3-responsive">
            <table class="w3-table w3-bordered w3-striped w3-white w3-card">
                <thead>
                    <tr class="w3-blue">
                        <th>Title</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($surveys as $survey): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($survey->title); ?></td>
                            <td><?php echo htmlspecialchars($survey->category); ?></td>
                            <td><?php echo htmlspecialchars($survey->type); ?></td>
                            <td>
                                <?php if ($survey->status == 'active'): ?>
                                    <span class="w3-tag w3-green">Active</span>
                                <?php else: ?>
                                    <span class="w3-tag w3-yellow">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($survey->created_at)); ?></td>
                            <td>
                                <a href="organization-responses.php?survey_id=<?php echo $survey->id; ?>" class="w3-button w3-small w3-blue">View Responses</a>
                                <a href="organization-reports.php?survey_id=<?php echo $survey->id; ?>" class="w3-button w3-small w3-green">Reports</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="w3-panel w3-yellow w3-round">
            <p>You haven't created any surveys yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>