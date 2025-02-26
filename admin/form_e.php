<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch unique department names where department_status is MGMT or GOVT only
$departmentQuery = "SELECT DISTINCT preferenceDepartment, department_status 
                    FROM preference 
                    WHERE department_status IN ('MGMT', 'GOVT')";
$departmentResult = $conn->query($departmentQuery);
$departments = [];
while ($dept = $departmentResult->fetch_assoc()) {
    $departments[$dept['preferenceDepartment']][] = $dept['department_status'];
}

// Initialize table structure for accepted statuses only (MGMT and GOVT), using status as type
$tableData = [];
foreach ($departments as $department => $statuses) {
    foreach ($statuses as $status) {
        $tableData[$department][$status] = [
            'shift' => 'First',
            'Hindu' => ['boys' => 0, 'girls' => 0],
            'Muslim' => ['boys' => 0, 'girls' => 0],
            'Christian' => ['boys' => 0, 'girls' => 0],
            'Jain' => ['boys' => 0, 'girls' => 0],
            'Sikh' => ['boys' => 0, 'girls' => 0],
            'Buddhist' => ['boys' => 0, 'girls' => 0],
            'Others' => ['boys' => 0, 'girls' => 0],
            'total' => ['boys' => 0, 'girls' => 0],
            'side_total' => 0,
        ];
    }
}

// Fetch student data for MGMT or GOVT statuses only
$query = "
SELECT p.preferenceDepartment, p.department_status, 
       sd.studentGender, sd.studentReligion, COUNT(*) AS studentCount
FROM studentdetails sd
LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
WHERE p.preferenceStatus = 'success' AND p.department_status IN ('MGMT', 'GOVT')
GROUP BY p.preferenceDepartment, p.department_status, sd.studentReligion, sd.studentGender";
$result = $conn->query($query);

// Populate table data for accepted statuses only (MGMT or GOVT)
while ($row = $result->fetch_assoc()) {
    $department = $row['preferenceDepartment'];
    $status = $row['department_status']; // MGMT or GOVT only
    $religion = $row['studentReligion'];
    $gender = strtolower($row['studentGender']);
    $count = $row['studentCount'];

    // Normalize gender values
    $gender = ($gender === 'male' || $gender === 'm') ? 'boys' : 'girls';

    // Handle religion categories, default to 'Others' if not listed
    if (!isset($tableData[$department][$status][$religion])) {
        $religion = 'Others';
    }

    if (isset($tableData[$department][$status])) {
        $tableData[$department][$status][$religion][$gender] += $count;
        $tableData[$department][$status]['total'][$gender] += $count;
        $tableData[$department][$status]['side_total'] += $count;
    }
}

// Initialize totals for bottom row
$overallTotals = [
    'Hindu' => ['boys' => 0, 'girls' => 0],
    'Muslim' => ['boys' => 0, 'girls' => 0],
    'Christian' => ['boys' => 0, 'girls' => 0],
    'Jain' => ['boys' => 0, 'girls' => 0],
    'Sikh' => ['boys' => 0, 'girls' => 0],
    'Buddhist' => ['boys' => 0, 'girls' => 0],
    'Others' => ['boys' => 0, 'girls' => 0],
    'total' => ['boys' => 0, 'girls' => 0],
    'side_total' => 0,
];

// Calculate totals for MGMT and GOVT only
foreach ($tableData as $deptData) {
    foreach ($deptData as $statusData) {
        foreach ($statusData as $category => $values) {
            if (is_array($values)) {
                $overallTotals[$category]['boys'] += $values['boys'];
                $overallTotals[$category]['girls'] += $values['girls'];
            }
        }
        $overallTotals['side_total'] += $statusData['side_total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form E</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
     <style>
/* Import Professional Fonts from Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@400;500&display=swap');

/* General Reset */
body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #f0f4f8, #d9e2ec); /* Subtle blue-gray gradient */
    color: #2d3748;
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
    background: linear-gradient(145deg, #4a90e2, #357abd); /* Professional blue gradient */
    color: #fff;
    border-right: 1px solid #357abd;
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    padding-top: 80px;
    transition: transform 0.3s ease;
}

.sidebar h4 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.6rem;
    font-weight: 600;
    color: #fff;
    text-align: center;
    padding: 1.2rem;
    margin-bottom: 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.sidebar a {
    color: #fff;
    text-decoration: none;
    padding: 15px 20px;
    display: block;
    font-weight: 500;
    font-family: 'Roboto', sans-serif;
    transition: background-color 0.3s ease, padding-left 0.3s ease;
}

.sidebar a:hover {
    background-color: rgba(255, 255, 255, 0.2);
    padding-left: 25px; /* Subtle indent on hover */
}

/* Mobile Sidebar (Off-Canvas) */
.mobile-menu-btn {
    display: none;
    position: fixed;
    top: 75px;
    left: 10px;
    z-index: 1100;
    background: linear-gradient(145deg, #4a90e2, #357abd);
    border: 1px solid #357abd;
    padding: 12px 18px;
    border-radius: 6px;
    color: #fff;
    font-size: 1.1rem;
    font-family: 'Roboto', sans-serif;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.mobile-menu-btn:hover {
    background: linear-gradient(145deg, #357abd, #2a6395);
    transform: scale(1.05);
}

#mobileMenu {
    position: fixed;
    top: 0;
    left: -250px;
    width: 250px;
    height: 100vh;
    background: linear-gradient(145deg, #4a90e2, #357abd);
    color: #fff;
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
    z-index: 1050;
    padding-top: 80px;
    transition: left 0.3s ease;
}

#mobileMenu.show {
    left: 0;
}

#mobileMenu a {
    color: #fff;
    text-decoration: none;
    padding: 15px 20px;
    display: block;
    font-weight: 500;
    font-family: 'Roboto', sans-serif;
    transition: background-color 0.3s ease, padding-left 0.3s ease;
}

#mobileMenu a:hover {
    background-color: rgba(255, 255, 255, 0.2);
    padding-left: 25px;
}

/* Header Styles */
.header {
    background: linear-gradient(145deg, #4a90e2, #357abd);
    border-bottom: 1px solid #357abd;
    padding: 15px 25px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1100;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header .title {
    font-family: 'Poppins', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
}

.header .logout-btn {
    background: linear-gradient(145deg, #e53e3e, #c53030);
    border: none;
    padding: 10px 20px;
    font-size: 1rem;
    font-family: 'Roboto', sans-serif;
    font-weight: 500;
    border-radius: 6px;
    color: #fff;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.header .logout-btn:hover {
    background: linear-gradient(145deg, #c53030, #9b2c2c);
    transform: translateY(-2px);
}

/* Content Area */
.content {
    margin-left: 250px;
    padding: 40px;
    margin-top: 80px;
    background: #fff;
    min-height: calc(100vh - 80px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    border-radius: 12px;
}

/* Headings */
h2, h4 {
    font-family: 'Poppins', sans-serif;
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 1.5rem;
    letter-spacing: 0.5px;
}

h2 {
    font-size: 2rem;
    background: linear-gradient(90deg, #4a90e2, #63b3ed);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

h4 {
    font-size: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 5px;
}

/* Buttons */
.btn-primary {
    background: linear-gradient(145deg, #68d391, #48bb78);
    border: none;
    font-family: 'Roboto', sans-serif;
    font-weight: 500;
    padding: 12px 20px;
    border-radius: 6px;
    color: #fff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(145deg, #48bb78, #38a169);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

/* Enhanced Table Styling */
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    margin-bottom: 2rem;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    background: #fff;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}

.table thead th {
    background: linear-gradient(145deg, #68d391, #48bb78);
    color: #fff;
    text-align: left;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    padding: 15px 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #38a169;
}

.table tbody tr {
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.table tbody tr:nth-child(even) {
    background-color: #f7fafc;
}

.table tbody tr:hover {
    background-color: #edf2f7;
    transform: translateY(-2px);
}

.table tbody td {
    padding: 15px 20px;
    border-bottom: 1px solid #e2e8f0;
    font-family: 'Roboto', sans-serif;
    font-weight: 400;
    color: #4a5568;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

/* Preference Table Specific */
.table-bordered thead th {
    background: linear-gradient(145deg, #63b3ed, #4299e1);
}

.table-bordered tbody td {
    vertical-align: middle;
}

.badge {
    padding: 8px 12px;
    font-size: 0.9rem;
    border-radius: 20px;
    font-family: 'Roboto', sans-serif;
    font-weight: 500;
}

/* Image Thumbnail */
.img-thumbnail {
    max-width: 150px;
    height: auto;
    cursor: pointer;
    border-radius: 8px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.img-thumbnail:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* Full-Screen Modal */
.fullscreen-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    overflow: auto;
    transition: opacity 0.3s ease;
    opacity: 0;
}

.fullscreen-modal.show {
    opacity: 1;
}

.fullscreen-modal img {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
}

.fullscreen-modal .close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #e53e3e;
    color: #fff;
    border: none;
    font-size: 24px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: background-color 0.3s ease;
}

.fullscreen-modal .close-btn:hover {
    background: #c53030;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        height: 100vh;
        width: 250px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .content {
        margin-left: 0;
        margin-top: 80px;
        padding: 20px;
        border-radius: 8px;
    }

    .mobile-menu-btn {
        display: block;
    }

    .header {
        padding: 10px 15px;
    }

    .table-wrapper {
        box-shadow: none;
    }

    .table {
        font-size: 0.9rem;
    }

    .table thead th, .table tbody td {
        padding: 10px;
    }

    .img-thumbnail {
        max-width: 100px;
    }

    .fullscreen-modal img {
        max-width: 95%;
        max-height: 85%;
    }

    h2 {
        font-size: 1.5rem;
    }

    h4 {
        font-size: 1.2rem;
    }

    .btn-primary {
        padding: 10px 15px;
    }
}
    </style>
</head>
<body>
  <div class="header">
        <h1 class="title animate__fadeIn">Admin Dashboard</h1>
        <a href="../logout.php" class="logout-btn animate__fadeIn">Logout</a>
    </div>


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
            <h2 class="text-center">NPTC</h2>
            <h4 class="text-center">Admission Statistics - Religion (2024-2025)</h4>
            
          <div class="table-wrapper">
    <table class="table">
                <thead class="thead-dark">
                    <tr>
                        <th rowspan="2">S.No</th>
                        <th rowspan="2">Department</th>
                        <th rowspan="2">Type</th>
                        <th rowspan="2">Shift</th>
                        <th colspan="2">Hindu</th>
                        <th colspan="2">Muslim</th>
                        <th colspan="2">Christian</th>
                        <th colspan="2">Jain</th>
                        <th colspan="2">Sikh</th>
                        <th colspan="2">Buddhist</th>
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
                    </tr>
                </thead>
                <tbody>
                    <?php $serial = 1; ?>
                    <?php foreach ($tableData as $dept => $statuses): ?>
                        <?php foreach ($statuses as $status => $data): ?>
                            <tr>
                                <td><?= $serial++ ?></td>
                                <td><?= $dept ?></td>
                                <td><?= $status ?></td> <!-- Displays "GOVT" or "MGMT" for the respective status -->
                                <td><?= $data['shift'] ?></td>
                                <?php foreach (['Hindu', 'Muslim', 'Christian', 'Jain', 'Sikh', 'Buddhist', 'Others', 'total'] as $category): ?>
                                    <td><?= $data[$category]['boys'] ?></td>
                                    <td><?= $data[$category]['girls'] ?></td>
                                <?php endforeach; ?>
                                <td><?= $data['side_total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    
                    <!-- Totals Row -->
                    <tr class="table-success">
                        <td colspan="4" class="text-center"><strong>Total</strong></td>
                        <?php foreach (['Hindu', 'Muslim', 'Christian', 'Jain', 'Sikh', 'Buddhist', 'Others', 'total'] as $category): ?>
                            <td><strong><?= $overallTotals[$category]['boys'] ?></strong></td>
                            <td><strong><?= $overallTotals[$category]['girls'] ?></strong></td>
                        <?php endforeach; ?>
                        <td><strong><?= $overallTotals['side_total'] ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    </div>
</body>
</html>