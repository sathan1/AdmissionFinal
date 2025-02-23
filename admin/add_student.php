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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // User credentials (only email, generate username and password automatically)
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING) ?? ''; // Default to empty string if not set
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . ($lastName ? $lastName[0] : ''))) . rand(100, 999); // Generate username
        $password = generateUniquePassword(); // Generate unique password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $userRole = 'student'; // Store in a variable for bind_param

        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT userId FROM users WHERE userEmail = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            throw new Exception("Email already exists.");
        }
        $checkStmt->close();

        // Create user
        $stmt = $conn->prepare("INSERT INTO users 
            (userName, userEmail, userPassword, userRole, createdAt) 
            VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $userRole); // Use variable $userRole
        $stmt->execute();
        $studentUserId = $stmt->insert_id;

        // Student details (using filter_input for sanitization, matching forms.php style)
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING) ?? '';
        $fatherName = filter_input(INPUT_POST, 'fatherName', FILTER_SANITIZE_STRING);
        $motherName = filter_input(INPUT_POST, 'motherName', FILTER_SANITIZE_STRING);
        $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING); // Date format will be validated later
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $caste = filter_input(INPUT_POST, 'caste', FILTER_SANITIZE_STRING);
        $otherCaste = filter_input(INPUT_POST, 'otherCaste', FILTER_SANITIZE_STRING) ?? '';
        $religion = filter_input(INPUT_POST, 'religion', FILTER_SANITIZE_STRING);
        $motherTongue = filter_input(INPUT_POST, 'motherTongue', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $aadharNumber = filter_input(INPUT_POST, 'aadharNumber', FILTER_SANITIZE_NUMBER_INT);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
        $pinCode = filter_input(INPUT_POST, 'pinCode', FILTER_SANITIZE_NUMBER_INT);

        // Validate data based on the image (e.g., phone and Aadhar must be numeric, PIN code 6 digits)
        if (empty($caste)) {
            throw new Exception("Caste is required.");
        } elseif ($caste === 'Others' && empty($otherCaste)) {
            throw new Exception("Please specify the caste under 'Others'.");
        } elseif (!preg_match('/^\d{12}$/', $aadharNumber)) {
            throw new Exception("Aadhar number must be 12 digits.");
        } elseif (!preg_match('/^\d{10}$/', $phone)) {
            throw new Exception("Phone number must be 10 digits.");
        } elseif (empty($city) || empty($state)) {
            throw new Exception("City and State must be selected manually.");
        } elseif (!preg_match('/^\d{6}$/', $pinCode)) {
            throw new Exception("PIN code must be 6 digits.");
        } else {
            // Validate Date of Birth (minimum 15 years old, matching forms.php)
            $currentDate = new DateTime();
            $birthDate = new DateTime($dob);
            $age = $currentDate->diff($birthDate)->y;
            if ($age < 15) {
                throw new Exception("You must be at least 15 years old.");
            }

            $stmt = $conn->prepare("INSERT INTO studentdetails 
                (studentUserId, studentFirstName, studentLastName, studentFatherName, studentMotherName, 
                studentDateOfBirth, studentGender, studentCaste, studentCaste_2, studentReligion, 
                studentMotherTongue, studentPhoneNumber, studentAadharNumber, studentAddress, studentCity, studentState, studentPinCode) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssssssss", 
                $studentUserId, $firstName, $lastName, $fatherName, $motherName, $dob, $gender, $caste, $otherCaste, 
                $religion, $motherTongue, $phone, $aadharNumber, $address, $city, $state, $pinCode
            );
            $stmt->execute();
        }

        // Academic details (using filter_input for sanitization, matching forms.php style)
        $schoolName = filter_input(INPUT_POST, 'school_name', FILTER_SANITIZE_STRING);
        $yearOfPassing = filter_input(INPUT_POST, 'yearOfPassing', FILTER_SANITIZE_STRING);
        $tamil = filter_input(INPUT_POST, 'tamil', FILTER_VALIDATE_INT) ?: 0;
        $english = filter_input(INPUT_POST, 'english', FILTER_VALIDATE_INT) ?: 0;
        $maths = filter_input(INPUT_POST, 'maths', FILTER_VALIDATE_INT) ?: 0;
        $science = filter_input(INPUT_POST, 'science', FILTER_VALIDATE_INT) ?: 0;
        $socialScience = filter_input(INPUT_POST, 'socialScience', FILTER_VALIDATE_INT) ?: 0;
        $otherLanguage = filter_input(INPUT_POST, 'otherLanguage', FILTER_VALIDATE_INT) ?: 0;
        $emisNumber = filter_input(INPUT_POST, 'emisNumber', FILTER_SANITIZE_STRING) ?? '';

        // Validate academic data (matching forms.php)
        if (!preg_match('/^[A-Za-z]{3}-\d{4}$/', $yearOfPassing)) {
            throw new Exception("Year of passing must be in format like 'Apr-2023'");
        } elseif ($tamil < 35 || $tamil > 100 || $english < 35 || $english > 100 || 
                  $maths < 35 || $maths > 100 || $science < 35 || $science > 100 || 
                  $socialScience < 35 || $socialScience > 100 || 
                  ($otherLanguage > 0 && ($otherLanguage < 35 || $otherLanguage > 100))) {
            throw new Exception("All subjects (Tamil, English, Maths, Science, Social Science) must have at least 35 marks and not exceed 100. Other Language, if provided, must be between 35 and 100 marks.");
        }

        $totalMarks = $tamil + $english + $maths + $science + $socialScience + $otherLanguage;
        $stmt = $conn->prepare("INSERT INTO academic 
            (academicUserId, school_name, yearOfPassing, tamilMarks, englishMarks, 
            mathsMarks, scienceMarks, socialScienceMarks, otherLanguageMarks, emisNumber, totalMarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiiiiiisi", 
            $studentUserId, $schoolName, $yearOfPassing, $tamil, $english, $maths, $science, $socialScience, $otherLanguage, $emisNumber, $totalMarks
        );
        $stmt->execute();

        // Preferences (matching forms.php style)
        $department1 = filter_input(INPUT_POST, 'department1', FILTER_SANITIZE_STRING) ?? null;
        $department2 = filter_input(INPUT_POST, 'department2', FILTER_SANITIZE_STRING) ?? null;

        if (!$department1 || !$department2 || $department1 === $department2) {
            throw new Exception("Invalid department preferences. Both preferences must be different.");
        }

        $stmt = $conn->prepare("INSERT INTO preference 
            (preferenceUserId, preferenceOrder, preferenceDepartment, preferenceStatus) 
            VALUES (?, ?, ?, 'pending')");
        $order = 1;
        $stmt->bind_param("iis", $studentUserId, $order, $department1);
        $stmt->execute();

        $order = 2;
        $stmt->bind_param("iis", $studentUserId, $order, $department2);
        $stmt->execute();

        // File upload handling (with smaller resolution and size for images, matching forms.php)
        $uploadDir = "../documents/";
        $documents = ['aadhaar', 'marksheet', 'photo', 'birthCertificate', 'migrationCertificate', 'characterCertificate'];
        $requiredDocs = ['aadhaar', 'marksheet', 'photo', 'birthCertificate'];
        $uploadedFiles = [];

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Failed to create directory: $uploadDir");
            }
        }

        foreach ($documents as $docType) {
            if (isset($_FILES[$docType]) && $_FILES[$docType]['error'] === UPLOAD_ERR_OK) {
                $fileSize = $_FILES[$docType]['size'];
                $fileName = $_FILES[$docType]['name'];
                $fileTmpName = $_FILES[$docType]['tmp_name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception("Invalid file type for $docType. Allowed types: JPG, JPEG, PNG, PDF.");
                }

                // Different size limits for images and PDFs
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                    if ($fileSize > 50000) { // 50 KB for images
                        throw new Exception("File size for $docType exceeds 50KB. Please compress or resize the image.");
                    }
                    // Resize and compress image
                    $image = imagecreatefromstring(file_get_contents($fileTmpName));
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $maxDimension = 400; // Resize to max 400x400 pixels
                    $newWidth = $width;
                    $newHeight = $height;

                    if ($width > $height && $width > $maxDimension) {
                        $newWidth = $maxDimension;
                        $newHeight = ($height * $maxDimension) / $width;
                    } elseif ($height > $maxDimension) {
                        $newHeight = $maxDimension;
                        $newWidth = ($width * $maxDimension) / $height;
                    }

                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagejpeg($newImage, $fileTmpName, 75); // Compress with 75% quality

                    // Update file size after compression
                    $fileSize = filesize($fileTmpName);
                    if ($fileSize > 50000) {
                        throw new Exception("Compressed file size for $docType still exceeds 50KB. Please use a smaller image.");
                    }
                } elseif ($fileExtension === 'pdf') {
                    if ($fileSize > 100000) { // 100 KB for PDFs
                        throw new Exception("File size for $docType exceeds 100KB. Please compress the PDF.");
                    }
                }

                $newFileName = uniqid($docType . '_', true) . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpName, $targetPath)) {
                    $uploadedFiles[$docType] = $newFileName;

                    $insertStmt = $conn->prepare("INSERT INTO document 
                        (documentUserId, documentType, documentName) 
                        VALUES (?, ?, ?)");
                    $insertStmt->bind_param("iss", $studentUserId, $docType, $newFileName);
                    $insertStmt->execute();
                    $insertStmt->close();
                } else {
                    throw new Exception("Error uploading $docType.");
                }
            } elseif (in_array($docType, $requiredDocs)) {
                throw new Exception(ucfirst($docType) . " file is missing.");
            }
        }

        if (count(array_intersect($requiredDocs, array_keys($uploadedFiles))) !== count($requiredDocs)) {
            throw new Exception("Please upload all required documents (Aadhaar, Marksheet, Photo, Birth Certificate) to proceed.");
        }

        $conn->commit();

        // Send professional email with credentials
        sendCredentialsEmail($email, $firstName, $lastName, $username, $password);

        $message = "Student record created successfully! Login credentials sent to " . htmlspecialchars($email);
        $success = true;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error creating student record: " . $e->getMessage();
    }
}

// Function to send professional email with credentials
function sendCredentialsEmail($recipientEmail, $firstName, $lastName, $username, $password) {
    // Include PHPMailer manually or via autoloader (check if Composer is available)
    if (file_exists('../vendor/autoload.php')) {
        require '../vendor/autoload.php';
    } else {
        // Manual inclusion for shared hosting without Composer
        require '../PHPMailer/src/PHPMailer.php'; // Adjust path if necessary
        require '../PHPMailer/src/Exception.php'; // Adjust path if necessary
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sathancreator@gmail.com'; // Replace with your email
        $mail->Password = 'tatqezizskzqjgqg'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('sathancreator@gmail.com', 'Admissions Office');
        $mail->addAddress($recipientEmail);

        // Email Body with professional design
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

        // Email settings
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
    <title>Admin Student Entry - College Portal</title>
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

        /* Table Styles */
        .table {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            background-color: #2980b9;
            color: #ffffff;
            text-align: center;
            font-weight: 700;
            padding: 15px;
            border-bottom: 2px solid #1c5980;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table tbody tr:hover {
            background-color: #ecf0f1;
        }

        .table tbody td {
            vertical-align: middle;
            text-align: center;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 0.4em 0.8em;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            border-radius: 0.25rem;
        }

        .bg-success {
            background-color: #2ecc71 !important;
            color: white !important;
        }

        .bg-danger {
            background-color: #e74c3c !important;
            color: white !important;
        }

        .bg-warning {
            background-color: #f1c40f !important;
            color: black !important;
        }

        .bg-secondary {
            background-color: #7f8c8d !important;
            color: white !important;
        }

        /* Form Styling */
        .form-container {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 30px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
            background: transparent;
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

        .preview-image {
            max-width: 150px; /* Reduced for smaller, nicer view */
            margin-top: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

            .table {
                font-size: 0.9rem;
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

        /* Additional Styles for Form Inputs */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 10px 12px;
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            outline: none;
        }

        .is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
        }
    </style>
</head>
<body>

<!-- Sidebar for larger screens -->
<nav class="sidebar d-none d-md-block">
    <h4 class="text-center mt-3">Admin Panel</h4>
    <a href="dashboard.php">Dashboard</a>
    <a href="admin_student_entry.php">Student Entry</a>
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
                        <h2 class="mb-4"><i class="fas fa-user-plus me-2"></i>Create New Student Record</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $success ? 'success' : 'danger' ?>"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- New User Credentials Section (Email Only) -->
                            <div class="credential-section">
                                <h4 class="section-title mb-4"><i class="fas fa-envelope me-2"></i>Contact Information</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <ul class="nav nav-tabs mb-4" id="formTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#step1">Step 1: Personal Details</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#step2">Step 2: Banking Details (Optional)</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#step3">Step 3: Academic Details</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#step4">Step 4: Department Preferences</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#step5">Step 5: Document Upload</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Step 1: Personal Details -->
                                <div class="tab-pane fade show active" id="step1">
                                    <h4 class="section-title">Personal Information</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="firstName" class="form-label">First Name</label>
                                            <input type="text" name="firstName" id="firstName" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lastName" class="form-label">Last Name</label>
                                            <input type="text" name="lastName" id="lastName" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fatherName" class="form-label">Father's Name</label>
                                            <input type="text" name="fatherName" id="fatherName" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="motherName" class="form-label">Mother's Name</label>
                                            <input type="text" name="motherName" id="motherName" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="dob" class="form-label">Date of Birth</label>
                                            <input type="date" name="dob" id="dob" class="form-control" required min="<?php echo date('Y-m-d', strtotime('-100 years')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="gender" class="form-label">Gender</label>
                                            <select name="gender" id="gender" class="form-control" required>
                                                <option value="">Select gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="caste" class="form-label">Community</label>
                                            <select name="caste" id="caste" class="form-control" required>
                                                <option value="">Select Community</option>
                                                <option value="BC">BC - Backward Class</option>
                                                <option value="MBC">MBC - Most Backward Class</option>
                                                <option value="SC">SC - Scheduled Caste</option>
                                                <option value="ST">ST - Scheduled Tribe</option>
                                                <option value="OC">OC - Open Category</option>
                                                <option value="BC(M)">BC(M) - Backward Class (Muslim)</option>
                                                <option value="MBC(M)">MBC(M) - Most Backward Class (Muslim)</option>
                                                <option value="BC(Christian)">BC(Christian) - Backward Class (Christian)</option>
                                                <option value="MBC(Christian)">MBC(Christian) - Most Backward Class (Christian)</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6" id="otherCasteInput">
                                            <label for="otherCaste" class="form-label">Caste</label>
                                            <input type="text" name="otherCaste" id="otherCaste" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="religion" class="form-label">Religion</label>
                                            <select name="religion" id="religion" class="form-control" required>
                                                <option value="">Select Religion</option>
                                                <option value="Hindu">Hindu</option>
                                                <option value="Muslim">Muslim</option>
                                                <option value="Christian">Christian</option>
                                                <option value="Jain">Jain</option>
                                                <option value="Sikh">Sikh</option>
                                                <option value="Buddhist">Buddhist</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="motherTongue" class="form-label">Mother Tongue</label>
                                            <select name="motherTongue" id="motherTongue" class="form-control" required>
                                                <option value="">Select Mother Tongue</option>
                                                <option value="Tamil">Tamil</option>
                                                <option value="Telugu">Telugu</option>
                                                <option value="Kannada">Kannada</option>
                                                <option value="Sowrashtra">Sowrashtra</option>
                                                <option value="Malayalam">Malayalam</option>
                                                <option value="Hindi">Hindi</option>
                                                <option value="Urdu">Urdu</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="text" name="phone" id="phone" class="form-control" required pattern="\d{10}" title="Phone number must be 10 digits">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="aadharNumber" class="form-label">Aadhar Number</label>
                                            <input type="text" name="aadharNumber" id="aadharNumber" class="form-control" required pattern="\d{12}" title="12 digits Aadhar number">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="pinCode" class="form-label">PIN Code</label>
                                            <input type="text" name="pinCode" id="pinCode" class="form-control" required pattern="\d{6}" title="6 digits PIN code">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="address" class="form-label">Address (Door No / Street / Village)</label>
                                            <textarea name="address" id="address" class="form-control" required placeholder="Door No / Street / Village"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="state" class="form-label">State</label>
                                            <input type="text" name="state" id="state" class="form-control" list="stateSuggestions" required onchange="fetchDistricts(this.value)" onkeyup="fetchDistricts(this.value)">
                                            <datalist id="stateSuggestions">
                                                <?php
                                                $states = [
                                                    "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat", 
                                                    "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", 
                                                    "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", 
                                                    "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttar Pradesh", 
                                                    "Uttarakhand", "West Bengal", "Andaman and Nicobar Islands", "Chandigarh", "Dadra and Nagar Haveli and Daman and Diu", 
                                                    "Delhi", "Jammu and Kashmir", "Ladakh", "Lakshadweep", "Puducherry"
                                                ];
                                                foreach ($states as $state) {
                                                    echo "<option value='$state'>";
                                                }
                                                ?>
                                            </datalist>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="city" class="form-label">City (District)</label>
                                            <input type="text" name="city" id="city" class="form-control" list="citySuggestions" required>
                                            <datalist id="citySuggestions">
                                                <!-- Populated dynamically via JavaScript -->
                                            </datalist>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2: Banking Details (Optional) -->
                                <div class="tab-pane fade" id="step2">
                                    <h4 class="section-title">Banking Details (Optional)</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="accountNumber" class="form-label">Account Number</label>
                                            <input type="text" name="accountNumber" id="accountNumber" class="form-control" pattern="\d{12}" title="12 digits account number">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bankName" class="form-label">Bank Name</label>
                                            <input type="text" name="bankName" id="bankName" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="branch" class="form-label">Branch</label>
                                            <input type="text" name="branch" id="branch" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="ifsc" class="form-label">IFSC Code</label>
                                            <input type="text" name="ifsc" id="ifsc" class="form-control" pattern="[A-Z]{4}[0-9]{7}[A-Z]" title="IFSC code format: XXXX0000000X">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="panNumber" class="form-label">PAN Number</label>
                                            <input type="text" name="panNumber" id="panNumber" class="form-control" pattern="[A-Z]{5}[0-9]{4}[A-Z]" title="PAN format: AAAAA0000A">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="drivingLicenseNumber" class="form-label">Driving License Number</label>
                                            <input type="text" name="drivingLicenseNumber" id="drivingLicenseNumber" class="form-control" pattern="^[A-Z]{2}\d{13}$" title="Use format like 'TN1234567890123' (2 letters, 13 digits)">
                                        </div>
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                            <a href="admin_student_entry.php?form=step3" class="btn btn-secondary" onclick="skipBanking(); return false;">Skip</a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3: Academic Details -->
                                <div class="tab-pane fade" id="step3">
                                    <h4 class="section-title">Academic Details</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="school_name" class="form-label">School Name</label>
                                            <input type="text" name="school_name" id="school_name" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="yearOfPassing" class="form-label">Year of Passing</label>
                                            <input type="text" name="yearOfPassing" id="yearOfPassing" class="form-control" required pattern="^[A-Za-z]{3}-\d{4}$" title="Must be in format like 'Apr-2023'">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emisNumber" class="form-label">EMIS Number</label>
                                            <input type="text" name="emisNumber" id="emisNumber" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tamil" class="form-label">Tamil Marks</label>
                                            <input type="number" name="tamil" id="tamil" class="form-control" required min="35" max="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="english" class="form-label">English Marks</label>
                                            <input type="number" name="english" id="english" class="form-control" required min="35" max="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="maths" class="form-label">Maths Marks</label>
                                            <input type="number" name="maths" id="maths" class="form-control" required min="35" max="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="science" class="form-label">Science Marks</label>
                                            <input type="number" name="science" id="science" class="form-control" required min="35" max="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="socialScience" class="form-label">Social Science Marks</label>
                                            <input type="number" name="socialScience" id="socialScience" class="form-control" required min="35" max="100">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="otherLanguage" class="form-label">Other Language Marks</label>
                                            <input type="number" name="otherLanguage" id="otherLanguage" class="form-control" min="35" max="100">
                                        </div>
                                        <div class="total-marks" id="totalMarksDisplay">
                                            Total Marks: <span id="totalMarks">0</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 4: Department Preferences -->
                                <div class="tab-pane fade" id="step4">
                                    <h4 class="section-title">Department Preferences</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="department1" class="form-label">First Preference</label>
                                            <select name="department1" id="department1" class="form-select" required>
                                                <option value="">Select a Department</option>
                                                <option value="Civil Engineering">Civil Engineering</option>
                                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                                <option value="Electrical and Electronics Engineering">Electrical and Electronics Engineering</option>
                                                <option value="Electrical and Communication Engineering">Electrical and Communication Engineering</option>
                                                <option value="Automobile Engineering">Automobile Engineering</option>
                                                <option value="Textile Technology">Textile Technology</option>
                                                <option value="Computer Technology">Computer Technology</option>
                                                <option value="Printing Technology">Printing Technology</option>
                                                <option value="Mechanical Engineering (R&AC)">Mechanical Engineering (R&AC)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="department2" class="form-label">Second Preference</label>
                                            <select name="department2" id="department2" class="form-select" required>
                                                <option value="">Select a Department</option>
                                                <option value="Civil Engineering">Civil Engineering</option>
                                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                                <option value="Electrical and Electronics Engineering">Electrical and Electronics Engineering</option>
                                                <option value="Electrical and Communication Engineering">Electrical and Communication Engineering</option>
                                                <option value="Automobile Engineering">Automobile Engineering</option>
                                                <option value="Textile Technology">Textile Technology</option>
                                                <option value="Computer Technology">Computer Technology</option>
                                                <option value="Printing Technology">Printing Technology</option>
                                                <option value="Mechanical Engineering (R&AC)">Mechanical Engineering (R&AC)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 5: Document Upload -->
                                <div class="tab-pane fade" id="step5">
                                    <h4 class="section-title">Document Upload</h4>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="file-upload">
                                                <label for="aadhaar" class="form-label">Aadhaar (Required)</label>
                                                <input type="file" name="aadhaar" id="aadhaar" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'aadhaarPreview')">
                                                <img id="aadhaarPreview" class="preview-image" src="#" alt="Aadhaar Preview" style="display:none;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="file-upload">
                                                <label for="marksheet" class="form-label">Marksheet (Required)</label>
                                                <input type="file" name="marksheet" id="marksheet" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'marksheetPreview')">
                                                <img id="marksheetPreview" class="preview-image" src="#" alt="Marksheet Preview" style="display:none;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="file-upload">
                                                <label for="photo" class="form-label">Photo (Required)</label>
                                                <input type="file" name="photo" id="photo" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'photoPreview')">
                                                <img id="photoPreview" class="preview-image" src="#" alt="Photo Preview" style="display:none;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="file-upload">
                                                <label for="birthCertificate" class="form-label">Birth Certificate (Required)</label>
                                                <input type="file" name="birthCertificate" id="birthCertificate" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'birthCertificatePreview')">
                                                <img id="birthCertificatePreview" class="preview-image" src="#" alt="Birth Certificate Preview" style="display:none;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="file-upload">
                                                <label for="migrationCertificate" class="form-label">Migration Certificate (Optional)</label>
                                                <input type="file" name="migrationCertificate" id="migrationCertificate" class="form-control" accept=".jpg, .jpeg, .png, .pdf" onchange="previewFile(this, 'migrationCertificatePreview')">
                                                <img id="migrationCertificatePreview" class="preview-image" src="#" alt="Migration Certificate Preview" style="display:none;">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="file-upload">
                                                <label for="characterCertificate" class="form-label">Character Certificate (Optional)</label>
                                                <input type="file" name="characterCertificate" id="characterCertificate" class="form-control" accept=".jpg, .jpeg, .png, .pdf" onchange="previewFile(this, 'characterCertificatePreview')">
                                                <img id="characterCertificatePreview" class="preview-image" src="#" alt="Character Certificate Preview" style="display:none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save Student Record</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Enhanced form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        let valid = true;
        
        // Check all required fields
        this.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.classList.add('is-invalid');
            }
        });

        // Check department preferences
        const dept1 = document.querySelector('[name="department1"]');
        const dept2 = document.querySelector('[name="department2"]');
        if (dept1.value === dept2.value && dept1.value !== "") {
            valid = false;
            dept2.classList.add('is-invalid');
            alert('Department preferences must be different!');
        }

        // Validate marks (minimum 35, maximum 100)
        const marks = ['tamil', 'english', 'maths', 'science', 'socialScience', 'otherLanguage'];
        marks.forEach(mark => {
            const input = document.getElementById(mark);
            if (input && input.value) {
                const value = parseInt(input.value);
                if (value < 35 || value > 100) {
                    valid = false;
                    input.classList.add('is-invalid');
                    alert(`${mark.charAt(0).toUpperCase() + mark.slice(1)} marks must be between 35 and 100.`);
                }
            }
        });

        // Validate phone number (10 digits)
        const phone = document.getElementById('phone');
        if (phone && !/^\d{10}$/.test(phone.value)) {
            valid = false;
            phone.classList.add('is-invalid');
            alert('Phone number must be 10 digits.');
        }

        // Validate Aadhar number (12 digits)
        const aadhar = document.getElementById('aadharNumber');
        if (aadhar && !/^\d{12}$/.test(aadhar.value)) {
            valid = false;
            aadhar.classList.add('is-invalid');
            alert('Aadhar number must be 12 digits.');
        }

        // Validate PIN code (6 digits)
        const pinCode = document.getElementById('pinCode');
        if (pinCode && !/^\d{6}$/.test(pinCode.value)) {
            valid = false;
            pinCode.classList.add('is-invalid');
            alert('PIN code must be 6 digits.');
        }

        // Validate Year of Passing (format: Apr-YYYY)
        const yearOfPassing = document.getElementById('yearOfPassing');
        if (yearOfPassing && !/^[A-Za-z]{3}-\d{4}$/.test(yearOfPassing.value)) {
            valid = false;
            yearOfPassing.classList.add('is-invalid');
            alert('Year of Passing must be in format like "Apr-2023".');
        }

        if (!valid) {
            e.preventDefault();
        }
    });

    // Academic total marks calculation
    function calculateTotal() {
        var tamil = parseInt(document.getElementById("tamil").value) || 0;
        var english = parseInt(document.getElementById("english").value) || 0;
        var maths = parseInt(document.getElementById("maths").value) || 0;
        var science = parseInt(document.getElementById("science").value) || 0;
        var socialScience = parseInt(document.getElementById("socialScience").value) || 0;
        var otherLanguage = parseInt(document.getElementById("otherLanguage").value) || 0;

        var totalMarks = tamil + english + maths + science + socialScience + otherLanguage;
        document.getElementById("totalMarks").textContent = totalMarks;
    }

    document.querySelectorAll("#step3 input[type='number']").forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    // Department preference logic
    function updateDepartments() {
        var department1 = document.getElementById("department1").value;
        var department2 = document.getElementById("department2").value;
        var options = document.querySelectorAll("#step4 select[name='department2'] option");

        options.forEach(option => {
            option.disabled = false;
            option.classList.remove("faded");
        });

        if (department1) {
            document.querySelector("#step4 select[name='department2'] option[value='" + department1 + "']").disabled = true;
        }
        if (department2) {
            document.querySelector("#step4 select[name='department1'] option[value='" + department2 + "']").disabled = true;
        }

        var submitButton = document.querySelector('#step4 button[type="submit"]');
        submitButton.disabled = department1 === department2 && department1 !== "";
    }

    document.getElementById("department1").addEventListener("change", updateDepartments);
    document.getElementById("department2").addEventListener("change", updateDepartments);
    window.onload = updateDepartments;

    // File preview functionality
    function previewFile(input, previewId) {
        var file = input.files[0];
        var preview = document.getElementById(previewId);
        var reader = new FileReader();

        reader.onloadend = function () {
            if (file.type.startsWith('image/')) {
                var img = new Image();
                img.onload = function () {
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d');
                    var maxDimension = 150; // Match preview size for nice view
                    var width = img.width;
                    var height = img.height;
                    var newWidth, newHeight;

                    if (width > height && width > maxDimension) {
                        newWidth = maxDimension;
                        newHeight = (height * maxDimension) / width;
                    } else if (height > maxDimension) {
                        newHeight = maxDimension;
                        newWidth = (width * maxDimension) / height;
                    } else {
                        newWidth = width;
                        newHeight = height;
                    }

                    canvas.width = newWidth;
                    canvas.height = newHeight;
                    ctx.drawImage(img, 0, 0, newWidth, newHeight);
                    preview.src = canvas.toDataURL(file.type, 0.7); // Compress with 70% quality
                };
                img.src = reader.result;
            } else {
                preview.src = "#"; // No preview for PDFs
            }
            preview.style.display = 'block';
        };

        if (file) {
            reader.readAsDataURL(file);
        } else {
            preview.src = "#";
            preview.style.display = 'none';
        }
    }

    // Fetch location data based on PIN code (local lookup)
    function fetchLocationData(pinCode) {
        if (!/^\d{6}$/.test(pinCode)) {
            clearFields();
            return;
        }

        // Fetch data from PHP endpoint
        fetch('?pinCode=' + pinCode)
            .then(response => response.json())
            .then(data => {
                if (data.city !== 'Not Available' && data.state !== 'Not Available') {
                    document.getElementById('city').value = data.city;
                    document.getElementById('state').value = data.state;
                    document.getElementById('address').value = "Door No / Street / Village"; // Default pre-filled value
                    fetchDistricts(data.state); // Update city suggestions based on auto-filled state
                } else {
                    alert("Invalid PIN code or no data found. Please try again.");
                    clearFields();
                }
            })
            .catch(error => {
                alert("Error fetching location data. Please try again later.");
                console.error('Error:', error);
                clearFields();
            });
    }

    // Clear fields function
    function clearFields() {
        document.getElementById('pinCode').value = '';
        document.getElementById('city').value = '';
        document.getElementById('state').value = '';
        document.getElementById('address').value = '';
    }

    // Fetch districts based on selected state
    function fetchDistricts(state) {
        if (!state) {
            document.getElementById('city').value = '';
            const citySuggestions = document.getElementById('citySuggestions');
            citySuggestions.innerHTML = ''; // Clear existing options
            return;
        }

        // Define districts for each state (simplified, expand as needed)
        const districts = {
            "Tamil Nadu": [
                "Ariyalur", "Chengalpattu", "Chennai", "Coimbatore", "Cuddalore", 
                "Dharmapuri", "Dindigul", "Erode", "Kallakurichi", "Kanchipuram", 
                "Kanyakumari", "Karur", "Krishnagiri", "Madurai", "Mayiladuthurai", 
                "Nagapattinam", "Namakkal", "Nilgiris", "Perambalur", "Pudukkottai", 
                "Ramanathapuram", "Ranipet", "Salem", "Sivaganga", "Tenkasi", 
                "Thanjavur", "Theni", "Thoothukudi", "Tiruchirappalli", "Tirunelveli", 
                "Tirupathur", "Tiruppur", "Tiruvallur", "Tiruvannamalai", "Tiruvarur", 
                "Vellore", "Viluppuram", "Virudhunagar"
            ],
            "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Thane", "Nashik", "Aurangabad", "Solapur", "Kolhapur", "Satara", "Sangli"],
            "West Bengal": ["Kolkata", "Howrah", "Darjeeling", "Siliguri", "Asansol", "Durgapur", "Bardhaman", "Malda", "Murshidabad", "Nadia"],
            "Delhi": ["New Delhi", "South Delhi", "North Delhi", "East Delhi", "West Delhi", "Central Delhi"],
            "Andhra Pradesh": ["Visakhapatnam", "Vijayawada", "Guntur", "Nellore", "Kurnool", "Rajahmundry", "Tirupati", "Anantapur", "Kakinada", "Kadapa"],
            "Arunachal Pradesh": ["Itanagar", "Naharlagun", "Pasighat", "Ziro", "Tezu", "Bomdila", "Along", "Seppa", "Daporijo", "Tawang"],
            "Assam": ["Guwahati", "Dibrugarh", "Silchar", "Jorhat", "Nagaon", "Tezpur", "Tinsukia", "Bongaigaon", "Goalpara", "Dhubri"],
            "Bihar": ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur", "Purnia", "Darbhanga", "Arrah", "Bihar Sharif", "Hajipur", "Samastipur"],
            "Chhattisgarh": ["Raipur", "Bhilai", "Bilaspur", "Korba", "Raigarh", "Durg", "Jagdalpur", "Ambikapur", "Chirmiri", "Janjgir-Champa"],
            "Goa": ["Panaji", "Margao", "Vasco da Gama", "Mapusa", "Ponda", "Bicholim", "Sanquelim", "Quepem", "Curchorem", "Sanguem"],
            "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar", "Junagadh", "Gandhinagar", "Anand", "Nadiad"],
            "Haryana": ["Chandigarh", "Faridabad", "Gurugram", "Hisar", "Panipat", "Yamunanagar", "Rohtak", "Karnal", "Sonipat", "Ambala"],
            "Himachal Pradesh": ["Shimla", "Manali", "Dharamsala", "Kullu", "Solan", "Palampur", "Nahan", "Hamirpur", "Una", "Bilaspur"],
            "Jharkhand": ["Ranchi", "Jamshedpur", "Dhanbad", "Bokaro", "Deoghar", "Hazaribagh", "Giridih", "Ramgarh", "Medininagar", "Dumka"],
            "Karnataka": ["Bengaluru", "Mysuru", "Hubli-Dharwad", "Mangaluru", "Belagavi", "Kalaburagi", "Davanagere", "Vijayapura", "Shivamogga", "Tumakuru"],
            "Kerala": ["Thiruvananthapuram", "Kochi", "Kozhikode", "Thrissur", "Kollam", "Palakkad", "Alappuzha", "Kannur", "Malappuram", "Kottayam"],
            "Madhya Pradesh": ["Bhopal", "Indore", "Jabalpur", "Gwalior", "Ujjain", "Sagar", "Dewas", "Satna", "Ratlam", "Rewa"],
            "Manipur": ["Imphal", "Thoubal", "Bishnupur", "Churachandpur", "Ukhrul", "Senapati", "Tamenglong", "Chandel", "Kakching", "Jiribam"],
            "Meghalaya": ["Shillong", "Tura", "Jowai", "Nongpoh", "Baghmara", "Williamnagar", "Nongstoin", "Mawlai", "Khliehriat", "Sohra"],
            "Mizoram": ["Aizawl", "Lunglei", "Saiha", "Champhai", "Kolasib", "Serchhip", "Mamit", "Lawngtlai", "Hnahthial", "Khawzawl"],
            "Nagaland": ["Kohima", "Dimapur", "Mokokchung", "Tuensang", "Wokha", "Zunheboto", "Phek", "Kiphire", "Longleng", "Peren"],
            "Odisha": ["Bhubaneswar", "Cuttack", "Rourkela", "Berhampur", "Sambalpur", "Puri", "Balasore", "Bhadrak", "Jajpur", "Kendrapara"],
            "Punjab": ["Chandigarh", "Ludhiana", "Amritsar", "Jalandhar", "Patiala", "Bathinda", "Hoshiarpur", "Mohali", "Firozpur", "Sangrur"],
            "Rajasthan": ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Bikaner", "Ajmer", "Bhilwara", "Alwar", "Sikar", "Pali"],
            "Sikkim": ["Gangtok", "Namchi", "Gyalshing", "Mangan", "Pakyong", "Rongli", "Jorethang", "Singtam", "Rangpo", "Soreng"],
            "Telangana": ["Hyderabad", "Warangal", "Nizamabad", "Khammam", "Karimnagar", "Ramagundam", "Mahbubnagar", "Nalgonda", "Adilabad", "Suryapet"],
            "Tripura": ["Agartala", "Udaipur", "Dharmanagar", "Kailashahar", "Belonia", "Amarpur", "Sabroom", "Khowai", "Sepahijala", "Gomati"],
            "Uttar Pradesh": ["Lucknow", "Kanpur", "Varanasi", "Agra", "Meerut", "Ghaziabad", "Prayagraj", "Bareilly", "Aligarh", "Moradabad"],
            "Uttarakhand": ["Dehradun", "Haridwar", "Rishikesh", "Nainital", "Mussoorie", "Pauri", "Almora", "Rudraprayag", "Uttarkashi", "Chamoli"],
            "Andaman and Nicobar Islands": ["Port Blair", "Mayabunder", "Rangat", "Hut Bay", "Neil Island", "Havelock Island", "Little Andaman", "Car Nicobar", "Nancowry", "Katchal"],
            "Chandigarh": ["Chandigarh"],
            "Dadra and Nagar Haveli and Daman and Diu": ["Daman", "Diu", "Silvassa"],
            "Delhi": ["New Delhi", "South Delhi", "North Delhi", "East Delhi", "West Delhi", "Central Delhi"],
            "Jammu and Kashmir": ["Srinagar", "Jammu", "Anantnag", "Baramulla", "Kupwara", "Pulwama", "Ganderbal", "Budgam", "Kulgam", "Shopian"],
            "Ladakh": ["Leh", "Kargil"],
            "Lakshadweep": ["Kavaratti", "Agatti", "Minicoy", "Kadmat", "Androth", "Amini", "Kalpeni", "Kiltan", "Chetlat", "Bitra"],
            "Puducherry": ["Puducherry", "Karaikal", "Mahe", "Yanam"]
        };

        const citySuggestions = document.getElementById('citySuggestions');
        citySuggestions.innerHTML = ''; // Clear existing options

        if (districts[state]) {
            districts[state].forEach(district => {
                const option = document.createElement('option');
                option.value = district;
                citySuggestions.appendChild(option);
            });
        } else {
            document.getElementById('city').value = '';
        }
    }

    // Add event listener for state input to trigger district fetch on keyup
    document.getElementById('state').addEventListener('keyup', function() {
        fetchDistricts(this.value);
    });

    // Add event listener for city input to filter suggestions as user types
    document.getElementById('city').addEventListener('keyup', function() {
        const state = document.getElementById('state').value;
        const cityValue = this.value.toLowerCase();
        const citySuggestions = document.getElementById('citySuggestions');
        citySuggestions.innerHTML = '';

        if (state && districts[state]) {
            districts[state].forEach(district => {
                if (district.toLowerCase().includes(cityValue)) {
                    const option = document.createElement('option');
                    option.value = district;
                    citySuggestions.appendChild(option);
                }
            });
        }
    });

    // Skip banking step
    function skipBanking() {
        if (confirm('Are you sure you want to skip this step?')) {
            window.location.href = 'admin_student_entry.php?form=step3';
        }
    }
</script>
</body>
</html>