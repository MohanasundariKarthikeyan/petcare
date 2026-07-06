<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contact Us - PetCare Clinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
      background-size: cover;
      color: white;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
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
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }

    .glass-box {
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px);
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.4);
      max-width: 700px;
      width: 100%;
      text-align: center;
    }

    .glass-box h2 {
      color: #fff;
      margin-bottom: 15px;
    }

    .glass-box p {
      font-size: 1rem;
      margin-bottom: 10px;
      color: #eee;
    }

    .glass-box .working-hours {
      margin-top: 20px;
      background: rgba(255, 255, 255, 0.1);
      padding: 15px;
      border-radius: 10px;
    }

    footer {
      background-color: rgba(0,0,0,0.85);
      padding: 15px 20px;
      color: #ccc;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
    }

    .footer-left {
      max-width: 300px;
      font-size: 0.95rem;
    }

    .footer-right {
      text-align: right;
      font-size: 0.9rem;
      flex-grow: 1;
    }

    @media (max-width: 576px) {
      footer {
        flex-direction: column;
        text-align: center;
      }
      .footer-right {
        text-align: center;
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>

<!-- Navbar -->
 <nav class="navbar">
        <a class="navbar-brand" href="#">PetCare</a>
        <div class="nav-links">
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
            <a href="home.php">Home</a>
            <a href="shopping.php">Shop</a>
          
        </div>
    </nav>

<!-- Main Content -->
<div class="page-content">
  <div class="glass-box">
    <h2>Get in Touch with PetCare Clinic</h2>
    <p>We're here to help your furry, feathered, and scaly friends stay healthy and happy.</p>
    <p>For appointments, general inquiries, or emergencies, reach out to us using the details below.</p>

    <div class="working-hours">
      <h5><i class="bi bi-clock-fill"></i> Working Hours</h5>
      <p>Monday to Sunday: <strong>9:00 AM – 9:00 PM</strong></p>
      
    </div>
  </div>
</div>

<!-- Footer -->
<footer>
  <div class="footer-left">
    <strong>Contact Info:</strong><br>
    📍 123 Pet Street, Tail Town<br>
    📞 +91 98765 43210<br>
    📧 contact@petcareclinic.com
  </div>
  <div class="footer-right">
    &copy; <?= date("Y") ?> PetCare Clinic. All rights reserved.
  </div>
</footer>

</body>
</html>
