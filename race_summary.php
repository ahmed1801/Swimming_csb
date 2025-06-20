<?php
session_start();
require 'db.php';

if (!isset($_SESSION['last_race_group_id'])) {
    header("Location: prepare_race.php");
    exit;
}

$race_group_id = $_SESSION['last_race_group_id'];
unset($_SESSION['last_race_group_id']);

$stmt = $pdo->prepare("SELECT rg.*, r.is_relay, s.name AS swimmer_name, c.name AS club_name, c.logo
    FROM race_groups rg
    JOIN races r ON rg.id = r.race_group_id
    JOIN swimmers s ON r.swimmer_id = s.id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE rg.id = ?
    ORDER BY r.id");
$stmt->execute([$race_group_id]);
$rows = $stmt->fetchAll();

if (!$rows) {
    echo "❌ لم يتم العثور على السباق.";
    exit;
}

$meta = $rows[0];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ملخص السباق</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .summary-box { background: #fff; padding: 20px; margin: auto; max-width: 900px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        .race-meta { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: bold; }
        .club-logo { width: 30px; height: 30px; border-radius: 50%; vertical-align: middle; margin-left: 10px; }
    </style>
</head>
<body>
<div class="summary-box">
    <h2>📋 ملخص السباق</h2>
    <div class="race-meta">
        🏊‍♂️ <strong>نوع السباحة:</strong> <?= htmlspecialchars($meta['swim_type']) ?> |
        📏 <strong>المسافة:</strong> <?= htmlspecialchars($meta['distance']) ?> |
        👥 <strong>الفئة:</strong> <?= htmlspecialchars($meta['age_category']) ?> |
        ⚧ <strong>الجنس:</strong> <?= htmlspecialchars($meta['gender']) ?> |
        🔁 <strong>نوع السباق:</strong> <?= $meta['is_relay'] ? 'تتابع' : 'فردي' ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>الترتيب</th>
                <th>الاسم</th>
                <th>النادي</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $row): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($row['swimmer_name']) ?></td>
                <td>
                    <?php if ($row['logo']): ?>
                        <img src="<?= htmlspecialchars($row['logo']) ?>" class="club-logo">
                    <?php endif; ?>
                    <?= htmlspecialchars($row['club_name']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <br>
    <a href="prepare_race.php" class="add-new">🔁 إعداد سباق جديد</a>
    <a href="all_races.php" class="add-new" style="background:#6c757d;">📂 جميع السباقات</a>
</div>
</body>
</html>
