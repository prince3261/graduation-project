<?php
session_start();
include('condb.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['กลุ่มผู้ใช้งาน'];

    if ($user_type == "ผู้เช่า") {
        $sql = "SELECT * FROM user WHERE user_id = ?";
    } elseif ($user_type == "เจ้าของหอพัก") {
        $sql = "SELECT * FROM admin WHERE admin_id = ?";
    }
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($user_type == "ผู้เช่า" && $row['password'] === $password) {
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = "ผู้เช่า";
                echo "<script>alert('เข้าสู่ระบบสำเร็จ (ผู้เช่า)');</script>";
                header("Location: user_home.php");
                exit();
            } elseif ($user_type == "เจ้าของหอพัก" && $row['password'] === $password) {
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = "เจ้าของหอพัก";
                echo "<script>alert('เข้าสู่ระบบสำเร็จ (เจ้าของหอพัก)');</script>";
                header("Location: admin_home.php");
                exit();
            } else {
                echo "<script>alert('ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'); window.location.href='index.php';</script>";
            }
        } else {
            echo "<script>alert('ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง'); window.location.href='index.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล');</script>";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - หอพักสตรี SPK</title>
    <link rel="stylesheet" href="style.css">
        <style>
            body {
                background-color: #ffc0cb;
                font-family: Arial, sans-serif;
                font-weight: bold;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                text-align: center;
            }

            .icon {
                width: 20%;
                height: auto;
            }

            h1 {
                color: #333;
                font-size: 24px;
                margin-bottom: 20px;
            }

            form {
                width: 100%;
                max-width: 300px;
            }

            label {
                font-size: 14px;
                color: #333;
                display: block;
                text-align: left;
                margin-top: 10px;
            }

            input[type="text"], input[type="password"] {
                width: 100%;
                padding: 10px;
                margin: 8px 0;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-sizing: border-box;
            }

            .user-type {
                display: flex;
                justify-content: center;
                margin: 10px 0;
                gap: 10px;
            }

            .user-type input[type="radio"] {
                margin: 12px 5px;
            }

            .login-button {
                width: 100%;
                padding: 10px;
                background-color: #ff69b4;
                color: #fff;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
            }

            .login-button:hover {
                background-color: #ff4da6;
            }

        </style>
</head>
<body>
    <img src="pic\index_pic.png" alt="House Icon" class="icon">
    <h1>หอพักสตรี SPK</h1>
    
    <form action="" method="post">
        <div class="form-group">
        <label for="username">ชื่อผู้ใช้งาน:</label>
        <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้งาน" required>
        </div>

        <label for="password">รหัสผ่าน:</label>
        <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required>

        <div class="user-type">
            <input type="radio" id="tenant" name="กลุ่มผู้ใช้งาน" value="ผู้เช่า" required>
            <label for="tenant">ผู้เช่า</label>
            
            <input type="radio" id="admin" name="กลุ่มผู้ใช้งาน" value="เจ้าของหอพัก" required>
            <label for="admin">เจ้าของหอพัก</label>
        </div>

        <button type="submit" class="login-button">เข้าสู่ระบบ</button>
    </form>
</body>
</html>