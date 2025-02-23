<?php
include '../include/db.php';
session_start();

// Check admin authentication
if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$success = false;

// Function to generate a unique password
function generateUniquePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Function to generate a unique username
function generateUniqueUsername($firstName, $lastName = '') {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . ($lastName ? $lastName[0] : '')));
    $username = $base . rand(100, 999);
    $stmt = $conn->prepare("SELECT userId FROM users WHERE userName = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($result->num_rows > 0) {
        $username = $base . rand(100, 999);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    $stmt->close();
    return $username;
}

// Handle Excel file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        $conn->begin_transaction();

        // Check if file is uploaded
        if ($_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName = $_FILES['excel_file']['tmp_name'];
            $fileName = $_FILES['excel_file']['name'];

            // Manually include PhpSpreadsheet with multiple path checks
            $phpSpreadsheetPaths = [
                '../PhpSpreadsheet/src/Bootstrap.php', // Default path
                './PhpSpreadsheet/src/Bootstrap.php',  // Try current directory
                '/PhpSpreadsheet/src/Bootstrap.php'    // Try root directory (less likely)
            ];
            $phpSpreadsheetFound = false;

            foreach ($phpSpreadsheetPaths as $path) {
                if (file_exists($path)) {
                    require $path;
                    $phpSpreadsheetFound = true;
                    break;
                }
            }

            if (!$phpSpreadsheetFound) {
                throw new Exception("PhpSpreadsheet library not found. Please download and place it in the 'PhpSpreadsheet' folder in your project directory.");
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row (first row)
            array_shift($rows);

            $requiredStudentFields = ['studentFirstName', 'studentDateOfBirth', 'studentGender', 'studentCaste', 'studentReligion', 'studentMotherTongue', 'studentPhoneNumber', 'studentAadharNumber', 'studentAddress', 'studentCity', 'studentState', 'studentPinCode', 'email'];
            $requiredAcademicFields = ['school_name', 'yearOfPassing', 'tamilMarks', 'englishMarks', 'mathsMarks', 'scienceMarks', 'socialScienceMarks'];

            foreach ($rows as $row) {
                if (empty($row)) continue; // Skip empty rows

                // Map Excel headers to database columns (case-insensitive)
                $data = [];
                $headers = array_map('strtolower', array_shift($rows)); // Get headers from the first row of data
                $rowData = array_combine($headers, array_map('strval', $row)); // Combine headers with row data

                // Check for required fields
                $missingFields = [];
                foreach ($requiredStudentFields as $field) {
                    if (!isset($rowData[strtolower($field)]) || empty(trim($rowData[strtolower($field)]))) {
                        $missingFields[] = $field;
                    }
                }
                foreach ($requiredAcademicFields as $field) {
                    if (!isset($rowData[strtolower($field)]) || empty(trim($rowData[strtolower($field)]))) {
                        $missingFields[] = $field;
                    }
                }
                if (!empty($missingFields)) {
                    throw new Exception("Missing required fields in row: " . implode(", ", $missingFields));
                }

                // Extract and sanitize data
                $firstName = filter_var($rowData['studentfirstname'] ?? '', FILTER_SANITIZE_STRING);
                $lastName = filter_var($rowData['studentlastname'] ?? '', FILTER_SANITIZE_STRING);
                $fatherName = filter_var($rowData['studentfathername'] ?? '', FILTER_SANITIZE_STRING);
                $motherName = filter_var($rowData['studentmothername'] ?? '', FILTER_SANITIZE_STRING);
                $dob = filter_var($rowData['studentdateofbirth'] ?? '', FILTER_SANITIZE_STRING);
                $gender = filter_var($rowData['studentgender'] ?? '', FILTER_SANITIZE_STRING);
                $caste = filter_var($rowData['studentcaste'] ?? '', FILTER_SANITIZE_STRING);
                $otherCaste = filter_var($rowData['studentcaste_2'] ?? '', FILTER_SANITIZE_STRING);
                $religion = filter_var($rowData['studentreligion'] ?? '', FILTER_SANITIZE_STRING);
                $motherTongue = filter_var($rowData['studentmothertongue'] ?? '', FILTER_SANITIZE_STRING);
                $phone = filter_var($rowData['studentphonenumber'] ?? '', FILTER_SANITIZE_STRING);
                $aadharNumber = filter_var($rowData['studentaadharnumber'] ?? '', FILTER_SANITIZE_NUMBER_INT);
                $address = filter_var($rowData['studentaddress'] ?? '', FILTER_SANITIZE_STRING);
                $city = filter_var($rowData['studentcity'] ?? '', FILTER_SANITIZE_STRING);
                $state = filter_var($rowData['studentstate'] ?? '', FILTER_SANITIZE_STRING);
                $pinCode = filter_var($rowData['studentpincode'] ?? '', FILTER_SANITIZE_NUMBER_INT);
                $schoolName = filter_var($rowData['school_name'] ?? '', FILTER_SANITIZE_STRING);
                $yearOfPassing = filter_var($rowData['yearofpassing'] ?? '', FILTER_SANITIZE_STRING);
                $tamil = filter_var($rowData['tamilmarks'] ?? 0, FILTER_VALIDATE_INT);
                $english = filter_var($rowData['englishmarks'] ?? 0, FILTER_VALIDATE_INT);
                $maths = filter_var($rowData['mathsmarks'] ?? 0, FILTER_VALIDATE_INT);
                $science = filter_var($rowData['sciencemarks'] ?? 0, FILTER_VALIDATE_INT);
                $socialScience = filter_var($rowData['socialsciencemarks'] ?? 0, FILTER_VALIDATE_INT);
                $otherLanguage = filter_var($rowData['otherlanguagemarks'] ?? 0, FILTER_VALIDATE_INT);
                $emisNumber = filter_var($rowData['emisnumber'] ?? '', FILTER_SANITIZE_STRING);
                $email = filter_var($rowData['email'] ?? '', FILTER_SANITIZE_EMAIL);

                // Validate data
                if (!preg_match('/^\d{12}$/', $aadharNumber)) {
                    throw new Exception("Invalid Aadhar number for student $firstName $lastName: must be 12 digits.");
                }
                if (!preg_match('/^\d{10}$/', $phone)) {
                    throw new Exception("Invalid phone number for student $firstName $lastName: must be 10 digits.");
                }
                if (!preg_match('/^\d{6}$/', $pinCode)) {
                    throw new Exception("Invalid PIN code for student $firstName $lastName: must be 6 digits.");
                }
                if (!preg_match('/^[A-Za-z]{3}-\d{4}$/', $yearOfPassing)) {
                    throw new Exception("Invalid year of passing for student $firstName $lastName: must be in format like 'Apr-2023'");
                }
                if ($tamil < 35 || $tamil > 100 || $english < 35 || $english > 100 || 
                    $maths < 35 || $maths > 100 || $science < 35 || $science > 100 || 
                    $socialScience < 35 || $socialScience > 100 || 
                    ($otherLanguage > 0 && ($otherLanguage < 35 || $otherLanguage > 100))) {
                    throw new Exception("Invalid marks for student $firstName $lastName: all subjects must be between 35 and 100.");
                }

                $currentDate = new DateTime();
                $birthDate = new DateTime($dob);
                $age = $currentDate->diff($birthDate)->y;
                if ($age < 15) {
                    throw new Exception("Student $firstName $lastName must be at least 15 years old.");
                }

                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid or missing email for student $firstName $lastName.");
                }

                // Check if email already exists
                $checkStmt = $conn->prepare("SELECT userId FROM users WHERE userEmail = ?");
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult->num_rows > 0) {
                    throw new Exception("Email $email already exists for another user.");
                }
                $checkStmt->close();

                // Generate unique username and password
                $username = generateUniqueUsername($firstName, $lastName);
                $password = generateUniquePassword();
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Create user account
                $userStmt = $conn->prepare("INSERT INTO users (userName, userEmail, userPassword, userRole, createdAt) VALUES (?, ?, ?, ?, NOW())");
                $userStmt->bind_param("ssss", $username, $email, $hashedPassword, $userRole);
                $userStmt->execute();
                $userId = $userStmt->insert_id;
                $userStmt->close();

                // Insert student details
                $studentStmt = $conn->prepare("INSERT INTO studentdetails 
                    (studentUserId, studentFirstName, studentLastName, studentFatherName, studentMotherName, 
                    studentDateOfBirth, studentGender, studentCaste, studentCaste_2, studentReligion, 
                    studentMotherTongue, studentPhoneNumber, studentAadharNumber, studentAddress, studentCity, studentState, studentPinCode) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $studentStmt->bind_param("issssssssssssssss", 
                    $userId, $firstName, $lastName, $fatherName, $motherName, $dob, $gender, $caste, $otherCaste, 
                    $religion, $motherTongue, $phone, $aadharNumber, $address, $city, $state, $pinCode
                );
                $studentStmt->execute();
                $studentStmt->close();

                // Insert academic details
                $totalMarks = $tamil + $english + $maths + $science + $socialScience + $otherLanguage;
                $academicStmt = $conn->prepare("INSERT INTO academic 
                    (academicUserId, school_name, yearOfPassing, tamilMarks, englishMarks, 
                    mathsMarks, scienceMarks, socialScienceMarks, otherLanguageMarks, emisNumber, totalMarks) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $academicStmt->bind_param("isiiiiiiisi", 
                    $userId, $schoolName, $yearOfPassing, $tamil, $english, $maths, $science, $socialScience, $otherLanguage, $emisNumber, $totalMarks
                );
                $academicStmt->execute();
                $academicStmt->close();

                // Send email with credentials
                sendCredentialsEmail($email, $firstName, $lastName, $username, $password);
            }

            $conn->commit();
            $message = "Students imported successfully! Login credentials sent to their emails.";
            $success = true;
        } else {
            throw new Exception("Error uploading Excel file: " . $_FILES['excel_file']['error']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error importing students: " . $e->getMessage();
    }
}

// Function to send professional email with credentials
function sendCredentialsEmail($recipientEmail, $firstName, $lastName, $username, $password) {
    // Include PHPMailer manually or via autoloader (check if Composer is available)
    if (file_exists('../vendor/autoload.php')) {
        require '../vendor/autoload.php';
    } else {
        require '../PHPMailer/src/PHPMailer.php'; // Adjust path if necessary
        require '../PHPMailer/src/Exception.php'; // Adjust path if necessary
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sathancreator@gmail.com'; // Replace with your email
        $mail->Password = 'tatqezizskzqjgqg'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('sathancreator@gmail.com', 'Admissions Office');
        $mail->addAddress($recipientEmail);

        $emailBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; background: #f8f9fa; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto;'>
            <div style='text-align: center; padding: 20px; background: linear-gradient(to right, #007bff, #66b2ff); color: white; border-radius: 8px 8px 0 0;'>
                <h2>Welcome to NPTC - Admissions Office</h2>
            </div>
            <div style='padding: 20px; background: white; border-radius: 0 0 8px 8px;'>
                <h3 style='color: #2c3e50;'>Dear {$firstName} " . ($lastName ? $lastName : '') . ",</h3>
                <p>We are pleased to inform you that your student account has been created successfully.</p>
                <p>Please use the following credentials to log in to your account:</p>
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr style='background: #f8f9fa;'>
                        <td style='padding: 10px; font-weight: bold; border: 1px solid #dee2e6;'>Username:</td>
                        <td style='padding: 10px; border: 1px solid #dee2e6;'>{$username}</td>
                    </tr>
                    <tr style='background: #f8f9fa;'>
                        <td style='padding: 10px; font-weight: bold; border: 1px solid #dee2e6;'>Password:</td>
                        <td style='padding: 10px; border: 1px solid #dee2e6;'>{$password}</td>
                    </tr>
                </table>
                <p><strong>Important:</strong> For security reasons, we recommend changing your password after your first login. You can use the <a href='../auth/forgot_password.php' style='color: #007bff; text-decoration: underline;'>Forget Password</a> feature to reset or change your password later.</p>
                <p>If you have any questions or need assistance, please contact our support team at <a href='mailto:sathancreator@gmail.com' style='color: #007bff; text-decoration: underline;'>sathancreator@gmail.com</a>.</p>
                <p>Best regards,<br>Admissions Office<br>NPTC</p>
            </div>
            <div style='text-align: center; font-size: 12px; color: #6c757d; margin-top: 20px;'>
                <p>© " . date('Y') . " NPTC - ADMISSION OFFICE. All rights reserved.</p>
            </div>
        </div>";

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to NPTC - Your Student Account Credentials';
        $mail->Body = $emailBody;

        $mail->send();
        error_log("Credentials email sent to {$recipientEmail}");
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        throw new Exception("Failed to send email: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import from Excel - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* General Reset */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
            border-right: 1px solid #34495e;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            padding-top: 70px; /* Align sidebar content with header */
        }

        .sidebar a {
            color: #bdc3c7;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            border-bottom: 1px solid #34495e;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #34495e;
            color: #1abc9c;
        }

        /* Content Area */
        .content {
            margin-left: 250px;
            padding: 30px;
            margin-top: 70px; /* Adjust for header space */
            background-color: #f4f6f9;
            min-height: calc(100vh - 70px);
        }

        /* Header Styles */
        .header {
            background-color: #ffffff;
            border-bottom: 1px solid #ddd;
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header .title {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 700;
        }

        .header .logout-btn {
            color: #ffffff;
            background-color: #e74c3c;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .header .logout-btn:hover {
            background-color: #c0392b;
        }

        /* Buttons */
        .btn-primary {
            background-color: #3498db;
            border: none;
            font-weight: 600;
            padding: 10px 15px;
            border-radius: 5px;
            color: #ffffff;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn {
            font-size: 0.95rem;
            padding: 10px 15px;
            border-radius: 5px;
        }

        /* Form Styling */
        .form-container {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 30px;
        }

        .section-title {
            color: #2c3e50;
            border-left: 4px solid #0d6efd;
            padding-left: 15px;
            margin: 25px 0;
        }

        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: border-color 0.3s;
        }

        .file-upload:hover {
            border-color: #0d6efd;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                height: auto;
                width: 100%;
                padding-top: 0;
            }

            .content {
                margin-left: 0;
                margin-top: 100px;
            }

            .btn {
                font-size: 0.8rem;
                padding: 8px 12px;
            }

            .header {
                padding: 10px 15px;
            }

            .form-container {
                padding: 20px;
            }
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 10px 12px;
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            outline: none;
        }
    </style>
</head>
<body>

<!-- Sidebar for larger screens -->
<nav class="sidebar d-none d-md-block">
    <h4 class="text-center mt-3">Admin Panel</h4>
    <a href="dashboard.php">Dashboard</a>
    <a href="admin_student_entry.php">Student Entry</a>
    <a href="import_excel.php">Import from Excel</a>
    <a href="form_a.php">Form A</a>
    <a href="form_b.php">Form B</a>
    <a href="form_c.php">Form C</a>
    <a href="form_d.php">Form D</a>
    <a href="form_e.php">Form E</a>
</nav>

<!-- Mobile menu toggle button -->
<div class="mobile-menu-btn d-md-none p-2 bg-dark text-white text-center">
    <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu" aria-expanded="false" aria-controls="mobileMenu">
        Menu
    </button>
</div>

<!-- Mobile menu -->
<div class="collapse d-md-none" id="mobileMenu">
    <nav class="bg-dark">
        <a href="dashboard.php" class="text-white">Dashboard</a>
        <a href="admin_student_entry.php" class="text-white">Student Entry</a>
        <a href="import_excel.php" class="text-white">Import from Excel</a>
        <a href="form_a.php" class="text-white">Form A</a>
        <a href="form_b.php" class="text-white">Form B</a>
        <a href="form_c.php" class="text-white">Form C</a>
        <a href="form_d.php" class="text-white">Form D</a>
        <a href="form_e.php" class="text-white">Form E</a>
    </nav>
</div>

<!-- Main content -->
<div class="content">
    <div class="container mt-4">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="form-container">
                        <h2 class="mb-4"><i class="fas fa-file-import me-2"></i>Import from Excel</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $success ? 'success' : 'danger' ?>"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="credential-section">
                                <h4 class="section-title mb-4"><i class="fas fa-upload me-2"></i>Upload Excel File</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Excel File (.xlsx)</label>
                                        <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                                        <small class="text-muted">Download the sample Excel template <a href="students_sample.xlsx" download>here</a>.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-upload me-2"></i>Import Students</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>