<?php
include('auth.php');
include('condb.php');

if (!function_exists('generateMeterID')) {
    function generateMeterID($conn) {
        $query = "SELECT meter_id FROM meter ORDER BY meter_id DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        $lastID = mysqli_fetch_assoc($result)['meter_id'] ?? 'meter00';

        $lastNumber = intval(substr($lastID, 5)) + 1;
        return 'meter' . str_pad($lastNumber, 2, '0', STR_PAD_LEFT);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];

    $query = "SELECT meter_value FROM meter WHERE room_id = ? ORDER BY meter_date DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastMeter = $result->fetch_assoc();

    echo json_encode(['last_meter_value' => $lastMeter['meter_value'] ?? 0]);
    exit();
}

if (!function_exists('getMonthThaiName')) {
    function getMonthThaiName($monthNumber) {
        $thaiMonths = [
            '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
            '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
            '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
        ];
        return $thaiMonths[$monthNumber] ?? '';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();

    try {
        $meter_ids = [];
        $meter_date = $_POST['meter_date'];
        $meter_rate = $_POST['meter_rate'];
        $meter_month = $_POST['meter_month'];
        $meter_month_thai = getMonthThaiName($meter_month);

        $meter_date_buddhist = date('Y-m-d H:i:s', strtotime($meter_date . ' +543 years'));

        foreach ($_POST['room_id'] as $index => $room_id) {
            $check_query = "SELECT COUNT(*) AS count FROM meter WHERE room_id = ? AND DATE_FORMAT(meter_date, '%Y-%m') = DATE_FORMAT(?, '%Y-%m')";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ss", $room_id, $meter_date_buddhist);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();

            if ($check_row['count'] > 0) {
                throw new Exception("ไม่สามารถบันทึกมิเตอร์สำหรับห้อง $room_id ในเดือนนี้ได้ เนื่องจากมีการบันทึกไปแล้ว");
            }
        }

        foreach ($_POST['room_id'] as $index => $room_id) {
            $meter_value = $_POST['meter_value'][$index];
            $meter_price = $_POST['meter_price'][$index];

            $meter_id = generateMeterID($conn);
            $meter_ids[] = $meter_id;

            $stmt = $conn->prepare("INSERT INTO meter (meter_id, room_id, meter_month, meter_value, meter_rate, meter_price, meter_date) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $meter_id, $room_id, $meter_month_thai, $meter_value, $meter_rate, $meter_price, $meter_date_buddhist);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
        }

        $conn->commit();
        $meter_ids_str = implode(',', $meter_ids);

        echo "<script>alert('บันทึกมิเตอร์สำเร็จ'); window.location.href='search_meter.php?meter_id=$meter_ids_str';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');
                window.location.href = 'add_meter.php';
              </script>";
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มข้อมูลมิเตอร์</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            padding: 50px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .content {
            width: 60%;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            width: 98%;
        }
        .form-group label {
            width: 150px;
        }
        input[type="text"], input[type="date"], select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        .meter-group {
            border: 1px solid #17202a;
            border-radius: 5px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f5eef8;
        }
        button {
            background-color: #d9534f;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        button:hover {
            background-color: #c9302c;
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
            padding: 10px;
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
        .meter-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .meter-btn:hover{
            background-color: #0056b3;
        }
        .submit-btn {
            background-color: #fe2d85;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 15px;
        }
        .submit-btn:hover {
            background-color: #e5005f;
        }
    </style>
<script>
    async function calculateMeterPrice(group) {
    const meterRateInput = document.getElementById('meter_rate');
    const meterValueInput = group.querySelector('.meter_value');
    const meterPriceInput = group.querySelector('.meter_price');
    const roomId = group.querySelector('select[name="room_id[]"]').value;

    const meterRate = parseFloat(meterRateInput.value) || 0;
    const meterValue = parseFloat(meterValueInput.value) || 0;

    if (roomId && meterValue && meterRate) {
        const lastMeterValue = await fetch(`add_meter.php?room_id=${roomId}`)
            .then((res) => res.json())
            .then((data) => parseFloat(data.last_meter_value) || 0);

        const meterDifference = meterValue - lastMeterValue;

        const meterPrice = meterDifference > 0 ? meterDifference * meterRate : 0;
        meterPriceInput.value = meterPrice.toFixed(2);
    }
}
    function addMeterGroup() {
        const meterContainer = document.getElementById('meter-container');
        const newGroup = document.createElement('div');
        newGroup.className = 'meter-group';

        newGroup.innerHTML = `
            <div class="form-group">
                <label for="room_id">ห้อง:<span style="color: red;">*</span></label>
                <select name="room_id[]" required onchange="
                calculateMeterPrice(this.parentElement.parentElement)">
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
                        echo "<option value='{$row['room_id']}'>{$row['room_id']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="meter_value">ค่ามิเตอร์:<span style="color: red;">*</span></label>
                <input type="text" name="meter_value[]" 
                class="meter_value" required oninput="calculateMeterPrice(this.parentElement.parentElement)">
            </div>
            <div class="form-group">
                <label for="meter_price">ราคามิเตอร์:</label>
                <input type="text" name="meter_price[]" class="meter_price" readonly>
            </div>
            <button type="button" onclick="removeMeterGroup(this)">ลบมิเตอร์นี้</button>
        `;

        meterContainer.appendChild(newGroup);
    }

    function removeMeterGroup(button) {
        const group = button.parentElement;
        group.remove();
    }
</script>

</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="content">
        <h2>เพิ่มข้อมูลมิเตอร์</h2>
        <form action="add_meter.php" method="post">
            <div class="form-group">
                <label for="meter_date">วันที่บันทึก:<span style="color: red;">*</span></label>
                <input type="date" name="meter_date" id="meter_date" required>
            </div>

            <div class="form-group">
                <label for="meter_rate">เรทมิเตอร์:<span style="color: red;">*</span></label>
                <input type="text" name="meter_rate" id="meter_rate" required oninput="document.querySelectorAll('.meter-group').forEach(calculateMeterPrice)">
            </div>

            <div class="form-group">
                <label for="meter_month">มิเตอร์เดือน:<span style="color: red;">*</span></label>
                <?php $meter_months = ['01' => 'มกราคม',
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
                echo '<select name="meter_month" id="meter_month" required>';
                echo '<option value="">-- เลือกเดือน --</option>';
                foreach ($meter_months as $value => $label) {
                    echo "<option value=\"$value\">$label</option>";
                }
                echo '</select>';
                ?>
            </div>

            <div id="meter-container">
                <div class="meter-group">
                    <div class="form-group">
                        <label for="room_id">ห้อง:<span style="color: red;">*</span></label>
                        <select name="room_id[]" required>
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
                                echo "<option value='{$row['room_id']}'>{$row['room_id']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="meter_value">ค่ามิเตอร์:<span style="color: red;">*</span></label>
                        <input type="text" name="meter_value[]" class="meter_value" 
                        required oninput="calculateMeterPrice(this.parentElement.parentElement)">
                    </div>
                    <div class="form-group">
                        <label for="meter_price">ราคามิเตอร์:</label>
                        <input type="text" name="meter_price[]" class="meter_price" readonly>
                    </div>
                </div>
            </div>
            <button type="button" class="meter-btn" onclick="addMeterGroup()">เพิ่มมิเตอร์</button>
            <button type="submit" class="submit-btn">บันทึกมิเตอร์</button>
        </form>

        <h2>ข้อมูลมิเตอร์เดือนก่อน</h2>
        <table>
            <thead>
                <tr>
                    <th>รหัสมิเตอร์</th>
                    <th>ห้อง</th>
                    <th>ค่ามิเตอร์</th>
                    <th>เรทมิเตอร์</th>
                    <th>ราคามิเตอร์</th>
                    <th>วันที่บันทึก</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $previous_month = date('Y-m', strtotime('-1 month'));
                $query = "SELECT meter_id, room_id, meter_value, meter_rate, meter_price, meter_date 
                                FROM meter 
                                WHERE DATE_FORMAT(DATE_SUB(meter_date, INTERVAL 543 YEAR), '%Y-%m') = ? 
                                ORDER BY CASE WHEN room_id LIKE '%A' THEN 1
                                            WHEN room_id LIKE '%B' THEN 2
                                            WHEN room_id LIKE '%C' THEN 3
                                            WHEN room_id LIKE '%D' THEN 4
                                            ELSE 5
                                            END, room_id ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $previous_month);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['meter_id']}</td>
                                <td>{$row['room_id']}</td>
                                <td>{$row['meter_value']}</td>
                                <td>{$row['meter_rate']}</td>
                                <td>" . number_format($row['meter_price']) . " บาท</td>
                                <td>{$row['meter_date']}</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>ไม่มีข้อมูลมิเตอร์</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
