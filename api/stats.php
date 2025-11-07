<?php
// ไฟล์: api/stats.php
// ดึงข้อมูลสถิติจาก DB

// 1. ตั้งค่า Header ว่าจะส่ง JSON
header('Content-Type: application/json');

// 2. เชื่อมต่อ DB
require_once __DIR__ . '/../db-config.php';

try {
    // 3. ดึงข้อมูลสถิติ
    $sql = "SELECT 
                page_name, 
                COUNT(*) as views
            FROM page_views 
            GROUP BY page_name 
            ORDER BY views DESC";
    
    $stmt = $pdo->query($sql);
    $stats = $stmt->fetchAll();

    // 4. ส่งผลลัพธ์กลับเป็น JSON
    echo json_encode($stats);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve stats.', 'message' => $e->getMessage()]);
}
?>