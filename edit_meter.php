<?php
include('auth.php');
include('condb.php');

function getThaiMonth($month) {
    $thai_months = ['01' => 'มกราคม', 
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
                    '12' => 'ธันวาคม'
    ];
    return $thai_months[$month] ?? '';
}

if (isset($_GET['meter_id']) || $_SERVER['REQUEST_METHOD'] == 'POST') {
    $meter_id = $_GET['meter_id'] ?? $_POST['meter_id'];

    $sql = "SELECT * FROM meter WHERE meter_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $meter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $meter = $result->fetch_assoc();

    if (!$meter) {
        echo "<script>alert('ไม่พบข้อมูลมิเตอร์'); 
                window.location.href = 'search_meter.php';
              </script>";
        exit();
    }
}

if (isset($meter['room_id']) && isset($meter['meter_date'])) {
    $prev_query = "SELECT meter_value FROM meter 
        WHERE room_id = ? 
        AND meter_date < ? 
        ORDER BY meter_date DESC 
        LIMIT 1";
    $stmt_prev = $conn->prepare($prev_query);
    $stmt_prev->bind_param("ss", $meter['room_id'], $meter['meter_date']);
    $stmt_prev->execute();
    $prev_result = $stmt_prev->get_result();
    $prev_meter_value = $prev_result->fetch_assoc()['meter_value'] ?? 0;
} else {
    $prev_meter_value = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();

    try {
        if (empty($_POST['meter_value']) || empty($_POST['meter_rate']) || 
            empty($_POST['meter_date'])) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
        }

        $room_id = $_POST['room_id'];
        $meter_value = $_POST['meter_value'];
        $meter_rate = $_POST['meter_rate'];
        $meter_date = $_POST['meter_date'];
        $meter_month = $_POST['meter_month'];

        $meter_difference = $meter_value - $prev_meter_value;
        if ($meter_difference < 0) {
            throw new Exception("ค่ามิเตอร์ปัจจุบันน้อยกว่าค่ามิเตอร์เดือนก่อน");
        }
        $meter_price = $meter_difference * $meter_rate;
        $meter_month_thai = getThaiMonth($meter_month);
        $stmt = $conn->prepare("UPDATE meter 
                                SET room_id = ?, meter_value = ?, meter_rate = ?, 
                                    meter_price = ?, meter_date = ?, meter_month = ? 
                                WHERE meter_id = ?");
        $stmt->bind_param("sssssss", $room_id, $meter_value, $meter_rate, $meter_price, 
                           $meter_date, $meter_month_thai, $meter_id);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $conn->commit();

        echo "<script>alert('แก้ไขข้อมูลมิเตอร์สำเร็จ'); 
                window.location.href='search_meter.php?meter_id=$meter_id';
              </script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลมิเตอร์</title>
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
        function calculateMeterPrice() {
            const meterValueInput = document.getElementById('meter_value');
            const meterRateInput = document.getElementById('meter_rate');
            const prevMeterValueInput = document.getElementById('prev_meter_value');
            const meterPriceInput = document.getElementById('meter_price');

            const meterValue = parseFloat(meterValueInput.value) || 0;
            const meterRate = parseFloat(meterRateInput.value) || 0;
            const prevMeterValue = parseFloat(prevMeterValueInput.value) || 0;

            const meterDifference = meterValue - prevMeterValue;
            if (meterDifference < 0) {
                alert(`ค่ามิเตอร์ปัจจุบัน (${meterValue}) ต้องมากกว่าหรือเท่ากับค่ามิเตอร์เดือนก่อน (${prevMeterValue})`);
                meterPriceInput.value = "0.00";
                return;
            }

            const meterPrice = meterDifference * meterRate;
            meterPriceInput.value = meterPrice.toFixed(2);
        }

    </script>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="content">
        <h2>แก้ไขข้อมูลมิเตอร์</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="meter_id">รหัสมิเตอร์:<span style="color: red;">*</span></label>
                <select name="meter_id" id="meter_id" onchange="location.href='edit_meter.php?meter_id=' + this.value" required>
                    <option value="">-- เลือกรหัสมิเตอร์ --</option>
                    <?php
                    $sql_meter = "SELECT meter_id FROM meter";
                    $result_meter = $conn->query($sql_meter);
                    while ($row_meter = $result_meter->fetch_assoc()) {
                        $selected = (isset($meter) && $row_meter['meter_id'] == $meter['meter_id']) ? 'selected' : '';
                        echo "<option value='{$row_meter['meter_id']}' $selected>{$row_meter['meter_id']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="room_id">ห้อง:<span style="color: red;">*</span></label>
                <select name="room_id" id="room_id" onchange="calculateMeterPrice()" required>
                    <option value="">-- เลือกห้อง --</option>
                    <?php
                    $sql_room = "SELECT room_id FROM room";
                    $result_room = $conn->query($sql_room);
                    while ($row_room = $result_room->fetch_assoc()) {
                        $selected = (isset($meter) && $row_room['room_id'] == $meter['room_id']) ? 'selected' : '';
                        echo "<option value='{$row_room['room_id']}' $selected>{$row_room['room_id']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="meter_month">มิเตอร์เดือน:<span style="color: red;">*</span></label>
                <?php $meter_months = ['01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
                                 '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
                                 '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'];
                echo '<select name="meter_month" id="meter_month" required>';
                echo '<option value="">-- เลือกเดือน --</option>';
                $meter_month_value = array_search($meter['meter_month'], $meter_months); //แปลงกลับไปเป็นตัวเลข
                    foreach ($meter_months as $value => $label) {
                    echo "<option value=\"$value\" " . ($value === $meter_month_value ? 'selected' : '') . ">$label</option>";
                }
                echo '</select>';
                ?>
            </div>

            <div class="form-group">
                <label for="prev_meter_value">ค่ามิเตอร์เดือนก่อน:<span style="color: red;">*</span></label>
                <input type="text" id="prev_meter_value" value="<?php echo $prev_meter_value; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="meter_value">ค่ามิเตอร์:<span style="color: red;">*</span></label>
                <input type="text" name="meter_value" id="meter_value" value="<?php echo isset($meter['meter_value']) ? $meter['meter_value'] : ''; ?>" oninput="calculateMeterPrice()" required>
            </div>

            <div class="form-group">
                <label for="meter_rate">เรทมิเตอร์:<span style="color: red;">*</span></label>
                <input type="text" name="meter_rate" id="meter_rate" value="<?php echo isset($meter['meter_rate']) ? $meter['meter_rate'] : ''; ?>" oninput="calculateMeterPrice()" required>
            </div>

            <div class="form-group">
                <label for="meter_price">ราคามิเตอร์:<span style="color: red;">*</span></label>
                <input type="text" name="meter_price" id="meter_price" value="<?php echo isset($meter['meter_price']) ? $meter['meter_price'] : ''; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="meter_date">วันที่บันทึกมิเตอร์:<span style="color: red;">*</span></label>
                <input type="date" name="meter_date" id="meter_date" value="<?php echo isset($meter['meter_date']) ? date('Y-m-d', strtotime($meter['meter_date'])) : ''; ?>" required>
            </div>
    
            <input type="submit" class="submit-btn" value="บันทึกการแก้ไข">
        </form>
        
    </div>
</body>
</html>
