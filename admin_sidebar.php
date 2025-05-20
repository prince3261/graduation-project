<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar</title>
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
        function toggleDropdown(dropdownId, element) {
            const dropdown = document.getElementById(dropdownId);
            const isVisible = dropdown.style.display === "block";
            dropdown.style.display = isVisible ? "none" : "block";
            localStorage.setItem(dropdownId, !isVisible ? "open" : "closed");
            element.querySelector('.symbol').textContent = isVisible ? "►" : "▼";
        }

        function activateLink(linkElement) {
            document.querySelectorAll('.sidebar a, .dropdown a').forEach(link => link.classList.remove('active'));
            linkElement.classList.add('active');
            localStorage.setItem('activeLink', linkElement.getAttribute('href'));
        }

        function loadStates() {
            const activeLink = localStorage.getItem('activeLink');
            if (activeLink) {
                const linkToActivate = document.querySelector(`a[href="${activeLink}"]`);
                if (linkToActivate) linkToActivate.classList.add('active');
            }

            document.querySelectorAll('.dropdown-btn').forEach(button => {
                const dropdownId = button.getAttribute('onclick').match(/'([^']+)'/)[1];
                const dropdown = document.getElementById(dropdownId);
                const state = localStorage.getItem(dropdownId);
                if (state === "open") {
                    dropdown.style.display = "block";
                    button.querySelector('.symbol').textContent = "▼";
                }
            });
        }

        function clearStorageAndLogout() {
            localStorage.clear();
            window.location.href = 'logout.php';
        }

        window.addEventListener('load', loadStates);
    </script>
</head>
<body>
    <div class="banner">
        <a href="admin_home.php" class="home" onclick="activateLink(this)">
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
            <a href="javascript:void(0);" class="dropdown-btn" onclick="toggleDropdown('userDropdown', this)">จัดการบัญชีผู้ใช้งาน <span class="symbol">►</span></a>
            <div id="userDropdown" class="dropdown">
                <a href="register.php" onclick="activateLink(this)">เพิ่มบัญชีผู้ใช้งาน</a>
                <a href="search_admin.php" onclick="activateLink(this)">ค้นหาบัญชีผู้ใช้งาน</a>
                <a href="edit_admin.php" onclick="activateLink(this)">แก้ไขบัญชีผู้ใช้งาน</a>
            </div>

            <a href="javascript:void(0);" class="dropdown-btn" onclick="toggleDropdown('contractDropdown', this)">จัดการเอกสารสัญญา <span class="symbol">►</span></a>
            <div id="contractDropdown" class="dropdown">    
                <a href="add_contract.php" onclick="activateLink(this)">เพิ่มเอกสารสัญญา</a>
                <a href="search_contract.php" onclick="activateLink(this)">ค้นหาเอกสารสัญญา</a>
                <a href="edit_contract.php" onclick="activateLink(this)">แก้ไขเอกสารสัญญา</a>
            </div>

            <a href="javascript:void(0);" class="dropdown-btn" onclick="toggleDropdown('roomDropdown', this)">จัดการอุปกรณ์ห้องพัก <span class="symbol">►</span></a>
            <div id="roomDropdown" class="dropdown">
                <a href="add_equipment.php" onclick="activateLink(this)">เพิ่ม/แก้ไขอุปกรณ์ห้องพัก</a>
                <a href="search_equipment.php" onclick="activateLink(this)">ค้นหาอุปกรณ์ห้องพัก</a>
            </div>

            <a href="javascript:void(0);" class="dropdown-btn" onclick="toggleDropdown('meterDropdown', this)">จัดการมิเตอร์ <span class="symbol">►</span></a>
            <div id="meterDropdown" class="dropdown">
                <a href="add_meter.php" onclick="activateLink(this)">เพิ่มมิเตอร์</a>
                <a href="search_meter.php" onclick="activateLink(this)">ค้นหามิเตอร์</a>
                <a href="edit_meter.php" onclick="activateLink(this)">แก้ไขมิเตอร์</a>
            </div>

            <a href="javascript:void(0);" class="dropdown-btn" onclick="toggleDropdown('invoiceDropdown', this)">จัดการใบแจ้งหนี้และใบเสร็จ <span class="symbol">►</span></a>
            <div id="invoiceDropdown" class="dropdown">
                <a href="add_invoice.php" onclick="activateLink(this)">เพิ่มใบแจ้งหนี้</a>
                <a href="search_invoice.php" onclick="activateLink(this)">ค้นหาใบแจ้งหนี้และใบเสร็จ</a>
                <a href="edit_invoice.php" onclick="activateLink(this)">แก้ไขใบแจ้งหนี้และใบเสร็จ</a>
            </div>

            <a href="income_report.php" onclick="activateLink(this)">สร้างรายงานสรุปรายได้</a>

            <a href="javascript:void(0);" class="dropdown-btn" onclick="toggleDropdown('scheduleDropdown', this)">จัดการนัดหมาย <span class="symbol">►</span></a>
            <div id="scheduleDropdown" class="dropdown">
                <a href="add_schedule.php" onclick="activateLink(this)">เพิ่ม/แก้ไข/ลบตารางเวลา</a>
                <a href="admin_appointment.php" onclick="activateLink(this)">เพิ่มการนัดหมาย</a>
                <a href="search_appointment.php" onclick="activateLink(this)">ค้นหาการนัดหมาย</a>
                <a href="edit_appointment.php" onclick="activateLink(this)">แก้ไขการนัดหมาย</a>
            </div>

            <a href="javascript:void(0);" class="dropdown-btn" onclick="toggleDropdown('parcelDropdown', this)">จัดการพัสดุ <span class="symbol">►</span></a>
            <div id="parcelDropdown" class="dropdown">
                <a href="add_parcel.php" onclick="activateLink(this)">เพิ่ม/แก้ไขพัสดุ</a>
                <a href="search_parcel.php" onclick="activateLink(this)">ค้นหาพัสดุ</a>
            </div>

        </div>
        <a href="javascript:void(0);" class="logout" onclick="clearStorageAndLogout()">ออกจากระบบ</a>
    </div>
</body>
</html>