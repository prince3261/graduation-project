<?php 
session_start();
include('condb.php');

if (!isset($_SESSION['username']) || $_SESSION['user_type'] != "ผู้เช่า") {
    echo "<script>alert('กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
    exit();
}

$username = $_SESSION['username'];

$user_query = "SELECT user_id FROM user WHERE user_id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_data = $user_result->fetch_assoc();
$user_id = $user_data['user_id'] ?? '';

if (!$user_id) {
    echo "<p>ไม่พบข้อมูลผู้ใช้</p>";
    exit();
}

$contract_query = "SELECT c.room_id 
                   FROM contract_user cu 
                   JOIN contract c ON cu.contract_id = c.contract_id
                   WHERE cu.user_id = ? AND c.contract_status = 'กำลังมีผล'";
$stmt_contract = $conn->prepare($contract_query);
$stmt_contract->bind_param("s", $user_id);
$stmt_contract->execute();
$contract_result = $stmt_contract->get_result();
$contract_data = $contract_result->fetch_assoc();
$room_id = $contract_data['room_id'] ?? '';

if (!$room_id) {
    echo "<p>ไม่พบข้อมูลห้อง</p>";
    exit();
}

$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$searchQuery = "SELECT s.schedule_id, s.schedule_date, s.schedule_detail, s.description,
                   t.schedule_type_name, ss.schedule_status_name 
                FROM schedule s
                JOIN schedule_type t ON s.schedule_typeID = t.schedule_typeID
                JOIN schedule_status ss ON s.schedule_statusID = ss.schedule_statusID
                WHERE s.contract_id IN (
                    SELECT contract_id FROM contract WHERE room_id = ?
                )";

$params = [$room_id];
$types = "s";

if (!empty($month) && !empty($year)) {
    $selected_date = "$year-$month";
    $searchQuery .= " AND DATE_FORMAT(s.schedule_date, '%Y-%m') = ?";
    $params[] = $selected_date;
    $types .= "s";
} elseif (!empty($month)) {
    $searchQuery .= " AND MONTH(s.schedule_date) = ?";
    $params[] = $month;
    $types .= "s";
} elseif (!empty($year)) {
    $searchQuery .= " AND YEAR(s.schedule_date) = ?";
    $params[] = $year;
    $types .= "s";
}

$stmt_schedule = $conn->prepare($searchQuery);
$stmt_schedule->bind_param($types, ...$params);
$stmt_schedule->execute();
$result = $stmt_schedule->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ข้อมูลการนัดหมาย</title>
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
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        select, button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #6633FF;
            color: white;
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
    </style>
</head>
<body>
    <?php include('user_sidebar.php'); ?>
    <div class="container">
        <h2>ข้อมูลการนัดหมาย</h2>
        <form method="POST" action="">
            <label for="month">เลือกเดือน:</label>
            <select name="month" id="month">
                <option value="">--เลือกเดือน--</option>
                <?php 
                $months = [
                    "01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม",
                    "04" => "เมษายน", "05" => "พฤษภาคม", "06" => "มิถุนายน",
                    "07" => "กรกฎาคม", "08" => "สิงหาคม", "09" => "กันยายน",
                    "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"
                ];
                foreach ($months as $key => $value) {
                    $selected = ($key == $month) ? "selected" : "";
                    echo "<option value='$key' $selected>$value</option>";
                }
                ?>
            </select>

            <label for="year">เลือกปี:</label>
            <select name="year" id="year">
                <option value="">--เลือกปี--</option>
                <?php
                $current_year = date("Y") + 543;
                for ($i = $current_year; $i >= $current_year - 10; $i--) {
                    $selected = ($i == $year) ? "selected" : "";
                    echo "<option value='$i' $selected>$i</option>";
                }
                ?>
            </select>

            <button type="submit">ค้นหา</button>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>วันที่</th>
                <th>รายละเอียด</th>
                <th>ประเภท</th>
                <th>สถานะ</th>
                <th>หมายเหตุ</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['schedule_date']; ?></td>
                <td><?php echo $row['schedule_detail']; ?></td>
                <td><?php echo $row['schedule_type_name']; ?></td>
                <td><?php echo $row['schedule_status_name']; ?></td>
                <td><?php echo $row['description']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>ไม่พบข้อมูลการนัดหมายสำหรับเงื่อนไขที่เลือก</p>
        <?php endif; ?>
    </div>
</body>
</html>
