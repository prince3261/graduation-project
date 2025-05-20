<?php 
session_start();
include('condb.php');

if (!isset($_SESSION['username']) || $_SESSION['user_type'] != "ผู้เช่า") {
    echo "<script>alert('กรุณาเข้าสู่ระบบ'); window.location.href='index.php';</script>";
    exit();
}

$user = null; 
$username = $_SESSION['username'];

$sql = "SELECT * FROM user WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $user_address = $_POST['user_address'];

    $sql_check_email = "SELECT user_id FROM user WHERE email = ? AND user_id != ?";
    if ($stmt_check = $conn->prepare($sql_check_email)) {
        $stmt_check->bind_param("ss", $email, $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo "<script>alert('อีเมลนี้มีอยู่ในระบบแล้ว กรุณาใช้อีเมลอื่น'); window.history.back();</script>";
            $stmt_check->close();
            exit();
        }
        $stmt_check->close();
    }

    $sql = "UPDATE user SET password = ?, first_name = ?, last_name = ?, email = ?, phone = ?, user_address = ? WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssss", $password, $first_name, $last_name, $email, $phone, $user_address, $username);
        if ($stmt->execute()) {
            echo "<script> alert('ข้อมูลถูกอัปเดตเรียบร้อยแล้ว'); window.location.href='edit_user.php'; </script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาด: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลส่วนตัว</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
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
        label {
            display: block;
            margin: 10px 0 5px;
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
        textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            height: 150px; 
        }
        input[type="text"], input[type="password"], input[type="email"], select, textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
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
    <?php include('user_sidebar.php'); ?>
    <div class="content">
        <?php if ($user): ?>
            <form method="post" action="">
            <h2>แก้ไขข้อมูลส่วนตัว</h2>
            
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้งาน:<span style="color: red;">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo $user['user_id']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="password">รหัสผ่าน:<span style="color: red;">*</span></label>
                    <input type="text" id="password" name="password" placeholder="รหัสผ่าน" value="<?php echo $user['password']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="first_name">ชื่อจริง:<span style="color: red;">*</span></label>
                    <input type="text" id="first_name" name="first_name" placeholder="ชื่อจริง" value="<?php echo $user['first_name']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">นามสกุล:<span style="color: red;">*</span></label>
                    <input type="text" id="last_name" name="last_name" placeholder="นามสกุล" value="<?php echo $user['last_name']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">อีเมล:<span style="color: red;">*</span></label>
                    <input type="email" id="email" name="email" placeholder="อีเมล" value="<?php echo $user['email']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">เบอร์โทรศัพท์:<span style="color: red;">*</span></label>
                    <input type="text" id="phone" name="phone" placeholder="เบอร์โทรศัพท์" value="<?php echo $user['phone']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="user_address">ที่อยู่ผู้ใช้งาน:<span style="color: red;">*</span></label>
                    <textarea id="user_address" name="user_address" required placeholder="ที่อยู่ผู้ใช้งาน"><?php echo $user['user_address']; ?></textarea>
                </div>

                <input type="submit" value="อัปเดตข้อมูล">
            </form>
        <?php else: ?>
            <p>ไม่พบข้อมูลผู้ใช้งาน</p>
        <?php endif; ?>
    </div>
</body>
</html>
