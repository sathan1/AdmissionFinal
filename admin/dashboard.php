<?php
include '../include/db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['userId']) || $_SESSION['userRole'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Ensure no output before header calls
ob_start(); // Start output buffering

// Variables
$adminId = $_SESSION['userId'];
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Query to fetch users with optional department and search filters
$query = "
SELECT sd.studentUserId, sd.studentFirstName, sd.studentLastName, sd.studentPhoneNumber,
       a.school_name, a.totalMarks,
       GROUP_CONCAT(DISTINCT p.preferenceDepartment ORDER BY p.preferenceOrder SEPARATOR ', ') AS preferenceDepartments,
       GROUP_CONCAT(DISTINCT p.preferenceStatus ORDER BY p.preferenceOrder SEPARATOR ', ') AS preferenceStatuses
FROM studentdetails sd
LEFT JOIN academic a ON sd.studentUserId = a.academicUserId
LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
WHERE 1=1
";

// Apply department filter
if (!empty($filterDepartment)) {
    $query .= " AND p.preferenceDepartment = ?";
}

// Apply search filter
if (!empty($searchQuery)) {
    $query .= " AND (sd.studentFirstName LIKE ? OR sd.studentLastName LIKE ? OR sd.studentPhoneNumber LIKE ? OR a.school_name LIKE ?)";
}

$query .= " GROUP BY sd.studentUserId ORDER BY sd.studentUserId ASC";

$stmt = $conn->prepare($query);

if (!empty($filterDepartment) && !empty($searchQuery)) {
    $searchPattern = "%" . $searchQuery . "%";
    $stmt->bind_param("sssss", $filterDepartment, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
} elseif (!empty($filterDepartment)) {
    $stmt->bind_param("s", $filterDepartment);
} elseif (!empty($searchQuery)) {
    $searchPattern = "%" . $searchQuery . "%";
    $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
}

$stmt->execute();
$allUsersResult = $stmt->get_result();

// Query to find users with incomplete forms excluding users already in All Users
$incompleteQuery = "
SELECT sd.studentUserId, sd.studentFirstName, sd.studentLastName
FROM studentdetails sd
LEFT JOIN academic a ON sd.studentUserId = a.academicUserId
LEFT JOIN document d ON sd.studentUserId = d.documentUserId
LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
WHERE (a.academicUserId IS NULL OR d.documentUserId IS NULL OR p.preferenceUserId IS NULL)
AND sd.studentUserId NOT IN (
    SELECT DISTINCT studentUserId 
    FROM studentdetails
    LEFT JOIN preference ON studentdetails.studentUserId = preference.preferenceUserId
)
GROUP BY sd.studentUserId
";

$incompleteResult = $conn->query($incompleteQuery);

// Include admin header AFTER starting buffer
include '../header_admin.php';

// End output buffering to prevent errors
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
form {
    margin-top: 20px;
}

form .form-control {
    border-radius: 5px;
    padding: 10px;
    border: 1px solid #ccc;
    transition: border-color 0.3s ease;
}

form .form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
}

form .btn {
    font-size: 0.9rem;
    font-weight: 600;
    padding: 10px 20px;
}

/* Dropdown */
select.form-select {
    max-width: 300px;
    margin: 10px auto;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ccc;
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
}

    </style>
</head>
<body>
<!-- Sidebar for larger screens -->
<nav class="sidebar d-none d-md-block">
    <h4 class="text-center mt-3">Student Forms</h4>
    <a href="dashboard.php">Dashboard</a>
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
        <h2 class="text-center">Admin Dashboard</h2>

        <!-- Action Buttons -->
        <div class="text-center my-3">
            <a href="request.php" class="btn btn-primary">View Detailed Requests</a>
            <a href="add_student.php" class="btn btn-success">Add Student</a>
        </div>

        <form method="GET" class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <label for="department" class="form-label me-2 mb-0">Filter by Department</label>
                <select name="department" id="department" class="form-select me-3" style="max-width: 300px;" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <option value="Civil Engineering" <?= $filterDepartment === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                    <option value="Mechanical Engineering" <?= $filterDepartment === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                    <option value="Electrical and Electronics Engineering" <?= $filterDepartment === 'Electrical and Electronics Engineering' ? 'selected' : '' ?>>Electrical and Electronics Engineering</option>
                    <option value="Electrical and Communication Engineering" <?= $filterDepartment === 'Electrical and Communication Engineering' ? 'selected' : '' ?>>Electrical and Communication Engineering</option>
                    <option value="Automobile Engineering" <?= $filterDepartment === 'Automobile Engineering' ? 'selected' : '' ?>>Automobile Engineering</option>
                    <option value="Textile Technology" <?= $filterDepartment === 'Textile Technology' ? 'selected' : '' ?>>Textile Technology</option>
                    <option value="Computer Technology" <?= $filterDepartment === 'Computer Technology' ? 'selected' : '' ?>>Computer Technology</option>
                    <option value="Printing Technology" <?= $filterDepartment === 'Printing Technology' ? 'selected' : '' ?>>Printing Technology</option>
                    <option value="Mechanical Engineering (R&AC)" <?= $filterDepartment === 'Mechanical Engineering (R&AC)' ? 'selected' : '' ?>>Mechanical Engineering (R&AC)</option>
                </select>
            </div>
            <div class="d-flex">
                <input type="text" name="search" class="form-control me-2" style="max-width: 300px;" placeholder="Search by name, phone, or school" value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit" class="btn btn-secondary">Search</button>
            </div>
        </form>

        <!-- User Table -->
        <h3>All Users</h3>
        <div class="table-container">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Phone Number</th>
                        <th>School</th>
                        <th>Total Marks</th>
                        <th>Preference Departments</th>
                        <th>Preference Statuses</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $serialNumber = 0; 
                    while ($row = $allUsersResult->fetch_assoc()): 
                        $serialNumber++;
                    ?>
                        <tr>
                            <td><?= $serialNumber ?></td>
                            <td><?= htmlspecialchars($row['studentFirstName']) ?></td>
                            <td><?= htmlspecialchars($row['studentLastName']) ?></td>
                            <td><?= htmlspecialchars($row['studentPhoneNumber']) ?></td>
                            <td><?= htmlspecialchars($row['school_name']) ?></td>
                            <td><?= htmlspecialchars($row['totalMarks']) ?></td>
                            <td><?= htmlspecialchars($row['preferenceDepartments']) ?></td>
                            <td><?= htmlspecialchars($row['preferenceStatuses']) ?></td>
                            <td>
                                <a href="request.php?view=<?= $row['studentUserId'] ?>" class="btn btn-info btn-sm">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>