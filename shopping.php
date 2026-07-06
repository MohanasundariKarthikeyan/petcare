<?php
session_start();
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'petdemo';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            // Prepare statement with error handling
            $stmt = $conn->prepare("SELECT id, username, password FROM shopplogin WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $username);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("Get result failed: " . $stmt->error);
            }

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['password'])) {
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set session variables - using the same names as shoppinghome.php expects
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['loggedin'] = true;
                    $_SESSION['last_login'] = time();
                    
                    header("Location: shoppinghome.php");
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }

            $stmt->close();
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred during login. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop Login - PetCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
      font-size: 1.8rem;
      margin-right: 10px;
      color: var(--primary-color);
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
      color: rgba(255, 255, 255, 0.6);
      transition: color 0.3s ease;
    }
    
    .password-toggle:hover {
      color: var(--light-color);
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
    
    .error-msg, .success-msg {
      margin-top: 15px;
      font-size: 0.95rem;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
    }
    
    .error-msg {
      color: var(--accent-color);
      background: rgba(231, 76, 60, 0.15);
      border-left: 4px solid var(--accent-color);
    }
    
    .success-msg {
      color: var(--secondary-color);
      background: rgba(46, 204, 113, 0.15);
      border-left: 4px solid var(--secondary-color);
    }
    
    .login-link {
      text-align: center;
      color: var(--light-color);
      font-size: 0.95rem;
      display: block;
      margin-top: 20px;
    }
    
    .login-link a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }
    
    .login-link a:hover {
      color: var(--secondary-color);
      text-decoration: underline;
    }
    
    .error-span {
      color: var(--accent-color);
      font-size: 0.85rem;
      display: none;
      margin-top: 5px;
    }
    
    .forgot-password {
      display: block;
      margin-top: 10px;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.85rem;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    
    .forgot-password:hover {
      color: var(--light-color);
      text-decoration: underline;
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
        </div>
    </nav>

  <div class="main-content">
    <div class="login-container">
      <div class="login-box">
        <div class="login-title">
          <i class="fas fa-user-circle user-icon"></i> Shop Login
        </div>

        <?php if (isset($_SESSION['registration_success'])): ?>
          <div class="success-msg">
            <i class="fas fa-check-circle"></i> Account created successfully. You can login now.
          </div>
          <?php unset($_SESSION['registration_success']); ?>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="error-msg">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" autocomplete="off" id="loginForm">
          <div class="mb-3">
            <label class="form-label">Username <span>*</span></label>
            <input type="text" name="username" id="username" class="form-control" autocomplete="off" />
            <span class="error-span" id="userError">
              <i class="fas fa-exclamation-circle"></i> Please enter your username.
            </span>
          </div>
          
          <div class="mb-4">
            <label class="form-label">Password <span>*</span></label>
            <div class="input-group">
              <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" />
              <span class="password-toggle" id="passwordToggle">
                <i class="fas fa-eye"></i>
              </span>
            </div>
            <span class="error-span" id="passError">
              <i class="fas fa-exclamation-circle"></i> Please enter your password.
            </span>
          
          <button type="submit" class="btn btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
          </button>
        </form>

        <div class="login-link">
          Don't have an account? <a href="shoppingcreate.php">Create an account</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
      
      // Form validation
      const loginForm = document.getElementById('loginForm');
      const usernameField = document.getElementById('username');
      const passwordField = document.getElementById('password');
      const userError = document.getElementById('userError');
      const passError = document.getElementById('passError');
      
      function validateForm() {
        let isValid = true;
        
        // Validate username
        if (usernameField.value.trim() === '') {
          userError.style.display = 'block';
          isValid = false;
        } else {
          userError.style.display = 'none';
        }
        
        // Validate password
        if (passwordField.value.trim() === '') {
          passError.style.display = 'block';
          isValid = false;
        } else {
          passError.style.display = 'none';
        }
        
        return isValid;
      }
      
      // Real-time validation
      usernameField.addEventListener('input', function() {
        if (usernameField.value.trim() !== '') {
          userError.style.display = 'none';
        }
      });
      
      passwordField.addEventListener('input', function() {
        if (passwordField.value.trim() !== '') {
          passError.style.display = 'none';
        }
      });
      
      // Form submission
      loginForm.addEventListener('submit', function(e) {
        if (!validateForm()) {
          e.preventDefault();
          
          // Focus on the first empty field
          if (usernameField.value.trim() === '') {
            usernameField.focus();
          } else if (passwordField.value.trim() === '') {
            passwordField.focus();
          }
        }
      });
    });
  </script>
</body>
</html>