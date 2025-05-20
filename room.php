<?php
include('auth.php');
include('condb.php');

$sql = "SELECT r.room_id, r.room_status, r.room_detail, 
               c.contract_id, 
               GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR '<br>') AS tenant_names,
               c.contract_start, c.contract_end 
        FROM room r
        LEFT JOIN contract c ON r.room_id = c.room_id AND c.contract_status = 'กำลังมีผล'
        LEFT JOIN contract_user cu ON c.contract_id = cu.contract_id
        LEFT JOIN user u ON cu.user_id = u.user_id
        GROUP BY r.room_id, c.contract_id
        ORDER BY 
        CASE
            WHEN r.room_id LIKE '%A' THEN 1
            WHEN r.room_id LIKE '%B' THEN 2
            WHEN r.room_id LIKE '%C' THEN 3
            WHEN r.room_id LIKE '%D' THEN 4
        END,
        CAST(SUBSTRING(r.room_id, 1, LENGTH(r.room_id) - 1) AS UNSIGNED)";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ห้อง</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            max-width: 1500px;
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
            word-wrap: break-word;
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
            text-align: center;
            color: #333;
        }
        button {
            padding: 5px 10px;
            background-color: #9b59b6;
            border-radius: 4px;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #884ea0;
        }
        .contract-start {
            color: green;
        }
        .contract-end {
            color: red;
        }

        th:nth-child(1), td:nth-child(1) {
            width: 5%;
        }
        th:nth-child(2), td:nth-child(2) {
            width: 10%;
        }
        th:nth-child(3), td:nth-child(3) {
            width: 25%;
        }
        th:nth-child(4), td:nth-child(4) {
            width: 10%;
        }
        th:nth-child(5), td:nth-child(5) {
            width: 25%;
        }
        th:nth-child(6), td:nth-child(6) {
            width: 15%;
        }
        th:nth-child(7), td:nth-child(7) {
            width: 10%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>ข้อมูลห้องพัก</h2>

        <?php 
            $floors = ['A' => 'ชั้น 1', 'B' => 'ชั้น 2', 'C' => 'ชั้น 3', 'D' => 'ชั้น 4'];
            foreach ($floors as $suffix => $floor_name) {
                echo "<h3>{$floor_name}</h3>";
                echo "<table>
                        <tr>
                            <th>ห้อง</th>
                            <th>สถานะห้อง</th>
                            <th>รายละเอียดห้อง</th>
                            <th>รหัสสัญญา</th>
                            <th>ชื่อผู้เช่า</th>
                            <th>ระยะเวลา (ปี-เดือน-วัน)</th>
                            <th></th>
                        </tr>";

                $result->data_seek(0);
                $has_data = false;
                while ($row = $result->fetch_assoc()) {
                    if (strpos($row['room_id'], $suffix) !== false) {
                        $has_data = true;
                        echo "<tr>";
                        echo "<td>{$row['room_id']}</td>";
                        echo "<td>" . (!empty($row['room_status']) ? $row['room_status'] : '') . "</td>";
                        echo "<td>" . (!empty($row['room_detail']) ? $row['room_detail'] : '') . "</td>";
                        echo "<td>" . (!empty($row['contract_id']) ? $row['contract_id'] : '') . "</td>";
                        echo "<td>" . (!empty($row['tenant_names']) ? nl2br($row['tenant_names']) : '-') . "</td>";
                        echo "<td>";
                        if (!empty($row['contract_start'])) {
                            echo "<span class='contract-start'>{$row['contract_start']}</span> ถึง ";
                        }
                        if (!empty($row['contract_end'])) {
                            echo "<span class='contract-end'>{$row['contract_end']}</span>";
                        }
                        echo "</td>";
                        echo "<td><a href='room_detail.php?room_id={$row['room_id']}'><button>ตรวจสอบห้องพัก</button></a></td>";
                        echo "</tr>";
                    }
                }
                if (!$has_data) {
                    echo "<tr><td colspan='7'>ไม่มีข้อมูลห้อง</td></tr>";
                }
                echo "</table><br>";
            }
        ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
