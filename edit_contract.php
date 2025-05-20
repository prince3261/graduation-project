<?php
include('auth.php'); 
include('condb.php');

$contract_id = isset($_GET['contract_id']) ? $_GET['contract_id'] : (isset($_POST['contract_id']) ? $_POST['contract_id'] : null);

if ($contract_id) {
    $sql = "SELECT c.*, GROUP_CONCAT(DISTINCT u.user_id ORDER BY u.user_id SEPARATOR ',') AS user_ids,r.room_id
                FROM contract c
                LEFT JOIN contract_user cu ON c.contract_id = cu.contract_id
                LEFT JOIN user u ON cu.user_id = u.user_id
                LEFT JOIN room r ON c.room_id = r.room_id
                WHERE c.contract_id = ?
                GROUP BY c.contract_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $contract = $result->fetch_assoc();
    } else {
        echo "<script>alert('ไม่มีข้อมูลสัญญา'); window.location.href = 'edit_contract.php';</script>";
        exit();
    }
    $stmt->close();
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['select_contract'])) {
    $contract_id = $_POST['contract_id'];
    header("Location: edit_contract.php?contract_id=" . urlencode($contract_id));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_contract'])) {
    $contract_id = $_POST['contract_id'];
    $contract_name = $_POST['contract_name'];
    $contract_detail = $_POST['contract_detail'];
    $contract_start = $_POST['contract_start'];
    $contract_end = $_POST['contract_end'];
    $user_ids = [$_POST['user_id1'], $_POST['user_id2']]; // Separate tenants
    $room_id = $_POST['room_id'];
    $deposit = $_POST['deposit'];
    $contract_status = $_POST['contract_status'];

    $contract_start = (new DateTime($contract_start))->format('Y-m-d');
    $contract_end = (new DateTime($contract_end))->format('Y-m-d');

    $target_dir = "contract/";
    $contract_img = $contract['contract_img'];

    if ($_FILES["contract_img"]["error"] != UPLOAD_ERR_NO_FILE) {
        $target_file = $target_dir . basename($_FILES["contract_img"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'pdf'])) {
            move_uploaded_file($_FILES["contract_img"]["tmp_name"], $target_file);
            $contract_img = $target_file;
        } else {
            echo "<script>alert('ไฟล์ต้องเป็น JPG, JPEG, PNG หรือ PDF เท่านั้น');</script>";
            exit();
        }
    }

    $sql = "UPDATE contract 
        SET contract_name = ?, 
            contract_detail = ?, 
            contract_start = ?, 
            contract_end = ?, 
            room_id = ?, 
            contract_img = ?, 
            deposit = ?, 
            contract_status = ? 
        WHERE contract_id = ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param(
        "sssssssss", 
        $contract_name, 
        $contract_detail, 
        $contract_start, 
        $contract_end, 
        $room_id, 
        $contract_img, 
        $deposit, 
        $contract_status, 
        $contract_id
    );

    if ($stmt->execute()) {
        // Delete existing tenants for this contract
        $sql_delete_tenants = "DELETE FROM contract_user WHERE contract_id = ?";
        $stmt_delete_tenants = $conn->prepare($sql_delete_tenants);
        if ($stmt_delete_tenants) {
            $stmt_delete_tenants->bind_param("s", $contract_id);
            $stmt_delete_tenants->execute();
            $stmt_delete_tenants->close();
        }

        // Insert updated tenants
        foreach ($user_ids as $user_id) {
            if (!empty($user_id)) {
                $sql_add_tenant = "INSERT INTO contract_user (contract_id, user_id) VALUES (?, ?)";
                $stmt_add_tenant = $conn->prepare($sql_add_tenant);
                if ($stmt_add_tenant) {
                    $stmt_add_tenant->bind_param("ss", $contract_id, $user_id);
                    $stmt_add_tenant->execute();
                    $stmt_add_tenant->close();
                }
            }
        }

        // Update room status
        $room_status = ($contract_status == 'กำลังมีผล') ? 'ไม่ว่าง' : 'ว่าง';
        $sql_update_room_status = "UPDATE room SET room_status = ? WHERE room_id = ?";
        $stmt_update_room_status = $conn->prepare($sql_update_room_status);
        if ($stmt_update_room_status) {
            $stmt_update_room_status->bind_param("ss", $room_status, $room_id);
            $stmt_update_room_status->execute();
            $stmt_update_room_status->close();
        }

        echo "<script>alert('อัปเดตข้อมูลสำเร็จ'); window.location.href='search_contract.php?contract_id=" . urlencode($contract_id) . "';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตข้อมูล');</script>";
    }
    $stmt->close();
} else {
    echo "<script>alert('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL');</script>";
}

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสัญญา</title>
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
        label {
            display: block;
            margin: 10px 0 5px;
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
        textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            height: 150px; 
        }
        input[type="text"], input[type="date"], select, textarea, input[type="file"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #fe2d85;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 20px;
        }
        input[type="submit"]:hover {
            background-color: #e5005f;
        }
        h2 {
            text-align: center;
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
        <h2>แก้ไขสัญญา</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>เลือกสัญญา:</label>
                <select name="contract_id" onchange="this.form.submit()">
                    <option value="">-- เลือกรหัสสัญญา --</option>
                    <?php
                    $sql_contracts = "SELECT contract_id FROM contract";
                    $result_contracts = $conn->query($sql_contracts);
                    while ($row = $result_contracts->fetch_assoc()) {
                        $selected = ($row['contract_id'] == $contract_id) ? 'selected' : '';
                        echo "<option value='{$row['contract_id']}' $selected>{$row['contract_id']}</option>";
                    }
                    ?>
                </select>
                <input type="hidden" name="select_contract" value="1">
            </div>
            <?php if (isset($contract)) { ?>
                <div class="form-group">
                    <label>ผู้เช่า 1:</label>
                    <select name="user_id1" required>
                        <option value="">-- เลือกผู้เช่า 1 --</option>
                        <?php
                        $sql_users = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name FROM user";
                        $result_users = $conn->query($sql_users);
                        $selected_user_ids = explode(',', $contract['user_ids']);
                        while ($user = $result_users->fetch_assoc()) {
                            $selected = ($user['user_id'] == $selected_user_ids[0]) ? 'selected' : '';
                            echo "<option value='{$user['user_id']}' $selected>{$user['full_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ผู้เช่า 2 (ถ้ามี):</label>
                    <select name="user_id2">
                        <option value="">-- เลือกผู้เช่า 2 --</option>
                        <?php
                        $result_users = $conn->query($sql_users); // Re-run the query for the second dropdown
                        while ($user = $result_users->fetch_assoc()) {
                            $selected = (isset($selected_user_ids[1]) && $user['user_id'] == $selected_user_ids[1]) ? 'selected' : '';
                            echo "<option value='{$user['user_id']}' $selected>{$user['full_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ห้อง:</label>
                    <select name="room_id" required>
                        <option value="">-- เลือกห้อง --</option>
                        <?php
                        $sql_rooms = "SELECT room_id FROM room";
                        $result_rooms = $conn->query($sql_rooms);
                        while ($room = $result_rooms->fetch_assoc()) {
                            $selected = ($room['room_id'] == $contract['room_id']) ? 'selected' : '';
                            echo "<option value='{$room['room_id']}' $selected>{$room['room_id']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>สถานะสัญญา:</label>
                    <select name="contract_status" required>
                        <option value="กำลังมีผล" <?= $contract['contract_status'] == 'กำลังมีผล' ? 'selected' : '' ?>>กำลังมีผล</option>
                        <option value="สิ้นสุด" <?= $contract['contract_status'] == 'สิ้นสุด' ? 'selected' : '' ?>>สิ้นสุด</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ชื่อสัญญา:</label>
                    <input type="text" name="contract_name" value="<?= htmlspecialchars($contract['contract_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>รายละเอียดสัญญา:</label>
                    <textarea name="contract_detail"><?= htmlspecialchars($contract['contract_detail']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>วันที่เริ่มต้น:</label>
                    <input type="date" name="contract_start" value="<?= $contract['contract_start'] ?>" required>
                </div>
                <div class="form-group">
                    <label>วันที่สิ้นสุด:</label>
                    <input type="date" name="contract_end" value="<?= $contract['contract_end'] ?>" required>
                </div>
                <div class="form-group">
                    <label>ค่ามัดจำ:</label>
                    <input type="text" name="deposit" value="<?= $contract['deposit'] ?>" required>
                </div>
                <div class="form-group">
                    <label>ไฟล์สัญญา:</label>
                    <input type="file" name="contract_img">
                </div>
                <button type="submit" name="update_contract">อัปเดตสัญญา</button>
            <?php } ?>
        </form>
    </div>
</body>
</html>
