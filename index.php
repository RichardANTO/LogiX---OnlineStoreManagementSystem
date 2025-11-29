<?php
require_once 'includes/config.php';

// Fetch products for the homepage display (only active and in-stock products)
$sql_products = "SELECT id, title, description, photo, price, available_stock 
                 FROM products 
                 WHERE available_stock > 0 
                 ORDER BY created_at DESC";
$result_products = secure_query($sql_products);

// Prepare featured products for the carousel (first 3)
$featured_products = [];
if ($result_products) {
    // Reset internal pointer to fetch the first 3 for carousel
    $result_products->data_seek(0);
    while ($row = $result_products->fetch_assoc()) {
        $featured_products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Online Store</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-4">

        <div class="jumbotron-carousel">
            <h1 class="display-4">Welcome to LogiX!</h1>
            <p class="lead">Your one-stop shop for the latest gadgets, electronics and Books.</p>
            
            <div id="productCarousel" class="carousel slide carousel-custom" data-ride="carousel">
                <div class="carousel-inner">
                    <?php 
                    $carousel_items = array_slice($featured_products, 0, 7);
                    if (!empty($carousel_items)):
                        foreach ($carousel_items as $index => $product): 
                            // Determine background class based on index for alternating color scheme
                            $bg_class = ($index % 2 == 0) ? 'bg-slide-blue' : 'bg-slide-orange';
                    ?>
                            <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?> <?php echo $bg_class; ?>">
                                <div class="row align-items-center">
                                    
                                    <div class="col-md-5 carousel-image-container">
                                        <img src="assets/img/<?php echo htmlspecialchars($product['photo']); ?>" 
                                            class="d-block w-100 carousel-product-img" 
                                            alt="<?php echo htmlspecialchars($product['title']); ?>">
                                    </div>

                                    <div class="col-md-7 carousel-details-container p-4">
                                        <h2 class="carousel-title"><?php echo htmlspecialchars($product['title']); ?></h2>
                                        <p class="carousel-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                        
                                        <div class="price-stock-group mt-4">
                                            <div class="price-info">
                                                <small class="text-uppercase">Price</small>
                                                <h3 class="font-weight-bold">€<?php echo number_format($product['price'], 2); ?></h3>
                                            </div>
                                            <div class="stock-info">
                                                <small class="text-uppercase">Stock</small>
                                                <h3 class="font-weight-bold"><?php echo $product['available_stock']; ?></h3>
                                            </div>
                                        </div>
                                        
                                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-light btn-lg mt-3">View Product Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <div class="carousel-item active bg-slide-default">
                            <div class="row align-items-center h-100">
                                <div class="col-12 text-center py-5">
                                    <h2 class="text-white">No Featured Products Available</h2>
                                    <p class="text-white-50">Please add products to the catalog.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <a class="carousel-control-prev" href="#productCarousel" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" href="#productCarousel" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
            </div>
        </div>

        <h2 class="mt-5 mb-4" id="products">Available Products</h2>
        <div class="row">
            <?php
            // We need to re-fetch the results or reset the pointer if we want the full list here.
            if ($result_products && $result_products->num_rows > 0) {
                $result_products->data_seek(0);
                while ($product = $result_products->fetch_assoc()):
            ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card">
                            <div class="product-img-wrapper">
                                <img src="assets/img/<?php echo htmlspecialchars($product['photo']); ?>" class="product-card-img" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h5>
                                <p class="card-price">€<?php echo number_format($product['price'], 2); ?></p>
                                <p class="card-text text-muted">Stock: <?php echo $product['available_stock']; ?></p>
                                <div class="mt-auto">
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                endwhile;
                $result_products->free();
            } else {
                echo '<div class="col-12"><p class="alert alert-warning">No products available at the moment. Check back later!</p></div>';
            }
            ?>
        </div>
        </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php include 'includes/footer.php'; ?>