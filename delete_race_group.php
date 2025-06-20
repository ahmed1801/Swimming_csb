<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['race_group_id'])) {
    $group_id = intval($_POST['race_group_id']);

    try {
        // حذف من جدول relay_results إن وجد
        $pdo->prepare("DELETE FROM relay_results WHERE race_group_id = ?")->execute([$group_id]);

        // حذف من جدول results
        $pdo->prepare("DELETE FROM results WHERE race_group_id = ?")->execute([$group_id]);

        // حذف من جدول races
        $pdo->prepare("DELETE FROM races WHERE race_group_id = ?")->execute([$group_id]);

        // حذف من جدول race_groups
        $pdo->prepare("DELETE FROM race_groups WHERE id = ?")->execute([$group_id]);

        $_SESSION['success'] = "✅ تم حذف السباق بنجاح.";
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ حدث خطأ أثناء الحذف: " . $e->getMessage();
    }
}

header("Location: all_races.php");
exit;
