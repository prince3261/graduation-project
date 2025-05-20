<?php
include('auth.php');
include('condb.php');

// ตรวจสอบว่า `room_id` ถูกส่งมาหรือไม่
if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];

    // ดึงข้อมูลผู้เช่าจาก `contract_user` และ `user`
    $sql_user = "SELECT u.user_id, u.first_name, u.last_name, u.phone, u.email, u.user_address
                 FROM contract_user cu
                 JOIN user u ON cu.user_id = u.user_id
                 JOIN contract c ON cu.contract_id = c.contract_id
                 WHERE c.room_id = ? AND c.contract_status = 'กำลังมีผล'";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $room_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    // ดึงข้อมูลห้อง
    $sql_room = "SELECT room_id, room_detail, room_price FROM room WHERE room_id = ?";
    $stmt_room = $conn->prepare($sql_room);
    $stmt_room->bind_param("s", $room_id);
    $stmt_room->execute();
    $result_room = $stmt_room->get_result();
    $room = $result_room->fetch_assoc();

    // ดึงข้อมูลอุปกรณ์ในห้อง
    $sql = "SELECT e.equipment_id, re.room_eqID, e.equipment_detail, e.equipment_price, e.equipment_img 
            FROM equipment e
            JOIN room_equipment re ON e.equipment_id = re.equipment_id
            WHERE re.room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo "ไม่มีข้อมูลห้องที่ระบุ";
    exit();
}

// ฟังก์ชันสำหรับลบอุปกรณ์ในห้อง
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM room_equipment WHERE room_eqID = ?";
    $stmt_delete = $conn->prepare($delete_sql);
    $stmt_delete->bind_param("s", $delete_id);
    if ($stmt_delete->execute()) {
        echo "<script>alert('ลบอุปกรณ์เรียบร้อยแล้ว'); window.location.href = 'room_detail.php?room_id={$room_id}';</script>";
        exit();
    } else {
        echo "<script>alert('ไม่สามารถลบอุปกรณ์ได้'); window.history.back();</script>";
        exit();
    }
    $stmt_delete->close();
}

// ฟังก์ชันสำหรับอัปเดตราคาห้องและรายละเอียดห้อง
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_room_price'])) {
        $updated_room_price = $_POST['room_price'];
        $sql_update_price = "UPDATE room SET room_price = ? WHERE room_id = ?";
        $stmt_update_price = $conn->prepare($sql_update_price);
        $stmt_update_price->bind_param("ds", $updated_room_price, $room_id);

        if ($stmt_update_price->execute()) {
            echo "<script>alert('อัปเดตราคาห้องเรียบร้อยแล้ว'); window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตราคาห้อง');</script>";
        }
        $stmt_update_price->close();
    }

    if (isset($_POST['submit_room_detail'])) {
        $updated_room_detail = $_POST['room_detail'];
        $sql_update_detail = "UPDATE room SET room_detail = ? WHERE room_id = ?";
        $stmt_update_detail = $conn->prepare($sql_update_detail);
        $stmt_update_detail->bind_param("ss", $updated_room_detail, $room_id);

        if ($stmt_update_detail->execute()) {
            echo "<script>alert('อัปเดตรายละเอียดห้องเรียบร้อยแล้ว'); window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตรายละเอียดห้อง');</script>";
        }
        $stmt_update_detail->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดห้องและอุปกรณ์ภายในห้อง</title>
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
        .container, .room-details-container {
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #17202a;
        }
        .table2 {
            border: 1px solid #17202a;
            margin-top: 64px;
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
        .edit-btn, .delete-btn, .edit3-btn {
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
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }
        .edit2-btn {
            background-color: #337ab7;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 30px;
            margin-top: 5px;
        }
        .edit-btn:hover, .edit2-btn:hover, .edit3-btn:hover {
            background-color: #286090;
        }
        .edit2-btn:active {
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
    <?php include('admin_sidebar.php'); ?>
    <div class="container-wrapper">
        <div class="flex-container">
             <div class="container">
            <h2>ข้อมูลผู้เช่าห้อง <?php echo htmlspecialchars($room_id); ?></h2>
            <?php
            $tenant_count = 0;
            if ($result_user->num_rows > 0) {
                while ($user = $result_user->fetch_assoc()) {
                    $tenant_count++;
                    echo "<h3>ผู้เช่า {$tenant_count}</h3>";
                    echo "<table>";
                    echo "<tr><th>รหัสผู้เช่า</th><td>{$user['user_id']}</td></tr>";
                    echo "<tr><th>ชื่อ-นามสกุล</th><td>{$user['first_name']} {$user['last_name']}</td></tr>";
                    echo "<tr><th>เบอร์โทรศัพท์</th><td>{$user['phone']}</td></tr>";
                    echo "<tr><th>อีเมล</th><td>{$user['email']}</td></tr>";
                    echo "<tr><th>ที่อยู่</th><td>{$user['user_address']}</td></tr>";
                    echo "</table><br>";
                }
            } else {
                echo "<p>ไม่มีข้อมูลผู้เช่าในห้องนี้</p>";
            }
            ?>
        </div>

            <div class="room-details-container">
                <h2>รายละเอียดห้อง <?php echo htmlspecialchars($room_id); ?></h2>
                
                <form method="POST" action="">
                    <table class="table2">
                        <tr><th style="width: 125px;">เลขห้อง</th><td><?php echo htmlspecialchars($room['room_id']); ?></td></tr>
                        <tr>
                            <th>ราคาห้อง
                                <?php if (!isset($_POST['edit_room_price'])) { ?><form method="POST" action="">
                                <input type="submit" name="edit_room_price" value="แก้ไข" class="edit2-btn"></form>
                                <?php } ?>
                            </th>
                            <td>
                                <?php if (isset($_POST['edit_room_price'])) { ?>
                                    <textarea name="room_price" rows="4" style="width: 100%;"><?php echo htmlspecialchars($room['room_price']); ?></textarea>
                                    <input type="submit" name="submit_room_price" value="ยืนยันการแก้ไข" class="edit3-btn">
                                <?php } else { ?>
                                    <?php echo htmlspecialchars($room['room_price']); ?>
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <th>รายละเอียดห้อง
                                <?php if (!isset($_POST['edit_room_detail'])) { ?><form method="POST" action="">
                                <input type="submit" name="edit_room_detail" value="แก้ไข" class="edit2-btn"></form>
                                <?php } ?>
                            </th>
                            <td>
                                <?php if (isset($_POST['edit_room_detail'])) { ?>
                                    <textarea name="room_detail" rows="4" style="width: 100%;"><?php echo htmlspecialchars($room['room_detail']); ?></textarea>
                                    <input type="submit" name="submit_room_detail" value="ยืนยันการแก้ไข" class="edit3-btn">
                                <?php } else { ?>
                                    <?php echo htmlspecialchars($room['room_detail']); ?>
                                <?php } ?>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
        
        <div class="equipment-table-wrapper">
            <h2>อุปกรณ์ในห้อง <?php echo htmlspecialchars($room_id); ?></h2>
            <table>
                <tr>
                    <th>รหัสอุปกรณ์</th>
                    <th>ชื่ออุปกรณ์</th>
                    <th>รายละเอียดอุปกรณ์</th>
                    <th>ราคาอุปกรณ์</th>
                    <th>รูปภาพอุปกรณ์</th>
                    <th></th>
                </tr>
        
                <?php   
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$row['room_eqID']}</td>";
                            echo "<td>{$row['equipment_id']}</td>";
                            echo "<td>{$row['equipment_detail']}</td>";
                            echo "<td>" . number_format($row['equipment_price']) . " บาท</td>";
                            echo "<td>";
                            if (!empty($row['equipment_img'])) {
                                $img_path = "equipment/" . basename($row['equipment_img']);
                                echo "<img src='{$img_path}' alt='รูปภาพอุปกรณ์' style='max-width: 100px; max-height: 100px;'>";
                            } else {
                                echo "ไม่มีรูปภาพ";
                            }
                            echo "</td>";
                            echo "<td>";
                            echo "<a class='edit-btn' href='add_equipment.php?equipment_id={$row['equipment_id']}'>แก้ไข</a> &nbsp;";
                            echo "<a class='delete-btn' href='room_detail.php?room_id={$room_id}&delete_id={$row['room_eqID']}'
                                onclick='return confirm(\"คุณแน่ใจว่าต้องการลบอุปกรณ์นี้?\");'>ลบ</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>ไม่มีข้อมูลอุปกรณ์ในห้องนี้</td></tr>";
                    }
                    ?>
            </table>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$stmt_user->close();
$stmt_room->close();
$conn->close();
?>
