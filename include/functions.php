<?php
include 'db.php';

function checkFormSubmission($userId, $formType) {
    global $conn;
    // Optimized query to check if the user has already submitted the form
    $query = "SELECT COUNT(*) FROM $formType WHERE {$formType}UserId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    return $count > 0;
}

function createDocumentDirectory($dir) {
    // Simplified function to create the document directory if it doesn't exist
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true); // Ensure directory is created with proper permissions
    }
}

function getStudentStatus($userId) {
    global $conn;
    // Fetch the status of all form submissions for a user
    $status = [
        'details' => checkFormSubmission($userId, 'studentDetails') ? 'success' : 'pending',
        'academic' => checkFormSubmission($userId, 'academic') ? 'success' : 'pending',
        'preference' => checkFormSubmission($userId, 'preference') ? 'success' : 'pending',
        'document' => checkFormSubmission($userId, 'document') ? 'success' : 'pending'
    ];
    return $status;
}

function uploadDocument($userId, $documentType, $file) {
    global $conn;

    $targetDir = "../documents/";
    createDocumentDirectory($targetDir);

    // Check for file upload success and move the file to the desired directory
    $targetFile = $targetDir . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Insert the document details into the database
        $query = "INSERT INTO document (documentUserId, documentType, documentName) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $userId, $documentType, $file['name']);
        return $stmt->execute();  // Return true if the document insertion is successful
    } else {
        return false; // Return false if file upload fails
    }
}

?>
