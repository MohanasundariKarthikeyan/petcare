<?php
session_start();
include 'config.php';

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

$search = '';
$where = '';
$params = [];
$types = '';

if (isset($_POST['search']) && !empty(trim($_POST['search']))) {
    $search = trim($_POST['search']);
    $where = " WHERE patient_id LIKE ?";
    $searchParam = "%{$search}%";
    $types = 's';
    $params = [$searchParam];
}

$sql = "SELECT * FROM createrecord $where ORDER BY visited_date DESC, visited_time DESC";
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($where)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Error preparing query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Doctor Dashboard - Pet Medical Records</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
      background-size: cover;
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

    .page-content {
      padding-top: 100px;
      padding-bottom: 40px;
    }

    .container-glass {
      max-width: 1100px;
      margin: 0 auto;
      padding: 30px;
      background: rgba(255, 255, 255, 0.06);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      color: #333;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
    }

    h2 {
      color: #ffffff;
      text-align: center;
      margin-bottom: 25px;
    }

    .alert-success {
      text-align: center;
    }

    .search-box {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
    }

    .search-box input {
      flex-grow: 1;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .search-box button {
      padding: 10px 20px;
      background: #0d6efd;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .btn-create {
      background-color: #198754;
      color: white;
    }

    .btn-create:hover {
      background-color: #157347;
    }

    .table {
      margin-top: 20px;
    }

    th, td {
      padding: 12px;
      border: 1px solid rgba(128, 128, 128, 0.4);
      text-align: left;
      background-color: rgba(255, 255, 255, 0.45);
      color: #333;
    }

    th {
      background-color: rgba(128, 128, 128, 0.8);
      color: white;
    }

    tr:nth-child(even) td {
      background-color: rgba(240, 240, 240, 0.35);
    }

    tr:hover td {
      background-color: rgb(180, 180, 180);
    }

    .no-records {
      color: #fff;
      font-size: 1rem;
      text-align: center;
      margin-top: 20px;
    }

    @media (max-width: 767px) {
      .header-buttons {
        flex-direction: column;
        gap: 8px;
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>

  <!-- Fixed Navbar -->
   <nav class="navbar">
        <a class="navbar-brand" href="#">PetCare</a>
        <div class="nav-links">
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
            <a href="home.php">Home</a>
            <a href="shopping.php">Shop</a>
            
            <a href="shopping.php">Logout</a>
        </div>
    </nav>
  <!-- Page Content -->
  <div class="page-content">
    <div class="container-glass">
      <h2>Pet Medical Details</h2>

      <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
      <?php endif; ?>

      <div class="row mb-3">
        <div class="col-md-6 mb-2">
          <a href="createrecord.php" class="btn btn-create w-100">➕ Create Patient Details</a>
        </div>
        <div class="col-md-6">
          <form method="post" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by Patient ID" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
          </form>
        </div>
      </div>

      <?php if (empty($records)): ?>
        <p class="no-records">No records found.</p>
        <?php if (!empty($search)): ?>
          <div class="text-center">
            <a href="doctor.php" class="btn btn-outline-light btn-sm">Clear Search</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th>Patient ID</th>
                <th>Pet Name</th>
                <th>Owner</th>
                <th>Category</th>
                <th>Visited On</th>
                <th>Disease</th>
                <th>Symptoms</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
                <th>Medications</th>
                <th>Next Visit</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($records as $record): ?>
                <tr>
                  <td><?= htmlspecialchars($record['patient_id']) ?></td>
                  <td><?= htmlspecialchars($record['pet_name']) ?></td>
                  <td><?= htmlspecialchars($record['owner_name']) ?></td>
                  <td><?= htmlspecialchars($record['category']) ?></td>
                  <td><?= htmlspecialchars($record['visited_date']) . ' ' . htmlspecialchars($record['visited_time']) ?></td>
                  <td><?= htmlspecialchars($record['disease']) ?></td>
                  <td><?= htmlspecialchars($record['symptoms']) ?></td>
                  <td><?= htmlspecialchars($record['diagnosis']) ?></td>
                   <td><?= htmlspecialchars($record['treatment']) ?></td>
                   <td><?= htmlspecialchars($record['medications']) ?></td>
                  <td><?= htmlspecialchars($record['next_visit']) ?></td>  
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
