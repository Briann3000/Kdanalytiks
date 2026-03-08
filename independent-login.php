<?php
include 'header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $user = R::findOne('users', ' email = ? AND role = ? ', [$email, 'independent']);
    
    if ($user && password_verify($password, $user->password)) {
        if ($user->status == 'active') {
            $_SESSION['user'] = $user->export();
            header('Location: independent-dashboard.php');
            exit();
        } else {
            $message = 'Your account is pending approval';
        }
    } else {
        $message = 'Invalid email or password';
    }
}


?>

<div class="w3-container w3-padding" style="max-width:500px;margin:auto;">
    <div class="w3-card w3-white w3-padding w3-round">
        <h2 class="w3-text-purple w3-center"><i class="fa fa-user-graduate"></i> Researcher Login</h2>
        
        <?php if ($message): ?>
            <div class="w3-panel w3-red w3-round"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <p>
                <label>Email</label>
                <input class="w3-input w3-border" type="email" name="email" required>
            </p>
            <p>
                <label>Password</label>
                <input class="w3-input w3-border" type="password" name="password" required>
            </p>
            <p>
                <button class="w3-button w3-purple w3-round w3-block" type="submit">
                    <i class="fa fa-sign-in-alt"></i> Login
                </button>
            </p>
        </form>
        
        <p class="w3-center">
            Don't have an account? <a href="independent-register.php" class="w3-text-purple">Register here</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>