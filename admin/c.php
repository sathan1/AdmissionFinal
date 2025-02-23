<?php
include '../include/db.php';
session_start();

// Declare use statements for export
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch all unique department names along with department_status (MGMT/GOVT)
$departmentQuery = "SELECT DISTINCT preferenceDepartment, department_status FROM preference";
$departmentResult = $conn->query($departmentQuery);
$departments = [];
while ($dept = $departmentResult->fetch_assoc()) {
    $departments[$dept['preferenceDepartment']][] = $dept['department_status'];
}

// Initialize table structure
$tableData = [];
foreach ($departments as $department => $statuses) {
    foreach ($statuses as $status) {
        $tableData[$department][$status] = [
            'shift' => 'First',
            'OC' => ['boys' => 0, 'girls' => 0],
            'BC' => ['boys' => 0, 'girls' => 0],
            'BCM' => ['boys' => 0, 'girls' => 0],
            'MBC' => ['boys' => 0, 'girls' => 0],
            'SCA' => ['boys' => 0, 'girls' => 0],
            'SC' => ['boys' => 0, 'girls' => 0],
            'ST' => ['boys' => 0, 'girls' => 0],
            'total' => ['boys' => 0, 'girls' => 0],
            'side_total' => 0,
        ];
    }
}

// Fetch student data
$query = "
SELECT p.preferenceDepartment, p.department_status, sd.studentGender, sd.studentCaste, COUNT(*) AS studentCount
FROM studentdetails sd
LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
WHERE p.preferenceStatus = 'success'
GROUP BY p.preferenceDepartment, p.department_status, sd.studentCaste, sd.studentGender";
$result = $conn->query($query);

// Populate table data
while ($row = $result->fetch_assoc()) {
    $department = $row['preferenceDepartment'];
    $status = $row['department_status'];
    $caste = $row['studentCaste'];
    $gender = strtolower($row['studentGender']);
    $count = $row['studentCount'];

    if ($gender === 'male' || $gender === 'm') {
        $gender = 'boys';
    } elseif ($gender === 'female' || $gender === 'f') {
        $gender = 'girls';
    } else {
        continue;
    }

    if (isset($tableData[$department][$status][$caste][$gender])) {
        $tableData[$department][$status][$caste][$gender] += $count;
        $tableData[$department][$status]['total'][$gender] += $count;
        $tableData[$department][$status]['side_total'] += $count;
    }
}

// Initialize totals for bottom row
$totals = [
    'OC' => ['boys' => 0, 'girls' => 0],
    'BC' => ['boys' => 0, 'girls' => 0],
    'BCM' => ['boys' => 0, 'girls' => 0],
    'MBC' => ['boys' => 0, 'girls' => 0],
    'SCA' => ['boys' => 0, 'girls' => 0],
    'SC' => ['boys' => 0, 'girls' => 0],
    'ST' => ['boys' => 0, 'girls' => 0],
    'total' => ['boys' => 0, 'girls' => 0],
    'side_total' => 0,
];

// Calculate totals
foreach ($tableData as $department => $statusData) {
    foreach ($statusData as $status => $data) {
        foreach ($data as $key => $values) {
            if (is_array($values)) {
                $totals[$key]['boys'] += $values['boys'];
                $totals[$key]['girls'] += $values['girls'];
            }
        }
        $totals['side_total'] += $data['side_total'];
    }
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
        $pdf->SetTitle('Girls Boys Statistics - Form C');
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
        $pdf->Cell(0, 10, 'ADMISSION TO FIRST YEAR DIPLOMA COURSES: 2024 - 2025', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'FORM C – (Girls Boys Statistics - Admitted)', 0, 1, 'C');
        $pdf->Cell(0, 5, 'INSTITUTION CODE: 212', 0, 1, 'C');
        $pdf->Cell(0, 5, 'INSTITUTION NAME: NACHIMUTHU POLYTECHNIC COLLEGE (AUT), COIMBATORE', 0, 1, 'C');
        $pdf->Ln(5);

        // Table with 21 columns, matching Excel proportions
        $html = '<table border="1" cellpadding="5"><thead><tr style="background-color: #2980b9; color: #ffffff;">';
        $columns = ['S.No', 'Department', 'Type', 'Shift', 'OC (B)', 'OC (G)', 'BC (B)', 'BC (G)', 'BCM (B)', 'BCM (G)', 'MBC (B)', 'MBC (G)', 'SCA (B)', 'SCA (G)', 'SC (B)', 'SC (G)', 'ST (B)', 'ST (G)', 'Total (B)', 'Total (G)', 'Overall Total'];
        $colWidths = [6, 25, 10, 10, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8]; // Total = 179 units
        $totalWidth = array_sum($colWidths);
        $scaleFactor = 277 / $totalWidth; // Scale to fit 277mm
        $scaledWidths = array_map(fn($w) => $w * $scaleFactor, $colWidths);

        foreach ($columns as $idx => $col) {
            $html .= "<th width=\"" . $scaledWidths[$idx] . "mm\" align=\"center\">$col</th>";
        }
        $html .= '</tr></thead><tbody>';

        $serialNumber = 1;
        foreach ($tableData as $department => $statusData) {
            foreach ($statusData as $status => $data) {
                $html .= '<tr>';
                $html .= "<td width=\"" . $scaledWidths[0] . "mm\" align=\"center\">{$serialNumber}</td>";
                $html .= "<td width=\"" . $scaledWidths[1] . "mm\" align=\"center\">{$department}</td>";
                $html .= "<td width=\"" . $scaledWidths[2] . "mm\" align=\"center\">{$status}</td>";
                $html .= "<td width=\"" . $scaledWidths[3] . "mm\" align=\"center\">{$data['shift']}</td>";
                $html .= "<td width=\"" . $scaledWidths[4] . "mm\" align=\"center\">{$data['OC']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[5] . "mm\" align=\"center\">{$data['OC']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[6] . "mm\" align=\"center\">{$data['BC']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[7] . "mm\" align=\"center\">{$data['BC']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[8] . "mm\" align=\"center\">{$data['BCM']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[9] . "mm\" align=\"center\">{$data['BCM']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[10] . "mm\" align=\"center\">{$data['MBC']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[11] . "mm\" align=\"center\">{$data['MBC']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[12] . "mm\" align=\"center\">{$data['SCA']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[13] . "mm\" align=\"center\">{$data['SCA']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[14] . "mm\" align=\"center\">{$data['SC']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[15] . "mm\" align=\"center\">{$data['SC']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[16] . "mm\" align=\"center\">{$data['ST']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[17] . "mm\" align=\"center\">{$data['ST']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[18] . "mm\" align=\"center\">{$data['total']['boys']}</td>";
                $html .= "<td width=\"" . $scaledWidths[19] . "mm\" align=\"center\">{$data['total']['girls']}</td>";
                $html .= "<td width=\"" . $scaledWidths[20] . "mm\" align=\"center\">{$data['side_total']}</td>";
                $html .= '</tr>';
                $serialNumber++;
            }
        }

        // Totals Row with merged cells from S.No to Shift
        $html .= '<tr style="background-color: #cce5ff;">';
        $mergedWidth = $scaledWidths[0] + $scaledWidths[1] + $scaledWidths[2] + $scaledWidths[3];
        $html .= "<td width=\"" . $mergedWidth . "mm\" align=\"center\" colspan=\"4\"><strong>Total</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[4] . "mm\" align=\"center\"><strong>{$totals['OC']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[5] . "mm\" align=\"center\"><strong>{$totals['OC']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[6] . "mm\" align=\"center\"><strong>{$totals['BC']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[7] . "mm\" align=\"center\"><strong>{$totals['BC']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[8] . "mm\" align=\"center\"><strong>{$totals['BCM']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[9] . "mm\" align=\"center\"><strong>{$totals['BCM']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[10] . "mm\" align=\"center\"><strong>{$totals['MBC']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[11] . "mm\" align=\"center\"><strong>{$totals['MBC']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[12] . "mm\" align=\"center\"><strong>{$totals['SCA']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[13] . "mm\" align=\"center\"><strong>{$totals['SCA']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[14] . "mm\" align=\"center\"><strong>{$totals['SC']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[15] . "mm\" align=\"center\"><strong>{$totals['SC']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[16] . "mm\" align=\"center\"><strong>{$totals['ST']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[17] . "mm\" align=\"center\"><strong>{$totals['ST']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[18] . "mm\" align=\"center\"><strong>{$totals['total']['boys']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[19] . "mm\" align=\"center\"><strong>{$totals['total']['girls']}</strong></td>";
        $html .= "<td width=\"" . $scaledWidths[20] . "mm\" align=\"center\"><strong>{$totals['side_total']}</strong></td>";
        $html .= '</tr>';

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

        $pdf->Output('girls_boys_statistics_form_c.pdf', 'D');
        exit;
    } elseif ($format === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getPageMargins()->setTop(10 / 25.4);
        $sheet->getPageMargins()->setRight(10 / 25.4);
        $sheet->getPageMargins()->setBottom(10 / 25.4);
        $sheet->getPageMargins()->setLeft(10 / 25.4);

        $sheet->setCellValue('A1', 'ADMISSION TO FIRST YEAR DIPLOMA COURSES: 2024 - 2025');
        $sheet->setCellValue('A2', 'FORM C – (Girls Boys Statistics - Admitted)');
        $sheet->setCellValue('A3', 'INSTITUTION CODE: 212');
        $sheet->setCellValue('A4', 'INSTITUTION NAME: NACHIMUTHU POLYTECHNIC COLLEGE (AUT), COIMBATORE');
        $sheet->mergeCells('A1:U1');
        $sheet->mergeCells('A2:U2');
        $sheet->mergeCells('A3:U3');
        $sheet->mergeCells('A4:U4');

        $sheet->getStyle('A1:U4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setPath('./logo.png');
        $drawing->setCoordinates('A1');
        $drawing->setWidth(50);
        $drawing->setHeight(50);
        $drawing->setOffsetX(10);
        $drawing->setWorksheet($sheet);

        $columns = ['S.No', 'Department', 'Type', 'Shift', 'OC (B)', 'OC (G)', 'BC (B)', 'BC (G)', 'BCM (B)', 'BCM (G)', 'MBC (B)', 'MBC (G)', 'SCA (B)', 'SCA (G)', 'SC (B)', 'SC (G)', 'ST (B)', 'ST (G)', 'Total (B)', 'Total (G)', 'Overall Total'];
        $colWidths = [6, 25, 10, 10, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8]; // 21 columns
        $col = 'A';
        foreach ($columns as $idx => $column) {
            $sheet->setCellValue($col . '6', $column);
            $sheet->getColumnDimension($col)->setWidth($colWidths[$idx]);
            $col++;
        }

        $sheet->getStyle('A6:U6')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2980B9'],
            ],
            'font' => [
                'color' => ['rgb' => 'FFFFFF'],
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $rowNum = 7;
        $serialNumber = 1;
        foreach ($tableData as $department => $statusData) {
            foreach ($statusData as $status => $data) {
                $sheet->setCellValue('A' . $rowNum, $serialNumber);
                $sheet->setCellValue('B' . $rowNum, $department);
                $sheet->setCellValue('C' . $rowNum, $status);
                $sheet->setCellValue('D' . $rowNum, $data['shift']);
                $sheet->setCellValue('E' . $rowNum, $data['OC']['boys']);
                $sheet->setCellValue('F' . $rowNum, $data['OC']['girls']);
                $sheet->setCellValue('G' . $rowNum, $data['BC']['boys']);
                $sheet->setCellValue('H' . $rowNum, $data['BC']['girls']);
                $sheet->setCellValue('I' . $rowNum, $data['BCM']['boys']);
                $sheet->setCellValue('J' . $rowNum, $data['BCM']['girls']);
                $sheet->setCellValue('K' . $rowNum, $data['MBC']['boys']);
                $sheet->setCellValue('L' . $rowNum, $data['MBC']['girls']);
                $sheet->setCellValue('M' . $rowNum, $data['SCA']['boys']);
                $sheet->setCellValue('N' . $rowNum, $data['SCA']['girls']);
                $sheet->setCellValue('O' . $rowNum, $data['SC']['boys']);
                $sheet->setCellValue('P' . $rowNum, $data['SC']['girls']);
                $sheet->setCellValue('Q' . $rowNum, $data['ST']['boys']);
                $sheet->setCellValue('R' . $rowNum, $data['ST']['girls']);
                $sheet->setCellValue('S' . $rowNum, $data['total']['boys']);
                $sheet->setCellValue('T' . $rowNum, $data['total']['girls']);
                $sheet->setCellValue('U' . $rowNum, $data['side_total']);
                $rowNum++;
                $serialNumber++;
            }
        }

        // Totals Row with merged cells from S.No to Shift
        $sheet->setCellValue('A' . $rowNum, 'Total');
        $sheet->mergeCells('A' . $rowNum . ':D' . $rowNum);
        $sheet->setCellValue('E' . $rowNum, $totals['OC']['boys']);
        $sheet->setCellValue('F' . $rowNum, $totals['OC']['girls']);
        $sheet->setCellValue('G' . $rowNum, $totals['BC']['boys']);
        $sheet->setCellValue('H' . $rowNum, $totals['BC']['girls']);
        $sheet->setCellValue('I' . $rowNum, $totals['BCM']['boys']);
        $sheet->setCellValue('J' . $rowNum, $totals['BCM']['girls']);
        $sheet->setCellValue('K' . $rowNum, $totals['MBC']['boys']);
        $sheet->setCellValue('L' . $rowNum, $totals['MBC']['girls']);
        $sheet->setCellValue('M' . $rowNum, $totals['SCA']['boys']);
        $sheet->setCellValue('N' . $rowNum, $totals['SCA']['girls']);
        $sheet->setCellValue('O' . $rowNum, $totals['SC']['boys']);
        $sheet->setCellValue('P' . $rowNum, $totals['SC']['girls']);
        $sheet->setCellValue('Q' . $rowNum, $totals['ST']['boys']);
        $sheet->setCellValue('R' . $rowNum, $totals['ST']['girls']);
        $sheet->setCellValue('S' . $rowNum, $totals['total']['boys']);
        $sheet->setCellValue('T' . $rowNum, $totals['total']['girls']);
        $sheet->setCellValue('U' . $rowNum, $totals['side_total']);

        $sheet->getStyle('A' . $rowNum . ':U' . $rowNum)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCE5FF'],
            ],
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A6:U' . $rowNum)->applyFromArray($styleArray);

        $rowNum += 2;
        if (!empty($signatures)) {
            $sigCount = count($signatures);
            $sigPositions = [];
            if ($sigCount === 1) {
                $sigPositions = ['U'];
            } elseif ($sigCount === 2) {
                $sigPositions = ['B', 'U'];
            } elseif ($sigCount >= 3) {
                $sigPositions = ['B', 'K', 'U'];
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
        $sheet->getStyle('A1:U' . $lastRow)->applyFromArray($borderStyle);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="girls_boys_statistics_form_c.xlsx"');
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
    <title>Form C</title>
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

        .table-primary td {
            background-color: #cce5ff;
            font-weight: bold;
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
        <p class="text-center">GIRLS BOYS STATISTICS - ADMITTED (MOTHER TONGUE)</p>
        <p class="text-center">Form C</p>
        <h4 class="text-center">Admission to First Year Diploma Courses (2024-2025)</h4>
    
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th rowspan="2">S.No</th>
                    <th rowspan="2">Department</th>
                    <th rowspan="2">Type</th>
                    <th rowspan="2">Shift</th>
                    <th colspan="2">OC</th>
                    <th colspan="2">BC</th>
                    <th colspan="2">BCM</th>
                    <th colspan="2">MBC</th>
                    <th colspan="2">SCA</th>
                    <th colspan="2">SC</th>
                    <th colspan="2">ST</th>
                    <th colspan="2">Total</th>
                    <th rowspan="2">Overall Total</th>
                </tr>
                <tr>
                    <th>(B)</th><th>(G)</th>
                    <th>(B)</th><th>(G)</th>
                    <th>(B)</th><th>(G)</th>
                    <th>(B)</th><th>(G)</th>
                    <th>(B)</th><th>(G)</th>
                    <th>(B)</th><th>(G)</th>
                    <th>(B)</th><th>(G)</th>
                    <th>(B)</th><th>(G)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $serialNumber = 1;
                foreach ($tableData as $department => $statusData) {
                    foreach ($statusData as $status => $data) {
                        echo "<tr>";
                        echo "<td data-label=\"S.No\">{$serialNumber}</td>";
                        echo "<td data-label=\"Department\">{$department}</td>";
                        echo "<td data-label=\"Type\">{$status}</td>";
                        echo "<td data-label=\"Shift\">{$data['shift']}</td>";
                        foreach (['OC', 'BC', 'BCM', 'MBC', 'SCA', 'SC', 'ST', 'total'] as $category) {
                            echo "<td data-label=\"$category (B)\">{$data[$category]['boys']}</td>";
                            echo "<td data-label=\"$category (G)\">{$data[$category]['girls']}</td>";
                        }
                        echo "<td data-label=\"Overall Total\">{$data['side_total']}</td>";
                        echo "</tr>";
                        $serialNumber++;
                    }
                }
                ?>
                <!-- Totals Row -->
                <tr class="table-primary">
                    <td colspan="4" class="text-center" data-label="Total"><strong>Total</strong></td>
                    <?php
                    foreach (['OC', 'BC', 'BCM', 'MBC', 'SCA', 'SC', 'ST', 'total'] as $category) {
                        echo "<td data-label=\"$category (B)\"><strong>{$totals[$category]['boys']}</strong></td>";
                        echo "<td data-label=\"$category (G)\"><strong>{$totals[$category]['girls']}</strong></td>";
                    }
                    ?>
                    <td data-label="Overall Total"><strong><?= $totals['side_total']; ?></strong></td>
                </tr>
            </tbody>
        </table>
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