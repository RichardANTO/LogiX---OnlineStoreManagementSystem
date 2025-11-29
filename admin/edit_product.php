<?php
require_once '../includes/config.php';
if (!is_logged_in() || !is_admin()) { header("location: ../login.php"); exit; }

// Check for product ID
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: product_management.php");
    exit;
}

$product_id = trim($_GET['id']);
$title = $description = $price = $stock = $photo_name = "";
$title_err = $description_err = $price_err = $stock_err = $photo_err = "";
$message = "";

// 1. Fetch current product data
$sql_fetch = "SELECT title, description, price, available_stock, photo, is_active FROM products WHERE id = ?";
if ($result = secure_query($sql_fetch, 'i', [$product_id])) {
    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
        $title = $product['title'];
        $description = $product['description'];
        $price = $product['price'];
        $stock = $product['available_stock'];
        $photo_name = $product['photo'];
        $is_active = $product['is_active'];
    } else {
        $message = '<div class="alert alert-danger">Product not found.</div>';
    }
    $result->free();
} else {
    $message = '<div class="alert alert-danger">Error fetching product details.</div>';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Validate and process form submission

    // Validate Title
    if (empty(trim($_POST["title"]))) { $title_err = "Please enter a product title."; } else { $title = trim($_POST["title"]); }
    $description = trim($_POST["description"]);

    // Validate Price
    if (empty(trim($_POST["price"]))) { $price_err = "Please enter a price."; } elseif (!is_numeric($_POST["price"]) || $_POST["price"] <= 0) { $price_err = "Price must be a positive number."; } else { $price = number_format(floatval($_POST["price"]), 2, '.', ''); }

    // Validate Stock
    if (empty(trim($_POST["available_stock"]))) { $stock_err = "Please enter stock quantity."; } elseif (!filter_var($_POST["available_stock"], FILTER_VALIDATE_INT) || $_POST["available_stock"] < 0) { $stock_err = "Stock must be a non-negative integer."; } else { $stock = intval($_POST["available_stock"]); }

    // Validate Status (is_active)
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Handle Photo Upload
    $new_photo_name = $photo_name; // Default to existing photo
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES["photo"]["name"];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('product_', true) . '.' . $file_ext;
            $upload_path = "../assets/img/" . $new_file_name;
            
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_path)) {
                $new_photo_name = $new_file_name;
            } else {
                $photo_err = "Error uploading photo.";
            }
        } else {
            $photo_err = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
        }
    }
    
    // 3. Update database
    if (empty($title_err) && empty($price_err) && empty($stock_err) && empty($photo_err)) {
        $sql = "UPDATE products SET title = ?, description = ?, price = ?, available_stock = ?, photo = ?, is_active = ? WHERE id = ?";
        if (secure_query($sql, 'ssdisii', [$title, $description, $price, $stock, $new_photo_name, $is_active, $product_id])) {
            $message = '<div class="alert alert-success">Product updated successfully! <a href="product_management.php">View Products</a></div>';
            $photo_name = $new_photo_name; // Update photo name for display
        } else {
            $message = '<div class="alert alert-danger">Database error: Could not update product.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>✏️ Edit Product: <?php echo htmlspecialchars($title); ?></h2>
            <a href="product_management.php" class="btn btn-secondary">← Go Back to Product Management</a>
        </div>
        <hr>

        <?php echo $message; ?>

        <?php if (!isset($product)): ?>
            <div class="alert alert-danger">Product data could not be loaded.</div>
        <?php else: ?>
            <div class="card p-4 shadow">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <p>Current Photo:</p>
                        <img src="../assets/img/<?php echo htmlspecialchars($photo_name); ?>" class="img-fluid mb-3 rounded" style="max-height: 150px; object-fit: cover;" alt="Product Photo">
                    </div>
                    <div class="col-md-9">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>" required>
                                <span class="invalid-feedback"><?php echo $title_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                                <span class="invalid-feedback"><?php echo $description_err; ?></span>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Price ($)</label>
                                    <input type="number" step="0.01" name="price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($price); ?>" required>
                                    <span class="invalid-feedback"><?php echo $price_err; ?></span>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Available Stock</label>
                                    <input type="number" name="available_stock" class="form-control <?php echo (!empty($stock_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($stock); ?>" required>
                                    <span class="invalid-feedback"><?php echo $stock_err; ?></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Update Product Photo</label>
                                <input type="file" name="photo" class="form-control-file <?php echo (!empty($photo_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $photo_err; ?></span>
                                <small class="form-text text-muted">Select a new file to change the photo.</small>
                            </div>
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $is_active ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active on Store (Uncheck to Archive)</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Product</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php include '../includes/footer.php'; ?>