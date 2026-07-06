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
$error = '';
$success = '';
$appointment_data = [];
$booked_times = [];

// Check if user is logged in and get their username
$logged_in_username = '';
if (isset($_SESSION['username'])) {
    $logged_in_username = $_SESSION['username'];
}

// Determine selected date
$today = date('Y-m-d');
if (isset($_POST['appointment_date'])) {
    $selected_date = $_POST['appointment_date'];
} elseif (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
} else {
    $selected_date = $today;
}

// Get booked times for selected date
$booked_stmt = $conn->prepare("SELECT appointment_time FROM appointments4 WHERE appointment_date = ? AND status != 'cancelled'");
$booked_stmt->bind_param("s", $selected_date);
$booked_stmt->execute();
$result = $booked_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $booked_times[] = $row['appointment_time'];
}
$booked_stmt->close();

// Generate time slots (9:00 AM to 5:00 PM, every 30 minutes)
$times = [];
$start = strtotime('09:00');
$end = strtotime('17:00');
while ($start <= $end) {
    $times[] = date('H:i', $start);
    $start = strtotime('+30 minutes', $start);
}

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'])) {
    // Get form data
    $username = trim($_POST['username']);
    $pet_name = trim($_POST['pet_name']);
    $owner_name = trim($_POST['owner_name']);
    $breed = trim($_POST['breed']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $confirmation_number = generateConfirmationNumber();
    
    // Validate data
    if (empty($username) || empty($pet_name) || empty($owner_name) || empty($email) || 
        empty($appointment_date) || empty($appointment_time) || empty($phone) || empty($breed)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Please enter a valid 10-digit phone number.";
    } elseif (in_array($appointment_time, $booked_times)) {
        $error = "Sorry, that time slot is already booked. Please choose another time.";
    } else {
        // Check again if the time slot is still available (to prevent race conditions)
        $check_stmt = $conn->prepare("SELECT id FROM appointments4 WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $check_stmt->bind_param("ss", $appointment_date, $appointment_time);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Sorry, that time slot was just booked by someone else. Please choose another time.";
        } else {
            // Store data in database
            $stmt = $conn->prepare("INSERT INTO appointments4 (username, pet_name, owner_name, breed, email, phone, appointment_date, appointment_time, confirmation_number, status, payment_status, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', 250.00)");
            $stmt->bind_param("sssssssss", $username, $pet_name, $owner_name, $breed, $email, $phone, $appointment_date, $appointment_time, $confirmation_number);
            
            if ($stmt->execute()) {
                $success = "Appointment booked successfully! Your confirmation number is: " . $confirmation_number;
                // Store data to show in payment section
                $appointment_data = [
                    'username' => $username,
                    'pet_name' => $pet_name,
                    'owner_name' => $owner_name,
                    'breed' => $breed,
                    'email' => $email,
                    'phone' => $phone,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'confirmation_number' => $confirmation_number,
                    'amount' => 500.00
                ];
                
                // Store in session for payment processing
                $_SESSION['appointment_details'] = $appointment_data;
                
                // Update booked times after successful booking
                $booked_times[] = $appointment_time;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Function to generate confirmation number
function generateConfirmationNumber() {
    return 'PC-' . substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 6);
}

// Process payment if payment form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cardholder'])) {
    // Get payment data
    $cardholder = trim($_POST['cardholder']);
    $cardnumber = preg_replace('/\s+/', '', trim($_POST['cardnumber'])); // Remove spaces
    $expiry = trim($_POST['expiry']);
    $cvv = trim($_POST['cvv']);
    $confirmation_number = $_POST['confirmation_number'];
    
    // Validate payment data
    $payment_error = '';
    
    if (empty($cardholder)) {
        $payment_error = "Cardholder name is required!";
    } elseif (empty($cardnumber) || !preg_match('/^[0-9]{16}$/', $cardnumber)) {
        $payment_error = "Please enter a valid 16-digit card number!";
    } elseif (empty($expiry) || !preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiry)) {
        $payment_error = "Please enter a valid expiry date in MM/YY format!";
    } elseif (empty($cvv) || !preg_match('/^[0-9]{3,4}$/', $cvv)) {
        $payment_error = "Please enter a valid CVV (3 or 4 digits)!";
    } else {
        // Check if expiry date is valid (not expired)
        $expiry_parts = explode('/', $expiry);
        $expiry_month = $expiry_parts[0];
        $expiry_year = '20' . $expiry_parts[1]; // Convert YY to YYYY
        
        $current_month = date('m');
        $current_year = date('Y');
        
        if ($expiry_year < $current_year || ($expiry_year == $current_year && $expiry_month < $current_month)) {
            $payment_error = "Your card has expired!";
        }
    }
    
    if (!empty($payment_error)) {
        $error = $payment_error;
        // Retrieve appointment data from session to keep payment form populated
        if (isset($_SESSION['appointment_details'])) {
            $appointment_data = $_SESSION['appointment_details'];
        }
    } else {
        // Process payment (in a real application, you would integrate with a payment gateway here)
        
        // Update payment status in database
        $update_stmt = $conn->prepare("UPDATE appointments4 SET payment_status = 'completed' WHERE confirmation_number = ?");
        $update_stmt->bind_param("s", $confirmation_number);
        
        if ($update_stmt->execute()) {
            // Redirect to success page
            header("Location: booked.php?confirmation=" . $confirmation_number);
            exit();
        } else {
            $error = "Payment processing failed: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare Appointment & Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
      margin: 0; padding: 0; box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    html, body {
      margin: 0; padding: 0;
      background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    body::before {
      content: ''; position: absolute; top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: -1;
    }
    /* Navbar - dark transparent without blue */
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
    .header-buttons .btn {
      font-size: 0.9rem; padding: 6px 16px;
      border-radius: 30px; font-weight: 600;
    }
    .page-content { padding-top: 100px; padding-bottom: 40px; }
    .container-glass {
      max-width: 1400px; margin: 0 auto;
      padding: 30px;
      background: rgba(255,255,255,0.06);
      backdrop-filter: blur(10px);
      border-radius: 15px; color: #333;
      box-shadow: 0 0 15px rgba(0,0,0,0.3);
      overflow-x: auto;
    }


        .logo {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }
        
        .logo::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #fff, transparent);
        }
        
        .content {
            display: flex;
            flex-wrap: wrap;
        }
        
        .left-panel {
            flex: 1;
            min-width: 300px;
            background: #f8f9fa;
            padding: 30px;
            border-right: 1px solid #eaeaea;
        }
        
        .right-panel {
            flex: 1.5;
            min-width: 400px;
            padding: 30px;
            background: #fff;
        }
        
        .appointment-form h2, .payment-section h2 {
            margin-bottom: 25px;
            color: #2d3748;
            font-weight: 600;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }
        
        .appointment-form h2::after, .payment-section h2::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: #4b6cb7;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a5568;
        }
        
        input, select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background: #fff;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #4b6cb7;
            box-shadow: 0 0 0 3px rgba(75, 108, 183, 0.2);
        }
        
        .row {
            display: flex;
            gap: 15px;
        }
        
        .row .form-group {
            flex: 1;
        }
        
        .btns {
             background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.2);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .appointment-details {
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #4b6cb7;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4b6cb7;
        }
        
        .secure-payment {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            color: #718096;
            font-size: 14px;
        }
        
        .secure-payment i {
            margin-right: 8px;
            color: #38a169;
            font-size: 18px;
        }
        
        .service-icon {
            text-align: center;
            margin: 30px 0;
            color: #4b6cb7;
            position: relative;
        }
        
        .service-icon i {
            font-size: 60px;
            margin-bottom: 15px;
            display: block;
        }
        
        .service-icon::before, .service-icon::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: linear-gradient(90deg, transparent, #4b6cb7, transparent);
        }
        
        .service-icon::before {
            left: 0;
        }
        
        .service-icon::after {
            right: 0;
        }
        
        .features {
            margin-top: 20px;
        }
        
        .feature {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 12px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .feature i {
            margin-right: 15px;
            font-size: 20px;
            color: #4b6cb7;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(75, 108, 183, 0.1);
            border-radius: 50%;
        }
        
        .confirmation {
            text-align: center;
            padding: 25px;
            background: #f0fff4;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #38a169;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .confirmation i {
            font-size: 60px;
            color: #38a169;
            margin-bottom: 20px;
            display: block;
        }
        
        .confirmation h2 {
            margin-bottom: 15px;
            color: #2d3748;
        }
        
        .error {
            color: #e53e3e;
            padding: 15px;
            background-color: #fed7d7;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e53e3e;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .success {
            color: #38a169;
            padding: 15px;
            background-color: #f0fff4;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #38a169;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .time-slots-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
        }
        
        .time-slot {
            padding: 12px 5px;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .time-slot:hover {
            background-color: rgba(76, 175, 80, 0.1);
            transform: translateY(-2px);
        }
        
        .time-slot.selected {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.3);
        }
        
        .time-slot.booked {
            background-color: #ff6b6b;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #4b6cb7;
        }
        
        .payment-method.selected {
            border-color: #4b6cb7;
            background: rgba(75, 108, 183, 0.1);
        }
        
        .payment-method i {
            font-size: 30px;
            margin-bottom: 10px;
            color: #4a5568;
        }
        
        @media (max-width: 992px) {
            .content {
                flex-direction: column;
            }
            .left-panel, .right-panel {
                width: 100%;
            }
            .left-panel {
                border-right: none;
                border-bottom: 1px solid #eaeaea;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                width: 100%;
                max-width: 100%;
                margin: 70px auto 20px;
            }
            .row {
                flex-direction: column;
                gap: 0;
            }
            .time-slots-container {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
            .payment-methods {
                flex-direction: column;
            }
        }
        
        .card-input-container {
            position: relative;
        }
        
        .card-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #4a5568;
            font-size: 20px;
        }
          .nav-tabs {
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      margin-bottom: 20px;
    }

    .nav-tabs .nav-link {
      color: rgba(255, 255, 255, 0.7);
      border: none;
      padding: 10px 20px;
      font-weight: 500;
    }

    .nav-tabs .nav-link.active {
      color: white;
      background-color: transparent;
      border-bottom: 3px solid #4CAF50;
    }

    .nav-tabs .nav-link:hover {
      color: white;
      border-color: transparent;
    }
.header-buttons {
      display: flex;
      gap: 10px;
    }

    .header-buttons .btn {
      font-size: 0.9rem;
      padding: 6px 16px;
      border-radius: 30px;
      font-weight: 600;
    }

    .validation-error {
        color: #e53e3e;
        font-size: 14px;
        margin-top: 5px;
        display: block;
    }

    .input-error {
        border-color: #e53e3e !important;
        box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.2) !important;
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
            
            <?php if (!empty($logged_in_username)): ?>
                <a href="logout.php">Logout (<?php echo htmlspecialchars($logged_in_username); ?>)</a>
            <?php else: ?>
                <a href="applogin.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <div class="container">
        <div class="header">
            <div class="logo">PetCare+</div>
            <p>Complete your appointment booking and payment</p>
        </div>
        
        <div class="content">
            <div class="left-panel">
                <div class="appointment-form">
                    <h2>Book Appointment</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="error">
                            <p><strong>Error:</strong> <?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="success">
                            <p><strong>Success:</strong> <?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="appointmentForm">
                        <div class="form-group">
                            <label for="username">Your Name</label>
                            <input type="text" id="username" name="username" placeholder="John Doe" required 
                                value="<?php echo !empty($logged_in_username) ? htmlspecialchars($logged_in_username) : (isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''); ?>"
                                <?php echo !empty($logged_in_username) ? 'readonly' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="pet_name">Pet's Name</label>
                            <input type="text" id="pet_name" name="pet_name" placeholder="Buddy" required value="<?php echo isset($_POST['pet_name']) ? htmlspecialchars($_POST['pet_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="owner_name">Pet Owner's Name</label>
                            <input type="text" id="owner_name" name="owner_name" placeholder="John Doe" required value="<?php echo isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="breed">Pet Breed</label>
                            <input type="text" id="breed" name="breed" placeholder="Golden Retriever" required value="<?php echo isset($_POST['breed']) ? htmlspecialchars($_POST['breed']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="john@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="10-digit number" required pattern="[0-9]{10}" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <span class="validation-error" id="phoneError"></span>
                        </div>
                        
                        <div class="row">
                            <div class="form-group">
                                <label for="appointment_date">Appointment Date</label>
                                <input type="date" id="appointment_date" name="appointment_date" required value="<?php echo htmlspecialchars($selected_date); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Select Time *</label><br/>
                            <div class="time-slots-container">
                                <?php 
                                foreach ($times as $time_slot): 
                                    $isBooked = in_array($time_slot, $booked_times);
                                    $isSelected = (isset($_POST['appointment_time']) && $_POST['appointment_time'] === $time_slot);
                                ?>
                                    <span class="time-slot <?= $isBooked ? 'booked' : '' ?> <?= $isSelected ? 'selected' : '' ?>" 
                                          onclick="selectTime(this, '<?= $time_slot ?>')">
                                        <?= $time_slot ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="appointment_time" id="selectedTime" value="<?php echo isset($_POST['appointment_time']) ? htmlspecialchars($_POST['appointment_time']) : ''; ?>" required>
                            <span class="validation-error" id="timeError" style="display: none;">Please select a time slot.</span>
                        </div>
                        
                        <button type="submit" class="btns">Book Appointment</button>
                    </form>
                </div>
                
                <div class="service-icon">
                    <i class="fas fa-paw"></i>
                    Premium Pet Care
                </div>
                
                <h3>Why Choose Us?</h3>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Certified veterinarians</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Same-day appointment availability</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Secure and confidential</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Emergency services available</span>
                    </div>
                </div>
            </div>
            
            <div class="right-panel">
                <div class="payment-section">
                    <h2>Payment Details</h2>
                    
                    <?php if (!empty($appointment_data)): ?>
                        <div class="appointment-details">
                            <h3>Appointment Summary</h3>
                            <div class="detail-item">
                                <span>Pet Name</span>
                                <span><?php echo htmlspecialchars($appointment_data['pet_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Owner Name</span>
                                <span><?php echo htmlspecialchars($appointment_data['owner_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Breed</span>
                                <span><?php echo htmlspecialchars($appointment_data['breed']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Appointment Date & Time</span>
                                <span><?php echo htmlspecialchars($appointment_data['appointment_date']); ?> at <?php echo htmlspecialchars($appointment_data['appointment_time']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Confirmation Number</span>
                                <span><?php echo htmlspecialchars($appointment_data['confirmation_number']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span>Price</span>
                                <span class="price">Rs. <?php echo number_format($appointment_data['amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="payment-methods">
                            <div class="payment-method selected" onclick="selectPaymentMethod(this, 'card')">
                                <i class="fas fa-credit-card"></i>
                                <div>Credit Card</div>
                            </div>
                            
                        </div>
                        
                        <form id="payment-form" action="" method="POST">
                            <input type="hidden" name="confirmation_number" value="<?php echo htmlspecialchars($appointment_data['confirmation_number']); ?>">
                            <input type="hidden" name="payment_method" id="payment_method" value="card">
                            
                            <div class="form-group">
                                <label for="cardholder">Cardholder Name</label>
                                <input type="text" id="cardholder" name="cardholder" placeholder="John Doe" required value="<?php echo isset($_POST['cardholder']) ? htmlspecialchars($_POST['cardholder']) : ''; ?>">
                                <span class="validation-error" id="cardholderError"></span>
                            </div>
                            
                            <div class="form-group card-input-container">
                                <label for="cardnumber">Card Number</label>
                                <input type="text" id="cardnumber" name="cardnumber" placeholder="1234 5678 9012 3456" required value="<?php echo isset($_POST['cardnumber']) ? htmlspecialchars($_POST['cardnumber']) : ''; ?>">
                                <i class="fas fa-credit-card card-icon"></i>
                                <span class="validation-error" id="cardnumberError"></span>
                            </div>
                            
                            <div class="row">
                                <div class="form-group">
                                    <label for="expiry">Expiry Date</label>
                                    <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required value="<?php echo isset($_POST['expiry']) ? htmlspecialchars($_POST['expiry']) : ''; ?>">
                                    <span class="validation-error" id="expiryError"></span>
                                </div>
                                
                                <div class="form-group card-input-container">
                                    <label for="cvv">CVV</label>
                                    <input type="text" id="cvv" name="cvv" placeholder="123" required value="<?php echo isset($_POST['cvv']) ? htmlspecialchars($_POST['cvv']) : ''; ?>">
                                    <i class="fas fa-question-circle card-icon" title="3-digit code on the back of your card"></i>
                                    <span class="validation-error" id="cvvError"></span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btns">Pay Rs. <?php echo number_format($appointment_data['amount'], 2); ?></button>
                            
                            <div class="secure-payment">
                                <i class="fas fa-lock"></i>
                                <span>Your payment is secured with SSL encryption</span>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="appointment-details">
                            <div class="text-center py-4">
                                <i class="fas fa-paw" style="font-size: 48px; color: #4b6cb7;"></i>
                                <h4 class="mt-3">Complete Appointment Details First</h4>
                                <p class="text-muted">Please fill out the appointment form to proceed with payment</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('appointment_date').setAttribute('min', today);
        
        // Set default date to today if not already set
        if (!document.getElementById('appointment_date').value) {
            document.getElementById('appointment_date').value = today;
        }
        
        // Function to select time slot
        function selectTime(element, time) {
            if (element.classList.contains('booked')) {
                alert('This time slot is already booked. Please select another time.');
                return;
            }

            document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('selectedTime').value = time;
            document.getElementById('timeError').style.display = 'none';
        }
        
        // Update available times when date changes
        document.getElementById('appointment_date').addEventListener('change', function() {
            const selectedDate = this.value;
            window.location.href = `appoi.php?date=${selectedDate}`;
        });
        
        // Form validation
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            const selectedTime = document.getElementById('selectedTime').value;
            if (!selectedTime) {
                e.preventDefault();
                document.getElementById('timeError').style.display = 'block';
            }
            
            // Phone number validation
            const phoneInput = document.getElementById('phone');
            const phoneError = document.getElementById('phoneError');
            const phonePattern = /^[0-9]{10}$/;
            
            if (!phonePattern.test(phoneInput.value)) {
                e.preventDefault();
                phoneError.textContent = 'Please enter a valid 10-digit phone number';
                phoneInput.classList.add('input-error');
            } else {
                phoneError.textContent = '';
                phoneInput.classList.remove('input-error');
            }
        });
        
        // Auto-select time if coming from date selection
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('time')) {
            const time = urlParams.get('time');
            document.querySelectorAll('.time-slot').forEach(slot => {
                if (slot.textContent === time && !slot.classList.contains('booked')) {
                    slot.click();
                }
            });
        }
        
        // Payment method selection
        function selectPaymentMethod(element, method) {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('payment_method').value = method;
        }
        
        // Payment form validation
        document.getElementById('payment-form')?.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Cardholder validation
            const cardholder = document.getElementById('cardholder');
            const cardholderError = document.getElementById('cardholderError');
            if (!cardholder.value.trim()) {
                cardholderError.textContent = 'Cardholder name is required';
                cardholder.classList.add('input-error');
                isValid = false;
            } else {
                cardholderError.textContent = '';
                cardholder.classList.remove('input-error');
            }
            
            // Card number validation
            const cardnumber = document.getElementById('cardnumber');
            const cardnumberError = document.getElementById('cardnumberError');
            const cardnumberValue = cardnumber.value.replace(/\s+/g, '');
            const cardPattern = /^[0-9]{16}$/;
            
            if (!cardPattern.test(cardnumberValue)) {
                cardnumberError.textContent = 'Please enter a valid 16-digit card number';
                cardnumber.classList.add('input-error');
                isValid = false;
            } else {
                cardnumberError.textContent = '';
                cardnumber.classList.remove('input-error');
            }
            
            // Expiry validation
            const expiry = document.getElementById('expiry');
            const expiryError = document.getElementById('expiryError');
            const expiryPattern = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
            
            if (!expiryPattern.test(expiry.value)) {
                expiryError.textContent = 'Please enter a valid expiry date in MM/YY format';
                expiry.classList.add('input-error');
                isValid = false;
            } else {
                // Check if card is expired
                const expiryParts = expiry.value.split('/');
                const expiryMonth = parseInt(expiryParts[0]);
                const expiryYear = parseInt('20' + expiryParts[1]);
                
                const currentDate = new Date();
                const currentYear = currentDate.getFullYear();
                const currentMonth = currentDate.getMonth() + 1;
                
                if (expiryYear < currentYear || (expiryYear === currentYear && expiryMonth < currentMonth)) {
                    expiryError.textContent = 'Your card has expired';
                    expiry.classList.add('input-error');
                    isValid = false;
                } else {
                    expiryError.textContent = '';
                    expiry.classList.remove('input-error');
                }
            }
            
            // CVV validation
            const cvv = document.getElementById('cvv');
            const cvvError = document.getElementById('cvvError');
            const cvvPattern = /^[0-9]{3,4}$/;
            
            if (!cvvPattern.test(cvv.value)) {
                cvvError.textContent = 'Please enter a valid CVV (3 or 4 digits)';
                cvv.classList.add('input-error');
                isValid = false;
            } else {
                cvvError.textContent = '';
                cvv.classList.remove('input-error');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Format card number input
        document.getElementById('cardnumber')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            
            // Add spaces every 4 characters
            value = value.replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = value;
        });
        
        // Format expiry date input
        document.getElementById('expiry')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });
        
        // Phone number validation on input
        document.getElementById('phone')?.addEventListener('input', function(e) {
            const phoneError = document.getElementById('phoneError');
            const phonePattern = /^[0-9]{0,10}$/;
            
            if (!phonePattern.test(e.target.value)) {
                phoneError.textContent = 'Please enter only numbers (max 10 digits)';
                e.target.classList.add('input-error');
            } else {
                phoneError.textContent = '';
                e.target.classList.remove('input-error');
            }
        });
    </script>
</body>
</html>