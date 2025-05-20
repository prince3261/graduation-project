<?php
$font_dir = 'path/to/fonts'; // เปลี่ยนเป็น path ของโฟลเดอร์ที่เก็บฟอนต์
$files = scandir($font_dir);

foreach ($files as $file) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);

    // ตรวจสอบว่าเป็นไฟล์ฟอนต์หรือไม่ (นามสกุล .ttf หรือ .otf)
    if (in_array($ext, ['ttf', 'otf'])) {
        $font_file = $font_dir . '/' . $file;
        echo "กำลังเพิ่มฟอนต์: $font_file\n";
        
        // เรียกใช้คำสั่งในการเพิ่มฟอนต์
        $cmd = "php vendor/tecnickcom/tcpdf/tools/tcpdf_addfont.php -i $font_file";
        shell_exec($cmd); // ใช้ shell_exec ในการรันคำสั่ง
    }
}
