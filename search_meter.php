<?php
include('auth.php');
include('condb.php');

$search_term = isset($_GET['meter_id']) ? $_GET['meter_id'] : (isset($_POST['search_term']) ? $_POST['search_term'] : '');
$room_id = isset($_POST['room_id']) ? $_POST['room_id'] : '';
$month = isset($_POST['month']) ? $_POST['month'] : '';
$year = isset($_POST['year']) ? $_POST['year'] : '';

$meters = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' || !empty($search_term) || !empty($room_id)) {
    $sql = "SELECT m.meter_id, m.room_id,
                GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR '<br>') AS tenant_names,
                m.meter_value, m.meter_rate, m.meter_price, m.meter_date, m.meter_month
            FROM meter m
            JOIN contract c ON m.room_id = c.room_id
            JOIN contract_user cu ON c.contract_id = cu.contract_id
            JOIN user u ON cu.user_id = u.user_id
            WHERE 1=1";

    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($search_term)) {
        $search_terms = explode(',', $search_term);

        $search_conditions = [];
        foreach ($search_terms as $term) {
            $term = trim($term);
            $search_conditions[] = "(m.meter_id LIKE ? OR m.room_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $like_term = "%$term%";
            $params = array_merge($params, [$like_term, $like_term, $like_term]);
            $types .= 'sss';
        }

        $conditions[] = '(' . implode(' OR ', $search_conditions) . ')';
    }

    if (!empty($room_id)) {
        $conditions[] = "m.room_id = ?";
        $params[] = $room_id;
        $types .= 's';
    }

    if (!empty($month) && !empty($year)) {
        $conditions[] = "MONTH(m.meter_date) = ? AND YEAR(m.meter_date) = ?";
        $params = array_merge($params, [$month, $year]);
        $types .= 'ss';
    } elseif (!empty($month)) {
        $conditions[] = "MONTH(m.meter_date) = ?";
        $params[] = $month;
        $types .= 's';
    } elseif (!empty($year)) {
        $conditions[] = "YEAR(m.meter_date) = ?";
        $params[] = $year;
        $types .= 's';
    }

    if (!empty($conditions)) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }

    $sql .= " GROUP BY m.meter_id, m.room_id, m.meter_value, m.meter_rate, m.meter_price, m.meter_date";

    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $meters = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหามิเตอร์</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        h2 {
            text-align: center;
            color: #333;
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
        .form-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            align-items: center;
        }
        .form-group label {
            margin-right: 10px;
        }
        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 500px;
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
        .edit-btn, .delete-btn {
            background-color: #337ab7;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .edit-btn:hover {
            background-color: #286090;
        }
        .delete-btn {
            background-color: #d9534f;
            color: white;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container">
        <h2>ค้นหาข้อมูลมิเตอร์</h2>
        <form action="search_meter.php" method="post" class="form-container">
            <div class="form-group">
                <label for="room_id">เลือกห้อง:</label>
                <select name="room_id" id="room_id">
                    <option value="">-- เลือกห้อง --</option>
                    <?php
                    $room_query = "SELECT room_id FROM room ORDER BY CASE
                                        WHEN room_id LIKE '%A' THEN 1
                                        WHEN room_id LIKE '%B' THEN 2
                                        WHEN room_id LIKE '%C' THEN 3
                                        WHEN room_id LIKE '%D' THEN 4
                                        ELSE 5 END, room_id ASC";
                    $room_result = mysqli_query($conn, $room_query);
                    while ($row = mysqli_fetch_assoc($room_result)) {
                        echo "<option value='{$row['room_id']}'" . (($room_id == $row['room_id']) ? ' selected' : '') . ">{$row['room_id']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="month">เดือน:</label>
                <select name="month" id="month">
                    <option value="">-- เลือกเดือน --</option>
                    <?php $thai_months = ['01' => 'มกราคม', 
                                          '02' => 'กุมภาพันธ์', 
                                          '03' => 'มีนาคม', 
                                          '04' => 'เมษายน',
                                          '05' => 'พฤษภาคม', 
                                          '06' => 'มิถุนายน', 
                                          '07' => 'กรกฎาคม', 
                                          '08' => 'สิงหาคม',
                                          '09' => 'กันยายน', 
                                          '10' => 'ตุลาคม', 
                                          '11' => 'พฤศจิกายน', 
                                          '12' => 'ธันวาคม'];
                    foreach ($thai_months as $key => $month_name): ?>
                        <option value="<?= $key ?>" <?= ($month == $key) ? 'selected' : '' ?>><?= $month_name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="year">ปี:</label>
                <select name="year" id="year">
                    <option value="">-- เลือกปี --</option>
                    <?php for ($y = date('Y') + 543; $y >= 2000 + 543; $y--): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="search_term">ค้นหา (ชื่อ-นามสกุล, เลขห้อง, รหัสมิเตอร์):</label>
                <input type="text" name="search_term" id="search_term" value="<?php echo $search_term; ?>">
            </div>

            <button type="submit">ค้นหา</button>
        </form>

        * รหัสมิเตอร์(สามารถค้นหาพร้อมกันได้หลายรหัส เช่น meter01, meter02) *

        <?php if (!empty($meters)): ?>
            <table>
                <thead>
                    <tr>
                        <th>รหัสมิเตอร์</th>
                        <th>ห้อง</th>
                        <th>ผู้เช่า</th>
                        <th>มิเตอร์เดือน</th>
                        <th>ค่ามิเตอร์</th>
                        <th>เรท</th>
                        <th>ราคามิเตอร์</th>
                        <th>วันที่บันทึก</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meters as $meter): ?>
                        <tr>
                            <td><?php echo $meter['meter_id']; ?></td>
                            <td><?php echo $meter['room_id']; ?></td>
                            <td><?php echo $meter['tenant_names']; ?></td>
                            <td><?php echo $meter['meter_month']; ?></td>
                            <td><?php echo $meter['meter_value']; ?></td>
                            <td><?php echo $meter['meter_rate']; ?></td>
                            <td><?php echo number_format($meter['meter_price']); ?> บาท</td>
                            <td><?php echo date('Y-m-d', strtotime($meter['meter_date'])); ?></td>
                            <td>
                                <a href="edit_meter.php?meter_id=<?php echo $meter['meter_id']; ?>" class="btn edit-btn">แก้ไข</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>ไม่พบข้อมูล</p>
        <?php endif; ?>
    </div>
</body>
</html>