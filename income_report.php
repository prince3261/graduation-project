<?php
include('auth.php');
include('condb.php');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานรายได้</title>
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
        button {
            background-color: #6633FF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        button:hover {
            background-color: #6600FF;
        }
        .excel-btn {
            background-color: #28a745;
        }
        .excel-btn:hover {
            background-color: #218838;
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
    </style>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>

    <div class="container">
        <h2>รายงานสรุปรายได้</h2>
        <form method="POST" action="" class="form-container">
            <div class="form-group">
                <label for="month">เลือกเดือน:</label>
                <select name="month" id="month">
                    <option value="">-- เลือกเดือน --</option>
                    <?php
                    $months = [
                        '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
                        '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
                        '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
                    ];
                    foreach ($months as $key => $month) {
                        echo "<option value=\"$key\">$month</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="year">เลือกปี:</label>
                <select name="year" id="year">
                    <option value="">-- เลือกปี --</option>
                    <?php
                    $currentYear = date("Y") + 543;
                    for ($i = $currentYear; $i >= $currentYear - 20; $i--) {
                        echo "<option value=\"$i\">$i</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="generate_report">ค้นหา</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_report'])) {
            $selectedMonth = $_POST['month'] ?? '';
            $selectedYear = $_POST['year'] ?? '';

            $searchQuery = "SELECT invoice_id, room_id, water_cost, electric_cost, rent_cost, penalty_service_cost, cost_detail, total_cost, invoice_date, invoice_month
                            FROM invoice i 
                            WHERE payment_statusID = 'P_status02'";

            if (!empty($selectedMonth) && !empty($selectedYear)) {
                $selected_date = "$selectedYear-$selectedMonth-01"; 
                $searchQuery .= " AND DATE(i.invoice_date) >= '$selected_date' 
                                  AND DATE(i.invoice_date) < DATE_ADD('$selected_date', INTERVAL 1 MONTH)";
            } elseif (!empty($selectedMonth)) {
                $searchQuery .= " AND MONTH(i.invoice_date) = '$selectedMonth'";
            } elseif (!empty($selectedYear)) {
                $searchQuery .= " AND YEAR(i.invoice_date) = '$selectedYear'";
            }

            $result = mysqli_query($conn, $searchQuery);

            if (mysqli_num_rows($result) > 0) {
                $header = "รายงานสรุปรายได้";
                if (!empty($selectedMonth)) $header .= " เดือน " . $months[$selectedMonth];
                if (!empty($selectedYear)) $header .= " ปี " . $selectedYear;
                echo "<h2>$header 
                        <form method='POST' action='export_excel.php' style='display: inline;'>
                            <button type='submit' name='export_excel' class='excel-btn'>ส่งออกเป็น Excel</button>
                        </form>
                      </h2>";

                echo "<table>";
                echo "<tr>
                        <th>เดือน</th>
                        <th>บิลรอบเดือน</th>
                        <th>รหัสเอกสาร</th>
                        <th>ห้อง</th>
                        <th>ค่าน้ำประปา</th>
                        <th>ค่าไฟฟ้า</th>
                        <th>ค่าเช่า</th>
                        <th>ค่าบริการและค่าปรับ</th>
                        <th>รายละเอียดค่าบริการและค่าปรับ</th>
                        <th>ราคารวม</th>
                      </tr>";

                $totalSum = 0;
                while ($row = mysqli_fetch_assoc($result)) {
                    $monthName = isset($row['invoice_date']) ? $months[date('m', strtotime($row['invoice_date']))] : 'ไม่ระบุ';
                    echo "<tr>
                            <td>$monthName</td>
                            <td>{$row['invoice_month']}</td>
                            <td>{$row['invoice_id']}</td>
                            <td>{$row['room_id']}</td>
                            <td>{$row['water_cost']}</td>
                            <td>{$row['electric_cost']}</td>
                            <td>{$row['rent_cost']}</td>
                            <td>{$row['penalty_service_cost']}</td>
                            <td>{$row['cost_detail']}</td>
                            <td>{$row['total_cost']}</td>
                          </tr>";
                    $totalSum += $row['total_cost'];
                }

                echo "<tr>
                        <td colspan='8' class='total-row'><strong>ยอดรวมทั้งหมด</strong></td>
                        <td><strong>$totalSum</strong></td>
                      </tr>";
                echo "</table>";
            } else {
                echo "<p>ไม่พบข้อมูลสำหรับเงื่อนไขที่เลือก</p>";
            }
        }
        ?>
    </div>
</body>
</html>
