<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['userId'];

// Fetch existing details for the logged-in user
$query = "SELECT * FROM preference WHERE preferenceUserId = ? ORDER BY preferenceOrder ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$preferences = [];
while ($row = $result->fetch_assoc()) {
    $preferences[] = $row;
}

$department1 = $preferences[0]['preferenceDepartment'] ?? ''; // First preference
$department2 = $preferences[1]['preferenceDepartment'] ?? ''; // Second preference

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department1 = isset($_POST['department1']) ? $_POST['department1'] : null;
    $department2 = isset($_POST['department2']) ? $_POST['department2'] : null;

    // Update the preference details
    if ($preferences) {
        // Update existing preferences
        $query = "UPDATE preference SET preferenceDepartment = ? WHERE preferenceUserId = ? AND preferenceOrder = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $department1, $userId);
        $stmt->execute();

        $query = "UPDATE preference SET preferenceDepartment = ? WHERE preferenceUserId = ? AND preferenceOrder = 2";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $department2, $userId);
        $stmt->execute();
    } else {
        // Insert new preferences
        $query = "INSERT INTO preference (preferenceUserId, preferenceOrder, preferenceDepartment) VALUES (?, 1, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $userId, $department1);
        $stmt->execute();

        $query = "INSERT INTO preference (preferenceUserId, preferenceOrder, preferenceDepartment) VALUES (?, 2, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $userId, $department2);
        $stmt->execute();
    }

    header("Location: forms.php?form=document");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Academic Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* styles.css */
    body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f6f8;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: #333;
}

.container {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 20px;
    max-width: 400px;
    width: 100%;
}

h2 {
    text-align: center;
    color: #222;
    margin-bottom: 15px;
    font-size: 20px;
}

p.text-success {
    text-align: center;
    color: #28a745;
    font-size: 14px;
    margin-bottom: 10px;
}

form {
    margin-top: 15px;
}

.mb-3 {
    margin-bottom: 12px;
}

.form-label {
    font-weight: 500;
    color: #555;
    font-size: 14px;
    margin-bottom: 5px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ccc;
    padding: 8px 12px;
    font-size: 13px;
    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    width: 100%;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
    outline: none;
}

button.btn {
    background-color: #007bff;
    color: #ffffff;
    padding: 8px 12px;
    font-size: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: 100%;
}

button.btn:hover {
    background-color: #0056b3;
}

select.form-control {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22 viewBox%3D%220 0 4 5%22%3E%3Cpath fill%3D%22%23333%22 d%3D%22M2 0L0 2h4zm0 5L0 3h4z%22%2F%3E%3C%2Fsvg%3E');
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 10px;
    padding-right: 25px;
}

.total-marks {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-top: 10px;
    text-align: center;
}

input[type="file"] {
    border: 1px solid #ccc;
    padding: 6px;
    font-size: 13px;
    border-radius: 8px;
}

input[type="file"]:focus {
    border: 1px solid #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
    outline: none;
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    h2 {
        font-size: 18px;
    }

    button.btn {
        font-size: 13px;
        padding: 7px 10px;
    }
}
/* Styling for main heading */
h2 {
    font-family: 'Poppins', sans-serif; /* Use a modern font */
    color: #2c3e50; /* Professional dark blue */
    text-align: center;
    margin-bottom: 20px;
    font-weight: 600;
    letter-spacing: 1.5px;
    line-height: 1.4;
}

h2 u {
    text-decoration: none; /* Remove default underline */
    border-bottom: 3px solid #3498db; /* Add a modern underline effect */
    padding-bottom: 5px;
}

/* Styling for subheading */
h2 + h2 {
    font-size: 18px;
    font-weight: 500;
    color: #6c757d; /* Subtle gray color */
    margin-top: -10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Success message styling */
.text-success {
    font-family: 'Arial', sans-serif;
    font-size: 14px;
    color: #28a745;
    text-align: center;
    margin-bottom: 15px;
    font-weight: 500;
}


    </style>
</head>
<body>
    
<div class="container mt-5">
    <h2>Edit Preferences</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="department1" class="form-label">First Preference</label>
            <select name="department1" id="department1" class="form-control" required>
                <option value="">Select a Department</option>
                <option value="Civil Engineering" <?= $department1 == 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                <option value="Mechanical Engineering" <?= $department1 == 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                <option value="Electrical and Electronics Engineering" <?= $department1 == 'Electrical and Electronics Engineering' ? 'selected' : '' ?>>Electrical and Electronics Engineering</option>
                <option value="Electrical and Communication Engineering" <?= $department1 == 'Electrical and Communication Engineering' ? 'selected' : '' ?>>Electrical and Communication Engineering</option>
                <option value="Automobile Engineering" <?= $department1 == 'Automobile Engineering' ? 'selected' : '' ?>>Automobile Engineering</option>
                <option value="Textile Technology" <?= $department1 == 'Textile Technology' ? 'selected' : '' ?>>Textile Technology</option>
                <option value="Computer Technology" <?= $department1 == 'Computer Technology' ? 'selected' : '' ?>>Computer Technology</option>
                <option value="Printing Technology" <?= $department1 == 'Printing Technology' ? 'selected' : '' ?>>Printing Technology</option>
                <option value="Mechanical Engineering (R&AC)" <?= $department1 == 'Mechanical Engineering (R&AC)' ? 'selected' : '' ?>>Mechanical Engineering (R&AC)</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="department2" class="form-label">Second Preference</label>
            <select name="department2" id="department2" class="form-control" required>
                <option value="">Select a Department</option>
                <option value="Civil Engineering" <?= $department2 == 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                <option value="Mechanical Engineering" <?= $department2 == 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                <option value="Electrical and Electronics Engineering" <?= $department2 == 'Electrical and Electronics Engineering' ? 'selected' : '' ?>>Electrical and Electronics Engineering</option>
                <option value="Electrical and Communication Engineering" <?= $department2 == 'Electrical and Communication Engineering' ? 'selected' : '' ?>>Electrical and Communication Engineering</option>
                <option value="Automobile Engineering" <?= $department2 == 'Automobile Engineering' ? 'selected' : '' ?>>Automobile Engineering</option>
                <option value="Textile Technology" <?= $department2 == 'Textile Technology' ? 'selected' : '' ?>>Textile Technology</option>
                <option value="Computer Technology" <?= $department2 == 'Computer Technology' ? 'selected' : '' ?>>Computer Technology</option>
                <option value="Printing Technology" <?= $department2 == 'Printing Technology' ? 'selected' : '' ?>>Printing Technology</option>
                <option value="Mechanical Engineering (R&AC)" <?= $department2 == 'Mechanical Engineering (R&AC)' ? 'selected' : '' ?>>Mechanical Engineering (R&AC)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
</body>
</html>
