<?php
session_start();
// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'petdemo';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Create reviews table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$db->query($create_table)) {
    error_log("Error creating reviews table: " . $db->error);
}

// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Check if product exists
    $check_product = $db->prepare("SELECT * FROM products2 WHERE id = ?");
    if ($check_product) {
        $check_product->bind_param("i", $product_id);
        $check_product->execute();
        $product_result = $check_product->get_result();
        
        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            
            // Get username from session if logged in
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : NULL;
            
            $session_id = session_id();
            $product_name = $product['name'];
            $price = $product['price'];
            $image_url = $product['image_url'];
            
            // Determine category based on product name
            $category = 'Pet'; // Default category
            if (stripos($product_name, 'bird') !== false) {
                $category = 'Bird';
            } elseif (stripos($product_name, 'cat') !== false) {
                $category = 'Cat';
            } elseif (stripos($product_name, 'dog') !== false) {
                $category = 'Dog';
            } elseif (stripos($product_name, 'rabbit') !== false) {
                $category = 'Rabbit';
            }
            
            $subtotal = $price * $quantity;
            
            // Check if product already in cart
            if ($username) {
                // User is logged in - check by username and product_id
                $check_cart = $db->prepare("SELECT id, quantity FROM carts1 WHERE product_id = ? AND username = ?");
                if ($check_cart) {
                    $check_cart->bind_param("is", $product_id, $username);
                    $check_cart->execute();
                    $check_cart->store_result();
                    
                    if ($check_cart->num_rows > 0) {
                        // Update quantity and subtotal
                        $check_cart->bind_result($cart_id, $current_quantity);
                        $check_cart->fetch();
                        
                        $new_quantity = $current_quantity + $quantity;
                        $new_subtotal = $price * $new_quantity;
                        
                        $update = $db->prepare("UPDATE carts1 SET quantity = ?, subtotal = ? WHERE id = ?");
                        if ($update) {
                            $update->bind_param("idi", $new_quantity, $new_subtotal, $cart_id);
                            $update->execute();
                            $update->close();
                        }
                    } else {
                        // Insert new item into carts1 table
                        $insert = $db->prepare("INSERT INTO carts1 (username, product_id, session_id, product_name, price, image_url, quantity, category, subtotal) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($insert) {
                            $insert->bind_param("sissdsisd", $username, $product_id, $session_id, $product_name, $price, $image_url, $quantity, $category, $subtotal);
                            $insert->execute();
                            $insert->close();
                        }
                    }
                    $check_cart->close();
                }
            } else {
                // User is not logged in - check by session_id and product_id
                $check_cart = $db->prepare("SELECT id, quantity FROM carts1 WHERE product_id = ? AND session_id = ? AND (username IS NULL OR username = '')");
                if ($check_cart) {
                    $check_cart->bind_param("is", $product_id, $session_id);
                    $check_cart->execute();
                    $check_cart->store_result();
                    
                    if ($check_cart->num_rows > 0) {
                        // Update quantity and subtotal
                        $check_cart->bind_result($cart_id, $current_quantity);
                        $check_cart->fetch();
                        
                        $new_quantity = $current_quantity + $quantity;
                        $new_subtotal = $price * $new_quantity;
                        
                        $update = $db->prepare("UPDATE carts1 SET quantity = ?, subtotal = ? WHERE id = ?");
                        if ($update) {
                            $update->bind_param("idi", $new_quantity, $new_subtotal, $cart_id);
                            $update->execute();
                            $update->close();
                        }
                    } else {
                        // Insert new item into carts1 table
                        $insert = $db->prepare("INSERT INTO carts1 (username, product_id, session_id, product_name, price, image_url, quantity, category, subtotal) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($insert) {
                            $insert->bind_param("sissdsisd", $username, $product_id, $session_id, $product_name, $price, $image_url, $quantity, $category, $subtotal);
                            $insert->execute();
                            $insert->close();
                        }
                    }
                    $check_cart->close();
                }
            }
            
            header("Location: cart.php");
            exit();
        } else {
            $error = "Product not found!";
        }
        $check_product->close();
    } else {
        $error = "Database error!";
    }
}

// Handle review submission
if (isset($_POST['submit_review'])) {
    $product_id = $_POST['product_id'];
    $user_name = $_POST['user_name'];
    $rating = $_POST['rating'];
    $review_text = $_POST['review_text'];
    
    // Insert review into database
    $insert_review = $db->prepare("INSERT INTO product_reviews (product_id, user_name, rating, review_text, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($insert_review) {
        $insert_review->bind_param("isis", $product_id, $user_name, $rating, $review_text);
        if ($insert_review->execute()) {
            $review_success = "Review submitted successfully!";
        } else {
            $review_error = "Error submitting review: " . $db->error;
        }
        $insert_review->close();
    } else {
        $review_error = "Database error: " . $db->error;
    }
}

// Get all products
$result = $db->query("SELECT * FROM products2");
if (!$result) {
    die("Query failed: " . $db->error);
}

// Extract categories from product names
$categories = [
    'All' => 'Show All Products',
    'Bird' => 'Bird Products',
    'Cat' => 'Cat Products',
    'Dog' => 'Dog Products',
    'Rabbit' => 'Rabbit Products',
    'Pet' => 'General Pet Products'
];

// Get current category from URL parameter
$current_category = isset($_GET['category']) ? $_GET['category'] : 'All';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Shop - Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 60px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }
        
        .navbar {
            background-color: rgba(0, 0, 0, 0.8);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 60px;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            color: white !important;
            text-decoration: none;
        }
        
        .navbar-brand::before {
            content: '🐾';
            font-size: 1.5rem;
            margin-right: 8px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-align: center;
            padding: 10px 15px;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            width: 100%;
        }
        
        h1 {
            text-align: center;
            color: white;
            margin-top: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        
        .category-filter {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .category-btn {
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .category-btn:hover {
            background-color: #3498db;
            color: white;
            transform: translateY(-2px);
        }
        
        .category-btn.active {
            background-color: #3498db;
            color: white;
        }
        
        .products {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .product {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            width: calc(25% - 20px);
            min-width: 250px;
        }
        
        .product:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .product-img-container {
            width: 100%;
            height: 200px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f8f8;
            cursor: pointer;
        }
        
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            transition: transform 0.3s;
        }
        
        .product:hover .product-img {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            font-size: 18px;
            margin: 0 0 10px;
            color: #333;
            font-weight: 600;
        }
        
        .product-price {
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .product-rating {
            color: #f39c12;
            margin-bottom: 15px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .add-to-cart {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .add-to-cart:hover {
            background: #2980b9;
        }
        
        .review-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .review-btn:hover {
            background: #219653;
        }
        
        .error {
            color: red;
            text-align: center;
            margin: 10px 0;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 5px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .success {
            color: green;
            text-align: center;
            margin: 10px 0;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 5px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .user-info {
            color: white;
            margin-right: 15px;
            font-weight: 600;
        }
        
        .cart-count {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            position: relative;
            top: -8px;
            right: -5px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 700px;
            position: relative;
        }
        
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
        }
        
        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }
        
        .modal-product {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        @media (min-width: 768px) {
            .modal-product {
                flex-direction: row;
            }
        }
        
        .modal-product-image {
            flex: 1;
            max-width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-product-image img {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .modal-product-details {
            flex: 2;
        }
        
        .modal-product-title {
            font-size: 24px;
            margin-top: 0;
            color: #333;
        }
        
        .modal-product-price {
            font-size: 22px;
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .modal-product-rating {
            color: #f39c12;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .modal-product-description {
            margin-bottom: 20px;
            line-height: 1.6;
            color: #555;
        }
        
        .modal-add-to-cart {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }
        
        .modal-add-to-cart:hover {
            background: #2980b9;
        }
        
        /* Review Modal Styles */
        .review-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            position: relative;
        }
        
        .review-section {
            margin-top: 20px;
        }
        
        .review-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-review {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .submit-review:hover {
            background: #219653;
        }
        
        .reviews-list {
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .reviewer-name {
            font-weight: 600;
            color: #333;
            margin-right: 10px;
        }
        
        .review-rating {
            color: #f39c12;
            font-size: 18px;
        }
        
        .review-date {
            color: #777;
            font-size: 14px;
        }
        
        .review-text {
            color: #555;
            line-height: 1.6;
        }
        
        .no-reviews {
            text-align: center;
            color: #777;
            padding: 20px;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            cursor: pointer;
            width: 30px;
            height: 30px;
            background: #ccc;
            color: transparent;
            margin-right: 5px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        
        .star-rating label:before {
            content: '★';
            color: white;
            font-size: 20px;
            transition: color 0.3s;
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            background: #f39c12;
        }
        
        .star-rating input:checked ~ label:before,
        .star-rating label:hover:before,
        .star-rating label:hover ~ label:before {
            color: white;
        }
        
        @media screen and (max-width: 992px) {
            .product {
                width: calc(33.33% - 20px);
            }
        }
        
        @media screen and (max-width: 768px) {
            .product {
                width: calc(50% - 20px);
            }
            
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 10px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
                margin-top: 10px;
            }
            
            .nav-links a {
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .category-filter {
                overflow-x: auto;
                justify-content: flex-start;
                padding-bottom: 10px;
            }
            
            .modal-content,
            .review-modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
        
        @media screen and (max-width: 480px) {
            .product {
                width: 100%;
            }
            
            .navbar-brand {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="navbar-brand" href="shoppinghome.php">PetCare</a>
        <div class="nav-links">
            <?php if (isset($_SESSION['username'])): ?>
                <span class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <?php endif; ?>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
            <a href="home.php">Home</a>
            <a href="cart.php">Cart 
                <?php
                // Display cart item count
                if (isset($_SESSION['username'])) {
                    $cart_count = $db->prepare("SELECT COUNT(*) as count FROM carts1 WHERE username = ?");
                    $cart_count->bind_param("s", $_SESSION['username']);
                    $cart_count->execute();
                    $count_result = $cart_count->get_result();
                    $count_data = $count_result->fetch_assoc();
                    if ($count_data['count'] > 0) {
                        echo '<span class="cart-count">' . $count_data['count'] . '</span>';
                    }
                    $cart_count->close();
                } else {
                    $session_id = session_id();
                    $cart_count = $db->prepare("SELECT COUNT(*) as count FROM carts1 WHERE session_id = ? AND (username IS NULL OR username = '')");
                    $cart_count->bind_param("s", $session_id);
                    $cart_count->execute();
                    $count_result = $cart_count->get_result();
                    $count_data = $count_result->fetch_assoc();
                    if ($count_data['count'] > 0) {
                        echo '<span class="cart-count">' . $count_data['count'] . '</span>';
                    }
                    $cart_count->close();
                }
                ?>
            </a>
            <a href="order.php">My Order</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1>Pet Shop Products</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($review_success)): ?>
            <div class="success"><?php echo htmlspecialchars($review_success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($review_error)): ?>
            <div class="error"><?php echo htmlspecialchars($review_error); ?></div>
        <?php endif; ?>
        
        <!-- Category Filter Buttons -->
        <div class="category-filter">
            <?php foreach ($categories as $key => $name): ?>
                <button class="category-btn <?php echo $current_category === $key ? 'active' : ''; ?>" 
                        data-category="<?php echo $key; ?>">
                    <?php echo htmlspecialchars($name); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="products">
            <?php 
            // Reset result pointer
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()): 
                // Determine category based on product name
                $product_category = 'Pet'; // Default category
                
                if (stripos($row['name'], 'bird') !== false) {
                    $product_category = 'Bird';
                } elseif (stripos($row['name'], 'cat') !== false) {
                    $product_category = 'Cat';
                } elseif (stripos($row['name'], 'dog') !== false) {
                    $product_category = 'Dog';
                } elseif (stripos($row['name'], 'rabbit') !== false) {
                    $product_category = 'Rabbit';
                }
                
                // Show product if category matches or if "All" is selected
                if ($current_category === 'All' || $product_category === $current_category):
            ?>
            <div class="product" data-category="<?php echo $product_category; ?>">
                <div class="product-img-container" onclick="openModal(<?php echo $row['id']; ?>)">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-img">
                </div>
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <div class="product-price">₹<?php echo number_format($row['price'], 2); ?></div>
                    <div class="product-rating">Rating: <?php echo htmlspecialchars($row['rating']); ?> ★</div>
                    
                    <div class="product-actions">
                        <form method="post" class="add-to-cart-form">
                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                            <input type="number" name="quantity" class="quantity-input" value="1" min="1" max="99">
                            <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
                        </form>
                        <button class="review-btn" onclick="openReviewModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')">Reviews</button>
                    </div>
                </div>
            </div>
            <?php endif; endwhile; ?>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="modal-product" id="modalProductContent">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="review-modal-content">
            <span class="close-modal" onclick="closeReviewModal()">&times;</span>
            <div id="reviewModalContent">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Add click event listeners to category buttons
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                
                // Update URL without reloading the page
                const url = new URL(window.location);
                url.searchParams.set('category', category);
                window.history.pushState({}, '', url);
                
                // Update active button
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Show/hide products based on category
                document.querySelectorAll('.product').forEach(product => {
                    const productCategory = product.getAttribute('data-category');
                    
                    if (category === 'All' || productCategory === category) {
                        product.style.display = 'block';
                    } else {
                        product.style.display = 'none';
                    }
                });
            });
        });
        
        // Modal functionality
        function openModal(productId) {
            // Fetch product details via AJAX
            fetch('get_product_details.php?id=' + productId)
                .then(response => response.json())
                .then(product => {
                    // Populate modal with product details
                    document.getElementById('modalProductContent').innerHTML = `
                        <div class="modal-product-image">
                            <img src="${product.image_url}" alt="${product.name}">
                        </div>
                        <div class="modal-product-details">
                            <h2 class="modal-product-title">${product.name}</h2>
                            <div class="modal-product-price">₹${parseFloat(product.price).toFixed(2)}</div>
                            <div class="modal-product-rating">Rating: ${product.rating} ★</div>
                            <p class="modal-product-description">${product.description || 'No description available.'}</p>
                            <form method="post">
                                <input type="hidden" name="product_id" value="${product.id}">
                                <input type="number" name="quantity" class="quantity-input" value="1" min="1" max="99" style="margin-bottom: 10px;">
                                <button type="submit" name="add_to_cart" class="modal-add-to-cart">Add to Cart</button>
                            </form>
                            <button class="review-btn" onclick="openReviewModal(${product.id}, '${product.name.replace(/'/g, "\\'")}')">View Reviews</button>
                        </div>
                    `;
                    
                    // Show the modal
                    document.getElementById('productModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    alert('Error loading product details.');
                });
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        // Review Modal functionality
        function openReviewModal(productId, productName) {
            // Close product modal if open
            closeModal();
            
            // Fetch reviews for this product
            fetch('get_product_reviews.php?id=' + productId)
                .then(response => response.json())
                .then(data => {
                    let reviewsHTML = '';
                    
                    if (data.reviews && data.reviews.length > 0) {
                        reviewsHTML = data.reviews.map(review => `
                            <div class="review-item">
                                <div class="review-header">
                                    <div>
                                        <span class="reviewer-name">${review.user_name}</span>
                                        <span class="review-rating">${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}</span>
                                    </div>
                                    <span class="review-date">${new Date(review.created_at).toLocaleDateString()}</span>
                                </div>
                                <p class="review-text">${review.review_text}</p>
                            </div>
                        `).join('');
                    } else {
                        reviewsHTML = '<div class="no-reviews">No reviews yet. Be the first to review this product!</div>';
                    }
                    
                    // Populate review modal
                    document.getElementById('reviewModalContent').innerHTML = `
                        <h2>Reviews for ${productName}</h2>
                        
                        <div class="review-section">
                            <h3>Add Your Review</h3>
                            <form class="review-form" method="post">
                                <input type="hidden" name="product_id" value="${productId}">
                                
                                <div class="form-group">
                                    <label for="user_name">Your Name</label>
                                    <input type="text" id="user_name" name="user_name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="rating">Rating</label>
                                    <div class="star-rating">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5" title="5 stars"></label>
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4" title="4 stars"></label>
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3" title="3 stars"></label>
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2" title="2 stars"></label>
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1" title="1 star"></label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="review_text">Your Review</label>
                                    <textarea id="review_text" name="review_text" required></textarea>
                                </div>
                                
                                <button type="submit" name="submit_review" class="submit-review">Submit Review</button>
                            </form>
                        </div>
                        
                        <div class="review-section">
                            <h3>Customer Reviews</h3>
                            <div class="reviews-list">
                                ${reviewsHTML}
                            </div>
                        </div>
                    `;
                    
                    // Show the review modal
                    document.getElementById('reviewModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching reviews:', error);
                    alert('Error loading reviews.');
                });
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
        
        // Close modals when clicking outside of them
        window.onclick = function(event) {
            const productModal = document.getElementById('productModal');
            const reviewModal = document.getElementById('reviewModal');
            
            if (event.target === productModal) {
                closeModal();
            }
            if (event.target === reviewModal) {
                closeReviewModal();
            }
        };
        
        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeReviewModal();
            }
        });
    </script>

</body>
</html>
<?php 
$result->free();
$db->close(); 
?>