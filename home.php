<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Our Services | PetCare - Premium Veterinary Services</title>
  <meta name="description" content="Explore our comprehensive veterinary services including wellness exams, dental care, surgery, and emergency treatments for your pets.">
  <meta name="keywords" content="veterinary services, pet health, animal care, pet wellness, pet surgery, pet dental">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <!-- AOS Animation -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <!-- Glide.js -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.5.0/css/glide.core.min.css">

  <style>
    :root {
      --primary-color: #3498db;
      --secondary-color: #ff6b35;
      --accent-color: #2ecc71;
      --dark-color: #1a1a2e;
      --light-color: #f8f9fa;
      --text-color: #333;
      --text-light: #6c757d;
      --gradient-orange: linear-gradient(135deg, #ff6b35 0%, #ff8e53 100%);
      --gradient-blue: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
      --gradient-green: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    }

    body {
      font-family: 'Poppins', sans-serif;
      color: var(--text-color);
      background-color: #f9f9f9;
      overflow-x: hidden;
    }

    /* Navbar Styles */
    .navbar {
      padding: 15px 0;
      background: rgba(255, 255, 255, 0.98) !important;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.4s ease;
    }

    .navbar.scrolled {
      padding: 10px 0;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
      font-size: 1.8rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      color: var(--dark-color) !important;
      font-family: 'Playfair Display', serif;
      letter-spacing: 1px;
    }

    .navbar-brand i {
      font-size: 1.8rem;
      margin-right: 12px;
      color: var(--secondary-color);
    }
    
    .navbar-brand::before {
      content: '🐾';
      font-size: 1.8rem;
      margin-right: 10px;
    }

    .nav-link {
      color: var(--dark-color) !important;
      font-weight: 500;
      padding: 8px 15px !important;
      margin: 0 5px;
      position: relative;
    }

    .nav-link::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--gradient-orange);
      transition: width 0.3s ease;
    }

    .nav-link:hover::after,
    .nav-link.active::after {
      width: 100%;
    }

    .nav-buttons .btn {
      font-size: 0.9rem;
      padding: 8px 18px;
      border-radius: 30px;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-shop {
      background: var(--gradient-orange);
      color: white;
      border: none;
      box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
    }

    .btn-shop:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
    }

    .btn-outline-primary {
      border: 1px solid var(--primary-color);
      color: var(--primary-color);
      background-color: transparent;
    }

    .btn-outline-primary:hover {
      background-color: var(--primary-color);
      color: white;
    }

    /* Hero Section */
    .hero-section {
      background: linear-gradient(rgba(26, 26, 46, 0.85), rgba(26, 26, 46, 0.9)), 
                  url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center;
      background-size: cover;
      padding: 120px 0 100px;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .hero-section::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 100px;
      background: linear-gradient(to top, #f9f9f9, transparent);
      z-index: 1;
    }

    .hero-content {
      position: relative;
      z-index: 2;
    }

    .hero-title {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      font-family: 'Playfair Display', serif;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .hero-subtitle {
      font-size: 1.2rem;
      margin-bottom: 30px;
      opacity: 0.9;
      max-width: 700px;
    }

    .hero-btn {
      padding: 12px 30px;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 50px;
      background: var(--gradient-orange);
      color: white;
      border: none;
      box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
      transition: all 0.3s ease;
    }

    .hero-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
    }

    /* Services Section */
    .services-section {
      padding: 100px 0;
      background-color: white;
      position: relative;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-family: 'Playfair Display', serif;
      text-align: center;
    }

    .section-subtitle {
      font-size: 1.1rem;
      color: var(--text-light);
      margin-bottom: 60px;
      text-align: center;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }

    .service-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      margin-bottom: 30px;
      border: none;
      position: relative;
      z-index: 1;
    }

    .service-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: var(--gradient-orange);
      z-index: 2;
    }

    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }

    .service-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
      font-size: 2rem;
      color: white;
      background: var(--gradient-orange);
      box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
      transition: all 0.3s ease;
    }

    .service-card:hover .service-icon {
      transform: rotate(15deg) scale(1.1);
    }

    .service-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--dark-color);
      text-align: center;
    }

    .service-description {
      color: var(--text-light);
      margin-bottom: 20px;
      text-align: center;
      padding: 0 20px;
    }

    .service-btn {
      display: inline-block;
      padding: 8px 20px;
      background: transparent;
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
      border-radius: 50px;
      font-weight: 500;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .service-btn:hover {
      background: var(--primary-color);
      color: white;
    }

    /* Features Section */
    .features-section {
      padding: 100px 0;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
      position: relative;
    }

    .feature-box {
      background: white;
      border-radius: 15px;
      padding: 40px 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      height: 100%;
      position: relative;
      overflow: hidden;
    }

    .feature-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }

    .feature-icon {
      font-size: 2.5rem;
      color: var(--secondary-color);
      margin-bottom: 20px;
    }

    .feature-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--dark-color);
    }

    .feature-text {
      color: var(--text-light);
      margin-bottom: 20px;
    }

    /* Testimonials Section */
    .testimonials-section {
      padding: 100px 0;
      background: white;
    }

    .testimonial-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      margin: 15px;
      position: relative;
    }

    .testimonial-card::before {
      content: '\201C';
      font-family: Georgia, serif;
      font-size: 5rem;
      color: rgba(52, 152, 219, 0.1);
      position: absolute;
      top: 10px;
      left: 20px;
      line-height: 1;
    }

    .testimonial-text {
      font-style: italic;
      color: var(--text-color);
      margin-bottom: 20px;
      position: relative;
      z-index: 1;
    }

    .testimonial-author {
      display: flex;
      align-items: center;
    }

    .author-img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 15px;
    }

    .author-name {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .author-role {
      font-size: 0.9rem;
      color: var(--text-light);
    }

    /* CTA Section */
    .cta-section {
      padding: 100px 0;
      background: linear-gradient(135deg, var(--primary-color) 0%, #2c3e50 100%);
      color: white;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .cta-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://images.unsplash.com/photo-1517849845537-4d257902454a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') no-repeat center center;
      background-size: cover;
      opacity: 0.1;
    }

    .cta-title {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      font-family: 'Playfair Display', serif;
    }

    .cta-text {
      font-size: 1.1rem;
      margin-bottom: 30px;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }

    .cta-btn {
      padding: 12px 30px;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 50px;
      background: white;
      color: var(--primary-color);
      border: none;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .cta-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      background: var(--secondary-color);
      color: white;
    }

    /* Footer */
    .footer {
      background: var(--dark-color);
      color: white;
      padding: 80px 0 30px;
    }

    .footer-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 25px;
      font-family: 'Playfair Display', serif;
    }

    .footer-links {
      list-style: none;
      padding: 0;
    }

    .footer-links li {
      margin-bottom: 10px;
    }

    .footer-links a {
      color: rgba(255, 255, 255, 0.7);
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .footer-links a:hover {
      color: white;
      padding-left: 5px;
    }

    .social-links {
      display: flex;
      gap: 15px;
    }

    .social-links a {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      transition: all 0.3s ease;
    }

    .social-links a:hover {
      background: var(--secondary-color);
      transform: translateY(-3px);
    }

    .copyright {
      margin-top: 50px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      text-align: center;
      color: rgba(255, 255, 255, 0.5);
    }

    /* Floating Elements */
    .floating-elements {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 0;
      pointer-events: none;
    }

    .floating-element {
      position: absolute;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 50%;
      filter: blur(2px);
      animation: float 15s infinite linear;
    }

    @keyframes float {
      0% {
        transform: translateY(0) rotate(0deg);
      }
      100% {
        transform: translateY(-1000px) rotate(720deg);
      }
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
      .hero-title {
        font-size: 3rem;
      }
      
      .section-title {
        font-size: 2.2rem;
      }
    }

    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.5rem;
      }
      
      .hero-subtitle {
        font-size: 1.1rem;
      }
      
      .section-title {
        font-size: 2rem;
      }
      
      .service-title {
        font-size: 1.3rem;
      }
    }

    @media (max-width: 576px) {
      .hero-title {
        font-size: 2rem;
      }
      
      .hero-btn, .cta-btn {
        padding: 10px 25px;
        font-size: 1rem;
      }
      
      .section-title {
        font-size: 1.8rem;
      }
      
      .section-subtitle {
        font-size: 1rem;
      }
    }
  </style>
</head>

<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
      <a class="navbar-brand" href="home.php">
        PetCare
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="home.php">Home</a>
          </li>
          
          <li class="nav-item">
            <a class="nav-link" href="about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="contact.php">Contact</a>
          </li>
        </ul>
        <div class="nav-buttons ms-lg-3">
          <a href="shopping.php" class="btn btn-shop me-2">
            <i class="fas fa-shopping-cart"></i> Shop
          </a>
          <a href="doctoradminlogin.php" class="btn btn-outline-primary">
            <i class="fas fa-user-md"></i> Doctor/AdminLogin
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto text-center hero-content">
          <h1 class="hero-title animate__animated animate__fadeInDown">Our Veterinary Services</h1>
          <p class="hero-subtitle animate__animated animate__fadeIn animate__delay-1s">Comprehensive, compassionate care for your beloved pets. From routine checkups to specialized treatments, we're here to keep your furry family members healthy and happy.</p>
          <a href="#services" class="btn hero-btn animate__animated animate__fadeInUp animate__delay-2s">
            <i class="fas fa-paw me-2"></i> Explore Services
          </a>
        </div>
      </div>
    </div>
    <div class="floating-elements">
      <div class="floating-element" style="width: 100px; height: 100px; top: 20%; left: 10%; animation-duration: 20s;"></div>
      <div class="floating-element" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-duration: 25s;"></div>
    </div>
  </section>

  <!-- Services Section -->
  <section id="services" class="services-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
          <h2 class="section-title" data-aos="fade-up">Comprehensive Pet Care Services</h2>
          <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">We offer a full range of veterinary services to keep your pets healthy at every stage of life.</p>
        </div>
      </div>
      
      <div class="row">
        <!-- Service 1 -->
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <div class="card service-card h-100">
            <div class="card-body text-center py-4">
              <div class="service-icon">
                <i class="fas fa-stethoscope"></i>
              </div>
              <h3 class="service-title">Wellness Exams</h3>
              <p class="service-description">Regular check-ups to monitor your pet's health and catch potential issues early before they become serious problems.</p>
              <a href="applogin.php" class="service-btn">Learn More</a>
            </div>
          </div>
        </div>
        
        <!-- Service 2 -->
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
          <div class="card service-card h-100">
            <div class="card-body text-center py-4">
              <div class="service-icon">
                <i class="fas fa-syringe"></i>
              </div>
              <h3 class="service-title">Vaccinations</h3>
              <p class="service-description">Essential immunizations to protect your pet from dangerous diseases and ensure their long-term health.</p>
              <a href="applogin.php" class="service-btn">Learn More</a>
            </div>
          </div>
        </div>
        
        <!-- Service 3 -->
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
          <div class="card service-card h-100">
            <div class="card-body text-center py-4">
              <div class="service-icon">
                <i class="fas fa-teeth"></i>
              </div>
              <h3 class="service-title">Dental Care</h3>
              <p class="service-description">Professional cleanings and treatments to maintain your pet's oral health and prevent painful dental diseases.</p>
              <a href="applogin.php" class="service-btn">Learn More</a>
            </div>
          </div>
        </div>
        
        <!-- Service 4 -->
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <div class="card service-card h-100">
            <div class="card-body text-center py-4">
              <div class="service-icon">
                <i class="fas fa-heartbeat"></i>
              </div>
              <h3 class="service-title">Internal Medicine</h3>
              <p class="service-description">Diagnosis and treatment of complex medical conditions affecting your pet's internal organs and systems.</p>
              <a href="applogin.php" class="service-btn">Learn More</a>
            </div>
          </div>
        </div>
        
        <!-- Service 5 -->
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
          <div class="card service-card h-100">
            <div class="card-body text-center py-4">
              <div class="service-icon">
                <i class="fas fa-bone"></i>
              </div>
              <h3 class="service-title">Surgical Services</h3>
              <p class="service-description">State-of-the-art surgical procedures performed by experienced veterinarians in our modern surgical suite.</p>
              <a href="applogin.php" class="service-btn">Learn More</a>
            </div>
          </div>
        </div>
        
        <!-- Service 6 -->
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
          <div class="card service-card h-100">
            <div class="card-body text-center py-4">
              <div class="service-icon">
                <i class="fas fa-first-aid"></i>
              </div>
              <h3 class="service-title">Emergency Care</h3>
              <p class="service-description">24/7 emergency services for urgent medical situations when your pet needs immediate attention.</p>
              <a href="applogin.php" class="service-btn">Learn More</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
          <h2 class="section-title" data-aos="fade-up">Why Choose PetCare?</h2>
          <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">We're committed to providing the highest quality care for your pets with these key benefits.</p>
        </div>
      </div>
      
      <div class="row mt-5">
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
          <div class="feature-box">
            <div class="feature-icon">
              <i class="fas fa-user-md"></i>
            </div>
            <h3 class="feature-title">Expert Veterinarians</h3>
            <p class="feature-text">Our team consists of highly trained and experienced veterinarians who are passionate about animal health.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
          <div class="feature-box">
            <div class="feature-icon">
              <i class="fas fa-clinic-medical"></i>
            </div>
            <h3 class="feature-title">Modern Facility</h3>
            <p class="feature-text">State-of-the-art equipment and facilities to provide the best possible care for your pets.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
          <div class="feature-box">
            <div class="feature-icon">
              <i class="fas fa-heart"></i>
            </div>
            <h3 class="feature-title">Compassionate Care</h3>
            <p class="feature-text">We treat every pet as if they were our own, with kindness, patience, and understanding.</p>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="500">
          <div class="feature-box">
            <div class="feature-icon">
              <i class="fas fa-clock"></i>
            </div>
            <h3 class="feature-title">Flexible Hours</h3>
            <p class="feature-text">Extended hours and emergency services to accommodate your busy schedule and urgent needs.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="testimonials-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
          <h2 class="section-title" data-aos="fade-up">What Pet Owners Say</h2>
          <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Hear from our happy clients about their experiences with PetCare.</p>
        </div>
      </div>
      
      <div class="row mt-5">
        <div class="col-12">
          <div class="glide" data-aos="fade-up">
            <div class="glide__track" data-glide-el="track">
              <ul class="glide__slides">
                <li class="glide__slide">
                  <div class="testimonial-card">
                    <p class="testimonial-text">"The team at PetCare saved my dog's life when he had a sudden illness. Their quick response and expert care made all the difference. I can't thank them enough!"</p>
                    <div class="testimonial-author">
                      <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sarah Johnson" class="author-img">
                      <div>
                        <h4 class="author-name">Sarah Johnson</h4>
                        <p class="author-role">Dog Owner</p>
                      </div>
                    </div>
                  </div>
                </li>
                <li class="glide__slide">
                  <div class="testimonial-card">
                    <p class="testimonial-text">"I've been bringing my cats to PetCare for years. The vets are knowledgeable and truly care about animals. They explain everything clearly and never rush through appointments."</p>
                    <div class="testimonial-author">
                      <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Michael Chen" class="author-img">
                      <div>
                        <h4 class="author-name">Michael Chen</h4>
                        <p class="author-role">Cat Owner</p>
                      </div>
                    </div>
                  </div>
                </li>
                <li class="glide__slide">
                  <div class="testimonial-card">
                    <p class="testimonial-text">"The dental care my rabbit received was exceptional. They handled him with such care and gentleness. I highly recommend PetCare for all exotic pets!"</p>
                    <div class="testimonial-author">
                      <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Emily Rodriguez" class="author-img">
                      <div>
                        <h4 class="author-name">Emily Rodriguez</h4>
                        <p class="author-role">Rabbit Owner</p>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
            <div class="glide__arrows" data-glide-el="controls">
              <button class="glide__arrow glide__arrow--left" data-glide-dir="<"><i class="fas fa-chevron-left"></i></button>
              <button class="glide__arrow glide__arrow--right" data-glide-dir=">"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 mx-auto text-center">
          <h2 class="cta-title" data-aos="fade-up">Ready to Schedule an Appointment?</h2>
          <p class="cta-text" data-aos="fade-up" data-aos-delay="100">Your pet's health is our top priority. Book an appointment today and experience the PetCare difference.</p>
          <a href="applogin.php" class="btn cta-btn" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-calendar-alt me-2"></i> Book Now
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 mb-5 mb-lg-0">
          <h3 class="footer-title">PetCare</h3>
          <p>Providing exceptional veterinary care with compassion and expertise since 2010.</p>
          <div class="social-links mt-4">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-6 mb-5 mb-md-0">
          <h3 class="footer-title">Quick Links</h3>
          <ul class="footer-links">
            <li><a href="home.php">Home</a></li>
            
            <li><a href="about.php">About Us</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="applogin.php">Book Appointment</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-6 mb-5 mb-md-0">
          <h3 class="footer-title">Services</h3>
          <ul class="footer-links">
            <li><a href="#">Wellness Exams</a></li>
            <li><a href="#">Vaccinations</a></li>
            <li><a href="#">Dental Care</a></li>
            <li><a href="#">Surgery</a></li>
            <li><a href="#">Emergency Care</a></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-6">
          <h3 class="footer-title">Contact Us</h3>
          <ul class="footer-links">
            <li><i class="fas fa-map-marker-alt me-2"></i> 123 PetCare Ave, Veterinary City</li>
            <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
            <li><i class="fas fa-envelope me-2"></i> info@petcare.com</li>
            <li><i class="fas fa-clock me-2"></i> Mon-Fri: 8AM-8PM, Sat: 9AM-5PM</li>
          </ul>
        </div>
      </div>
      <div class="copyright">
        <p>&copy; 2023 PetCare Veterinary Clinic. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- AOS Animation JS -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <!-- Glide.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Glide.js/3.5.0/glide.min.js"></script>
  
  <!-- Custom JS -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS animation
      AOS.init({
        once: true,
        easing: 'ease-out-cubic',
        duration: 800
      });
      
      // Navbar scroll effect
      const navbar = document.querySelector('.navbar');
      window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
          navbar.classList.add('scrolled');
        } else {
          navbar.classList.remove('scrolled');
        }
      });
      
      // Initialize Glide.js testimonials slider
      new Glide('.glide', {
        type: 'carousel',
        perView: 1,
        gap: 30,
        autoplay: 5000,
        breakpoints: {
          768: {
            perView: 1
          }
        }
      }).mount();
      
      // Add ripple effect to buttons
      const buttons = document.querySelectorAll('.btn');
      buttons.forEach(button => {
        button.addEventListener('click', function(e) {
          const x = e.clientX - e.target.getBoundingClientRect().left;
          const y = e.clientY - e.target.getBoundingClientRect().top;
          
          const ripple = document.createElement('span');
          ripple.className = 'ripple';
          ripple.style.left = `${x}px`;
          ripple.style.top = `${y}px`;
          
          this.appendChild(ripple);
          
          setTimeout(() => {
            ripple.remove();
          }, 1000);
        });
      });
      
      // Service card hover effects
      const serviceCards = document.querySelectorAll('.service-card');
      serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          const icon = this.querySelector('.service-icon');
          icon.style.transform = 'rotate(15deg) scale(1.1)';
        });
        
        card.addEventListener('mouseleave', function() {
          const icon = this.querySelector('.service-icon');
          icon.style.transform = 'rotate(0) scale(1)';
        });
      });
    });
  </script>
</body>
</html>