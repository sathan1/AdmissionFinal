<?php
include '../include/db.php';
session_start();

// Declare use statements at the top
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
    // Simplify gender to M/F
    $gender = $row['studentGender'] === 'M' ? 'M' : 'F';
    if (!isset($studentsData[$row['studentUserId']])) {
        $studentsData[$row['studentUserId']] = [
            'sno' => $serialNumber++,
            'studentFirstName' => $row['studentFirstName'],
            'studentLastName' => $row['studentLastName'],
            'sex' => $gender,
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
            'department1' => $row['preferenceDepartment'],
            'department2' => '',
        ];
    } else {
        if (empty($studentsData[$row['studentUserId']]['department2'])) {
            $studentsData[$row['studentUserId']]['department2'] = $row['preferenceDepartment'];
        }
    }
}

// Handle export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    require_once '../vendor/autoload.php';

    $format = $_POST['export_format'];
    $password = !empty($_POST['password']) ? $_POST['password'] : '';
    $signatures = !empty($_POST['signatures']) ? $_POST['signatures'] : [];

    if ($format === 'pdf') {
        // Use TCPDF for PDF
        require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('NPTC');
        $pdf->SetTitle('Merit List Report - Form A');
        $pdf->SetMargins(10, 10, 10); // 1 cm margins
        $pdf->SetAutoPageBreak(true, 10);

        // Password protection (only for PDF)
        if ($password) {
            $pdf->SetProtection(array('print'), $password, $password);
        }

        // Add a page
        $pdf->AddPage();

        // Add black border around the page
        $pdf->SetLineStyle(array('width' => 0.5, 'color' => array(0, 0, 0))); // #000 border
        $pdf->Rect(10, 10, 277, 190); // Rectangle for A4 landscape (297mm - 20mm margins, 210mm - 20mm margins)

        // Header with logo on left and centered text
        $pdf->Image('./logo.png', 12, 12, 20, 20); // Adjusted inside border
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'ADMISSION TO FIRST YEAR (REGULAR) DIPLOMA COURSES: 2024 - 2025', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'FORM A – (Merit list prepared after receiving all applications from prospective candidates before due date)', 0, 1, 'C');
        $pdf->Cell(0, 5, 'INSTITUTION CODE: 212', 0, 1, 'C');
        $pdf->Cell(0, 5, 'INSTITUTION NAME: NACHIMUTHU POLYTECHNIC COLLEGE (AUT), COIMBATORE', 0, 1, 'C');
        $pdf->Ln(5); // Space after header

        // Table with 16 columns and professional widths (total 277mm, matching Excel proportions)
        $html = '<table border="1" cellpadding="5"><thead><tr style="background-color: #ffffff; color: #000000;">';
        $columns = ['S.No', 'NAME', 'SEX', 'COMMUNITY', 'DOB', 'QUALIFY', 'YR PASS', 'TAM', 'ENG', 'MATHS', 'SCI', 'SOC', 'OTHER', 'TOTAL', '%', 'STATUS'];
        $widths = [6, 25, 6, 15, 12, 12, 10, 8, 8, 8, 8, 8, 8, 10, 8, 10]; // Proportional to Excel's 162 units, scaled to 277mm
        $totalWidth = array_sum($widths);
        $scaleFactor = 277 / $totalWidth; // Scale to fit A4 landscape (297mm - 20mm margins)
        $scaledWidths = array_map(fn($w) => $w * $scaleFactor, $widths);

        foreach ($columns as $idx => $col) {
            $html .= "<th width=\"" . $scaledWidths[$idx] . "mm\" align=\"center\">$col</th>";
        }
        $html .= '</tr></thead><tbody>';

        // Table data
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
            $html .= '<td width="' . $scaledWidths[15] . 'mm" align="center">' . htmlspecialchars($row['status']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Add two blank lines (matching Excel)
        $pdf->Ln(10);

        // Signatures (spanning table width, matching Excel positioning)
        if (!empty($signatures)) {
            $sigCount = count($signatures);
            $sigPositions = [];
            $tableWidth = 277; // A4 landscape width minus margins
            if ($sigCount === 1) {
                $sigPositions = [$tableWidth - 20]; // Rightmost (adjusted for signature width)
            } elseif ($sigCount === 2) {
                $sigPositions = [10, $tableWidth - 20]; // Leftmost and Rightmost
            } elseif ($sigCount >= 3) {
                $sigPositions = [10, $tableWidth / 2 - 20, $tableWidth - 20]; // Leftmost, Middle, Rightmost
            }

            foreach ($signatures as $idx => $sig) {
                if (isset($sigPositions[$idx])) {
                    $pdf->SetX($sigPositions[$idx]);
                    $pdf->Cell(0, 5, $sig, 0, 0, 'L');
                }
            }
        }

        // Output PDF
        $pdf->Output('merit_list_form_a.pdf', 'D');
        exit;
    } elseif ($format === 'excel') {
        // Use PhpSpreadsheet for Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set margins to 1 cm (10 mm)
        $sheet->getPageMargins()->setTop(10 / 25.4); // Convert mm to inches
        $sheet->getPageMargins()->setRight(10 / 25.4);
        $sheet->getPageMargins()->setBottom(10 / 25.4);
        $sheet->getPageMargins()->setLeft(10 / 25.4);

        // Header with logo on left and centered text
        $sheet->setCellValue('A1', 'ADMISSION TO FIRST YEAR (REGULAR) DIPLOMA COURSES: 2024 - 2025');
        $sheet->setCellValue('A2', 'FORM A – (Merit list prepared after receiving all applications from prospective candidates before due date)');
        $sheet->setCellValue('A3', 'INSTITUTION CODE: 212');
        $sheet->setCellValue('A4', 'INSTITUTION NAME: NACHIMUTHU POLYTECHNIC COLLEGE (AUT), COIMBATORE');
        $sheet->mergeCells('A1:P1');
        $sheet->mergeCells('A2:P2');
        $sheet->mergeCells('A3:P3');
        $sheet->mergeCells('A4:P4');

        // Center align header
        $sheet->getStyle('A1:P4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add logo to left side (A1)
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath('./logo.png'); // Logo path
        $drawing->setCoordinates('A1');
        $drawing->setWidth(50);
        $drawing->setHeight(50);
        $drawing->setOffsetX(10); // Adjust position slightly
        $drawing->setWorksheet($sheet);

        // Table headers with 16 columns and professional widths
        $columns = ['S.No', 'NAME', 'SEX', 'COMMUNITY', 'DOB', 'QUALIFY', 'YR PASS', 'TAM', 'ENG', 'MATHS', 'SCI', 'SOC', 'OTHER', 'TOTAL', '%', 'STATUS'];
        $colWidths = [6, 25, 6, 15, 12, 12, 10, 8, 8, 8, 8, 8, 8, 10, 8, 10]; // Total = 162 units
        $col = 'A';
        foreach ($columns as $idx => $column) {
            $sheet->setCellValue($col . '6', $column);
            $sheet->getColumnDimension($col)->setWidth($colWidths[$idx]);
            $col++;
        }

        // Style header with white background and black text
        $sheet->getStyle('A6:P6')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'font' => [
                'color' => ['rgb' => '000000'],
                'bold' => true,
            ],
        ]);

        // Table data with matching widths
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
            $sheet->setCellValue('P' . $rowNum, $row['status']);
            $rowNum++;
        }

        // Apply borders to table
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A6:P' . ($rowNum - 1))->applyFromArray($styleArray);

        // Add two blank rows
        $rowNum += 2;

        // Signatures (spanning table width)
        if (!empty($signatures)) {
            $sigCount = count($signatures);
            $sigPositions = [];
            if ($sigCount === 1) {
                $sigPositions = ['P']; // Rightmost (Column 16)
            } elseif ($sigCount === 2) {
                $sigPositions = ['B', 'P']; // Leftmost and Rightmost (Columns 2 and 16)
            } elseif ($sigCount >= 3) {
                $sigPositions = ['B', 'I', 'P']; // Leftmost, Middle, Rightmost (Columns 2, 9, 16)
            }

            foreach ($signatures as $idx => $sig) {
                if (isset($sigPositions[$idx])) {
                    $sheet->setCellValue($sigPositions[$idx] . $rowNum, $sig);
                }
            }
        }

        // Add black border around the entire content area
        $lastRow = $rowNum; // Last row including signatures
        $borderStyle = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'], // #000 border
                ],
            ],
        ];
        $sheet->getStyle('A1:P' . $lastRow)->applyFromArray($borderStyle);

        // Output Excel (no password for Excel, controlled via UI)
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="merit_list_form_a.xlsx"');
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
            background-color: #ffffff;
            color: #000000;
            text-align: center;
            font-weight: 700;
            padding: 15px;
            border-bottom: 2px solid #ddd;
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

        /* Professional Column Widths for 16 Columns (Headers and Records) */
        .col-sno { width: 4%; }
        .col-name { width: 14%; }
        .col-sex { width: 4%; }
        .col-community { width: 9%; }
        .col-dob { width: 7%; }
        .col-qualify { width: 7%; }
        .col-yrpass { width: 6%; }
        .col-tamil { width: 5%; }
        .col-english { width: 5%; }
        .col-maths { width: 5%; }
        .col-science { width: 5%; }
        .col-social { width: 5%; }
        .col-other { width: 5%; }
        .col-total { width: 6%; }
        .col-average { width: 6%; }
        .col-status { width: 6%; }

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

            /* Stack columns on small screens */
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
        <h2 class="text-center">NPTC</h2>
        <p class="text-center">Merit List Report</p>
        <p class="text-center">Form A</p>

        <h3>Merit List (Prepared After Applications)</h3>
        <div class="table-container">
            <table class="table table-bordered table-striped" id="meritTable">
                <thead>
                    <tr>
                        <th class="col-sno">S.No</th>
                        <th class="col-name">Name</th>
                        <th class="col-sex">Sex</th>
                        <th class="col-community">Community</th>
                        <th class="col-dob">DOB</th>
                        <th class="col-qualify">Qualification</th>
                        <th class="col-yrpass">Year of Passing</th>
                        <th class="col-tamil">Tamil</th>
                        <th class="col-english">English</th>
                        <th class="col-maths">Maths</th>
                        <th class="col-science">Science</th>
                        <th class="col-social">Social Science</th>
                        <th class="col-other">Other Marks</th>
                        <th class="col-total">Total</th>
                        <th class="col-average">Average</th>
                        <th class="col-status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentsData as $row): ?>
                        <tr>
                            <td class="col-sno" data-label="S.No"><?= htmlspecialchars($row['sno']) ?></td>
                            <td class="col-name" data-label="Name"><?= htmlspecialchars($row['studentFirstName'] . ' ' . $row['studentLastName']) ?></td>
                            <td class="col-sex" data-label="Sex"><?= htmlspecialchars($row['sex']) ?></td>
                            <td class="col-community" data-label="Community"><?= htmlspecialchars($row['community']) ?></td>
                            <td class="col-dob" data-label="DOB"><?= htmlspecialchars($row['dob']) ?></td>
                            <td class="col-qualify" data-label="Qualification"><?= htmlspecialchars($row['qualify']) ?></td>
                            <td class="col-yrpass" data-label="Year of Passing"><?= htmlspecialchars($row['yr_pass']) ?></td>
                            <td class="col-tamil" data-label="Tamil"><?= htmlspecialchars($row['tamilMarks']) ?></td>
                            <td class="col-english" data-label="English"><?= htmlspecialchars($row['englishMarks']) ?></td>
                            <td class="col-maths" data-label="Maths"><?= htmlspecialchars($row['mathsMarks']) ?></td>
                            <td class="col-science" data-label="Science"><?= htmlspecialchars($row['scienceMarks']) ?></td>
                            <td class="col-social" data-label="Social Science"><?= htmlspecialchars($row['socialScienceMarks']) ?></td>
                            <td class="col-other" data-label="Other Marks"><?= htmlspecialchars($row['otherLanguageMarks']) ?></td>
                            <td class="col-total" data-label="Total"><?= htmlspecialchars($row['totalMarks']) ?></td>
                            <td class="col-average" data-label="Average"><?= number_format($row['average'], 2) ?></td>
                            <td class="col-status" data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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