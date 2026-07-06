<?php
session_start();
include 'config.php';

// Search functionality
$search = '';
$where = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $where = " WHERE patient_id LIKE ? OR pet_name LIKE ? OR owner_name LIKE ? OR category LIKE ?";
}

$sql = "SELECT * FROM createrecord" . $where . " ORDER BY visited_date DESC";
$stmt = $conn->prepare($sql);

if (!empty($where)) {
    $searchTerm = "%$search%";
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Pet Medical Records</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4CAF50;
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
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-box button {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .edit-btn {
            background: #2196F3;
            color: white;
        }
        .edit-btn:hover {
            background: #0b7dda;
        }
        .create-btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-decoration: none;
        }
        .create-btn:hover {
            background: #45a049;
        }
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pet Medical Records</h1>
        
        <a href="createrecord.php" class="create-btn">Create New Record</a>
        
        <form method="get" class="search-box">
            <input type="text" name="search" placeholder="Search by ID, Pet Name, Owner Name, or Category" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
        
        <?php if (empty($records)): ?>
            <p>No records found. <a href="viewrecord.php">Clear search</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        
                        <th>Patient ID</th>
                        <th>Pet Name</th>
                        <th>Owner Name</th>
                        <th>Category</th>
                        <th>Breed</th>
                        <th>Disease</th>
                        <th>Mode</th>
                        <th>Visited Date</th>
                        <th>Weight</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            
                            <td><?= htmlspecialchars($record['patient_id']) ?></td>
                            <td><?= htmlspecialchars($record['pet_name']) ?></td>
                            <td><?= htmlspecialchars($record['owner_name']) ?></td>
                            <td><?= htmlspecialchars($record['category']) ?></td>
                            <td><?= htmlspecialchars($record['breed']) ?></td>
                            <td><?= nl2br(htmlspecialchars(substr($record['disease'], 0, 50))) ?>...</td>
                            <td><?= htmlspecialchars($record['mode']) ?></td>
                            <td><?= date('M j, Y', strtotime($record['visited_date'])) ?></td>
                            <td><?= $record['weight'] ?> kg</td>
                           
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
