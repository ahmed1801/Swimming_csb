<?php
require 'db.php';
$group_id = $_GET['group_id'] ?? 0;

$stmt = $pdo->prepare("SELECT r.*, s.name AS swimmer_name, s.club_name, c.logo, g.created_at, g.swim_type, g.distance, g.age_category, g.gender, g.is_relay
FROM races r
JOIN swimmers s ON r.swimmer_id = s.id
LEFT JOIN clubs c ON s.club_id = c.id
JOIN race_groups g ON r.race_group_id = g.id
WHERE r.race_group_id = ?
ORDER BY r.id");
$stmt->execute([$group_id]);
$rows = $stmt->fetchAll();
if (!$rows) die("لا توجد بيانات.");

$meta = $rows[0];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة سباق</title>
    <style>
        body { font-family: 'Arial'; padding: 30px; direction: rtl; }
        h2 { text-align: center; }
        .meta { margin: 15px 0; font-weight: bold; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #007bff; color: white; }
        img { height: 40px; }
    </style>
</head>
<body onload="window.print()">

<h2>تقرير سباق</h2>
<p>الرابطة الولائية للسباحة</p>
<div class="meta">
    🗓️ <?= date('Y-m-d', strtotime($meta['created_at'])) ?> |
    🕐 <?= date('H:i:s', strtotime($meta['created_at'])) ?> |
    🏊 <?= $meta['swim_type'] ?> |
    📏 <?= $meta['distance'] ?> |
    👥 <?= $meta['age_category'] ?> |
    ⚧ <?= $meta['gender'] ?> |
    🔁 <?= $meta['is_relay'] ? 'تتابع' : 'فردي' ?>
</div>

<table>
    <thead>
        <tr>
            <th>الرقم</th>
            <th>اسم السباح</th>
            <th>النادي</th>
            <th>الملاحظة</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $i => $row): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($row['swimmer_name']) ?></td>
                <td>
                    <?= htmlspecialchars($row['club_name']) ?>
                    <?php if ($row['logo']): ?>
                        <img src="<?= $row['logo'] ?>" alt="Logo">
                    <?php endif; ?>
                    <td> </td>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
