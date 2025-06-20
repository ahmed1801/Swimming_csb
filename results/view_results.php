<?php
require '../db.php';

$race_group_id = $_GET['highlight'] ?? null;

if (!$race_group_id) {
    die("❌ معرف السباق غير موجود.");
}

// جلب بيانات السباق
$stmt = $pdo->prepare("
    SELECT 
        rg.swim_type, rg.distance, rg.age_category, rg.gender, rg.created_at
    FROM race_groups rg
    WHERE rg.id = ?
");
$stmt->execute([$race_group_id]);
$race = $stmt->fetch();

if (!$race) {
    die("❌ لم يتم العثور على بيانات هذا السباق.");
}

// جلب النتائج الخاصة بهذا السباق
$stmt = $pdo->prepare("
    SELECT 
        s.name AS swimmer_name,
        s.club_name,
        c.logo AS club_logo,
        res.time_record,
        res.rank,
        res.note
    FROM results res
    JOIN swimmers s ON res.swimmer_id = s.id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE res.race_group_id = ?
    ORDER BY res.rank ASC
");
$stmt->execute([$race_group_id]);
$results = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نتائج السباق</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            font-family: 'Arial';
            padding: 20px;
            background: #f9f9f9;
            direction: rtl;
        }
        h2, .race-info {
            text-align: center;
        }
        .race-info {
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
            background: #eef;
            padding: 10px;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background: #333;
            color: white;
        }
        .club-logo {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            vertical-align: middle;
            margin-left: 5px;
        }
        .btn {
    display: inline-block;
    padding: 8px 16px;
    font-size: 15px;
    font-weight: bold;
    text-decoration: none;
    border-radius: 6px;
    margin: 5px;
    color: #fff;
}

.btn.back {
    background-color: #6c757d;
}

.btn.pdf {
    background-color: #007bff;
}

.btn:hover {
    opacity: 0.9;
}

        .btn-print {
            background: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-print:hover {
            background-color: #0056b3;
        }
        .no-data {
            background: #ffeeba;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

    </style>
</head>
<body>

<!-- شريط التنقل -->
<nav class="navbar">
    <ul>
        <li><a href="../index.php">🏠 الصفحة الرئيسية</a></li>
        <li><a href="../all_races.php">📋 جميع السباقات</a></li>
        <li><a href="../prepare_race.php">➕ إعداد سباق</a></li>
    </ul>
</nav>

<h2>📊 نتائج السباق</h2>


<div class="race-info">
    📅 <?= date('Y-m-d', strtotime($race['created_at'])) ?> |
    🏊 <?= htmlspecialchars($race['swim_type']) ?> |
    📏 <?= htmlspecialchars($race['distance']) ?> |
    👥 <?= htmlspecialchars($race['age_category']) ?> |
    ⚧ <?= htmlspecialchars($race['gender']) ?>
</div>

<?php if (count($results)): ?>
    
    <div class="result-actions" style="margin-bottom: 20px; display: flex; justify-content: space-between; flex-wrap: wrap;">
    <a href="../all_races.php" class="btn back">🔙 العودة لكل السباقات</a>
    
    <?php if ($race_group_id): ?>
        <a href="export_result_pdf.php?group=<?= $race_group_id ?>" class="btn pdf" target="_blank">🖨️ حفظ PDF</a>
        
    <?php endif; ?>
</div>


    <table>
        <thead>
            <tr>
                <th>🏆 المرتبة</th>
                <th>اسم السباح</th>
                <th>النادي</th>
                <th>⏱️ الوقت</th>
                <th>📌 ملاحظة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $res): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($res['rank']) ?>
                        <?php
                        if ($res['rank'] == 1) echo " 🥇";
                        elseif ($res['rank'] == 2) echo " 🥈";
                        elseif ($res['rank'] == 3) echo " 🥉";
                        ?>
                    </td>
                    <td><?= htmlspecialchars($res['swimmer_name']) ?></td>
                    <td>
                        <?php if (!empty($res['club_logo'])): ?>
                            <img src="../<?= $res['club_logo'] ?>" class="club-logo" alt="logo">
                        <?php endif; ?>
                        <?= htmlspecialchars($res['club_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($res['time_record']) ?></td>
                    <td><?= htmlspecialchars($res['note']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php else: ?>
    <div class="no-data">❌ لا توجد نتائج لهذا السباق.</div>
<?php endif; ?>

</body>
</html>
