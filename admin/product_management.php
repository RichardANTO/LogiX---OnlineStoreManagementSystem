<?php
require_once '../includes/config.php';
// Role-based access control: Only logged-in administrators can access this page
if (!is_logged_in() || !is_admin()) { header("location: ../login.php"); exit; }

$message = "";

// Handle DELETE Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // 1. Define the SQL queries
    $sql_delete_items = "DELETE FROM order_items WHERE product_id = ?";
    $sql_delete_product = "DELETE FROM products WHERE id = ?";
    
    // Step 1: Delete Order Items associated with this product (Child Records)
    if (secure_query($sql_delete_items, 'i', [$product_id])) {
        
        // Step 2: Delete the parent product record
        if (secure_query($sql_delete_product, 'i', [$product_id])) {
            
            // 3. Check if the PRODUCTS table is now empty
            $count_result = secure_query("SELECT COUNT(id) AS count FROM products");
            $product_count = $count_result->fetch_assoc()['count'] ?? 0;
            
            if ($product_count == 0) {
                // 🔑 AUTOMATIC RESET: If the table is empty, reset the AUTO_INCREMENT counter to 1
                if (secure_query("ALTER TABLE products AUTO_INCREMENT = 1")) {
                    $message = '<div class="alert alert-success">Product ID **'.$product_id.'** deleted. As this was the last product, the Product ID counter has been **RESET TO 1**.</div>';
                } else {
                    $message = '<div class="alert alert-warning">Product deleted. Failed to reset the ID counter to 1.</div>';
                }
            } else {
                // If products still exist, confirm deletion without resetting
                $message = '<div class="alert alert-success">Product ID **'.$product_id.'** and associated history successfully DELETED. Other products remain.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Error deleting product record.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Error deleting associated order items. Product was not deleted.</div>';
    }
}

// Fetch all products, NOW INCLUDING the description column
$sql_products = "SELECT id, title, description, price, available_stock, photo FROM products ORDER BY id DESC";
$result_products = secure_query($sql_products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>📦 Product Management</h2>
            <div>
                <a href="dashboard.php" class="btn btn-secondary mr-2">← Go Back to Dashboard</a>
                <a href="add_product.php" class="btn btn-primary">➕ Add New Product</a>
            </div>
        </div>
        <hr>

        <?php echo $message; ?>
        
        <?php if ($result_products && $result_products->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $result_products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <img src="../assets/img/<?php echo htmlspecialchars($product['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td><?php echo htmlspecialchars($product['title']); ?></td>
                        <td><?php echo htmlspecialchars(substr($product['description'], 0, 75)) . (strlen($product['description']) > 75 ? '...' : ''); ?></td>
                        <td>€<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['available_stock']; ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                            <a href="product_management.php?action=delete&id=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('WARNING: Are you sure you want to permanently DELETE product ID <?php echo $product['id']; ?>? This will also delete all associated order history. This action cannot be undone.');">
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; // $result_products->free() is not needed here as $result_products is already free if using the fetch_assoc loop directly
                    ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info">No products found. Add a new product to get started!</div>
        <?php endif; ?>
    </div>
    <?php include '../includes/footer.php'; ?>