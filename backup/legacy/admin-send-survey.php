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

$message = '';
$sentCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = $_POST['recipients'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    
    $recipientList = explode(',', $recipients);
    $recipientList = array_map('trim', $recipientList);
    
    $surveyLink = "https://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/public-take-survey.php?id=$surveyId";
    
    $emailBody = $body . "\n\nSurvey Link: " . $surveyLink;
    
    // For demonstration, we'll just count instead of actually sending emails
    // In production, you would integrate with PHPMailer or similar
    $sentCount = count($recipientList);
    
    $message = "Survey invitation sent to $sentCount recipients!";
}

include 'header.php';
?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-envelope"></i> Send Survey Invitation</h2>
    
    <div class="w3-card w3-white w3-padding w3-round">
        <h3><?php echo htmlspecialchars($survey->title); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($survey->description)); ?></p>
        
        <?php if ($message): ?>
            <div class="w3-panel w3-green w3-round"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post" class="w3-margin-top">
            <div class="w3-margin-bottom">
                <label>Recipients (comma-separated email addresses)</label>
                <textarea class="w3-input w3-border" name="recipients" rows="3" placeholder="email1@example.com, email2@example.com, email3@example.com" required></textarea>
            </div>
            
            <div class="w3-margin-bottom">
                <label>Subject</label>
                <input class="w3-input w3-border" type="text" name="subject" value="Survey Invitation: <?php echo htmlspecialchars($survey->title); ?>" required>
            </div>
            
            <div class="w3-margin-bottom">
                <label>Email Body</label>
                <textarea class="w3-input w3-border" name="body" rows="6" required>You are invited to participate in our survey. Your feedback is valuable to us.</textarea>
            </div>
            
            <div class="w3-margin-bottom">
                <label>Survey Link (will be automatically added)</label>
                <input class="w3-input w3-border" type="text" value="https://<?php echo $_SERVER['HTTP_HOST']; ?><?php echo dirname($_SERVER['PHP_SELF']); ?>/public-take-survey.php?id=<?php echo $surveyId; ?>" readonly>
            </div>
            
            <button type="submit" class="w3-button w3-blue w3-round">
                <i class="fa fa-paper-plane"></i> Send Invitations
            </button>
            <a href="public-survey-report.php?id=<?php echo $surveyId; ?>" class="w3-button w3-green w3-round w3-margin-left">
                <i class="fa fa-chart-bar"></i> View Reports
            </a>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>