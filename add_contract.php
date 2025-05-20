<?php 
include('auth.php');
include('condb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_ids = $_POST['user_id']; // รับค่าเป็น array
    $room_id = $_POST['room_id'];
    $contract_name = $_POST['contract_name'];
    $contract_detail = $_POST['contract_detail'];
    $contract_start = $_POST['contract_start'];
    $contract_end = $_POST['contract_end'];
    $deposit = $_POST['deposit'];

    // แปลงวันที่เป็น พ.ศ.
    $contract_start_date = new DateTime($contract_start);
    $contract_start_year = (int)$contract_start_date->format('Y') + 543;
    $contract_start = $contract_start_date->setDate($contract_start_year, (int)$contract_start_date->format('m'), (int)$contract_start_date->format('d'))->format('Y-m-d');

    $contract_end_date = new DateTime($contract_end);
    $contract_end_year = (int)$contract_end_date->format('Y') + 543;
    $contract_end = $contract_end_date->setDate($contract_end_year, (int)$contract_end_date->format('m'), (int)$contract_end_date->format('d'))->format('Y-m-d');

    // ตรวจสอบสถานะห้อง
    $sql_check_room = "SELECT room_status FROM room WHERE room_id = ? AND room_status = 'ไม่ว่าง'";
    if ($stmt_check_room = $conn->prepare($sql_check_room)) {
        $stmt_check_room->bind_param("s", $room_id);
        $stmt_check_room->execute();
        $stmt_check_room->store_result();

        if ($stmt_check_room->num_rows > 0) {
            echo "<script>alert('ห้องไม่ว่าง ไม่สามารถเลือกได้'); window.history.back();</script>";
            exit();
        }
        $stmt_check_room->close();
    }

    // สร้าง contract_id ใหม่
    $sql_max_id = "SELECT MAX(CAST(SUBSTRING(contract_id, 9) AS UNSIGNED)) AS max_id FROM contract WHERE contract_id LIKE 'contract%'";
    $result_max_id = $conn->query($sql_max_id);
    if ($result_max_id) {
        $row_max_id = $result_max_id->fetch_assoc();
        $max_id = $row_max_id['max_id'] ? $row_max_id['max_id'] : 0;

        $contract_id = 'contract' . str_pad(($max_id + 1), 2, '0', STR_PAD_LEFT);

        // บันทึกข้อมูลลงใน contract
        $target_dir = "contract/";
        $contract_img = $target_dir . basename($_FILES["contract_img"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($contract_img, PATHINFO_EXTENSION));

        if ($imageFileType != "jpg" && $imageFileType != "jpeg" && $imageFileType != "png" && $imageFileType != "pdf") {
            echo "<script>alert('ขออภัย, อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & PDF เท่านั้น.');</script>";
            $uploadOk = 0;
        }

        if ($uploadOk && move_uploaded_file($_FILES["contract_img"]["tmp_name"], $contract_img)) {
            $sql = "INSERT INTO contract (contract_id, room_id, contract_name, contract_detail, contract_start, contract_end, contract_img, deposit, contract_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'กำลังมีผล')";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssssss", $contract_id, $room_id, $contract_name, $contract_detail, $contract_start, $contract_end, $contract_img, $deposit);
                if ($stmt->execute()) {
                    // บันทึกข้อมูลลงใน contract_user
                    foreach ($user_ids as $user_id) {
                        if (!empty($user_id)) { // ตรวจสอบว่าผู้เช่าไม่ใช่ค่าว่าง
                            $sql_contract_user = "INSERT INTO contract_user (contract_id, user_id) VALUES (?, ?)";
                            if ($stmt_contract_user = $conn->prepare($sql_contract_user)) {
                                $stmt_contract_user->bind_param("ss", $contract_id, $user_id);
                                $stmt_contract_user->execute();
                                $stmt_contract_user->close();
                            }
                        }
                    }

                    // อัปเดตสถานะห้อง
                    $new_room_status = 'ไม่ว่าง';
                    $sql_update_room = "UPDATE room SET room_status = ? WHERE room_id = ?";
                    if ($stmt_update_room = $conn->prepare($sql_update_room)) {
                        $stmt_update_room->bind_param("ss", $new_room_status, $room_id);
                        if ($stmt_update_room->execute()) {
                            echo "<script>alert('สร้างสัญญาและบันทึกข้อมูลผู้เช่าเรียบร้อยแล้ว'); window.location.href='search_contract.php?contract_id=" . urlencode($contract_id) . "';</script>";
                        } else {
                            echo "<script>alert('เกิดข้อผิด: ไม่สามารถอัปเดตสถานะห้องได้');</script>";
                        }
                        $stmt_update_room->close();
                    }
                } else {
                    echo "<script>alert('เกิดข้อผิดในการเพิ่มสัญญา: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            }
        } else {
            echo "<script>alert('เกิดข้อผิดในการอัปโหลดไฟล์');</script>";
        }
    } else {
        echo "<script>alert('เกิดข้อผิดในการดึงข้อมูลสูงสุดของ contract_id');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสัญญา</title>
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
        }

        textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            height: 150px; 
        }

        input[type="file"] {
            padding: 5px;
        }

        button {
            background-color: #fe2d85;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #e5005f;
        }
    </style>
</head>
<body>
<?php include('admin_sidebar.php'); ?>
<div class="content">
    <form action="add_contract.php" method="post" enctype="multipart/form-data">
    <h2>เพิ่มสัญญา</h2>

        <div class="form-group">
            <label for="user_id">ผู้เช่า 1:<span style="color: red;">*</span></label>
            <select name="user_id[]" required>
                <option value="">-- เลือกผู้เช่า --</option>
                <?php
                $sql = "SELECT user_id, first_name, last_name FROM user";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['user_id'] . '">' . $row['user_id'] . ' (' . $row['first_name'] . ' ' . $row['last_name'] . ')</option>';
                }
                ?>
            </select>
            <button type="button" class="delete-btn" style="display: none;">ลบ</button>
        </div>
        
        <div class="form-group">
            <label for="user_id">ผู้เช่า 2:</label>
            <select name="user_id[]">
                <option value="">-- เลือกผู้เช่า (ถ้ามี) --</option>
                <?php
                $sql = "SELECT user_id, first_name, last_name FROM user";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['user_id'] . '">' . $row['user_id'] . ' (' . $row['first_name'] . ' ' . $row['last_name'] . ')</option>';
                }
                ?>
            </select>
            <button type="button" class="delete-btn" style="display: none;">ลบ</button>
        </div>
        
        <div class="form-group">
            <label for="room_id">ห้อง:<span style="color: red;">*</span></label>
            <select name="room_id" required>
                <option value="">-- เลือกห้อง --</option>
                <?php
                $sql = "SELECT room_id FROM room ORDER BY CASE
                            WHEN room_id LIKE '%A' THEN 1
                            WHEN room_id LIKE '%B' THEN 2
                            WHEN room_id LIKE '%C' THEN 3
                            WHEN room_id LIKE '%D' THEN 4
                            ELSE 5
                        END, room_id ASC";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['room_id'] . "'>" . $row['room_id'] . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="contract_name">ชื่อสัญญา:<span style="color: red;">*</span></label>
            <input type="text" name="contract_name" placeholder="ชื่อสัญญา" required>
        </div>

        <div class="form-group">
            <label for="contract_detail">รายละเอียดสัญญา:</label>
            <textarea name="contract_detail" rows="6" placeholder="รายละเอียดสัญญา"></textarea>
        </div>

        <div class="form-group">
            <label for="contract_start">วันเริ่มต้นสัญญา:<span style="color: red;">*</span></label>
            <input type="date" name="contract_start" required>
        </div>

        <div class="form-group">
            <label for="contract_end">วันสิ้นสุดสัญญา:<span style="color: red;">*</span></label>
            <input type="date" name="contract_end" required>
        </div>

        <div class="form-group">
            <label for="contract_img">ไฟล์รูปภาพสัญญา <br> (jpg, jpeg, png, pdf):<span style="color: red;">*</span></label>
            <input type="file" name="contract_img" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>

        <div class="form-group">
            <label for="deposit">ค่ามัดจำ:<span style="color: red;">*</span></label>
            <input type="text" name="deposit" placeholder="ค่ามัดจำ" required>
        </div>

        <button type="submit">เพิ่มสัญญา</button>
    </form>
</div>

</body>
</html>