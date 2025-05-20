<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // ใช้ PHPMailer

session_start();
include('condb.php');

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['username']) || $_SESSION['user_type'] != "ผู้เช่า") {
    echo "<script>alert('กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['username'];

// ดึงข้อมูลประเภทการนัดหมายจาก schedule_type
$sql_schedule_types = "SELECT * FROM schedule_type";
$result_schedule_types = $conn->query($sql_schedule_types);
$scheduleTypes = [];
if ($result_schedule_types->num_rows > 0) {
    while ($row = $result_schedule_types->fetch_assoc()) {
        $scheduleTypes[] = $row;
    }
}

// ดึงข้อมูล room_id จาก contract_user และ contract
$sql = "SELECT c.room_id 
        FROM contract_user cu 
        JOIN contract c ON cu.contract_id = c.contract_id
        WHERE cu.user_id = '$user_id' AND c.contract_status = 'กำลังมีผล'";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $data = mysqli_fetch_assoc($result);
    $room_id = $data['room_id'];
} else {
    echo "SQL Error: " . mysqli_error($conn);
}

// เมื่อกด "ยืนยันการนัดหมาย"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $block_id = $_POST['block_id'];
    $schedule_typeID = $_POST['schedule_typeID'];
    $schedule_detail = $_POST['schedule_detail'];
    $schedule_date = $_POST['schedule_date'];

    // ดึงข้อมูล block_name, start_time, end_time จาก time_block
    $block_query = "SELECT block_name, start_time, end_time FROM time_block WHERE block_id = ?";
    $stmt_block = $conn->prepare($block_query);
    $stmt_block->bind_param("s", $block_id);
    $stmt_block->execute();
    $block_result = $stmt_block->get_result();
    $block_data = $block_result->fetch_assoc();
    $block_name = $block_data['block_name'] ?? '';
    $start_time = $block_data['start_time'] ?? '';
    $end_time = $block_data['end_time'] ?? '';

    // ตรวจสอบ contract_id
    $user_id = $_SESSION['username'];
    $sql_contract = "SELECT c.contract_id FROM contract c
                     INNER JOIN contract_user cu ON c.contract_id = cu.contract_id
                     WHERE cu.user_id = ? AND c.contract_status = 'กำลังมีผล'";
    $stmt_contract = $conn->prepare($sql_contract);
    $stmt_contract->bind_param("s", $user_id);
    $stmt_contract->execute();
    $result_contract = $stmt_contract->get_result();
    $contract = $result_contract->fetch_assoc();
    $contract_id = $contract['contract_id'] ?? null;

    if (!$contract_id) {
        echo "<script>alert('ไม่พบสัญญาที่กำลังมีผลสำหรับผู้ใช้นี้');</script>";
    } else {
        // Generate schedule_id
        $sql_max_id = "SELECT MAX(CAST(SUBSTRING(schedule_id, 10) AS UNSIGNED)) AS max_id FROM schedule";
        $result_max_id = $conn->query($sql_max_id);
        $row_max_id = $result_max_id->fetch_assoc();
        $next_id = str_pad(($row_max_id['max_id'] ?? 0) + 1, 2, "0", STR_PAD_LEFT);
        $schedule_id = "schedule" . $next_id;

        // บันทึกข้อมูลการนัดหมาย
        $sql_insert = "INSERT INTO schedule (schedule_id, contract_id, block_id, schedule_typeID, schedule_detail, schedule_date, schedule_statusID)
                       VALUES (?, ?, ?, ?, ?, ?, 'sc_status01')";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ssssss", $schedule_id, $contract_id, $block_id, $schedule_typeID, $schedule_detail, $schedule_date);

        if ($stmt_insert->execute()) {
            // ส่งอีเมลแจ้งเตือน
            $mail = new PHPMailer(true);
            try {
                // ตั้งค่าการเชื่อมต่อ SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'SPKdormitory@gmail.com';
                $mail->Password = 'ofmd hxjs pria guvz'; //APP PASSWORD
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                // ตั้งค่าผู้ส่งและผู้รับ
                $mail->setFrom('SPKdormitory@gmail.com', 'SPK Dormitory');
                $mail->addAddress('SPKdormitory@gmail.com');

                // ตั้งค่าหัวข้อและเนื้อหา
                $mail->isHTML(true);
                $mail->Subject = 'การนัดหมายใหม่';
                $mail->Body = "<h3>มีการนัดหมายใหม่</h3>
                               <p>ผู้เช่าห้อง: $room_id</p>
                               <p>วันที่นัดหมาย: $schedule_date</p>
                               <p>ช่วงเวลา: $block_name ($start_time ถึง $end_time)</p>
                               <p>รายละเอียด: $schedule_detail</p>";

                $mail->send();
            } catch (Exception $e) {
                echo "การส่งอีเมลล้มเหลว: {$mail->ErrorInfo}";
            }

            echo "<script>alert('บันทึกการนัดหมายสำเร็จ'); window.location.href='user_appointment.php';</script>";
            exit();
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกการนัดหมาย');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบนัดหมาย</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .calendar-table th, .calendar-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .btn-select {
            background-color: #fe2d85;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-select:hover {
            background-color: #e5005f;
        }
        .container {
            width: 100%;
            max-width: 1020px;
            margin: 10px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 30%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #17202a;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #e8daef;
        }
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        tr:nth-child(odd) {
            background-color: #f5eef8;
        }
        h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            font-weight: bolder;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            width: 98%;
        }
        .form-group label {
            width: 25%;
        }
        input[type="text"], select, textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        .submit-btn {
            background-color: #fe2d85;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
        }

        .submit-btn:hover {
            background-color: #e5005f;
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
        function selectBlock(block_id, block_name) {
            document.getElementById('block_id').value = block_id;
            document.getElementById('block_name').value = block_name;
        }
        document.addEventListener("DOMContentLoaded", () => {
            console.log("DOMContentLoaded ถูกเรียกใช้งาน");

            const form = document.getElementById("appointmentForm");
            const loadingOverlay = document.getElementById("loadingOverlay");

            if (!form) {
                console.error("ไม่พบ form");
                return;
            } else {
                console.log("พบ form");
            }

            if (!loadingOverlay) {
                console.error("ไม่พบ loadingOverlay");
                return;
            } else {
                console.log("พบ loadingOverlay");
            }

            form.addEventListener("submit", (e) => {
                console.log("Form กำลังถูกส่ง");
                loadingOverlay.style.display = "flex";
            });
        });
    </script>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>
    
    <?php include('user_sidebar.php');
          include('calendar.php'); 
    ?>
    <div class="container">
        <!-- calendar -->
        <?php if (isset($_POST['selected_date'])) {
                    $selected_date = $_POST['selected_date'];
            } else {
                $selected_date = date('Y-m-d'); // ค่าเริ่มต้น: วันที่ปัจจุบัน
            }

            $selected_date_buddhist = date('Y-m-d', strtotime('+543 years', strtotime($selected_date)));

            $sql = "SELECT tb.block_id, tb.block_name, tb.start_time, tb.end_time, 
                    CASE 
                        WHEN s.schedule_statusID IS NOT NULL 
                        AND s.schedule_date = ? 
                        AND s.schedule_statusID != 'sc_status03' THEN 'ไม่ว่าง'
                    ELSE 'ว่าง'
                    END AS status
                    FROM time_block tb
                    LEFT JOIN schedule s ON tb.block_id = s.block_id AND s.schedule_date = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $selected_date_buddhist, $selected_date_buddhist);
            $stmt->execute();
            $result = $stmt->get_result();

            // แสดงผลตาราง
            echo "<h3> ตารางเวลาสำหรับวันที่ $selected_date_buddhist</h3>";
            echo "<table border='1'>
                    <tr>
                        <th>ช่วงเวลา</th>
                        <th>เวลาเริ่มต้น</th>
                        <th>เวลาสิ้นสุด</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>";

            while ($row = $result->fetch_assoc()) {
                // ซ่อนปุ่มเลือกหากสถานะคือ "ไม่ว่าง"
                    $buttonHTML = $row['status'] === 'ว่าง' 
                    ? "<button class='btn-select' onclick=\"selectBlock('{$row['block_id']}', '{$row['block_name']}')\">เลือก</button>" 
                    : "";

                echo "<tr>
                        <td>{$row['block_name']}</td>
                        <td>{$row['start_time']}</td>
                        <td>{$row['end_time']}</td>
                        <td>{$row['status']}</td>
                        <td>$buttonHTML</td>
                    </tr>";
            }
            echo "</table>";
        ?>
    </div>
    
    <div class="container">
    <h3>แบบฟอร์มขอนัดหมาย</h3>
    <form id="appointmentForm" method="POST">
        <div class="form-group">
            <label for="schedule_date">วันที่:<span style="color: red;">*</span></label>
                <?php $selected_date_buddhist = date('Y-m-d', strtotime('+543 years', strtotime($selected_date)));?>
                <input type="text" id="schedule_date" name="schedule_date" value="<?php echo $selected_date_buddhist; ?>" readonly>
        </div>

        <div class="form-group">
            <label for="block_name">ช่วงเวลา:<span style="color: red;">*</span></label>
            <input type="text" placeholder="ช่วงเวลา" id="block_name" readonly>
            <input type="hidden" id="block_id" name="block_id">
        </div>
        
        <div class="form-group">
            <label for="schedule_typeID">ประเภทการนัดหมาย:<span style="color: red;">*</span></label>
            <select id="schedule_typeID" name="schedule_typeID" required>
                <option value="">-- เลือกประเภทการนัดหมาย --</option>
                <?php foreach ($scheduleTypes as $type): ?>
                    <option value="<?php echo $type['schedule_typeID']; ?>"><?php echo $type['schedule_type_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="schedule_detail">รายละเอียดการนัดหมาย:<span style="color: red;">*</span></label>
            <textarea id="schedule_detail" name="schedule_detail" placeholder="กรุณากรอกรายละเอียดการนัดหมาย" rows="6" required></textarea>
        </div>

        <button class="submit-btn" type="submit" name="submit_appointment">ยืนยันการนัดหมาย</button>
    </form>
    </div>
</body>
</html>
