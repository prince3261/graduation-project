<?php 
include('auth.php'); 
include('condb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $user_address = $_POST['user_address'];

    $sql_check = "SELECT user_id FROM user WHERE user_id = ? OR email = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("ss", $user_id, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo "<script>alert('รหัสผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว กรุณาใช้ข้อมูลอื่น'); window.history.back();</script>";
        } else {
            $sql = "INSERT INTO user (user_id, password, first_name, last_name, email, phone, user_address) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssssss", $user_id, $password, $first_name, $last_name, $email, $phone, $user_address);
                if ($stmt->execute()) {
                    echo "<script>alert('สร้างบัญชีผู้ใช้งานเรียบร้อยแล้ว'); window.location.href='search_admin.php?user_id=" . urlencode($user_id) . "';</script>";
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาด: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL');</script>";
            }
        }
        $stmt_check->close();
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการตรวจสอบข้อมูล');</script>";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างบัญชีผู้ใช้งาน</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f9fb;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin: 0;
            padding: 30px;
        }
        .content {
            margin-top: 30px;
            margin-left: 220px;
            padding: 20px;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        form {
            width: 100%;
            max-width: 600px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }
        .form-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            width: 100%;
        }
        label {
            font-size: 16px;
            color: #333;
            margin-right: 10px;
            white-space: nowrap;
            width: 150px;
        }
        input[type="text"], input[type="password"], input[type="email"], textarea {
            width: calc(100% - 160px);
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border-color 0.3s;
        }
        input[type="submit"] {
            background-color: #fe2d85;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 20px;
        }
        input[type="submit"]:hover {
            background-color: #e5005f;
        }
    </style>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="content">
    
        <form method="post" action="">
        <h2>เพิ่มบัญชีผู้ใช้งาน</h2>
            <div class="form-group">
                <label for="user_id">ชื่อผู้ใช้งาน:<span style="color: red;">*</span></label>
                <input type="text" id="user_id" name="user_id" placeholder="ชื่อผู้ใช้งาน" required>
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน:<span style="color: red;">*</span></label>
                <input type="text" id="password" name="password" placeholder="รหัสผ่าน" required>
            </div>

            <div class="form-group">
                <label for="first_name">ชื่อจริง:<span style="color: red;">*</span></label>
                <input type="text" id="first_name" name="first_name" placeholder="ชื่อจริง" required>
            </div>

            <div class="form-group">
                <label for="last_name">นามสกุล:<span style="color: red;">*</span></label>
                <input type="text" id="last_name" name="last_name" placeholder="นามสกุล" required>
            </div>

            <div class="form-group">
                <label for="email">อีเมล:<span style="color: red;">*</span></label>
                <input type="email" id="email" name="email" placeholder="อีเมล" required>
            </div>

            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์:<span style="color: red;">*</span></label>
                <input type="text" id="phone" name="phone" placeholder="เบอร์โทรศัพท์" required>
            </div>

            <div class="form-group">
                <label for="user_address">ที่อยู่ผู้ใช้งาน:<span style="color: red;">*</span></label>
                <textarea id="user_address" name="user_address" rows="6" placeholder="ที่อยู่ผู้ใช้งาน" required></textarea>
            </div>

            <input type="submit" value="สร้างบัญชีผู้ใช้งาน">
        </form>
    </div>
</body>
</html>