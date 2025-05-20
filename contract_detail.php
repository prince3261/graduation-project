<?php
include('auth.php');
include('condb.php');

$contract_id = isset($_GET['contract_id']) ? $_GET['contract_id'] : null;

if (!$contract_id) {
    echo "ไม่มีรหัสสัญญาที่ระบุ";
    exit;
}

$sql = "SELECT 
            c.*, 
            GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR '\n') AS tenant_names,
            GROUP_CONCAT(DISTINCT u.user_address SEPARATOR ' nl2br ') AS tenant_addresses,
            GROUP_CONCAT(DISTINCT u.phone SEPARATOR ', ') AS tenant_phones,
            GROUP_CONCAT(DISTINCT u.email SEPARATOR ', ') AS tenant_emails,
            r.room_id
        FROM contract c
        LEFT JOIN contract_user cu ON c.contract_id = cu.contract_id
        LEFT JOIN user u ON cu.user_id = u.user_id
        LEFT JOIN room r ON c.room_id = r.room_id
        WHERE c.contract_id = ?
        GROUP BY c.contract_id";

$contract = null;
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดสัญญา</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container-wrapper {
            display: flex;
            flex-direction: row;
            justify-content: center;
            gap: 20px;
            margin: 90px;
            margin-left: 350px;
            max-width: 1800px;
        }
        .image-container {
            flex: 3;
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 15px;
        }
        .details-wrapper {
            flex: 2;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .details-container, .user-details-container {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #17202a;
        }
        th, td {
            padding: 10px;
            text-align: left;
            width: 40%;
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
        h2 {
            margin-bottom: 15px;
            text-align: center;
            color: #333;
        }
        .buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        .btn {
            background-color: #337ab7;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #286090;
        }
        .print-btn {
            background-color: #FF9800;
        }
        .print-btn:hover {
            background-color: #fb8c00;
        }
        .refund-btn {
            background-color: #5cb85c;
        }
        .refund-btn:hover {
            background-color: #4cae4c;
        }
    </style>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container-wrapper">
        <!-- รูปภาพสัญญา -->
        <div class="image-container">
            <h2>รูปภาพสัญญา</h2>
            <?php if (!empty($contract['contract_img'])): ?>
                <img src="<?php echo htmlspecialchars($contract['contract_img']); ?>" alt="Contract Image">
            <?php else: ?>
                <p>ไม่มีรูปภาพ</p>
            <?php endif; ?>
        </div>

        <!-- รายละเอียดผู้เช่าและสัญญา -->
        <div class="details-wrapper">
            <!-- รายละเอียดผู้เช่า -->
            <div class="user-details-container">
                <h2>รายละเอียดผู้เช่า</h2>
                <?php
                // แยกข้อมูลผู้เช่าออกเป็น array
                $tenant_names = explode("\n", $contract['tenant_names']);
                $tenant_addresses = explode(' nl2br ', $contract['tenant_addresses']);
                $tenant_phones = explode(', ', $contract['tenant_phones']);
                $tenant_emails = explode(', ', $contract['tenant_emails']);

                // วนลูปแสดงข้อมูลผู้เช่า
                for ($i = 0; $i < count($tenant_names); $i++) {
                    echo '
                    <div class="user-detail-table">
                        <h3>ผู้เช่า ' . ($i + 1) . '</h3>
                        <table>
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <td>' . htmlspecialchars($tenant_names[$i]) . '</td>
                            </tr>
                            <tr>
                                <th>ที่อยู่</th>
                                <td>' . htmlspecialchars($tenant_addresses[$i] ?? '-') . '</td>
                            </tr>
                            <tr>
                                <th>เบอร์โทรศัพท์</th>
                                <td>' . htmlspecialchars($tenant_phones[$i] ?? '-') . '</td>
                            </tr>
                            <tr>
                                <th>อีเมล</th>
                                <td>' . htmlspecialchars($tenant_emails[$i] ?? '-') . '</td>
                            </tr>
                        </table>
                    </div>
                    ';
                }
                ?>
            </div>

            <!-- รายละเอียดสัญญา -->
            <div class="details-container">
                <h2>รายละเอียดสัญญา</h2>
                <table>
                    <tr>
                        <th>รหัสสัญญา</th>
                        <td><?php echo htmlspecialchars($contract['contract_id']); ?></td>
                    </tr>
                    <tr>
                        <th>ชื่อผู้เช่า</th>
                        <td><?php echo nl2br(htmlspecialchars($contract['tenant_names'])); ?></td>
                    </tr>
                    <tr>
                        <th>ห้อง</th>
                        <td><?php echo htmlspecialchars($contract['room_id']); ?></td>
                    </tr>
                    <tr>
                        <th>ชื่อสัญญา</th>
                        <td><?php echo htmlspecialchars($contract['contract_name']); ?></td>
                    </tr>
                    <tr>
                        <th>รายละเอียดสัญญา</th>
                        <td><?php echo htmlspecialchars($contract['contract_detail']); ?></td>
                    </tr>
                    <tr>
                        <th>วันเริ่มต้นสัญญา</th>
                        <td><?php echo htmlspecialchars($contract['contract_start']); ?></td>
                    </tr>
                    <tr>
                        <th>วันสิ้นสุดสัญญา</th>
                        <td><?php echo htmlspecialchars($contract['contract_end']); ?></td>
                    </tr>
                    <tr>
                        <th>เงินมัดจำ</th>
                        <td><?php echo htmlspecialchars(number_format($contract['deposit'], 2)); ?> บาท</td>
                    </tr>
                    <tr>
                        <th>สถานะสัญญา</th>
                        <td><?php echo htmlspecialchars($contract['contract_status']); ?></td>
                    </tr>
                </table>
                <div class="buttons">
                    <a class="btn print-btn" onclick="printImage('<?php echo $contract['contract_img']; ?>')">พิมพ์</a>
                    <a href="edit_contract.php?contract_id=<?php echo $contract['contract_id']; ?>" class="btn">แก้ไข</a>
                    <a href="security_refund.php?contract_id=<?php echo $contract['contract_id']; ?>" class="btn refund-btn">คืนมัดจำ</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        function printImage(imageUrl) {
            var newWindow = window.open('', '_blank');
            newWindow.document.write('<img src="' + imageUrl + '" style="width:100%;height:auto;" onload="window.print();window.close();">');
            newWindow.document.close();
        }
    </script>
</body>
</html>
