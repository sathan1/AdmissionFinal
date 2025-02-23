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
$query = "SELECT * FROM studentdetails WHERE studentUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$detailsSubmitted = $stmt->get_result()->num_rows > 0;

$query = "SELECT * FROM academic WHERE academicUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$academicSubmitted = $stmt->get_result()->num_rows > 0;

$query = "SELECT * FROM preference WHERE preferenceUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$preferenceSubmitted = $stmt->get_result()->num_rows > 0;

$query = "SELECT * FROM document WHERE documentUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$documentSubmitted = $stmt->get_result()->num_rows > 0;

// Determine the next form to display
if (!$detailsSubmitted) {
    $nextForm = 'details';
} elseif (!$academicSubmitted) {
    $nextForm = 'academic';
} elseif (!$preferenceSubmitted) {
    $nextForm = 'preference';
} elseif (!$documentSubmitted) {
    $nextForm = 'document';
} else {
    $nextForm = 'complete';
}

// Redirect to the next form if no specific form is selected
if (empty($formType) || $formType !== $nextForm) {
    if ($nextForm === 'complete') {
        header("Location: status.php");
    } else {
        header("Location: forms.php?form=$nextForm");
    }
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($formType === 'details' && !$detailsSubmitted) {
        // Handle student details form submission
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $fatherName = $_POST['fatherName'];
        $motherName = $_POST['motherName'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $caste = $_POST['caste'];
        $otherCaste = isset($_POST['otherCaste']) ? $_POST['otherCaste'] : '';
        $religion = $_POST['religion'];
        $motherTongue = $_POST['motherTongue'];
        $phone = $_POST['phone'];

        if (empty($caste) || (empty($otherCaste) && $caste === 'Others')) {
            $message = "Both caste and other caste fields are required.";
        } else {
            $query = "INSERT INTO studentdetails 
                      (studentUserId, studentFirstName, studentLastName, studentFatherName, studentMotherName, studentDateOfBirth, studentGender, studentCaste, studentCaste_2, studentReligion, studentMotherTongue, studentPhoneNumber) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssssssssss", $userId, $firstName, $lastName, $fatherName, $motherName, $dob, $gender, $caste, $otherCaste, $religion, $motherTongue, $phone);
            if ($stmt->execute()) {
                header("Location: forms.php?form=academic");
                exit();
            } else {
                $message = "Error saving student details.";
            }
        }
    } elseif ($formType === 'academic' && !$academicSubmitted) {
        // Handle academic form submission
        $school_name = $_POST['school_name'];
        $yearOfPassing = $_POST['yearOfPassing'] ?? 0;
        $tamil = isset($_POST['tamil']) ? (int)$_POST['tamil'] : 0;
        $english = isset($_POST['english']) ? (int)$_POST['english'] : 0;
        $maths = isset($_POST['maths']) ? (int)$_POST['maths'] : 0;
        $science = isset($_POST['science']) ? (int)$_POST['science'] : 0;
        $socialScience = isset($_POST['socialScience']) ? (int)$_POST['socialScience'] : 0;
        $otherLanguage = isset($_POST['otherLanguage']) ? (int)$_POST['otherLanguage'] : 0;

        $totalMarks = $tamil + $english + $maths + $science + $socialScience + $otherLanguage;

        $query = "INSERT INTO academic 
                  (academicUserId, school_name, yearOfPassing, tamilMarks, englishMarks, mathsMarks, scienceMarks, socialScienceMarks, otherLanguageMarks) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isiiiiiii", $userId, $school_name, $yearOfPassing, $tamil, $english, $maths, $science, $socialScience, $otherLanguage);
        if ($stmt->execute()) {
            header("Location: forms.php?form=preference");
            exit();
        } else {
            $message = "Error saving academic details.";
        }

    } elseif ($formType === 'preference' && !$prefernceSubmitted) {
        // Retrieve the user's preferences (from form submission or existing logic)
        $department1 = $_POST['department1'] ?? null;
        $department2 = $_POST['department2'] ?? null;
    
        if (!$department1 || !$department2 || $department1 === $department2) {
            $message = "Invalid department preferences. Both preferences must be different.";
            header("Location: forms.php?form=preference&error=" . urlencode($message));
            exit();
        }
    
        // Check if preferences already exist for the user
        $checkQuery = "SELECT preferenceId FROM preference WHERE preferenceUserId = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $existingPreferences = $stmt->get_result();
    
        if ($existingPreferences->num_rows > 0) {
            // Update existing preferences to 'pending'
            while ($row = $existingPreferences->fetch_assoc()) {
                $updateQuery = "UPDATE preference SET preferenceStatus = 'pending' WHERE preferenceId = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("i", $row['preferenceId']);
                $updateStmt->execute();
            }
    
            $message = "Preferences updated to 'pending' successfully.";
        } else {
            // Insert new preferences if they don't exist
            $query = "INSERT INTO preference (preferenceUserId, preferenceOrder, preferenceDepartment, preferenceStatus) 
                      VALUES (?, ?, ?, 'pending')";
            $stmt = $conn->prepare($query);
    
            // Insert first preference
            $order = 1;
            $stmt->bind_param("iis", $userId, $order, $department1);
            $stmt->execute();
    
            // Insert second preference
            $order = 2;
            $stmt->bind_param("iis", $userId, $order, $department2);
            $stmt->execute();
    
            $message = "Preferences submitted successfully with status 'pending'.";
        }
    
        header("Location: forms.php?form=document");
        exit();
    }
    
      elseif ($formType === 'document' && !$documentSubmitted) {
          // Directory where files will be uploaded
          $targetDir = "../documents/";
      
          // Array of required document types
          $documents = ['aadhaar', 'marksheet', 'photo'];
          $uploadedFiles = [];
          $message = "";
      
          foreach ($documents as $docType) {
              if (isset($_FILES[$docType]) && $_FILES[$docType]['error'] === 0) {
                  // Get file details
                  $fileName = $_FILES[$docType]['name'];
                  $fileTmpName = $_FILES[$docType]['tmp_name'];
                  $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
      
                  // Validate allowed file extensions
                  $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                  if (!in_array($fileExtension, $allowedExtensions)) {
                      $message .= "Invalid file type for $docType. Allowed types: JPG, JPEG, PNG, PDF.<br>";
                      continue;
                  }
      
                  // Generate a unique file name
                  $newFileName = uniqid($docType . '_', true) . '.' . $fileExtension;
                  $targetFilePath = $targetDir . $newFileName;
      
                  // Ensure the upload directory exists
                  if (!is_dir($targetDir)) {
                      mkdir($targetDir, 0755, true);
                  }
      
                  // Move the uploaded file to the target directory
                  if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                      $uploadedFiles[$docType] = $newFileName; // Store the uploaded file's name
                  } else {
                      $message .= "Error uploading $docType.<br>";
                  }
              } else {
                  $message .= ucfirst($docType) . " file is missing.<br>";
              }
          }
      
          // Check if all required files are uploaded
          if (count($uploadedFiles) === count($documents)) {
              $documentNames = json_encode($uploadedFiles); // Encode file names as JSON for better handling
      
              $query = "INSERT INTO document (documentUserId, documentType, documentName) VALUES (?, 'documents', ?)";
              $stmt = $conn->prepare($query);
              $stmt->bind_param("is", $userId, $documentNames);
      
              if ($stmt->execute()) {
                  $message = "Documents uploaded successfully.";
                  header("Location: forms.php?form=complete");
                  exit();
              } else {
                  $message = "Error saving documents to the database.";
              }
          } else {
              $message .= "Please upload all required files.";
          }
      }
}

// Redirect if all forms are submitted
if ($detailsSubmitted && $academicSubmitted && $preferenceSubmitted && $documentSubmitted) {
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
    <link href="styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="script.js"></script>
</head>
<body>
<div class="container mt-4">
    <h2>Student Admission Form</h2>
    <p class="text-success"><?= $message ?></p>

    <?php if ($formType === 'details' && !$detailsSubmitted): ?>
        <!-- Student Details Form -->
        <form method="post">
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
                <input type="date" name="dob" id="dob" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select name="gender" id="gender" class="form-control" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-3">
            <label for="community" class="form-label">Community</label>
            <select name="caste" id="caste" class="form-control" required>
                <option value="BC">BC - Backward Class</option>
                <option value="MBC">MBC - Most Backward Class</option>
                <option value="SC">SC - Scheduled Caste</option>
                <option value="ST">ST - Scheduled Tribe</option>
                <option value="OC">OC - Open Category</option>
                <option value="BC(M)">BC(M) - Backward Class (Muslim)</option>
                <option value="MBC(M)">MBC(M) - Most Backward Class (Muslim)</option>
                <option value="BC(Christian)">BC(Christian) - Backward Class (Christian)</option>
                <option value="MBC(Christian)">MBC(Christian) - Most Backward Class (Christian)</option>
                <option value="Others">Others - Any other types</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="extraCaste" class="form-label">caste</label>
            <input type="text" name="otherCaste" id="otherCaste" class="form-control">
        </div>
        <div class="mb-3">
        <label for="religion" class="form-label">Religion</label>
        <select name="religion" id="religion" class="form-control" required>
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
                <input type="text" name="phone" id="phone" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    <?php elseif ($formType === 'academic' && !$academicSubmitted): ?>
        <!-- Academic Details Form -->
        <form method="post">
            <div class="mb-3">
                <label for="school_name" class="form-label">School Name</label>
                <input type="text" name="school_name" id="school_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="yearOfPassing" class="form-label">Year of Passing</label>
                <input type="number" name="yearOfPassing" id="yearOfPassing" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="tamil" class="form-label">Tamil Marks</label>
                <input type="number" name="tamil" id="tamil" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="english" class="form-label">English Marks</label>
                <input type="number" name="english" id="english" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="maths" class="form-label">Maths Marks</label>
                <input type="number" name="maths" id="maths" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="science" class="form-label">Science Marks</label>
                <input type="number" name="science" id="science" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="socialScience" class="form-label">Social Science Marks</label>
                <input type="number" name="socialScience" id="socialScience" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="otherLanguage" class="form-label">Other Language Marks</label>
                <input type="number" name="otherLanguage" id="otherLanguage" class="form-control">
            </div>
            <div class="total-marks" id="totalMarksDisplay">
            Total Marks: <span id="totalMarks">0</span>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <script>
    // Attach event listeners to each input field to trigger the total calculation
    function calculateTotal() {
        // Get all the subject marks values
        var tamil = parseInt(document.getElementById("tamil").value) || 0;
        var english = parseInt(document.getElementById("english").value) || 0;
        var maths = parseInt(document.getElementById("maths").value) || 0;
        var science = parseInt(document.getElementById("science").value) || 0;
        var socialScience = parseInt(document.getElementById("socialScience").value) || 0;
        var otherLanguage = parseInt(document.getElementById("otherLanguage").value) || 0;

        // Calculate total marks
        var totalMarks = tamil + english + maths + science + socialScience + otherLanguage;

        // Update total marks in the display
        document.getElementById("totalMarks").textContent = totalMarks;
    }

    // Event listeners to call calculateTotal whenever a value changes
    document.getElementById("tamil").addEventListener("input", calculateTotal);
    document.getElementById("english").addEventListener("input", calculateTotal);
    document.getElementById("maths").addEventListener("input", calculateTotal);
    document.getElementById("science").addEventListener("input", calculateTotal);
    document.getElementById("socialScience").addEventListener("input", calculateTotal);
    document.getElementById("otherLanguage").addEventListener("input", calculateTotal);
</script>

    <?php elseif ($formType === 'preference' && !$preferenceSubmitted): ?>
             <!-- Preference Form -->
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
        
    <?php elseif ($formType === 'document' && !$documentSubmitted): ?>
        <!-- Document Upload Form -->
        <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="aadhaar" class="form-label">Aadhaar Card</label>
        <input type="file" name="aadhaar" id="aadhaar" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="marksheet" class="form-label">Marksheet</label>
        <input type="file" name="marksheet" id="marksheet" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="photo" class="form-label">Photo</label>
        <input type="file" name="photo" id="photo" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Submit</button>
</form>

    <?php endif; ?>
</div>
</body>
</html>