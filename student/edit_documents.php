<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = $_SESSION['userId'];
$message = "";

// Fetch existing document details for the logged-in user
$query = "SELECT * FROM document WHERE documentUserId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch existing document data for the user
$existingDocuments = [];
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $existingDocuments = json_decode($row['documentName'], true) ?? [];
}

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetDir = "../documents/";
    $allowedTypes = ['aadhaar', 'marksheet', 'photo'];
    $uploadedFiles = $existingDocuments; // Start with existing files

    foreach ($allowedTypes as $docType) {
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
            $newFileName = $docType . '_' . uniqid() . '.' . $fileExtension;
            $targetFilePath = $targetDir . $newFileName;

            // Ensure the upload directory exists
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            // Move the uploaded file to the target directory
            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                // If file already exists for this type, delete the old one first
                if (isset($existingDocuments[$docType]) && file_exists($targetDir . $existingDocuments[$docType])) {
                    unlink($targetDir . $existingDocuments[$docType]);
                }
                $uploadedFiles[$docType] = $newFileName; // Store the uploaded file's name
            } else {
                $message .= "Error uploading $docType.<br>";
            }
        }
    }

    // Update the database with the new file names
    if (count($uploadedFiles) > 0) {
        $documentNames = json_encode($uploadedFiles); // Encode file names as JSON

        // Check if a record already exists for the user
        $checkQuery = "SELECT * FROM document WHERE documentUserId = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Update existing record
            $updateQuery = "UPDATE document SET documentName = ? WHERE documentUserId = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $documentNames, $userId);
            if ($updateStmt->execute()) {
                $message = "Documents updated successfully.";
            } else {
                $message = "Error updating documents in the database.";
            }
        } else {
            // Insert new record
            $insertQuery = "INSERT INTO document (documentUserId, documentType, documentName) VALUES (?, 'documents', ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("is", $userId, $documentNames);
            if ($insertStmt->execute()) {
                $message = "Documents uploaded successfully.";
            } else {
                $message = "Error saving documents to the database.";
            }
        }
    } else {
        $message = "No files were uploaded.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
        .current-file {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Edit Documents</h2>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="aadhaar" class="form-label">Aadhaar Card</label>
            <input type="file" name="aadhaar" id="aadhaar" class="form-control">
            <?php if (isset($existingDocuments['aadhaar'])): ?>
                <div class="current-file">Current file: <?= $existingDocuments['aadhaar'] ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="marksheet" class="form-label">Marksheet</label>
            <input type="file" name="marksheet" id="marksheet" class="form-control">
            <?php if (isset($existingDocuments['marksheet'])): ?>
                <div class="current-file">Current file: <?= $existingDocuments['marksheet'] ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="photo" class="form-label">Photo</label>
            <input type="file" name="photo" id="photo" class="form-control">
            <?php if (isset($existingDocuments['photo'])): ?>
                <div class="current-file">Current file: <?= $existingDocuments['photo'] ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Upload Documents</button>
        <a href="status.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>