<?php 
session_start();
include('condb.php');

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['username']) || $_SESSION['user_type'] != "ผู้เช่า") {
    echo "<script>alert('กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
    exit();
}

$username = $_SESSION['username'];

// ค้นหา user_id จาก user table
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

// ค้นหา room_id จาก contract_user และ contract table
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

// ค้นหาข้อมูลมิเตอร์
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$searchQuery = "SELECT m.meter_id, m.room_id, m.meter_value, 
                   m.meter_rate, m.meter_price, m.meter_date
                FROM meter m
                WHERE m.room_id = ?";

$params = [$room_id];
$types = "s";

if (!empty($month) && !empty($year)) {
    $selected_date = "$year-$month";
    $searchQuery .= " AND DATE_FORMAT(m.meter_date, '%Y-%m') = ?";
    $params[] = $selected_date;
    $types .= "s";
} elseif (!empty($month)) {
    $searchQuery .= " AND MONTH(m.meter_date) = ?";
    $params[] = $month;
    $types .= "s";
} elseif (!empty($year)) {
    $searchQuery .= " AND YEAR(m.meter_date) = ?";
    $params[] = $year;
    $types .= "s";
}

$stmt_meter = $conn->prepare($searchQuery);
$stmt_meter->bind_param($types, ...$params);
$stmt_meter->execute();
$result = $stmt_meter->get_result();

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ข้อมูลมิเตอร์</title>
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
        <h2>ข้อมูลมิเตอร์</h2>
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

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <table>
            <tr>
                <th>รหัสมิเตอร์</th>
                <th>ห้อง</th>
                <th>ค่ามิเตอร์</th>
                <th>เรทมิเตอร์</th>
                <th>ราคามิเตอร์</th>
                <th>วัน-เวลาที่บันทึกมิเตอร์</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['meter_id']; ?></td>
                <td><?php echo $row['room_id']; ?></td>
                <td><?php echo $row['meter_value']; ?></td>
                <td><?php echo $row['meter_rate']; ?></td>
                <td><?= number_format ($row['meter_price']); ?> บาท</td>
                <td><?php echo $row['meter_date']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>ไม่พบข้อมูลมิเตอร์สำหรับเงื่อนไขที่เลือก</p>
        <?php endif; ?>
    </div>
</body>
</html>
