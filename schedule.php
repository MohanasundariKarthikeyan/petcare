<?php
session_start();
include 'config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: booking.php"); // Redirect to login page if not logged in
    exit();
}

// Fetch appointments from the database
$appointments = [];
$stmt = $conn->prepare("SELECT pet_name, owner_name, breed, email, phone, appointment_date, appointment_time FROM appointments ORDER BY appointment_date, appointment_time");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare - Appointment Schedule</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 10px 0;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        nav a:hover, nav a.active {
            background-color: #45a049;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h1 {
            color: #4CAF50;
            margin-bottom: 20px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="bookform.php">Book Appointment</a>
            <a href="schedule.php" class="active">Schedule</a>
        </nav>
    </header>

    <div class="container">
        <h1>Appointment Schedule</h1>

        <?php if (count($appointments) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Pet's Name</th>
                        <th>Owner's Name</th>
                        <th>Breed</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Appointment Date</th>
                        <th>Appointment Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['pet_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['breed']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['email']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['phone']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No appointments scheduled.</p>
        <?php endif; ?>
    </div>
</body>
</html>
