<?php
require_once('vendor/autoload.php');
include('condb.php');

$invoice_id = $_GET['invoice_id'];

// ปรับ SQL เพื่อดึงข้อมูลชื่อผู้เช่าหลายคน
$query = "SELECT i.*, 
            GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) ORDER BY cu.user_id SEPARATOR '
                ,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS tenant_names,
            GROUP_CONCAT(DISTINCT u.user_address ORDER BY cu.user_id SEPARATOR '
                ,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS tenant_addresses,
            c.room_id
          FROM invoice i
          JOIN contract c ON i.room_id = c.room_id
          JOIN contract_user cu ON c.contract_id = cu.contract_id
          JOIN user u ON cu.user_id = u.user_id
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

// ดึงข้อมูลที่อยู่และชื่อจากตาราง admin
$admin_query = "SELECT admin_address, admin_Fname, admin_Lname FROM admin LIMIT 1";
$admin_result = mysqli_query($conn, $admin_query);
$admin = mysqli_fetch_assoc($admin_result);
$admin_address = $admin['admin_address'];
$admin_name = $admin['admin_Fname'] . ' ' . $admin['admin_Lname'];

// สร้างอินสแตนซ์ของ TCPDF
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// ตั้งค่าข้อมูลพื้นฐานของไฟล์ PDF
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dormitory SPK');
$pdf->SetTitle('Invoice ' . $invoice['invoice_id']);
$pdf->SetSubject('Invoice');
$pdf->SetKeywords('TCPDF, PDF, invoice');

$pdf->AddFont('thsarabun', '', 'THSarabun.php');
$pdf->SetFont('thsarabun', '', 14);

$pdf->AddPage();

$html = '
    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td style="font-size: 18px; text-align: left;"><strong>หอพักสตรี SPK</strong></td>
            <td style="text-align:right; font-size: 18px;"><strong>ใบแจ้งหนี้/INVOICE</strong></td>
        </tr>
        <tr>
            <td style="font-size: 14px; text-align: left;">' . $admin_address . '</td>
            <td style="text-align:right; font-size: 14px;"><strong>เลขที่เอกสาร: ' . $invoice['invoice_id'] . '</strong></td>
        </tr>
    </table>

    <br>

    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td><strong>ชื่อผู้เช่า:</strong> ' . $invoice['tenant_names'] . '</td>
            <td style="text-align:right; font-size: 14px;"><strong>วันที่: ' . $invoice['invoice_date'] . '</strong></td>
        </tr>
        <tr>
            <td><strong>ห้อง:</strong> ' . $invoice['room_id'] . '</td>
        </tr>
        <tr>
            <td colspan="2"><strong>ที่อยู่:</strong> ' . $invoice['tenant_addresses'] . '</td>
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
                <td style="text-align:right;" colspan="3"><strong>รวมทั้งหมด</strong></td>
                <td style="text-align:right;"><strong>' . number_format((float)$invoice['total_cost'], 2) . '</strong></td>
            </tr>
        </tbody>
    </table>

    <br><br><br>
    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td style="text-align: center;"> <br>  <br>
                .........................................................<br>
                ผู้เช่า
            </td>
            <td style="text-align: center;">
                ' . $admin_name . '<br>
                .........................................................<br>
                เจ้าของหอพัก
            </td>
        </tr>
    </table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('invoice_' . $invoice_id . '.pdf', 'I');
?>
