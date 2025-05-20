<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include('condb.php');

if (isset($_GET['parcel_id'])) {
    $parcel_id = $_GET['parcel_id'];

    // Query to fetch parcel data and tenant emails
    $parcel_query = "SELECT p.parcel_id, p.room_id, 
                        GROUP_CONCAT(DISTINCT u.email SEPARATOR ', ') AS tenant_emails,
                        GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS tenant_names
                    FROM parcel p
                    INNER JOIN contract c ON p.room_id = c.room_id AND c.contract_status = 'กำลังมีผล'
                    INNER JOIN contract_user cu ON c.contract_id = cu.contract_id
                    INNER JOIN user u ON cu.user_id = u.user_id
                    WHERE p.parcel_id = ? GROUP BY p.parcel_id";

    $stmt = $conn->prepare($parcel_query);
    if (!$stmt) {
        die("Query Prepare Failed: " . $conn->error);
    }

    $stmt->bind_param("s", $parcel_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('ไม่พบข้อมูลพัสดุหรืออีเมลลูกบ้าน'); window.close();</script>";
        exit();
    }

    $parcel_data = $result->fetch_assoc();

    // Check if tenant emails exist
    if (!isset($parcel_data['tenant_emails']) || empty($parcel_data['tenant_emails'])) {
        echo "<script>alert('ไม่พบอีเมลที่ถูกต้องสำหรับการส่ง'); window.close();</script>";
        exit();
    }

    $emails = explode(', ', $parcel_data['tenant_emails']);
    $validEmails = array_filter($emails, fn($email) => filter_var(trim($email), FILTER_VALIDATE_EMAIL));

    if (empty($validEmails)) {
        echo "<script>alert('ไม่พบอีเมลที่ถูกต้องสำหรับการส่ง'); window.close();</script>";
        exit();
    }

    $tenant_names = $parcel_data['tenant_names'] ?? 'ไม่มีผู้เช่า';
    $room_id = $parcel_data['room_id'] ?? 'ไม่ทราบ';
    $subject = "แจ้งเตือนพัสดุ - หอพัก SPK";
    $body = "เรียนคุณ $tenant_names,<br><br>" .
            "พัสดุของคุณได้มาถึงแล้ว กรุณามารับได้ที่เคาน์เตอร์หอพัก<br><br>";

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
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

        // Sender info
        $mail->setFrom('SPKdormitory@gmail.com', 'หอพัก SPK');

        // Add recipients
        foreach ($validEmails as $email) {
            $mail->addAddress(trim($email));
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send email
        $mail->send();
        echo "<script>alert('ส่งอีเมลสำเร็จ'); window.close();</script>";
    } catch (Exception $e) {
        echo "<script>alert('เกิดข้อผิดพลาดในการส่งอีเมล: {$mail->ErrorInfo}');</script>";
    }
} else {
    echo "<script>alert('ข้อมูลไม่ครบถ้วน'); window.close();</script>";
}
?>
