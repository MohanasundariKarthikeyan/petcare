<?php
session_start();
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "petdemo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = '';
$message_type = ''; // 'success' or 'error'
$appointment_details = [];
$appointments = []; // Array to hold all user appointments

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    $message = "Please log in to view your appointments.";
    $message_type = "error";
} else {
    $current_user = $_SESSION['username'];
    
    // Handle payment request
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        
        // Verify the appointment belongs to the logged-in user before updating
        $verify_stmt = $conn->prepare("SELECT * FROM appointments4 WHERE id = ? AND username = ?");
        $verify_stmt->bind_param("is", $appointment_id, $current_user);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Update payment status in database
            $stmt = $conn->prepare("UPDATE appointments4 SET payment_status = 'paid', status = 'confirmed' WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            
            if ($stmt->execute()) {
                $message = "Payment successful! Your appointment has been confirmed.";
                $message_type = "success";
            } else {
                $message = "Error processing payment: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "You are not authorized to update this appointment.";
            $message_type = "error";
        }
        $verify_stmt->close();
    } 
    // Handle cancel appointment request
    elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        
        // Verify the appointment belongs to the logged-in user before deleting
        $verify_stmt = $conn->prepare("SELECT * FROM appointments4 WHERE id = ? AND username = ?");
        $verify_stmt->bind_param("is", $appointment_id, $current_user);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Delete the appointment from the database
            $stmt = $conn->prepare("DELETE FROM appointments4 WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            
            if ($stmt->execute()) {
                $message = "Appointment has been successfully cancelled.";
                $message_type = "success";
            } else {
                $message = "Error cancelling appointment: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "You are not authorized to cancel this appointment.";
            $message_type = "error";
        }
        $verify_stmt->close();
    }
    
    // Fetch all appointments for the logged-in user
    $stmt = $conn->prepare("SELECT * FROM appointments4 WHERE username = ? ORDER BY appointment_date DESC, appointment_time DESC");
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    } else {
        $message = "No appointments found for your account.";
        $message_type = "info";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - PetCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding: 20px;
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
            max-width: 1000px;
            width: 90%;
            margin: 80px auto 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .header {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .appointment-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border-left: 4px solid #38a169;
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .appointment-icon {
            font-size: 40px;
            color: #38a169;
            margin-right: 15px;
        }
        
        .appointment-title {
            font-size: 24px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .appointment-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-confirmed {
            background-color: #c6f6d5;
            color: #2f855a;
        }
        
        .status-pending {
            background-color: #fed7d7;
            color: #c53030;
        }
        
        .appointment-details {
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-label {
            font-weight: 500;
            color: #4a5568;
        }
        
        .detail-value {
            font-weight: 600;
            color: #2d3748;
        }
        
        .confirmation-number {
            font-size: 1.2rem;
            color: #4b6cb7;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4b6cb7;
            text-align: center;
            margin: 20px 0;
        }
        
        .payment-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .payment-info h3 {
            margin-bottom: 15px;
            color: #2d3748;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
            color: white;
            border: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
            color: white;
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background-color: #f0fff4;
            color: #38a169;
            border-left: 4px solid #38a169;
        }
        
        .error {
            background-color: #fed7d7;
            color: #e53e3e;
            border-left: 4px solid #e53e3e;
        }
        
        .info {
            background-color: #ebf8ff;
            color: #3182ce;
            border-left: 4px solid #3182ce;
        }
        
        .cancel-form, .payment-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .no-appointments {
            text-align: center;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .no-appointments i {
            font-size: 60px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                width: 100%;
                margin-top: 70px;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
            .appointment-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .appointment-status {
                margin-top: 10px;
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
            <a href="shopping.php">Shop</a>
            <a href="booked.php">My Appointment</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="header">
            <div class="logo">PetCare+</div>
            <p>My Appointments</p>
        </div>
        
        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($appointments)): ?>
                <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-calendar-check appointment-icon"></i>
                                <h2 class="appointment-title">Appointment Details</h2>
                            </div>
                            <div class="appointment-status <?php echo ($appointment['status'] == 'confirmed') ? 'status-confirmed' : 'status-pending'; ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </div>
                        </div>
                        
                        <div class="confirmation-number">
                            Confirmation #: <?php echo htmlspecialchars($appointment['confirmation_number']); ?>
                        </div>
                        
                        <div class="appointment-details">
                            <div class="detail-item">
                                <span class="detail-label">Pet Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['pet_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Owner Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['owner_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Breed:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['breed']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['phone']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Appointment Date:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['appointment_date']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Appointment Time:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['appointment_time']); ?></span>
                            </div>
                        </div>
                        
                        <div class="price">
                            Amount: Rs. <?php echo number_format($appointment['amount'], 2); ?>
                        </div>
                        
                        <div class="payment-info">
                            <h3>Payment Information</h3>
                            <div class="detail-item">
                                <span class="detail-label">Payment Status:</span>
                                <span class="detail-value" style="color: <?php echo ($appointment['payment_status'] == 'paid') ? '#38a169' : '#e53e3e'; ?>;">
                                    <?php echo ucfirst($appointment['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="actions">
                            <a href="appointment_payment.php" class="btn btn-primary">Book Another Appointment</a>
                            <a href="#" class="btn btn-secondary" onclick="window.print();">Print Details</a>
                        </div>
                        
                        <?php if ($appointment['payment_status'] != 'paid'): ?>
                            <div class="payment-form">
                                <form method="POST">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <button type="submit" name="pay_appointment" class="btn btn-success">Pay Now</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="cancel-form">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment? This action cannot be undone.');">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                <button type="submit" name="cancel_appointment" class="btn btn-danger">Cancel Appointment</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-appointments">
                    <i class="fas fa-calendar-times"></i>
                    <h2>No Appointments Found</h2>
                    <p>You don't have any appointments yet. Book your first appointment now!</p>
                    <div class="actions">
                        <a href="appointment_payment.php" class="btn btn-primary">Book New Appointment</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>