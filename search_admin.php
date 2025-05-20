<?php
include('auth.php');
include('condb.php');

$search_term = '';
$user_id = '';

if (isset($_GET['search_term'])) {
    $search_term = $_GET['search_term'];
} elseif (isset($_POST['search_term'])) {
    $search_term = $_POST['search_term'];
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
} elseif (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
}

$users = [];
if (!empty($search_term) || !empty($user_id)) {
    $sql = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, email, phone, user_address FROM user WHERE (user_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    
    if (!empty($user_id)) {
        $sql = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name, email, phone, user_address FROM user WHERE user_id = ?";
    }

    if ($stmt = $conn->prepare($sql)) {
        if (!empty($user_id)) {
            $stmt->bind_param("s", $user_id);
        } else {
            $like_term = "%$search_term%";
            $stmt->bind_param("sssss", $like_term, $like_term, $like_term, $like_term, $like_term);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $check_sql = "SELECT * FROM contract WHERE user_id = ?";
    if ($check_stmt = $conn->prepare($check_sql)) {
        $check_stmt->bind_param("s", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            session_start();
            $_SESSION['error_message'] = 'ไม่สามารถลบผู้ใช้งานที่มีสัญญาได้';
            header("Location: search_admin.php?search_term=$search_term");
            exit;
        } else {
            $delete_sql = "DELETE FROM user WHERE user_id = ?";
            if ($delete_stmt = $conn->prepare($delete_sql)) {
                $delete_stmt->bind_param("s", $delete_id);
                if ($delete_stmt->execute()) {
                    $params = http_build_query([
                        'search_term' => $search_term,
                        'user_id' => $user_id
                    ]);
                    echo "<script>
                        alert('ลบผู้ใช้งานเรียบร้อยแล้ว');
                        window.location.href = 'search_admin.php?$params';
                        </script>";
                    exit;
                } else {
                    echo "<script>alert('ไม่สามารถลบผู้ใช้งานได้'); window.history.back();</script>";
                    exit;
                }
                $delete_stmt->close();
            }
        }
        $check_stmt->close();
    }
}
if (isset($_SESSION['error_message'])) {
    echo "<script>alert('" . $_SESSION['error_message'] . "');</script>";
    unset($_SESSION['error_message']);
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาผู้ใช้งาน</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            max-width: 1500px;
            margin: 80px;
            margin-left: 300px;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #17202a;
            height: fit-content;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #e8daef;
        }
        td {
            padding: 15px;
        }
        tr:nth-child(even) {
            background-color: #ffffff;
        }
        tr:nth-child(odd) {
            background-color: #f5eef8;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        img {
            max-width: 150px;
            height: auto;
        }
        input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 63%;
            margin-right: 5px;
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
        .delete-btn {
            background-color: #d9534f;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }
        .edit-btn {
            background-color: #337ab7;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .edit-btn:hover {
            background-color: #286090;
        }
    </style>
</head>
<body>
    <?php include('admin_sidebar.php'); ?>
    <div class="container">
        <h2>ค้นหาผู้ใช้งาน</h2>
        <form method="post" action="search_admin.php">
            <label for="search_term">ค้นหา (ชื่อผู้ใช้งานระบบ, ชื่อจริง, นามสกุล, อีเมล์, เบอร์โทรศัพท์):</label>
            <input type="text" name="search_term" id="search_term" required value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit">ค้นหา</button>
        </form>

        <?php if (!empty($users)): ?>
            <table>
                <tr>
                    <th>User ID</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>อีเมล</th>
                    <th>เบอร์โทร</th>
                    <th>ที่อยู่</th>
                    <th></th>
                </tr>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo $user['full_name']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['phone']; ?></td>
                    <td><?php echo $user['user_address']; ?></td>
                    <td>
                        <a href="edit_admin.php?user_id=<?php echo $user['user_id']; ?>&user_type=user" class="btn edit-btn">แก้ไข</a> &nbsp;
                        <a href="search_admin.php?delete_id=<?php echo $user['user_id']; ?>&search_term=<?php echo urlencode($search_term); ?>" 
                        onclick="return confirm('คุณแน่ใจว่าต้องการลบผู้ใช้นี้?');" 
                        class="delete-btn">ลบ</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <p>ไม่พบผลลัพธ์ที่ตรงกับการค้นหา</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>
