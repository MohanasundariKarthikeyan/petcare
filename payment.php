<?php
session_start();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Validate order_id parameter
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header('Location: home.php');
    exit();
}

$order_id = (int)$_GET['order_id'];

// Database configuration (should be in a separate config file in production)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'petdemo');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$order = null;
$error = '';
$success = '';

try {
    // Database connection with error handling
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    
    // Set charset to UTF-8
    $db->set_charset("utf8mb4");

    // Get order details with prepared statement
    $order_query = $db->prepare("SELECT id, order_number, total, status, created_at FROM orders7 WHERE id = ?");
    if (!$order_query) {
        throw new Exception("Prepare failed: " . $db->error);
    }
    
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    
    $result = $order_query->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Order not found.");
    }
    
    $order = $result->fetch_assoc();
    
    // Check if order is already paid
    if ($order['status'] === 'paid') {
        $success = "This order has already been paid.";
    }
    
    // Process payment if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token.");
        }
        
        // Validate payment form data
        $card_number = sanitize_input($_POST['card_number']);
        $expiry = sanitize_input($_POST['expiry']);
        $cvv = sanitize_input($_POST['cvv']);
        $name_on_card = sanitize_input($_POST['name_on_card']);
        
        if (!validate_card_number($card_number)) {
            throw new Exception("Invalid card number.");
        }
        
        if (!validate_expiry_date($expiry)) {
            throw new Exception("Invalid expiry date.");
        }
        
        if (!validate_cvv($cvv)) {
            throw new Exception("Invalid CVV.");
        }
        
        if (empty($name_on_card)) {
            throw new Exception("Name on card is required.");
        }
        
        // Simulate payment processing (in a real application, integrate with payment gateway)
        // Update order status to paid
        $update_order = $db->prepare("UPDATE orders7 SET status = 'paid', payment_date = NOW() WHERE id = ?");
        if (!$update_order) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        $update_order->bind_param("i", $order_id);
        
        if (!$update_order->execute()) {
            throw new Exception("Update failed: " . $update_order->error);
        }
        
        // Store order ID in session for confirmation page
        $_SESSION['last_order_id'] = $order_id;
        $_SESSION['order_success'] = true;
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Redirect to confirmation with order ID
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit();
    }
    
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    $db->close();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validate_card_number($number) {
    $number = preg_replace('/\s+/', '', $number);
    return preg_match('/^\d{16}$/', $number);
}

function validate_expiry_date($expiry) {
    return preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry);
}

function validate_cvv($cvv) {
    return preg_match('/^\d{3,4}$/', $cvv);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - Pet Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-hover: #2980b9;
            --secondary-color: #2c3e50;
            --light-gray: #f8f9fa;
            --border-radius: 10px;
            --box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .payment-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            width: 100%;
            max-width: 550px;
            transition: transform 0.3s ease;
        }
        
        .payment-container:hover {
            transform: translateY(-5px);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .payment-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--primary-color);
        }
        
        .payment-icon {
            font-size: 50px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        h1 {
            color: var(--secondary-color);
            margin: 0;
            font-weight: 700;
        }
        
        .subtitle {
            color: #6c757d;
            margin-top: 5px;
        }
        
        .order-info {
            background: var(--light-gray);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .info-label {
            color: #6c757d;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .total-row {
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .payment-form {
            margin-top: 30px;
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
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .card-icons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            justify-content: center;
        }
        
        .card-icon {
            font-size: 30px;
            color: #6c757d;
            transition: color 0.3s;
        }
        
        .card-icon:hover {
            color: var(--primary-color);
        }
        
        .btn-pay {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        .btn-pay:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .security-note {
            text-align: center;
            margin-top: 15px;
            color: #28a745;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 576px) {
            .payment-container {
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <div class="payment-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Secure Payment Gateway</h1>
            <p class="subtitle">Safe and encrypted transaction</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($order && $order['status'] !== 'paid'): ?>
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Order Number:</span>
                <span class="info-value">#<?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Amount to Pay:</span>
                <span class="info-value">₹<?php echo number_format($order['total'], 2); ?></span>
            </div>
            <div class="info-row total-row">
                <span class="info-label">Total Amount:</span>
                <span class="info-value">₹<?php echo number_format($order['total'], 2); ?></span>
            </div>
        </div>
        
        <form method="POST" action="order_confirmation.php" id="payment-form" class="payment-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" required maxlength="19">
                <small class="text-muted">Enter 16-digit card number</small>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" class="form-control" placeholder="MM/YY" required maxlength="5">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" required maxlength="4">
                        <small class="text-muted">3 or 4 digits on back</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="name_on_card">Name on Card</label>
                <input type="text" id="name_on_card" name="name_on_card" class="form-control" placeholder="John Doe" required>
            </div>
            
            <div class="card-icons">
                <i class="fab fa-cc-visa card-icon"></i>
                <i class="fab fa-cc-mastercard card-icon"></i>
                <i class="fab fa-cc-amex card-icon"></i>
                <i class="fab fa-cc-discover card-icon"></i>
            </div>
            
            <button type="submit" name="pay_now" id="pay-button" class="btn-pay">
                <i class="fas fa-lock"></i> Pay Now ₹<?php echo number_format($order['total'], 2); ?>
            </button>
            
            <div class="security-note">
                <i class="fas fa-shield-alt"></i> Your payment is secured with 256-bit SSL encryption
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
        
        // Format expiry date
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '').substring(0, 4);
            
            if (value.length > 2) {
                e.target.value = value.substring(0, 2) + '/' + value.substring(2);
            } else {
                e.target.value = value;
            }
        });
        
        // Only allow numbers in CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
        
        // Show loading spinner on form submission
        document.getElementById('payment-form').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
            document.getElementById('pay-button').disabled = true;
        });
    </script>
</body>
</html>