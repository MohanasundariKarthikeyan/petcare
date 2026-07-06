<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$start_date = '';
$end_date = '';
$appointments = [];
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Validate dates
    if (!empty($start_date) && !empty($end_date)) {
        if ($start_date > $end_date) {
            $error = "Start date cannot be after end date.";
        } else {
            // Fetch appointments based on date range
            $query = "SELECT id, pet_name, owner_name, email, phone, appointment_date, appointment_time, status, confirmation_number, created_at 
                      FROM appointments4 
                      WHERE appointment_date BETWEEN '$start_date' AND '$end_date'
                      ORDER BY appointment_date DESC, appointment_time DESC";
            
            $result = $conn->query($query);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $appointments[] = $row;
                }
                $result->free();
            } else {
                $error = "Error retrieving appointments: " . $conn->error;
            }
        }
    } else {
        $error = "Please select both start and end dates.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare - Appointment Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            padding-top: 70px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        
        .btn-primary:hover {
            background-color: #3e8e41;
            border-color: #3e8e41;
        }
        
        .table {
            color: #333;
        }
        
        th {
            background-color: #4CAF50;
            color: white;
        }
        p, h2, label{
            color: white;
        }
        .status-pending { color: #FFA500; font-weight: 600; }
        .status-completed { color: #28a745; font-weight: 600; }
        .status-cancelled { color: #dc3545; font-weight: 600; }
        .status-confirmed { color: #007bff; font-weight: 600; }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: rgba(0,0,0,0.85);">
        <div class="container-fluid d-flex justify-content-between">
            <div class="d-flex align-items-center">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-paw me-2"></i>PetCare Admin
                </a>
            </div>
            <div>
                <a href="admin.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="doctoradminlogin.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <h2 class="mb-4"><i class="fas fa-file-alt me-2"></i>Appointment Reports</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($appointments)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pet Name</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Confirmation #</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td><?= $a['id'] ?></td>
                                <td><?= htmlspecialchars($a['pet_name']) ?></td>
                                <td><?= htmlspecialchars($a['owner_name']) ?></td>
                                <td><?= htmlspecialchars($a['email']) ?></td>
                                <td><?= htmlspecialchars($a['phone']) ?></td>
                                <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                                <td><?= date('g:i A', strtotime($a['appointment_time'])) ?></td>
                                <td class="status-<?= strtolower($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></td>
                                <td><?= htmlspecialchars($a['confirmation_number']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <p class="fw-bold">Total Appointments: <?= count($appointments) ?></p>
                </div>
                
                <div class="mt-4">
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print Report
                    </button>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="alert alert-info">
                    No appointments found for the selected date range.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Please select a date range to generate the appointment report.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set default dates (today and 30 days ago)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            const thirtyDaysAgoFormatted = thirtyDaysAgo.toISOString().split('T')[0];
            
            // Set default values if no dates are selected
            if (!document.getElementById('start_date').value) {
                document.getElementById('start_date').value = thirtyDaysAgoFormatted;
            }
            if (!document.getElementById('end_date').value) {
                document.getElementById('end_date').value = today;
            }
        });
    </script>
</body>
</html>