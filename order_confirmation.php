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

// Get orders for the current session
$session_id = session_id();
$orders_query = $db->prepare("SELECT * FROM orders7 WHERE session_id = ? ORDER BY created_at DESC");
$orders_query->bind_param("s", $session_id);
$orders_query->execute();
$orders = $orders_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Pet Shop</title>
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
            margin: 80px auto 40px;
            padding: 0 20px;
        }
        
        h1 {
            text-align: center;
            color: #eff3f7ff;
            margin-bottom: 30px;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .order-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-number {
            font-weight: 600;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        
        .order-date {
            color: #6c757d;
        }
        
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-processing {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-shipped {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-details {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 20px;
        }
        
        .order-section {
            flex: 1;
            min-width: 250px;
        }
        
        .section-title {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            width: 120px;
        }
        
        .detail-value {
            color: #6c757d;
            flex: 1;
        }
        
        .order-items {
            margin-top: 30px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-right: 15px;
            border-radius: 5px;
            background: #f8f9fa;
            padding: 5px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 1.1rem;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .no-orders {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .no-orders-icon {
            font-size: 50px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .delivery-info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .delivery-date {
            font-weight: bold;
            color: #28a745;
        }
        
        .delivery-status {
            margin-top: 5px;
            font-size: 0.9rem;
        }
        
        .tracking-progress {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 20px;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .progress-bar {
            position: absolute;
            top: 10px;
            left: 0;
            height: 2px;
            background: #28a745;
            z-index: 2;
            transition: width 0.3s ease;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 3;
        }
        
        .step-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        .step.active .step-icon {
            background: #28a745;
            color: white;
        }
        
        .step.completed .step-icon {
            background: #28a745;
            color: white;
        }
        
        .step-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: center;
        }
        
        .step.active .step-label,
        .step.completed .step-label {
            color: #28a745;
            font-weight: 600;
        }
        
        .tracking-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .tracking-btn {
            padding: 8px 15px;
            borderRadius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        @media screen and (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .order-status {
                align-self: flex-start;
            }
            
            .progress-steps {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .step {
                flex: 1 0 60px;
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
        <h1>My Orders</h1>
        
        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <?php
                // Get order items
                $items_query = $db->prepare("SELECT oi.*, p.image_url as product_image 
                                           FROM order_items7 oi 
                                           LEFT JOIN products2 p ON oi.product_id = p.id 
                                           WHERE oi.order_id = ?");
                $items_query->bind_param("i", $order['id']);
                $items_query->execute();
                $items = $items_query->get_result();
                
                // Format dates
                $order_date = new DateTime($order['created_at']);
                $order_date_formatted = $order_date->format('F j, Y');
                
                // Use stored delivery date if available, otherwise calculate it
                if (!empty($order['delivery_date'])) {
                    $delivery_date = new DateTime($order['delivery_date']);
                    $delivery_date_formatted = $delivery_date->format('F j, Y');
                } else {
                    // Calculate delivery date (3-5 business days from order date)
                    $delivery_date = clone $order_date;
                    $business_days_to_add = rand(3, 5);
                    $added_days = 0;
                    while ($added_days < $business_days_to_add) {
                        $delivery_date->modify('+1 day');
                        if ($delivery_date->format('N') < 6) {
                            $added_days++;
                        }
                    }
                    $delivery_date_formatted = $delivery_date->format('F j, Y');
                    
                    // Store the calculated delivery date in the database
                    $update_delivery = $db->prepare("UPDATE orders7 SET delivery_date = ? WHERE id = ?");
                    $delivery_date_db = $delivery_date->format('Y-m-d');
                    $update_delivery->bind_param("si", $delivery_date_db, $order['id']);
                    $update_delivery->execute();
                }
                
                // Format delivered date if available
                $delivered_date_formatted = '';
                if (!empty($order['delivered_at'])) {
                    $delivered_date = new DateTime($order['delivered_at']);
                    $delivered_date_formatted = $delivered_date->format('F j, Y');
                }
                
                // Determine status class
                $status_class = 'status-' . $order['status'];
                
                // Determine progress for tracking
                $progress = 0;
                $current_step = 1;
                $steps = [
                    ['label' => 'Order Placed', 'icon' => 'fa-shopping-cart'],
                    ['label' => 'Processing', 'icon' => 'fa-cog'],
                    ['label' => 'Shipped', 'icon' => 'fa-truck'],
                    ['label' => 'Delivered', 'icon' => 'fa-check']
                ];
                
                switch ($order['status']) {
                    case 'processing':
                        $progress = 33;
                        $current_step = 2;
                        break;
                    case 'shipped':
                        $progress = 66;
                        $current_step = 3;
                        break;
                    case 'delivered':
                        $progress = 100;
                        $current_step = 4;
                        break;
                    default:
                        $progress = 0;
                        $current_step = 1;
                }
                ?>
                
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date">Placed on <?php echo $order_date_formatted; ?></div>
                        </div>
                        <div class="order-status <?php echo $status_class; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </div>
                    </div>
                    
                    <div class="delivery-info">
                        <div class="detail-row">
                            <div class="detail-label">Expected Delivery:</div>
                            <div class="detail-value delivery-date"><?php echo $delivery_date_formatted; ?></div>
                        </div>
                        <?php if ($order['status'] === 'delivered' && !empty($delivered_date_formatted)): ?>
                            <div class="detail-row">
                                <div class="detail-label">Delivered on:</div>
                                <div class="detail-value"><?php echo $delivered_date_formatted; ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="delivery-status">
                            <?php if ($order['status'] === 'delivered'): ?>
                                <i class="fas fa-check-circle" style="color: #28a745;"></i> Your order has been delivered
                            <?php elseif ($order['status'] === 'shipped'): ?>
                                <i class="fas fa-truck" style="color: #17a2b8;"></i> Your order is on the way
                            <?php elseif ($order['status'] === 'processing'): ?>
                                <i class="fas fa-cog" style="color: #ffc107;"></i> Your order is being processed
                            <?php else: ?>
                                <i class="fas fa-shopping-cart" style="color: #6c757d;"></i> Your order has been received
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="tracking-progress">
                        <h3 class="section-title">Order Tracking</h3>
                        <div class="progress-steps">
                            <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                            <?php foreach ($steps as $index => $step): ?>
                                <div class="step <?php echo ($index + 1) < $current_step ? 'completed' : ''; ?> <?php echo ($index + 1) == $current_step ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="fas <?php echo $step['icon']; ?>"></i>
                                    </div>
                                    <div class="step-label"><?php echo $step['label']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                            <div class="tracking-actions">
                                <button class="tracking-btn btn-primary" onclick="window.location.href='contact.php'">
                                    <i class="fas fa-headset"></i> Contact Support
                                </button>
                                
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-section">
                            <h3 class="section-title">Shipping Details</h3>
                            <div class="detail-row">
                                <div class="detail-label">Name:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['full_name']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Address:</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($order['address']); ?><br>
                                    <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?><br>
                                    <?php echo htmlspecialchars($order['pincode']); ?>, <?php echo htmlspecialchars($order['country']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-section">
                            <h3 class="section-title">Payment Details</h3>
                            <div class="detail-row">
                                <div class="detail-label">Method:</div>
                                <div class="detail-value">
                                    <?php 
                                    echo htmlspecialchars(ucfirst($order['payment_method']));
                                    if ($order['payment_method'] === 'cod') {
                                        echo ' (Cash on Delivery)';
                                    } else {
                                        echo ' (Online Payment)';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Email:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['email']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phone:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <h3 class="section-title">Order Items</h3>
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <div class="order-item">
                                <?php
                                // Use product image from products2 if available, otherwise use the default
                                $image_url = !empty($item['product_image']) ? $item['product_image'] : 
                                            ($item['image_url'] ?? 'default-product.jpg');
                                ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-img">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="item-price">₹<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?></div>
                                </div>
                                <div>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (10%):</span>
                            <span>₹<?php echo number_format($order['tax'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>FREE</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>₹<?php echo number_format($order['total'], 2); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($order['status'] === 'delivered'): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <button class="btn" onclick="window.location.href='contact.php'">
                                <i class="fas fa-headset"></i> Need Help with Your Order?
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-orders">
                <div class="no-orders-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h2>No Orders Found</h2>
                <p>You haven't placed any orders yet.</p>
                <a href="home.php" class="btn">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order has been cancelled successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order.');
                });
            }
        }
    </script>
</body>
</html>
<?php
$db->close();
?>