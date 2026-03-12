<?php
require_once 'functions.php';

handle_remember_me();

include 'header.php';

$error = login_admin('admin-dashboard.php');
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
            <p>
                <input class="w3-check" type="checkbox" name="remember_me" id="remember_me" value="1">
                <label for="remember_me">Keep me logged in</label>
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