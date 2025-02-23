<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: .../index.php");
    exit();
}

$adminId = $_SESSION['userId'];

// Fetch unique department names with department_status
$departmentQuery = "SELECT DISTINCT CONCAT(preferenceDepartment, ' (', department_status, ')') AS departmentFull 
                    FROM preference";
$departmentResult = $conn->query($departmentQuery);
$departments = [];
while ($dept = $departmentResult->fetch_assoc()) {
    $departments[] = $dept['departmentFull'];
}

// Get the selected department from the dropdown filter
$selectedDepartment = isset($_GET['department']) ? $_GET['department'] : 'All Departments';

// Fetch students based on the selected department
$query = "
SELECT sd.studentUserId, sd.studentFirstName, sd.studentLastName, sd.studentPhoneNumber, sd.studentGender, 
       sd.studentCaste, sd.studentDateOfBirth, a.school_name, a.yearOfPassing, a.tamilMarks, a.englishMarks, 
       a.mathsMarks, a.scienceMarks, a.socialScienceMarks, a.otherLanguageMarks, a.totalMarks, 
       p.preferenceId, p.preferenceDepartment, p.preferenceStatus, p.department_status
FROM studentdetails sd
LEFT JOIN academic a ON sd.studentUserId = a.academicUserId
LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
WHERE p.preferenceStatus = 'success'";

if ($selectedDepartment !== 'All Departments') {
    $query .= " AND CONCAT(p.preferenceDepartment, ' (', p.department_status, ')') = ?";
}
$query .= " ORDER BY sd.studentUserId, p.preferenceOrder ASC LIMIT 30";

$stmt = $conn->prepare($query);
if ($selectedDepartment !== 'All Departments') {
    $stmt->bind_param('s', $selectedDepartment);
}
$stmt->execute();
$allUsersResult = $stmt->get_result();

$studentsData = [];
$serialNumber = 1;
while ($row = $allUsersResult->fetch_assoc()) {
    $studentsData[] = [
        'sno' => $serialNumber++,
        'studentFirstName' => $row['studentFirstName'],
        'studentLastName' => $row['studentLastName'],
        'sex' => $row['studentGender'],
        'community' => $row['studentCaste'],
        'dob' => $row['studentDateOfBirth'],
        'qualify' => 'SSLC',
        'yr_pass' => $row['yearOfPassing'],
        'tamilMarks' => $row['tamilMarks'],
        'englishMarks' => $row['englishMarks'],
        'mathsMarks' => $row['mathsMarks'],
        'scienceMarks' => $row['scienceMarks'],
        'socialScienceMarks' => $row['socialScienceMarks'],
        'otherLanguageMarks' => $row['otherLanguageMarks'],
        'totalMarks' => $row['totalMarks'],
        'average' => $row['totalMarks'] / 5,
        'department' => $row['preferenceDepartment'],
        'allocated' => $row['department_status'],
        'status' => 'Admitted',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form B</title>
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

<?php include '../header_admin.php'; ?>

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
<div class="content">
    <div class="container mt-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col text-center">
                <h2>NPTC</h2>
                <p>Merit List Report</p>
                <p>Form B</p>
            </div>
        </div>

        <!-- Filter Section -->
        <form method="GET" class="mb-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select name="department" class="form-select">
                        <option value="All Departments" <?= $selectedDepartment === 'All Departments' ? 'selected' : '' ?>>
                            All Departments
                        </option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= $selectedDepartment === $dept ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>

        <!-- Table Section -->
        <h3>Merit List - <?= htmlspecialchars($selectedDepartment) ?> (Admitted Students)</h3>
        <?php if (count($studentsData) > 0): ?>
            <p><?= count($studentsData) ?> student(s) found.</p>
            <div class="table-container">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Name</th>
                            <th>Sex</th>
                            <th>Community</th>
                            <th>DOB</th>
                            <th>Qualification</th>
                            <th>Year of Passing</th>
                            <th>Tamil</th>
                            <th>English</th>
                            <th>Maths</th>
                            <th>Science</th>
                            <th>Social Science</th>
                            <th>Other Marks</th>
                            <th>Total</th>
                            <th>Average</th>
                            <th>Department</th>
                            <th>Allocated</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentsData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['sno']) ?></td>
                                <td><?= htmlspecialchars($row['studentFirstName'] . ' ' . $row['studentLastName']) ?></td>
                                <td><?= htmlspecialchars($row['sex']) ?></td>
                                <td><?= htmlspecialchars($row['community']) ?></td>
                                <td><?= htmlspecialchars($row['dob']) ?></td>
                                <td><?= htmlspecialchars($row['qualify']) ?></td>
                                <td><?= htmlspecialchars($row['yr_pass']) ?></td>
                                <td><?= htmlspecialchars($row['tamilMarks']) ?></td>
                                <td><?= htmlspecialchars($row['englishMarks']) ?></td>
                                <td><?= htmlspecialchars($row['mathsMarks']) ?></td>
                                <td><?= htmlspecialchars($row['scienceMarks']) ?></td>
                                <td><?= htmlspecialchars($row['socialScienceMarks']) ?></td>
                                <td><?= htmlspecialchars($row['otherLanguageMarks']) ?></td>
                                <td><?= htmlspecialchars($row['totalMarks']) ?></td>
                                <td><?= number_format($row['average'], 2) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['allocated']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No results found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
