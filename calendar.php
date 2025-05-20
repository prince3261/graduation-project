<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินนัดหมาย</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
        }
        .calendar-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 80px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 1000px;
            height: 375px;
            margin-left: 30%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 600px;
            margin-bottom: 20px;
        }
        .calendar-header button {
            padding: 10px 20px;
            background-color: #fe2d85;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .calendar-header button:hover {
            background-color: #e5005f;
        }
        .calendar-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .calendar-table {
            width: 100%;
            max-width: 800px;
            border-collapse: collapse;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .calendar-table th, .calendar-table td {
            border: 1px solid #17202a;
            padding: 15px;
            text-align: center;
            font-size: 16px;
            transition: background-color 0.3s, color 0.3s;
        }
        .calendar-table th {
            background-color: #e8daef;
            color: black;
            font-weight: bold;
        }
        .calendar-table td {
            background-color: white;
        }
        .today {
            background-color: #ffeb3b;
            font-weight: bold;
            border-radius: 50%;
            color: black;
        }
        .selected {
            background-color: #81c784;
            font-weight: bold;
            border-radius: 50%;
            color: white;
        }
        .calendar-table td:hover {
            background-color: #ffcce1;
            color: #e5005f;
            cursor: pointer;
        }
        @media (max-width: 600px) {
            .calendar-header h2 {
                font-size: 20px;
            }
            .calendar-header button {
                padding: 8px 15px;
                font-size: 14px;
            }
            .calendar-table th, .calendar-table td {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
    <script>
        const monthNamesThai = [
            "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
            "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
        ];

        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let selectedDate = null; // วันที่ที่เลือก

        function generateCalendar(month, year) {
            const calendarBody = document.getElementById("calendarBody");
            const monthYear = document.getElementById("monthYear");

            // แปลงปี ค.ศ. เป็น พ.ศ.
            const thaiYear = year + 543;
            monthYear.textContent = `${monthNamesThai[month]} ${thaiYear}`;

            calendarBody.innerHTML = "";

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            let date = 1;
            for (let i = 0; i < 6; i++) {
                const row = document.createElement("tr");

                for (let j = 0; j < 7; j++) {
                    const cell = document.createElement("td");

                    if (i === 0 && j < firstDay) {
                        cell.textContent = "";
                    } else if (date > daysInMonth) {
                        cell.textContent = "";
                    } else {
                        cell.textContent = date;
                        const dateValue = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
                        cell.setAttribute("data-date", dateValue);

                        // ไฮไลต์วันที่ปัจจุบัน
                        const today = new Date();
                        if (
                            date === today.getDate() &&
                            month === today.getMonth() &&
                            year === today.getFullYear()
                        ) {
                            cell.classList.add("today");
                        }

                        // ไฮไลต์วันที่ที่เลือก
                        if (selectedDate === dateValue) {
                            cell.classList.add("selected");
                        }

                        cell.onclick = () => selectDate(dateValue);
                        date++;
                    }

                    row.appendChild(cell);
                }

                calendarBody.appendChild(row);

                if (date > daysInMonth) break;
            }
        }

        function selectDate(date) {
            selectedDate = date;

            // ส่งวันที่ที่เลือกไปยัง PHP
            const form = document.getElementById("dateForm");
            const dateInput = document.getElementById("selectedDate");
            dateInput.value = date;
            form.submit();
        }

        function changeMonth(step) {
            currentMonth += step;

            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            } else if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }

            generateCalendar(currentMonth, currentYear);
        }

        document.addEventListener("DOMContentLoaded", () => {
            generateCalendar(currentMonth, currentYear);
        });
    </script>
</head>
<body>
    <div class="calendar-container">
        <div class="calendar-header">
            <button onclick="changeMonth(-1)">ก่อนหน้า</button>
            <h2 id="monthYear"></h2>
            <button onclick="changeMonth(1)">ถัดไป</button>
        </div>
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>อา</th>
                    <th>จ</th>
                    <th>อ</th>
                    <th>พ</th>
                    <th>พฤ</th>
                    <th>ศ</th>
                    <th>ส</th>
                </tr>
            </thead>
            <tbody id="calendarBody"></tbody>
        </table>
    </div>

    <!-- ฟอร์มซ่อนสำหรับส่งวันที่ -->
    <form id="dateForm" method="POST" action="add_appointment.php">
        <input type="hidden" id="selectedDate" name="selected_date">
    </form>
</body>
</html>
