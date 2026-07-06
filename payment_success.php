<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login1.php");
    exit();
}

// Get confirmation number from URL
$confirmation_number = isset($_GET['confirmation']) ? $_GET['confirmation'] : '';

// Fetch appointment details from database
$appointment = [];
if (!empty($confirmation_number)) {
    $stmt = $conn->prepare("SELECT * FROM appointments4 WHERE confirmation_number = ?");
    $stmt->bind_param("s", $confirmation_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();
}

// If no appointment found, redirect to booking page
if (empty($appointment)) {
    header("Location: bookform.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PetCare - Payment Success</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
      background-size: cover;
      color: white;
    }

    .navbar {
      background-color: rgba(0, 0, 0, 0.7);
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    }

    .navbar-brand {
      font-size: 1.75rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      color: white !important;
    }

    .navbar-brand::before {
      content: '🐾';
      font-size: 2rem;
      margin-right: 8px;
    }

    .page-content {
      padding-top: 100px;
      padding-bottom: 50px;
    }

    .container-glass {
      max-width: 600px;
      margin: auto;
      background: rgba(255, 255, 255, 0.06);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 0 15px rgba(0,0,0,0.3);
      color: white;
      text-align: center;
    }

    .success-icon {
      font-size: 5rem;
      color: #4CAF50;
      margin-bottom: 20px;
    }

    .cancelled-icon {
      font-size: 5rem;
      color: #f44336;
      margin-bottom: 20px;
    }

    h2 {
      color: #fff;
      margin-bottom: 25px;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .btn-home {
      background-color: #4CAF50;
      color: white;
      font-weight: bold;
      padding: 12px 25px;
      border-radius: 30px;
      border: none;
      transition: all 0.3s;
      font-size: 1.1rem;
      margin-top: 20px;
      margin-right: 15px;
      text-decoration: none;
      display: inline-block;
    }

    .btn-home:hover {
      background-color: #3e8e41;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      color: white;
    }

    .btn-cancel {
      background-color: #f44336;
      color: white;
      font-weight: bold;
      padding: 12px 25px;
      border-radius: 30px;
      border: none;
      transition: all 0.3s;
      font-size: 1.1rem;
      margin-top: 20px;
      cursor: pointer;
    }

    .btn-cancel:hover {
      background-color: #d32f2f;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .confirmation-details {
      background: rgba(255,255,255,0.1);
      border-radius: 15px;
      padding: 20px;
      margin: 25px 0;
      text-align: left;
    }

    .detail-item {
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
    }

    .detail-label {
      font-weight: 600;
      color: rgba(255,255,255,0.8);
    }

    .detail-value {
      font-weight: 500;
    }

    .status-badge {
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .status-confirmed {
      background-color: rgba(76, 175, 80, 0.2);
      color: #4CAF50;
      border: 1px solid #4CAF50;
    }

    .status-cancelled {
      background-color: rgba(244, 67, 54, 0.2);
      color: #f44336;
      border: 1px solid #f44336;
    }

    .modal-content {
      background: rgba(40, 40, 40, 0.95);
      backdrop-filter: blur(10px);
      color: white;
      border: 1px solid rgba(255,255,255,0.1);
    }

    .btn-close-white {
      filter: invert(1);
    }

    .btn-primary {
      background-color: #4CAF50;
      border: none;
      padding: 8px 20px;
      border-radius: 30px;
    }

    .btn-primary:hover {
      background-color: #3e8e41;
    }

    .btn-danger {
      background-color: #f44336;
      border: none;
      padding: 8px 20px;
      border-radius: 30px;
    }

    .btn-danger:hover {
      background-color: #d32f2f;
    }

    .form-control {
      background-color: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.2);
      color: white;
    }

    .form-control:focus {
      background-color: rgba(255,255,255,0.2);
      color: white;
      border-color: rgba(255,255,255,0.3);
      box-shadow: 0 0 0 0.25rem rgba(255,255,255,0.1);
    }

    @media (max-width: 768px) {
      .container-glass {
        padding: 20px;
        margin: 20px;
      }
      
      .btn-home, .btn-cancel {
        display: block;
        width: 100%;
        margin: 10px 0;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="home.php">PetCare</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto">
        <a href="home.php" class="btn btn-outline-light me-2">
          <i class="fas fa-home"></i> Home
        </a>
        <a href="about.php" class="btn btn-outline-light me-2">
          <i class="fas fa-info-circle"></i> About
        </a>
        <a href="contact.php" class="btn btn-outline-light me-2">
          <i class="fas fa-envelope"></i> Contact Us
        </a>
        <a href="booked.php" class="btn btn-outline-light me-2">
          <i class="fas fa-calendar-check"></i> Appointment Booked
        </a>
        <a href="shopping.php" class="btn btn-outline-light me-2">
          <i class="fas fa-shopping-cart"></i> Shop Now
        </a>
        <a href="booking.php" class="btn btn-outline-light">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </div>
  </div>
</nav>

<div class="page-content">
  <div class="container-glass">
    <?php if ($appointment['status'] == 'cancelled'): ?>
      <div class="cancelled-icon">
        <i class="fas fa-times-circle"></i>
      </div>
      <h2>Appointment Cancelled</h2>
    <?php else: ?>
      <div class="success-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <h2>Payment Successful!</h2>
    <?php endif; ?>
    
    <p class="lead">
      <?php if ($appointment['status'] == 'cancelled'): ?>
        Your appointment has been cancelled. We're sorry to see you go.
      <?php else: ?>
        Thank you for your payment. Your appointment has been confirmed.
      <?php endif; ?>
    </p>
    
    <div class="confirmation-details">
      <div class="detail-item">
        <div class="detail-label">Confirmation Number:</div>
        <div class="detail-value"><?= htmlspecialchars($appointment['confirmation_number']) ?></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Status:</div>
        <div class="detail-value">
          <span class="status-badge <?= $appointment['status'] == 'cancelled' ? 'status-cancelled' : 'status-confirmed' ?>">
            <?= ucfirst($appointment['status']) ?>
          </span>
        </div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Amount Paid:</div>
        <div class="detail-value">₹<?= number_format($appointment['payment_amount'], 2) ?></div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Payment Method:</div>
        <div class="detail-value">Credit Card</div>
      </div>
      <div class="detail-item">
        <div class="detail-label">Payment Date:</div>
        <div class="detail-value"><?= date('F j, Y', strtotime($appointment['created_at'])) ?></div>
      </div>
      <?php if ($appointment['status'] == 'cancelled'): ?>
        <div class="detail-item">
          <div class="detail-label">Cancellation Reason:</div>
          <div class="detail-value"><?= htmlspecialchars($appointment['cancellation_reason']) ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Cancellation Date:</div>
          <div class="detail-value"><?= date('F j, Y g:i a', strtotime($appointment['cancelled_at'])) ?></div>
        </div>
      <?php endif; ?>
    </div>
    
    <p class="mt-4">A confirmation email has been sent to your registered email address with all the details.</p>
    
    <div class="action-buttons">
      <a href="home.php" class="btn-home">
        <i class="fas fa-home"></i> Back to Home
      </a>
      
      <?php if ($appointment['status'] != 'cancelled'): ?>
        <button class="btn-cancel" data-bs-toggle="modal" data-bs-target="#cancelModal">
          <i class="fas fa-calendar-times"></i> Cancel Appointment
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Cancel Appointment Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cancelModalLabel">Cancel Appointment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to cancel this appointment? Please let us know the reason for cancellation.</p>
        <form id="cancelForm">
          <input type="hidden" name="confirmation_number" value="<?= htmlspecialchars($appointment['confirmation_number']) ?>">
          <div class="mb-3">
            <label for="cancelReason" class="form-label">Reason for Cancellation</label>
            <textarea class="form-control" id="cancelReason" name="cancel_reason" rows="3" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" id="confirmCancel">Cancel Appointment</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Message Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div class="cancelled-icon mb-3">
          <i class="fas fa-check-circle"></i>
        </div>
        <h4 class="mb-3" id="successMessage">Appointment cancelled successfully!</h4>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="window.location.reload()">OK</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
  // Handle cancel appointment confirmation
  $('#confirmCancel').click(function() {
    const formData = $('#cancelForm').serialize();
    
    $.ajax({
      type: 'POST',
      url: 'cancel_appointment.php',
      data: formData,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $('#cancelModal').modal('hide');
          $('#successMessage').text(response.message);
          $('#successModal').modal('show');
        } else {
          alert(response.message || 'An error occurred. Please try again.');
        }
      },
      error: function() {
        alert('An error occurred. Please try again.');
      }
    });
  });
});
</script>
</body>
</html>