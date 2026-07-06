<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get current section from URL or default to dashboard
$current_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Initialize filter variables - reset if reset parameter is present
if (isset($_GET['reset'])) {
    $start_date = '';
    $end_date = '';
} else {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
}

// ================= FETCH ORDERS ===================
$orders = [];
$orders_query = "SELECT id, order_number, session_id, full_name, email, phone, address, city, state, pincode, country, payment_method, subtotal, tax, total, status, created_at 
                 FROM orders7 ORDER BY created_at DESC";
$orders_result = $conn->query($orders_query);

if ($orders_result) {
    while ($order = $orders_result->fetch_assoc()) {
        $order_id = $order['id'];
        $items_query = "SELECT product_id, product_name, quantity, price 
                        FROM order_items7 WHERE order_id = $order_id";
        $items_result = $conn->query($items_query);

        $order_items = [];
        if ($items_result) {
            while ($item = $items_result->fetch_assoc()) {
                $item['total'] = $item['price'] * $item['quantity'];
                $order_items[] = $item;
            }
            $items_result->free();
        }

        $order['items'] = $order_items;
        $orders[] = $order;
    }
    $orders_result->free();
} else {
    $error = "Error retrieving orders: " . $conn->error;
}

// ================= FETCH APPOINTMENTS ===================
$appointments = [];
$appointments_query = "SELECT id, pet_name, owner_name, email, phone, appointment_date, appointment_time, status, confirmation_number, created_at 
                       FROM appointments4";

// Add date filter if provided and not reset
if (!empty($start_date) && !empty($end_date)) {
    $appointments_query .= " WHERE appointment_date BETWEEN '$start_date' AND '$end_date'";
}

$appointments_query .= " ORDER BY appointment_date DESC, appointment_time DESC";
$appointments_result = $conn->query($appointments_query);

if ($appointments_result) {
    while ($row = $appointments_result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $appointments_result->free();
} else {
    $error = "Error retrieving appointments: " . $conn->error;
}

// ================= FETCH PET MEDICAL RECORDS ===================
$medical_records = [];
$medical_query = "SELECT id, patient_id, pet_name, owner_name, category, visited_date, visited_time, disease, symptoms, diagnosis, treatment, medications, next_visit, created_at 
                  FROM createrecord ORDER BY visited_date DESC, visited_time DESC";
$medical_result = $conn->query($medical_query);

if ($medical_result) {
    while ($row = $medical_result->fetch_assoc()) {
        $medical_records[] = $row;
    }
    $medical_result->free();
} else {
    $error = "Error retrieving medical records: " . $conn->error;
}

// ================= FETCH SHOPPING LOGINS ===================
$shop_logins = [];
$shop_login_query = "SELECT id, username, email, full_name, created_at, last_login, is_active 
                     FROM shopplogin ORDER BY created_at DESC";
$shop_login_result = $conn->query($shop_login_query);

if ($shop_login_result) {
    while ($row = $shop_login_result->fetch_assoc()) {
        $shop_logins[] = $row;
    }
    $shop_login_result->free();
} else {
    $error = "Error retrieving shop logins: " . $conn->error;
}

// Handle dashboard report generation
if (isset($_GET['generate_dashboard_report'])) {
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=petcare_dashboard_report.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, array('PetCare - Dashboard Summary Report'));
    fputcsv($output, array('Generated on: ' . date('Y-m-d H:i:s')));
    fputcsv($output, array(''));
    
    // Add statistics
    fputcsv($output, array('STATISTICS SUMMARY'));
    fputcsv($output, array('Category', 'Count'));
    fputcsv($output, array('Total Appointments', count($appointments)));
    fputcsv($output, array('Medical Records', count($medical_records)));
    fputcsv($output, array('Total Orders', count($orders)));
    fputcsv($output, array('Registered Users', count($shop_logins)));
    fputcsv($output, array(''));
    
    // Add recent appointments
    fputcsv($output, array('RECENT APPOINTMENTS (Last 5)'));
    fputcsv($output, array('ID', 'Pet Name', 'Owner', 'Date', 'Time', 'Status'));
    $counter = 0;
    foreach ($appointments as $a) {
        if ($counter >= 5) break;
        fputcsv($output, array(
            $a['id'],
            $a['pet_name'],
            $a['owner_name'],
            $a['appointment_date'],
            date('g:i A', strtotime($a['appointment_time'])),
            $a['status']
        ));
        $counter++;
    }
    fputcsv($output, array(''));
    
    // Add recent orders
    fputcsv($output, array('RECENT ORDERS (Last 5)'));
    fputcsv($output, array('Order #', 'Customer', 'Email', 'Total', 'Status'));
    $counter = 0;
    foreach ($orders as $order) {
        if ($counter >= 5) break;
        fputcsv($output, array(
            $order['order_number'],
            $order['full_name'],
            $order['email'],
            '₹' . number_format($order['total'], 2),
            $order['status']
        ));
        $counter++;
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PetCare - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-width: 250px;
      --sidebar-bg: rgba(0,0,0,0.85);
      --content-bg: rgba(255,255,255,0.08);
      --primary-color: #4CAF50;
      --secondary-color: #FF9800;
      --text-light: #fff;
      --text-dark: #333;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
      background-size: cover;
      color: var(--text-light);
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }
    
    .dashboard-container {
      display: flex;
      min-height: 100vh;
      padding-top: 70px;
    }
    
    /* Sidebar Styles */
    .sidebar {
      width: var(--sidebar-width);
      background: var(--sidebar-bg);
      backdrop-filter: blur(10px);
      height: calc(100vh - 70px);
      position: fixed;
      left: 0;
      top: 70px;
      overflow-y: auto;
      transition: all 0.3s ease;
      z-index: 1000;
      box-shadow: 5px 0 15px rgba(0,0,0,0.2);
    }
    
    .sidebar-header {
      padding: 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      text-align: center;
    }
    
    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .menu-item {
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .menu-item > a {
      color: var(--text-light);
      padding: 15px 20px;
      display: block;
      text-decoration: none;
      transition: all 0.3s;
      font-weight: 500;
    }
    
    .menu-item > a:hover {
      background: rgba(255,255,255,0.1);
    }
    
    .menu-item > a.active {
      background: var(--primary-color);
    }
    
    .menu-item > a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }
    
    .submenu {
      list-style: none;
      padding: 0;
      margin: 0;
      background: rgba(0,0,0,0.3);
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }
    
    .submenu.show {
      max-height: 500px;
    }
    
    .submenu li a {
      color: var(--text-light);
      padding: 12px 20px 12px 50px;
      display: block;
      text-decoration: none;
      transition: all 0.3s;
      font-size: 0.9rem;
    }
    
    .submenu li a:hover {
      background: rgba(255,255,255,0.05);
    }
    
    .submenu li a.active {
      color: var(--primary-color);
      background: rgba(76, 175, 80, 0.1);
    }
    
    /* Main Content Styles */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      padding: 20px;
    }
    
    .content-header {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .content-card {
      background: var(--content-bg);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.4);
      margin-bottom: 20px;
    }
    
    /* Navbar Styles */
    .navbar {
      background: var(--sidebar-bg);
      padding: 15px 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.4);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1030;
    }
    
    .navbar-brand {
      font-size: 1.7rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      color: var(--text-light) !important;
    }
    
    .navbar-brand::before {
      content: '🐾';
      margin-right: 8px;
      font-size: 2rem;
    }
    
    /* Table Styles */
    .table-container {
      background: rgba(255,255,255,0.05);
      border-radius: 10px;
      overflow: hidden;
    }
    
    table {
      margin: 0;
      color: var(--text-dark);
    }
    
    th, td {
      padding: 12px;
      background: rgba(255,255,255,0.7);
    }
    
    th {
      background: var(--primary-color);
      color: var(--text-light);
      font-weight: 600;
    }
    
    tr:nth-child(even) td {
      background: rgba(255,255,255,0.5);
    }
    
    tr:hover td {
      background: rgba(255,255,255,0.8);
    }
    
    .status-pending { color: #FFA500; font-weight: 600; }
    .status-completed { color: #28a745; font-weight: 600; }
    .status-cancelled { color: #dc3545; font-weight: 600; }
    .status-confirmed { color: #007bff; font-weight: 600; }
    .status-shipped { color: #6f42c1; font-weight: 600; }
    .status-active { color: #28a745; font-weight: 600; }
    .status-inactive { color: #6c757d; font-weight: 600; }
    
    .search-box {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }
    
    .search-box input {
      flex: 1;
      padding: 10px 15px;
      border-radius: 8px;
      border: none;
      background: rgba(255,255,255,0.1);
      color: var(--text-light);
    }
    
    .search-box input::placeholder {
      color: rgba(255,255,255,0.7);
    }
    
    .search-box button {
      border-radius: 8px;
      background: var(--primary-color);
      border: none;
    }
    
    .no-data {
      text-align: center;
      padding: 40px;
      font-size: 1.1rem;
      color: #ddd;
    }
    
    .data-section {
      display: none;
    }
    
    .data-section.active {
      display: block;
    }
    
    /* Dashboard Stats */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      transition: transform 0.3s;
      cursor: pointer;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-card i {
      font-size: 2.5rem;
      margin-bottom: 15px;
    }
    
    .stat-card h3 {
      font-size: 2rem;
      margin: 10px 0;
    }
    
    .stat-card p {
      margin: 0;
      color: #ddd;
    }
    
    .stat-card.appointments { border-top: 5px solid #4CAF50; }
    .stat-card.medical { border-top: 5px solid #2196F3; }
    .stat-card.orders { border-top: 5px solid #FF9800; }
    .stat-card.users { border-top: 5px solid #9C27B0; }
    
    /* Date Filter Styles */
    .date-filter {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      align-items: center;
      flex-wrap: wrap;
    }
    
    .date-filter label {
      margin-bottom: 0;
      font-weight: 500;
    }
    
    .date-filter input {
      padding: 8px 12px;
      border-radius: 5px;
      border: 1px solid rgba(255,255,255,0.2);
      background: rgba(255,255,255,0.1);
      color: var(--text-light);
    }
    
    .date-filter button {
      padding: 8px 15px;
      border-radius: 5px;
      border: none;
      background: var(--primary-color);
      color: white;
      cursor: pointer;
    }
    
    .date-filter button.reset {
      background: #6c757d;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      .main-content {
        margin-left: 0;
      }
      
      .sidebar-toggle {
        display: block !important;
      }
      
      .stats-container {
        grid-template-columns: 1fr;
      }
      
      .date-filter {
        flex-direction: column;
        align-items: flex-start;
      }
    }
    
    .sidebar-toggle {
      display: none;
      background: var(--primary-color);
      border: none;
      border-radius: 5px;
      color: white;
      padding: 5px 10px;
      margin-right: 15px;
    }
    
    /* Document Button Styles */
    .document-btn {
      background: #28a745;
      border: none;
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .document-btn:hover {
      background: #218838;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    /* Dashboard Report Button */
    .dashboard-report-btn {
      background: #17a2b8;
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .dashboard-report-btn:hover {
      background: #138496;
      transform: translateY(-2px);
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid d-flex justify-content-between">
      <div class="d-flex align-items-center">
        <button class="sidebar-toggle me-2" id="sidebarToggle">
          <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand" href="#">PetCare Admin</a>
      </div>
      <div>
        <a href="home.php" class="btn btn-outline-light btn-sm me-2">Home</a>
        <a href="about.php" class="btn btn-outline-light btn-sm me-2">About</a>
        <a href="contact.php" class="btn btn-outline-light btn-sm me-2">Contact</a>
        <a href="doctoradminlogin.php" class="btn btn-danger btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <!-- DASHBOARD CONTAINER -->
  <div class="dashboard-container">
    
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <h5>Navigation Menu</h5>
      </div>
      <ul class="sidebar-menu">
        <!-- Dashboard -->
        <li class="menu-item">
          <a href="#" class="sidebar-link <?= $current_section == 'dashboard' ? 'active' : '' ?>" data-section="dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
          </a>
        </li>
        
        <!-- Pet Clinic Section -->
        <li class="menu-item">
          <a href="#petClinicSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
            <i class="fas fa-clinic-medical"></i> Pet Clinic
          </a>
          <ul class="submenu collapse" id="petClinicSubmenu">
            <li><a href="#" class="sidebar-link <?= $current_section == 'appointment-details' ? 'active' : '' ?>" data-section="appointment-details">
                <i class="fas fa-calendar-check"></i> Appointment Details
              </a></li>
            <li><a href="#" class="sidebar-link <?= $current_section == 'pet-medical-details' ? 'active' : '' ?>" data-section="pet-medical-details">
                <i class="fas fa-heartbeat"></i> Pet Medical Details
              </a></li>
          </ul>
        </li>
        
        <!-- Pet Shopping Section -->
        <li class="menu-item">
          <a href="#petShoppingSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
            <i class="fas fa-shopping-cart"></i> Pet Shopping
          </a>
          <ul class="submenu collapse" id="petShoppingSubmenu">
            <li><a href="#" class="sidebar-link <?= $current_section == 'shopping-login' ? 'active' : '' ?>" data-section="shopping-login">
                <i class="fas fa-user"></i> Shopping Login
              </a></li>
            <li><a href="#" class="sidebar-link <?= $current_section == 'order-details' ? 'active' : '' ?>" data-section="order-details">
                <i class="fas fa-list-alt"></i> Order Details
              </a></li>
          </ul>
        </li>
      </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
      
      <!-- Content Header -->
      <div class="content-header">
        <div>
          <h2 id="content-title">
            <?php 
            switch($current_section) {
              case 'dashboard': echo 'Dashboard Overview'; break;
              case 'appointment-details': echo 'Appointment Details'; break;
              case 'pet-medical-details': echo 'Pet Medical Details'; break;
              case 'shopping-login': echo 'Shopping Login Details'; break;
              case 'order-details': echo 'Order Details'; break;
              default: echo 'Dashboard Overview';
            }
            ?>
          </h2>
          <p class="text-muted" id="content-subtitle">
            <?php 
            switch($current_section) {
              case 'dashboard': echo 'Welcome to PetCare Admin Panel'; break;
              case 'appointment-details': echo 'View and manage all appointment bookings'; break;
              case 'pet-medical-details': echo 'View and manage pet medical records'; break;
              case 'shopping-login': echo 'View and manage shopping user accounts'; break;
              case 'order-details': echo 'View and manage product orders'; break;
              
            }
            ?>
          </p>
        </div>
        
        <?php if ($current_section == 'dashboard'): ?>
          <a href="appointment_reports.php" class="dashboard-report-btn">
            <i class="fas fa-file-export"></i> Generate Report
          </a>
        <?php endif; ?>
      </div>
      
      <?php if (isset($error)): ?>
        <div class="alert alert-danger text-center"><?= $error; ?></div>
      <?php endif; ?>
      
      <!-- Dashboard Stats Section -->
      <div class="data-section <?= $current_section == 'dashboard' ? 'active' : '' ?>" id="dashboard">
        <div class="stats-container">
          <div class="stat-card appointments" data-target="appointment-details">
            <i class="fas fa-calendar-check text-success"></i>
            <h3><?= count($appointments) ?></h3>
            <p>Total Appointments</p>
          </div>
          
          <div class="stat-card medical" data-target="pet-medical-details">
            <i class="fas fa-heartbeat text-primary"></i>
            <h3><?= count($medical_records) ?></h3>
            <p>Medical Records</p>
          </div>
          
          <div class="stat-card orders" data-target="order-details">
            <i class="fas fa-shopping-cart text-warning"></i>
            <h3><?= count($orders) ?></h3>
            <p>Total Orders</p>
          </div>
          
          <div class="stat-card users" data-target="shopping-login">
            <i class="fas fa-users text-purple"></i>
            <h3><?= count($shop_logins) ?></h3>
            <p>Registered Users</p>
          </div>
        </div>
        
        <div class="content-card">
          <h4 class="mb-4">Recent Activity</h4>
          
          <div class="row">
            <div class="col-md-6">
              <h5>Latest Appointments</h5>
              <?php if (count($appointments) > 0): ?>
                <div class="table-container">
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>Pet</th>
                          <th>Date</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $counter = 0; ?>
                        <?php foreach ($appointments as $a): ?>
                          <?php if ($counter >= 5) break; ?>
                          <tr>
                            <td><?= htmlspecialchars($a['pet_name']) ?></td>
                            <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                            <td class="status-<?= strtolower($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></td>
                          </tr>
                          <?php $counter++; ?>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              <?php else: ?>
                <div class="no-data">No appointments found.</div>
              <?php endif; ?>
            </div>
            
            <div class="col-md-6">
              <h5>Recent Orders</h5>
              <?php if (count($orders) > 0): ?>
                <div class="table-container">
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>Order #</th>
                          <th>Customer</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $counter = 0; ?>
                        <?php foreach ($orders as $order): ?>
                          <?php if ($counter >= 5) break; ?>
                          <tr>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td><?= htmlspecialchars($order['full_name']) ?></td>
                            <td>₹<?= number_format($order['total'], 2) ?></td>
                          </tr>
                          <?php $counter++; ?>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              <?php else: ?>
                <div class="no-data">No orders found.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Appointment Details Section -->
      <div class="data-section <?= $current_section == 'appointment-details' ? 'active' : '' ?>" id="appointment-details">
        <div class="content-card">
          <div class="section-header">
            <h4>Appointment Details</h4>
            <div>
              
              <button class="btn btn-outline-light btn-sm" onclick="showDashboard()">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
              </button>
            </div>
          </div>
          
          <!-- Date Filter Form -->

          
          <?php if (!empty($start_date) && !empty($end_date)): ?>
            <div class="alert alert-info">
              Showing appointments from <?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?>
            </div>
          <?php endif; ?>
          
          <div class="search-box">
            <input type="text" id="searchAppointmentsInput" placeholder="Search appointments..." onkeyup="searchTable('appointmentsTable', 'searchAppointmentsInput')">
            <button class="btn btn-success" onclick="searchTable('appointmentsTable', 'searchAppointmentsInput')">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
          
          <?php if (count($appointments) > 0): ?>
          <div class="table-container">
            <div class="table-responsive">
              <table class="table table-bordered" id="appointmentsTable">
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
          </div>
          <?php else: ?>
            <div class="no-data">No appointments found for the selected date range.</div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Pet Medical Details Section -->
      <div class="data-section <?= $current_section == 'pet-medical-details' ? 'active' : '' ?>" id="pet-medical-details">
        <div class="content-card">
          <div class="section-header">
            <h4>Pet Medical Details</h4>
            <div>
            
              <button class="btn btn-outline-light btn-sm" onclick="showDashboard()">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
              </button>
            </div>
          </div>
          <div class="search-box">
            <input type="text" id="searchMedicalInput" placeholder="Search medical records..." onkeyup="searchTable('medicalTable', 'searchMedicalInput')">
            <button class="btn btn-success" onclick="searchTable('medicalTable', 'searchMedicalInput')">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
          <?php if (count($medical_records) > 0): ?>
          <div class="table-container">
            <div class="table-responsive">
              <table class="table table-bordered" id="medicalTable">
                <thead>
                  <tr>
                    <th>Record ID</th>
                    <th>Patient ID</th>
                    <th>Pet Name</th>
                    <th>Owner</th>
                    <th>Category</th>
                    <th>Visit Date</th>
                    <th>Disease</th>
                    <th>Treatment</th>
                    <th>Next Visit</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($medical_records as $record): ?>
                  <tr>
                    <td><?= $record['id'] ?></td>
                    <td><?= htmlspecialchars($record['patient_id']) ?></td>
                    <td><?= htmlspecialchars($record['pet_name']) ?></td>
                    <td><?= htmlspecialchars($record['owner_name']) ?></td>
                    <td><?= htmlspecialchars($record['category']) ?></td>
                    <td><?= htmlspecialchars($record['visited_date']) ?></td>
                    <td><?= htmlspecialchars($record['disease']) ?></td>
                    <td><?= htmlspecialchars($record['treatment']) ?></td>
                    <td><?= htmlspecialchars($record['next_visit'] ? $record['next_visit'] : 'Not scheduled') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
            <div class="no-data">No medical records found.</div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Shopping Login Section -->
      <div class="data-section <?= $current_section == 'shopping-login' ? 'active' : '' ?>" id="shopping-login">
        <div class="content-card">
          <div class="section-header">
            <h4>Shopping Login Details</h4>
            <div>
             
              <button class="btn btn-outline-light btn-sm" onclick="showDashboard()">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
              </button>
            </div>
          </div>
          <div class="search-box">
            <input type="text" id="searchShopLoginInput" placeholder="Search shop logins..." onkeyup="searchTable('shopLoginTable', 'searchShopLoginInput')">
            <button class="btn btn-success" onclick="searchTable('shopLoginTable', 'searchShopLoginInput')">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
          <?php if (count($shop_logins) > 0): ?>
          <div class="table-container">
            <div class="table-responsive">
              <table class="table table-bordered" id="shopLoginTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Full Name</th>
                    <th>Created At</th>
                    <th>Last Login</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($shop_logins as $login): ?>
                  <tr>
                    <td><?= $login['id'] ?></td>
                    <td><?= htmlspecialchars($login['username']) ?></td>
                    <td><?= htmlspecialchars($login['email']) ?></td>
                    <td><?= htmlspecialchars($login['full_name']) ?></td>
                    <td><?= htmlspecialchars($login['created_at']) ?></td>
                    <td><?= htmlspecialchars($login['last_login']) ?></td>
                    <td class="status-<?= $login['is_active'] ? 'active' : 'inactive' ?>">
                      <?= $login['is_active'] ? 'Active' : 'Inactive' ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
            <div class="no-data">No shop login records found.</div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- Order Details Section -->
      <div class="data-section <?= $current_section == 'order-details' ? 'active' : '' ?>" id="order-details">
        <div class="content-card">
          <div class="section-header">
            <h4>Order Details</h4>
            <div>
              
              <button class="btn btn-outline-light btn-sm" onclick="showDashboard()">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
              </button>
            </div>
          </div>
          <div class="search-box">
            <input type="text" id="searchOrdersInput" placeholder="Search orders..." onkeyup="searchTable('ordersTable', 'searchOrdersInput')">
            <button class="btn btn-success" onclick="searchTable('ordersTable', 'searchOrdersInput')">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
          <?php if (count($orders) > 0): ?>
          <div class="table-container">
            <div class="table-responsive">
              <table class="table table-bordered" id="ordersTable">
                <thead>
                  <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $order): ?>
                  <tr>
                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                    <td><?= htmlspecialchars($order['full_name']) ?></td>
                    <td><?= htmlspecialchars($order['email']) ?></td>
                    <td><?= htmlspecialchars($order['phone']) ?></td>
                    <td>
                      <?php foreach ($order['items'] as $item): ?>
                        <div><?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)</div>
                      <?php endforeach; ?>
                    </td>
                    <td>₹<?= number_format($order['total'], 2) ?></td>
                    <td class="status-<?= strtolower($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></td>
                    <td><?= htmlspecialchars($order['created_at']) ?></td>
                    <td>
                      <button class="btn btn-sm btn-info" onclick="viewOrderDetails(<?= $order['id'] ?>)">
                        <i class="fas fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php else: ?>
            <div class="no-data">No orders found.</div>
          <?php endif; ?>
        </div>
      </div>
      
    </div>
  </div>

  <!-- Order Details Modal -->
  <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Order Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="orderDetailsContent">
          <!-- Order details will be loaded here via AJAX -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('show');
    });
    
    // Initialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
      return new bootstrap.Dropdown(dropdownToggleEl)
    });
    
    // Handle sidebar navigation
    document.querySelectorAll('.sidebar-link').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all links
        document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
        
        // Add active class to clicked link
        this.classList.add('active');
        
        // Hide all data sections
        document.querySelectorAll('.data-section').forEach(section => {
          section.classList.remove('active');
        });
        
        // Show the selected section
        const sectionId = this.getAttribute('data-section');
        document.getElementById(sectionId).classList.add('active');
        
        // Update content title and subtitle
        updateContentTitle(sectionId);
        
        // Update URL with section parameter
        updateUrlParameter('section', sectionId);
        
        // Close sidebar on mobile after selection
        if (window.innerWidth < 992) {
          document.getElementById('sidebar').classList.remove('show');
        }
      });
    });
    
    // Make stat cards clickable
    document.querySelectorAll('.stat-card').forEach(card => {
      card.addEventListener('click', function() {
        const targetSection = this.getAttribute('data-target');
        
        // Hide all data sections
        document.querySelectorAll('.data-section').forEach(section => {
          section.classList.remove('active');
        });
        
        // Show the selected section
        document.getElementById(targetSection).classList.add('active');
        
        // Update content title and subtitle
        updateContentTitle(targetSection);
        
        // Update URL with section parameter
        updateUrlParameter('section', targetSection);
        
        // Update sidebar active state
        document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
        const sidebarLink = document.querySelector(`.sidebar-link[data-section="${targetSection}"]`);
        if (sidebarLink) {
          sidebarLink.classList.add('active');
        }
      });
    });
    
    // Function to show dashboard
    function showDashboard() {
      // Hide all data sections
      document.querySelectorAll('.data-section').forEach(section => {
        section.classList.remove('active');
      });
      
      // Show dashboard
      document.getElementById('dashboard').classList.add('active');
      
      // Update content title and subtitle
      updateContentTitle('dashboard');
      
      // Update URL with section parameter
      updateUrlParameter('section', 'dashboard');
      
      // Update sidebar active state
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      document.querySelector('.sidebar-link[data-section="dashboard"]').classList.add('active');
    }
    
    // Function to update content title and subtitle
    function updateContentTitle(sectionId) {
      const contentTitle = document.getElementById('content-title');
      const contentSubtitle = document.getElementById('content-subtitle');
      
      switch(sectionId) {
        case 'dashboard':
          contentTitle.textContent = 'Dashboard Overview';
          contentSubtitle.textContent = 'Welcome to PetCare Admin Panel';
          break;
        case 'appointment-details':
          contentTitle.textContent = 'Appointment Details';
          contentSubtitle.textContent = 'View and manage all appointment bookings';
          break;
        case 'pet-medical-details':
          contentTitle.textContent = 'Pet Medical Details';
          contentSubtitle.textContent = 'View and manage pet medical records';
          break;
        case 'shopping-login':
          contentTitle.textContent = 'Shopping Login Details';
          contentSubtitle.textContent = 'View and manage shopping user accounts';
          break;
        case 'order-details':
          contentTitle.textContent = 'Order Details';
          contentSubtitle.textContent = 'View and manage product orders';
          break;
      }
    }
    
    // Search function for tables
    function searchTable(tableId, inputId) {
      const input = document.getElementById(inputId).value.toUpperCase();
      const table = document.getElementById(tableId);
      const rows = table.getElementsByTagName('tr');
      
      for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        let found = false;
        
        for (let j = 0; j < row.cells.length; j++) {
          const cell = row.cells[j];
          if (cell.textContent.toUpperCase().indexOf(input) > -1) {
            found = true;
            break;
          }
        }
        
        row.style.display = found ? '' : 'none';
      }
    }
    
    // Function to reset date filter
    function resetDateFilter() {
      document.getElementById('resetFlag').value = '1';
      document.querySelector('form').submit();
    }
    
    // Function to update URL parameter
    function updateUrlParameter(key, value) {
      const url = new URL(window.location);
      url.searchParams.set(key, value);
      window.history.replaceState({}, '', url);
    }
    
    // Set today's date as default end date and 30 days prior as default start date
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      const thirtyDaysAgo = new Date();
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
      const thirtyDaysAgoFormatted = thirtyDaysAgo.toISOString().split('T')[0];
      
      // Set default values if no dates are selected and not reset
      if (!document.getElementById('start_date').value && !document.getElementById('end_date').value && 
          document.getElementById('resetFlag').value === '0') {
        document.getElementById('start_date').value = thirtyDaysAgoFormatted;
        document.getElementById('end_date').value = today;
      }
    });
    
    // Function to view order details
    function viewOrderDetails(orderId) {
      // You would typically make an AJAX request here to fetch order details
      // For now, we'll just show a placeholder
      document.getElementById('orderDetailsContent').innerHTML = `
        <div class="text-center py-4">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading order details...</p>
        </div>
      `;
      
      // Show the modal
      const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
      modal.show();
      
      // Simulate loading data (replace with actual AJAX call)
      setTimeout(() => {
        document.getElementById('orderDetailsContent').innerHTML = `
          <h6>Order #${orderId}</h6>
          <p>Detailed information about this order would be displayed here.</p>
          <p>This would include customer information, order items, payment details, and shipping information.</p>
        `;
      }, 1000);
    }
  </script>
</body>
</html>