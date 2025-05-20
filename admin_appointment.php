<?php
include('auth.php');
include('condb.php');

// ดึงข้อมูลสัญญาเช่าทั้งหมด
$sql_contracts = "SELECT c.contract_id, c.room_id, 
                        GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS tenant_names
                  FROM contract c
                  JOIN contract_user cu ON c.contract_id = cu.contract_id
                  JOIN user u ON cu.user_id = u.user_id
                  WHERE c.contract_status = 'กำลังมีผล'
                  GROUP BY c.contract_id, c.room_id";
$result_contracts = $conn->query($sql_contracts);
$contracts = [];
if ($result_contracts && $result_contracts->num_rows > 0) {
    while ($row = $result_contracts->fetch_assoc()) {
        $contracts[] = $row;
    }
}

// ดึงข้อมูลประเภทการนัดหมายจาก schedule_type
$sql_schedule_types = "SELECT * FROM schedule_type";
$result_schedule_types = $conn->query($sql_schedule_types);
$scheduleTypes = [];
if ($result_schedule_types->num_rows > 0) {
    while ($row = $result_schedule_types->fetch_assoc()) {
        $scheduleTypes[] = $row;
    }
}

// เมื่อกด "ยืนยันการนัดหมาย"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $block_id = $_POST['block_id'];
    $schedule_typeID = $_POST['schedule_typeID'];
    $schedule_detail = $_POST['schedule_detail'];
    $schedule_date = $_POST['schedule_date'];
    $contract_id = $_POST['contract_id'];

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
        echo "<script> alert('บันทึกการนัดหมายสำเร็จ'); window.location.href = 'search_appointment.php?schedule_id=$schedule_id'; </script>";
        exit();
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกการนัดหมาย');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มการนัดหมาย</title>
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
        
    </style>
    <script>
        function selectBlock(block_id, block_name) {
            document.getElementById('block_id').value = block_id;
            document.getElementById('block_name').value = block_name;
        }
        
    </script>
</head>
<body> 
    <?php include('admin_sidebar.php');
          include('calendar2.php'); 
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
        <h3>เพิ่มการนัดหมาย</h3>
        <form id="appointmentForm" method="POST">
            <div class="form-group">
                <label for="contract_id">เลือกห้อง/ผู้เช่า:<span style="color: red;">*</span></label>
                <select name="contract_id" id="contract_id" required>
                    <option value="">-- เลือกห้อง/ผู้เช่า --</option>
                    <?php foreach ($contracts as $contract): ?>
                        <option value="<?php echo $contract['contract_id']; ?>">
                            ห้อง <?php echo $contract['room_id']; ?> - 
                            <?php echo $contract['tenant_names']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="block_name">ช่วงเวลา:<span style="color: red;">*</span></label>
                <input type="text" id="block_name" placeholder="ช่วงเวลา" readonly>
                <input type="hidden" id="block_id" name="block_id">
            </div>
            
            <div class="form-group">
                <label for="schedule_date">วันที่:<span style="color: red;">*</span></label>
                <?php $selected_date_buddhist = date('Y-m-d', strtotime('+543 years', strtotime($selected_date)));?>
                <input type="text" id="schedule_date" name="schedule_date" value="<?php echo $selected_date_buddhist; ?>" readonly>
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
                <textarea id="schedule_detail" name="schedule_detail" placeholder="รายละเอียดการนัดหมาย" rows="6" required></textarea>
            </div>
            
            <button class="submit-btn" type="submit" name="submit_appointment">บันทึก</button>
        </form>
    </div>
</body>
</html>
