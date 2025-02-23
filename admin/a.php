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

        // Header with logo
        $pdf->Image('./logo.png', 267, 10, 20, 20); // Right side logo (A4 width 297mm - 20mm logo - 10mm margin)
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'NPTC', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Merit List Report - Form A', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Address: 123 College Road, City', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Phone: +123-456-7890', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Email: info@nptc.edu', 0, 1, 'C');
        $pdf->Line(10, 35, 287, 35); // Horizontal line (A4 width 297mm - 10mm margins)

        // Table
        $html = '<table border="1" cellpadding="5"><thead><tr>';
        $columns = ['S.No', 'Name', 'Sex', 'Community', 'DOB', 'Qualification', 'Year of Passing', 'Tamil', 'English', 'Maths', 'Science', 'Social Science', 'Other Marks', 'Total', 'Average', 'Department 1', 'Department 2', 'Status'];
        foreach ($columns as $col) {
            $html .= "<th>$col</th>";
        }
        $html .= '</tr></thead><tbody>';
        foreach ($studentsData as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['sno']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['studentFirstName'] . ' ' . $row['studentLastName']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['sex']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['community']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['dob']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['qualify']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['yr_pass']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['tamilMarks']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['englishMarks']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['mathsMarks']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['scienceMarks']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['socialScienceMarks']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['otherLanguageMarks']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['totalMarks']) . '</td>';
            $html .= '<td>' . number_format($row['average'], 2) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['department1']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['department2']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['status']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Signatures (spanning table width)
        if (!empty($signatures)) {
            $pdf->Ln(10);
            $tableWidth = 277; // A4 landscape width 297mm - 20mm margins
            $sigCount = count($signatures);
            $sigPositions = [];
            if ($sigCount === 1) {
                $sigPositions = [287 - 20]; // Rightmost
            } elseif ($sigCount === 2) {
                $sigPositions = [10, 287 - 20]; // Leftmost and Rightmost
            } elseif ($sigCount >= 3) {
                $sigPositions = [10, 148.5, 287 - 20]; // Leftmost, Middle, Rightmost
            }

            foreach ($signatures as $idx => $sig) {
                if (isset($sigPositions[$idx])) {
                    $pdf->SetX($sigPositions[$idx]);
                    $pdf->Cell(0, 5, $sig, 0, 0, 'L');
                }
            }
        }

        // Output PDF (no footer)
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

        // Header with logo and centered text
        $sheet->setCellValue('A1', 'NPTC');
        $sheet->setCellValue('A2', 'Merit List Report - Form A');
        $sheet->setCellValue('A3', 'Address: 123 College Road, City');
        $sheet->setCellValue('A4', 'Phone: +123-456-7890');
        $sheet->setCellValue('A5', 'Email: info@nptc.edu');
        $sheet->mergeCells('A1:R1');
        $sheet->mergeCells('A2:R2');
        $sheet->mergeCells('A3:R3');
        $sheet->mergeCells('A4:R4');
        $sheet->mergeCells('A5:R5');

        // Center align header
        $sheet->getStyle('A1:R5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add logo to right side (R1)
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath('./logo.png'); // Logo path
        $drawing->setCoordinates('R1');
        $drawing->setWidth(50);
        $drawing->setHeight(50);
        $drawing->setWorksheet($sheet);

        // Table headers
        $columns = ['S.No', 'Name', 'Sex', 'Community', 'DOB', 'Qualification', 'Year of Passing', 'Tamil', 'English', 'Maths', 'Science', 'Social Science', 'Other Marks', 'Total', 'Average', 'Department 1', 'Department 2', 'Status'];
        $col = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($col . '7', $column);
            $col++;
        }

        // Table data
        $rowNum = 8;
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
            $sheet->setCellValue('P' . $rowNum, $row['department1']);
            $sheet->setCellValue('Q' . $rowNum, $row['department2']);
            $sheet->setCellValue('R' . $rowNum, $row['status']);
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
        $sheet->getStyle('A7:R' . ($rowNum - 1))->applyFromArray($styleArray);

        // Add two blank rows
        $rowNum += 2;

        // Signatures (spanning table width)
        if (!empty($signatures)) {
            $sigCount = count($signatures);
            $sigPositions = [];
            if ($sigCount === 1) {
                $sigPositions = ['R']; // Rightmost (Column 18)
            } elseif ($sigCount === 2) {
                $sigPositions = ['B', 'R']; // Leftmost and Rightmost (Columns 2 and 18)
            } elseif ($sigCount >= 3) {
                $sigPositions = ['B', 'I', 'R']; // Leftmost, Middle, Rightmost (Columns 2, 9, 18)
            }

            foreach ($signatures as $idx => $sig) {
                if (isset($sigPositions[$idx])) {
                    $sheet->setCellValue($sigPositions[$idx] . $rowNum, $sig);
                }
            }
        }

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