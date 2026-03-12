<?php
include 'header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
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
            $user->role = 'respondent';
            $user->status = 'active';
            R::store($user);
            
            $message = 'Registration successful! You can now login.';
        }
    }
}


?>

<div class="w3-container w3-padding" style="max-width:600px;margin:auto;">
    <div class="w3-card w3-white w3-padding w3-round">
        <h2 class="w3-text-blue w3-center"><i class="fa fa-user-plus"></i> Respondent Registration</h2>
        
        <?php if ($message): ?>
            <div class="w3-panel w3-blue w3-round"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <p>
                <label>Full Name</label>
                <input class="w3-input w3-border" type="text" name="name" required>
            </p>
            <p>
                <label>Email</label>
                <input class="w3-input w3-border" type="email" name="email" required>
            </p>
            <p>
                <label>Age</label>
                <input class="w3-input w3-border" type="number" name="age" min="10" max="120" required>
            </p>
            <p>
                <label>Gender</label>
                <select class="w3-select w3-border" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                    <option value="Prefer not to say">Prefer not to say</option>
                </select>
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
            Already have an account? <a href="respondent-login.php" class="w3-text-blue">Login here</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>