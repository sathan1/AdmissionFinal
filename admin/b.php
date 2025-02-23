<?php
include '../include/db.php';
session_start();

// Declare use statements at the top for export
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php");
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

// Handle export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    require_once '../vendor/autoload.php';

    $format = $_POST['export_format'];
    $password = !empty($_POST['password']) ? $_POST['password'] : '';
    $signatures = !empty($_POST['signatures']) ? $_POST['signatures'] : [];

    if ($format === 'pdf') {
        require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('NPTC');
        $pdf->SetTitle('Merit List Report - Form B');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);

        if ($password) {
            $pdf->SetProtection(array('print'), $password, $password);
        }

        $pdf->AddPage();
        $pdf->SetLineStyle(array('width' => 0.5, 'color' => array(0, 0, 0))); // Black border #000
        $pdf->Rect(10, 10, 277, 190); // A4 landscape border

        $pdf->Image('./logo.png', 12, 12, 20, 20);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'ADMISSION TO FIRST YEAR (REGULAR) DIPLOMA COURSES: 2024 - 2025', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'FORM B – (Merit list of admitted students)', 0, 1, 'C');
        $pdf->Cell(0, 5, 'INSTITUTION CODE: 212', 0, 1, 'C');
        $pdf->Cell(0, 5, 'INSTITUTION NAME: NACHIMUTHU POLYTECHNIC COLLEGE (AUT), COIMBATORE', 0, 1, 'C');
        $pdf->Ln(5);

        $html = '<table border="1" cellpadding="5"><thead><tr style="background-color: #2980b9; color: #ffffff;">';
        $columns = ['S.No', 'NAME', 'SEX', 'COMMUNITY', 'DOB', 'QUALIFY', 'YR PASS', 'TAM', 'ENG', 'MATHS', 'SCI', 'SOC', 'OTHER', 'TOTAL', '%', 'DEPT', 'ALLOC', 'STATUS'];
        $widths = [6, 25, 6, 15, 12, 12, 10, 8, 8, 8, 8, 8, 8, 10, 8, 12, 12, 10]; // 18 columns, total 186 units
        $totalWidth = array_sum($widths);
        $scaleFactor = 277 / $totalWidth;
        $scaledWidths = array_map(fn($w) => $w * $scaleFactor, $widths);

        foreach ($columns as $idx => $col) {
            $html .= "<th width=\"" . $scaledWidths[$idx] . "mm\" align=\"center\">$col</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach ($studentsData as $row) {
            $html .= '<tr>';
            $html .= '<td width="' . $scaledWidths[0] . 'mm" align="center">' . htmlspecialchars($row['sno']) . '</td>';
            $html .= '<td width="' . $scaledWidths[1] . 'mm" align="center">' . htmlspecialchars($row['studentFirstName'] . ' ' . $row['studentLastName']) . '</td>';
            $html .= '<td width="' . $scaledWidths[2] . 'mm" align="center">' . htmlspecialchars($row['sex']) . '</td>';
            $html .= '<td width="' . $scaledWidths[3] . 'mm" align="center">' . htmlspecialchars($row['community']) . '</td>';
            $html .= '<td width="' . $scaledWidths[4] . 'mm" align="center">' . htmlspecialchars($row['dob']) . '</td>';
            $html .= '<td width="' . $scaledWidths[5] . 'mm" align="center">' . htmlspecialchars($row['qualify']) . '</td>';
            $html .= '<td width="' . $scaledWidths[6] . 'mm" align="center">' . htmlspecialchars($row['yr_pass']) . '</td>';
            $html .= '<td width="' . $scaledWidths[7] . 'mm" align="center">' . htmlspecialchars($row['tamilMarks']) . '</td>';
            $html .= '<td width="' . $scaledWidths[8] . 'mm" align="center">' . htmlspecialchars($row['englishMarks']) . '</td>';
            $html .= '<td width="' . $scaledWidths[9] . 'mm" align="center">' . htmlspecialchars($row['mathsMarks']) . '</td>';
            $html .= '<td width="' . $scaledWidths[10] . 'mm" align="center">' . htmlspecialchars($row['scienceMarks']) . '</td>';
            $html .= '<td width="' . $scaledWidths[11] . 'mm" align="center">' . htmlspecialchars($row['socialScienceMarks']) . '</td>';
            $html .= '<td width="' . $scaledWidths[12] . 'mm" align="center">' . htmlspecialchars($row['otherLanguageMarks']) . '</td>';
            $html .= '<td width="' . $scaledWidths[13] . 'mm" align="center">' . htmlspecialchars($row['totalMarks']) . '</td>';
            $html .= '<td width="' . $scaledWidths[14] . 'mm" align="center">' . number_format($row['average'], 2) . '</td>';
            $html .= '<td width="' . $scaledWidths[15] . 'mm" align="center">' . htmlspecialchars($row['department']) . '</td>';
            $html .= '<td width="' . $scaledWidths[16] . 'mm" align="center">' . htmlspecialchars($row['allocated']) . '</td>';
            $html .= '<td width="' . $scaledWidths[17] . 'mm" align="center">' . htmlspecialchars($row['status']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(10);
        if (!empty($signatures)) {
            $sigCount = count($signatures);
            $sigPositions = [];
            $tableWidth = 277;
            if ($sigCount === 1) {
                $sigPositions = [$tableWidth - 20];
            } elseif ($sigCount === 2) {
                $sigPositions = [10, $tableWidth - 20];
            } elseif ($sigCount >= 3) {
                $sigPositions = [10, $tableWidth / 2 - 20, $tableWidth - 20];
            }

            foreach ($signatures as $idx => $sig) {
                if (isset($sigPositions[$idx])) {
                    $pdf->SetX($sigPositions[$idx]);
                    $pdf->Cell(0, 5, $sig, 0, 0, 'L');
                }
            }
        }

        $pdf->Output('merit_list_form_b.pdf', 'D');
        exit;
    } elseif ($format === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageMargins()->setTop(10 / 25.4);
        $sheet->getPageMargins()->setRight(10 / 25.4);
        $sheet->getPageMargins()->setBottom(10 / 25.4);
        $sheet->getPageMargins()->setLeft(10 / 25.4);

        $sheet->setCellValue('A1', 'ADMISSION TO FIRST YEAR (REGULAR) DIPLOMA COURSES: 2024 - 2025');
        $sheet->setCellValue('A2', 'FORM B – (Merit list of admitted students)');
        $sheet->setCellValue('A3', 'INSTITUTION CODE: 212');
        $sheet->setCellValue('A4', 'INSTITUTION NAME: NACHIMUTHU POLYTECHNIC COLLEGE (AUT), COIMBATORE');
        $sheet->mergeCells('A1:R1');
        $sheet->mergeCells('A2:R2');
        $sheet->mergeCells('A3:R3');
        $sheet->mergeCells('A4:R4');

        $sheet->getStyle('A1:R4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath('./logo.png');
        $drawing->setCoordinates('A1');
        $drawing->setWidth(50);
        $drawing->setHeight(50);
        $drawing->setOffsetX(10);
        $drawing->setWorksheet($sheet);

        $columns = ['S.No', 'NAME', 'SEX', 'COMMUNITY', 'DOB', 'QUALIFY', 'YR PASS', 'TAM', 'ENG', 'MATHS', 'SCI', 'SOC', 'OTHER', 'TOTAL', '%', 'DEPT', 'ALLOC', 'STATUS'];
        $colWidths = [6, 25, 6, 15, 12, 12, 10, 8, 8, 8, 8, 8, 8, 10, 8, 12, 12, 10]; // 18 columns
        $col = 'A';
        foreach ($columns as $idx => $column) {
            $sheet->setCellValue($col . '6', $column);
            $sheet->getColumnDimension($col)->setWidth($colWidths[$idx]);
            $col++;
        }

        $sheet->getStyle('A6:R6')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2980B9'],
            ],
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
                'bold' => true,
            ],
        ]);

        $rowNum = 7;
        foreach ($studentsData as $row) {
            $sheet->setCellValue('A' . $rowNum, $row['sno']);
            $sheet->setCellValue('B' . $rowNum, $row['studentFirstName'] . ' ' . $row['studentLastName']);
            $sheet->setCellValue('C' . $rowNum, $row['sex']);
            $sheet->setCellValue('D' . $rowNum, $row['community']);
            $sheet->setCellValue('E' . $rowNum, $row['dob']);
            $sheet->setCellValue('F' . $rowNum, $row['qualify']);
            $sheet->setCellValue('G' . $rowNum, $row['yr_pass']);
            $sheet->setCellValue('H' . $rowNum, $row['tamilMarks']);
            $sheet->setCellValue('I' . $rowNum, $row['englishMarks']);
            $sheet->setCellValue('J' . $rowNum, $row['mathsMarks']);
            $sheet->setCellValue('K' . $rowNum, $row['scienceMarks']);
            $sheet->setCellValue('L' . $rowNum, $row['socialScienceMarks']);
            $sheet->setCellValue('M' . $rowNum, $row['otherLanguageMarks']);
            $sheet->setCellValue('N' . $rowNum, $row['totalMarks']);
            $sheet->setCellValue('O' . $rowNum, number_format($row['average'], 2));
            $sheet->setCellValue('P' . $rowNum, $row['department']);
            $sheet->setCellValue('Q' . $rowNum, $row['allocated']);
            $sheet->setCellValue('R' . $rowNum, $row['status']);
            $rowNum++;
        }

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A6:R' . ($rowNum - 1))->applyFromArray($styleArray);

        $rowNum += 2;
        if (!empty($signatures)) {
            $sigCount = count($signatures);
            $sigPositions = [];
            if ($sigCount === 1) {
                $sigPositions = ['R'];
            } elseif ($sigCount === 2) {
                $sigPositions = ['B', 'R'];
            } elseif ($sigCount >= 3) {
                $sigPositions = ['B', 'J', 'R'];
            }

            foreach ($signatures as $idx => $sig) {
                if (isset($sigPositions[$idx])) {
                    $sheet->setCellValue($sigPositions[$idx] . $rowNum, $sig);
                }
            }
        }

        $lastRow = $rowNum;
        $borderStyle = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'], // #000 border
                ],
            ],
        ];
        $sheet->getStyle('A1:R' . $lastRow)->applyFromArray($borderStyle);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="merit_list_form_b.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
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
            padding-top: 70px;
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
            margin-top: 70px;
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

            /* Stack table columns on small screens */
            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 10px;
            }

            .table tbody td {
                display: block;
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            .table tbody td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                text-align: left;
                font-weight: bold;
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
                                <td data-label="S.No"><?= htmlspecialchars($row['sno']) ?></td>
                                <td data-label="Name"><?= htmlspecialchars($row['studentFirstName'] . ' ' . $row['studentLastName']) ?></td>
                                <td data-label="Sex"><?= htmlspecialchars($row['sex']) ?></td>
                                <td data-label="Community"><?= htmlspecialchars($row['community']) ?></td>
                                <td data-label="DOB"><?= htmlspecialchars($row['dob']) ?></td>
                                <td data-label="Qualification"><?= htmlspecialchars($row['qualify']) ?></td>
                                <td data-label="Year of Passing"><?= htmlspecialchars($row['yr_pass']) ?></td>
                                <td data-label="Tamil"><?= htmlspecialchars($row['tamilMarks']) ?></td>
                                <td data-label="English"><?= htmlspecialchars($row['englishMarks']) ?></td>
                                <td data-label="Maths"><?= htmlspecialchars($row['mathsMarks']) ?></td>
                                <td data-label="Science"><?= htmlspecialchars($row['scienceMarks']) ?></td>
                                <td data-label="Social Science"><?= htmlspecialchars($row['socialScienceMarks']) ?></td>
                                <td data-label="Other Marks"><?= htmlspecialchars($row['otherLanguageMarks']) ?></td>
                                <td data-label="Total"><?= htmlspecialchars($row['totalMarks']) ?></td>
                                <td data-label="Average"><?= number_format($row['average'], 2) ?></td>
                                <td data-label="Department"><?= htmlspecialchars($row['department']) ?></td>
                                <td data-label="Allocated"><?= htmlspecialchars($row['allocated']) ?></td>
                                <td data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
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

<!-- Floating Export Button -->
<div class="export-btn" style="position: fixed; bottom: 20px; right: 20px; padding: 10px 20px; background-color: #3498db; color: white; border-radius: 5px; cursor: pointer;" onclick="showExportModal()">Export</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="exportForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="exportFormat" class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat" name="export_format">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="passwordCheck" name="password_check">
                        <label class="form-check-label" for="passwordCheck">Add Password Protection (PDF only)</label>
                    </div>
                    <div id="passwordField" class="mb-3" style="display: none;">
                        <label for="passwordInput" class="form-label">Password</label>
                        <input type="password" class="form-control" id="passwordInput" name="password">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="signatureCheck" name="signature_check">
                        <label class="form-check-label" for="signatureCheck">Add Signatures</label>
                    </div>
                    <div id="signatureFields" style="display: none;">
                        <label for="signatureCount" class="form-label">Number of Signatures</label>
                        <input type="number" class="form-control mb-2" id="signatureCount" name="signature_count" min="1">
                        <div id="signatureInputs"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="export">Export</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showExportModal() {
        $('#exportModal').modal('show');
    }

    $('#exportFormat').on('change', function() {
        const format = $(this).val();
        if (format === 'excel') {
            $('#passwordCheck').prop('disabled', true).prop('checked', false);
            $('#passwordField').hide();
        } else {
            $('#passwordCheck').prop('disabled', false);
        }
    });

    $('#passwordCheck').on('change', function() {
        $('#passwordField').toggle(this.checked);
    });

    $('#signatureCheck').on('change', function() {
        $('#signatureFields').toggle(this.checked);
        if (!this.checked) $('#signatureInputs').empty();
    });

    $('#signatureCount').on('change', function() {
        const count = parseInt(this.value) || 0;
        const container = $('#signatureInputs');
        container.empty();
        for (let i = 0; i < count; i++) {
            container.append(`
                <label class="form-label">Signature ${i + 1} Name</label>
                <input type="text" class="form-control mb-2" name="signatures[]">
            `);
        }
    });
</script>
</body>
</html>