<?php
require_once 'includes/config.php';

// Redirect non-logged-in users or admins
if (!is_logged_in() || is_admin()) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$user = null;
$orders_data = []; // Holds order records
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

// --- Handle Batch Order Deletion (FORCE DELETE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_selected' && isset($_POST['selected_orders'])) {
    
    // Sanitize the array of IDs
    $orders_to_delete = array_map('intval', $_POST['selected_orders']);
    $deleted_count = 0;
    
    foreach ($orders_to_delete as $order_to_delete) {
        
        // Check ownership only before deleting
        $sql_check_ownership = "SELECT id FROM orders WHERE id = ? AND user_id = ?";
        if ($result_check = secure_query($sql_check_ownership, 'ii', [$order_to_delete, $user_id])) {
            if ($result_check->num_rows == 1) {
                
                // Delete associated order items first
                $sql_delete_items = "DELETE FROM order_items WHERE order_id = ?";
                if (secure_query($sql_delete_items, 'i', [$order_to_delete])) {
                    // Delete the order itself
                    $sql_delete_order = "DELETE FROM orders WHERE id = ?";
                    if (secure_query($sql_delete_order, 'i', [$order_to_delete])) {
                        $deleted_count++;
                    }
                }
            }
        }
    }
    
    if ($deleted_count > 0) {
        $message = '<div class="alert alert-success">Successfully **DELETED** '.$deleted_count.' order(s) permanently.</div>';
    } elseif (!empty($orders_to_delete)) {
        $message = '<div class="alert alert-danger">Error: Could not delete the selected orders, or you do not have permission.</div>';
    } else {
         $message = '<div class="alert alert-warning">No orders were selected for deletion.</div>';
    }
}
// --- END: Handle Batch Order Deletion (FORCE DELETE) ---


// 1. Fetch user details
$sql_user = "SELECT username, email, photo, created_at FROM users WHERE id = ?";
if ($result_user = secure_query($sql_user, 'i', [$user_id])) {
    $user = $result_user->fetch_assoc();
    $result_user->free();
}

// 2. Fetch all orders with concatenated product names
// Assuming product title column is 'title' in the 'products' table.
$sql_orders = "
    SELECT 
        o.id AS order_id, 
        o.order_date, 
        o.status, 
        SUM(oi.quantity * oi.unit_price) AS total_amount,
        GROUP_CONCAT(p.title SEPARATOR ' | ') AS product_titles
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? 
    GROUP BY o.id, o.order_date, o.status
    ORDER BY o.order_date DESC
";

if ($result_orders = secure_query($sql_orders, 'i', [$user_id])) {
    while ($order = $result_orders->fetch_assoc()) {
        $orders_data[] = $order;
    }
    $result_orders->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile and Orders</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <h2 class="mb-4">👤 My Account & Orders</h2>
        
        <?php echo $message; // Displaying the message variable ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        Profile Information
                    </div>
                    <div class="card-body text-center">
                        <img src="assets/img/<?php echo htmlspecialchars($user['photo'] ?? 'default.png'); ?>" class="rounded-circle mb-3 mx-auto d-block" style="width: 100px; height: 100px; object-fit: cover;" alt="User Photo">
                        <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <p class="card-text">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="card-text text-muted"><small>Member Since: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></small></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-secondary text-white">
                        My Orders
                    </div>
                    <div class="card-body">
                        <?php if (!empty($orders_data)): ?>
                            <form method="post" action="user_profile.php" onsubmit="return confirm('WARNING: Are you sure you want to permanently DELETE the selected orders? This action cannot be undone.');">
                            <input type="hidden" name="action" value="delete_selected">
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th><input type="checkbox" id="select-all" title="Select All"></th>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Products</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders_data as $order): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="selected_orders[]" value="<?php echo $order['order_id']; ?>">
                                                </td>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($order['product_titles']); ?></small></td>
                                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td><span class="badge badge-<?php 
                                                    echo $order['status'] == 'Delivered' ? 'success' : 
                                                         ($order['status'] == 'Shipped' ? 'info' : 
                                                         ($order['status'] == 'Canceled' ? 'danger' : 'warning'));
                                                    ?>"><?php echo $order['status']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right mt-3">
                                <button type="submit" class="btn btn-danger" id="batch-delete-btn" disabled>Delete Selected Orders</button>
                            </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">You have not placed any orders yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('input[name="selected_orders[]"]');
            const deleteButton = document.getElementById('batch-delete-btn');

            function updateDeleteButton() {
                const checkedCount = document.querySelectorAll('input[name="selected_orders[]"]:checked').length;
                deleteButton.disabled = checkedCount === 0;
                deleteButton.textContent = checkedCount > 0 ? `Delete Selected Orders (${checkedCount})` : 'Delete Selected Orders';
            }

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateDeleteButton();
            });

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeleteButton();
                    if (!this.checked) {
                        selectAll.checked = false;
                    } else {
                        // If all non-disabled are checked, check select-all
                        if (document.querySelectorAll('input[name="selected_orders[]"]:not(:disabled)').length === document.querySelectorAll('input[name="selected_orders[]"]:checked').length) {
                            selectAll.checked = true;
                        }
                    }
                });
            });
            
             // Initial check
             updateDeleteButton();
        });
    </script>
    <?php include 'includes/footer.php'; ?>