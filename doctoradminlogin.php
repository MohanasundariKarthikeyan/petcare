<?php
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === "Admin" && $password === "Admin@123") {
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = $username;
        header("Location: admin.php");
        exit;
    } elseif ($username === "Doctor101" && $password === "Doctor@101") {
        $_SESSION['role'] = 'doctor';
        $_SESSION['username'] = $username;
        header("Location: doctor.php");
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - PetCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    :root {
      --primary-color: #3498db;
      --secondary-color: #2ecc71;
      --accent-color: #e74c3c;
      --dark-color: #2c3e50;
      --light-color: #ecf0f1;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                  url('https://wallpapers.com/images/hd/dark-animals-9syx082drq1lkwly.jpg') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      flex-direction: column;
      color: var(--light-color);
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
    
    .header-buttons .btn {
      font-size: 0.9rem;
      padding: 8px 18px;
      border-radius: 30px;
      font-weight: 600;
      margin-left: 10px;
      transition: all 0.3s ease;
    }
    
    .header-buttons .btn-outline-light:hover {
      background-color: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }
    
    .main-content {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .login-container {
      width: 100%;
      max-width: 450px;
      margin: 40px 0;
    }
    
    .login-box {
      background: rgba(255, 255, 255, 0.12);
      padding: 35px 30px;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: transform 0.3s ease;
    }
    
    .login-box:hover {
      transform: translateY(-5px);
    }
    
    .login-title {
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 25px;
      color: var(--light-color);
      text-align: center;
      position: relative;
      padding-bottom: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .login-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    .user-icon {
      width: 30px;
      height: 30px;
      margin-right: 12px;
      filter: brightness(0) invert(1);
    }
    
    .form-label {
      font-weight: 600;
      color: var(--light-color);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
    }
    
    .form-label span {
      color: var(--accent-color);
      margin-left: 4px;
    }
    
    .input-group {
      position: relative;
      margin-bottom: 20px;
    }
    
    .form-control {
      border-radius: 12px;
      background-color: rgba(255, 255, 255, 0.1);
      border: 2px solid rgba(255, 255, 255, 0.2);
      color: var(--light-color);
      padding: 12px 20px;
      height: 50px;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
      border-color: var(--primary-color);
      background-color: rgba(255, 255, 255, 0.15);
    }
    
    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }
    
    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--primary-color);
      transition: color 0.3s ease;
    }
    
    .password-toggle:hover {
      color: var(--primary-color);
    }
    
    .btn-login {
      width: 100%;
      border-radius: 12px;
      padding: 14px;
      font-weight: bold;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      margin-top: 10px;
      transition: all 0.3s ease;
      height: 50px;
      font-size: 1.1rem;
    }
    
    .btn-login:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 14px rgba(0, 0, 0, 0.3);
      background: linear-gradient(90deg, #2980b9, #27ae60);
    }
    
    .error-msg {
      color: var(--accent-color);
      margin-top: 15px;
      font-size: 0.95rem;
      text-align: center;
      padding: 10px;
      background: rgba(231, 76, 60, 0.15);
      border-radius: 8px;
      border-left: 4px solid var(--accent-color);
    }
    
    .error-span {
      color: var(--accent-color);
      font-size: 0.85rem;
      margin-top: 5px;
      display: none;
    }
    
    .demo-accounts {
      margin-top: 25px;
      padding: 15px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      border-left: 4px solid var(--primary-color);
    }
    
    .demo-title {
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--light-color);
      display: flex;
      align-items: center;
    }
    
    .demo-title i {
      margin-right: 8px;
      color: var(--primary-color);
    }
    
    .demo-account {
      margin-bottom: 8px;
      font-size: 0.9rem;
    }
    
    .demo-account:last-child {
      margin-bottom: 0;
    }
    
    .demo-role {
      font-weight: 600;
      color: var(--primary-color);
    }
    
    @media (max-width: 576px) {
      .login-box {
        padding: 25px 20px;
      }
      
      .login-title {
        font-size: 1.75rem;
      }
      
      .header-buttons .btn {
        padding: 6px 12px;
        font-size: 0.8rem;
        margin-left: 5px;
      }
      
      .demo-accounts {
        padding: 12px;
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
        
        </div>
    </nav>

  <div class="main-content">
    <div class="login-container">
      <div class="login-box">
        <div class="login-title">
          <i class="fas fa-user-circle user-icon"></i> Login
        </div>
        <form method="POST" onsubmit="return validateForm();" autocomplete="off" id="loginForm">
          <div class="mb-3">
            <label for="username" class="form-label">Username <span>*</span></label>
            <input type="text" name="username" id="username" class="form-control" oninput="checkUsername()" autocomplete="off" />
            <span class="error-span" id="userError">Please enter your username.</span>
          </div>
          
          <div class="mb-3">
            <label for="password" class="form-label">Password <span>*</span></label>
            <div class="input-group">
              <input type="password" name="password" id="password" class="form-control" oninput="checkPassword()" autocomplete="new-password" />
              <span class="password-toggle" id="passwordToggle">
                <i class="fas fa-eye"></i>
              </span>
            </div>
            <span class="error-span" id="passError">Please enter your password.</span>
          </div>
          
          <button type="submit" class="btn btn-login">Login</button>
          
          <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
        </form>
        
       
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Clear any existing values on page load
      document.getElementById("username").value = "";
      document.getElementById("password").value = "";
      
      // Password visibility toggle
      const passwordToggle = document.getElementById('passwordToggle');
      const passwordField = document.getElementById('password');
      
      passwordToggle.addEventListener('click', function() {
        if (passwordField.type === 'password') {
          passwordField.type = 'text';
          passwordToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
          passwordField.type = 'password';
          passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
        }
      });
    });

    function checkUsername() {
      const username = document.getElementById("username").value.trim();
      document.getElementById("userError").style.display = username === "" ? "block" : "none";
    }

    function checkPassword() {
      const password = document.getElementById("password").value.trim();
      document.getElementById("passError").style.display = password === "" ? "block" : "none";
    }

    function validateForm() {
      let valid = true;
      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value.trim();
      
      checkUsername();
      checkPassword();
      
      if (username === "" || password === "") {
        valid = false;
        
        // Add shake animation to empty fields
        if (username === "") {
          document.getElementById("username").classList.add('shake');
          setTimeout(() => {
            document.getElementById("username").classList.remove('shake');
          }, 500);
        }
        
        if (password === "") {
          document.getElementById("password").classList.add('shake');
          setTimeout(() => {
            document.getElementById("password").classList.remove('shake');
          }, 500);
        }
      }
      
      return valid;
    }
  </script>
</body>
</html>