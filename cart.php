<?php
require_once 'includes/config.php';

// Redirect non-logged-in users or admins
if (!is_logged_in() || is_admin()) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$cart_items = [];
$total_amount = 0;
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

// Fetch current 'Pending' order and its items, including the current available stock
$sql_cart = "
    SELECT 
        o.id AS order_id, 
        oi.id AS item_id, 
        oi.product_id,
        oi.quantity, 
        oi.unit_price, 
        p.title, 
        p.available_stock, /* CRITICAL: Fetch current stock */
        p.photo
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND o.status = 'Pending'
";

$order_id = null;
if ($result_cart = secure_query($sql_cart, 'i', [$user_id])) {
    while ($item = $result_cart->fetch_assoc()) {
        $order_id = $item['order_id'];
        $item['subtotal'] = $item['quantity'] * $item['unit_price'];
        $total_amount += $item['subtotal'];
        $cart_items[] = $item;
    }
    // Reset pointer for potential use later 
    $result_cart->data_seek(0);
}


// Handle POST actions (Remove and Checkout)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // --- 1. Handle Removal Action ---
    if ($_POST['action'] == 'remove' && isset($_POST['item_id'])) {
        $item_id = intval($_POST['item_id']);
        $sql_remove = "DELETE FROM order_items WHERE id = ? AND order_id IN (SELECT id FROM orders WHERE user_id = ? AND status = 'Pending')";
        if (secure_query($sql_remove, 'ii', [$item_id, $user_id])) {
            $_SESSION['cart_message'] = '<div class="alert alert-info">Item removed from cart.</div>';
        } else {
            $_SESSION['cart_message'] = '<div class="alert alert-danger">Error removing item.</div>';
        }
        header("location: cart.php");
        exit;
    }
    
    // --- 2. Handle Checkout Action (The Live Stock Fix) ---
    if ($_POST['action'] == 'checkout' && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $checkout_success = false;

        if (empty($cart_items)) {
            $_SESSION['cart_message'] = '<div class="alert alert-warning">Your cart is empty and cannot be checked out.</div>';
            header("location: cart.php");
            exit;
        }

        // --- VALIDATION: Check for sufficient stock one last time ---
        $stock_error = false;
        foreach ($cart_items as $item) {
            // Check if the quantity ordered is greater than the stock retrieved earlier
            if ($item['quantity'] > $item['available_stock']) {
                $_SESSION['cart_message'] = '<div class="alert alert-danger">Stock error! **' . htmlspecialchars($item['title']) . '** only has **' . $item['available_stock'] . '** left, but you requested **' . $item['quantity'] . '**. Please adjust the quantity.</div>';
                $stock_error = true;
                break;
            }
        }

        if (!$stock_error) {
            // --- LIVE STOCK DEDUCTION ---
            $stock_update_success = true;
            // This UPDATE query ensures stock does not go below zero and performs the deduction.
            $sql_decrement_stock = "UPDATE products SET available_stock = available_stock - ? WHERE id = ? AND available_stock >= ?";
            
            foreach ($cart_items as $item) {
                // Parameters: [quantity_to_deduct, product_id, minimum_stock_required]
                if (!secure_query($sql_decrement_stock, 'iii', [$item['quantity'], $item['product_id'], $item['quantity']])) {
                    // If the query fails, it usually means the WHERE condition (available_stock >= ?) was not met.
                    $stock_update_success = false;
                    $_SESSION['cart_message'] = '<div class="alert alert-danger">Critical Stock Error: One or more items became unavailable during checkout. Please review your cart.</div>';
                    break; 
                }
            }

            if ($stock_update_success) {
                // --- ORDER STATUS UPDATE ---
                $sql_checkout = "UPDATE orders SET status = 'Shipped', order_date = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND status = 'Pending'";
                if (secure_query($sql_checkout, 'ii', [$order_id, $user_id])) {
                    $checkout_success = true;
                }
            }
        }
        
        // --- FINAL REDIRECT ---
        if ($checkout_success) {
            $_SESSION['cart_message'] = '<div class="alert alert-success">Order successfully placed and is now **Shipped**! Check your profile for tracking.</div>';
            header("location: user_profile.php"); 
            exit;
        } else {
            if (!isset($_SESSION['cart_message'])) {
                $_SESSION['cart_message'] = '<div class="alert alert-danger">Error placing order. Please try again.</div>';
            }
            header("location: cart.php");
            exit;
        }
    }
}

// Re-fetch message after potential redirect
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <h2>🛍️ Your Shopping Cart</h2>
        <hr>

        <?php echo $message; ?>

        <?php if (!empty($cart_items)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/img/<?php echo htmlspecialchars($item['photo']); ?>" 
                                            alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                            style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                        <a href="product_details.php?id=<?php echo $item['product_id']; ?>">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                    </div>
                                    </td>
                                <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td>**$<?php echo number_format($item['subtotal'], 2); ?>**</td>
                                <td>
                                    <form method="post" action="cart.php" style="display:inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right">**Total Amount:**</td>
                            <td colspan="2">**$<?php echo number_format($total_amount, 2); ?>**</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-12 text-right">
                    <form method="post" action="cart.php">
                        <input type="hidden" name="action" value="checkout">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <button type="submit" class="btn btn-success btn-lg">Complete Order (Checkout)</button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-warning text-center">
                Your cart is empty. <a href="index.php" class="alert-link">Continue shopping.</a>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>