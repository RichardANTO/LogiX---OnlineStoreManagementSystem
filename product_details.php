<?php
require_once 'includes/config.php';

// Check if a product ID is provided
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: index.php");
    exit;
}

$product_id = trim($_GET['id']);
$product = null;

// Fetch product details, ensuring it is active
$sql = "SELECT id, title, description, photo, price, available_stock, is_active FROM products WHERE id = ?";
if ($result = secure_query($sql, 'i', [$product_id])) {
    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
        $result->free();
    }
}

// Product not found or inactive
if (!$product || $product['is_active'] == 0) {
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['title']); ?> Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-5">
                <img src="assets/img/<?php echo htmlspecialchars($product['photo']); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($product['title']); ?>">
            </div>
            <div class="col-md-7">
                <h2><?php echo htmlspecialchars($product['title']); ?></h2>
                <hr>
                <h3 class="text-primary display-4">€<?php echo number_format($product['price'], 2); ?></h3>
                <p class="lead mt-3"><?php echo htmlspecialchars($product['description']); ?></p>
                <p class="text-muted">Available Stock: **<?php echo $product['available_stock']; ?>**</p>
                
                <?php if (is_logged_in() && !is_admin()): ?>
                    <?php if ($product['available_stock'] > 0): ?>
                        <form action="add_to_cart.php" method="post" class="mt-4">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="form-group row">
                                <label for="quantity" class="col-sm-3 col-form-label">Quantity</label>
                                <div class="col-sm-4">
                                    <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" max="<?php echo $product['available_stock']; ?>" required>
                                </div>
                                <div class="col-sm-5">
                                    <button type="submit" class="btn btn-success btn-lg btn-block">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-danger btn-lg mt-4" disabled>Out of Stock</button>
                    <?php endif; ?>
                <?php elseif (!is_logged_in()): ?>
                    <div class="alert alert-info mt-4">
                        <a href="login.php" class="alert-link">Login</a> to add this product to your cart.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>