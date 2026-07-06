<?php
// Start session at the very beginning
session_start();

// Database connection - Update these credentials with your actual database details
$db = new mysqli('localhost', 'root', '', 'petdemo');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Check if user is logged in
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

// Function to merge session cart with user cart when user logs in
function mergeCarts($db, $username, $session_id) {
    // Get all items from session cart
    $session_cart_query = "SELECT * FROM carts1 WHERE session_id = ? AND (username IS NULL OR username = '')";
    $stmt = $db->prepare($session_cart_query);
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $session_items = $stmt->get_result();
    
    while ($item = $session_items->fetch_assoc()) {
        // Check if user already has this product in their cart
        $check_query = "SELECT * FROM carts1 WHERE username = ? AND product_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bind_param("si", $username, $item['product_id']);
        $check_stmt->execute();
        $existing_item = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing_item) {
            // Update quantity if product already exists in user's cart
            $new_quantity = $existing_item['quantity'] + $item['quantity'];
            $new_subtotal = $existing_item['price'] * $new_quantity;
            
            $update_query = "UPDATE carts1 SET quantity = ?, subtotal = ? WHERE username = ? AND product_id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bind_param("idsi", $new_quantity, $new_subtotal, $username, $item['product_id']);
            $update_stmt->execute();
            
            // Remove the session item
            $delete_query = "DELETE FROM carts1 WHERE session_id = ? AND product_id = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bind_param("si", $session_id, $item['product_id']);
            $delete_stmt->execute();
        } else {
            // Move session item to user cart
            $update_query = "UPDATE carts1 SET username = ?, session_id = NULL WHERE session_id = ? AND product_id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bind_param("ssi", $username, $session_id, $item['product_id']);
            $update_stmt->execute();
        }
    }
}

// If user just logged in, merge their session cart with their user cart
if ($username && !isset($_SESSION['cart_merged'])) {
    $session_id = session_id();
    mergeCarts($db, $username, $session_id);
    $_SESSION['cart_merged'] = true; // Prevents repeated merging
}

// Handle remove item action
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    
    if ($username) {
        // User is logged in - remove by username
        $delete = $db->prepare("DELETE FROM carts1 WHERE product_id = ? AND username = ?");
        $delete->bind_param("is", $remove_id, $username);
    } else {
        // User is not logged in - remove by session_id
        $session_id = session_id();
        $delete = $db->prepare("DELETE FROM carts1 WHERE product_id = ? AND session_id = ? AND (username IS NULL OR username = '')");
        $delete->bind_param("is", $remove_id, $session_id);
    }
    
    $delete->execute();
    header("Location: cart.php");
    exit();
}

// Handle quantity update via AJAX
if (isset($_POST['update_quantity_ajax'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($username) {
        // User is logged in - update by username
        // Get product price to calculate new subtotal
        $price_query = "SELECT price FROM carts1 WHERE product_id = ? AND username = ?";
        $stmt = $db->prepare($price_query);
        $stmt->bind_param("is", $product_id, $username);
        $stmt->execute();
        $price_result = $stmt->get_result();
        $price_row = $price_result->fetch_assoc();
        $price = $price_row['price'];
        
        $subtotal = $price * $quantity;
        
        $update = $db->prepare("UPDATE carts1 SET quantity = ?, subtotal = ? WHERE product_id = ? AND username = ?");
        $update->bind_param("idis", $quantity, $subtotal, $product_id, $username);
        $update->execute();
        
        // Return updated subtotal and total
        $total_query = "SELECT SUM(subtotal) as total FROM carts1 WHERE username = ?";
        $stmt = $db->prepare($total_query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $total_result = $stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $total = $total_row['total'] ?? 0;
    } else {
        // User is not logged in - update by session_id
        $session_id = session_id();
        
        // Get product price to calculate new subtotal
        $price_query = "SELECT price FROM carts1 WHERE product_id = ? AND session_id = ? AND (username IS NULL OR username = '')";
        $stmt = $db->prepare($price_query);
        $stmt->bind_param("is", $product_id, $session_id);
        $stmt->execute();
        $price_result = $stmt->get_result();
        $price_row = $price_result->fetch_assoc();
        $price = $price_row['price'];
        
        $subtotal = $price * $quantity;
        
        $update = $db->prepare("UPDATE carts1 SET quantity = ?, subtotal = ? WHERE product_id = ? AND session_id = ? AND (username IS NULL OR username = '')");
        $update->bind_param("idis", $quantity, $subtotal, $product_id, $session_id);
        $update->execute();
        
        // Return updated subtotal and total
        $total_query = "SELECT SUM(subtotal) as total FROM carts1 WHERE session_id = ? AND (username IS NULL OR username = '')";
        $stmt = $db->prepare($total_query);
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $total_result = $stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $total = $total_row['total'] ?? 0;
    }
    
    echo json_encode([
        'success' => true,
        'subtotal' => number_format($subtotal, 2),
        'total' => number_format($total, 2)
    ]);
    exit();
}

// Get cart items from cart table
if ($username) {
    // User is logged in - get cart by username
    $cart_query = "SELECT product_id, session_id, product_name, price, image_url, quantity, category, subtotal 
                   FROM carts1 
                   WHERE username = ?";
    $stmt = $db->prepare($cart_query);
    $stmt->bind_param("s", $username);
} else {
    // User is not logged in - get cart by session_id
    $cart_query = "SELECT product_id, session_id, product_name, price, image_url, quantity, category, subtotal 
                   FROM carts1 
                   WHERE session_id = ? AND (username IS NULL OR username = '')";
    $stmt = $db->prepare($cart_query);
    $session_id = session_id();
    $stmt->bind_param("s", $session_id);
}

$stmt->execute();
$cart_items = $stmt->get_result();

// Calculate total
$total = 0;
$cart_items_data = [];
while ($row = $cart_items->fetch_assoc()) {
    $total += $row['subtotal'];
    $cart_items_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart</title>
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
        a, button {
            cursor: pointer;
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
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 95%;
            overflow-x: auto;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            min-width: 800px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .remove-btn:hover {
            background: #c0392b;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 18px;
        }
        
        .continue-btn {
            display: inline-block;
            background: #2ecc71;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .continue-btn:hover {
            background: #27ae60;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }
        
        .checkout-btn {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            margin-left: 10px;
            transition: background 0.3s;
        }
        
        .checkout-btn:hover {
            background: #e67e22;
        }
        
        .action-buttons {
            display: flex;
            justify-content: flex-end;
        }
        
        .subtotal {
            font-weight: bold;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
            display: none;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .info-text {
            background-color: #e7f3fe;
            border-left: 4px solid #2196F3;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        @media screen and (max-width: 768px) {
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
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: flex-end;
            }
            
            .checkout-btn {
                margin-left: 0;
                margin-top: 10px;
            }
        }
        
        @media screen and (max-width: 480px) {
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .container {
                padding: 10px;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            .quantity-input {
                width: 50px;
                padding: 6px;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="navbar-brand" href="#">PetCare</a>
        <div class="nav-links">
            <?php if (isset($_SESSION['username'])): ?>
                <span class="user-info">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <?php endif; ?>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
            <a href="home.php">Home</a>
            <a href="shoppinghome.php">Shop</a>
            <a href="cart.php">Cart</a>
            <a href="order.php">My Order</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1>Your Shopping Cart</h1>
        
        <?php if ($username): ?>
        <div class="info-text">
            <i class="fas fa-info-circle"></i> You are logged in as <?php echo htmlspecialchars($username); ?>. Your cart is saved to your account.
        </div>
        <?php endif; ?>
        
        <?php if (!empty($cart_items_data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items_data as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                    <td>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-img">
                        
                    </td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                    <td>
                        <input type="number" 
                               class="quantity-input" 
                               value="<?php echo $item['quantity']; ?>" 
                               min="1" 
                               data-product-id="<?php echo $item['product_id']; ?>"
                               data-price="<?php echo $item['price']; ?>">
                    </td>
                    <td class="subtotal" id="subtotal-<?php echo $item['product_id']; ?>">
                        ₹<?php echo number_format($item['subtotal'], 2); ?>
                    </td>
                    <td><a href="cart.php?remove=<?php echo $item['product_id']; ?>" class="remove-btn">Remove</a></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5" style="text-align: right;">Total:</td>
                    <td id="cart-total">₹<?php echo number_format($total, 2); ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        
        <div class="action-buttons">
            <a href="shoppinghome.php" class="continue-btn">Continue Shopping</a>
            <?php if (!empty($cart_items_data)): ?>
                <a href="checkout.php" class="checkout-btn" id="checkout-btn">
                    Proceed to Checkout
                    <span class="loading" id="checkout-loading"></span>
                </a>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="shoppinghome.php" class="continue-btn">Continue Shopping</a>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Automatically update quantity when changed
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.getAttribute('data-product-id');
                const quantity = parseInt(this.value);
                
                if (quantity < 1) {
                    this.value = 1;
                    return;
                }
                
                // Show loading indicator
                const checkoutLoading = document.getElementById('checkout-loading');
                if (checkoutLoading) checkoutLoading.style.display = 'inline-block';
                
                // Send AJAX request to update quantity
                const formData = new FormData();
                formData.append('update_quantity_ajax', 'true');
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                
                fetch('cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update subtotal for this item
                        document.getElementById('subtotal-' + productId).textContent = '₹' + data.subtotal;
                        
                        // Update cart total
                        document.getElementById('cart-total').textContent = '₹' + data.total;
                    }
                    
                    // Hide loading indicator
                    if (checkoutLoading) checkoutLoading.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Hide loading indicator
                    if (checkoutLoading) checkoutLoading.style.display = 'none';
                });
            });
        });
        
        document.getElementById('checkout-btn')?.addEventListener('click', function(e) {
            // For debugging - you can remove this in production
            console.log('Checkout button clicked');
            
            // You can add additional validation here if needed
            const cartItems = <?php echo json_encode($cart_items_data); ?>;
            if (cartItems.length === 0) {
                e.preventDefault();
                alert('Your cart is empty. Please add items before checkout.');
            }
        });
    </script>
</body>
</html>
<?php $db->close(); ?>