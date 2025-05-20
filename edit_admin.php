<?php 
include('auth.php'); 
include('condb.php');

$user_type = isset($_GET['user_type']) ? $_GET['user_type'] : '';
$selected_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$user = null; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['user_type'])) {
        $user_type = $_POST['user_type'];
        if (isset($_POST['user_select'])) {
            $selected_user_id = $_POST['user_select'];
            $sql = $user_type === 'admin' ? "SELECT * FROM admin WHERE admin_id = ?" : "SELECT * FROM user WHERE user_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $selected_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
            }
        } else {
            $selected_user_id = '';
        }
    }   
}

if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    if ($user_type == 'admin') {
        $sql = "UPDATE admin SET password = ?, email = ? WHERE admin_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sss", $password, $email, $user_id);
            if ($stmt->execute()) {
                echo "<script>
                        alert('ข้อมูลถูกอัปเดตเรียบร้อยแล้ว');
                        window.location.href = 'edit_admin.php?user_type=$user_type&user_id=$user_id&auto_select=true';
                      </script>";
                exit();
            } else {
                echo "<script>alert('เกิดข้อผิดพลาด: " . $stmt->error . "');</script>";
            }                
            $stmt->close();
        }
    } elseif ($user_type == 'user') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $user_address = $_POST['user_address'];
        $sql = "UPDATE user SET password = ?, email = ?, first_name = ?, last_name = ?, phone = ?, user_address = ? WHERE user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssss", $password, $email, $first_name, $last_name, $phone, $user_address, $user_id);
            if ($stmt->execute()) {
                echo "<script>
                        alert('ข้อมูลถูกอัปเดตเรียบร้อยแล้ว');
                        window.location.href = 'search_admin.php?user_type=$user_type&user_id=$user_id&auto_select=true';
                      </script>";
                exit();
            } else {
                echo "<script>alert('เกิดข้อผิดพลาด: " . $stmt->error . "');</script>";
            }                
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
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
        input[type="text"],  input[type="email"], select, textarea {
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
        h2 {
            text-align: center;
        }
        </style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขบัญชีผู้ใช้</title>
    <script>
        function updateUserSelect() {
            const userType = document.getElementById('user_type').value;
            const userSelect = document.getElementById('user_select');
            userSelect.innerHTML = '';

            if (userType === 'admin') {
                userSelect.innerHTML += '<option value="">-- เลือกผู้ดูแลระบบ --</option>';
                <?php
                $sql = "SELECT admin_id FROM admin";
                if ($result = $conn->query($sql)) {
                    while ($row = $result->fetch_assoc()) {
                        echo "userSelect.innerHTML += '<option value=\"{$row['admin_id']}\" " . (isset($selected_user_id) && $selected_user_id == $row['admin_id'] ? "selected" : "") . ">{$row['admin_id']}</option>'; ";
                    }
                }
                ?>
            } else if (userType === 'user') {
                userSelect.innerHTML += '<option value="">-- เลือกผู้ใช้งาน --</option>';
                <?php
                $sql = "SELECT user_id FROM user";
                if ($result = $conn->query($sql)) {
                    while ($row = $result->fetch_assoc()) {
                        echo "userSelect.innerHTML += '<option value=\"{$row['user_id']}\" " . (isset($selected_user_id) && $selected_user_id == $row['user_id'] ? "selected" : "") . ">{$row['user_id']}</option>'; ";
                    }
                }
                ?>
            }
        }
    </script>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="content">
    <h2>แก้ไขข้อมูลผู้ใช้งาน</h2>
        <form method="post" action="" id="selectUserForm">
            <div class="form-group">
                <label for="user_type">เลือกประเภทผู้ใช้งาน:<span style="color: red;">*</span></label>
                <select id="user_type" name="user_type" required onchange="updateUserSelect()">
                    <option value="">-- เลือกประเภทผู้ใช้งาน --</option>
                    <option value="admin" <?php if ($user_type == 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="user" <?php if ($user_type == 'user') echo 'selected'; ?>>User</option>
                </select>
            </div>

            <div class="form-group">
                <label for="user_select">เลือกผู้ใช้งาน:<span style="color: red;">*</span></label>
                <select id="user_select" name="user_select" required>
                    <option value="">-- เลือกผู้ใช้งาน --</option>
                    <?php
                    if (!empty($user_type)) {
                        if ($user_type == 'admin') {
                            $sql = "SELECT admin_id FROM admin";
                            if ($result = $conn->query($sql)) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['admin_id']}' " . (isset($selected_user_id) && $selected_user_id == $row['admin_id'] ? "selected" : "") . ">{$row['admin_id']}</option>";
                                }
                            }
                        } elseif ($user_type == 'user') {
                            $sql = "SELECT user_id FROM user";
                            if ($result = $conn->query($sql)) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['user_id']}' " . (isset($selected_user_id) && $selected_user_id == $row['user_id'] ? "selected" : "") . ">{$row['user_id']}</option>";
                                }
                            }
                        }
                    }
                    ?>
                </select>
            </div>
            <input type="submit" value="เลือกผู้ใช้งาน">
        </form>

        <?php if ($user): ?>
            <form method="post" action="">
                <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">
                <input type="hidden" name="user_id" value="<?php echo $user[$user_type == 'admin' ? 'admin_id' : 'user_id']; ?>">

                <div class="form-group">
                    <label for="password">รหัสผ่าน:<span style="color: red;">*</span></label>
                    <input type="text" name="password" value="<?php echo $user['password']; ?>" placeholder="รหัสผ่าน" required>
                </div>
                <div class="form-group">
                    <label for="email">อีเมล:<span style="color: red;">*</span></label>
                    <input type="email" name="email" value="<?php echo $user['email']; ?>" placeholder="อีเมล" required>
                </div>

                <?php if ($user_type == 'user'): ?>
                    <div class="form-group">
                        <label for="first_name">ชื่อ:<span style="color: red;">*</span></label>
                        <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" placeholder="ชื่อจริง" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">นามสกุล:<span style="color: red;">*</span></label>
                        <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" placeholder="นามสกุล" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">เบอร์โทร:<span style="color: red;">*</span></label>
                        <input type="text" name="phone" value="<?php echo $user['phone']; ?>" placeholder="เบอร์โทรศัพท์" required>
                    </div>
                    <div class="form-group">
                        <label for="user_address">ที่อยู่ผู้ใช้งาน:<span style="color: red;">*</span></label>
                        <textarea name="user_address" required placeholder="ที่อยู่ผู้ใช้งาน"><?php echo $user['user_address']; ?></textarea>
                    </div>
                <?php endif; ?>

                <input type="submit" name="update_user" value="อัปเดตข้อมูล">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
