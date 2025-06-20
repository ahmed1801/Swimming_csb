<?php
require 'db.php';

// ✅ جلب التصفية من المستخدم
$swim_type = $_GET['swim_type'] ?? '';
$distance = $_GET['distance'] ?? '';
$age_category = $_GET['age_category'] ?? '';

// ✅ إعداد شروط الفلترة
$conditions = [];
$params = [];

if (!empty($swim_type)) {
    $conditions[] = "rg.swim_type = ?";
    $params[] = $swim_type;
}
if (!empty($distance)) {
    $conditions[] = "rg.distance = ?";
    $params[] = $distance;
}
if (!empty($age_category)) {
    $conditions[] = "rg.age_category = ?";
    $params[] = $age_category;
}

$where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ✅ جلب النتائج من جميع المجموعات التي تطابق التصفية
$stmt = $pdo->prepare("
    SELECT 
        s.name AS swimmer_name,
        c.name AS club_name,
        res.time_record,
        res.note
    FROM results res
    JOIN swimmers s ON res.swimmer_id = s.id
    JOIN race_groups rg ON res.race_group_id = rg.id
    LEFT JOIN clubs c ON s.club_id = c.id
    $where
");
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ ترتيب النتائج حسب الوقت من الأصغر إلى الأكبر
usort($results, function($a, $b) {
    $timeA = floatval(str_replace([':', '.'], '', $a['time_record']));
    $timeB = floatval(str_replace([':', '.'], '', $b['time_record']));
    return $timeA <=> $timeB;
});
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>النتائج النهائية</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial; direction: rtl; padding: 20px; background: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #333; color: white; }
        .filters { margin-bottom: 20px; }
        .filters label { margin-left: 10px; }
        .filters select { padding: 6px; }
        .print-btn {
            background: #007bff; color: white; padding: 8px 16px; border: none;
            border-radius: 5px; cursor: pointer; margin: 10px 5px;
        }
    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul>
    <li><a href="index.php">🏠 الرئيسية</a></li>
    <li><a href="prepare_race.php">🏁 إعداد سباق</a></li>
    <li><a href="all_races.php">📋 جميع السباقات</a></li>
    <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>

  </ul>
</nav>
<h2>📊 النتائج النهائية حسب التصفية</h2>

<!-- ✅ نموذج التصفية -->
<form method="get" class="filters">
    <label>نوع السباحة:
        <select name="swim_type">
            <option value="">الكل</option>
            <option value="سباحة حرة" <?= $swim_type == 'سباحة حرة' ? 'selected' : '' ?>>سباحة حرة</option>
            <option value="سباحة الفراشة" <?= $swim_type == 'سباحة الفراشة' ? 'selected' : '' ?>>سباحة الفراشة</option>
            <option value="سباحة على الظهر" <?= $swim_type == 'سباحة على الظهر' ? 'selected' : '' ?>>سباحة على الظهر</option>
            <option value="سباحة على الصدر" <?= $swim_type == 'سباحة على الصدر' ? 'selected' : '' ?>>سباحة على الصدر</option>
            <option value="الفردي المتنوع" <?= $swim_type == 'الفردي المتنوع' ? 'selected' : '' ?>>الفردي المتنوع</option>
        </select>
    </label>

    <label>المسافة:
        <select name="distance">
            <option value="">الكل</option>
            <option value="25m" <?= $distance == '25m' ? 'selected' : '' ?>>25m</option>
            <option value="50m" <?= $distance == '50m' ? 'selected' : '' ?>>50m</option>
            <option value="100m" <?= $distance == '100m' ? 'selected' : '' ?>>100m</option>
            <option value="200m" <?= $distance == '200m' ? 'selected' : '' ?>>200m</option>
        </select>
    </label>

    <label>الفئة:
        <select name="age_category">
            <option value="">الكل</option>
            <option value="Ecole 1" <?= $age_category == 'Ecole 1' ? 'selected' : '' ?>>Ecole 1</option>
            <option value="Ecole 2" <?= $age_category == 'Ecole 2' ? 'selected' : '' ?>>Ecole 2</option>
            <option value="Benjamin1" <?= $age_category == 'Benjamin1' ? 'selected' : '' ?>>Benjamin1</option>
            <option value="Benjamin2" <?= $age_category == 'Benjamin2' ? 'selected' : '' ?>>Benjamin2</option>
        </select>
    </label>

    <button type="submit" class="print-btn">🔍 تصفية</button>
</form>

<?php if (count($results) > 0): ?>
    <?php if (count($results) > 0): ?>
    <form method="get" action="export_final_results.php" target="_blank" style="display:inline;">
        <input type="hidden" name="swim_type" value="<?= htmlspecialchars($swim_type) ?>">
        <input type="hidden" name="distance" value="<?= htmlspecialchars($distance) ?>">
        <input type="hidden" name="age_category" value="<?= htmlspecialchars($age_category) ?>">
        <button type="submit" class="print-btn">💾 حفظ كـ PDF</button>
    </form>
<?php endif; ?>

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
            <?php foreach ($results as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?> <?= $index == 0 ? '🥇' : ($index == 1 ? '🥈' : ($index == 2 ? '🥉' : '')) ?></td>
                    <td><?= htmlspecialchars($row['swimmer_name']) ?></td>
                    <td><?= htmlspecialchars($row['club_name']) ?></td>
                    <td><?= htmlspecialchars($row['time_record']) ?></td>
                    <td><?= htmlspecialchars($row['note']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
   

<?php else: ?>
    <p style="color:red;">❌ لا توجد نتائج مطابقة للتصفية.</p>
<?php endif; ?>

</body>
</html>
