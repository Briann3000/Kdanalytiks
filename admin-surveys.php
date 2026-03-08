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

$surveys = R::getAll('SELECT s.*, o.name as organization_name FROM surveys s JOIN organizations o ON s.organization_id = o.id');

?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-list"></i> Survey Management</h2>
    
    <div class="w3-responsive">
        <table class="w3-table w3-bordered w3-striped w3-white w3-card">
            <thead>
                <tr class="w3-blue">
                    <th>ID</th>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($surveys as $survey): ?>
                    <tr>
                        <td><?php echo $survey['id']; ?></td>
                        <td><?php echo htmlspecialchars($survey['title']); ?></td>
                        <td><?php echo htmlspecialchars($survey['organization_name']); ?></td>
                        <td><?php echo htmlspecialchars($survey['category']); ?></td>
                        <td><?php echo htmlspecialchars($survey['type']); ?></td>
                        <td>
                            <?php if ($survey['status'] == 'active'): ?>
                                <span class="w3-tag w3-green">Active</span>
                            <?php else: ?>
                                <span class="w3-tag w3-yellow">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($survey['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>