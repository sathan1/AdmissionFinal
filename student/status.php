<?php
// Start the session
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
include '../include/db.php';

// Initialize variables
$userId = $_SESSION['userId'];
$showEditButtons = false; // Flag to show/hide edit buttons

// Initialize data arrays
$studentData = [];
$academicData = [];
$documents = [];
$preferences = [];
$bankingData = [];

// Fetch Student Details
$studentStmt = $conn->prepare("SELECT * FROM studentdetails WHERE studentUserId = ?");
$studentStmt->bind_param("i", $userId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows > 0) {
    $studentData = $studentResult->fetch_assoc();
}

// Fetch Academic Details
$academicStmt = $conn->prepare("SELECT * FROM academic WHERE academicUserId = ?");
$academicStmt->bind_param("i", $userId);
$academicStmt->execute();
$academicResult = $academicStmt->get_result();

if ($academicResult->num_rows > 0) {
    $academicData = $academicResult->fetch_assoc();
}

// Fetch Banking Details
$bankStmt = $conn->prepare("SELECT * FROM bankingdetails WHERE bankingUserId = ?");
$bankStmt->bind_param("i", $userId);
$bankStmt->execute();
$bankResult = $bankStmt->get_result();

if ($bankResult->num_rows > 0) {
    $bankingData = $bankResult->fetch_assoc();
}

// Fetch Documents
$docStmt = $conn->prepare("SELECT documentType, documentName FROM document WHERE documentUserId = ?");
$docStmt->bind_param("i", $userId);
$docStmt->execute();
$docResult = $docStmt->get_result();

while ($doc = $docResult->fetch_assoc()) {
    $documents[$doc['documentType']] = $doc['documentName'];
}

// Fetch Preferences
$prefStmt = $conn->prepare("SELECT * FROM preference 
                          WHERE preferenceUserId = ? 
                          ORDER BY preferenceOrder");
$prefStmt->bind_param("i", $userId);
$prefStmt->execute();
$prefResult = $prefStmt->get_result();

while ($pref = $prefResult->fetch_assoc()) {
    $preferences[] = [
        'order' => $pref['preferenceOrder'],
        'department' => $pref['preferenceDepartment'],
        'status' => $pref['preferenceStatus']
    ];
    if ($pref['preferenceStatus'] === 'reset') {
        $showEditButtons = true;
    }
}

// Fetch Form Status and Message
$formStatus = strtolower($studentData['form_status'] ?? 'pending');
$statusMessage = $studentData['status_message'] ?? '';

// Determine status alert style
$statusClass = 'secondary';
switch ($formStatus) {
    case 'success': $statusClass = 'success'; break;
    case 'rejected': $statusClass = 'danger'; break;
    case 'pending': $statusClass = 'warning'; break;
    case 'reset': $statusClass = 'info'; break;
    default: $statusClass = 'secondary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - College Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            background: linear-gradient(45deg, #a2cffe, #f5f7fa); /* Soft gradient: light blue to light gray */
            padding: 2rem;
            color: #2c3e50;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-card {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .section-card:last-child {
            border-bottom: none;
        }

        .status-alert {
            border-radius: 10px;
            border-left: 5px solid;
            margin: 1rem auto;
            max-width: 1200px;
        }

        .status-alert strong {
            text-transform: capitalize;
        }

        .detail-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .detail-item h5 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .detail-item p {
            margin-bottom: 0;
            color: #555;
        }

        .list-group-item {
            border: none;
            border-bottom: 1px solid #eee;
            padding: 1rem;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        /* Ensure text readability on gradient background */
        .profile-header h1 {
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
<?php include '../header_student.php'; ?>

<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header text-center">
        <?php if(isset($documents['photo'])): ?>
            <img src="../documents/<?= htmlspecialchars($documents['photo']) ?>?t=<?= filemtime('../documents/' . $documents['photo']) ?>" 
                 class="profile-photo" 
                 alt="Profile Photo">
        <?php endif; ?>
        <h1 class="mt-3 mb-0">
            <?= htmlspecialchars($studentData['studentFirstName'] ?? '') ?> 
            <?= htmlspecialchars($studentData['studentLastName'] ?? '') ?>
        </h1>
    </div>

    <!-- Status Alert -->
    <?php if ($statusMessage): ?>
        <div class="alert alert-<?= $statusClass ?> status-alert">
            <strong><?= htmlspecialchars($formStatus) ?></strong>: <?= htmlspecialchars($statusMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="row g-0">
        <!-- Left Column - All Details -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">Personal Information</h2>
                    <?php if($showEditButtons): ?>
                        <a href="edit_personal.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <?php 
                    $personalFields = [
                        'First Name' => 'studentFirstName',
                        'Last Name' => 'studentLastName',
                        'Date of Birth' => 'studentDateOfBirth',
                        'Contact Number' => 'studentPhoneNumber',
                        "Father's Name" => 'studentFatherName',
                        "Mother's Name" => 'studentMotherName',
                        'Gender' => 'studentGender',
                        'Caste' => 'studentCaste',
                        'Other Caste' => 'studentCaste_2',
                        'Religion' => 'studentReligion',
                        'Mother Tongue' => 'studentMotherTongue',
                        'Aadhar Number' => 'studentAadharNumber',
                        'Address' => 'studentAddress',
                        'City' => 'studentCity',
                        'State' => 'studentState',
                        'PIN Code' => 'studentPinCode'
                    ];
                    
                    foreach ($personalFields as $label => $field): ?>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <h5><?= $label ?></h5>
                                <p><?= htmlspecialchars($studentData[$field] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Academic Details -->
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">Academic Details</h2>
                    <?php if($showEditButtons): ?>
                        <a href="edit_academic.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <h5>School Name</h5>
                            <p><?= htmlspecialchars($academicData['school_name'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <h5>Year of Passing</h5>
                            <p><?= htmlspecialchars($academicData['yearOfPassing'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    
                        <div class="col-md-6">
                            <div class="detail-item">
                                <h5>EMIS Number</h5>
                                <p><?= htmlspecialchars($academicData['emisNumber'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    
                    <?php 
                    $academicMarks = [
                        'Tamil' => 'tamilMarks',
                        'English' => 'englishMarks',
                        'Maths' => 'mathsMarks',
                        'Science' => 'scienceMarks',
                        'Social Science' => 'socialScienceMarks',
                        'Other Language' => 'otherLanguageMarks'
                    ];
                    
                    foreach ($academicMarks as $subject => $markField): ?>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <h5><?= $subject ?> Marks</h5>
                                <p><?= htmlspecialchars($academicData[$markField] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="col-12">
                        <div class="detail-item">
                            <h5>Academic Performance</h5>
                            <div class="progress" style="height: 10px;">
                            <?php 
                                $totalMarks = 0;
                                foreach (['tamilMarks', 'englishMarks', 'mathsMarks', 'scienceMarks', 'socialScienceMarks', 'otherLanguageMarks'] as $mark) {
                                    $totalMarks += $academicData[$mark] ?? 0;
                                }
                                $percentage = ($totalMarks / 600) * 100;
                            ?>
                                <div class="progress-bar bg-primary" 
                                     style="width: <?= $percentage ?>%"></div>
                            </div>
                            <p class="mt-2"><?= number_format($percentage, 2) ?>%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banking Details -->
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">Optional datas</h2>
                    <?php if($showEditButtons): ?>
                        <a href="edit_banking.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <?php 
                    $bankingFields = [
                        'Account Number' => 'accountNumber',
                        'Bank Name' => 'bankName',
                        'Branch' => 'branch',
                        'IFSC Code' => 'ifsc',
                        'PAN Number' => 'panNumber',
                        'Driving License Number' => 'drivingLicenseNumber'
                    ];
                    
                    foreach ($bankingFields as $label => $field): ?>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <h5><?= $label ?></h5>
                                <p><?= htmlspecialchars($bankingData[$field] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4 border-start">
            <!-- Preferences -->
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">Department Preferences</h2>
                    <?php if($showEditButtons): ?>
                        <a href="edit_preferences.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="list-group">
                    <?php foreach ($preferences as $pref): ?>
                        <div class="list-group-item">
                            <h5><?= htmlspecialchars($pref['department']) ?></h5>
                            <p class="mb-1">Priority: <?= $pref['order'] ?></p>
                            <span class="badge <?= $pref['status'] === 'confirmed' ? 'bg-success' : 'bg-warning' ?>">
                                <?= htmlspecialchars($pref['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($preferences)): ?>
                        <div class="list-group-item">
                            <p class="text-muted">No preferences submitted yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Documents -->
            <div class="section-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary">Uploaded Documents</h2>
                    <?php if($showEditButtons): ?>
                        <a href="edit_documents.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    <?php endif; ?>
                </div>

                <div class="row g-3">
                    <?php if (!empty($documents)): ?>
                        <?php foreach ($documents as $type => $filename): ?>
                            <div class="col-6">
                                <div class="card h-100">
                                    <div class="card-body text-center p-3">
                                        <?php if(preg_match('/\.(jpg|jpeg|png)$/i', $filename)): ?>
                                            <img src="../documents/<?= htmlspecialchars($filename) ?>?t=<?= filemtime('../documents/' . $filename) ?>" 
                                                 class="img-fluid rounded mb-2"
                                                 alt="<?= ucfirst(str_replace('_', ' ', $type)) ?>"
                                                 style="max-height: 120px;">
                                        <?php else: ?>
                                            <div class="display-4 text-muted mb-2">📄</div>
                                        <?php endif; ?>
                                        <small class="text-muted d-block text-uppercase fw-bold">
                                            <?= ucfirst(str_replace('_', ' ', $type)) ?>
                                        </small>
                                        <a href="../documents/<?= htmlspecialchars($filename) ?>" 
                                           target="_blank" 
                                           class="btn btn-link btn-sm text-primary">
                                            View Full
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p class="text-muted">No documents uploaded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Button (if applicable) -->
    <?php if ($showEditButtons): ?>
        <div class="text-center mt-4 mb-4">
            <a href="update_status.php" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise"></i> Update Status
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>