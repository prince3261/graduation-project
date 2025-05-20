<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sidebar</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #ffcce1;
        }
        .banner {
            width: 100%;
            background-color: #CE93D8;
            text-align: center;
            padding: 15px 0;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 4px 0px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-left: 20px;
            padding-right: 20px;
        }
        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 60px;
            left: 0;
            background-color: #ff99c3;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0px 15px rgba(0, 0, 0, 0.1);
            padding-bottom: 20px;
            overflow-y: auto;
        }
        .content {
            margin-left: 260px;
            padding-top: 80px;
            padding: 20px;
        }
        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 18px;
            color: black;
            display: block;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #ff65a5;
            text-decoration: underline;
        }
        .dropdown {
            display: none;
            background-color: #e1ccff;
            padding-left: 20px;
        }
        .dropdown a {
            padding: 10px;
            color: black;
            text-decoration: none;
            display: block;
        }
        .dropdown a:hover, .dropdown a.active {
            background-color: #c399ff;
        }
        .dropdown-btn {
            position: relative;
            padding-right: 30px;
        }
        .symbol {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        .logout {
            margin-top: auto;
            margin-bottom: 80px;
            text-align: center;
            color: white;
            text-decoration: none;
        }
        .logout:hover {
            text-decoration: underline;
        }
        .username {
            font-size: 16px;
            color: white;
            background-color: #333;
            padding: 5px 10px;
            border-radius: 5px;
            margin-right: 30px;
        }
        .home {
            text-decoration: none;
            color: black;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .icon {
            width: 35px;
            height: auto;
        }
    </style>
    <script>
        function activateLink(linkElement) {
            document.querySelectorAll('.sidebar a').forEach(link => link.classList.remove('active'));

            linkElement.classList.add('active');
            localStorage.setItem('activeUserLink', linkElement.getAttribute('href'));
        }

        function loadActiveLink() {
            const activeLink = localStorage.getItem('activeUserLink');
            if (activeLink) {
                const linkToActivate = document.querySelector(`a[href="${activeLink}"]`);
                if (linkToActivate) linkToActivate.classList.add('active');
            }
        }

        function clearStorageAndLogout() {
            localStorage.clear();
            window.location.href = 'logout.php';
        }

        window.addEventListener('load', loadActiveLink);
    </script>
</head>
<body>
    <div class="banner">
        <a href="user_home.php" class="home" onclick="activateLink(this)">
            <img src="pic\home.png" alt="House Icon" class="icon">HOME
        </a>
        หอพักสตรี SPK
        <span class="username">
            <?php
            echo 'ผู้ใช้งาน: ' . $_SESSION['username'];
            ?>
        </span>
    </div>

    <div class="sidebar">
        <div>
            <a href="edit_user.php" onclick="activateLink(this)">แก้ไขข้อมูลส่วนตัว</a>
            <a href="search_payment.php" onclick="activateLink(this)">ตรวจสอบการชำระเงิน</a>
            <a href="user_meter.php" onclick="activateLink(this)">ตรวจสอบประวัติมิเตอร์</a>
            <a href="add_appointment.php" onclick="activateLink(this)">นัดหมาย</a>
            <a href="user_appointment.php" onclick="activateLink(this)">ตรวจสอบการนัดหมาย</a>
        </div>
        <a href="javascript:void(0);" class="logout" onclick="clearStorageAndLogout()">ออกจากระบบ</a>
    </div>
</body>
</html>
