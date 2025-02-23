<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['userId'];

// Fetch existing document details for the logged-in user
$query = "SELECT * FROM document WHERE documentUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch existing document data for the user
$existingDocuments = [];
while ($row = $result->fetch_assoc()) {
    $existingDocuments[$row['documentType']] = $row['documentName'];
}

// Handle file uploads
$targetDir = "../documents/";
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
            // If file already exists for this type, delete the old one first
            if (isset($existingDocuments[$docType])) {
                unlink($targetDir . $existingDocuments[$docType]);
            }
            $uploadedFiles[$docType] = $newFileName; // Store the uploaded file's name
        } else {
            $message .= "Error uploading $docType.<br>";
        }
    } else {
        // If file isn't uploaded, retain the old one
        if (isset($existingDocuments[$docType])) {
            $uploadedFiles[$docType] = $existingDocuments[$docType];
        } else {
            $message .= ucfirst($docType) . " file is missing.<br>";
        }
    }
}

// Check if all required files are uploaded or retained
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

header("Location: status.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Documents</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="aadhaar" class="form-label">Aadhaar Card</label>
            <input type="file" name="aadhaar" id="aadhaar" class="form-control" required>
            <?php
            if (isset($existingDocuments['aadhaar'])) {
                echo "<p>Current file: " . $existingDocuments['aadhaar'] . "</p>";
            }
            ?>
        </div>
        <div class="mb-3">
            <label for="marksheet" class="form-label">Marksheet</label>
            <input type="file" name="marksheet" id="marksheet" class="form-control" required>
            <?php
            if (isset($existingDocuments['marksheet'])) {
                echo "<p>Current file: " . $existingDocuments['marksheet'] . "</p>";
            }
            ?>
        </div>
        <div class="mb-3">
            <label for="photo" class="form-label">Photo</label>
            <input type="file" name="photo" id="photo" class="form-control" required>
            <?php
            if (isset($existingDocuments['photo'])) {
                echo "<p>Current file: " . $existingDocuments['photo'] . "</p>";
            }
            ?>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
</body>
</html>
