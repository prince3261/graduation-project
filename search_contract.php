<?php
include('auth.php'); 
include('condb.php');

$contracts = [];
$search_term = isset($_GET['contract_id']) ? $_GET['contract_id'] : (isset($_POST['search_term']) ? $_POST['search_term'] : '');

if (!empty($search_term)) {
    $sql = "SELECT 
                c.contract_id,
                c.room_id,
                GROUP_CONCAT(cu.user_id ORDER BY cu.user_id SEPARATOR '<br>') AS user_ids,
                GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR '<br>') AS tenant_names,
                c.contract_start,
                c.contract_end,
                c.contract_status,
                c.contract_img
            FROM contract c
            JOIN contract_user cu ON c.contract_id = cu.contract_id
            JOIN user u ON cu.user_id = u.user_id
            WHERE 
                (c.contract_id LIKE ? OR c.contract_status LIKE ? 
                OR cu.user_id LIKE ? OR c.room_id LIKE ? 
                OR u.first_name LIKE ? OR u.last_name LIKE ?)
                AND c.contract_status IN ('กำลังมีผล', 'สิ้นสุด')
            GROUP BY c.contract_id, c.room_id, c.contract_start, c.contract_end, c.contract_status, c.contract_img";

    if ($stmt = $conn->prepare($sql)) {
        $like_term = "%$search_term%";
        $stmt->bind_param("ssssss", $like_term, $like_term, $like_term, $like_term, $like_term, $like_term);
        $stmt->execute();
        $result = $stmt->get_result();
        $contracts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาสัญญา</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width:80%;
            max-width: 1600px;
            margin: 80px;
            margin-left: 300px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        td {
            padding: 20px;
        }
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        tr:nth-child(odd) {
            background-color: #f5eef8;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        img {
            max-width: 100px;
            height: auto;
        }
        .print-btn {
            background-color: #FF9800;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .print-btn:hover {
            background-color: #fb8c00;
        }
        input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 68%;
            margin-right: 5px;
        }
        button {
            background-color: #6633FF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #6600FF;
        }
        .edit-btn {
            background-color: #337ab7;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .edit-btn:hover {
            background-color: #286090;
        }
        .status-valid {
            color: #4CAF50;
            font-weight: bold;
        }
        .status-invalid {
            color: red;
            font-weight: bold;
        }
        .refund-btn {
            background-color: #5cb85c;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .refund-btn:hover {
            background-color: #4cae4c;
        }
        .date-start {
            color: green;
            font-weight: bold;
        }
        .date-end {
            color: red;
            font-weight: bold;
        }
        .details-btn {
            background-color: #9b59b6;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .details-btn:hover {
            background-color: #884ea0;
        }
    </style>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container">
        <h2>ค้นหาสัญญา</h2>

        <form method="post" action="search_contract.php">
            <label for="search_term">ค้นหา (รหัสสัญญา, ชื่อผู้ใช้งานระบบ, ชื่อผู้เช่า, เลขห้อง):</label>
            <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>" required>
            <button type="submit">ค้นหา</button>
        </form>

        <?php if (!empty($contracts)): ?>
            <table>
                <tr>
                    <th>รหัสสัญญา</th>
                    <th>ห้อง</th>
                    <th>ชื่อผู้ใช้งานระบบ</th>
                    <th>ผู้เช่า</th>
                    <th>ระยะเวลา (ปี-เดือน-วัน)</th>
                    <th>สถานะสัญญา</th>
                    <th>การดำเนินการ</th>
                </tr>
                <?php foreach ($contracts as $contract): ?>
                <tr>
                    <td><?php echo $contract['contract_id']; ?></td>
                    <td><?php echo $contract['room_id']; ?></td>
                    <td><?php echo nl2br($contract['user_ids']); ?></td>
                    <td><?php echo nl2br($contract['tenant_names']); ?></td>
                    <td>
                        <span class="date-start"><?php echo $contract['contract_start']; ?></span> ถึง 
                        <span class="date-end"><?php echo $contract['contract_end']; ?></span>
                    </td>
                    <td class="<?php echo $contract['contract_status'] == 'กำลังมีผล' ? 'status-valid' : 'status-invalid'; ?>">
                        <?php echo $contract['contract_status']; ?>
                    </td>
                    <td>
                        <a href="contract_detail.php?contract_id=<?php echo $contract['contract_id']; ?>" class="btn details-btn">รายละเอียด</a>
                        <a class="print-btn" onclick="printImage('<?php echo $contract['contract_img']; ?>')">พิมพ์</a>
                        <a href="edit_contract.php?contract_id=<?php echo $contract['contract_id']; ?>" class="btn edit-btn">แก้ไข</a>
                        <a href="security_refund.php?contract_id=<?php echo $contract['contract_id']; ?>" target="_blank" class="refund-btn">คืนมัดจำ</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <p>ไม่พบผลลัพธ์ที่ตรงกับการค้นหา</p>
        <?php endif; ?>
    </div>

    <script>
        function printImage(imageUrl) {
            var newWindow = window.open('', '_blank');
            newWindow.document.write('<img src="' + imageUrl + '" onload="window.print();window.close()">');
            newWindow.document.close();
        }
    </script>
</body>
</html>
