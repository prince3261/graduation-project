<?php
include('auth.php');
include('condb.php');

$search_term = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : (isset($_POST['search_term']) ? $_POST['search_term'] : '');
$room_id = $_POST['room_id'] ?? '';
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$result = null;
$search_performed = false;
$payment_status = $_POST['payment_status'] ?? '';

$thai_months = [
    "01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม",
    "04" => "เมษายน", "05" => "พฤษภาคม", "06" => "มิถุนายน",
    "07" => "กรกฎาคม", "08" => "สิงหาคม", "09" => "กันยายน",
    "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($search_term) || !empty($room_id)) {
    $search_performed = true;

    $sql = "SELECT i.invoice_id, i.room_id, 
                GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR '<br>') AS tenant_names,
                i.water_cost, i.electric_cost, i.rent_cost, i.penalty_service_cost, i.total_cost, 
                i.invoice_date, i.cost_detail, ps.payment_status_name, pt.payment_type_name, 
                i.payment_date, i.payment_img, i.invoice_month
            FROM invoice i
            JOIN contract c ON i.room_id = c.room_id
            JOIN contract_user cu ON c.contract_id = cu.contract_id
            JOIN user u ON cu.user_id = u.user_id
            JOIN payment_status ps ON i.payment_statusID = ps.payment_statusID
            LEFT JOIN payment_type pt ON i.payment_typeID = pt.payment_typeID
            WHERE 1=1";

    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($search_term)) {
        $conditions[] = "(i.invoice_id LIKE ? OR i.room_id LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
        $like_term = "%$search_term%";
        $params = array_merge($params, [$like_term, $like_term, $like_term]);
        $types .= 'sss';
    }

    if (!empty($room_id)) {
        $conditions[] = "i.room_id = ?";
        $params[] = $room_id;
        $types .= 's';
    }

    if (!empty($month)) {
        $conditions[] = "i.invoice_month = ?";
        $params[] = $thai_months[$month];
        $types .= 's';
    }

    if (!empty($year)) {
        $conditions[] = "YEAR(i.invoice_date) = ?";
        $params[] = $year;
        $types .= 's';
    }

    if (!empty($payment_status)) {
        $conditions[] = "i.payment_statusID = ?";
        $params[] = $payment_status;
        $types .= 's';
    }

    if (!empty($conditions)) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }

    $sql .= " GROUP BY i.invoice_id";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $invoices = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
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
            width: 170px;
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
        .edit-btn, .delete-btn, .pdf-btn, .toggle-btn, .mail-btn {
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

        .delete-btn {
            background-color: #d9534f;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }

        .pdf-btn {
            background-color: #5cb85c;
        }
        .pdf-btn:hover {
            background-color: #4cae4c;
        }

        .mail-btn {
            background-color: #fd7e14;
        }
        .mail-btn:hover {
            background-color: #e06a00;
        }

        .toggle-btn {
            background-color: #9b59b6;
        }
        .toggle-btn:hover {
            background-color: #8e44ad;
        }
        .extra-info {
            display: none;
            background-color: #f9f9f9;
        }
    </style>
    <script>
        $(document).ready(function() {
            $('.toggle-btn').on('click', function() {
                $(this).closest('tr').next('.extra-info').toggle();
                $(this).text($(this).text() === 'รายละเอียด' ? 'ซ่อนรายละเอียด' : 'รายละเอียด');
            });
        });
    </script>
</head>

<body>
    <?php include('admin_sidebar.php');?>
    <div class="container">
        <h2>ค้นหาข้อมูลใบแจ้งหนี้และใบเสร็จ</h2>
        <form action="search_invoice.php" method="post" class="form-container">
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
            <div class="form-group">
                <label for="month">เดือน:</label>
                <select name="month" id="month">
                    <option value="">-- เลือกเดือน --</option>
                    <?php foreach ($thai_months as $key => $value): ?>
                        <option value="<?= $key ?>" <?= ($month == $key) ? 'selected' : '' ?>><?= $value ?></option>
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
                <label for="payment_status">สถานะการชำระ:</label>
                <select name="payment_status" id="payment_status">
                    <option value="">-- เลือกสถานะการชำระ --</option>
                    <?php
                    $status_query = "SELECT payment_statusID, payment_status_name FROM payment_status";
                    $status_result = mysqli_query($conn, $status_query);
                    while ($row = mysqli_fetch_assoc($status_result)) {
                        echo "<option value='{$row['payment_statusID']}'" . (($payment_status == $row['payment_statusID']) ? ' selected' : '') . ">{$row['payment_status_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="search_term">ค้นหา (ชื่อ-นามสกุล, เลขห้อง, รหัสใบแจ้งหนี้/ใบเสร็จ):</label>
                <input type="text" name="search_term" id="search_term" value="<?php echo $search_term; ?>" placeholder="">
            </div>
            <button type="submit">ค้นหา</button>
        </form>

        <?php if ($search_performed && $result && mysqli_num_rows($result) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>รหัสเอกสาร</th>
                        <th>รอบเดือน</th>
                        <th>ห้อง</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>วันที่บันทึก</th>
                        <th>ราคาทั้งหมด</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?= htmlspecialchars($invoice['invoice_id']) ?></td>
                        <td><?= htmlspecialchars($invoice['invoice_month']) ?></td>
                        <td><?= htmlspecialchars($invoice['room_id']) ?></td>
                        <td><?= $invoice['tenant_names'] ?></td>
                        <td><?= htmlspecialchars($invoice['invoice_date']) ?></td>
                        <td><?= number_format($invoice['total_cost']) ?> บาท</td>
                        <td><?= htmlspecialchars($invoice['payment_status_name']) ?></td>
                        <td>
                            <button class="toggle-btn">รายละเอียด</button>
                            <?php if ($invoice['payment_status_name'] == 'ยังไม่ชำระ') { ?>
                                <a href="invoice.php?invoice_id=<?= htmlspecialchars($invoice['invoice_id']) ?>" target="_blank" class="pdf-btn">PDF</a>
                                <a href="edit_invoice.php?invoice_id=<?= htmlspecialchars($invoice['invoice_id']) ?>" class="edit-btn">แก้ไข</a>
                                <a href="search_invoice.php?delete_id=<?= htmlspecialchars($invoice['invoice_id']) ?>" onclick="return confirm('คุณแน่ใจว่าต้องการลบใบแจ้งหนี้นี้?');" class="delete-btn">ลบ</a>
                            <?php } elseif ($invoice['payment_status_name'] == 'ชำระแล้ว') { ?>
                                <a href="receipt.php?invoice_id=<?= htmlspecialchars($invoice['invoice_id']) ?>" target="_blank" class="pdf-btn">PDF</a>
                            <?php } elseif ($invoice['payment_status_name'] == 'รอการตรวจสอบ') { ?>
                                <a href="edit_invoice.php?invoice_id=<?= htmlspecialchars($invoice['invoice_id']) ?>" class="edit-btn">แก้ไข</a>
                            <?php } ?>
                            <a href="mail_invoice.php?invoice_id=<?= htmlspecialchars($invoice['invoice_id']) ?>" 
                                onclick="return confirm('ส่งใบแจ้งหนี้/ใบเสร็จให้ลูกค้า');" target="_blank" class="mail-btn">ส่งอีเมล</a>
                        </td>
                        </tr>
                        <tr class="extra-info">
                            <td colspan="8">
                                <table style="width: 100%; background-color: #fff; margin-bottom: 20px;">
                                    <tr>
                                        <th>ค่าน้ำประปา</th>
                                        <th>ค่าไฟฟ้า</th>
                                        <th>ค่าเช่า</th>
                                        <th>ค่าบริการและค่าปรับ</th>
                                        <th>รายละเอียดค่าบริการ</th>
                                        <th>วันที่ชำระ</th>
                                        <th>วิธีการชำระ</th>
                                        <th>หลักฐานการชำระเงิน</th>
                                    </tr>
                                    <tr>
                                        <td><?php echo number_format($invoice['water_cost']); ?> บาท</td>
                                        <td><?php echo number_format($invoice['electric_cost']); ?> บาท</td>
                                        <td><?php echo number_format(($invoice['rent_cost'] ?? 0)); ?> บาท</td>
                                        <td><?= number_format(floatval($invoice['penalty_service_cost'] ?? 0)); ?> บาท</td>
                                        <td><?php echo htmlspecialchars($invoice['cost_detail']); ?></td>
                                        <td><?php echo isset($invoice['payment_date']) ? date('Y-m-d', strtotime($invoice['payment_date'])) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($invoice['payment_type_name'] ?? '-'); ?></td>
                                        <td colspan="2">
                                            <?php 
                                            $payment_img_path = "payment/" . $invoice['payment_img'];
                                            if (!empty($invoice['payment_img']) && file_exists($payment_img_path)): ?>
                                                <a href="<?php echo htmlspecialchars($payment_img_path); ?>" target="_blank">ดูหลักฐาน</a>
                                            <?php else: ?>
                                                ไม่มีหลักฐาน                           
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php } else if ($search_performed) { ?>
            <p>ไม่พบข้อมูล</p>
        <?php } ?>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>
