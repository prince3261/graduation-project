<?php
include('auth.php'); 
include('condb.php');

function generateInvoiceId($conn) {
    $query = "SELECT invoice_id FROM invoice ORDER BY invoice_id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row) {
        $lastId = intval(substr($row['invoice_id'], 7));
        $newId = $lastId + 1;
    } else {
        $newId = 1;
    }
    
    return 'invoice' . str_pad($newId, 4, '0', STR_PAD_LEFT);
}

$room_id = $_POST['room_id'] ?? '';
$month = $_POST['month'] ?? '';
$year = $_POST['year'] ?? '';
$electric_cost = 0;
$rent_cost = 0;
$water_cost = 0;
$total_cost = $total_cost ?? 0;
$meter_month = $meter_month ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_meter'])) {
    if (!empty($room_id) && !empty($month) && !empty($year)) {
        $electric_query = "SELECT meter_price, meter_month FROM meter 
                           WHERE room_id = '$room_id' 
                             AND MONTH(meter_date) = '$month' 
                             AND YEAR(meter_date) = '$year'
                           ORDER BY meter_date DESC LIMIT 1";
        $electric_result = mysqli_query($conn, $electric_query);
        if ($electric_result && mysqli_num_rows($electric_result) > 0) {
            $electric_data = mysqli_fetch_assoc($electric_result);
            $electric_cost = $electric_data['meter_price'];
            $meter_month = $electric_data['meter_month'];
        } else {
            $electric_cost = 0;
            $meter_month = 'ไม่ทราบ';
        }

        // คำนวณค่าเช่า
        $rent_query = "SELECT room_price FROM room WHERE room_id = '$room_id'";
        $rent_result = mysqli_query($conn, $rent_query);
        $rent_cost = ($rent_result && mysqli_num_rows($rent_result) > 0) ? mysqli_fetch_assoc($rent_result)['room_price'] : 0;

        // คำนวณค่าน้ำ (ผู้เช่า x 150 บาท)
        $water_cost_query = "SELECT COUNT(cu.user_id) AS total_users
                             FROM contract_user cu
                             JOIN contract c ON cu.contract_id = c.contract_id
                             WHERE c.room_id = ?";
        $stmt = $conn->prepare($water_cost_query);
        $stmt->bind_param("s", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_users = $row['total_users'] ?? 0;
        $water_cost = $total_users * 150;

        // คำนวณราคารวม
        $total_cost = $electric_cost + $rent_cost + $water_cost;
    } else {
        echo "<script>alert('กรุณาเลือกห้อง เดือน และปีให้ครบถ้วน');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_invoice'])) {
    if (!empty($_POST['room_id'])) {
        $invoice_id = generateInvoiceId($conn);
        $room_id = $_POST['room_id'];
        $water_cost = $_POST['water_cost'] ?? 0;
        $electric_cost = $_POST['electric_cost'] ?? 0;
        $rent_cost = $_POST['rent_cost'] ?? 0;
        $penalty_service_cost = $_POST['penalty_service_cost'] ?? 0;
        $cost_detail = $_POST['cost_detail'] ?? '';
        $total_cost = $_POST['total_cost'] ?? 0;
        $invoice_date = $_POST['invoice_date'] ?? date('Y-m-d');
        $invoice_month = $_POST['meter_month'] ?? 'ไม่ระบุ'; // ใช้ meter_month

        $date = new DateTime($invoice_date);
        $year = (int)$date->format('Y') + 543;
        $invoice_date = $date->setDate($year, (int)$date->format('m'), (int)$date->format('d'))->format('Y-m-d');

        $payment_statusID = 'P_status01';

        $query = "INSERT INTO invoice (invoice_id, room_id, water_cost, electric_cost, rent_cost, penalty_service_cost, cost_detail, total_cost, invoice_date, invoice_month, payment_statusID) 
                  VALUES ('$invoice_id', '$room_id', '$water_cost', '$electric_cost', '$rent_cost', '$penalty_service_cost', '$cost_detail', '$total_cost', '$invoice_date', '$invoice_month', '$payment_statusID')";

        if (mysqli_query($conn, $query)) {
            echo "<script>
                    alert('บันทึกใบแจ้งหนี้เรียบร้อยแล้ว');
                    window.location.href = 'search_invoice.php?invoice_id=$invoice_id';
                  </script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาด: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('กรุณาเลือกห้องก่อนบันทึกใบแจ้งหนี้');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มใบแจ้งหนี้</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
        }

        .content {
            margin-top: 30px;
            max-width: 100%;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-left: 10%;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            width: 600px;
        }

        .form-group label {
            width: 150px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            
        }
        .label2 {
            display: block;
            margin: 10px 0 5px;
            margin-right: 74px;
        }

        input[type="text"], input[type="date"], select, textarea, input[type="file"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        
        button {
            background-color: #fe2d85;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 15px;
        }

        button:hover {
            background-color: #e5005f;
        }

        .button2 {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .button2:hover {
            background-color: #0056b3;
        }

        .delete-btn {
            background-color: #d9534f;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            align-self: flex-start;
            width: 8%;
            margin-top: 13px;
        }

        .delete-btn:hover {
            background-color: #c9302c;
        }
        .search-btn {
            background-color: #6633FF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 15px;
        }

        .search-btn:hover {
            background-color: #6600FF;
        }

        #additional-costs {
            margin-top: 10px;
        }

        .additional-cost-row {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
            justify-content: space-between;
            width: 600px;
        }
    </style>
</head>
<body>
<?php include('admin_sidebar.php'); ?>
<div class="content">
    <h2>เพิ่มใบแจ้งหนี้</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="room_id">เลือกห้อง:<span style="color: red;">*</span></label>
            <select name="room_id" id="room_id" required>
                <option value="">-- เลือกห้อง --</option>
                <?php
                $room_query = "SELECT room_id FROM room ORDER BY CASE
                                        WHEN room_id LIKE '%A' THEN 1
                                        WHEN room_id LIKE '%B' THEN 2
                                        WHEN room_id LIKE '%C' THEN 3
                                        WHEN room_id LIKE '%D' THEN 4
                                        ELSE 5
                                    END, 
                                    room_id ASC
                                ";
                $room_result = mysqli_query($conn, $room_query);
                while ($row = mysqli_fetch_assoc($room_result)) {
                    $selected = ($row['room_id'] == ($_POST['room_id'] ?? '')) ? 'selected' : '';
                    echo "<option value='{$row['room_id']}' $selected>{$row['room_id']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="month">เลือกเดือน:<span style="color: red;">*</span></label>
            <select name="month" id="month" required>
                <option value="">-- เลือกเดือน --</option>
                <?php
                $thai_months = [
                    "01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม",
                    "04" => "เมษายน", "05" => "พฤษภาคม", "06" => "มิถุนายน",
                    "07" => "กรกฎาคม", "08" => "สิงหาคม", "09" => "กันยายน",
                    "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"
                ];
                foreach ($thai_months as $key => $value) {
                    $selected = ($key == ($_POST['month'] ?? '')) ? 'selected' : '';
                    echo "<option value='$key' $selected>$value</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="year">เลือกปี:<span style="color: red;">*</span></label>
            <select name="year" id="year" required>
                <option value="">-- เลือกปี --</option>
                <?php
                $current_year = date("Y") + 543;
                for ($i = $current_year; $i >= $current_year - 10; $i--) {
                    $selected = ($i == ($_POST['year'] ?? '')) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" name="search_meter" class="search-btn">ค้นหา</button>
    </form>

    <form method="POST" action="add_invoice.php">
        <div class="form-group">
            <label for="electric_cost">ค่าไฟฟ้า:</label>
            <input type="text" id="electric_cost" name="electric_cost" value="<?= $electric_cost ?>" readonly>
        </div>

        <div class="form-group">
            <label for="water_cost">ค่าน้ำประปา:</label>
            <input type="text" id="water_cost" name="water_cost" value="<?= $water_cost ?>" readonly>
        </div>

        <div class="form-group">
            <label for="rent_cost">ค่าเช่า:</label>
            <input type="text" id="rent_cost" name="rent_cost" value="<?= $rent_cost ?>" readonly>
        </div>

    <form method="POST" action="">
        <input type="hidden" name="room_id" value="<?= $_POST['room_id'] ?? '' ?>">
        <input type="hidden" name="month" value="<?= $_POST['month'] ?? '' ?>">
        <input type="hidden" name="year" value="<?= $_POST['year'] ?? '' ?>">

        <button class="button2" type="button" id="add_penalty_service">เพิ่มค่าบริการและค่าปรับ</button>
        
        <div id="penalty_service_container"></div>

        <div class="form-group">
            <label for="cost_detail">รายละเอียด<br>ค่าบริการ<br>และค่าปรับ:</label>
            <textarea id="cost_detail" name="cost_detail" rows="8" placeholder="รายละเอียดค่าบริการและค่าปรับ"></textarea>
        </div>

        <div class="form-group">
            <label for="penalty_service_cost">รวมค่าบริการ<br>และค่าปรับ:</label>
            <input type="text" id="penalty_service_cost" placeholder="รวมค่าบริการและค่าปรับ" name="penalty_service_cost" readonly>
        </div>

        <div class="form-group">
            <label for="total_cost">ราคาทั้งหมด:</label>
            <input type="text" id="total_cost" name="total_cost" value="<?= $total_cost ?>" readonly>
        </div>

        <div class="form-group">
            <label for="invoice_date">วันที่บันทึก<br>ใบแจ้งหนี้:<span style="color: red;">*</span></label>
            <input type="date" id="invoice_date" name="invoice_date" required>
        </div>

        <input type="hidden" id="meter_month" name="meter_month" value="<?= $meter_month ?>" readonly>
        
        <button type="submit" name="save_invoice">บันทึกใบแจ้งหนี้</button>
    </form>
</div>

<script>
document.querySelectorAll('#water_cost, #electric_cost, #rent_cost, #penalty_service_cost').forEach(input => {
    input.addEventListener('input', calculateTotalCost);
});

document.getElementById('add_penalty_service').addEventListener('click', function () {
    const container = document.getElementById('penalty_service_container');
    const inputField = document.createElement('div');
    inputField.className = 'additional-cost-row';

    inputField.innerHTML = `
        <label for="cost" class="label2">ราคา:</label>
        <input type="text" class="penalty_service_input" placeholder="ราคา" oninput="updatePenaltyServiceCost()">
        <button type="button" class="delete-btn">ลบ</button>
    `;

    container.appendChild(inputField);

    inputField.querySelector('.delete-btn').addEventListener('click', function () {
        inputField.remove();
        updatePenaltyServiceCost();
    });
});

function updatePenaltyServiceCost() {
    let totalPenaltyServiceCost = 0;
    document.querySelectorAll('.penalty_service_input').forEach(input => {
        totalPenaltyServiceCost += parseFloat(input.value) || 0;
    });
    document.getElementById('penalty_service_cost').value = totalPenaltyServiceCost.toFixed(2);

    calculateTotalCost();
}

function calculateTotalCost() {
    const waterCost = parseFloat(document.getElementById('water_cost').value) || 0;
    const electricCost = parseFloat(document.getElementById('electric_cost').value) || 0;
    const rentCost = parseFloat(document.getElementById('rent_cost').value) || 0;
    const penaltyServiceCost = parseFloat(document.getElementById('penalty_service_cost').value) || 0;

    const totalCost = waterCost + electricCost + rentCost + penaltyServiceCost;
    document.getElementById('total_cost').value = totalCost.toFixed(2);
}
</script>
</body>
</html>
