<?php
include('auth.php');
include('condb.php');

$invoice_list = [];
$invoice_query = "SELECT invoice_id FROM invoice";
$invoice_result = mysqli_query($conn, $invoice_query);
while ($row = mysqli_fetch_assoc($invoice_result)) {
    $invoice_list[] = $row['invoice_id'];
}

$invoice_id = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : null;
$invoice = null;

if ($invoice_id) {
    $query = "SELECT * FROM invoice WHERE invoice_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $invoice = $result->fetch_assoc();
    } else {
        echo "<script>alert('ไม่พบข้อมูลใบแจ้งหนี้');</script>";
    }
}

if (!$invoice) {
    $invoice = [
        'invoice_id' => '',
        'room_id' => '',
        'water_cost' => '',
        'electric_cost' => '',
        'rent_cost' => '',
        'penalty_service_cost' => '',
        'cost_detail' => '',
        'total_cost' => '',
        'invoice_date' => '',
        'payment_typeID' => '',
        'payment_img' => '',
        'payment_date' => '',
        'payment_statusID' => ''
    ];
}

$payment_types = [];
$payment_type_query = "SELECT payment_typeID, payment_type_name FROM payment_type";
$payment_type_result = mysqli_query($conn, $payment_type_query);
while ($row = mysqli_fetch_assoc($payment_type_result)) {
    $payment_types[] = $row;
}

$payment_status = [];
$payment_status_query = "SELECT payment_statusID, payment_status_name FROM payment_status";
$payment_status_result = mysqli_query($conn, $payment_status_query);
while ($row = mysqli_fetch_assoc($payment_status_result)) {
    $payment_status[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $invoice_id = $_POST['invoice_id'];
    $query = "SELECT * FROM invoice WHERE invoice_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_invoice = $result->fetch_assoc();

    $room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : $current_invoice['room_id'];
    $water_cost = !empty($_POST['water_cost']) ? $_POST['water_cost'] : $current_invoice['water_cost'];
    $electric_cost = !empty($_POST['electric_cost']) ? $_POST['electric_cost'] : $current_invoice['electric_cost'];
    $rent_cost = !empty($_POST['rent_cost']) ? $_POST['rent_cost'] : $current_invoice['rent_cost'];
    $penalty_service_cost = !empty($_POST['penalty_service_cost']) ? $_POST['penalty_service_cost'] : $current_invoice['penalty_service_cost'];
    $cost_detail = !empty($_POST['cost_detail']) ? $_POST['cost_detail'] : $current_invoice['cost_detail'];
    $total_cost = !empty($_POST['total_cost']) ? $_POST['total_cost'] : $current_invoice['total_cost'];
    $invoice_date = !empty($_POST['invoice_date']) ? $_POST['invoice_date'] : $current_invoice['invoice_date'];
    $payment_typeID = !empty($_POST['payment_typeID']) ? $_POST['payment_typeID'] : $current_invoice['payment_typeID'];
    $payment_statusID = !empty($_POST['payment_statusID']) ? $_POST['payment_statusID'] : $current_invoice['payment_statusID'];

    if (!empty($_POST['payment_date'])) {
        $payment_date_parts = explode('-', $_POST['payment_date']);
        if (count($payment_date_parts) === 3) {
            $payment_year = (int)$payment_date_parts[0] + 543;
            $payment_month = $payment_date_parts[1];
            $payment_day = $payment_date_parts[2];
            $payment_date = "$payment_year-$payment_month-$payment_day";
        } else {
            $payment_date = $_POST['payment_date'];
        }
    } else {
        $payment_date = $invoice_date;
    }

    $target_dir = "payment/";
    if (isset($_FILES['payment_img']) && $_FILES['payment_img']['error'] == 0) {
        $payment_img = basename($_FILES['payment_img']['name']);
        $target_file = $target_dir . $payment_img;

        if (move_uploaded_file($_FILES['payment_img']['tmp_name'], $target_file)) {
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');</script>";
            exit();
        }
    } else {
        $payment_img = $current_invoice['payment_img'];
    }

    if (!empty($payment_img) && !empty($payment_typeID) && !empty($payment_date)) {
        $payment_statusID = 'P_status02';
    } else {
        $payment_statusID = $current_invoice['payment_statusID'];
    }

    $query = "UPDATE invoice 
              SET room_id = ?, water_cost = ?, electric_cost = ?, rent_cost = ?, penalty_service_cost = ?, cost_detail = ?, total_cost = ?,
                  invoice_date = ?, payment_typeID = ?, payment_date = ?, payment_img = ?, payment_statusID = ? 
              WHERE invoice_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssssssss", $room_id, $water_cost, $electric_cost, $rent_cost, $penalty_service_cost, $cost_detail,
                                      $total_cost, $invoice_date, $payment_typeID, $payment_date, $payment_img, $payment_statusID, $invoice_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('อัปเดตข้อมูลใบแจ้งหนี้เรียบร้อย');
                window.location.href='search_invoice.php?invoice_id=$invoice_id';
              </script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตข้อมูล');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขใบแจ้งหนี้</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    <?php include('admin_sidebar.php');?>
    <div class="content">
        <h2>แก้ไขใบแจ้งหนี้</h2>

        <form method="GET" action="edit_invoice.php">
            <div class="form-group">
                <label for="invoice_id">เลือกใบแจ้งหนี้:<span style="color: red;">*</span></label>
                <select name="invoice_id" id="invoice_id" onchange="this.form.submit()">
                    <option value="">--เลือกใบแจ้งหนี้--</option>
                    <?php
                    foreach ($invoice_list as $id) {
                        $selected = ($invoice_id == $id) ? 'selected' : '';
                        echo "<option value='$id' $selected>$id</option>";
                    }
                    ?>
                </select>
            </div>
        </form>

        <form method="POST" action="edit_invoice.php" enctype="multipart/form-data">
            <div class="form-group" style="display: none;">
                <label for="invoice_id">รหัสใบแจ้งหนี้:</label>
                <input type="text" name="invoice_id" value="<?php echo $invoice['invoice_id']; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="room_id">รหัสห้อง:</label>
                <input type="text" placeholder="รหัสห้อง" name="room_id" value="<?php echo $invoice['room_id']; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="water_cost">ค่าน้ำประปา:</label>
                <input type="text" placeholder="ค่าน้ำประปา" name="water_cost" id="water_cost" value="<?php echo $invoice['water_cost']; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="electric_cost">ค่าไฟฟ้า:</label>
                <input type="text" placeholder="ค่าไฟฟ้า" name="electric_cost" id="electric_cost" value="<?php echo $invoice['electric_cost']; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="rent_cost">ค่าเช่า:</label>
                <input type="text" placeholder="ค่าเช่า" name="rent_cost" id="rent_cost" value="<?php echo $invoice['rent_cost']; ?>" readonly>
            </div>

            <button type="button" id="add_penalty_service" class="button2">เพิ่มค่าบริการและค่าปรับ</button>
            <div id="penalty_service_container"></div>

            <div class="form-group">
                <label for="cost_detail">รายละเอียด <br> ค่าบริการ <br> และค่าปรับ:</label>
                <textarea rows="6" placeholder="รายละเอียดค่าบริการ" name="cost_detail"><?php echo $invoice['cost_detail']; ?></textarea>
            </div>

            <div class="form-group">
                <label for="penalty_service_cost">รวมค่าบริการ <br> และค่าปรับ:</label>
                <input type="text" placeholder="รวมค่าบริการ" name="penalty_service_cost" id="penalty_service_cost" value="<?php echo $invoice['penalty_service_cost']; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="total_cost">ราคาทั้งหมด:</label>
                <input type="text" placeholder="ราคาทั้งหมด" name="total_cost" id="total_cost" value="<?php echo $invoice['total_cost']; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="invoice_date">วันที่บันทึก <br> ใบแจ้งหนี้:</label>
                <input type="date" name="invoice_date" value="<?php echo $invoice['invoice_date']; ?>">
            </div>
   
            <button type="button" id="showPaymentFields" class="button2">เพิ่มการชำระเงิน</button>
            <div id="paymentFields" style="display: none;">
                <div class="form-group">
                    <label for="payment_img">ไฟล์หลักฐาน <br> การชำระเงิน:</label>
                    <input type="file" name="payment_img" id="payment_img">
                </div>

                <div class="form-group">
                    <label for="payment_typeID">ประเภท <br> การชำระเงิน:</label>
                    <select name="payment_typeID" id="payment_typeID">
                        <option value="">--เลือกประเภทการชำระเงิน--</option>
                        <?php foreach ($payment_types as $type) {
                                $selected = ($type['payment_typeID'] == $invoice['payment_typeID']) ? 'selected' : '';
                                echo "<option value='{$type['payment_typeID']}' $selected>{$type['payment_type_name']}</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="payment_date">วันที่ชำระเงิน:</label>
                    <input type="date" name="payment_date" value="<?php echo $invoice['payment_date']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="payment_statusID">สถานะ <br> การชำระเงิน:</label>
                <select name="payment_statusID" id="payment_statusID">
                    <option value="">--สถานะการชำระเงิน--</option>
                    <?php foreach ($payment_status as $type) {
                            $selected = ($type['payment_statusID'] == $invoice['payment_statusID']) ? 'selected' : '';
                            echo "<option value='{$type['payment_statusID']}' $selected>{$type['payment_status_name']}</option>";
                        }
                    ?>
                </select>
            </div>

            <button type="submit">บันทึกการแก้ไข</button>
        </form>
    </div>

    <script>
        $('#add_penalty_service').on('click', function() {
            var inputField = '<div class="additional-cost-row">' +
                                '<label for="cost" class="label2">ราคา:</label>' +
                                '<input type="text" placeholder="ราคา" class="penalty_service_input" oninput="updatePenaltyServiceCost()">' + 
                                '<button type="button" class="delete-btn">ลบ</button>' +
                             '</div>';
            $('#penalty_service_container').append(inputField);
            updatePenaltyServiceCost();
        });

        $(document).on('click', '.delete-btn', function() {
            $(this).parent('.additional-cost-row').remove();
            updatePenaltyServiceCost();
        });

        function updatePenaltyServiceCost() {
            var totalPenaltyServiceCost = 0;
            $('.penalty_service_input').each(function() {
                var value = parseFloat($(this).val()) || 0;
                totalPenaltyServiceCost += value;
            });
            $('#penalty_service_cost').val(totalPenaltyServiceCost.toFixed(2));
            calculateTotalCost();
        }

        function calculateTotalCost() {
            var water_cost = parseFloat($('#water_cost').val()) || 0;
            var electric_cost = parseFloat($('#electric_cost').val()) || 0;
            var rent_cost = parseFloat($('#rent_cost').val()) || 0;
            var penalty_service_cost = parseFloat($('#penalty_service_cost').val()) || 0;

            var total_cost = water_cost + electric_cost + rent_cost + penalty_service_cost;
            $('#total_cost').val(total_cost.toFixed(2));
        }

        $('#rent_cost').on('input', calculateTotalCost);

        document.getElementById("showPaymentFields").addEventListener("click", function() {
            document.getElementById("paymentFields").style.display = "block";
            this.style.display = "none";
        });
    </script>
</body>
</html>
