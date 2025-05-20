<?php
include('auth.php');
include('condb.php');

$sql_rooms = "SELECT room_id FROM room ORDER BY CASE
                WHEN room_id LIKE '%A' THEN 1
                WHEN room_id LIKE '%B' THEN 2
                WHEN room_id LIKE '%C' THEN 3
                WHEN room_id LIKE '%D' THEN 4
                ELSE 5 END, room_id ASC ";
$result_rooms = $conn->query($sql_rooms);
$rooms = [];
while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row['room_id'];
}

$sql_equipments = "SELECT * FROM equipment";
$result_equipments = $conn->query($sql_equipments);

if (isset($_GET['equipment_id'])) {
    $_GET['edit_id'] = $_GET['equipment_id'];
}

$editing = isset($_GET['edit_id']);
$equipment_data = [
    'equipment_id' => '',
    'equipment_detail' => '',
    'equipment_price' => '',
    'equipment_img' => ''
];

if ($editing) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT * FROM equipment WHERE equipment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment_data = $result->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_equipment'])) {
        $equipment_id = $_POST['equipment_id'];
        $equipment_detail = $_POST['equipment_detail'];
        $equipment_price = $_POST['equipment_price'];

        $target_dir = "equipment/";
        $equipment_img = $equipment_data['equipment_img'];

        if (!empty($_FILES["equipment_img"]["name"])) {
            $target_file = $target_dir . basename($_FILES["equipment_img"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $check = getimagesize($_FILES["equipment_img"]["tmp_name"]);
            if ($check === false) {
                echo "<script>alert('ไฟล์ที่อัปโหลดไม่ใช่ภาพ'); window.history.back();</script>";
                exit();
            }

            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'pdf'])) {
                echo "<script>alert('รองรับเฉพาะไฟล์ JPG, JPEG, PNG และ PDF เท่านั้น'); window.history.back();</script>";
                exit();
            }

            if (move_uploaded_file($_FILES["equipment_img"]["tmp_name"], $target_file)) {
                $equipment_img = $target_file;
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์'); window.history.back();</script>";
                exit();
            }
        }

        if ($editing) {
            $sql = "UPDATE equipment SET equipment_detail = ?, equipment_price = ?, equipment_img = ? WHERE equipment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $equipment_detail, $equipment_price, $equipment_img, $equipment_id);
        } else {
            $sql = "INSERT INTO equipment (equipment_id, equipment_detail, equipment_price, equipment_img) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $equipment_id, $equipment_detail, $equipment_price, $equipment_img);
        }

        if ($stmt->execute()) {
            echo "<script>alert('บันทึกข้อมูลสำเร็จ'); window.location.href = window.location.href.split('?')[0];</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');</script>";
        }
        $stmt->close();
    }

    if (isset($_POST['selected_equipments'], $_POST['room_ids'])) {
    $selected_equipments = $_POST['selected_equipments'];
    $room_ids = $_POST['room_ids'];

    $room_eqIDs = [];

    foreach ($room_ids as $room_id) {
        foreach ($selected_equipments as $equipment_id) {
            $sql_check = "SELECT COUNT(*) AS cnt FROM room_equipment WHERE room_id = ? AND equipment_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ss", $room_id, $equipment_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $row_check = $result_check->fetch_assoc();

            if ($row_check['cnt'] == 0) {
                $sql_max_id = "SELECT MAX(CAST(SUBSTRING(room_eqID, -2) AS UNSIGNED)) AS max_id 
                               FROM room_equipment 
                               WHERE equipment_id = ?";
                $stmt_max_id = $conn->prepare($sql_max_id);
                $stmt_max_id->bind_param("s", $equipment_id);
                $stmt_max_id->execute();
                $result_max_id = $stmt_max_id->get_result();
                $max_id_row = $result_max_id->fetch_assoc();
                $next_id = str_pad(($max_id_row['max_id'] ?? 0) + 1, 2, "0", STR_PAD_LEFT);

                $room_eqID = $equipment_id . $next_id;

                $sql_insert = "INSERT INTO room_equipment (room_eqID, room_id, equipment_id) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("sss", $room_eqID, $room_id, $equipment_id);

                if ($stmt_insert->execute()) {
                    $room_eqIDs[] = $room_eqID;
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาดในการเพิ่มอุปกรณ์ $equipment_id ลงในห้อง $room_id');</script>";
                }
            }
        }
    }

    if (!empty($room_eqIDs)) {
        $room_eqIDs_query = urlencode(implode(',', $room_eqIDs));
        echo "<script>
            alert('เพิ่มอุปกรณ์ที่เลือกลงในห้องเรียบร้อยแล้ว');
            window.location.href = 'search_equipment.php?room_eqIDs={$room_eqIDs_query}';
        </script>";
        exit();
    } else {
        echo "<script>alert('ไม่มีอุปกรณ์ใหม่ที่เพิ่มได้');</script>";
    }
}

}
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM equipment WHERE equipment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $delete_id);
    if ($stmt->execute()) {
        echo "<script>alert('ลบอุปกรณ์สำเร็จ'); window.location.href = window.location.href.split('?')[0];</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการลบอุปกรณ์');</script>";
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มและแก้ไขอุปกรณ์</title>
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
        .equipment-table-wrapper {
            width: 78%;
            max-width: 1500px;
            margin: 10px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 13.5%;
        }
        input[type="text"], select, textarea, input[type="file"] {
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
        table, th, td {
            border: 1px solid #17202a;
        }
        th, td {
            padding: 8px;
            text-align: left;
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
        .equipment-table-wrapper table th, .equipment-table-wrapper table td {
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
        .submit-btn:hover , .submit-btn2:hover {
            background-color: #e5005f;
        }
        .submit-btn2 {
            background-color: #fe2d85;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 13%;
            margin-left: 10px;
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
    <script>
        function toggleSelectAllEquipments(source) {
            const checkboxes = document.querySelectorAll('input[name="selected_equipments[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }

        function toggleSelectAllRooms(source) {
            const checkboxes = document.querySelectorAll('input[name="room_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }
    </script>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container-wrapper">
        <div class="flex-container">
            <div class="container">
                <h2>เพิ่มอุปกรณ์</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="equipment_id">ชื่ออุปกรณ์:<span style="color: red;">*</span></label>
                        <input type="text" placeholder="ชื่ออุปกรณ์" name="equipment_id" id="equipment_id" required>
                    </div>
                    <div class="form-group">
                        <label for="equipment_detail">รายละเอียดอุปกรณ์:</label>
                        <textarea name="equipment_detail" placeholder="รายละเอียดอุปกรณ์" id="equipment_detail" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="equipment_price">ราคาอุปกรณ์:<span style="color: red;">*</span></label>
                        <input type="text" name="equipment_price" placeholder="ราคาอุปกรณ์" id="equipment_price" required>
                    </div>
                    <div class="form-group">
                        <label for="equipment_img">รูปภาพอุปกรณ์:<span style="color: red;">*</span></label>
                        <input type="file" name="equipment_img" id="equipment_img" required>
                    </div>
                    <button type="submit" name="submit_equipment" class="submit-btn">เพิ่มอุปกรณ์</button>
                </form>
            </div>

            <div class="edit-form-container">
                <h2>แก้ไขอุปกรณ์</h2>
                <?php if ($editing && $equipment_data): ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="equipment_id">ชื่ออุปกรณ์:</label>
                        <input type="text" placeholder="ชื่ออุปกรณ์" name="equipment_id" id="equipment_id" value="<?php echo htmlspecialchars($equipment_data['equipment_id']); ?>" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="equipment_detail">รายละเอียดอุปกรณ์:</label>
                        <textarea name="equipment_detail" placeholder="รายละเอียดอุปกรณ์" id="equipment_detail" rows="4"><?php echo htmlspecialchars($equipment_data['equipment_detail']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="equipment_price">ราคาอุปกรณ์:</label>
                        <input type="text" name="equipment_price" placeholder="ราคาอุปกรณ์" id="equipment_price" value="<?php echo htmlspecialchars($equipment_data['equipment_price']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="equipment_img">รูปภาพอุปกรณ์:</label>
                        <input type="file" name="equipment_img" id="equipment_img">
                        <?php if (!empty($equipment_data['equipment_img'])): ?>
                            
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="submit_equipment" class="submit-btn">อัปเดตข้อมูล</button>
                </form>
                <?php else: ?>
                    <p>เลือกอุปกรณ์จากตารางอุปกรณ์เพื่อแก้ไขข้อมูล</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="equipment-table-wrapper">
            <form method="post" action="">
                <div class="room-container">
                    <h2>เพิ่มอุปกรณ์ในห้องพัก</h2> 
                    <label>
                        <input type="checkbox" id="select_all_rooms" onclick="toggleSelectAllRooms(this)">
                        เลือกทั้งหมด
                    </label>
                    <?php foreach ($rooms as $room_id): ?>
                        <label>
                            <input type="checkbox" name="room_ids[]" value="<?php echo htmlspecialchars($room_id); ?>">
                            <?php echo htmlspecialchars($room_id); ?>
                        </label>
                    <?php endforeach; ?>
                    <button type="submit" class="submit-btn2">บันทึกอุปกรณ์ที่เลือกในห้อง</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select_all_equipments" onclick="toggleSelectAllEquipments(this)">
                                เลือกทั้งหมด
                            </th>
                            <th>รหัสอุปกรณ์</th>
                            <th>รายละเอียด</th>
                            <th>ราคา</th>
                            <th>รูปภาพ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_equipments->num_rows > 0): ?>
                            <?php while ($row = $result_equipments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_equipments[]" value="<?php echo htmlspecialchars($row['equipment_id']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($row['equipment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['equipment_detail']); ?></td>
                                    <td><?php echo number_format($row['equipment_price']); ?> บาท</td>
                                    <td>
                                        <?php if (!empty($row['equipment_img'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['equipment_img']); ?>" style="max-width: 100px; max-height: 100px;">
                                        <?php else: ?>
                                            ไม่มีรูปภาพ
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="edit-btn" href="?edit_id=<?php echo htmlspecialchars($row['equipment_id']); ?>" class="btn btn-edit">แก้ไข</a>
                                        <a class= "delete-btn" href="?delete_id=<?php echo htmlspecialchars($row['equipment_id']); ?>" class="btn btn-delete" onclick="return confirm('คุณต้องการลบอุปกรณ์นี้หรือไม่?');">ลบ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">ไม่มีข้อมูลอุปกรณ์</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</body>
</html>
