<?php
require_once '../includes/config.php';
// Role-based access control: Only logged-in administrators can access this page
if (!is_logged_in() || !is_admin()) { header("location: ../login.php"); exit; }

$title = $description = $price = $stock = $photo_name = "";
$title_err = $description_err = $price_err = $stock_err = $photo_err = "";
$message = "";

// Define the constant for UPLOAD_ERR_NO_FILE if not defined
if (!defined('UPLOAD_ERR_NO_FILE')) {
    define('UPLOAD_ERR_NO_FILE', 4);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validate Title (MANDATORY)
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a product title.";
    } else {
        $title = trim($_POST["title"]);
    }

    // 2. Validate Description (MANDATORY, Max 500 Characters)
    $description_raw = trim($_POST["description"]);
    
    if (empty($description_raw)) {
        $description_err = "Please enter a product description."; 
        $description = ""; 
    } elseif (strlen($description_raw) > 500) {
        $description_err = "Description must not exceed 500 characters.";
        $description = $description_raw; 
    } else {
        $description = $description_raw;
    }

    // 3. Validate Price (MANDATORY)
    if (empty(trim($_POST["price"]))) {
        $price_err = "Please enter a price.";
    } elseif (!is_numeric($_POST["price"]) || $_POST["price"] <= 0) {
        $price_err = "Price must be a positive number.";
    } else {
        $price = number_format(floatval($_POST["price"]), 2, '.', '');
    }

    // 4. Validate Stock (MANDATORY)
    if (empty(trim($_POST["available_stock"]))) {
        $stock_err = "Please enter stock quantity.";
    } elseif (!filter_var($_POST["available_stock"], FILTER_VALIDATE_INT) || $_POST["available_stock"] < 0) {
        $stock_err = "Stock must be a non-negative integer.";
    } else {
        $stock = intval($_POST["available_stock"]);
    }
    
    // 5. Handle Photo Upload (MANDATORY)
    $photo_name = ''; 
    
    if ($_FILES["photo"]["error"] == UPLOAD_ERR_NO_FILE) { 
        $photo_err = "Please upload a product photo."; 
    } elseif (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES["photo"]["name"];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('product_', true) . '.' . $file_ext;
            $upload_path = "../assets/img/" . $new_file_name;
            
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_path)) {
                $photo_name = $new_file_name;
            } else {
                $photo_err = "Error uploading photo.";
            }
        } else {
            $photo_err = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
        }

    } elseif ($_FILES["photo"]["error"] != 4) {
        $photo_err = "Unexpected upload error (Code: " . $_FILES["photo"]["error"] . ").";
    }

    // 6. Insert into database
    if (empty($title_err) && empty($description_err) && empty($price_err) && empty($stock_err) && empty($photo_err) && !empty($photo_name)) {
        
        $sql = "INSERT INTO products (title, description, price, available_stock, photo) VALUES (?, ?, ?, ?, ?)";
        
        if (secure_query($sql, 'ssdis', [$title, $description, $price, $stock, $photo_name])) {
            $message = '<div class="alert alert-success">Product added successfully! <a href="product_management.php">View Products</a></div>';
            
            // Clear fields
            $title = $description = $price = $stock = "";
        } else {
            $message = '<div class="alert alert-danger">Database error: Could not add product.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>➕ Add New Product</h2>
            <a href="product_management.php" class="btn btn-secondary">← Go Back to Product Management</a>
        </div>
        <hr>

        <?php echo $message; ?>

        <div class="card p-4 shadow">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>" required>
                    <span class="invalid-feedback"><?php echo $title_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Description (Required, Max 500 Characters)</label>
                    <textarea name="description" 
                              class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" 
                              rows="4" maxlength="500" required><?php echo htmlspecialchars($description); ?></textarea>
                    <span class="invalid-feedback"><?php echo $description_err; ?></span>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Price (€)</label>
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
                    <label>Product Photo (Required)</label>
                    <input type="file" name="photo" class="form-control-file <?php echo (!empty($photo_err)) ? 'is-invalid' : ''; ?>" required>
                    <span class="invalid-feedback"><?php echo $photo_err; ?></span>
                </div>

                <button type="submit" class="btn btn-primary">Submit Product</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
