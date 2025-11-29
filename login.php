<?php
require_once 'includes/config.php';

// Check if user is already logged in, redirect them
if (is_logged_in()) {
    if (is_admin()) {
        header("location: admin/dashboard.php");
    } else {
        header("location: index.php");
    }
    exit;
}

$username_or_email = $password = "";
$login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username/email is empty
    if (empty(trim($_POST["username_or_email"]))) {
        $login_err = "Please enter username or email.";
    } else {
        $username_or_email = trim($_POST["username_or_email"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $login_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($login_err)) {
        // Prepare a select statement to find user by username OR email
        $sql = "SELECT id, username, password, role FROM users WHERE username = ? OR email = ?";
        
        if ($result = secure_query($sql, 'ss', [$username_or_email, $username_or_email])) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $hashed_password = $row['password'];

                if (password_verify($password, $hashed_password)) {
                    // Password is correct, start a new session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $row['id'];
                    $_SESSION["username"] = $row['username'];
                    $_SESSION["role"] = $row['role'];

                    // Redirect user based on role
                    if ($_SESSION["role"] === 'admin') {
                        header("location: admin/dashboard.php");
                    } else {
                        header("location: index.php");
                    }
                } else {
                    $login_err = "Invalid username/email or password.";
                }
            } else {
                $login_err = "Invalid username/email or password.";
            }
            $result->free();
        } else {
            $login_err = "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Online Store</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card auth-card">
                    <div class="card-header auth-header">
                        <h2 class="mb-0">🔑 User Login</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Please fill in your credentials to login.</p>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group mb-3">
                                <label for="username_or_email">Username or Email</label>
                                <input type="text" id="username_or_email" name="username_or_email" class="form-control auth-input <?php echo (!empty($login_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username_or_email; ?>">
                                <span class="invalid-feedback"><?php echo $login_err; ?></span>
                            </div>    
                            <div class="form-group mb-4">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control auth-input">
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-block btn-action-green" value="Login">
                            </div>
                            <p class="text-center mt-4">
                                Don't have an account? <a href="register.php" class="link-signup">Sign up now</a>.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>