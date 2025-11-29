<?php
require_once 'includes/config.php';

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

// 🔑 New variables for Photo upload
$photo = $photo_err = "";
$target_dir = "assets/img/"; // Target directory for uploaded photos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validate username (existing logic remains)
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($result = secure_query($sql, 's', [trim($_POST["username"])])) {
            if ($result->num_rows == 1) {
                $username_err = "This username is already taken.";
            } else {
                $username = trim($_POST["username"]);
            }
            $result->free();
        }
    }

    // 2. Validate email (existing logic remains)
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($result = secure_query($sql, 's', [trim($_POST["email"])])) {
            if ($result->num_rows == 1) {
                $email_err = "This email is already registered.";
            } else {
                $email = trim($_POST["email"]);
            }
            $result->free();
        }
    }

    // 3. Validate password (existing logic remains)
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // 4. Validate confirm password (existing logic remains)
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // 🔑 5. Handle Photo Upload
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpeg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];
        $filename = $_FILES["photo"]["name"];
        $filetype = $_FILES["photo"]["type"];
        $filesize = $_FILES["photo"]["size"];

        // Verify file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            $photo_err = "Error: Please select a valid file format (JPG, JPEG, PNG, GIF).";
        }

        // Verify file size - max 5MB
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($filesize > $max_size) {
            $photo_err = "Error: File size must be less than 5MB.";
        }

        // If no upload errors, attempt to save the file
        if (empty($photo_err)) {
            // Create a unique filename
            $new_filename = uniqid('user_') . '.' . $ext;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $photo = $new_filename;
            } else {
                $photo_err = "Error: There was an error moving the uploaded file.";
            }
        }
    } else {
        // Use default photo if no file uploaded
        $photo = 'default.png'; 
    }

    // 6. Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($photo_err)) {
        // 🔑 Updated SQL to include 'photo' and 'role'
        $sql = "INSERT INTO users (username, email, password, photo, role) VALUES (?, ?, ?, ?, ?)";
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user'; // Default role for standard registration
        
        // 🔑 Updated parameters to include $photo and $role (5 's' type strings)
        if (secure_query($sql, 'sssss', [$username, $email, $hashed_password, $photo, $role])) {
            header("location: login.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later. Database error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Online Store</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
                        <div class="col-12 col-sm-10 col-md-8 col-lg-6"> 
                <div class="card auth-card">
                    <div class="card-header auth-header auth-header-blue">
                        <h2 class="mb-0">📝 User Registration</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Please fill this form to create an account.</p>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="form-group mb-3">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control auth-input <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>    
                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control auth-input <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                            <div class="form-group mb-3">
                                <label>Profile Photo (Max 5MB)</label>
                                <input type="file" name="photo" class="form-control-file <?php echo (!empty($photo_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $photo_err; ?></span>
                            </div>
                            <div class="form-group mb-3">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control auth-input <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group mb-4">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control auth-input <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-block btn-action-blue" value="Register">
                            </div>
                            <p class="text-center mt-4">
                                Already have an account? <a href="login.php" class="link-signup">Login here</a>.
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
