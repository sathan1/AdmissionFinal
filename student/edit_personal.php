<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['userId'];

// Fetch existing details
$query = "SELECT * FROM studentdetails WHERE studentUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$studentDetails = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $fatherName = $_POST['father_name'];
    $motherName = $_POST['mother_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $religion = $_POST['religion'];
    $community = $_POST['caste'];
    $caste = $_POST['otherCaste'];
    $motherTongue = $_POST['motherTongue'];
    $phoneNumber = $_POST['phone_number'];

    // Update details
    $query = "UPDATE studentdetails SET studentFirstName=?, studentLastName=?, studentFatherName=?, studentMotherName=?, studentDateOfBirth=?, studentGender=?, studentReligion=?, studentCaste=?, studentCaste_2=?, studentMotherTongue=?, studentPhoneNumber=? WHERE studentUserId=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssssssi", $firstName, $lastName, $fatherName, $motherName, $dob, $gender, $religion, $community, $caste, $motherTongue, $phoneNumber, $userId);
    $stmt->execute();

    header("Location: student_status.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Personal Details</title>
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
    <h2>Edit Personal Details</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($studentDetails['studentFirstName']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($studentDetails['studentLastName']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="father_name" class="form-label">Father's Name</label>
            <input type="text" class="form-control" name="father_name" value="<?= htmlspecialchars($studentDetails['studentFatherName']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="mother_name" class="form-label">Mother's Name</label>
            <input type="text" class="form-control" name="mother_name" value="<?= htmlspecialchars($studentDetails['studentMotherName']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="dob" class="form-label">Date of Birth</label>
            <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($studentDetails['studentDateOfBirth']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="gender" class="form-label">Gender</label>
            <select class="form-control" name="gender" required>
                <option value="Male" <?= $studentDetails['studentGender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $studentDetails['studentGender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= $studentDetails['studentGender'] === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>
        <div class="mb-3">
        <label for="religion" class="form-label">Religion</label>
        <select name="religion" id="religion" class="form-control" required>
            <option value="Hindu"<?= $studentDetails['studentReligion'] === 'Hindu' ? 'selected' : '' ?>>Hindu</option>
            <option value="Muslim"<?= $studentDetails['studentReligion'] === 'Muslim' ? 'selected' : '' ?>>Muslim</option>
            <option value="Christian"<?= $studentDetails['studentReligion'] === 'Christian' ? 'selected' : '' ?>>Christian</option>
            <option value="Jain"<?= $studentDetails['studentReligion'] === 'Jain' ? 'selected' : '' ?>>Jain</option>
            <option value="Sikh"<?= $studentDetails['studentReligion'] === 'Sikh' ? 'selected' : '' ?>>Sikh</option>
            <option value="Buddhist"<?= $studentDetails['studentReligion'] === 'Buddhist' ? 'selected' : '' ?>>Buddhist</option>
            <option value="Others"<?= $studentDetails['studentReligion'] === 'Others' ? 'selected' : '' ?>>Others</option>
        </select>
    </div>
        <div class="mb-3">
            <label for="community" class="form-label">Community</label>
            <select name="caste" id="caste" class="form-control" required>
                <option value="BC"<?= $studentDetails['studentCaste'] === 'BC' ? 'selected' : '' ?>>BC - Backward Class</option>
                <option value="MBC"<?= $studentDetails['studentCaste'] === 'MBC' ? 'selected' : '' ?>>MBC - Most Backward Class</option>
                <option value="SC"<?= $studentDetails['studentCaste'] === 'SC' ? 'selected' : '' ?>>SC - Scheduled Caste</option>
                <option value="ST"<?= $studentDetails['studentCaste'] === 'ST' ? 'selected' : '' ?>>ST - Scheduled Tribe</option>
                <option value="OC"<?= $studentDetails['studentCaste'] === 'OC' ? 'selected' : '' ?>>OC - Open Category</option>
                <option value="BC(M)"<?= $studentDetails['studentCaste'] === 'BC(M)' ? 'selected' : '' ?>>BC(M) - Backward Class (Muslim)</option>
                <option value="MBC(M)"<?= $studentDetails['studentCaste'] === 'MBC(M)' ? 'selected' : '' ?>>MBC(M) - Most Backward Class (Muslim)</option>
                <option value="BC(Christian)"<?= $studentDetails['studentCaste'] === 'BC(Christian)' ? 'selected' : '' ?>>BC(Christian) - Backward Class (Christian)</option>
                <option value="MBC(Christian)"<?= $studentDetails['studentCaste'] === 'MBC(Christian)' ? 'selected' : '' ?>>MBC(Christian) - Most Backward Class (Christian)</option>
                <option value="Others"<?= $studentDetails['studentCaste'] === 'Others' ? 'selected' : '' ?>>Others - Any other types</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="extraCaste" class="form-label">caste</label>
            <input type="text" class="form-control" name="otherCaste" id="otherCaste" value="<?= htmlspecialchars($studentDetails['studentCaste_2']) ?>" required>
        </div>
        <div class="mb-3">
        <label for="motherTongue" class="form-label">Mother Tongue</label>
        <select name="motherTongue" id="motherTongue" class="form-control" required>
            <option value="Tamil"<?= $studentDetails['studentMotherTongue'] === 'Tamil' ? 'selected' : '' ?>>Tamil</option>
            <option value="Telugu"<?= $studentDetails['studentMotherTongue'] === 'Telugu' ? 'selected' : '' ?>>Telugu</option>
            <option value="Kannada"<?= $studentDetails['studentMotherTongue'] === 'Kannada' ? 'selected' : '' ?>>Kannada</option>
            <option value="Sowrashtra"<?= $studentDetails['studentMotherTongue'] === 'Sowrashtra' ? 'selected' : '' ?>>Sowrashtra</option>
            <option value="Malayalam"<?= $studentDetails['studentMotherTongue'] === 'Malayalam' ? 'selected' : '' ?>>Malayalam</option>
            <option value="Hindi"<?= $studentDetails['studentMotherTongue'] === 'Hindi' ? 'selected' : '' ?>>Hindi</option>
            <option value="Urdu"<?= $studentDetails['studentMotherTongue'] === 'Urdu' ? 'selected' : '' ?>>Urdu</option>
            <option value="Others"<?= $studentDetails['studentMotherTongue'] === 'Others' ? 'selected' : '' ?>>Others</option>
        </select>
    </div>
        <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="phone_number" value="<?= htmlspecialchars($studentDetails['studentPhoneNumber']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
</body>
</html>
