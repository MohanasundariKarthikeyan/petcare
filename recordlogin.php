<?php
session_start();
include 'config.php'; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT password FROM recordlogin WHERE username = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        // Check if passwords match (Assuming passwords are hashed)
        if (password_verify($password, $hashed_password)) {
            $_SESSION['username'] = $username; // Store username in session
            header("Location: petsrecord.php"); // Redirect to booking form
            exit(); // Ensure no further code is executed
        } else {
            $error = "Invalid username or password."; // General error message
        }
    } else {
        $error = "Invalid username or password."; // General error message
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetCare</title>
    <style>
        body {
            background-image: url('https://www.harringtonspetfood.com/cdn/shop/articles/shutterstock_2475092277.jpg?v=1719476667');
            background-size: cover;
            background-repeat: no-repeat;
            font-family: 'Arial', sans-serif;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
            transition: transform 0.3s;
        }

        .container:hover {
            transform: scale(1.02);
        }

        h1 {
            margin-bottom: 20px;
            color: #4CAF50;
            font-size: 28px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            text-align: left;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #4CAF50;
            outline: none;
        }

        input[type="submit"] {
            background-color: #4caf50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            margin-top: 10px;
            font-size: 14px;
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .appointment-btn {
            background-color: #3498db;
            color: white;
        }

        .appointment-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <form method="post" action="login.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <a href="createrecord.php" class="btn appointment-btn">Login</a>

            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

            
        </form>
        <div class="footer">
            <p>&copy; <?php echo date("Y"); ?> PetCare. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
