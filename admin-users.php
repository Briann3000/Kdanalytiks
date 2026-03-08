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

// Handle user status toggle
if (isset($_GET['toggle'])) {
    $userId = $_GET['toggle'];
    $user = R::load('users', $userId);
    if ($user->id) {
        $user->status = ($user->status == 'active') ? 'pending' : 'active';
        R::store($user);
        header('Location: admin-users.php');
        exit();
    }
}

$users = R::findAll('users');

?>

<div class="w3-container w3-padding">
    <h2 class="w3-text-blue"><i class="fa fa-users"></i> User Management</h2>
    
    <div class="w3-responsive">
        <table class="w3-table w3-bordered w3-striped w3-white w3-card">
            <thead>
                <tr class="w3-blue">
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user->id; ?></td>
                        <td><?php echo htmlspecialchars($user->name); ?></td>
                        <td><?php echo htmlspecialchars($user->email); ?></td>
                        <td>
                            <?php
                            $roleClass = '';
                            $roleIcon = '';
                            switch($user->role) {
                                case 'admin':
                                    $roleClass = 'w3-blue';
                                    $roleIcon = 'fa-user-shield';
                                    break;
                                case 'organization':
                                    $roleClass = 'w3-green';
                                    $roleIcon = 'fa-building';
                                    break;
                                case 'independent':
                                    $roleClass = 'w3-purple';
                                    $roleIcon = 'fa-user-graduate';
                                    break;
                                case 'respondent':
                                    $roleClass = 'w3-orange';
                                    $roleIcon = 'fa-user';
                                    break;
                            }
                            ?>
                            <span class="w3-tag <?php echo $roleClass; ?>">
                                <i class="fa <?php echo $roleIcon; ?>"></i> <?php echo ucfirst($user->role); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user->status == 'active'): ?>
                                <span class="w3-tag w3-green">Active</span>
                            <?php else: ?>
                                <span class="w3-tag w3-red">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user->created_at)); ?></td>
                        <td>
                            <a href="admin-users.php?toggle=<?php echo $user->id; ?>" class="w3-button w3-small w3-blue">
                                <?php echo ($user->status == 'active') ? 'Deactivate' : 'Activate'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>