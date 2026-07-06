<?php
session_start();
include 'config.php';

$isEdit = false;
$record = [
    'patient_id' => '',
    'pet_name' => '',
    'owner_name' => '',
    'category' => '',
    'visited_date' => date('Y-m-d'),
    'visited_time' => date('H:i'),
    'disease' => '',
    'symptoms' => '',
    'diagnosis' => '',
    'treatment' => '',
    'medications' => '',
    'next_visit' => ''
];

// Function to convert 24-hour time to 12-hour format
function convertTo12Hour($time24) {
    if (empty($time24)) return '';
    
    $time = DateTime::createFromFormat('H:i', $time24);
    return $time ? $time->format('h:i A') : '';
}

// Function to convert 12-hour time to 24-hour format
function convertTo24Hour($time12) {
    if (empty($time12)) return '';
    
    $time = DateTime::createFromFormat('h:i A', $time12);
    return $time ? $time->format('H:i') : '';
}

// Fetch appointment data when patient_id is provided
if (isset($_GET['fetch_appointment']) && !empty($_GET['patient_id'])) {
    $patient_id = trim($_GET['patient_id']);
    $sql = "SELECT * FROM appointments4 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $appointment = $result->fetch_assoc();
            
            // Return JSON response for AJAX call
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'pet_name' => $appointment['pet_name'],
                    'owner_name' => $appointment['owner_name'],
                    'breed' => $appointment['breed'],
                    'appointment_date' => $appointment['appointment_date'],
                    'appointment_time' => convertTo12Hour($appointment['appointment_time'])
                ]
            ]);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "No appointment found with ID: " . $patient_id
            ]);
            exit();
        }
        $stmt->close();
    }
}

if (isset($_GET['id'])) {
    $isEdit = true;
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM createrecord WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        
        // Convert visited_time to 12-hour format for display
        if (!empty($record['visited_time'])) {
            $record['visited_time'] = convertTo12Hour($record['visited_time']);
        }
        
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_record'])) {
    $patient_id = trim($_POST['patient_id']);
    $pet_name = trim($_POST['pet_name']);
    $owner_name = trim($_POST['owner_name']);
    $category = trim($_POST['category']);
    $visited_date = trim($_POST['visited_date']);
    $visited_time = trim($_POST['visited_time']);
    $disease = trim($_POST['disease']);
    $symptoms = trim($_POST['symptoms']);
    $diagnosis = trim($_POST['diagnosis']);
    $treatment = trim($_POST['treatment']);
    $medications = trim($_POST['medications']);
    $next_visit = !empty($_POST['next_visit']) ? trim($_POST['next_visit']) : null;

    // Convert 12-hour time to 24-hour format for database storage
    if (!empty($visited_time)) {
        $visited_time = convertTo24Hour($visited_time);
    }

    if ($isEdit) {
        $sql = "UPDATE createrecord SET patient_id=?, pet_name=?, owner_name=?, category=?, visited_date=?, visited_time=?, disease=?, symptoms=?, diagnosis=?, treatment=?, medications=?, next_visit=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssssssssi", $patient_id, $pet_name, $owner_name, $category, $visited_date, $visited_time, $disease, $symptoms, $diagnosis, $treatment, $medications, $next_visit, $id);
        }
    } else {
        $sql = "INSERT INTO createrecord (patient_id, pet_name, owner_name, category, visited_date, visited_time, disease, symptoms, diagnosis, treatment, medications, next_visit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssssssss", $patient_id, $pet_name, $owner_name, $category, $visited_date, $visited_time, $disease, $symptoms, $diagnosis, $treatment, $medications, $next_visit);
        }
    }

    if ($stmt && $stmt->execute()) {
        $_SESSION['message'] = $isEdit ? "Record updated successfully!" : "Record created successfully!";
        header("Location: doctor.php");
        exit();
    } else {
        $error = "Error saving record: " . ($stmt ? $stmt->error : $conn->error);
    }
    if ($stmt) $stmt->close();
}

// Set default time values for new records
$defaultHour = date('h');
$defaultMinute = round(date('i') / 5) * 5; // Round to nearest 5 minutes
$defaultAmPm = date('A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= $isEdit ? 'Edit' : 'Create' ?> Pet Medical Record</title>
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
            max-width: 900px;
            margin: auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            color: #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 25px;
        }

        label {
            color: #fff;
        }

        .form-control, .form-select, textarea {
            background-color: #ffffff; /* Changed to white */
            border: 1px solid #ccc;
            color: #000;
        }

        .btn-save {
            background-color: #4CAF50;
            color: white;
            font-weight: 600;
        }
        
        .btn-fetch {
            background-color: #2196F3;
            color: white;
            font-weight: 600;
        }

        .btn-cancel {
            background-color: #f44336;
            color: white;
        }

        .btn-save:hover {
            background-color: #45a049;
        }
        
        .btn-fetch:hover {
            background-color: #0b7dda;
        }

        .btn-cancel:hover {
            background-color: #d32f2f;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .time-selector {
            display: flex;
            gap: 10px;
        }
        
        .time-selector select {
            flex: 1;
        }
        
        .time-selector .ampm-select {
            flex: 0 0 80px;
        }
        
        .fetch-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .fetch-container input {
            flex: 1;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .patient-id-container {
            position: relative;
            display: flex;
            align-items: center;
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
            <a href="doctoradminlogin.php">Logout</a>
        </div>
    </nav>

<div class="page-content">
    <div class="container-glass">
        <h1><?= $isEdit ? 'Edit' : 'Create' ?> Patient Details</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <form method="post" id="recordForm">
            <input type="hidden" name="save_record" value="1">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="patient_id" class="form-label">Patient ID*</label>
                    <div class="patient-id-container">
                        <input type="text" class="form-control" id="patient_id" name="patient_id" required value="<?= htmlspecialchars($record['patient_id']) ?>" onblur="fetchAppointmentData()">
                        <div id="loadingSpinner" class="loading-spinner"></div>
                    </div>
                    <div id="fetchError" class="error mt-2" style="display: none;"></div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="pet_name" class="form-label">Pet Name*</label>
                    <input type="text" class="form-control" id="pet_name" name="pet_name" required value="<?= htmlspecialchars($record['pet_name']) ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="owner_name" class="form-label">Owner Name*</label>
                    <input type="text" class="form-control" id="owner_name" name="owner_name" required value="<?= htmlspecialchars($record['owner_name']) ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label">Category*</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Dog" <?= $record['category'] == 'Dog' ? 'selected' : '' ?>>Dog</option>
                        <option value="Cat" <?= $record['category'] == 'Cat' ? 'selected' : '' ?>>Cat</option>
                        <option value="Bird" <?= $record['category'] == 'Bird' ? 'selected' : '' ?>>Bird</option>
                        <option value="Other" <?= $record['category'] == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="visited_date" class="form-label">Visited Date*</label>
                    <input type="date" class="form-control" id="visited_date" name="visited_date" required value="<?= htmlspecialchars($record['visited_date']) ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label for="visited_time" class="form-label">Visited Time*</label>
                    <div class="time-selector">
                        <select class="form-select" id="hour_select" required>
                            <option value="">Hour</option>
                            <?php for ($i = 1; $i <= 12; $i++): 
                                $hourValue = sprintf('%02d', $i);
                                $isSelected = false;
                                
                                // Check if editing and set selected value
                                if ($isEdit && !empty($record['visited_time'])) {
                                    $timeParts = explode(':', $record['visited_time']);
                                    $hourPart = (int)$timeParts[0];
                                    $ampmPart = isset($timeParts[1]) ? explode(' ', $timeParts[1])[1] : '';
                                    
                                    $displayHour = $hourPart % 12;
                                    if ($displayHour == 0) $displayHour = 12;
                                    
                                    if ($displayHour == $i && $ampmPart == $defaultAmPm) {
                                        $isSelected = true;
                                    }
                                } elseif (!$isEdit && $hourValue == $defaultHour) {
                                    $isSelected = true;
                                }
                            ?>
                                <option value="<?= $hourValue ?>" <?= $isSelected ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-select" id="minute_select" required>
                            <option value="">Minute</option>
                            <?php for ($i = 0; $i < 60; $i += 5): 
                                $minuteValue = sprintf('%02d', $i);
                                $isSelected = false;
                                
                                // Check if editing and set selected value
                                if ($isEdit && !empty($record['visited_time'])) {
                                    $timeParts = explode(':', $record['visited_time']);
                                    $minutePart = isset($timeParts[1]) ? (int)explode(' ', $timeParts[1])[0] : 0;
                                    
                                    if ($minutePart == $i) {
                                        $isSelected = true;
                                    }
                                } elseif (!$isEdit && $minuteValue == $defaultMinute) {
                                    $isSelected = true;
                                }
                            ?>
                                <option value="<?= $minuteValue ?>" <?= $isSelected ? 'selected' : '' ?>><?= $minuteValue ?></option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-select ampm-select" id="ampm_select" required>
                            <?php 
                            $amSelected = false;
                            $pmSelected = false;
                            
                            if ($isEdit && !empty($record['visited_time'])) {
                                $timeParts = explode(':', $record['visited_time']);
                                $hourPart = (int)$timeParts[0];
                                $ampmPart = isset($timeParts[1]) ? explode(' ', $timeParts[1])[1] : '';
                                
                                $amSelected = ($ampmPart == 'AM');
                                $pmSelected = ($ampmPart == 'PM');
                            } else {
                                $amSelected = ($defaultAmPm == 'AM');
                                $pmSelected = ($defaultAmPm == 'PM');
                            }
                            ?>
                            <option value="AM" <?= $amSelected ? 'selected' : '' ?>>AM</option>
                            <option value="PM" <?= $pmSelected ? 'selected' : '' ?>>PM</option>
                        </select>
                    </div>
                    <input type="hidden" id="visited_time" name="visited_time" value="<?= htmlspecialchars($record['visited_time']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="disease" class="form-label">Disease/Issue*</label>
                <textarea class="form-control" id="disease" name="disease" rows="3" required><?= htmlspecialchars($record['disease']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="symptoms" class="form-label">Symptoms</label>
                <textarea class="form-control" id="symptoms" name="symptoms" rows="3"><?= htmlspecialchars($record['symptoms']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="diagnosis" class="form-label">Diagnosis</label>
                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3"><?= htmlspecialchars($record['diagnosis']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="treatment" class="form-label">Treatment</label>
                <textarea class="form-control" id="treatment" name="treatment" rows="3"><?= htmlspecialchars($record['treatment']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="medications" class="form-label">Medications</label>
                <textarea class="form-control" id="medications" name="medications" rows="3"><?= htmlspecialchars($record['medications']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="next_visit" class="form-label">Next Visit</label>
                <input type="date" class="form-control" id="next_visit" name="next_visit" value="<?= htmlspecialchars($record['next_visit']) ?>">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-save"><?= $isEdit ? 'Update' : 'Save' ?> Record</button>
                <a href="doctor.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update hidden time field when selections change
    const hourSelect = document.getElementById('hour_select');
    const minuteSelect = document.getElementById('minute_select');
    const ampmSelect = document.getElementById('ampm_select');
    const hiddenTimeField = document.getElementById('visited_time');
    
    function updateTimeField() {
        const hour = hourSelect.value;
        const minute = minuteSelect.value;
        const ampm = ampmSelect.value;
        
        if (hour && minute && ampm) {
            // Convert to 24-hour format for storage
            let hour24 = parseInt(hour);
            if (ampm === 'PM' && hour24 < 12) {
                hour24 += 12;
            } else if (ampm === 'AM' && hour24 === 12) {
                hour24 = 0;
            }
            
            hiddenTimeField.value = `${hour24.toString().padStart(2, '0')}:${minute} ${ampm}`;
        } else {
            hiddenTimeField.value = '';
        }
    }
    
    hourSelect.addEventListener('change', updateTimeField);
    minuteSelect.addEventListener('change', updateTimeField);
    ampmSelect.addEventListener('change', updateTimeField);
    
    // Initialize the time field
    updateTimeField();
    
    // Validate form before submission
    document.getElementById('recordForm').addEventListener('submit', function(e) {
        updateTimeField(); // Ensure the hidden field is updated
        
        if (!hourSelect.value || !minuteSelect.value || !ampmSelect.value) {
            e.preventDefault();
            alert('Please select a valid time');
            return false;
        }
    });
});

// Function to fetch appointment data
function fetchAppointmentData() {
    const patientId = document.getElementById('patient_id').value.trim();
    const loadingSpinner = document.getElementById('loadingSpinner');
    const errorDiv = document.getElementById('fetchError');
    
    // Clear previous errors
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
    
    if (!patientId) return;
    
    // Show loading spinner
    loadingSpinner.style.display = 'block';
    
    // Make AJAX request to fetch appointment data
    fetch(`?fetch_appointment=1&patient_id=${encodeURIComponent(patientId)}`)
        .then(response => response.json())
        .then(data => {
            loadingSpinner.style.display = 'none';
            
            if (data.success) {
                // Populate form fields with fetched data
                document.getElementById('pet_name').value = data.data.pet_name || '';
                document.getElementById('owner_name').value = data.data.owner_name || '';
                document.getElementById('category').value = data.data.breed || '';
                document.getElementById('visited_date').value = data.data.appointment_date || '';
                
                // If time is provided, parse and set time fields
                if (data.data.appointment_time) {
                    const timeParts = data.data.appointment_time.split(':');
                    const timeValue = timeParts[0];
                    const ampm = timeParts[1].split(' ')[1];
                    
                    // Set hour
                    let hour = parseInt(timeValue);
                    if (ampm === 'PM' && hour < 12) hour += 12;
                    if (ampm === 'AM' && hour === 12) hour = 0;
                    
                    // Convert to 12-hour format for display
                    const displayHour = hour % 12 || 12;
                    document.getElementById('hour_select').value = displayHour.toString().padStart(2, '0');
                    
                    // Set minute
                    const minute = timeParts[1].split(' ')[0];
                    document.getElementById('minute_select').value = minute;
                    
                    // Set AM/PM
                    document.getElementById('ampm_select').value = ampm;
                    
                    // Update hidden time field
                    document.getElementById('visited_time').value = data.data.appointment_time;
                }
            } else {
                // Show error message
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            loadingSpinner.style.display = 'none';
            errorDiv.textContent = 'Error fetching appointment data';
            errorDiv.style.display = 'block';
            console.error('Error:', error);
        });
}
</script>

</body>
</html>