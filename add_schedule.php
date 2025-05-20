<?php
include('auth.php');
include('condb.php');

// ดึงข้อมูล block ทั้งหมด
$sql_blocks = "SELECT * FROM time_block ORDER BY block_id ASC";
$result_blocks = $conn->query($sql_blocks);

// ตรวจสอบว่ากำลังแก้ไขหรือไม่
$editing = isset($_GET['edit_id']);
$schedule_data = [
    'block_id' => '',
    'block_name' => '',
    'start_time' => '',
    'end_time' => ''
];

// หากเป็นการแก้ไข ให้ดึงข้อมูล block
if ($editing) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT * FROM time_block WHERE block_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule_data = $result->fetch_assoc();
}

// การเพิ่ม block
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $block_name = $_POST['block_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // สร้าง block_id ใหม่
    $sql_latest_id = "SELECT block_id FROM time_block ORDER BY block_id DESC LIMIT 1";
    $result_latest_id = $conn->query($sql_latest_id);
    $latest_id = $result_latest_id->fetch_assoc()['block_id'] ?? 'block00';
    $next_id = str_pad((int)substr($latest_id, 5) + 1, 2, "0", STR_PAD_LEFT);
    $block_id = "block" . $next_id;

    $sql = "INSERT INTO time_block (block_id, block_name, start_time, end_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $block_id, $block_name, $start_time, $end_time);

    if ($stmt->execute()) {
        echo "<script>alert('เพิ่มตารางเวลาสำเร็จ'); window.location.href = 'add_schedule.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการเพิ่มข้อมูล');</script>";
    }
    $stmt->close();
}

// การแก้ไข block
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_schedule'])) {
    $block_id = $_POST['block_id'];
    $block_name = $_POST['block_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $sql = "UPDATE time_block SET block_name = ?, start_time = ?, end_time = ? WHERE block_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $block_name, $start_time, $end_time, $block_id);

    if ($stmt->execute()) {
        echo "<script>alert('แก้ไขตารางเวลาสำเร็จ'); window.location.href = 'add_schedule.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการแก้ไขข้อมูล');</script>";
    }
    $stmt->close();
}

// การลบ block
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM time_block WHERE block_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('ลบตารางเวลาสำเร็จ'); window.location.href = 'add_schedule.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการลบตารางเวลา');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการตารางเวลา</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 80px;
        }
        .container, .edit-form-container {
            width: 100%;
            max-width: 1500px;
            margin: 10px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .time-table-wrapper {
            width: 78%;
            max-width: 1500px;
            margin: 10px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 13.5%;
        }
        input[type="text"], input[type="time"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            width: 98%;
        }
        .form-group label {
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #17202a;
            height: fit-content;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #e8daef;
        }
        td {
            padding: 15px;
        }
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        tr:nth-child(odd) {
            background-color: #f5eef8;
        }
        .time-table-wrapper table th, .time-table-wrapper table td {
            text-align: center;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        p {
            text-align: center;
        }
        .submit-btn {
            background-color: #fe2d85;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 98%;
        }
        .submit-btn:hover {
            background-color: #e5005f;
        }
        .edit-btn, .delete-btn {
            background-color: #337ab7;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .delete-btn {
            background-color: #d9534f;
            margin-left: 10px;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }     
        .edit-btn:hover {
            background-color: #286090;
        }
        .flex-container {
            display: flex;
            justify-content: space-around;
            width: 80%;
            margin-left: 13%;
        }
    </style>
</head>
<body>
    <?php include('admin_sidebar.php')?>
    <div class="container-wrapper">
        <div class="flex-container">
            <div class="container">
                <h2>เพิ่มตารางเวลา</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="block_name">ช่วงเวลา:<span style="color: red;">*</span></label>
                        <input type="text" id="block_name" placeholder="ช่วงเวลา เช่น 8 โมงเช้า" name="block_name" required>
                    </div>
                    <div class="form-group">
                        <label for="start_time">เวลาเริ่มต้น:<span style="color: red;">*</span></label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">เวลาสิ้นสุด:<span style="color: red;">*</span></label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                    <button type="submit" name="add_schedule" class="submit-btn">เพิ่ม</button>
                </form>
            </div>

            <div class="edit-form-container">
                <h2>แก้ไขตารางเวลา</h2>
                <?php if ($editing && $schedule_data): ?>
                <form method="post">
                    <div class="form-group">
                        <label for="block_id">รหัส Block:<span style="color: red;">*</span></label>
                        <input type="text" placeholder="รหัส Block" name="block_id" id="block_id" value="<?php echo htmlspecialchars($schedule_data['block_id']); ?>" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="block_name">ช่วงเวลา:<span style="color: red;">*</span></label>
                        <input type="text" placeholder="ช่วงเวลา เช่น 8 โมงเช้า" name="block_name" id="block_name" value="<?php echo htmlspecialchars($schedule_data['block_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="start_time">เวลาเริ่มต้น:<span style="color: red;">*</span></label>
                        <input type="time" name="start_time" id="start_time" value="<?php echo htmlspecialchars($schedule_data['start_time']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">เวลาสิ้นสุด:<span style="color: red;">*</span></label>
                        <input type="time" name="end_time" id="end_time" value="<?php echo htmlspecialchars($schedule_data['end_time']); ?>" required>
                    </div>
                    <button type="submit" name="edit_schedule" class="submit-btn">อัปเดตข้อมูล</button>
                </form>
                <?php else: ?>
                    <p>กรุณาเลือกรอบเวลาจากตารางเวลาเพื่อแก้ไขข้อมูล</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="time-table-wrapper">
            <h2>ตารางเวลา</h2>
            <table>
                <thead>
                    <tr>
                        <th>รหัส Block</th>
                        <th>ช่วงเวลา</th>
                        <th>เวลาเริ่มต้น</th>
                        <th>เวลาสิ้นสุด</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_blocks->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['block_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['block_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                            <td>
                                <a class="edit-btn" href="?edit_id=<?php echo htmlspecialchars($row['block_id']); ?>">แก้ไข</a>
                                <a class="delete-btn" href="?delete_id=<?php echo htmlspecialchars($row['block_id']); ?>" onclick="return confirm('คุณต้องการลบตารางเวลานี้หรือไม่?');">ลบ</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
