<?php
require 'db.php';

$swimmer_id = $_GET['id'] ?? null;

if (!$swimmer_id) {
    echo "<p>❌ معرف السباح غير صحيح.</p>";
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, c.name AS club_name, c.logo AS club_logo 
    FROM swimmers s 
    LEFT JOIN clubs c ON s.club_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$swimmer_id]);
$swimmer = $stmt->fetch();

if (!$swimmer) {
    echo "<p>❌ السباح غير موجود.</p>";
    exit;
}

$stmt = $pdo->prepare("
    SELECT rg.swim_type, rg.distance, rg.age_category, rg.gender, rg.created_at,
           res.time_record, res.rank, res.note
    FROM results res
    JOIN race_groups rg ON res.race_group_id = rg.id
    WHERE res.swimmer_id = ?
    ORDER BY rg.created_at DESC
");
$stmt->execute([$swimmer_id]);
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الملف الشخصي للسباح</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: 'Arial'; padding: 30px; background-color: #f0f0f0; color: #333; }
        .container { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px #ccc; max-width: 1000px; margin: auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .club-logo { width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; }
        .info-table, .results-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #333; color: #fff; }
        .print-controls { text-align: center; margin: 20px 0; }
        .btn { background: #007bff; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; margin: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .footer-signature { margin-top: 60px; display: flex; justify-content: space-between; font-weight: bold; }
        .footer-signature .box { width: 45%; text-align: center; padding: 15px; border-top: 1px dashed #999; }
        @media print {
            .print-controls, nav { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <ul>
        <li><a href="index.php">🏠 الرئيسية</a></li>
        <li><a href="view_swimmers.php">👤 السباحين</a></li>
        <li><a href="view_results.php">🏆 عرض النتائج</a></li>
    </ul>
</nav>

<div class="container">
    <div class="header">
        <?php if ($swimmer['club_logo']): ?>
            <img src="<?= htmlspecialchars($swimmer['club_logo']) ?>" class="club-logo" alt="شعار النادي">
        <?php endif; ?>
        <h2>الملف الشخصي: <?= htmlspecialchars($swimmer['name']) ?></h2>
    </div>

    <!-- 🧍‍♂️ بيانات السباح -->
    <table class="info-table">
        <tr>
            <th>الاسم</th>
            <td><?= htmlspecialchars($swimmer['name']) ?></td>
            <th>الجنس</th>
            <td><?= htmlspecialchars($swimmer['gender']) ?></td>
        </tr>
        <tr>
            <th>تاريخ الميلاد</th>
            <td><?= htmlspecialchars($swimmer['birth_date']) ?></td>
            <th>الفئة العمرية</th>
            <td><?= htmlspecialchars($swimmer['age_category']) ?></td>
        </tr>
        <tr>
            <th>النادي</th>
            <td colspan="3"><?= htmlspecialchars($swimmer['club_name']) ?></td>
        </tr>
    </table>

    <!-- 🏆 جدول النتائج -->
    <?php if ($results): ?>
        <h3 style="text-align:center; margin-top: 30px;">📊 نتائج السباقات</h3>
        <table class="results-table">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>المسافة</th>
                    <th>الجنس</th>
                    <th>الفئة</th>
                    <th>الوقت</th>
                    <th>المرتبة</th>
                    <th>ملاحظة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $res): ?>
                    <tr>
                        <td><?= date('Y-m-d', strtotime($res['created_at'])) ?></td>
                        <td><?= htmlspecialchars($res['swim_type']) ?></td>
                        <td><?= htmlspecialchars($res['distance']) ?></td>
                        <td><?= htmlspecialchars($res['gender']) ?></td>
                        <td><?= htmlspecialchars($res['age_category']) ?></td>
                        <td><?= htmlspecialchars($res['time_record']) ?></td>
                        <td>
                            <?= htmlspecialchars($res['rank']) ?>
                            <?php if ($res['rank'] == 1): ?> 🥇
                            <?php elseif ($res['rank'] == 2): ?> 🥈
                            <?php elseif ($res['rank'] == 3): ?> 🥉
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($res['note']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center; background:#fff3cd; padding:10px; border:1px solid #ffeeba;">❌ لا توجد نتائج مسجلة لهذا السباح.</p>
    <?php endif; ?>

    <!-- 🎯 أزرار -->
    <div class="print-controls">
        <button onclick="window.print()" class="btn">🖨️ طباعة</button>
        <a href="export_swimmer_pdf.php?id=<?= $swimmer_id ?>" target="_blank" class="btn">📄 تصدير PDF</a>
    </div>

    <!-- ✍️ ختم أو توقيع -->
    <div class="footer-signature">
        <div class="box">توقيع المدرب</div>
        <div class="box">ختم النادي</div>
    </div>
</div>

</body>
</html>
