<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['userId'];

// Fetch existing details for the logged-in user
$query = "SELECT * FROM academic WHERE academicUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$academicSubmitted = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = $_POST['school_name'];
    $yearOfPassing = $_POST['yearOfPassing'] ?? 0;
    $tamil = isset($_POST['tamil']) ? (int)$_POST['tamil'] : 0;
    $english = isset($_POST['english']) ? (int)$_POST['english'] : 0;
    $maths = isset($_POST['maths']) ? (int)$_POST['maths'] : 0;
    $science = isset($_POST['science']) ? (int)$_POST['science'] : 0;
    $socialScience = isset($_POST['socialScience']) ? (int)$_POST['socialScience'] : 0;
    $otherLanguage = isset($_POST['otherLanguage']) ? (int)$_POST['otherLanguage'] : 0;

    // Update the academic details
    $query = "UPDATE academic 
              SET school_name = ?, yearOfPassing = ?, tamilMarks = ?, englishMarks = ?, mathsMarks = ?, scienceMarks = ?, socialScienceMarks = ?, otherLanguageMarks = ?
              WHERE academicUserId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiiiiiii", $school_name, $yearOfPassing, $tamil, $english, $maths, $science, $socialScience, $otherLanguage, $userId);
    $stmt->execute();

    header("Location: status.php");
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
<?php
include '../header.php';
?>

<div class="container mt-5">
    <h2>Edit Academic Details</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="school_name" class="form-label">School Name</label>
            <input type="text" name="school_name" id="school_name" class="form-control" value="<?= htmlspecialchars($academicSubmitted['school_name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="yearOfPassing" class="form-label">Year of Passing</label>
            <input type="number" name="yearOfPassing" id="yearOfPassing" class="form-control" value="<?= htmlspecialchars($academicSubmitted['yearOfPassing'] ?? 0) ?>" required>
        </div>
        <div class="mb-3">
            <label for="tamil" class="form-label">Tamil Marks</label>
            <input type="number" name="tamil" id="tamil" class="form-control" value="<?= htmlspecialchars($academicSubmitted['tamilMarks'] ?? 0) ?>" required>
        </div>
        <div class="mb-3">
            <label for="english" class="form-label">English Marks</label>
            <input type="number" name="english" id="english" class="form-control" value="<?= htmlspecialchars($academicSubmitted['englishMarks'] ?? 0) ?>" required>
        </div>
        <div class="mb-3">
            <label for="maths" class="form-label">Maths Marks</label>
            <input type="number" name="maths" id="maths" class="form-control" value="<?= htmlspecialchars($academicSubmitted['mathsMarks'] ?? 0) ?>" required>
        </div>
        <div class="mb-3">
            <label for="science" class="form-label">Science Marks</label>
            <input type="number" name="science" id="science" class="form-control" value="<?= htmlspecialchars($academicSubmitted['scienceMarks'] ?? 0) ?>" required>
        </div>
        <div class="mb-3">
            <label for="socialScience" class="form-label">Social Science Marks</label>
            <input type="number" name="socialScience" id="socialScience" class="form-control" value="<?= htmlspecialchars($academicSubmitted['socialScienceMarks'] ?? 0) ?>" required>
        </div>
        <div class="mb-3">
            <label for="otherLanguage" class="form-label">Other Language Marks</label>
            <input type="number" name="otherLanguage" id="otherLanguage" class="form-control" value="<?= htmlspecialchars($academicSubmitted['otherLanguageMarks'] ?? 0) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
</body>
</html>
