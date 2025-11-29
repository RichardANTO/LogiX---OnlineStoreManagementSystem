<?php
require_once 'includes/config.php';

// Check if user is logged in and not an admin
if (!is_logged_in() || is_admin()) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'], $_POST['quantity'])) {
    $user_id = $_SESSION['id'];
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity <= 0) {
        header("location: product_details.php?id=" . $product_id . "&error=InvalidQuantity");
        exit;
    }

    // 1. Get product price and stock, ensure product is active
    $sql_product = "SELECT price, available_stock, is_active FROM products WHERE id = ?";
    if ($result_product = secure_query($sql_product, 'i', [$product_id])) {
        if ($result_product->num_rows == 1) {
            $product = $result_product->fetch_assoc();
            $price = $product['price'];
            $available_stock = $product['available_stock'];
            $is_active = $product['is_active'];
            $result_product->free();
        } else {
            header("location: index.php?error=ProductNotFound");
            exit;
        }
    } else {
        header("location: index.php?error=DBError");
        exit;
    }
    
    if (!$is_active) {
        header("location: index.php?error=ProductArchived");
        exit;
    }

    // Check if requested quantity exceeds stock
    if ($quantity > $available_stock) {
        header("location: product_details.php?id=" . $product_id . "&error=InsufficientStock");
        exit;
    }

    // 2. Find or create a 'Pending' order for the user (this is the current cart)
    $order_id = null;
    $sql_order = "SELECT id FROM orders WHERE user_id = ? AND status = 'Pending'";
    if ($result_order = secure_query($sql_order, 'i', [$user_id])) {
        if ($result_order->num_rows == 1) {
            $order = $result_order->fetch_assoc();
            $order_id = $order['id'];
        }
        $result_order->free();
    }

    if (!$order_id) {
        // Create a new 'Pending' order
        $sql_insert_order = "INSERT INTO orders (user_id, status) VALUES (?, 'Pending')";
        if (secure_query($sql_insert_order, 'i', [$user_id])) {
            global $link; // Access the global database link for mysqli_insert_id
            $order_id = mysqli_insert_id($link); // Get the ID of the newly created order
        } else {
            header("location: index.php?error=OrderCreationFailed");
            exit;
        }
    }

    // 3. Check if the product is already in the cart (order_items)
    $item_id = null;
    $current_quantity = 0;
    $sql_item = "SELECT id, quantity FROM order_items WHERE order_id = ? AND product_id = ?";
    if ($result_item = secure_query($sql_item, 'ii', [$order_id, $product_id])) {
        if ($result_item->num_rows == 1) {
            $item = $result_item->fetch_assoc();
            $item_id = $item['id'];
            $current_quantity = $item['quantity'];
        }
        $result_item->free();
    }

    // Calculate new total quantity
    $new_quantity = $current_quantity + $quantity;

    // Final stock check after adding current cart quantity
    if ($new_quantity > $available_stock) {
        header("location: product_details.php?id=" . $product_id . "&error=TotalExceedsStock");
        exit;
    }

    if ($item_id) {
        // 4a. Update existing item quantity
        $sql_update_item = "UPDATE order_items SET quantity = ? WHERE id = ?";
        secure_query($sql_update_item, 'ii', [$new_quantity, $item_id]);
    } else {
        // 4b. Insert new item into order_items
        $sql_insert_item = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
        secure_query($sql_insert_item, 'iiid', [$order_id, $product_id, $quantity, $price]);
    }

    // 5. Redirect to cart page with success message
    $_SESSION['cart_message'] = "Product added to cart successfully!";
    header("location: cart.php");
    exit;

} else {
    header("location: index.php");
    exit;
}
?>