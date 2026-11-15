<?php
// คัดลอกไฟล์นี้เป็น config/config.php แล้วแก้ค่าให้ตรงกับเครื่องจริง (config.php ถูก ignore จาก git แล้ว)
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'edoc_cooperative');
define('APP_URL',       'http://localhost/edoc-cooperative');
define('APP_NAME',      'ระบบนำส่งเอกสารอิเล็กทรอนิกส์');
define('UPLOAD_DIR',    dirname(__FILE__) . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('CSRF_TOKEN_NAME', 'csrf_token');
define('HQ_OFFICE', 'สำนักงานตรวจบัญชีสหกรณ์ที่ 5');
// ถ้ารันบน Windows ภาษาไทยแล้วชื่อไฟล์ในโฟลเดอร์ uploads แสดงเป็นตัวอักษรเพี้ยน ให้เอา comment บรรทัดล่างออก
// define('UPLOAD_FS_CHARSET', 'CP874');

// ---- ค่าของระบบค้นหารูปด้วยใบหน้า (ไม่ตั้งก็ได้) ----
// ระยะ Euclidean สูงสุดที่ถือว่าเป็นคนเดียวกัน ค่าเริ่มต้น 0.45 (เข้มงวดที่สุด) เพิ่มค่าถ้าหาไม่เจอ ค่ามาตรฐานของ face-api คือ 0.6
// define('FACE_DISTANCE_THRESHOLD', 0.45);
// จำนวนผลลัพธ์สูงสุดต่อการค้นหา ค่าเริ่มต้น 100
// define('FACE_MAX_RESULTS', 100);
