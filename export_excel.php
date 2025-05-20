<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

include('auth.php');
include('condb.php');

$selectedMonth = $_POST['month'] ?? '';
$selectedYear = $_POST['year'] ?? '';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'เดือน');
$sheet->setCellValue('B1', 'รหัสเอกสาร');
$sheet->setCellValue('C1', 'ห้อง');
$sheet->setCellValue('D1', 'ค่าน้ำประปา');
$sheet->setCellValue('E1', 'ค่าไฟฟ้า');
$sheet->setCellValue('F1', 'ค่าเช่า');
$sheet->setCellValue('G1', 'ค่าบริการและค่าปรับ');
$sheet->setCellValue('H1', 'รายละเอียดค่าบริการและค่าปรับ');
$sheet->setCellValue('I1', 'ราคารวม');

// กำหนดสไตล์ขอบและการจัดแนวสำหรับหัวตาราง
$sheet->getStyle('A1:I1')->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => '000000'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'font' => [
        'bold' => true,
    ],
]);

$months = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];

$searchQuery = "SELECT invoice_id, room_id, water_cost, electric_cost, rent_cost, penalty_service_cost, cost_detail, total_cost, invoice_date 
                FROM invoice i 
                WHERE payment_statusID = 'P_status02'";

if (!empty($selectedMonth) && !empty($selectedYear)) {
    $selected_date = "$selectedYear-$selectedMonth-01"; 
    $searchQuery .= " AND DATE(i.invoice_date) >= '$selected_date' 
                      AND DATE(i.invoice_date) < DATE_ADD('$selected_date', INTERVAL 1 MONTH)";
} elseif (!empty($selectedMonth)) {
    $searchQuery .= " AND MONTH(i.invoice_date) = '$selectedMonth'";
} elseif (!empty($selectedYear)) {
    $searchQuery .= " AND YEAR(i.invoice_date) = '$selectedYear'";
}

$result = mysqli_query($conn, $searchQuery);

$rowNumber = 2; // เริ่มเขียนข้อมูลจากแถวที่ 2
$totalSum = 0; // กำหนดยอดรวมเริ่มต้นที่ 0

while ($row = mysqli_fetch_assoc($result)) {
    $monthName = isset($row['invoice_date']) ? $months[date('m', strtotime($row['invoice_date']))] : 'ไม่ระบุ';

    $sheet->setCellValue('A' . $rowNumber, $monthName);
    $sheet->setCellValue('B' . $rowNumber, $row['invoice_id']);
    $sheet->setCellValue('C' . $rowNumber, $row['room_id']);
    $sheet->setCellValue('D' . $rowNumber, $row['water_cost']);
    $sheet->setCellValue('E' . $rowNumber, $row['electric_cost']);
    $sheet->setCellValue('F' . $rowNumber, $row['rent_cost']);
    $sheet->setCellValue('G' . $rowNumber, $row['penalty_service_cost']);
    $sheet->setCellValue('H' . $rowNumber, $row['cost_detail']);
    $sheet->setCellValue('I' . $rowNumber, $row['total_cost']);
    
    $totalSum += $row['total_cost'];

    // กำหนดขอบเส้นสำหรับข้อมูลแต่ละแถว
    $sheet->getStyle("A$rowNumber:I$rowNumber")->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '000000'],
            ],
        ],
    ]);
    
    $rowNumber++;
}

// เพิ่มแถวสุดท้ายสำหรับยอดรวมทั้งหมด
$sheet->setCellValue('H' . $rowNumber, 'ยอดรวมทั้งหมด');
$sheet->setCellValue('I' . $rowNumber, $totalSum);

// รวมเซลล์แถวสุดท้ายสำหรับคำว่า "ยอดรวมทั้งหมด"
$sheet->mergeCells("A$rowNumber:H$rowNumber");
$sheet->getStyle("A$rowNumber:I$rowNumber")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => '000000'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'font' => [
        'bold' => true,
    ],
]);

// ปรับขนาดของคอลัมน์ให้พอดีกับข้อมูล
foreach (range('A', 'I') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// กำหนด header สำหรับการดาวน์โหลดไฟล์ .xlsx
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="รายงานรายได้.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
