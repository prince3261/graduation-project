<?php 
session_start();
include('condb.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบและเป็นประเภทผู้เช่าหรือไม่
if (!isset($_SESSION['username']) || $_SESSION['user_type'] != "ผู้เช่า") {
    echo "<script>alert('กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
    exit();
}

$username = $_SESSION['username'];

// ดึง user_id จาก user table โดยใช้ user_id จาก Session
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

// ดึง room_id จาก contract_user และ contract table
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

// ดึงข้อมูลใบแจ้งหนี้
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$searchQuery = "SELECT i.invoice_id, i.room_id, 
                       GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name)) AS tenant_names, 
                       i.water_cost, i.electric_cost, i.rent_cost, i.total_cost, i.invoice_date, 
                       i.cost_detail, ps.payment_status_name, i.payment_img, 
                       pt.payment_type_name, i.payment_date, i.penalty_service_cost
                FROM invoice i 
                JOIN contract c ON i.room_id = c.room_id
                JOIN contract_user cu ON c.contract_id = cu.contract_id
                JOIN user u ON cu.user_id = u.user_id
                JOIN payment_status ps ON i.payment_statusID = ps.payment_statusID
                LEFT JOIN payment_type pt ON i.payment_typeID = pt.payment_typeID
                WHERE cu.user_id = ? AND i.room_id = ?";

$params = [$user_id, $room_id];
$types = "ss";

if (!empty($month) && !empty($year)) {
    $selected_date = ($year - 543) . "-$month-01"; // แปลงเป็นปี ค.ศ.
    $searchQuery .= " AND DATE_FORMAT(i.invoice_date, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')";
    $params[] = $selected_date;
    $types .= "s";
}

$searchQuery .= " GROUP BY i.invoice_id";
$stmt_invoice = $conn->prepare($searchQuery);
$stmt_invoice->bind_param($types, ...$params);
$stmt_invoice->execute();
$result = $stmt_invoice->get_result();

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ค้นหาข้อมูลใบแจ้งหนี้และใบเสร็จ</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        .edit-btn {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            background-color: #337ab7;
            text-decoration: none;
        }
        .edit-btn:hover {
            background-color: #286090;
        }
    </style>
</head>
<body>
    <?php include('user_sidebar.php'); ?>
    <div class="container">
        <h2>ตรวจสอบการชำระเงิน</h2>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>รหัสเอกสาร</th>
                        <th>ห้อง</th>
                        <th>ชื่อผู้เช่า</th>
                        <th>ราคาทั้งหมด</th>
                        <th>วันที่บันทึกใบแจ้งหนี้</th>
                        <th>สถานะ</th>
                        <th>ชำระเงิน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['invoice_id'] ?></td>
                            <td><?= $row['room_id'] ?></td>
                            <td><?= $row['tenant_names'] ?></td>
                            <td><?= number_format($row['total_cost']) ?> บาท</td>
                            <td><?= date('Y-m-d', strtotime($row['invoice_date'])) ?></td>
                            <td><?= $row['payment_status_name'] ?></td>
                            <td>
                                <?php if ($row['payment_status_name'] == 'ยังไม่ชำระ'): ?>
                                    <a href="add_payment.php?invoice_id=<?= $row['invoice_id'] ?>" class="edit-btn">ชำระเงิน</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>ไม่พบข้อมูล</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>
