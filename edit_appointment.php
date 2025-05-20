<?php
include('auth.php');
include('condb.php');

// รับ schedule_id จาก GET หรือ POST
$schedule_id = $_GET['schedule_id'] ?? $_POST['schedule_id'] ?? '';
$appointment = [];

// ดึงข้อมูลคำขอนัดหมาย
if (!empty($schedule_id)) {
    $sql = "SELECT s.*, tb.block_name, tb.start_time, tb.end_time, st.schedule_type_name, svs.service_name AS service_status_name,
                   c.room_id, GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS tenant_names
            FROM schedule s
            LEFT JOIN time_block tb ON s.block_id = tb.block_id
            LEFT JOIN schedule_type st ON s.schedule_typeID = st.schedule_typeID
            LEFT JOIN service_status svs ON s.service_statusID = svs.service_statusID
            LEFT JOIN contract c ON s.contract_id = c.contract_id
            LEFT JOIN contract_user cu ON c.contract_id = cu.contract_id
            LEFT JOIN user u ON cu.user_id = u.user_id
            WHERE s.schedule_id = ?
            GROUP BY s.schedule_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();
}

// ดึงข้อมูลคำขอทั้งหมด
$sql_requests = "SELECT s.schedule_id, c.room_id, GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS tenant_names
                 FROM schedule s
                 LEFT JOIN contract c ON s.contract_id = c.contract_id
                 LEFT JOIN contract_user cu ON c.contract_id = cu.contract_id
                 LEFT JOIN user u ON cu.user_id = u.user_id
                 WHERE (s.service_statusID != 'sv_status02' OR s.service_statusID IS NULL)
                    AND s.schedule_statusID != 'sc_status03'
                 GROUP BY s.schedule_id, c.room_id";
                 
$result_requests = $conn->query($sql_requests);
$requests = [];
while ($row = $result_requests->fetch_assoc()) {
    $requests[] = $row;
}

// ดึงข้อมูลประเภทการนัดหมาย
$sql_schedule_types = "SELECT * FROM schedule_type";
$result_schedule_types = $conn->query($sql_schedule_types);
$scheduleTypes = [];
while ($row = $result_schedule_types->fetch_assoc()) {
    $scheduleTypes[] = $row;
}

// ดึงข้อมูลสถานะการนัดหมาย
$sql_statuses = "SELECT * FROM schedule_status";
$result_statuses = $conn->query($sql_statuses);
$scheduleStatuses = [];
while ($row = $result_statuses->fetch_assoc()) {
    $scheduleStatuses[] = $row;
}

// ดึงข้อมูลสถานะการดำเนินการ
$sql_service_status = "SELECT * FROM service_status";
$result_service_status = $conn->query($sql_service_status);
$service_status = [];
while ($row = $result_service_status->fetch_assoc()) {
    $service_status[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $schedule_date = $_POST['schedule_date'];
    $block_id = $_POST['block_id'] ?? $appointment['block_id']; // ใช้ block_id เดิมหากไม่มีการเลือกใหม่
    $schedule_typeID = $_POST['schedule_typeID'];
    $schedule_detail = $_POST['schedule_detail'];
    $schedule_statusID = $_POST['schedule_statusID'] ?? $appointment['schedule_statusID']; // ใช้ค่าที่เลือกหรือค่าเดิม
    $service_statusID = $_POST['service_statusID'] ?? null; // ถ้าไม่มีค่าให้เป็น null
    $description = $_POST['description'] ?? null;

    // ตรวจสอบว่ามี schedule_id และ block_id หรือไม่
    if (empty($schedule_id) || empty($block_id)) {
        echo "<script>alert('ข้อมูลไม่ครบถ้วน กรุณาตรวจสอบ'); window.history.back();</script>";
        exit();
    }

    // เริ่มสร้างคำสั่ง SQL
    $sql_update = "UPDATE schedule 
                   SET schedule_date = ?, block_id = ?, schedule_typeID = ?, schedule_detail = ?, description = ?, schedule_statusID = ?";
    $params = [$schedule_date, $block_id, $schedule_typeID, $schedule_detail, $description, $schedule_statusID];
    $types = "ssssss";

    // เพิ่ม service_statusID เฉพาะกรณีที่มีการเลือก
    if (!empty($service_statusID)) {
        $sql_update .= ", service_statusID = ?";
        $params[] = $service_statusID;
        $types .= "s";
    }

    $sql_update .= " WHERE schedule_id = ?";
    $params[] = $schedule_id;
    $types .= "s";

    $stmt_update = $conn->prepare($sql_update);

    if ($stmt_update) {
        $stmt_update->bind_param($types, ...$params);

        if ($stmt_update->execute()) {
            echo "<script>alert('บันทึกการนัดหมายสำเร็จ'); window.location.href = 'search_appointment.php?schedule_id=$schedule_id';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตข้อมูล');</script>";
        }
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขการนัดหมาย</title>
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
    
</head>
<body> 
    <?php include('admin_sidebar.php');
          include('calendar3.php'); 
    ?>
    </div>
    <div class="container">
        <h3>แก้ไขการนัดหมาย</h3>
        <form method="POST">
            <div class="form-group">
                <label for="schedule_id">เลือกคำขอนัดหมาย:<span style="color: red;">*</span></label>
                <select name="schedule_id" id="schedule_id" onchange="this.form.submit()">
                    <option value="">-- เลือกคำขอนัดหมาย --</option>
                    <?php foreach ($requests as $request): ?>
                        <option value="<?php echo $request['schedule_id']; ?>" 
                            <?php echo ($request['schedule_id'] === $schedule_id) ? 'selected' : ''; ?>>
                            <?php echo $request['schedule_id']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="contract_id">ห้อง/ผู้เช่า:</label>
                <input type="text" id="contract_id" placeholder="ห้อง/ผู้เช่า" 
                    value="<?php echo trim(($appointment['room_id'] ?? '') . ' ' . ($appointment['tenant_names'] ?? '')); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="schedule_date">วันที่:</label>
                <input type="text" id="schedule_date" placeholder="วันที่" name="schedule_date" value="<?php echo $appointment['schedule_date'] ?? ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="block_name">ช่วงเวลา:</label>
                <input type="text" id="block_name" placeholder="ช่วงเวลา" value="<?php echo $appointment['block_name'] ?? ''; ?>" readonly>
                <input type="hidden" id="block_id" name="block_id" value="<?php echo $appointment['block_id'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="schedule_typeID">ประเภทการนัดหมาย:</label>
                <select id="schedule_typeID" name="schedule_typeID">
                    <?php foreach ($scheduleTypes as $type): ?>
                        <option value="<?php echo $type['schedule_typeID']; ?>" 
                            <?php echo ($type['schedule_typeID'] === ($appointment['schedule_typeID'] ?? '')) ? 'selected' : ''; ?>>
                            <?php echo $type['schedule_type_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="schedule_detail">รายละเอียดการนัดหมาย:</label>
                <textarea id="schedule_detail" placeholder="รายละเอียดการนัดหมาย" rows="6" name="schedule_detail"><?php echo $appointment['schedule_detail'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="schedule_statusID">การอนุมัติคำขอ:</label>
                <select id="schedule_statusID" name="schedule_statusID">
                    <option value="">-- เลือกสถานะการอนุมัติ --</option>
                    <?php foreach ($scheduleStatuses as $status): ?>
                        <option value="<?php echo $status['schedule_statusID']; ?>" 
                            <?php echo ($status['schedule_statusID'] === ($appointment['schedule_statusID'] ?? '')) ? 'selected' : ''; ?>>
                            <?php echo $status['schedule_status_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="service_statusID">การดำเนินการ:</label>
                <select id="service_statusID" name="service_statusID">
                    <option value="">-- เลือกการดำเนินการ --</option>
                    <?php foreach ($service_status as $status): ?>
                        <option value="<?php echo $status['service_statusID']; ?>"
                            <?php echo ($status['service_statusID'] === ($appointment['service_statusID'] ?? '')) ? 'selected' : ''; ?>>
                            <?php echo $status['service_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">หมายเหตุ:</label>
                <textarea id="description" rows="6" placeholder="หมายเหตุ" name="description"><?php echo $appointment['description'] ?? ''; ?></textarea>
            </div>

            <button class="submit-btn" type="submit" name="submit_appointment">อัปเดต</button>
        </form>
    </div>
</body>
</html>