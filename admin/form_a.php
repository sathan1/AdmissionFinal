<?php
include '../include/db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: ../auth/login.php");
    exit();
}

$adminId = $_SESSION['userId'];

// Fetch all users with the required details for Form A
$query = "
SELECT sd.studentUserId, sd.studentFirstName, sd.studentLastName, sd.studentPhoneNumber, sd.studentGender, sd.studentCaste, sd.studentDateOfBirth,
       a.school_name, a.yearOfPassing, a.tamilMarks, a.englishMarks, a.mathsMarks, a.scienceMarks, a.socialScienceMarks, a.otherLanguageMarks, a.totalMarks,
       p.preferenceId, p.preferenceDepartment, p.preferenceStatus
FROM studentdetails sd
LEFT JOIN academic a ON sd.studentUserId = a.academicUserId
LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
ORDER BY sd.studentUserId, p.preferenceOrder ASC";

$allUsersResult = $conn->query($query);

$studentsData = [];
$serialNumber = 1;
while ($row = $allUsersResult->fetch_assoc()) {
    // Check if the department preferences are already added to the array
    if (!isset($studentsData[$row['studentUserId']])) {
        $studentsData[$row['studentUserId']] = [
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
            'status' => 'Applied',
            'department1' => $row['preferenceDepartment'],  // Store the first preference department
            'department2' => '', // Initialize second department preference as empty
        ];
    } else {
        // Add second department preference if not already set
        if (empty($studentsData[$row['studentUserId']]['department2'])) {
            $studentsData[$row['studentUserId']]['department2'] = $row['preferenceDepartment'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form A</title>
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
<?php
include '../header_admin.php';
?>

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
        <a href="dashboard.php" class="text-white">Dashboard A</a>
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
    <p class="text-center">Merit List Report</p>
    <p class="text-center">Form A</p>

    <h3>Merit List (Prepared After Applications)</h3>
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
                    <th>Department 1</th>
                    <th>Department 2</th>
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
                        <td><?= htmlspecialchars($row['department1']) ?></td>
                        <td><?= htmlspecialchars($row['department2']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<!-- Floating Export Button -->
<div class="export-btn" onclick="exportToPDF()">Export to PDF</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape');

        // Dark black border
        const pageWidth = doc.internal.pageSize.width;
        const pageHeight = doc.internal.pageSize.height;
        doc.rect(10, 10, pageWidth - 20, pageHeight - 20, 'S');

        doc.setFontSize(18);
        doc.text("College Name", pageWidth / 2, 16, null, null, "center");
        doc.setFontSize(12);
        doc.text("Merit List Report - Form A", pageWidth / 2, 22, null, null, "center");

        const data = <?php echo json_encode($studentsData); ?>;
        const columns = [
            'S.No', 'Name', 'Sex', 'Community', 'DOB', 'Qualification', 'Year of Passing', 
            'Tamil', 'English', 'Maths', 'Science', 'Social Science', 'Other Marks', 'Total', 'Average', 'Status', 'Department 1', 'Department 2'
        ];

        doc.autoTable({
            startY: 30,
            head: [columns],
            body: data.map(row => [
                row.sno,
                `${row.studentFirstName} ${row.studentLastName}`,
                row.sex,
                row.community,
                row.dob,
                row.qualify,
                row.yr_pass,
                row.tamilMarks,
                row.englishMarks,
                row.mathsMarks,
                row.scienceMarks,
                row.socialScienceMarks,
                row.otherLanguageMarks,
                row.totalMarks,
                row.average.toFixed(2),
                row.status,
                row.department1,
                row.department2,
            ]),
            theme: 'grid',
            headStyles: { fillColor: [0, 0, 0], textColor: 255 },
            bodyStyles: { textColor: 0 },
            alternateRowStyles: { fillColor: [240, 240, 240] },
            margin: { top: 35 },
        });

        doc.save("merit_list_form_a.pdf");
    }
</script>
</body>
</html>