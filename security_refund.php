<?php
require_once('vendor/autoload.php');
include('condb.php');

$contract_id = $_GET['contract_id'];

// ปรับ SQL เพื่อดึงข้อมูลและจัดรูปแบบชื่อและที่อยู่ผู้เช่า
$query = "SELECT c.*, 
            GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) ORDER BY cu.user_id SEPARATOR '
                ,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS tenant_names, 
            GROUP_CONCAT(DISTINCT u.user_address ORDER BY cu.user_id SEPARATOR '
                ,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS tenant_addresses, c.deposit 
          FROM contract c
          JOIN contract_user cu ON c.contract_id = cu.contract_id
          JOIN user u ON cu.user_id = u.user_id
          WHERE c.contract_id = ?
          GROUP BY c.contract_id";


$stmt = $conn->prepare($query);
$stmt->bind_param("s", $contract_id);
$stmt->execute();
$result = $stmt->get_result();
$contract = $result->fetch_assoc();

if (!$contract) {
    echo "ไม่พบข้อมูลสัญญา";
    exit;
}

// ดึงข้อมูลที่อยู่และชื่อจากตาราง admin
$admin_query = "SELECT admin_address, admin_Fname, admin_Lname FROM admin LIMIT 1";
$admin_result = mysqli_query($conn, $admin_query);
$admin = mysqli_fetch_assoc($admin_result);
$admin_address = $admin['admin_address'];
$admin_name = $admin['admin_Fname'] . ' ' . $admin['admin_Lname'];

$date_thai = date('d-m-') . (date('Y') + 543);

// สร้าง PDF
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dormitory SPK');
$pdf->SetTitle('Security Refund ' . $contract['contract_id']);
$pdf->SetSubject('Security Refund');
$pdf->SetKeywords('TCPDF, PDF, security refund');

$pdf->AddFont('thsarabun', '', 'THSarabun.php');
$pdf->SetFont('thsarabun', '', 14);

$pdf->AddPage();

$html = '
    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td style="font-size: 18px; text-align: left;"><strong>หอพักสตรี SPK</strong></td>
            <td style="text-align:right; font-size: 18px;"><strong>ใบคืนเงินประกัน/SECURITY REFUND</strong></td>
        </tr>
        <tr>
            <td style="font-size: 14px; text-align: left;">' . $admin_address . '</td>
            <td style="text-align:right; font-size: 14px;"><strong>เลขที่เอกสาร: ' . $contract['contract_id'] . '</strong></td>
        </tr>
    </table>

    <br>

    <table border="0" cellspacing="0" cellpadding="2" style="width: 100%;">
        <tr>
            <td><strong>ชื่อผู้เช่า:</strong> ' . $contract['tenant_names'] . '</td>  
            <td style="text-align:right; font-size: 14px;"><strong>วันที่คืนเงิน: ' . $date_thai . '</strong></td>
        </tr>
        
        <tr>
            <td><strong>ห้อง:</strong> ' . $contract['room_id'] . '</td>
        </tr>

        <tr>
            <td colspan="2"><strong>ที่อยู่:</strong> ' . $contract['tenant_addresses'] . '</td>
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
                <td style="width: 60%;">เงินประกัน</td>
                <td style="text-align:right; width: 15%;">' . number_format($contract['deposit'], 2) . '</td>
                <td style="text-align:right; width: 15%;">' . number_format($contract['deposit'], 2) . '</td>
            </tr>
            <tr>
                <td style="text-align:right;" colspan="3"><strong>รวมทั้งหมด</strong></td>
                <td style="text-align:right;"><strong>' . number_format($contract['deposit'], 2) . '</strong></td>
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

$pdf->Output('security_refund_' . $contract_id . '.pdf', 'I');
?>
