<?php
require_once '../includes/config.php';

// Check if user is logged in AND is an admin
if (!is_logged_in() || !is_admin()) {
    header("location: ../login.php");
    exit;
}

// Fetch statistics
$total_products = secure_query("SELECT COUNT(id) AS count FROM products WHERE is_active = TRUE")->fetch_assoc()['count'] ?? 0;

// MODIFIED: Counts ALL orders to reflect the total number of records in the orders table.
$total_orders = secure_query("SELECT COUNT(id) AS count FROM orders")->fetch_assoc()['count'] ?? 0;

$total_users = secure_query("SELECT COUNT(id) AS count FROM users WHERE role = 'user'")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?> 
    <div class="container py-5">
        <h2>📊 Admin Dashboard</h2>
        <p class="lead">Welcome, **<?php echo htmlspecialchars($_SESSION["username"]); ?>**! Manage your store here.</p>
        <hr>

        <div class="row mb-5">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Active Products</h5>
                        <p class="card-text display-4"><?php echo $total_products; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <p class="card-text display-4"><?php echo $total_orders; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Registered Users</h5>
                        <p class="card-text display-4"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mb-4">Management Options</h3>
        <div class="row">
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="product_management.php" class="text-decoration-none">
                    <div class="card admin-card p-3 shadow-sm">
                        <h4>📦 Product Catalog</h4>
                        <p class="text-muted mb-0">Add, Edit, and Archive/View All products.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="order_management.php" class="text-decoration-none">
                    <div class="card admin-card p-3 shadow-sm">
                        <h4>🛒 Order Tracking</h4>
                        <p class="text-muted mb-0">View and manage customer orders and statuses.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="user_management.php" class="text-decoration-none">
                    <div class="card admin-card p-3 shadow-sm">
                        <h4>👥 User Accounts</h4>
                        <p class="text-muted mb-0">View, Delete, and Promote users.</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>