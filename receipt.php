<?php
require_once('vendor/autoload.php');
include('condb.php');

$invoice_id = $_GET['invoice_id'] ?? null;

if (!$invoice_id) {
    echo "ไม่พบเลขที่ใบแจ้งหนี้";
    exit;
}

$query = "SELECT i.invoice_id, i.room_id, 
            GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ',
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS tenant_names, 
            i.water_cost, i.electric_cost, i.rent_cost, i.penalty_service_cost, i.total_cost, 
            i.invoice_date, i.cost_detail, ps.payment_status_name, pt.payment_type_name, 
            i.payment_date, i.payment_img, i.invoice_month,
            GROUP_CONCAT(DISTINCT u.user_address SEPARATOR ',
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS user_address
          FROM invoice i
          JOIN contract c ON i.room_id = c.room_id
          JOIN contract_user cu ON c.contract_id = cu.contract_id
          JOIN user u ON cu.user_id = u.user_id
          LEFT JOIN payment_status ps ON i.payment_statusID = ps.payment_statusID
          LEFT JOIN payment_type pt ON i.payment_typeID = pt.payment_typeID
          WHERE i.invoice_id = ?
          GROUP BY i.invoice_id";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    echo "ไม่พบข้อมูลใบแจ้งหนี้";
    exit;
}

// Fetch admin details
$admin_query = "SELECT admin_address, admin_Fname, admin_Lname FROM admin LIMIT 1";
$admin_result = mysqli_query($conn, $admin_query);
$admin = mysqli_fetch_assoc($admin_result);

$admin_address = $admin['admin_address'] ?? 'ไม่ระบุ';
$admin_name = ($admin['admin_Fname'] ?? '') . ' ' . ($admin['admin_Lname'] ?? '');

// Initialize TCPDF
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set PDF metadata
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dormitory SPK');
$pdf->SetTitle('Invoice ' . $invoice['invoice_id']);
$pdf->SetSubject('Invoice');
$pdf->SetKeywords('TCPDF, PDF, invoice');

// Add Thai font
$pdf->AddFont('thsarabun', '', 'THSarabun.php');
$pdf->SetFont('thsarabun', '', 14);

$pdf->AddPage();

$html = '
    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td style="font-size: 18px; text-align: left;"><strong>หอพักสตรี SPK</strong></td>
            <td style="text-align:right; font-size: 18px;"><strong>ใบเสร็จรับเงิน/RECEIPT</strong></td>
        </tr>
        <tr>
            <td style="font-size: 14px; text-align: left;">' . htmlspecialchars($admin_address) . '</td>
            <td style="text-align:right; font-size: 14px;"><strong>เลขที่เอกสาร: ' . htmlspecialchars($invoice['invoice_id']) . '</strong></td>
        </tr>
    </table>

    <br>

    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td><strong>ชื่อผู้เช่า:</strong> ' . htmlspecialchars_decode($invoice['tenant_names'] ?? 'ไม่ระบุ') . '</td>
            <td style="text-align:right; font-size: 14px;"><strong>วันที่: ' . htmlspecialchars($invoice['payment_date'] ?? 'ไม่ระบุ') . '</strong></td>
        </tr> 
        <tr>
            <td><strong>ห้อง:</strong> ' . htmlspecialchars($invoice['room_id']) . '</td>
            <td style="text-align:right; font-size: 14px;"><strong>ชำระด้วย: ' . htmlspecialchars($invoice['payment_type_name'] ?? 'ไม่ระบุ') . '</strong></td>
        </tr>
        <tr>
            <td colspan="2"><strong>ที่อยู่:</strong> ' . htmlspecialchars_decode($invoice['user_address'] ?? 'ไม่ระบุ') . '</td>
        </tr>
    </table>


    <br>
    <table border="1" cellpadding="2" cellspacing="0" style="width: 100%; table-layout: fixed;">
        <thead>
            <tr>
                <th style="text-align:center; width: 10%;"><strong>ลำดับ</strong></th>
                <th style="text-align:center; width: 60%;"><strong>รายการ/Description</strong></th>
                <th style="text-align:center; width: 15%;"><strong>ราคา/Price</strong></th>
                <th style="text-align:center; width: 15%;"><strong>รวม/Total</strong></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:center; width: 10%;">1</td>
                <td style="width: 60%;">ค่าน้ำ</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['water_cost'], 2) . '</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['water_cost'], 2) . '</td>
            </tr>
            <tr>
                <td style="text-align:center; width: 10%;">2</td>
                <td style="width: 60%;">ค่าไฟ</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['electric_cost'], 2) . '</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['electric_cost'], 2) . '</td>
            </tr>
            <tr>
                <td style="text-align:center; width: 10%;">3</td>
                <td style="width: 60%;">ค่าเช่า</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['rent_cost'], 2) . '</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['rent_cost'], 2) . '</td>
            </tr>
            <tr>
                <td style="text-align:center; width: 10%;">4</td>
                <td style="width: 60%;">ค่าบริการและค่าปรับ<br>' . nl2br($invoice['cost_detail']) . '</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['penalty_service_cost'], 2) . '</td>
                <td style="text-align:right; width: 15%;">' . number_format((float)$invoice['penalty_service_cost'], 2) . '</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right;"><strong>รวมทั้งหมด</strong></td>
                <td style="text-align:right;"><strong>' . number_format((float)$invoice['total_cost'], 2) . '</strong></td>
            </tr>
        </tbody>
    </table>

    <br><br><br>
    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td style="text-align: center;">
                .........................................................<br>
                ผู้เช่า
            </td>
            <td style="text-align: center;">
                ' . htmlspecialchars($admin_name) . '<br>
                .........................................................<br>
                เจ้าของหอพัก
            </td>
        </tr>
    </table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('invoice_' . $invoice_id . '.pdf', 'I');
?>
