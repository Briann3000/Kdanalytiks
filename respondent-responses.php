<?php
include 'header.php';

// Check if respondent is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'respondent') {
    header('Location: respondent-login.php?error=Please login first');
    exit();
}

$responses = R::getAll('
    SELECT r.id, s.title, r.submitted_at 
    FROM responses r 
    JOIN surveys s ON r.survey_id = s.id 
    WHERE r.respondent_id = ? 
    ORDER BY r.submitted_at DESC
', [$_SESSION['user']['id']]);


?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-list"></i> My Survey Responses</h2>
    
    <?php if (count($responses) > 0): ?>
        <div class="w3-responsive">
            <table class="w3-table w3-bordered w3-striped w3-white w3-card">
                <thead>
                    <tr class="w3-blue">
                        <th>Survey</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $response): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($response['title']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($response['submitted_at'])); ?></td>
                            <td>
                                <a href="#" class="w3-button w3-small w3-blue">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="w3-panel w3-yellow w3-round">
            <p>You haven't completed any surveys yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>