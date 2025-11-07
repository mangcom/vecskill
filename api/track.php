<?php
// ไฟล์: api/track.php
// รับค่า POST, ดึง IP, และ INSERT ลง DB

// 1. ตรวจสอบว่าเป็น POST Request เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

// 2. รับข้อมูล JSON ที่ส่งมา
$data = json_decode(file_get_contents('php://input'), true);
$page = $data['page'] ?? null;

if (empty($page)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Page name is required.']);
    exit;
}

// 3. ดึง IP Address (โดยเคารพ Proxy เช่น Cloudflare/Apache)
$ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
}

// 4. เชื่อมต่อ DB (จากไฟล์ config)
require_once __DIR__ . '/../db-config.php'; // เรียกไฟล์ config ที่อยู่โฟลเดอร์บน
echo "ok";
try {
    // 5. บันทึก Log (ใช้ Prepared Statements เพื่อความปลอดภัย)
    $sql = "INSERT INTO page_views (page_name, ip_address) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$page, $ip]);

    // 6. ส่งผลลัพธ์กลับ
    http_response_code(201); // Created
    echo json_encode(['status' => 'OK', 'page' => $page, 'ip' => $ip]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to log page view.', 'message' => $e->getMessage()]);
}
?>