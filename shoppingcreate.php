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
    // Safely fetch values
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : '';
    $confirm_password = isset($_POST["confirm_password"]) ? trim($_POST["confirm_password"]) : '';

    // Validate empty fields
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM shopplogin WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO shopplogin (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['registration_success'] = "Account created successfully! Please login.";
                header("Location: shopping.php"); // redirect after success
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Create Account - PetCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
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
    
    .register-container {
      width: 100%;
      max-width: 450px;
      margin: 40px 0;
    }
    
    .register-box {
      background: rgba(255, 255, 255, 0.12);
      padding: 35px 30px;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: transform 0.3s ease;
    }
    
    .register-box:hover {
      transform: translateY(-5px);
    }
    
    .form-title {
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 25px;
      color: var(--light-color);
      text-align: center;
      position: relative;
      padding-bottom: 15px;
    }
    
    .form-title::after {
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
    
    .btn-register {
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
    
    .btn-register:hover {
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
    
    .password-strength {
      margin-top: 5px;
      height: 5px;
      border-radius: 5px;
      background: rgba(255, 255, 255, 0.2);
      overflow: hidden;
    }
    
    .password-strength-bar {
      height: 100%;
      width: 0;
      border-radius: 5px;
      transition: width 0.3s ease, background 0.3s ease;
    }
    
    .password-rules {
      font-size: 0.8rem;
      margin-top: 5px;
      color: rgba(255, 255, 255, 0.7);
    }
    
    .rule {
      margin-bottom: 3px;
    }
    
    .rule.valid {
      color: var(--secondary-color);
    }
    
    @media (max-width: 576px) {
      .register-box {
        padding: 25px 20px;
      }
      
      .form-title {
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
      <div class="register-container">
        <div class="register-box">
          <div class="form-title">Create Account</div>
          <form method="POST" action="" id="registrationForm">
            <div class="mb-3">
              <label class="form-label">Username <span>*</span></label>
              <input type="text" name="username" class="form-control" required autocomplete="off" />
            </div>
            
            <div class="mb-3">
              <label class="form-label">Email <span>*</span></label>
              <input type="email" name="email" class="form-control" required autocomplete="off" />
            </div>
            
            <div class="mb-3">
              <label class="form-label">Password <span>*</span></label>
              <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password" />
                <span class="password-toggle" id="passwordToggle">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              <div class="password-strength">
                <div class="password-strength-bar" id="passwordStrengthBar"></div>
              </div>
              <div class="password-rules">
                <div class="rule" id="lengthRule">At least 6 characters</div>
                <div class="rule" id="numberRule">Contains a number</div>
                <div class="rule" id="specialRule">Contains a special character</div>
              </div>
            </div>
            
            <div class="mb-4">
              <label class="form-label">Confirm Password <span>*</span></label>
              <div class="input-group">
                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required autocomplete="new-password" />
                <span class="password-toggle" id="confirmPasswordToggle">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              <div id="passwordMatch" class="password-rules"></div>
            </div>
            
            <button type="submit" class="btn btn-register">Create Account</button>

            <?php if (!empty($error)): ?>
              <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="login-link">
              Already have an account? <a href="shopping.php">Login here</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Password visibility toggling
        const passwordToggle = document.getElementById('passwordToggle');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirmPassword');
        const passwordStrengthBar = document.getElementById('passwordStrengthBar');
        const lengthRule = document.getElementById('lengthRule');
        const numberRule = document.getElementById('numberRule');
        const specialRule = document.getElementById('specialRule');
        const passwordMatch = document.getElementById('passwordMatch');
        
        // Clear all input fields on page load
        document.querySelectorAll('input').forEach(input => {
          input.value = '';
        });
        
        // Password visibility toggle
        passwordToggle.addEventListener('click', function() {
          if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
          } else {
            passwordField.type = 'password';
            passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
          }
        });
        
        // Confirm password visibility toggle
        confirmPasswordToggle.addEventListener('click', function() {
          if (confirmPasswordField.type === 'password') {
            confirmPasswordField.type = 'text';
            confirmPasswordToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
          } else {
            confirmPasswordField.type = 'password';
            confirmPasswordToggle.innerHTML = '<i class="fas fa-eye"></i>';
          }
        });
        
        // Password strength checker
        passwordField.addEventListener('input', function() {
          const password = passwordField.value;
          let strength = 0;
          
          // Check password length
          if (password.length >= 6) {
            strength += 25;
            lengthRule.classList.add('valid');
          } else {
            lengthRule.classList.remove('valid');
          }
          
          // Check for numbers
          if (/\d/.test(password)) {
            strength += 25;
            numberRule.classList.add('valid');
          } else {
            numberRule.classList.remove('valid');
          }
          
          // Check for special characters
          if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            strength += 25;
            specialRule.classList.add('valid');
          } else {
            specialRule.classList.remove('valid');
          }
          
          // Check for uppercase and lowercase letters
          if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
            strength += 25;
          }
          
          // Update strength bar
          passwordStrengthBar.style.width = strength + '%';
          
          // Update strength bar color
          if (strength < 50) {
            passwordStrengthBar.style.background = '#e74c3c';
          } else if (strength < 75) {
            passwordStrengthBar.style.background = '#f39c12';
          } else {
            passwordStrengthBar.style.background = '#2ecc71';
          }
        });
        
        // Password confirmation check
        confirmPasswordField.addEventListener('input', function() {
          if (confirmPasswordField.value === passwordField.value) {
            passwordMatch.innerHTML = 'Passwords match!';
            passwordMatch.style.color = '#2ecc71';
          } else {
            passwordMatch.innerHTML = 'Passwords do not match';
            passwordMatch.style.color = '#e74c3c';
          }
        });
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
          const password = passwordField.value;
          const confirmPassword = confirmPasswordField.value;
          
          if (password !== confirmPassword) {
            e.preventDefault();
            passwordMatch.innerHTML = 'Passwords do not match!';
            passwordMatch.style.color = '#e74c3c';
            confirmPasswordField.focus();
          }
        });
      });
    </script>
</body>
</html>