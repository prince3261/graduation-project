<?php
include('auth.php');
include('condb.php');

// รับค่า parcel_id จาก GET หรือ POST
$parcel_id = isset($_GET['parcel_id']) ? $_GET['parcel_id'] : (isset($_POST['parcel_id']) ? $_POST['parcel_id'] : "");
$parcel_detail = "";
$room_id = "";
$received_date = "";
$parcel_statusID = "";

// สถานะพัสดุ (สมมติฐานว่าสถานะถูกเก็บในตาราง parcel_status)
$parcel_status = [];
$status_query = "SELECT parcel_statusID, parcel_status_name FROM parcel_status";
$status_result = mysqli_query($conn, $status_query);
while ($status_row = mysqli_fetch_assoc($status_result)) {
    $parcel_status[] = $status_row;
}

// ตรวจสอบว่ามีการส่ง parcel_id เข้ามาเพื่อแก้ไข
if (isset($_POST['parcel_id']) && !empty($_POST['parcel_id'])) {
    $parcel_id = $_POST['parcel_id'];

    // ดึงข้อมูลพัสดุจากฐานข้อมูล
    $query = "SELECT * FROM parcel WHERE parcel_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $parcel_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $parcel_detail = $row['parcel_detail'];
        $room_id = $row['room_id'];
        $received_date = $row['received_date'];
        $parcel_statusID = $row['parcel_statusID'];
    } else {
        echo "ไม่พบข้อมูลพัสดุที่ระบุ";
    }
}

// เพิ่มข้อมูลพัสดุ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_parcel'])) {
    $query = "SELECT parcel_id FROM parcel ORDER BY parcel_id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    $last_id = mysqli_fetch_assoc($result)['parcel_id'];

    // สร้าง parcel_id ใหม่
    if ($last_id) {
        $number = (int)substr($last_id, 6) + 1;
        $parcel_id = "parcel" . str_pad($number, 2, "0", STR_PAD_LEFT);
    } else {
        $parcel_id = "parcel01";
    }

    $parcel_detail = $_POST["parcel_detail"];
    $room_id = $_POST["room_id"];
    
    $received_date = date('Y-m-d', strtotime($_POST["received_date"] . ' +543 years'));

    $parcel_statusID = 'pa_status02';

    $insert_sql = "INSERT INTO parcel (parcel_id, parcel_detail, room_id, received_date, parcel_statusID) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssss", $parcel_id, $parcel_detail, $room_id, $received_date, $parcel_statusID);

    if ($stmt->execute()) {
        echo "<script>
                alert('เพิ่มข้อมูลพัสดุเรียบร้อยแล้ว');
                window.location.href = 'search_parcel.php?parcel_id=$parcel_id';
              </script>";
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// บันทึกการแก้ไขข้อมูลพัสดุ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_parcel'])) {
    $parcel_id = $_POST['parcel_id'];
    $parcel_detail = $_POST["parcel_detail"];
    $room_id = $_POST["room_id"];
    $received_date = $_POST["received_date"]; // ใช้วันที่ตรง ๆ จากฟอร์มแก้ไข
    $parcel_statusID = $_POST["parcel_statusID"];

    $update_sql = "UPDATE parcel 
                   SET parcel_detail = ?, room_id = ?, received_date = ?, parcel_statusID = ?
                   WHERE parcel_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssss", $parcel_detail, $room_id, $received_date, $parcel_statusID, $parcel_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('แก้ไขข้อมูลพัสดุเรียบร้อยแล้ว');
                window.location.href = 'search_parcel.php?parcel_id=$parcel_id';
              </script>";
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
if (!empty($parcel_id)) {
    $query = "SELECT * FROM parcel WHERE parcel_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $parcel_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $parcel_detail = $row['parcel_detail'];
        $room_id = $row['room_id'];
        $received_date = $row['received_date'];
        $parcel_statusID = $row['parcel_statusID'];
    } else {
        echo "<script>alert('ไม่พบข้อมูลพัสดุที่ระบุ');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มและแก้ไขพัสดุ</title>
</head>
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
        input[type="text"], select, textarea, input[type="date"] {
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

        .form-group label, .form-group2 label {
            width: 150px;
        }
        .flex-container {
            display: flex;
            width: 80%;
            margin-left: 14%;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        button {
            background-color: #fe2d85;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 15px;
        }
        button:hover {
            background-color: #e5005f;
        }
    </style>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container-wrapper">
        <div class="flex-container">
            <div class="container">
                <h2>เพิ่มพัสดุ</h2>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="room_id">ห้องพัก:<span style="color: red;">*</span></label>
                        <select name="room_id" required>
                            <option value="">-- เลือกห้องพัก --</option>
                            <?php
                            $room_query = $sql = "SELECT room_id FROM room ORDER BY CASE
                                                    WHEN room_id LIKE '%A' THEN 1
                                                    WHEN room_id LIKE '%B' THEN 2
                                                    WHEN room_id LIKE '%C' THEN 3
                                                    WHEN room_id LIKE '%D' THEN 4
                                                    ELSE 5
                                                  END, room_id ASC";
                            $room_result = mysqli_query($conn, $room_query);
                            while ($row = mysqli_fetch_assoc($room_result)) {
                                echo "<option value='{$row['room_id']}'>{$row['room_id']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="parcel_detail">รายละเอียดพัสดุ:</label>
                        <textarea name="parcel_detail" placeholder="รายละเอียดพัสดุ" rows="6"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="received_date">วันที่รับพัสดุ:<span style="color: red;">*</span></label>
                        <input type="date" name="received_date" required>
                    </div>
                    <button class="button" type="submit" name="add_parcel">เพิ่มพัสดุ</button>
                </form>
            </div>

            <div class="edit-form-container">
                <h2>แก้ไขพัสดุ</h2>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="parcel_id">รหัสพัสดุ:<span style="color: red;">*</span></label>
                        <select name="parcel_id" id="parcel_id" onchange="this.form.submit()">
                            <option value="">-- เลือกรหัสพัสดุ --</option>
                            <?php
                            $parcel_query = "SELECT parcel_id FROM parcel ORDER BY parcel_id ASC";
                            $parcel_result = mysqli_query($conn, $parcel_query);
                            while ($row = mysqli_fetch_assoc($parcel_result)) {
                                $selected = ($row['parcel_id'] == $parcel_id) ? "selected" : "";
                                echo "<option value='{$row['parcel_id']}' $selected>{$row['parcel_id']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="room_id">ห้องพัก:<span style="color: red;">*</span></label>
                        <select name="room_id" required>
                            <option value="">-- เลือกห้องพัก --</option>
                            <?php
                            $room_query = "SELECT room_id FROM room ORDER BY CASE
                                                WHEN room_id LIKE '%A' THEN 1
                                                WHEN room_id LIKE '%B' THEN 2
                                                WHEN room_id LIKE '%C' THEN 3
                                                WHEN room_id LIKE '%D' THEN 4
                                                ELSE 5
                                           END, room_id ASC";
                            $room_result = mysqli_query($conn, $room_query);
                            while ($row = mysqli_fetch_assoc($room_result)) {
                                $selected = ($row['room_id'] == $room_id) ? "selected" : "";
                                echo "<option value='{$row['room_id']}' $selected>{$row['room_id']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <input type="hidden" name="parcel_id" value="<?php echo htmlspecialchars($parcel_id); ?>">
                    <div class="form-group">
                        <label for="parcel_detail">รายละเอียดพัสดุ:</label>
                        <textarea name="parcel_detail" rows="6" placeholder="รายละเอียดพัสดุ"><?php echo htmlspecialchars($parcel_detail); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="received_date">วันที่รับพัสดุ:<span style="color: red;">*</span></label>
                        <input type="date" name="received_date" value="<?php echo htmlspecialchars($received_date); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="parcel_statusID">สถานะพัสดุ:<span style="color: red;">*</span></label>
                        <select name="parcel_statusID" required>
                            <option value="">-- เลือกสถานะพัสดุ --</option>
                            <?php
                            foreach ($parcel_status as $status) {
                                $selected = ($status['parcel_statusID'] == $parcel_statusID) ? "selected" : "";
                                echo "<option value='{$status['parcel_statusID']}' $selected>{$status['parcel_status_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" name="edit_parcel">อัปเดตพัสดุ</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
