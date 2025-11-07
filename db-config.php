<?php
// ไฟล์: db-config.php
// เก็บข้อมูลการเชื่อมต่อฐานข้อมูล

define('DB_HOST', 'localhost');
define('DB_USER', 'apps');
define('DB_PASS', 'otF,9yllt4t8t;tF9'); // <-- ใส่รหัสผ่าน DB ของคุณที่นี่
define('DB_NAME', 'apps_vecskill');

try {
    // ใช้ PDO เพื่อการเชื่อมต่อที่ปลอดภัยและทันสมัย
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // หากเชื่อมต่อไม่ได้ ให้หยุดทำงานและส่ง Error
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}
?>