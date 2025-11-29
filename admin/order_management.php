<?php
require_once '../includes/config.php';
// Restrict access to logged-in admins only
if (!is_logged_in() || !is_admin()) { header("location: ../login.php"); exit; }

$message = "";

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['status']);
    
    // Validate status against ENUM values
    $valid_statuses = ['Pending', 'Shipped', 'Delivered', 'Canceled'];
    if (in_array($new_status, $valid_statuses)) {
        $sql_update = "UPDATE orders SET status = ? WHERE id = ?";
        if (secure_query($sql_update, 'si', [$new_status, $order_id])) {
            $message = '<div class="alert alert-success">Order #'.$order_id.' status updated to **'.$new_status.'** successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating order status.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Invalid status provided.</div>';
    }
}

// Fetch all orders with user details, total amount, AND the name of one product (Title)
$sql_orders = "
    SELECT 
        o.id AS order_id, 
        o.order_date, 
        o.status, 
        u.username,
        SUM(oi.quantity * oi.unit_price) AS total_amount,
        MAX(p.title) AS order_title  /* <-- CHECK THIS! Replace 'title' with your real product name column */
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id 
    GROUP BY o.id, o.order_date, o.status, u.username
    ORDER BY o.order_date DESC
";

$result_orders = secure_query($sql_orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>🛒 Order Management</h2>
            <a href="dashboard.php" class="btn btn-secondary">← Go Back to Dashboard</a>
        </div>
        <p class="lead">View and manage all customer orders.</p>
        <hr>

        <?php echo $message; ?>

        <?php if ($result_orders && $result_orders->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Title</th> 
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $valid_statuses = ['Pending', 'Shipped', 'Delivered', 'Canceled'];
                    while ($order = $result_orders->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>#<?php echo $order['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                        <td><?php echo htmlspecialchars($order['order_title'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                             <span class="badge badge-<?php 
                                 echo $order['status'] == 'Delivered' ? 'success' : 
                                      ($order['status'] == 'Shipped' ? 'info' : 
                                      ($order['status'] == 'Canceled' ? 'danger' : 'warning'));
                            ?>"><?php echo $order['status']; ?></span>
                        </td>
                        <td>
                            <form method="post" action="order_management.php" class="form-inline">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="status" class="form-control form-control-sm mr-2">
                                    <?php foreach ($valid_statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo ($order['status'] == $status) ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; $result_orders->free(); ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info">No orders found.</div>
        <?php endif; ?>
    </div>
    <?php include '../includes/footer.php'; ?>