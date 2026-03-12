<?php
// Common functions for login and logout

function handle_remember_me()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remember_me'])) {
        ini_set('session.cookie_lifetime', 30 * 24 * 3600);
    }
}

function login_user($role, $redirect_url, $status_message = 'Your account is not active')
{
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $user = R::findOne('users', ' email = ? AND role = ? ', [$email, $role]);

        if ($user && password_verify($password, $user->password)) {
            if ($user->status == 'active') {
                if (isset($_POST['remember_me'])) {
                    $_SESSION['remember_me'] = true;
                }
                $_SESSION['user'] = $user->export();
                header("Location: $redirect_url");
                exit();
            } else {
                $message = $status_message;
            }
        } else {
            $message = 'Invalid email or password';
        }
    }
    return $message;
}

function login_admin($redirect_url)
{
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $admin = R::findOne('users', ' email = ? AND role = ? ', [$email, 'admin']);

        if ($admin && password_verify($password, $admin->password)) {
            if ($admin->status == 'active') {
                if (isset($_POST['remember_me'])) {
                    $_SESSION['remember_me'] = true;
                }
                session_unset();

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin->id;
                $_SESSION['admin_name'] = $admin->name;
                $_SESSION['admin_email'] = $admin->email;
                $_SESSION['admin_role'] = $admin->role;
                $_SESSION['login_time'] = time();

                header("Location: $redirect_url");
                exit();
            } else {
                $error = "Your account is not active";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
    return $error;
}

function logout()
{
    session_start();
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600);
}
?>