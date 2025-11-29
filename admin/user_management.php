<?php
require_once '../includes/config.php';
// Access Control: Must be logged in and an Admin
if (!is_logged_in() || !is_admin()) { header("location: ../login.php"); exit; }

$message = "";

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['id']) {
        $message = '<div class="alert alert-danger">You cannot delete your own admin account.</div>';
    } else {
        // ON DELETE CASCADE on orders means user's orders will be deleted
        $sql_delete = "DELETE FROM users WHERE id = ?";
        if (secure_query($sql_delete, 'i', [$user_id])) {
            $message = '<div class="alert alert-success">User ID '.$user_id.' deleted successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: Could not delete user.</div>';
        }
    }
}

// Handle Promote/Demote Request
if (isset($_POST['action']) && $_POST['action'] == 'update_role' && isset($_POST['user_id'], $_POST['role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = trim($_POST['role']);
    
    // Prevent admin from changing their own role (optional but good practice)
    if ($user_id == $_SESSION['id']) {
        $message = '<div class="alert alert-danger">You cannot change your own role.</div>';
    } else {
        $sql_update = "UPDATE users SET role = ? WHERE id = ?";
        if (secure_query($sql_update, 'si', [$new_role, $user_id])) {
            $message = '<div class="alert alert-success">User ID '.$user_id.' role updated to **'.$new_role.'** successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating user role.</div>';
        }
    }
}


// Fetch all users
$sql_users = "SELECT id, username, email, role, created_at FROM users ORDER BY id ASC";
$result_users = secure_query($sql_users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>👥 User Management</h2>
            <a href="dashboard.php" class="btn btn-secondary">← Go Back to Dashboard</a>
        </div>
        <p class="lead">View, delete, and manage user roles.</p>
        <hr>

        <?php echo $message; ?>

        <?php if ($result_users && $result_users->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Member Since</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $roles = ['user', 'admin'];
                    while ($user = $result_users->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form method="post" action="user_management.php" class="form-inline">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="role" class="form-control form-control-sm mr-2" <?php echo ($user['id'] == $_SESSION['id']) ? 'disabled' : ''; ?>>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role; ?>" <?php echo ($user['role'] == $role) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($role); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-info" <?php echo ($user['id'] == $_SESSION['id']) ? 'disabled' : ''; ?>>Update</button>
                            </form>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                        <td>
                            <a href="user_management.php?action=delete&id=<?php echo $user['id']; ?>" 
                                class="btn btn-sm btn-danger" 
                                onclick="return confirm('WARNING: This will delete the user and all their orders. Are you sure?');"
                                <?php echo ($user['id'] == $_SESSION['id']) ? 'disabled' : ''; ?>>
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; $result_users->free(); ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info">No users registered yet.</div>
        <?php endif; ?>
    </div>
    <?php include '../includes/footer.php'; ?>