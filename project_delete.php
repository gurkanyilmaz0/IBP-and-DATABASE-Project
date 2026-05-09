<?php
// ============================================================
// PROJECT_DELETE.PHP — Proje Silme İşlemi
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Projeyi sil
    $stmt = $conn->prepare("DELETE FROM project_ WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        header('Location: projects.php?msg=deleted');
    } else {
        header('Location: projects.php?msg=error');
    }
    $stmt->close();
} else {
    header('Location: projects.php?msg=error');
}
$conn->close();
exit;
