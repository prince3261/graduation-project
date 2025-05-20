<?php
include('auth.php');
include('condb.php');

// รับค่าจาก GET หรือ POST
$search_term = '';
$room_id = $_POST['room_id'] ?? '';
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$parcel_status = $_POST['parcel_status'] ?? '';
$parcels = [];
$search_performed = false;

// ค้นหาด้วย GET (parcel_id) ก่อน
if (isset($_GET['parcel_id']) && !empty($_GET['parcel_id'])) {
    $parcel_id = $_GET['parcel_id'];
    $search_performed = true;

    $sql = "SELECT p.parcel_id, p.parcel_detail, p.room_id, p.received_date, ps.parcel_status_name
            FROM parcel p
            LEFT JOIN parcel_status ps ON p.parcel_statusID = ps.parcel_statusID
            WHERE p.parcel_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $parcel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $parcels = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
// หากไม่มี GET ให้ใช้ POST สำหรับการค้นหาทั่วไป
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $search_term = $_POST['search_term'] ?? '';
    $search_performed = true;

    // SQL พื้นฐาน
    $sql = "SELECT p.parcel_id, p.parcel_detail, p.room_id, p.received_date, ps.parcel_status_name
            FROM parcel p
            LEFT JOIN parcel_status ps ON p.parcel_statusID = ps.parcel_statusID
            WHERE 1=1";

    $conditions = [];
    $params = [];
    $types = '';

    // ค้นหาด้วยคำค้นหา
    if (!empty($search_term)) {
        $conditions[] = "(p.parcel_id LIKE ? OR p.parcel_detail LIKE ?)";
        $like_term = "%$search_term%";
        $params[] = $like_term;
        $params[] = $like_term;
        $types .= 'ss';
    }

    // ค้นหาด้วยห้องพัก
    if (!empty($room_id)) {
        $conditions[] = "p.room_id = ?";
        $params[] = $room_id;
        $types .= 's';
    }

    // ค้นหาด้วยเดือนและปี
    if (!empty($month)) {
        $conditions[] = "MONTH(p.received_date) = ?";
        $params[] = $month;
        $types .= 's';
    }

    if (!empty($year)) {
        $conditions[] = "YEAR(p.received_date) = ?";
        $params[] = $year;
        $types .= 's';
    }

    // ค้นหาด้วยสถานะพัสดุ
    if (!empty($parcel_status)) {
        $conditions[] = "p.parcel_statusID = ?";
        $params[] = $parcel_status;
        $types .= 's';
    }

    // เพิ่มเงื่อนไขลงใน SQL
    if (!empty($conditions)) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY p.received_date DESC";

    // เตรียมและดำเนินการ SQL
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $parcels = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ดึงข้อมูลสถานะพัสดุ
$status_query = "SELECT parcel_statusID, parcel_status_name FROM parcel_status";
$status_result = mysqli_query($conn, $status_query);
$statuses = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $statuses[] = $row;
}

// เดือนภาษาไทย
$thai_months = ['01' => 'มกราคม','02' => 'กุมภาพันธ์','03' => 'มีนาคม','04' => 'เมษายน',
                '05' => 'พฤษภาคม','06' => 'มิถุนายน','07' => 'กรกฎาคม','08' => 'สิงหาคม',
                '09' => 'กันยายน','10' => 'ตุลาคม','11' => 'พฤศจิกายน','12' => 'ธันวาคม',
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาพัสดุ</title>
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
        .form-container {
            display: flex;
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
        select, input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 350px;
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
        th{
            background-color: #e8daef;
        }
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        tr:nth-child(odd) {
            background-color: #f5eef8;
        }
        .edit-btn, .mail-btn {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            text-decoration: none;
        }
        .edit-btn {
            background-color: #337ab7;
        }
        .edit-btn:hover {
            background-color: #286090;
        }
        .mail-btn {
            background-color: #fd7e14;
        }
        .mail-btn:hover {
            background-color: #e06a00;
        }
    </style>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container">
        <h2>ค้นหาพัสดุ</h2>
        <form method="post" action="search_parcel.php" class="form-container">
            <!-- Dropdown ห้อง -->
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
                                                    ELSE 5
                                                END, 
                                                room_id ASC";
                    $room_result = mysqli_query($conn, $room_query);
                    while ($row = mysqli_fetch_assoc($room_result)) {
                        echo "<option value='{$row['room_id']}'" . (($room_id == $row['room_id']) ? ' selected' : '') . ">{$row['room_id']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Dropdown เดือน -->
            <div class="form-group">
                <label for="month">เลือกเดือน:</label>
                <select name="month" id="month">
                    <option value="">-- เลือกเดือน --</option>
                    <?php foreach ($thai_months as $key => $month_name): ?>
                        <option value="<?= $key ?>" <?= ($month == $key) ? 'selected' : '' ?>><?= $month_name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dropdown ปี -->
            <div class="form-group">
                <label for="year">ปี:</label>
                <select name="year" id="year">
                    <option value="">-- เลือกปี --</option>
                    <?php for ($y = date('Y') + 543; $y >= 2000 + 543; $y--): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Dropdown สถานะพัสดุ -->
            <div class="form-group">
                <label for="parcel_status">สถานะพัสดุ:</label>
                <select name="parcel_status" id="parcel_status">
                    <option value="">-- เลือกสถานะพัสดุ --</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status['parcel_statusID'] ?>" <?= ($parcel_status == $status['parcel_statusID']) ? 'selected' : '' ?>>
                            <?= $status['parcel_status_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ช่องค้นหา -->
            <div class="form-group">
                <label for="search_term">ค้นหา (รหัสพัสดุ, รายละเอียด):</label>
                <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            
            <button type="submit">ค้นหา</button>
        </form>

        <!-- ตารางแสดงผล -->
        <?php if (!empty($parcels)): ?>
            <table>
                <tr>
                    <th>รหัสพัสดุ</th>
                    <th>รายละเอียดพัสดุ</th>
                    <th>ห้องพัก</th>
                    <th>วันที่รับพัสดุ</th>
                    <th>สถานะ</th>
                    <th></th>
                </tr>
                <?php foreach ($parcels as $parcel): ?>
                    <tr>
                        <td><?= htmlspecialchars($parcel['parcel_id']); ?></td>
                        <td><?= htmlspecialchars($parcel['parcel_detail']); ?></td>
                        <td><?= htmlspecialchars($parcel['room_id']); ?></td>
                        <td><?= date("d/m/Y", strtotime($parcel['received_date'])); ?></td>
                        <td><?= htmlspecialchars($parcel['parcel_status_name']); ?></td>
                        <td>
                            <a href="add_parcel.php?parcel_id=<?= htmlspecialchars($parcel['parcel_id']); ?>" class="edit-btn">แก้ไข</a>
                            <a href="mail_parcel.php?parcel_id=<?= htmlspecialchars($parcel['parcel_id']); ?>" 
                                onclick="return confirm('ส่งอีเมลแจ้งพัสดุให้ลูกบ้าน?');" target="_blank" class="mail-btn">ส่งอีเมล</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>ไม่พบผลลัพธ์ที่ตรงกับการค้นหา</p>
        <?php endif; ?>
    </div>
</body>
</html>