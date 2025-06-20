<?php
require_once '../db.php';

$race_group_id = $_GET['group'] ?? 0;

$stmt = $pdo->prepare("
    SELECT 
        s.name AS swimmer_name,
        c.name AS club_name,
        c.logo AS club_logo,
        r.time_record,
        r.rank,
        r.note,
        g.swim_type,
        g.distance,
        g.age_category,
        g.gender,
        g.created_at
    FROM results r
    JOIN swimmers s ON r.swimmer_id = s.id
    JOIN race_groups g ON r.race_group_id = g.id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE r.race_group_id = ?
    ORDER BY r.rank ASC
");
$stmt->execute([$race_group_id]);
$results = $stmt->fetchAll();

if (!$results) {
    die("❌ لا توجد نتائج لعرضها.");
}

$meta = $results[0];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>🖨️ طباعة نتائج السباق</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            padding: 40px;
            color: #333;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .race-meta {
            text-align: center;
            margin-bottom: 20px;
            font-size: 15px;
            background: #f1f1f1;
            padding: 10px;
            border-radius: 6px;
            line-height: 2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #eee;
        }
        img.medal {
            width: 20px;
            vertical-align: middle;
        }
        img.logo {
            height: 24px;
            vertical-align: middle;
            margin-left: 5px;
        }

        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>

<h2>نتائج السباق</h2>

<div class="race-meta">
    🗓️ <strong>التاريخ:</strong> <?= date('Y-m-d', strtotime($meta['created_at'])) ?><br>
    ⏰ <strong>الوقت:</strong> <?= date('H:i:s', strtotime($meta['created_at'])) ?><br>
    🏊‍♂️ <strong>نوع السباحة:</strong> <?= htmlspecialchars($meta['swim_type']) ?> |
    📏 <strong>المسافة:</strong> <?= htmlspecialchars($meta['distance']) ?> |
    👥 <strong>الفئة:</strong> <?= htmlspecialchars($meta['age_category']) ?> |
    ⚧ <strong>الجنس:</strong> <?= htmlspecialchars($meta['gender']) ?>
</div>

<table>
    <thead>
        <tr>
            <th>الترتيب</th>
            <th>السباح</th>
            <th>النادي</th>
            <th>الوقت</th>
            <th>ملاحظة</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($results as $row): 
            $medal = '';
            switch ($row['rank']) {
                case 1:
                    $medal = '<img src="../assets/medals/gold.png" class="medal">';
                    break;
                case 2:
                    $medal = '<img src="../assets/medals/silver.png" class="medal">';
                    break;
                case 3:
                    $medal = '<img src="../assets/medals/bronze.png" class="medal">';
                    break;
            }

            $clubLogo = ($row['club_logo'] && file_exists('../' . $row['club_logo'])) 
                ? '<img src="../' . $row['club_logo'] . '" class="logo">' 
                : '';
        ?>
        <tr>
            <td><?= $row['rank'] . ' ' . $medal ?></td>
            <td><?= htmlspecialchars($row['swimmer_name']) ?></td>
            <td><?= $clubLogo . htmlspecialchars($row['club_name']) ?></td>
            <td><?= htmlspecialchars($row['time_record']) ?></td>
            <td><?= htmlspecialchars($row['note']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<br>
<div style="text-align: center;">
    <button onclick="window.print()">🖨️ طباعة</button>
    <a href="../results/view_results.php?highlight=<?= $race_group_id ?>" style="margin-right: 15px;">🔙 العودة</a>
</div>

</body>
</html>
