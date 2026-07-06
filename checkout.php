<?php
// Start session and set error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Define constants for configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'petdemo');
define('TAX_RATE', 0.1); // 10% tax

// Database connection class
class Database {
    private $connection;
    
    public function __construct($host, $user, $pass, $name) {
        $this->connection = new mysqli($host, $user, $pass, $name);
        
        if ($this->connection->connect_error) {
            throw new Exception("Database connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function close() {
        $this->connection->close();
    }
}

// Checkout processing class
class CheckoutProcessor {
    private $db;
    private $username;
    private $session_id;
    private $errors = [];
    private $cart_items = [];
    private $subtotal = 0;
    private $tax = 0;
    private $total = 0;
    
    public function __construct($db, $username, $session_id) {
        $this->db = $db;
        $this->username = $username;
        $this->session_id = $session_id;
    }
    
    public function validateTables() {
        $required_tables = ['carts1', 'products2', 'orders7', 'order_items7'];
        $missing_tables = [];
        
        foreach ($required_tables as $table) {
            $result = $this->db->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            throw new Exception("Missing required tables: " . implode(', ', $missing_tables));
        }
    }
    
    public function loadCartItems() {
        if ($this->username) {
            // User is logged in - get cart by username
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.price, c.quantity, p.image_url 
                FROM carts1 c 
                JOIN products2 p ON c.product_id = p.id 
                WHERE c.username = ?
            ");
            
            if ($stmt === false) {
                throw new Exception("Error preparing cart query: " . $this->db->error);
            }
            
            $stmt->bind_param("s", $this->username);
        } else {
            // User is not logged in - get cart by session_id
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.price, c.quantity, p.image_url 
                FROM carts1 c 
                JOIN products2 p ON c.product_id = p.id 
                WHERE c.session_id = ? AND (c.username IS NULL OR c.username = '')
            ");
            
            if ($stmt === false) {
                throw new Exception("Error preparing cart query: " . $this->db->error);
            }
            
            $stmt->bind_param("s", $this->session_id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing cart query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $this->cart_items = [];
        $this->subtotal = 0;
        
        while ($row = $result->fetch_assoc()) {
            $this->cart_items[] = $row;
            $this->subtotal += $row['price'] * $row['quantity'];
        }
        
        $stmt->close();
        
        $this->tax = $this->subtotal * TAX_RATE;
        $this->total = $this->subtotal + $this->tax;
    }
    
    public function validateForm($post_data) {
        $this->errors = [];
        
        // Required fields validation
        $required_fields = ['full_name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'country', 'payment_method'];
        foreach ($required_fields as $field) {
            if (empty($post_data[$field])) {
                $this->errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        }
        
        // Email validation
        if (!empty($post_data['email']) && !filter_var($post_data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email format.";
        }
        
        // Phone validation
        if (!empty($post_data['phone']) && !preg_match('/^[0-9]{10}$/', $post_data['phone'])) {
            $this->errors[] = "Phone number must be 10 digits.";
        }
        
        // Pincode validation
        if (!empty($post_data['pincode']) && !preg_match('/^[0-9]{6}$/', $post_data['pincode'])) {
            $this->errors[] = "Postal code must be 6 digits.";
        }
        
        // Cart validation
        if (empty($this->cart_items)) {
            $this->errors[] = "Your cart is empty.";
        }
        
        return empty($this->errors);
    }
    
    public function processOrder($post_data) {
        // Generate order number
        $order_number = 'ORD-' . strtoupper(uniqid());
        
        // Insert order
        $stmt = $this->db->prepare("
            INSERT INTO orders7 (
                order_number, 
                username,
                session_id, 
                full_name, 
                email, 
                phone, 
                address, 
                city,
                state,
                pincode, 
                country, 
                payment_method, 
                subtotal, 
                tax, 
                total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt === false) {
            throw new Exception("Error preparing order query: " . $this->db->error);
        }
        
        $stmt->bind_param(
            "ssssssssssssddd", 
            $order_number, 
            $this->username,
            $this->session_id, 
            $post_data['full_name'], 
            $post_data['email'], 
            $post_data['phone'], 
            $post_data['address'], 
            $post_data['city'],
            $post_data['state'],
            $post_data['pincode'], 
            $post_data['country'], 
            $post_data['payment_method'], 
            $this->subtotal, 
            $this->tax, 
            $this->total
        );
        
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Failed to place order. Please try again.");
        }
        
        $order_id = $this->db->insert_id;
        $stmt->close();
        
        // Insert order items
        $item_stmt = $this->db->prepare("
            INSERT INTO order_items7 (
                order_id, 
                product_id, 
                product_name, 
                price, 
                quantity
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($item_stmt === false) {
            throw new Exception("Error preparing order items query: " . $this->db->error);
        }
        
        foreach ($this->cart_items as $item) {
            $item_stmt->bind_param(
                "iisdi", 
                $order_id, 
                $item['id'], 
                $item['name'], 
                $item['price'], 
                $item['quantity']
            );
            
            if (!$item_stmt->execute()) {
                $item_stmt->close();
                throw new Exception("Error executing order items query.");
            }
        }
        
        $item_stmt->close();
        
        // Clear cart
        if ($this->username) {
            $clear_stmt = $this->db->prepare("DELETE FROM carts1 WHERE username = ?");
            $clear_stmt->bind_param("s", $this->username);
        } else {
            $clear_stmt = $this->db->prepare("DELETE FROM carts1 WHERE session_id = ? AND (username IS NULL OR username = '')");
            $clear_stmt->bind_param("s", $this->session_id);
        }
        
        if ($clear_stmt === false) {
            throw new Exception("Error preparing clear cart query: " . $this->db->error);
        }
        
        if (!$clear_stmt->execute()) {
            $clear_stmt->close();
            throw new Exception("Error clearing cart.");
        }
        
        $clear_stmt->close();
        
        return $order_id;
    }
    
    public function getCartItems() {
        return $this->cart_items;
    }
    
    public function getSubtotal() {
        return $this->subtotal;
    }
    
    public function getTax() {
        return $this->tax;
    }
    
    public function getTotal() {
        return $this->total;
    }
    
    public function getErrors() {
        return $this->errors;
    }
}

// Main processing
try {
    // Initialize database connection
    $database = new Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db = $database->getConnection();
    
    // Get username from session
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    
    // Initialize checkout processor
    $checkout = new CheckoutProcessor($db, $username, session_id());
    
    // Validate required tables exist
    $checkout->validateTables();
    
    // Load cart items
    $checkout->loadCartItems();
    
    $error = '';
    $order_id = null;
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
        // Sanitize POST data
        $post_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        
        if ($checkout->validateForm($post_data)) {
            try {
                $order_id = $checkout->processOrder($post_data);
                
                // Redirect based on payment method
                if ($post_data['payment_method'] === 'online') {
                    header('Location: payment.php?order_id=' . $order_id);
                    exit();
                } else {
                    header('Location: order_confirmation.php?order_id=' . $order_id);
                    exit();
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = implode("<br>", $checkout->getErrors());
        }
    }
    
    // Get data for display
    $cart_items = $checkout->getCartItems();
    $subtotal = $checkout->getSubtotal();
    $tax = $checkout->getTax();
    $total = $checkout->getTotal();
    
} catch (Exception $e) {
    // Handle errors gracefully
    error_log("Checkout error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pet Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 6px;
            --box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 60px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            min-height: 100vh;
            color: #495057;
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
            transition: var(--transition);
        }
        
        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .user-info {
            color: white;
            margin-right: 15px;
            font-weight: 600;
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
        
        .checkout-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 30px;
        }
        
        .checkout-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            flex: 1;
            min-width: 300px;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .order-summary {
            background: var(--light-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ddd;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 1.1rem;
            border-bottom: none;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 2px solid #ddd;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 10px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 40px;
            color: #6c757d;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: var(--light-color);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .payment-option:hover {
            background: #e9ecef;
        }
        
        .payment-option input {
            margin-right: 15px;
        }
        
        .payment-option label {
            font-weight: 500;
            cursor: pointer;
        }
        
        .payment-icon {
            margin-left: auto;
            color: #6c757d;
            font-size: 1.5rem;
        }
        
        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            margin-top: 20px;
        }
        
        .btn:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-right: 15px;
            border-radius: 4px;
            background: var(--light-color);
            padding: 5px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .cart-item-quantity {
            color: #6c757d;
        }
        
        .error {
            color: var(--danger-color);
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--danger-color);
        }
        
        .valid-feedback {
            color: var(--success-color);
            font-size: 0.875em;
            margin-top: 5px;
            display: none;
        }
        
        .is-valid ~ .valid-feedback {
            display: block;
        }
        
        .is-invalid {
            border-color: var(--danger-color);
        }
        
        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.875em;
            margin-top: 5px;
            display: none;
        }
        
        .is-invalid ~ .invalid-feedback {
            display: block;
        }
        
        .empty-cart {
            text-align: center;
            padding: 30px;
            background: var(--light-color);
            border-radius: var(--border-radius);
        }
        
        .empty-cart a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .empty-cart a:hover {
            text-decoration: underline;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: var(--border-radius);
            width: 80%;
            max-width: 700px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
            animation: modalopen 0.4s;
        }
        
        @keyframes modalopen {
            from {opacity: 0; transform: translateY(-60px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        
        .shipping-info-display {
            background: var(--light-color);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .shipping-info-display p {
            margin-bottom: 10px;
        }
        
        .shipping-info-display strong {
            display: inline-block;
            width: 120px;
        }
        
        .edit-shipping-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .edit-shipping-btn:hover {
            background: var(--primary-dark);
        }
        
        @media screen and (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
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
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="navbar-brand" href="#">PetCare</a>
        <div class="nav-links">
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
            <a href="home.php">Home</a>
            <a href="shoppinghome.php">Shop</a>
            <a href="cart.php">Cart</a>
            <a href="order.php">My Order</a>
            <a href="shopping.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1>Checkout</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="checkout.php" id="checkoutForm" novalidate>
            <div class="checkout-container">
                <!-- Payment Information -->
                <div class="checkout-section">
                    <h2 class="section-title">Payment Information</h2>
                    
                    <!-- Shipping Information Display -->
                    <div id="shippingInfoDisplay" class="shipping-info-display" style="display: none;">
                        <p><strong>Name:</strong> <span id="displayFullName"></span></p>
                        <p><strong>Email:</strong> <span id="displayEmail"></span></p>
                        <p><strong>Phone:</strong> <span id="displayPhone"></span></p>
                        <p><strong>Address:</strong> <span id="displayAddress"></span></p>
                        <p><strong>City:</strong> <span id="displayCity"></span>, <span id="displayState"></span></p>
                        <p><strong>Postal Code:</strong> <span id="displayPincode"></span></p>
                        <p><strong>Country:</strong> <span id="displayCountry"></span></p>
                    </div>
                    
                    <button type="button" id="editShippingBtn" class="edit-shipping-btn">
                        <i class="fas fa-edit"></i> Shipping Information
                    </button>
                    
                    <h2 class="section-title" style="margin-top: 30px;">Payment Method</h2>
                    
                    <div class="payment-option">
                        <input type="radio" id="online_payment" name="payment_method" value="online" required
                               <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'online') ? 'checked' : ''; ?>>
                        <label for="online_payment">Online Payment</label>
                        <span class="payment-icon"><i class="fas fa-credit-card"></i></span>
                    </div>
                    
                    <div class="payment-option">
                        <input type="radio" id="cod" name="payment_method" value="cod"
                               <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cod') ? 'checked' : ''; ?>>
                        <label for="cod">Cash on Delivery</label>
                        <span class="payment-icon"><i class="fas fa-money-bill-wave"></i></span>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn">Place Order</button>
                </div>
                
                <!-- Order Summary -->
                <div class="checkout-section">
                    <h2 class="section-title">Your Order</h2>
                    
                    <?php if (!empty($cart_items)): ?>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                                <div class="cart-item-details">
                                    <div class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="cart-item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="cart-item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>₹<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax (10%):</span>
                                <span>₹<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span>₹<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-cart">
                            <p>Your cart is empty.</p>
                            <p><a href="shopping.php">Continue shopping</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Hidden fields for shipping information -->
            <input type="hidden" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            <input type="hidden" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <input type="hidden" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            <input type="hidden" id="address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
            <input type="hidden" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
            <input type="hidden" id="state" name="state" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
            <input type="hidden" id="pincode" name="pincode" value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : ''; ?>">
            <input type="hidden" id="country" name="country" value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>">
        </form>
    </div>
    
    <!-- Shipping Information Modal -->
    <div id="shippingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="section-title">Shipping Information</h2>
            
            <div class="form-group">
                <label for="modal_full_name">Full Name</label>
                <input type="text" id="modal_full_name" class="form-control" required>
                <div class="valid-feedback">Looks good!</div>
                <div class="invalid-feedback">Please enter your full name.</div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="modal_email">Email Address</label>
                        <input type="email" id="modal_email" class="form-control" required>
                        <i class="fas fa-envelope input-icon"></i>
                        <div class="valid-feedback">Looks good!</div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="modal_phone">Phone Number</label>
                        <input type="tel" id="modal_phone" class="form-control" pattern="[0-9]{10}" required>
                        <i class="fas fa-phone input-icon"></i>
                        <div class="valid-feedback">Looks good!</div>
                        <div class="invalid-feedback">Please enter a 10-digit phone number.</div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="modal_address">Street Address</label>
                <textarea id="modal_address" class="form-control" rows="3" required></textarea>
                <div class="valid-feedback">Looks good!</div>
                <div class="invalid-feedback">Please enter your address.</div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="modal_city">City</label>
                        <input type="text" id="modal_city" class="form-control" required>
                        <div class="valid-feedback">Looks good!</div>
                        <div class="invalid-feedback">Please enter your city.</div>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="modal_state">State/Province</label>
                        <input type="text" id="modal_state" class="form-control" required>
                        <div class="valid-feedback">Looks good!</div>
                        <div class="invalid-feedback">Please enter your state/province.</div>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="modal_pincode">Postal/Zip Code</label>
                        <input type="text" id="modal_pincode" class="form-control" pattern="[0-9]{6}" required>
                        <div class="valid-feedback">Looks good!</div>
                        <div class="invalid-feedback">Please enter a 6-digit postal code.</div>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="modal_country">Country</label>
                        <select id="modal_country" class="form-control" required>
                            <option value="">Select Country</option>
                            <option value="US">United States</option>
                            <option value="UK">United Kingdom</option>
                            <option value="CA">Canada</option>
                            <option value="AU">Australia</option>
                            <option value="IN">India</option>
                        </select>
                        <div class="valid-feedback">Looks good!</div>
                        <div class="invalid-feedback">Please select your country.</div>
                    </div>
                </div>
            </div>
            
            <button type="button" id="saveShippingBtn" class="btn">Save Shipping Information</button>
        </div>
    </div>

    <script>
    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('shippingModal');
        const editBtn = document.getElementById('editShippingBtn');
        const closeBtn = document.getElementsByClassName('close')[0];
        const saveBtn = document.getElementById('saveShippingBtn');
        
        // Populate modal with existing values if any
        const fullName = document.getElementById('full_name').value;
        if (fullName) {
            document.getElementById('modal_full_name').value = fullName;
            document.getElementById('modal_email').value = document.getElementById('email').value;
            document.getElementById('modal_phone').value = document.getElementById('phone').value;
            document.getElementById('modal_address').value = document.getElementById('address').value;
            document.getElementById('modal_city').value = document.getElementById('city').value;
            document.getElementById('modal_state').value = document.getElementById('state').value;
            document.getElementById('modal_pincode').value = document.getElementById('pincode').value;
            document.getElementById('modal_country').value = document.getElementById('country').value;
            
            // Update shipping info display
            updateShippingDisplay();
        }
        
        // Open modal
        editBtn.onclick = function() {
            modal.style.display = 'block';
        }
        
        // Close modal
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Save shipping information
        saveBtn.onclick = function() {
            // Validate modal form
            const modalInputs = modal.querySelectorAll('input, select, textarea');
            let isValid = true;
            
            modalInputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            });
            
            if (!isValid) return;
            
            // Copy values to hidden fields
            document.getElementById('full_name').value = document.getElementById('modal_full_name').value;
            document.getElementById('email').value = document.getElementById('modal_email').value;
            document.getElementById('phone').value = document.getElementById('modal_phone').value;
            document.getElementById('address').value = document.getElementById('modal_address').value;
            document.getElementById('city').value = document.getElementById('modal_city').value;
            document.getElementById('state').value = document.getElementById('modal_state').value;
            document.getElementById('pincode').value = document.getElementById('modal_pincode').value;
            document.getElementById('country').value = document.getElementById('modal_country').value;
            
            // Update shipping info display
            updateShippingDisplay();
            
            // Close modal
            modal.style.display = 'none';
        }
        
        // Function to update shipping information display
        function updateShippingDisplay() {
            const display = document.getElementById('shippingInfoDisplay');
            const fullName = document.getElementById('full_name').value;
            
            if (fullName) {
                document.getElementById('displayFullName').textContent = fullName;
                document.getElementById('displayEmail').textContent = document.getElementById('email').value;
                document.getElementById('displayPhone').textContent = document.getElementById('phone').value;
                document.getElementById('displayAddress').textContent = document.getElementById('address').value;
                document.getElementById('displayCity').textContent = document.getElementById('city').value;
                document.getElementById('displayState').textContent = document.getElementById('state').value;
                document.getElementById('displayPincode').textContent = document.getElementById('pincode').value;
                
                const countrySelect = document.getElementById('country');
                const countryText = countrySelect.options[countrySelect.selectedIndex].text;
                document.getElementById('displayCountry').textContent = countryText;
                
                display.style.display = 'block';
            }
        }
        
        // Auto-fill the full name with username if available
        const username = "<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>";
        if (username && !document.getElementById('modal_full_name').value) {
            document.getElementById('modal_full_name').value = username;
            document.getElementById('full_name').value = username;
        }
        
        // If user is logged in, show welcome message
        if (username) {
            const userWelcome = document.createElement('span');
            userWelcome.className = 'user-info';
            userWelcome.textContent = 'Welcome, ' + username + '!';
            
            // Insert welcome message at the beginning of nav-links
            const navLinks = document.querySelector('.nav-links');
            if (navLinks.firstChild) {
                navLinks.insertBefore(userWelcome, navLinks.firstChild);
            } else {
                navLinks.appendChild(userWelcome);
            }
        }
    });
</script>
</body>
</html>
<?php 
// Close database connection
if (isset($database)) {
    $database->close();
}
?>