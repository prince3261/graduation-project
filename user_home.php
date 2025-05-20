<?php 
session_start();
include('condb.php');

if (!isset($_SESSION['username']) || $_SESSION['user_type'] != "ผู้เช่า") {
    echo "<script>alert('กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
    exit();
}

$username = $_SESSION['username'];

$room_query = "SELECT c.room_id, GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS members
                FROM contract c
                JOIN contract_user cu ON c.contract_id = cu.contract_id
                JOIN user u ON cu.user_id = u.user_id WHERE c.contract_status = 'กำลังมีผล' 
                AND cu.user_id = ? GROUP BY c.room_id";

$stmt = $conn->prepare($room_query);
$stmt->bind_param("s", $username);
$stmt->execute();
$room_result = $stmt->get_result();
$room = $room_result->fetch_assoc();


$invoice_query = "SELECT i.total_cost FROM invoice i WHERE i.payment_statusID = 'P_status01'AND i.room_id = ?
                        ORDER BY i.invoice_date DESC LIMIT 1";

$stmt = $conn->prepare($invoice_query);
$stmt->bind_param("s", $room['room_id']);
$stmt->execute();
$invoice_result = $stmt->get_result();
$latest_invoice = $invoice_result->fetch_assoc()['total_cost'] ?? 0;


$outstanding_query = "SELECT SUM(i.total_cost) AS total_outstanding FROM invoice i WHERE i.payment_statusID = 'P_status01'
                            AND i.room_id = ? ";

$stmt = $conn->prepare($outstanding_query);
$stmt->bind_param("s", $room['room_id']);
$stmt->execute();
$outstanding_result = $stmt->get_result();
$outstanding = $outstanding_result->fetch_assoc()['total_outstanding'] ?? 0;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ภาพรวมสถานะของผู้เช่า</title>
    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center; /* จัดกึ่งกลางแนวนอน */
            align-items: center; /* จัดกึ่งกลางแนวตั้ง */
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .dashboard {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-left: 30%;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .card {
            padding: 20px;
            border: 1px solid #17202a;
            border-radius: 8px;
            background-color: #f5eef8;
            margin-bottom: 20px;
            width: 800px;
        }
        .card h3 {
            margin-bottom: 10px;
        }
        .card p {
            margin: 5px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .table th {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <?php include('user_sidebar.php'); ?>
    <div class="dashboard">
        <h2>ภาพรวมสถานะของผู้เช่า</h2>

        <div class="card">
            <h3>สถานะห้อง</h3>
            <p><strong>หมายเลขห้อง:</strong> <?php echo htmlspecialchars($room['room_id'] ?? 'ไม่ระบุ'); ?></p>
            <p><strong>สมาชิกในห้อง:</strong> <?php echo htmlspecialchars($room['members'] ?? 'ไม่มีสมาชิก'); ?></p>
        </div>

        <div class="card">
            <h3>ยอดรวมใบแจ้งหนี้ล่าสุด</h3>
            <p><strong>ยอดรวม:</strong> <?php echo number_format($latest_invoice, 2); ?> บาท</p>
        </div>

        <div class="card">
            <h3>ยอดค่าใช้จ่ายที่ยังไม่ได้ชำระ</h3>
            <p><strong>ยอดรวม:</strong> <?php echo number_format($outstanding, 2); ?> บาท</p>
        </div>
    </div>
</body>
</html>
