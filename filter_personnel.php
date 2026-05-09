<?php
// ============================================================
// AJAX/FILTER_PERSONNEL.PHP — Departmana Göre Personel Filtrele
// index.php'deki departman dropdown'u tarafından çağrılır.
// GET parametresi: dept_id (integer)
// Döndürür: JSON { dept_name, count, personnel: [...] }
// ============================================================
session_start();

// Oturum kontrolü — AJAX'tan gelse dahi kimlik doğrulama zorunlu
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

// Parametre doğrulama
$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;

if ($dept_id <= 0) {
    echo json_encode(['error' => 'Geçersiz departman ID.']);
    exit;
}

// Departman adını çek
$deptStmt = $conn->prepare("SELECT name FROM departments_ WHERE id = ?");
$deptStmt->bind_param('i', $dept_id);
$deptStmt->execute();
$deptRow = $deptStmt->get_result()->fetch_assoc();
$deptStmt->close();

if (!$deptRow) {
    echo json_encode(['error' => 'Departman bulunamadı.']);
    exit;
}

// Departmana ait personelleri getir
$stmt = $conn->prepare("
    SELECT p.id,
           p.f_name,
           p.l_name,
           p.email,
           p.hire_date,
           p.mnthly_hrs,
           d.name AS dept_name,
           TIMESTAMPDIFF(MONTH, p.hire_date, CURDATE()) AS kidem_ay,
           CASE
               WHEN p.mngr_id  IS NOT NULL THEN CONCAT('Yönetici (', m.mngmnt_lvl, ')')
               WHEN p.staff_id IS NOT NULL THEN CONCAT('Teknik (', ts.tech_skill, ')')
               WHEN p.other_id IS NOT NULL THEN CONCAT('Diğer (', o.note, ')')
               ELSE 'Belirsiz'
           END AS staff_type,
           CONCAT(mgr.f_name, ' ', mgr.l_name) AS manager_name
    FROM personnel_ p
    JOIN departments_      d   ON p.dept_id  = d.id
    LEFT JOIN manager_         m   ON p.mngr_id  = m.id
    LEFT JOIN technical_staff_ ts  ON p.staff_id = ts.id
    LEFT JOIN other_           o   ON p.other_id = o.id
    LEFT JOIN personnel_       mgr ON p.mgr_id   = mgr.id
    WHERE p.dept_id = ?
    ORDER BY p.f_name
");
$stmt->bind_param('i', $dept_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// manager_name boşluk kontrolü
foreach ($rows as &$row) {
    if (trim($row['manager_name']) === '') {
        $row['manager_name'] = null;
    }
}
unset($row);

echo json_encode([
    'dept_name' => $deptRow['name'],
    'count'     => count($rows),
    'personnel' => $rows,
], JSON_UNESCAPED_UNICODE);
