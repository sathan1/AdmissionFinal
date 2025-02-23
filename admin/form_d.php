<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch departments with status
$departmentQuery = "SELECT DISTINCT preferenceDepartment, department_status FROM preference";
$departmentResult = $conn->query($departmentQuery);
$departments = [];
while ($dept = $departmentResult->fetch_assoc()) {
    $departments[$dept['preferenceDepartment']][] = $dept['department_status'];
}

// Initialize table structure
$tableData = [];
foreach ($departments as $deptName => $statuses) {
    foreach ($statuses as $status) {
        $tableData[$deptName][$status] = [
            'shift' => 'First',
            'Tamil' => ['boys' => 0, 'girls' => 0],
            'Telugu' => ['boys' => 0, 'girls' => 0],
            'Kannada' => ['boys' => 0, 'girls' => 0],
            'Sowrashtra' => ['boys' => 0, 'girls' => 0],
            'Malayalam' => ['boys' => 0, 'girls' => 0],
            'Hindi' => ['boys' => 0, 'girls' => 0],
            'Urdu' => ['boys' => 0, 'girls' => 0],
            'Others' => ['boys' => 0, 'girls' => 0],
            'total' => ['boys' => 0, 'girls' => 0],
            'side_total' => 0,
        ];
    }
}

// Fetch student data
$query = "SELECT p.preferenceDepartment, p.department_status, 
          sd.studentGender, sd.studentMotherTongue, COUNT(*) AS studentCount
          FROM studentdetails sd
          LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
          WHERE p.preferenceStatus = 'success'
          GROUP BY p.preferenceDepartment, p.department_status, sd.studentMotherTongue, sd.studentGender";
$result = $conn->query($query);

// Populate table data
while ($row = $result->fetch_assoc()) {
    $department = $row['preferenceDepartment'];
    $status = $row['department_status'];
    $motherTongue = $row['studentMotherTongue'];
    $gender = strtolower($row['studentGender']);
    $count = $row['studentCount'];

    // Normalize gender values
    $gender = ($gender === 'male' || $gender === 'm') ? 'boys' : 'girls';
    
    // Handle mother tongue categories
    if (!isset($tableData[$department][$status][$motherTongue])) {
        $motherTongue = 'Others';
    }

    if (isset($tableData[$department][$status])) {
        $tableData[$department][$status][$motherTongue][$gender] += $count;
        $tableData[$department][$status]['total'][$gender] += $count;
        $tableData[$department][$status]['side_total'] += $count;
    }
}

// Initialize totals
$totals = [
    'Tamil' => ['boys' => 0, 'girls' => 0],
    'Telugu' => ['boys' => 0, 'girls' => 0],
    'Kannada' => ['boys' => 0, 'girls' => 0],
    'Sowrashtra' => ['boys' => 0, 'girls' => 0],
    'Malayalam' => ['boys' => 0, 'girls' => 0],
    'Hindi' => ['boys' => 0, 'girls' => 0],
    'Urdu' => ['boys' => 0, 'girls' => 0],
    'Others' => ['boys' => 0, 'girls' => 0],
    'total' => ['boys' => 0, 'girls' => 0],
    'side_total' => 0,
];

// Calculate totals
foreach ($tableData as $deptData) {
    foreach ($deptData as $statusData) {
        foreach ($statusData as $category => $values) {
            if (is_array($values)) {
                $totals[$category]['boys'] += $values['boys'];
                $totals[$category]['girls'] += $values['girls'];
            }
        }
        $totals['side_total'] += $statusData['side_total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form D</title>
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
!-- Sidebar for larger screens -->
<nav class="sidebar d-none d-md-block">
    <h4 class="text-center mt-3">Student Forms</h4>
    <a href="dashboard.php">Dashboard</a>
    <a href="form_a.php">Form A</a>
    <a href="form_b.php">Form B</a>
    <a href="form_c.php">Form C</a>
    <a href="form_d.php">Form D</a>
    <a href="form_e.php">Form E</a>
</nav>
<div class="content">
    <div class="container mt-4">
        <h2 class="text-center">NPTC</h2>
        <h4 class="text-center">Admission Statistics - Mother Tongue (2024-2025)</h4>
        
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th rowspan="2">S.No</th>
                    <th rowspan="2">Department</th>
                    <th rowspan="2">Type</th>
                    <th rowspan="2">Shift</th>
                    <th colspan="2">Tamil</th>
                    <th colspan="2">Telugu</th>
                    <th colspan="2">Kannada</th>
                    <th colspan="2">Sowrashtra</th>
                    <th colspan="2">Malayalam</th>
                    <th colspan="2">Hindi</th>
                    <th colspan="2">Urdu</th>
                    <th colspan="2">Others</th>
                    <th colspan="2">Total</th>
                    <th rowspan="2">Overall Total</th>
                </tr>
                <tr>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                    <th>B</th><th>G</th>
                </tr>
            </thead>
            <tbody>
                <?php $serial = 1; ?>
                <?php foreach ($tableData as $dept => $statuses): ?>
                    <?php foreach ($statuses as $status => $data): ?>
                        <tr>
                            <td><?= $serial++ ?></td>
                            <td><?= $dept ?></td>
                            <td><?= $status ?></td>
                            <td><?= $data['shift'] ?></td>
                            <?php foreach (['Tamil', 'Telugu', 'Kannada', 'Sowrashtra', 'Malayalam', 'Hindi', 'Urdu', 'Others', 'total'] as $category): ?>
                                <td><?= $data[$category]['boys'] ?></td>
                                <td><?= $data[$category]['girls'] ?></td>
                            <?php endforeach; ?>
                            <td><?= $data['side_total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                
                <!-- Totals Row -->
                <tr class="table-primary">
                    <td colspan="4" class="text-center"><strong>Total</strong></td>
                    <?php foreach (['Tamil', 'Telugu', 'Kannada', 'Sowrashtra', 'Malayalam', 'Hindi', 'Urdu', 'Others', 'total'] as $category): ?>
                        <td><strong><?= $totals[$category]['boys'] ?></strong></td>
                        <td><strong><?= $totals[$category]['girls'] ?></strong></td>
                    <?php endforeach; ?>
                    <td><strong><?= $totals['side_total'] ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>