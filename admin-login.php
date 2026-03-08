<?php include 'header.php'; ?>

<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Find admin user
    $admin = R::findOne('users', ' email = ? AND role = ? ', [$email, 'admin']);
    
    if ($admin && password_verify($password, $admin->password)) {
        if ($admin->status == 'active') {
            // Clear any existing session data
            session_unset();
            
            // Set admin session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin->id;
            $_SESSION['admin_name'] = $admin->name;
            $_SESSION['admin_email'] = $admin->email;
            $_SESSION['admin_role'] = $admin->role;
            $_SESSION['login_time'] = time();
            
            // Redirect to admin dashboard
            header('Location: admin-dashboard.php');
            exit();
        } else {
            $error = "Your account is not active";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>

<div class="w3-container w3-padding" style="max-width:500px;margin:auto;">
    <div class="w3-card w3-white w3-padding w3-round">
        <h2 class="w3-text-blue w3-center"><i class="fa fa-user-shield"></i> Admin Login</h2>
        
        <?php if (isset($error)): ?>
            <div class="w3-panel w3-red w3-round">
                <?php echo htmlspecialchars($error); ?>
            </div>
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
                <button class="w3-button w3-blue w3-round w3-block" type="submit">
                    <i class="fa fa-sign-in-alt"></i> Login
                </button>
            </p>
        </form>
        
        <div class="w3-panel w3-blue w3-round w3-margin-top">
            <p><strong>Default Admin Credentials:</strong></p>
            <p>Email: admin@kmsurveytool.com</p>
            <p>Password: Kenya@254</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>