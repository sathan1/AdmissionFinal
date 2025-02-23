<?php
include '../include/db.php';
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Logout functionality
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['userId'];
$message = "";
$formType = isset($_GET['form']) ? $_GET['form'] : ''; // Default form type is empty

// Check if forms are already submitted
$checks = [
    'details' => "SELECT * FROM studentdetails WHERE studentUserId = ?",
    'banking' => "SELECT * FROM bankingdetails WHERE bankingUserId = ?",
    'academic' => "SELECT * FROM academic WHERE academicUserId = ?",
    'preference' => "SELECT * FROM preference WHERE preferenceUserId = ?",
    'document' => "SELECT * FROM document WHERE documentUserId = ?"
];

$submitted = [];

foreach ($checks as $form => $query) {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $submitted[$form] = $result->num_rows > 0;
    $stmt->close();
}

// Determine the next form to display
$steps = ['details', 'banking', 'academic', 'preference', 'document'];
$nextForm = null;
foreach ($steps as $step) {
    if (!$submitted[$step]) {
        $nextForm = $step;
        break;
    }
}

// If all steps are completed, redirect to status (banking is optional)
if ($nextForm === null && !$submitted['banking']) {
    header("Location: status.php");
    exit();
} elseif ($nextForm === null && $submitted['banking']) {
    header("Location: status.php");
    exit();
}

// Redirect to the next form if no specific form is selected
if (empty($formType)) {
    if ($nextForm) {
        header("Location: forms.php?form=$nextForm");
    } else {
        header("Location: status.php");
    }
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($formType === 'details' && !$submitted['details']) {
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
        $fatherName = filter_input(INPUT_POST, 'fatherName', FILTER_SANITIZE_STRING);
        $motherName = filter_input(INPUT_POST, 'motherName', FILTER_SANITIZE_STRING);
        $dob = $_POST['dob'];
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $caste = filter_input(INPUT_POST, 'caste', FILTER_SANITIZE_STRING);
        $otherCaste = isset($_POST['otherCaste']) ? filter_input(INPUT_POST, 'otherCaste', FILTER_SANITIZE_STRING) : '';
        $religion = filter_input(INPUT_POST, 'religion', FILTER_SANITIZE_STRING);
        $motherTongue = filter_input(INPUT_POST, 'motherTongue', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $aadharNumber = filter_input(INPUT_POST, 'aadharNumber', FILTER_SANITIZE_NUMBER_INT);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
        $pinCode = filter_input(INPUT_POST, 'pinCode', FILTER_SANITIZE_NUMBER_INT);

        if (empty($caste)) {
            $message = "Caste is required.";
        } elseif ($caste === 'Others' && empty($otherCaste)) {
            $message = "Please specify the caste under 'Others'.";
        } elseif (!preg_match('/^\d{12}$/', $aadharNumber)) {
            $message = "Aadhar number must be 12 digits.";
        } elseif (!preg_match('/^\d{10}$/', $phone)) {
            $message = "Phone number must be 10 digits.";
        } elseif (empty($city) || empty($state)) {
            $message = "City and State must be selected manually.";
        } elseif (!preg_match('/^\d{6}$/', $pinCode)) {
            $message = "PIN code must be 6 digits.";
        } else {
            // Validate Date of Birth (minimum 15 years old)
            $currentDate = new DateTime();
            $birthDate = new DateTime($dob);
            $age = $currentDate->diff($birthDate)->y;
            if ($age < 15) {
                $message = "You must be at least 15 years old.";
            } else {
                $query = "INSERT INTO studentdetails 
                          (studentUserId, studentFirstName, studentLastName, studentFatherName, studentMotherName, studentDateOfBirth, studentGender, studentCaste, studentCaste_2, studentReligion, studentMotherTongue, studentPhoneNumber, studentAadharNumber, studentAddress, studentCity, studentState, studentPinCode) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    die("Failed to prepare statement: " . $conn->error);
                }
                $stmt->bind_param("issssssssssssssss", $userId, $firstName, $lastName, $fatherName, $motherName, $dob, $gender, $caste, $otherCaste, $religion, $motherTongue, $phone, $aadharNumber, $address, $city, $state, $pinCode);
                if ($stmt->execute()) {
                    header("Location: forms.php?form=banking");
                    exit();
                } else {
                    $message = "Error saving student details: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif ($formType === 'banking' && !$submitted['banking']) {
        $accountNumber = filter_input(INPUT_POST, 'accountNumber', FILTER_SANITIZE_NUMBER_INT) ?? '';
        $bankName = filter_input(INPUT_POST, 'bankName', FILTER_SANITIZE_STRING) ?? '';
        $branch = filter_input(INPUT_POST, 'branch', FILTER_SANITIZE_STRING) ?? '';
        $ifsc = filter_input(INPUT_POST, 'ifsc', FILTER_SANITIZE_STRING) ?? '';
        $panNumber = filter_input(INPUT_POST, 'panNumber', FILTER_SANITIZE_STRING) ?? '';
        $drivingLicenseNumber = filter_input(INPUT_POST, 'drivingLicenseNumber', FILTER_SANITIZE_STRING) ?? '';

        // If any banking field is filled, validate and insert
        if (!empty($accountNumber) || !empty($bankName) || !empty($branch) || !empty($ifsc) || !empty($panNumber) || !empty($drivingLicenseNumber)) {
            if (!empty($accountNumber) && !preg_match('/^\d{12}$/', $accountNumber)) {
                $message = "Account number must be 12 digits.";
            } elseif (!empty($ifsc) && !preg_match('/^[A-Z]{4}[0-9]{7}[A-Z]$/', $ifsc)) {
                $message = "Invalid IFSC code.";
            } elseif (!empty($panNumber) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $panNumber)) {
                $message = "Invalid PAN number format.";
            } elseif (!empty($drivingLicenseNumber) && !preg_match('/^[A-Z]{2}\d{13}$/', $drivingLicenseNumber)) {
                $message = "Invalid Driving License format. Use format like 'TN1234567890123' (2 letters, 13 digits).";
            } else {
                $query = "INSERT INTO bankingdetails 
                          (bankingUserId, accountNumber, bankName, branch, ifsc, panNumber, drivingLicenseNumber) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                if ($stmt === false) {
                    die("Failed to prepare statement: " . $conn->error);
                }
                $stmt->bind_param("issssss", $userId, $accountNumber, $bankName, $branch, $ifsc, $panNumber, $drivingLicenseNumber);
                if ($stmt->execute()) {
                    header("Location: forms.php?form=academic");
                    exit();
                } else {
                    $message = "Error saving banking details: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            // Skip banking if no fields are filled or "Skip" is clicked
            header("Location: forms.php?form=academic");
            exit();
        }
    } elseif ($formType === 'academic' && !$submitted['academic']) {
        $school_name = filter_input(INPUT_POST, 'school_name', FILTER_SANITIZE_STRING);
        $yearOfPassing = filter_input(INPUT_POST, 'yearOfPassing', FILTER_SANITIZE_STRING);
        $tamil = filter_input(INPUT_POST, 'tamil', FILTER_VALIDATE_INT) ?: 0;
        $english = filter_input(INPUT_POST, 'english', FILTER_VALIDATE_INT) ?: 0;
        $maths = filter_input(INPUT_POST, 'maths', FILTER_VALIDATE_INT) ?: 0;
        $science = filter_input(INPUT_POST, 'science', FILTER_VALIDATE_INT) ?: 0;
        $socialScience = filter_input(INPUT_POST, 'socialScience', FILTER_VALIDATE_INT) ?: 0;
        $otherLanguage = filter_input(INPUT_POST, 'otherLanguage', FILTER_VALIDATE_INT) ?: 0;
        $emisNumber = filter_input(INPUT_POST, 'emisNumber', FILTER_SANITIZE_STRING) ?? '';

        if (empty($yearOfPassing) || !preg_match('/^[A-Za-z]{3}-\d{4}$/', $yearOfPassing)) {
            $message = "Year of passing must be in format like 'Apr-2023' and cannot be empty.";
        } elseif ($tamil < 35 || $tamil > 100 || $english < 35 || $english > 100 || 
                  $maths < 35 || $maths > 100 || $science < 35 || $science > 100 || 
                  $socialScience < 35 || $socialScience > 100 || 
                  ($otherLanguage > 0 && ($otherLanguage < 35 || $otherLanguage > 100))) {
            $message = "All subjects (Tamil, English, Maths, Science, Social Science) must have at least 35 marks and not exceed 100. Other Language, if provided, must be between 35 and 100 marks.";
        } else {
            $query = "INSERT INTO academic 
                      (academicUserId, school_name, yearOfPassing, tamilMarks, englishMarks, mathsMarks, scienceMarks, socialScienceMarks, otherLanguageMarks, emisNumber) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                die("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param("issiiiiiis", $userId, $school_name, $yearOfPassing, $tamil, $english, $maths, $science, $socialScience, $otherLanguage, $emisNumber);
            if ($stmt->execute()) {
                header("Location: forms.php?form=preference");
                exit();
            } else {
                $message = "Error saving academic details: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($formType === 'preference' && !$submitted['preference']) {
        $department1 = $_POST['department1'] ?? null;
        $department2 = $_POST['department2'] ?? null;

        if (!$department1 || !$department2 || $department1 === $department2) {
            $message = "Invalid department preferences. Both preferences must be different.";
            header("Location: forms.php?form=preference&error=" . urlencode($message));
            exit();
        }

        $checkQuery = "SELECT preferenceId FROM preference WHERE preferenceUserId = ?";
        $stmt = $conn->prepare($checkQuery);
        if ($stmt === false) {
            die("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $existingPreferences = $stmt->get_result();

        if ($existingPreferences->num_rows > 0) {
            while ($row = $existingPreferences->fetch_assoc()) {
                $updateQuery = "UPDATE preference SET preferenceStatus = 'pending' WHERE preferenceId = ?";
                $updateStmt = $conn->prepare($updateQuery);
                if ($updateStmt === false) {
                    die("Failed to prepare statement: " . $conn->error);
                }
                $updateStmt->bind_param("i", $row['preferenceId']);
                $updateStmt->execute();
                $updateStmt->close();
            }
            $message = "Preferences updated to 'pending' successfully.";
        } else {
            $query = "INSERT INTO preference (preferenceUserId, preferenceOrder, preferenceDepartment, preferenceStatus) 
                      VALUES (?, ?, ?, 'pending')";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                die("Failed to prepare statement: " . $conn->error);
            }

            $order = 1;
            $stmt->bind_param("iis", $userId, $order, $department1);
            $stmt->execute();

            $order = 2;
            $stmt->bind_param("iis", $userId, $order, $department2);
            $stmt->execute();

            $message = "Preferences submitted successfully with status 'pending'.";
        }
        $stmt->close();
        header("Location: forms.php?form=document");
        exit();
    } elseif ($formType === 'document' && !$submitted['document']) {
        $targetDir = "../documents/";
        $documents = ['aadhaar', 'marksheet', 'photo', 'birthCertificate', 'migrationCertificate', 'characterCertificate'];
        $requiredDocs = ['aadhaar', 'marksheet', 'photo', 'birthCertificate'];
        $uploadedFiles = [];
        $message = "";

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                die("Failed to create directory: $targetDir");
            }
        }

        foreach ($documents as $docType) {
            if (isset($_FILES[$docType]) && $_FILES[$docType]['error'] === UPLOAD_ERR_OK) {
                $fileSize = $_FILES[$docType]['size'];
                if ($fileSize > 300000) { // 300 KB in bytes
                    $message .= "File size for $docType exceeds 300KB.<br>";
                    continue;
                }

                $fileName = $_FILES[$docType]['name'];
                $fileTmpName = $_FILES[$docType]['tmp_name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $message .= "Invalid file type for $docType. Allowed types: JPG, JPEG, PNG, PDF.<br>";
                    continue;
                }

                $newFileName = uniqid($docType . '_', true) . '.' . $fileExtension;
                $targetFilePath = $targetDir . $newFileName;

                if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                    $uploadedFiles[$docType] = $newFileName;

                    $insertQuery = "INSERT INTO document (documentUserId, documentType, documentName) VALUES (?, ?, ?)";
                    $insertStmt = $conn->prepare($insertQuery);
                    if ($insertStmt === false) {
                        die("Failed to prepare statement: " . $conn->error);
                    }
                    $insertStmt->bind_param("iss", $userId, $docType, $newFileName);
                    if (!$insertStmt->execute()) {
                        $message .= "Error saving $docType to the database: " . $insertStmt->error . "<br>";
                    }
                    $insertStmt->close();
                } else {
                    $message .= "Error uploading $docType.<br>";
                }
            } elseif (in_array($docType, $requiredDocs)) {
                $message .= ucfirst($docType) . " file is missing.<br>";
            }
        }

        if (count($uploadedFiles) > 0) {
            $message .= "Documents uploaded successfully: " . count($uploadedFiles) . " out of " . count($documents) . ".<br>";
        }

        if (count(array_intersect($requiredDocs, array_keys($uploadedFiles))) === count($requiredDocs)) {
            header("Location: forms.php?form=complete");
            exit();
        } else {
            $message .= "Please upload all required documents (Aadhaar, Marksheet, Photo, Birth Certificate) to proceed.<br>";
        }
    }
}

function uploadErrorMessage($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload";
        default:
            return "Unknown upload error";
    }
}

// Local PIN code to city/state mapping (simplified for Indian PIN codes based on your data)
$pinCodeData = [
    '642103' => ['city' => 'Coimbatore', 'state' => 'Tamil Nadu'],
    '600001' => ['city' => 'Chennai', 'state' => 'Tamil Nadu'],
    '700001' => ['city' => 'Kolkata', 'state' => 'West Bengal'],
    '400001' => ['city' => 'Mumbai', 'state' => 'Maharashtra'],
    '110001' => ['city' => 'Delhi', 'state' => 'Delhi'],
    // Add more PIN codes as needed based on your database or region
];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pinCode'])) {
    $pinCode = filter_input(INPUT_GET, 'pinCode', FILTER_SANITIZE_NUMBER_INT);
    if (isset($pinCodeData[$pinCode])) {
        header('Content-Type: application/json');
        echo json_encode($pinCodeData[$pinCode]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['city' => 'Not Available', 'state' => 'Not Available']);
        exit();
    }
}

if ($submitted['details'] && $submitted['academic'] && $submitted['preference'] && $submitted['document']) {
    header("Location: status.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .error {
            color: red;
        }
        .file-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        .progress-bar {
            width: 0;
            height: 30px;
            background-color: #4CAF50;
            transition: width 1s ease-in-out;
        }
        .progress-container {
            margin: 20px 0;
        }
        .autocomplete {
            position: relative;
            display: inline-block;
        }
        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
        }
        .autocomplete-items div {
            padding: 10px;
            cursor: pointer;
            background-color: #fff;
            border-bottom: 1px solid #d4d4d4;
        }
        .autocomplete-items div:hover {
            background-color: #e9e9e9;
        }
        .autocomplete-active {
            background-color: DodgerBlue !important;
            color: #ffffff;
        }
        .readonly-field {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include '../header_student.php'; ?>

    <div class="container mt-4">
        <p class="text-success"><?= $message ?></p>

        <nav aria-label="Progress">
            <ol class="breadcrumb">
                <li class="breadcrumb-item <?= $formType === 'details' ? 'active' : ($submitted['details'] ? 'completed' : '') ?>">Step 1</li>
                <li class="breadcrumb-item <?= $formType === 'banking' ? 'active' : ($submitted['banking'] ? 'completed' : '') ?>">Step 2 (Optional)</li>
                <li class="breadcrumb-item <?= $formType === 'academic' ? 'active' : ($submitted['academic'] ? 'completed' : '') ?>">Step 3</li>
                <li class="breadcrumb-item <?= $formType === 'preference' ? 'active' : ($submitted['preference'] ? 'completed' : '') ?>">Step 4</li>
                <li class="breadcrumb-item <?= $formType === 'document' ? 'active' : ($submitted['document'] ? 'completed' : '') ?>">Step 5</li>
            </ol>
        </nav>

        <div class="progress-container">
            <div class="progress-bar" style="width: <?= array_search($formType, $steps) * 20 ?>%;"></div>
        </div>

        <!-- Step 1: Student Details -->
        <div id="details" class="step<?= $formType === 'details' ? ' active' : '' ?>">
            <h2>Step 1 - Student Details</h2>
            <form method="post" id="studentDetailsForm">
                <div class="mb-3">
                    <label for="firstName" class="form-label">First Name</label>
                    <input type="text" name="firstName" id="firstName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="lastName" class="form-label">Last Name</label>
                    <input type="text" name="lastName" id="lastName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="fatherName" class="form-label">Father's Name</label>
                    <input type="text" name="fatherName" id="fatherName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="motherName" class="form-label">Mother's Name</label>
                    <input type="text" name="motherName" id="motherName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" name="dob" id="dob" class="form-control" required min="<?php echo date('Y-m-d', strtotime('-100 years')); ?>">
                </div>
                <div class="mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="caste" class="form-label">Community</label>
                    <select name="caste" id="caste" class="form-control" required>
                        <option value="">Select Community</option>
                        <option value="BC">BC - Backward Class</option>
                        <option value="MBC">MBC - Most Backward Class</option>
                        <option value="SC">SC - Scheduled Caste</option>
                        <option value="ST">ST - Scheduled Tribe</option>
                        <option value="OC">OC - Open Category</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="mb-3" id="otherCasteInput" >
                    <label for="otherCaste" class="form-label">Caste</label>
                    <input type="text" name="otherCaste" id="otherCaste" class="form-control" list="casteSuggestions" required>
                    <datalist id="casteSuggestions">
                        <?php
                        $castes = [
                            "Adaviyar", "Agamudayar", "Ambattar", "Arunattu Vellalar", "Ashtasahasram", "Badagas", "Balija",
                            "Bhuiya", "Boom Boom Mattukaran", "Boya (caste)", "Chettiar", "Chozhia Vellalar", "Desikar",
                            "Devanga", "Devendrakulam", "Elur Chetty", "Ethnic groups in Tamil Nadu", "Gounder", "Hebbar Iyengar",
                            "Ilai Vaniyar", "Irula people", "Isai Vellalar", "Iyengar", "Iyer", "Jain communities", "Kaarkaathaar",
                            "Kallar (caste)", "Kamma (caste)", "Kammalar (caste)", "Kannadigas", "Karaiyar", "Katesar",
                            "Kayalar (Muslim)", "Kodikaal Vellalar", "Koliyar", "Konar (caste)", "Kondaikatti Vellalar",
                            "Kongu Vellalar", "Koravar", "Kosar people", "Koshta", "Kota people (India)", "Kulala", "Kuravar",
                            "Kuruba", "Kurumba Gounder", "Labbay", "Malai Vellalar", "Malabar Muslims", "Maravar", "Marakkar",
                            "Meenavar", "Mudugar", "Mukkulathor", "Muthuraja", "Muthuvan", "Nadan (subcaste)", "Nadar (caste)",
                            "Nadar climber", "Nagarathar", "Nai (caste)", "Nankudi Vellalar", "Padmasali (caste)",
                            "Palayakkara Naicker", "Palayakkaran", "Paliyan", "Panar (Kundapura)", "Pannaiyar", "Paravar",
                            "Pattanavar", "Pattariyar", "Pattusali", "Piramalai Kallar", "Pullingo", "Reddiar", "Reddy",
                            "Reddy Catholics", "Rowther", "Saliya", "Satani (caste)", "Saurashtra people", "Sembadavar",
                            "Sengunthar", "Siviyar", "Sri Lankan Mukkuvar", "Tamil Brahmin", "Tamil Hindus", "Tamil Jain",
                            "Tamil Muslim", "Thanjavur Marathi people", "Thigala", "Thondaimandala Vellalar", "Thuluva Vellala",
                            "Thurumbar", "Toda people", "Udayar (caste)", "Uppara", "Vadama", "Vaddera", "Valangai",
                            "Vallanattu Chettiar", "Valluvar (caste)", "Vannar", "Vanniyar", "Vathima", "Vatuka", "Velar (caste)",
                            "Vellalar", "Vettuva Gounder"
                        ];
                        foreach ($castes as $casteOption) {
                            echo "<option value='$casteOption'>";
                        }
                        ?>
                    </datalist>
                </div>
                <div class="mb-3">
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
                <div class="mb-3">
                    <label for="motherTongue" class="form-label">Mother Tongue</label>
                    <select name="motherTongue" id="motherTongue" class="form-control" required>
                        <option value="">Select MotherTongue</option>
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
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="form-control" required pattern="\d{10}" title="Phone number must be 10 digits">
                </div>
                <div class="mb-3">
                    <label for="aadharNumber" class="form-label">Aadhar Number</label>
                    <input type="text" name="aadharNumber" id="aadharNumber" class="form-control" required pattern="\d{12}" title="12 digits Aadhar number">
                </div>
                <div class="mb-3">
                    <label for="pinCode" class="form-label">PIN Code</label>
                    <input type="text" name="pinCode" id="pinCode" class="form-control" required pattern="\d{6}" 
                        title="6 digits PIN code">
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address (Door No / Street / Village)</label>
                    <textarea name="address" id="address" class="form-control" required placeholder="Door No / Street / Village"></textarea>
                </div>
                <div class="mb-3">
                    <label for="state" class="form-label">State</label>
                    <input type="text" name="state" id="state" class="form-control" list="stateSuggestions" required 
                        onchange="fetchDistricts(this.value)" onkeyup="fetchDistricts(this.value)">
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
                <div class="mb-3">
                    <label for="city" class="form-label">City (District)</label>
                    <input type="text" name="city" id="city" class="form-control" list="citySuggestions" required>
                    <datalist id="citySuggestions">
                        <!-- Populated dynamically via JavaScript -->
                    </datalist>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>

        <!-- Step 2: Banking Details (Optional) -->
        <div id="banking" class="step<?= $formType === 'banking' ? ' active' : '' ?>">
            <h2>Step 2 - Banking Details (Optional)</h2>
            <form method="post" id="bankingForm">
                <div class="mb-3">
                    <label for="accountNumber" class="form-label">Account Number</label>
                    <input type="text" name="accountNumber" id="accountNumber" class="form-control" pattern="\d{12}" title="12 digits account number">
                </div>
                <div class="mb-3">
                    <label for="bankName" class="form-label">Bank Name</label>
                    <input type="text" name="bankName" id="bankName" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="branch" class="form-label">Branch</label>
                    <input type="text" name="branch" id="branch" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="ifsc" class="form-label">IFSC Code</label>
                    <input type="text" name="ifsc" id="ifsc" class="form-control" pattern="[A-Z]{4}[0-9]{7}[A-Z]" title="IFSC code format: XXXX0000000X">
                </div>
                <div class="mb-3">
                    <label for="panNumber" class="form-label">PAN Number</label>
                    <input type="text" name="panNumber" id="panNumber" class="form-control" pattern="[A-Z]{5}[0-9]{4}[A-Z]" title="PAN format: AAAAA0000A">
                </div>
                <div class="mb-3">
                    <label for="drivingLicenseNumber" class="form-label">Driving License Number</label>
                    <input type="text" name="drivingLicenseNumber" id="drivingLicenseNumber" class="form-control" 
                        pattern="^[A-Z]{2}\d{13}$" title="Use format like 'TN1234567890123' (2 letters, 13 digits)">
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="forms.php?form=academic" class="btn btn-secondary" onclick="skipBanking(); return false;">Skip</a>
            </form>
        </div>

        <!-- Step 3: Academic Details -->
        <div id="academic" class="step<?= $formType === 'academic' ? ' active' : '' ?>">
            <h2>Step 3 - Academic Details</h2>
            <form method="post">
                <div class="mb-3">
                    <label for="school_name" class="form-label">School Name</label>
                    <input type="text" name="school_name" id="school_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="yearOfPassing" class="form-label">Year of Passing</label>
                    <input type="text" name="yearOfPassing" id="yearOfPassing" class="form-control" required pattern="^[A-Za-z]{3}-\d{4}$" title="Must be in format like 'Apr-2023'">
                </div>
                <div class="mb-3">
                    <label for="emisNumber" class="form-label">EMIS Number</label>
                    <input type="text" name="emisNumber" id="emisNumber" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="tamil" class="form-label">Tamil Marks</label>
                    <input type="number" name="tamil" id="tamil" class="form-control" required min="35" max="100">
                </div>
                <div class="mb-3">
                    <label for="english" class="form-label">English Marks</label>
                    <input type="number" name="english" id="english" class="form-control" required min="35" max="100">
                </div>
                <div class="mb-3">
                    <label for="maths" class="form-label">Maths Marks</label>
                    <input type="number" name="maths" id="maths" class="form-control" required min="35" max="100">
                </div>
                <div class="mb-3">
                    <label for="science" class="form-label">Science Marks</label>
                    <input type="number" name="science" id="science" class="form-control" required min="35" max="100">
                </div>
                <div class="mb-3">
                    <label for="socialScience" class="form-label">Social Science Marks</label>
                    <input type="number" name="socialScience" id="socialScience" class="form-control" required min="35" max="100">
                </div>
                <div class="mb-3">
                    <label for="otherLanguage" class="form-label">Other Language Marks</label>
                    <input type="number" name="otherLanguage" id="otherLanguage" class="form-control" min="35" max="100">
                </div>
                <div class="total-marks" id="totalMarksDisplay">
                    Total Marks: <span id="totalMarks">0</span>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>

        <!-- Step 4: Department Preference -->
        <div id="preference" class="step<?= $formType === 'preference' ? ' active' : '' ?>">
            <h2>Step 4 - Department Preference</h2>
            <form method="post">
                <div class="mb-3">
                    <label for="department1" class="form-label">First Preference</label>
                    <select name="department1" id="department1" class="form-control" required>
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
                <div class="mb-3">
                    <label for="department2" class="form-label">Second Preference</label>
                    <select name="department2" id="department2" class="form-control" required>
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
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>

        <!-- Step 5: Document Upload -->
        <div id="document" class="step<?= $formType === 'document' ? ' active' : '' ?>">
            <h2>Step 5 - Document Upload</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="aadhaar" class="form-label">Aadhaar (Required)</label>
                    <input type="file" name="aadhaar" id="aadhaar" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'aadhaarPreview')">
                    <img id="aadhaarPreview" class="file-preview" src="#" alt="Aadhaar Preview" style="display:none;">
                </div>
                <div class="mb-3">
                    <label for="marksheet" class="form-label">Marksheet (Required)</label>
                    <input type="file" name="marksheet" id="marksheet" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'marksheetPreview')">
                    <img id="marksheetPreview" class="file-preview" src="#" alt="Marksheet Preview" style="display:none;">
                </div>
                <div class="mb-3">
                    <label for="photo" class="form-label">Photo (Required)</label>
                    <input type="file" name="photo" id="photo" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'photoPreview')">
                    <img id="photoPreview" class="file-preview" src="#" alt="Photo Preview" style="display:none;">
                </div>
                <div class="mb-3">
                    <label for="birthCertificate" class="form-label">Birth Certificate (Required)</label>
                    <input type="file" name="birthCertificate" id="birthCertificate" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required onchange="previewFile(this, 'birthCertificatePreview')">
                    <img id="birthCertificatePreview" class="file-preview" src="#" alt="Birth Certificate Preview" style="display:none;">
                </div>
                <div class="mb-3">
                    <label for="migrationCertificate" class="form-label">Migration Certificate (Optional)</label>
                    <input type="file" name="migrationCertificate" id="migrationCertificate" class="form-control" accept=".jpg, .jpeg, .png, .pdf" onchange="previewFile(this, 'migrationCertificatePreview')">
                    <img id="migrationCertificatePreview" class="file-preview" src="#" alt="Migration Certificate Preview" style="display:none;">
                </div>
                <div class="mb-3">
                    <label for="characterCertificate" class="form-label">Character Certificate (Optional)</label>
                    <input type="file" name="characterCertificate" id="characterCertificate" class="form-control" accept=".jpg, .jpeg, .png, .pdf" onchange="previewFile(this, 'characterCertificatePreview')">
                    <img id="characterCertificatePreview" class="file-preview" src="#" alt="Character Certificate Preview" style="display:none;">
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>

   <script>
        // Progress bar animation
        document.addEventListener('DOMContentLoaded', function() {
            let progress = document.querySelector('.progress-bar');
            let width = 0;
            let id = setInterval(frame, 10);
            function frame() {
                if (width >= <?= array_search($formType, $steps) * 20 ?>) {
                    clearInterval(id);
                } else {
                    width++;
                    progress.style.width = width + '%';
                }
            }

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

            document.querySelectorAll("#academic input[type='number']").forEach(input => {
                input.addEventListener('input', calculateTotal);
            });

            // Department preference logic
            function updateDepartments() {
                var department1 = document.getElementById("department1").value;
                var department2 = document.getElementById("department2").value;
                var options = document.querySelectorAll("#department2 option");

                options.forEach(option => {
                    option.disabled = false;
                    option.classList.remove("faded");
                });

                if (department1) {
                    document.querySelector("#department2 option[value='" + department1 + "']").disabled = true;
                }
                if (department2) {
                    document.querySelector("#department1 option[value='" + department2 + "']").disabled = true;
                }

                var submitButton = document.querySelector('#preference button[type="submit"]');
                submitButton.disabled = department1 === department2 && department1 !== "";
            }

            document.getElementById("department1").addEventListener("change", updateDepartments);
            document.getElementById("department2").addEventListener("change", updateDepartments);
            window.onload = updateDepartments;

            // File preview function
            function previewFile(input, previewId) {
                var file = input.files[0];
                var reader = new FileReader();

                reader.onloadend = function () {
                    document.getElementById(previewId).src = reader.result;
                    document.getElementById(previewId).style.display = 'block';
                }

                if (file) {
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById(previewId).src = "#";
                    document.getElementById(previewId).style.display = 'none';
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

            // Validate address format on form submission
            document.getElementById('studentDetailsForm').addEventListener('submit', function(e) {
               
                if (!document.getElementById('city').value || !document.getElementById('state').value) {
                    e.preventDefault();
                    alert("City and State must be selected.");
                    return false;
                }
            });

            // Skip banking step
            function skipBanking() {
                if (confirm('Are you sure you want to skip this step?')) {
                    window.location.href = 'forms.php?form=academic';
                }
            }
        });
 // Validate address format and other fields on form submission
document.getElementById('studentDetailsForm').addEventListener('submit', function(e) {
    const address = document.getElementById('address').value.trim();
    const dob = document.getElementById('dob').value;
    const city = document.getElementById('city').value;
    const state = document.getElementById('state').value;

    if (!city || !state) {
        e.preventDefault();
        alert("City and State must be selected.");
        return false;
    }
    if (!dob) {
        e.preventDefault();
        alert("Date of Birth is required.");
        return false;
    }
    // Age validation (minimum 15 years) handled by HTML min attribute and PHP
});
        // Auto-suggestion for caste (using datalist in HTML, no additional JS needed here)
 // Validate academic marks on form submission
document.getElementById('academic').querySelector('form').addEventListener('submit', function(e) {
    const tamil = parseInt(document.getElementById('tamil').value) || 0;
    const english = parseInt(document.getElementById('english').value) || 0;
    const maths = parseInt(document.getElementById('maths').value) || 0;
    const science = parseInt(document.getElementById('science').value) || 0;
    const socialScience = parseInt(document.getElementById('socialScience').value) || 0;
    const otherLanguage = parseInt(document.getElementById('otherLanguage').value) || 0;

    if (tamil < 35 || tamil > 100 || english < 35 || english > 100 || 
        maths < 35 || maths > 100 || science < 35 || science > 100 || 
        socialScience < 35 || socialScience > 100 || 
        (otherLanguage > 0 && (otherLanguage < 35 || otherLanguage > 100))) {
        e.preventDefault();
        alert("All subjects (Tamil, English, Maths, Science, Social Science) must have at least 35 marks and not exceed 100. Other Language, if provided, must be between 35 and 100 marks.");
        return false;
    }
});
    </script>
</body>
</html>