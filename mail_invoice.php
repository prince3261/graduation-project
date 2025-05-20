<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include('condb.php');

$invoice_id = $_GET['invoice_id'] ?? null;

if (!$invoice_id) {
    echo "Invoice ID is missing.";
    exit;
}

// ดึงข้อมูลใบแจ้งหนี้จากฐานข้อมูล
$query = "SELECT i.*, 
                 GROUP_CONCAT(DISTINCT u.email SEPARATOR ', ') AS tenant_emails,
                 GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR '
                    ,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS tenant_names,
                 GROUP_CONCAT(DISTINCT u.user_address SEPARATOR '
                    ,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') AS tenant_addresses,
                 c.room_id, 
                 pt.payment_type_name, 
                 ps.payment_status_name
          FROM invoice i
          JOIN contract c ON i.room_id = c.room_id
          JOIN contract_user cu ON c.contract_id = cu.contract_id
          JOIN user u ON cu.user_id = u.user_id
          LEFT JOIN payment_type pt ON i.payment_typeID = pt.payment_typeID
          LEFT JOIN payment_status ps ON i.payment_statusID = ps.payment_statusID
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

// ตรวจสอบสถานะการชำระเงินและกำหนดเส้นทางไฟล์ PDF
$payment_status_name = $invoice['payment_status_name'] ?? 'ไม่มีสถานะ';
$tenant_names = $invoice['tenant_names'] ?? 'ไม่มีผู้เช่า';
$tenant_addresses = $invoice['tenant_addresses'] ?? 'ไม่มีที่อยู่';

$file_name = $payment_status_name === 'ยังไม่ชำระ' ? "invoice_$invoice_id.pdf" : "receipt_$invoice_id.pdf";
$folder_path = __DIR__ . ($payment_status_name === 'ยังไม่ชำระ' ? "/invoice/" : "/receipt/");
$subject = $payment_status_name === 'ยังไม่ชำระ' ? 'ใบแจ้งหนี้' : 'ใบเสร็จรับเงิน';
$body = 'เรียนคุณ ' . $tenant_names . '<br><br>' . ($payment_status_name === 'ยังไม่ชำระ'
        ? 'นี่คือใบแจ้งหนี้ของคุณ กรุณาชำระเงินตามรายละเอียดที่แนบมา'
        : 'ขอบคุณที่ชำระเงิน นี่คือใบเสร็จรับเงินของคุณ');

// สร้างโฟลเดอร์ถ้ายังไม่มี
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

// สร้างเนื้อหา PDF
$html_content = '
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

// สร้าง PDF
$save_path = $folder_path . $file_name;
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->AddFont('thsarabun', '', 'THSarabun.php');
$pdf->SetFont('thsarabun', '', 14);
$pdf->writeHTML($html_content, true, false, true, false, '');
$pdf->Output($save_path, 'F');

// ส่งอีเมล
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'SPKdormitory@gmail.com';
    $mail->Password = 'ofmd hxjs pria guvz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom('SPKdormitory@gmail.com', 'หอพักสตรี SPK');
    $emails = explode(', ', $invoice['tenant_emails']);
    foreach ($emails as $email) {
        $mail->addAddress(trim($email));
    }

    $mail->addAttachment($save_path, $file_name);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send();
    echo 'อีเมลถูกส่งเรียบร้อยแล้ว';
} catch (Exception $e) {
    echo "เกิดข้อผิดพลาด: {$mail->ErrorInfo}";
}

// ลบไฟล์ PDF หลังจากส่งอีเมลเสร็จ
if (file_exists($save_path)) {
    unlink($save_path);
}

mysqli_close($conn);
?>
