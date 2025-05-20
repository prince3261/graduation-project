<?php
include('auth.php');
include('condb.php');

$search_term = '';
$equipment_id = '';

// ตรวจสอบค่าค้นหา
if (isset($_GET['search_term'])) {
    $search_term = $_GET['search_term'];
} elseif (isset($_POST['search_term'])) {
    $search_term = $_POST['search_term'];
}
// ดึงข้อมูลห้องทั้งหมดสำหรับ Dropdown
$sql_rooms = "SELECT DISTINCT room_id 
              FROM room 
              ORDER BY 
                CASE
                    WHEN room_id LIKE '%A' THEN 1
                    WHEN room_id LIKE '%B' THEN 2
                    WHEN room_id LIKE '%C' THEN 3
                    WHEN room_id LIKE '%D' THEN 4
                    ELSE 5 -- สำหรับกรณีที่ไม่ตรงกับเงื่อนไขข้างต้น
                END, 
                room_id ASC";
$result_rooms = $conn->query($sql_rooms);

if (isset($_GET['room_eqIDs'])) {
    $room_eqIDs = explode(',', $_GET['room_eqIDs']); // แยกค่าออกเป็น Array
    if (!empty($room_eqIDs)) {
        $placeholders = implode(',', array_fill(0, count($room_eqIDs), '?'));

        $sql = "SELECT e.*, re.room_id, re.room_eqID
                FROM equipment e
                JOIN room_equipment re ON e.equipment_id = re.equipment_id
                WHERE re.room_eqID IN ($placeholders)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param(str_repeat('s', count($room_eqIDs)), ...$room_eqIDs);
            $stmt->execute();
            $result = $stmt->get_result();
            $equipment = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

if (!empty($search_term)) {
    $terms = array_map('trim', explode(',', $search_term));

    $conditions = [];
    $params = [];
    foreach ($terms as $term) {
        $conditions[] = "(re.room_id LIKE ? OR e.equipment_id LIKE ? OR re.room_eqID LIKE ? OR e.equipment_detail LIKE ?)";
        $params[] = "%$term%";
        $params[] = "%$term%";
        $params[] = "%$term%";
        $params[] = "%$term%";
    }

    $where_clause = implode(' OR ', $conditions);
    $sql = "SELECT e.*, re.room_id, re.room_eqID
            FROM equipment e
            JOIN room_equipment re ON e.equipment_id = re.equipment_id
            WHERE $where_clause";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipment = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $delete_sql = "DELETE FROM room_equipment WHERE room_eqID = ?";
    if ($delete_stmt = $conn->prepare($delete_sql)) {
        $delete_stmt->bind_param("s", $delete_id);
        if ($delete_stmt->execute()) {
            echo "<script>
                alert('ลบอุปกรณ์เรียบร้อยแล้ว');
                window.location.href = 'search_equipment.php?search_term=" . urlencode($search_term) . "';
            </script>";
            exit();
        } else {
            echo "<script>alert('ไม่สามารถลบอุปกรณ์ได้'); window.history.back();</script>";
        }
        $delete_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาอุปกรณ์</title>
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
            max-width: 150px;
            height: auto;
        }
        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 60%;
            margin-right: 5px;
        }
        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
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
        .edit-btn, .delete-btn {
            background-color: #337ab7;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .edit-btn:hover, .delete-btn:hover {
            background-color: #286090;
        }
        .delete-btn {
            background-color: #d9534f;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }
    </style>
    <script>
        function updateSearchTerm() {
            const roomDropdown = document.getElementById('room_id');
            const searchInput = document.getElementById('search_term');
            const roomValue = roomDropdown.value;

            if (roomValue) {
                searchInput.value = roomValue; // อัปเดต search_term ด้วยค่าห้องที่เลือก
            }
        }
    </script>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container">
        <h2>ค้นหาอุปกรณ์</h2>
        <form method="post" action="search_equipment.php">
            <label for="room_id">เลือกห้อง:</label>
            <select id="room_id" onchange="updateSearchTerm()">
                <option value="">-- เลือกห้อง --</option>
                <?php while ($row = $result_rooms->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['room_id']); ?>"><?php echo htmlspecialchars($row['room_id']); ?></option>
                <?php endwhile; ?>
            </select>
            <label for="search_term">ค้นหา (เลขห้อง, รหัสอุปกรณ์, รายละเอียด):</label>
            <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>" required>
            <button type="submit">ค้นหา</button>
        </form>

        <?php if (!empty($equipment)): ?>
            <?php 
            $grouped_equipment = [];
            foreach ($equipment as $item) {
                $grouped_equipment[$item['room_id']][] = $item;
            }

            foreach ($grouped_equipment as $room_id => $items): ?>
                <h3>ห้อง: <?php echo htmlspecialchars($room_id); ?></h3>
                <table>
                    <tr>
                        <th>รหัสอุปกรณ์</th>
                        <th>รายละเอียดอุปกรณ์</th>
                        <th>ราคาอุปกรณ์</th>
                        <th>รูปภาพอุปกรณ์</th>
                        <th></th>
                    </tr>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['room_eqID']); ?></td>
                            <td><?php echo htmlspecialchars($item['equipment_detail']); ?></td>
                            <td><?php echo number_format($item['equipment_price']); ?> บาท</td>
                            <td>
                                <?php if (!empty($item['equipment_img'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['equipment_img']); ?>" alt="Equipment Image">
                                <?php else: ?>
                                    ไม่มีรูปภาพ
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="add_equipment.php?equipment_id=<?php echo $item['equipment_id']; ?>" class="btn edit-btn">แก้ไข</a>
                                <a href="search_equipment.php?delete_id=<?php echo $item['room_eqID']; ?>&search_term=<?php echo urlencode($search_term); ?>" 
                                class="btn delete-btn" onclick="return confirm('คุณแน่ใจว่าต้องการลบอุปกรณ์นี้?');">ลบ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <p>ไม่พบผลลัพธ์ที่ตรงกับการค้นหา</p>
        <?php endif; ?>
    </div>
</body>
</html>
