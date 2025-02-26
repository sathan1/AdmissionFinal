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
            'type' => $status, // Use MGMT or GOVT as the type label
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

// Define mapping of studentCaste to community categories
$casteMapping = [
    'OC' => 'OC',
    'BC' => 'BC',
    'BC(M)' => 'BCM', // Assuming BC(M) maps to BCM
    'MBC' => 'MBC',
    'SCA' => 'SCA',   // Assuming SCA is a valid caste
    'SC' => 'SC',
    'ST' => 'ST',
    // Add more mappings as per your database values (e.g., 'Others' could map to 'OC' or be excluded)
];

// Fetch and aggregate student data for MGMT or GOVT statuses only
$query = "
SELECT p.preferenceDepartment, p.department_status, sd.studentGender, sd.studentCaste, COUNT(*) AS studentCount
FROM studentdetails sd
LEFT JOIN preference p ON sd.studentUserId = p.preferenceUserId
WHERE p.preferenceStatus = 'success' AND p.department_status IN ('MGMT', 'GOVT')
GROUP BY p.preferenceDepartment, p.department_status, sd.studentCaste, sd.studentGender";
$result = $conn->query($query);

// Populate table data, using MGMT or GOVT as type with correct community counts
while ($row = $result->fetch_assoc()) {
    $department = $row['preferenceDepartment'];
    $status = $row['department_status']; // MGMT or GOVT only
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

    // Map studentCaste to the correct community category
    $community = isset($casteMapping[strtoupper($caste)]) ? $casteMapping[strtoupper($caste)] : 'OC'; // Default to OC if unmapped

    if (isset($tableData[$department][$status][$community][$gender])) {
        $tableData[$department][$status]['OC'][$gender] += ($community === 'OC') ? $count : 0;
        $tableData[$department][$status]['BC'][$gender] += ($community === 'BC') ? $count : 0;
        $tableData[$department][$status]['BCM'][$gender] += ($community === 'BCM') ? $count : 0;
        $tableData[$department][$status]['MBC'][$gender] += ($community === 'MBC') ? $count : 0;
        $tableData[$department][$status]['SCA'][$gender] += ($community === 'SCA') ? $count : 0;
        $tableData[$department][$status]['SC'][$gender] += ($community === 'SC') ? $count : 0;
        $tableData[$department][$status]['ST'][$gender] += ($community === 'ST') ? $count : 0;
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

// Calculate totals for MGMT and GOVT
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

// Handle export request
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

        $html = '<table border="1" cellpadding="5"><thead><tr style="background-color: #2980b9; color: #ffffff;">';
        $columns = [
            'S.No', 'Department', 'Type', 'Shift', 'OC (B)', 'OC (G)', 'BC (B)', 'BC (G)', 
            'BCM (B)', 'BCM (G)', 'MBC (B)', 'MBC (G)', 'SCA (B)', 'SCA (G)', 'SC (B)', 'SC (G)', 
            'ST (B)', 'ST (G)', 'Total (B)', 'Total (G)', 'Overall Total'
        ];
        $colWidths = [6, 25, 10, 10, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8]; // 21 columns
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

        // Totals Row
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

        $columns = [
            'S.No', 'Department', 'Type', 'Shift', 'OC (B)', 'OC (G)', 'BC (B)', 'BC (G)', 
            'BCM (B)', 'BCM (G)', 'MBC (B)', 'MBC (G)', 'SCA (B)', 'SCA (G)', 'SC (B)', 'SC (G)', 
            'ST (B)', 'ST (G)', 'Total (B)', 'Total (G)', 'Overall Total'
        ];
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

        // Totals Row
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Import Professional Fonts from Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@400;500&display=swap');

        /* General Reset */
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
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
            background: linear-gradient(145deg, #4a90e2, #357abd);
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
            padding-left: 25px;
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

        .table-primary td {
            background-color: #cce5ff;
            font-weight: bold;
        }

        /* Floating Export Button */
        .export-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: linear-gradient(145deg, #4a90e2, #357abd);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .export-btn:hover {
            background: linear-gradient(145deg, #357abd, #2a6395);
            transform: translateY(-2px);
        }

        /* Responsive Design */
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

            h2 {
                font-size: 1.5rem;
            }

            h4 {
                font-size: 1.2rem;
            }

            .btn-primary, .export-btn {
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Admin Dashboard</h1>
        <a href="../logout.php" class="logout-btn">Logout</a>
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
            <p class="text-center">GIRLS BOYS STATISTICS - ADMITTED (COMMUNITY)</p>
            <p class="text-center">Form C</p>
            <h4 class="text-center">Admission to First Year Diploma Courses (2024-2025)</h4>

            <div class="table-wrapper">
                <table class="table table-bordered table-striped" id="meritTable">
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
                                    echo "<td data-label=\"{$category} (B)\">{$data[$category]['boys']}</td>";
                                    echo "<td data-label=\"{$category} (G)\">{$data[$category]['girls']}</td>";
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
                                echo "<td data-label=\"{$category} (B)\"><strong>{$totals[$category]['boys']}</strong></td>";
                                echo "<td data-label=\"{$category} (G)\"><strong>{$totals[$category]['girls']}</strong></td>";
                            }
                            ?>
                            <td data-label="Overall Total"><strong><?= $totals['side_total']; ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Floating Export Button -->
    <div class="export-btn" onclick="showExportModal()">Export</div>

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