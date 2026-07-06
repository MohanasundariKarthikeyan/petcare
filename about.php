<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - PetCare Clinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
      background-size: cover;
      color: white;
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
      max-width: 900px;
      margin: auto;
      padding: 30px;
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
    }

    h1, h2 {
      color: #ffffff;
    }

    p {
      font-size: 1.1rem;
      line-height: 1.7;
    }

    .highlight {
      color: #4CAF50;
      font-weight: bold;
    }

    .btn-custom {
      margin-top: 20px;
      border-radius: 30px;
      font-weight: 600;
      padding: 10px 20px;
    }

    .btn-appointment {
      background-color: #4CAF50;
      color: white;
    }

    .btn-shop {
      background-color: #00BCD4;
      color: white;
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

<!-- Page Content -->
<div class="page-content">
  <div class="container-glass">
    <h1 class="text-center mb-4">About PetCare Clinic</h1>
    <p>
      <span class="highlight">PetCare</span> is your one-stop solution for all pet health and wellness needs.
      We are a modern veterinary clinic dedicated to ensuring your furry, feathered, and scaled companions live long, healthy, and happy lives.
    </p>

    <h2 class="mt-4">Doctor Appointment Booking</h2>
    <p>
      Booking an appointment with our certified veterinary doctors is fast and easy.
      Whether it's for a routine check-up or an emergency, our booking system ensures you get the earliest available slot with the right specialist.
    </p>
    <a href="appointment_payment.php" class="btn btn-appointment btn-custom">Book a Doctor Appointment</a>

    <h2 class="mt-5">Pet Products Store</h2>
    <p>
      Visit our online store to purchase top-quality pet products, including food, toys, grooming essentials, and health supplements.
      We offer affordable pricing, fast delivery, and a wide variety of options tailored to your pet’s specific needs.
    </p>
    <a href="shopping.php" class="btn btn-shop btn-custom">Shop Pet Products</a>

    <h2 class="mt-5">Why Choose Us?</h2>
    <ul>
      <li>Experienced and compassionate veterinary doctors</li>
      <li>24/7 emergency support and consultation</li>
      <li>Digital medical records and prescription tracking</li>
      <li>Safe and secure pet product shopping</li>
    </ul>
  </div>
</div>

</body>
</html>
