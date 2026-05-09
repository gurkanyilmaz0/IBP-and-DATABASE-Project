<?php
// personnel_delete.php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM personnel_ WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $conn->close();
    header('Location: index.php?msg=deleted');
} else {
    header('Location: index.php?msg=error');
}
exit;
