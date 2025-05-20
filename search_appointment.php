<?php
include('auth.php');
include('condb.php');

// รับค่า schedule_id จาก URL (GET) และ POST
$schedule_id_from_url = $_GET['schedule_id'] ?? '';
$search_term = $_POST['search_term'] ?? $schedule_id_from_url;

// รับค่าจาก POST
$room_id = $_POST['room_id'] ?? '';
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$schedule_status = $_POST['schedule_status'] ?? '';
$appointments = [];
$search_performed = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' || !empty($schedule_id_from_url)) {
    $search_performed = true;

    // SQL พื้นฐาน
    $sql = "SELECT s.schedule_id, s.schedule_detail, s.schedule_date, s.block_id, 
                   st.schedule_type_name, ss.schedule_status_name, 
                   b.start_time, b.end_time, b.block_name, c.room_id, 
                   s.service_statusID, svs.service_name AS service_status_name, s.description
            FROM schedule s
            LEFT JOIN schedule_type st ON s.schedule_typeID = st.schedule_typeID
            LEFT JOIN schedule_status ss ON s.schedule_statusID = ss.schedule_statusID
            LEFT JOIN time_block b ON s.block_id = b.block_id
            LEFT JOIN contract c ON s.contract_id = c.contract_id
            LEFT JOIN service_status svs ON s.service_statusID = svs.service_statusID
            WHERE 1=1";

    $conditions = [];
    $params = [];
    $types = '';

    // ค้นหาด้วยคำค้นหา
    if (!empty($search_term)) {
        $conditions[] = "(s.schedule_id LIKE ? OR s.schedule_detail LIKE ?)";
        $like_term = "%$search_term%";
        $params[] = $like_term;
        $params[] = $like_term;
        $types .= 'ss';
    }

    // ค้นหาด้วยห้องพัก
    if (!empty($room_id)) {
        $conditions[] = "c.room_id = ?";
        $params[] = $room_id;
        $types .= 's';
    }

    // ค้นหาด้วยเดือนและปี
    if (!empty($month)) {
        $conditions[] = "MONTH(s.schedule_date) = ?";
        $params[] = $month;
        $types .= 's';
    }

    if (!empty($year)) {
        $conditions[] = "YEAR(s.schedule_date) = ?";
        $params[] = $year;
        $types .= 's';
    }

    // ค้นหาด้วยสถานะการนัดหมาย
    if (!empty($schedule_status)) {
        $conditions[] = "s.schedule_statusID = ?";
        $params[] = $schedule_status;
        $types .= 's';
    }

    // เพิ่มเงื่อนไขลงใน SQL
    if (!empty($conditions)) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY s.schedule_date DESC";

    // เตรียมและดำเนินการ SQL
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ดึงข้อมูลสถานะการนัดหมาย
$status_query = "SELECT schedule_statusID, schedule_status_name FROM schedule_status";
$status_result = mysqli_query($conn, $status_query);
$statuses = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $statuses[] = $row;
}

// เดือนภาษาไทย
$thai_months = ['01' => 'มกราคม','02' => 'กุมภาพันธ์','03' => 'มีนาคม','04' => 'เมษายน',
                '05' => 'พฤษภาคม','06' => 'มิถุนายน','07' => 'กรกฎาคม','08' => 'สิงหาคม',
                '09' => 'กันยายน','10' => 'ตุลาคม','11' => 'พฤศจิกายน','12' => 'ธันวาคม'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหานัดหมาย</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            max-width: 1600px;
            margin: 80px auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 300px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        select, input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 295px;
        }
        button {
            background-color: #6633FF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #6600FF;
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
        .edit-btn, .delete-btn, .toggle-btn, .mail-btn {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            text-decoration: none;
        }
        .toggle-btn {
            background-color: #9b59b6;
        }
        .toggle-btn:hover {
            background-color: #8e44ad;
        }
        .extra-info {
            display: none;
            background-color: #f9f9f9;
        }
        .edit-btn {
            background-color: #337ab7;
        }
        .edit-btn:hover {
            background-color: #286090;
        }
        .mail-btn {
            background-color: #fd7e14;
        }
        .mail-btn:hover {
            background-color: #e06a00;
        }
    </style>
   <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".toggle-btn").forEach(button => {
                button.addEventListener("click", function () {
                    const extraInfoRow = this.closest("tr").nextElementSibling;
                    extraInfoRow.style.display = extraInfoRow.style.display === "none" ? "table-row" : "none";
                    this.textContent = this.textContent === "รายละเอียด" ? "ซ่อนรายละเอียด" : "รายละเอียด";
                });
            });
        });
    </script>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container">
        <h2>ค้นหานัดหมาย</h2>
        <form method="post" action="search_appointment.php" class="form-container">
            <!-- Dropdown ห้อง -->
            <div class="form-group">
                <label for="room_id">เลือกห้อง:</label>
                <select name="room_id" id="room_id">
                    <option value="">-- เลือกห้อง --</option>
                    <?php
                    $room_query = "SELECT room_id FROM room ORDER BY CASE
                                                    WHEN room_id LIKE '%A' THEN 1
                                                    WHEN room_id LIKE '%B' THEN 2
                                                    WHEN room_id LIKE '%C' THEN 3
                                                    WHEN room_id LIKE '%D' THEN 4
                                                    ELSE 5
                                                END, 
                                                room_id ASC";
                    $room_result = mysqli_query($conn, $room_query);
                    while ($row = mysqli_fetch_assoc($room_result)) {
                        echo "<option value='{$row['room_id']}'" . (($room_id == $row['room_id']) ? ' selected' : '') . ">{$row['room_id']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Dropdown เดือน -->
            <div class="form-group">
                <label for="month">เลือกเดือน:</label>
                <select name="month" id="month">
                    <option value="">-- เลือกเดือน --</option>
                    <?php foreach ($thai_months as $key => $month_name): ?>
                        <option value="<?= $key ?>" <?= ($month == $key) ? 'selected' : '' ?>><?= $month_name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dropdown ปี -->
            <div class="form-group">
                <label for="year">ปี:</label>
                <select name="year" id="year">
                    <option value="">-- เลือกปี --</option>
                    <?php for ($y = date('Y') + 543; $y >= 2000 + 543; $y--): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Dropdown สถานะ -->
            <div class="form-group">
                <label for="schedule_status">สถานะการนัดหมาย:</label>
                <select name="schedule_status" id="schedule_status">
                    <option value="">-- เลือกสถานะ --</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status['schedule_statusID'] ?>" <?= ($schedule_status == $status['schedule_statusID']) ? 'selected' : '' ?>>
                            <?= $status['schedule_status_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- ช่องค้นหา -->
            <div class="form-group">
                <label for="search_term">ค้นหา (รหัสนัดหมาย, รายละเอียด):</label>
                <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
            </div>

            <button type="submit">ค้นหา</button>
        </form>

        <!-- ตารางแสดงผล -->
        <?php if (!empty($appointments)): ?>
            <table>
                <thead>
                    <tr>
                        <th>รหัสนัดหมาย</th>
                        <th>วันที่</th> 
                        <th>ห้องพัก</th>                    
                        <th>สถานะ</th>
                        <th>การดำเนินการ</th>
                        <th>ตัวเลือก</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr class="main-row">
                            <td><?= htmlspecialchars($appointment['schedule_id']); ?></td>
                            <td><?= htmlspecialchars($appointment['schedule_date']); ?></td>
                            <td><?= htmlspecialchars($appointment['room_id']); ?></td>
                            <td><?= htmlspecialchars($appointment['schedule_status_name']); ?></td>
                            <td><?= htmlspecialchars($appointment['service_status_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>  
                            <td>
                                <button class="toggle-btn">รายละเอียด</button>
                                    <?php if (isset($appointment['schedule_status_name']) && 
                                    ($appointment['schedule_status_name'] === 'รอการอนุมัติ' || $appointment['schedule_status_name'] !== 'ไม่อนุมัติ') && 
                                    (empty($appointment['service_status_name']) || $appointment['service_status_name'] !== 'ดำเนินการเรียบร้อย')): ?>
                                    <a href="edit_appointment.php?schedule_id=<?= htmlspecialchars($appointment['schedule_id']); ?>" class="edit-btn">แก้ไข</a>
                                <?php endif; ?>
                                <?php if ($appointment['service_status_name'] !== 'ดำเนินการเรียบร้อย'): ?>
                                    <a href="mail_appointment.php?schedule_id=<?= htmlspecialchars($appointment['schedule_id']); ?>" 
                                    onclick="return confirm('ส่งอีเมลแจ้งสถานะการนัดหมาย?');" target="_blank" class="mail-btn">ส่งอีเมล</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="extra-info" style="display: none;">
                            <td colspan="6">
                                <table style="width: 100%;">
                                    <tr>                                   
                                        <th>ประเภท</th>
                                        <th>ช่วงเวลา</th>
                                        <th>ระยะเวลา</th>
                                        <th>รายละเอียด</th>
                                        <th>หมายเหตุ</th>                              
                                    </tr>
                                    <tr>   
                                        <td><?= htmlspecialchars($appointment['schedule_type_name']); ?></td>
                                        <td><?= htmlspecialchars($appointment['block_name']); ?></td>
                                        <td><?= htmlspecialchars($appointment['start_time'] . " ถึง " . $appointment['end_time']); ?></td> 
                                        <td><?= htmlspecialchars($appointment['schedule_detail']); ?></td>
                                        <td><?= htmlspecialchars($appointment['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>    
                                    </tr>
                                </table><br>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>ไม่พบผลลัพธ์ที่ตรงกับการค้นหา</p>
        <?php endif; ?>
    </div>
</body>
</html>