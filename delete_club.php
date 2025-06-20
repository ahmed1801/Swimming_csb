<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    // حذف السباحين المرتبطين أولاً لتفادي الخطأ (إذا لم يكن ON DELETE SET NULL)
    $stmt1 = $pdo->prepare("UPDATE swimmers SET club_id = NULL, club_name = '' WHERE club_id = ?");
    $stmt1->execute([$id]);

    // حذف النادي
    $stmt = $pdo->prepare("DELETE FROM clubs WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "✅ تم حذف النادي بنجاح.";
}

header("Location: add_club.php");
exit;
