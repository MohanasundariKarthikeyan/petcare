<?php
require_once 'shop_auth.php';

// Handle add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if ($quantity < 1 || $quantity > 10) {
        $_SESSION['product_message'] = "Invalid quantity selected. Please choose between 1-10.";
        $_SESSION['product_message_type'] = 'danger';
        header("Location: product_detail.php?id=" . $product_id);
        exit();
    }
    
    try {
        // Get product details
        $stmt = $pdo->prepare("SELECT price, name, image_url, stock FROM product WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception("Product not found");
        }
        
        // Check stock availability
        if ($quantity > $product['stock']) {
            $_SESSION['product_message'] = "Only {$product['stock']} items available in stock.";
            $_SESSION['product_message_type'] = 'danger';
            header("Location: product_detail.php?id=" . $product_id);
            exit();
        }
        
        // Check if product already in cart
        $stmt = $pdo->prepare("SELECT * FROM carts4 WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['shop_user_id'], $product_id]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Update quantity if already in cart
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            // Check if new quantity exceeds stock
            if ($newQuantity > $product['stock']) {
                $_SESSION['product_message'] = "Only {$product['stock']} items available in stock (you already have {$existingItem['quantity']} in cart).";
                $_SESSION['product_message_type'] = 'danger';
                header("Location: product_detail.php?id=" . $product_id);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE carts4 SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$newQuantity, $_SESSION['shop_user_id'], $product_id]);
        } else {
            // Add new item to cart
            $stmt = $pdo->prepare("INSERT INTO carts4 (user_id, product_id, product_name, price, quantity, image) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['shop_user_id'],
                $product_id,
                $product['name'],
                $product['price'],
                $quantity,
                $product['image_url']
            ]);
        }
        
        // Update cart count in session
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM carts4 WHERE user_id = ?");
        $stmt->execute([$_SESSION['shop_user_id']]);
        $result = $stmt->fetch();
        $_SESSION['cart_count'] = $result['total'] ?? 0;
        
        $_SESSION['product_message'] = "Product added to cart successfully!";
        $_SESSION['product_message_type'] = 'success';
        
        header("Location: cart.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['product_message'] = "Error adding product to cart. Please try again.";
        $_SESSION['product_message_type'] = 'danger';
        header("Location: product_detail.php?id=" . $product_id);
        exit();
    }
}

// Get product details
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $_SESSION['product_message'] = "Product not found.";
            $_SESSION['product_message_type'] = 'danger';
            header("Location: shop.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['product_message'] = "Error fetching product details.";
        $_SESSION['product_message_type'] = 'danger';
        header("Location: shop.php");
        exit();
    }
} else {
    header("Location: shop.php");
    exit();
}

// Get related products (same category)
try {
    $stmt = $pdo->prepare("SELECT * FROM product WHERE category_id = ? AND id != ? LIMIT 4");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $related_products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'shop_navbar.php'; ?>

    <div class="container py-5">
        <?php if (isset($_SESSION['product_message'])): ?>
            <div class="alert alert-<?= $_SESSION['product_message_type'] ?>">
                <?= $_SESSION['product_message'] ?>
            </div>
            <?php unset($_SESSION['product_message']); unset($_SESSION['product_message_type']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <img src="<?= htmlspecialchars($product['image_url']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <div class="col-md-6">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <div class="mb-3">
                    <span class="text-muted">Category: <?= htmlspecialchars($product['category_id']) ?></span>
                </div>
                <div class="mb-3">
                    <span class="h4">$<?= number_format($product['price'], 2) ?></span>
                </div>
                <div class="mb-3">
                    <span class="<?= $product['stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                    </span>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="text-muted">(<?= $product['stock'] ?> available)</span>
                    <?php endif; ?>
                </div>
                <p><?= htmlspecialchars($product['description']) ?></p>
                
                <?php if ($product['stock'] > 0): ?>
                    <form method="post" action="product_detail.php">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <select class="form-select" id="quantity" name="quantity">
                                    <?php for ($i = 1; $i <= min(10, $product['stock']); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product details tabs -->
        <div class="row mt-5">
            <div class="col-12">
                <ul class="nav nav-tabs" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Details</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab">Specifications</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Reviews</button>
                    </li>
                </ul>
                <div class="tab-content p-3 border border-top-0 rounded-bottom" id="productTabsContent">
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                    <div class="tab-pane fade" id="specs" role="tabpanel">
                        <?php if (!empty($product['specifications'])): ?>
                            <?= nl2br(htmlspecialchars($product['specifications'])) ?>
                        <?php else: ?>
                            <p>No specifications available for this product.</p>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
                            $stmt->execute([$product_id]);
                            $reviews = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            $reviews = [];
                        }
                        ?>
                        
                        <?php if (!empty($reviews)): ?>
                            <div class="row">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <h5 class="card-title"><?= htmlspecialchars($review['username']) ?></h5>
                                                    <div>
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-empty' ?> text-warning"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                                                <small class="text-muted"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No reviews yet. Be the first to review this product!</p>
                        <?php endif; ?>
                        
                        <!-- Review form -->
                        <?php if (isset($_SESSION['shop_user_id'])): ?>
                            <div class="mt-4">
                                <h4>Write a Review</h4>
                                <form method="post" action="submit_review.php">
                                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="rating">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                                <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="comment" class="form-label">Comment</label>
                                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Review</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-4">
                                Please <a href="login.php">login</a> to leave a review.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related products -->
        <?php if (!empty($related_products)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h3>Related Products</h3>
                    <div class="row">
                        <?php foreach ($related_products as $related): ?>
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="card h-100">
                                    <img src="<?= htmlspecialchars($related['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($related['name']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($related['name']) ?></h5>
                                        <p class="card-text text-success">$<?= number_format($related['price'], 2) ?></p>
                                        <a href="product_detail.php?id=<?= $related['id'] ?>" class="btn btn-outline-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>