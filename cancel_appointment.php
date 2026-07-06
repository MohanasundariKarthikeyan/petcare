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

// Handle cancel appointment request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirmation_number'])) {
    $confirmation_number = $_POST['confirmation_number'];
    
    // First, get appointment details before updating
    $stmt_select = $conn->prepare("SELECT * FROM appointments4 WHERE confirmation_number = ?");
    $stmt_select->bind_param("s", $confirmation_number);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    
    if ($result->num_rows > 0) {
        $appointment_details = $result->fetch_assoc();
        
        // Update the appointment status to 'cancelled' instead of deleting
        $stmt = $conn->prepare("UPDATE appointments4 SET status = 'cancelled' WHERE confirmation_number = ?");
        $stmt->bind_param("s", $confirmation_number);
        
        if ($stmt->execute()) {
            $message = "Appointment with confirmation number " . $confirmation_number . " has been successfully cancelled.";
            $message_type = "success";
            
            // Refresh the appointment details to show updated status
            $appointment_details['status'] = 'cancelled';
        } else {
            $message = "Error cancelling appointment: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "No appointment found with confirmation number: " . $confirmation_number;
        $message_type = "error";
    }
    $stmt_select->close();
} else {
    $message = "No confirmation number provided.";
    $message_type = "error";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment - PetCare</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
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
            max-width: 800px;
            width: 90%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .header {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
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
        
        .confirmation-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border-left: 4px solid #e53e3e;
        }
        
        .confirmation-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .confirmation-icon {
            font-size: 40px;
            color: #e53e3e;
            margin-right: 15px;
        }
        
        .confirmation-title {
            font-size: 24px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .appointment-details {
            margin-bottom: 30px;
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
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-cancelled {
            background-color: #fed7d7;
            color: #e53e3e;
        }
        
        .confirmation-number {
            font-size: 1.5rem;
            color: #e53e3e;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
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
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
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
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                width: 100%;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
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
            <p>Appointment Cancellation</p>
        </div>
        
        <div class="content">
            <div class="message <?php echo $message_type; ?>">
                <p><?php echo $message; ?></p>
            </div>
            
            <?php if (!empty($appointment_details)): ?>
                <div class="confirmation-card">
                    <div class="confirmation-header">
                        <i class="fas fa-times-circle confirmation-icon"></i>
                        <h2 class="confirmation-title">Appointment Cancelled</h2>
                    </div>
                    
                    <p>Your appointment has been successfully cancelled. We're sorry to see you go!</p>
                    
                    <div class="confirmation-number">
                        Cancelled Confirmation #: <?php echo htmlspecialchars($appointment_details['confirmation_number']); ?>
                    </div>
                    
                    <div class="appointment-details">
                        <h3>Cancelled Appointment Details</h3>
                        <div class="detail-item">
                            <span class="detail-label">Pet Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment_details['pet_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Owner Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment_details['owner_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Breed:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment_details['breed']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Appointment Date:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment_details['appointment_date']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Appointment Time:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment_details['appointment_time']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <span class="status-badge status-cancelled"><?php echo htmlspecialchars($appointment_details['status']); ?></span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <a href="appointment_payment.php" class="btn btn-primary">Book New Appointment</a>
                        <a href="home.html" class="btn btn-secondary">Return to Home</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="confirmation-card">
                    <div class="confirmation-header">
                        <i class="fas fa-exclamation-circle confirmation-icon"></i>
                        <h2 class="confirmation-title">Unable to Process Cancellation</h2>
                    </div>
                    
                    <p>We couldn't find the appointment you're trying to cancel. Please check your confirmation number or contact support.</p>
                    
                    <div class="actions">
                        <a href="appointment_payment.php" class="btn btn-primary">Book New Appointment</a>
                        <a href="home.html" class="btn btn-secondary">Return to Home</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>