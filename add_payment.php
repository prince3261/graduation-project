<?php 
session_start();
include('condb.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['username']) || $_SESSION['user_type'] != "ผู้เช่า") {
    echo "<script>alert('กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['username'] ?? ''; // ใช้ user_id จาก Session

if (!$user_id) {
    echo "<script>alert('ไม่พบข้อมูลผู้ใช้งานในระบบ');</script>";
    exit();
}

// ดึงข้อมูล room_id จาก contract_user และ contract
$contract_query = "SELECT c.room_id 
                   FROM contract_user cu 
                   JOIN contract c ON cu.contract_id = c.contract_id
                   WHERE cu.user_id = ? AND c.contract_status = 'กำลังมีผล'";
$stmt_contract = $conn->prepare($contract_query);
$stmt_contract->bind_param("s", $user_id);
$stmt_contract->execute();
$contract_result = $stmt_contract->get_result();
$contract_data = $contract_result->fetch_assoc();
$room_id = $contract_data['room_id'] ?? '';

if (!$room_id) {
    echo "<script>alert('ไม่พบข้อมูลห้องที่ผู้ใช้งานเช่าในระบบ');</script>";
    exit();
}

// ดึงประเภทการชำระเงิน
$payment_types = [];
$payment_type_query = "SELECT payment_typeID, payment_type_name FROM payment_type";
$payment_type_result = mysqli_query($conn, $payment_type_query);
while ($row = mysqli_fetch_assoc($payment_type_result)) {
    $payment_types[] = $row;
}

// ตรวจสอบและดึงข้อมูล invoice
$invoice_id = $_GET['invoice_id'] ?? null;
$invoice = null;

if ($invoice_id) {
    $query = "SELECT i.*, 
                     GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS tenant_names
              FROM invoice i
              JOIN contract c ON i.room_id = c.room_id
              JOIN contract_user cu ON c.contract_id = cu.contract_id
              JOIN user u ON cu.user_id = u.user_id
              WHERE i.invoice_id = ? AND i.room_id = ?
              GROUP BY i.invoice_id";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $invoice_id, $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $invoice = $result->fetch_assoc();
    } else {
        echo "<script>alert('ไม่พบข้อมูลใบแจ้งหนี้ที่ตรงกัน');</script>";
    }
}

// บันทึกข้อมูลการชำระเงิน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_payment'])) {
    $invoice_id = $_POST['invoice_id'] ?? '';
    $payment_date = $_POST['payment_date'] ?? '';
    $payment_typeID = $_POST['payment_typeID'] ?? '';

    if (!$invoice_id) {
        echo "<script>alert('กรุณาเลือกใบแจ้งหนี้ก่อน');</script>";
        exit();
    }

    if (!$payment_date) {
        echo "<script>alert('กรุณาเลือกวันที่ชำระเงิน');</script>";
        exit();
    }

    if (!$payment_typeID) {
        echo "<script>alert('กรุณาเลือกประเภทการชำระเงิน');</script>";
        exit();
    }

    $datetime = new DateTime($payment_date);
    $year = (int)$datetime->format('Y') + 543;
    $formatted_payment_date = $datetime->setDate($year, (int)$datetime->format('m'), (int)$datetime->format('d'))->format('Y-m-d');

    // ตรวจสอบการอัปโหลดไฟล์
    if (isset($_FILES['payment_img']) && $_FILES['payment_img']['error'] == 0) {
        $payment_img = $_FILES['payment_img']['name'];
        $target_dir = "payment/";
        $target_file = $target_dir . basename($payment_img);

        if (move_uploaded_file($_FILES['payment_img']['tmp_name'], $target_file)) {
            $query = "UPDATE invoice 
                      SET payment_date = ?, payment_typeID = ?, payment_img = ?, payment_statusID = 'P_status03' 
                      WHERE invoice_id = ?";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param("ssss", $formatted_payment_date, $payment_typeID, $payment_img, $invoice_id);

                if ($stmt->execute()) {
                    // ส่งอีเมลแจ้งเตือน
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

                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );

                        $mail->setFrom('SPKdormitory@gmail.com', 'หอพักสตรี SPK');
                        $mail->addAddress('SPKdormitory@gmail.com');

                        $mail->isHTML(true);
                        $mail->Subject = 'มีการชำระเงินใหม่';
                        $mail->Body = "ผู้เช่า {$invoice['tenant_names']} ห้อง $room_id ได้ทำการชำระเงินสำหรับใบแจ้งหนี้หมายเลข: $invoice_id แล้ว";
                        $mail->addAttachment($target_file);

                        $mail->send();
                    } catch (Exception $e) {
                        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }

                    echo "<script>
                            alert('บันทึกการชำระเงินเรียบร้อย');
                            window.location.href = 'search_payment.php';
                          </script>";
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error . "');</script>";
                }
            } else {
                die("Error preparing statement: " . $conn->error);
            }
        } else {
            echo "<script>alert('ไม่สามารถอัปโหลดไฟล์ได้');</script>";
        }
    } else {
        echo "<script>alert('กรุณาอัปโหลดไฟล์หลักฐานการชำระเงิน');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ส่งหลักฐานการชำระเงิน</title>
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 50px;
    }

    .content {
        margin-top: 30px;
        max-width: 100%;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin-left: 10%;
    }

    h2 {
        text-align: center;
        color: #333;
    }

    form {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        width: 600px;
    }

    .form-group label {
        width: 150px;
    }

    label {
        display: block;
        margin: 10px 0 5px;
    }

    input[type="text"], input[type="date"], select, textarea, input[type="file"] {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        width: 100%;
    }

    button {
        background-color: #5cb85c;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
        margin-bottom: 15px;
    }

    button:hover {
        background-color: #4cae4c;
    }

    .button2 {
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border-radius: 4px;
        cursor: pointer;
    }

    .button2:hover {
        background-color: #0056b3;
    }

    .delete-btn {
        background-color: #d9534f;
        color: white;
        border-radius: 4px;
        cursor: pointer;
        align-self: flex-start;
        width: 8%;
        margin-top: 13px;
    }

    .delete-btn:hover {
        background-color: #c9302c;
    }
    #loadingOverlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    #loadingOverlay .spinner {
        border: 8px solid #f3f3f3;
        border-top: 8px solid #3498db;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.querySelector("form");
        const loadingOverlay = document.getElementById("loadingOverlay");

        if (form && loadingOverlay) {
            form.addEventListener("submit", (e) => {
                loadingOverlay.style.display = "flex"; // แสดง Loading Overlay
            });
        } else {
            console.error("Form หรือ Loading Overlay ไม่พบใน DOM");
        }
    });
</script>
<body>
    <?php include('user_sidebar.php');?>
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>
    
    <div class="content">
    <h2>ส่งหลักฐานการชำระเงิน</h2>
        <form method="POST" action="add_payment.php" enctype="multipart/form-data">
        <input type="hidden" name="invoice_id" value="<?php echo $invoice['invoice_id']; ?>">
            
            <div class="form-group">
                <label>รหัสใบแจ้งหนี้:</label>
                <input type="text" placeholder="รหัสใบแจ้งหนี้" value="<?php echo isset($invoice['invoice_id']) ? $invoice['invoice_id'] : ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label>ค่าน้ำประปา:</label>
                <input type="text" placeholder="ค่าน้ำประปา" value="<?php echo isset($invoice['water_cost']) ? $invoice['water_cost'] : ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label>ค่าไฟฟ้า:</label>
                <input type="text" placeholder="ค่าไฟฟ้า" value="<?php echo isset($invoice['electric_cost']) ? $invoice['electric_cost'] : ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label>ค่าเช่า:</label>
                <input type="text" placeholder="ค่าเช่า" value="<?php echo isset($invoice['rent_cost']) ? $invoice['rent_cost'] : ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label>ค่าบริการ <br> และค่าปรับ:</label>
                <input type="text" placeholder="ค่าบริการและค่าปรับ" value="<?php echo isset($invoice['penalty_service_cost']) ? $invoice['penalty_service_cost'] : ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label>รายละเอียด <br> ค่าบริการ <br> และค่าปรับ:</label>
                <textarea readonly rows="6" placeholder="รายละเอียดค่าบริการและค่าปรับ"><?php echo isset($invoice['cost_detail']) ? $invoice['cost_detail'] : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label>ราคารวม:</label>
                <input type="text" placeholder="ราคารวม" value="<?php echo isset($invoice['total_cost']) ? $invoice['total_cost'] : ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="payment_img">ไฟล์หลักฐาน <br> การชำระเงิน:<span style="color: red;">*</span></label>
                <input type="file" name="payment_img" id="payment_img" required>
            </div>

            <div class="form-group">
                <label for="payment_typeID">ประเภท <br> การชำระเงิน:<span style="color: red;">*</span></label>
                <select name="payment_typeID" id="payment_typeID" required>
                    <option value="">--เลือกประเภทการชำระเงิน--</option>
                    <?php
                    foreach ($payment_types as $type) {
                        echo "<option value='{$type['payment_typeID']}'>{$type['payment_type_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="payment_date">วันที่ชำระเงิน:<span style="color: red;">*</span></label>
                <input type="date" name="payment_date" required>
            </div>
            <button type="submit" name="save_payment">บันทึกการชำระเงิน</button>
        </form>
    </div>
</body>
</html>