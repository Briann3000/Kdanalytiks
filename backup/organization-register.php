<?php
include 'header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        $message = 'Passwords do not match';
    } else {
        // Check if email exists
        $existing = R::findOne('users', ' email = ? ', [$email]);
        if ($existing) {
            $message = 'Email already registered';
        } else {
            // Create user
            $user = R::dispense('users');
            $user->name = $name;
            $user->email = $email;
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->role = 'organization';
            $user->status = 'pending';
            R::store($user);
            
            // Create organization record
            $org = R::dispense('organizations');
            $org->user_id = $user->id;
            $org->name = $name;
            R::store($org);
            
            $message = 'Registration successful! Please wait for admin approval.';
        }
    }
}


?>

<div class="w3-container w3-padding" style="max-width:600px;margin:auto;">
    <div class="w3-card w3-white w3-padding w3-round">
        <h2 class="w3-text-blue w3-center"><i class="fa fa-building"></i> Organization Registration</h2>
        
        <?php if ($message): ?>
            <div class="w3-panel w3-blue w3-round"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <p>
                <label>Organization Name</label>
                <input class="w3-input w3-border" type="text" name="name" required>
            </p>
            <p>
                <label>Email</label>
                <input class="w3-input w3-border" type="email" name="email" required>
            </p>
            <p>
                <label>Password</label>
                <input class="w3-input w3-border" type="password" name="password" required>
            </p>
            <p>
                <label>Confirm Password</label>
                <input class="w3-input w3-border" type="password" name="confirm_password" required>
            </p>
            <p>
                <button class="w3-button w3-blue w3-round w3-block" type="submit">
                    <i class="fa fa-user-plus"></i> Register
                </button>
            </p>
        </form>
        
        <p class="w3-center">
            Already have an account? <a href="organization-login.php" class="w3-text-blue">Login here</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>