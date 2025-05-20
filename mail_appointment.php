<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include('condb.php');

if (isset($_GET['schedule_id'])) {
    $schedule_id = $_GET['schedule_id'];

    // Query ดึงข้อมูลการนัดหมายและอีเมล
    $query = "SELECT 
                s.schedule_id,
                s.schedule_date,
                st.schedule_type_name,
                ss.schedule_status_name,
                s.description,
                GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS tenant_names,
                GROUP_CONCAT(DISTINCT u.email SEPARATOR ', ') AS tenant_emails
              FROM schedule s
              INNER JOIN schedule_type st ON s.schedule_typeID = st.schedule_typeID
              INNER JOIN schedule_status ss ON s.schedule_statusID = ss.schedule_statusID
              INNER JOIN contract c ON s.contract_id = c.contract_id
              INNER JOIN contract_user cu ON c.contract_id = cu.contract_id
              INNER JOIN user u ON cu.user_id = u.user_id
              WHERE s.schedule_id = ?
              GROUP BY s.schedule_id";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();

    if ($appointment && !empty($appointment['tenant_emails'])) {
        $emails = explode(', ', $appointment['tenant_emails']);
        $validEmails = array_filter($emails, fn($email) => filter_var(trim($email), FILTER_VALIDATE_EMAIL));

        if (empty($validEmails)) {
            echo "<script>alert('ไม่พบอีเมลที่ถูกต้องสำหรับการส่ง'); window.close();</script>";
            exit();
        }

        $tenant_names = $appointment['tenant_names'] ?? 'ไม่มีผู้เช่า';
        $schedule_date = $appointment['schedule_date'] ?? 'ไม่ทราบ';
        $schedule_type_name = $appointment['schedule_type_name'] ?? 'ไม่มีข้อมูลประเภทการนัดหมาย';
        $schedule_status_name = $appointment['schedule_status_name'] ?? 'ไม่มีสถานะ';
        $description = $appointment['description'] ?? 'ไม่มีหมายเหตุ';

        $subject = "แจ้งเตือนการนัดหมาย - หอพัก SPK";
        $body = "<p>เรียนคุณ $tenant_names,</p>
                <ul>
                    <li><strong>วันที่:</strong> $schedule_date</li>
                    <li><strong>ประเภทการนัดหมาย:</strong> $schedule_type_name</li>
                    <li><strong>สถานะการนัดหมาย:</strong> $schedule_status_name</li>
                </ul>
                <p><strong>หมายเหตุ:</strong> $description</p>
                <p>กรุณาตรวจสอบและยืนยันข้อมูล</p>
                <p>ขอบคุณค่ะ<br>หอพัก SPK</p>";

        $mail = new PHPMailer(true);

        try {
            // ตั้งค่า SMTP
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

            // ตั้งค่าผู้ส่ง
            $mail->setFrom('SPKdormitory@gmail.com', 'หอพัก SPK');

            // เพิ่มผู้รับ
            foreach ($validEmails as $email) {
                $mail->addAddress(trim($email));
            }

            // ตั้งค่าข้อความ
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            // ส่งอีเมล
            $mail->send();
            echo "<script>alert('ส่งอีเมลสำเร็จ'); window.close();</script>";
        } catch (Exception $e) {
            echo "<script>alert('เกิดข้อผิดพลาดในการส่งอีเมล: {$mail->ErrorInfo}');</script>";
        }
    } else {
        echo "<script>alert('ไม่พบข้อมูลนัดหมายหรืออีเมลลูกบ้าน'); window.close();</script>";
    }
} else {
    echo "<script>alert('ข้อมูลไม่ครบถ้วน'); window.close();</script>";
}
?>
