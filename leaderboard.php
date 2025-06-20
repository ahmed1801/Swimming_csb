<?php
require 'db.php';

// جلب الفلاتر من GET
$selected_age = $_GET['age'] ?? '';
$selected_club = $_GET['club'] ?? '';

// ✅ جلب الفئات من السباحين
$categories = $pdo->query("SELECT DISTINCT age_category FROM swimmers ORDER BY age_category")->fetchAll(PDO::FETCH_COLUMN);

// ✅ جلب أسماء النوادي من جدول clubs بدلًا من swimmers
$clubs = $pdo->query("SELECT id, name FROM clubs ORDER BY name")->fetchAll();

// بناء الاستعلام
$query = "
    SELECT 
        r.swimmer_id,
        s.name AS swimmer_name,
        s.age_category,
        c.name AS club_name,
        r.rank
    FROM results r
    JOIN swimmers s ON r.swimmer_id = s.id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE 1
";

$params = [];
if ($selected_age !== '') {
    $query .= " AND s.age_category = ?";
    $params[] = $selected_age;
}
if ($selected_club !== '') {
    $query .= " AND c.id = ?";
    $params[] = $selected_club;
}

$query .= " ORDER BY s.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// حساب النقاط
function getPoints($rank) {
    return match((int)$rank) {
        1 => 10, 2 => 8, 3 => 6, 4 => 4, 5 => 3, 6 => 2,
        default => 1
    };
}

$leaderboard = [];
foreach ($results as $row) {
    $id = $row['swimmer_id'];
    if (!isset($leaderboard[$id])) {
        $leaderboard[$id] = [
            'name' => $row['swimmer_name'],
            'club' => $row['club_name'],
            'age_category' => $row['age_category'],
            'points' => 0,
            'races' => 0
        ];
    }
    $leaderboard[$id]['points'] += getPoints($row['rank']);
    $leaderboard[$id]['races'] += 1;
}

usort($leaderboard, fn($a, $b) => $b['points'] <=> $a['points']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>🏆 التصنيفات</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .filter-bar {
            text-align: center;
            margin: 20px 0;
        }
        .filter-bar select {
            padding: 6px 12px;
            margin: 0 10px;
            border-radius: 5px;
            font-size: 1rem;
        }
    </style>
    <script>
        function applyFilters() {
            const age = document.getElementById("age_filter").value;
            const club = document.getElementById("club_filter").value;
            window.location.href = `leaderboard.php?age=${encodeURIComponent(age)}&club=${encodeURIComponent(club)}`;
        }
    </script>
</head>
<body>
<!-- ✅ شريط التنقل -->
<nav class="navbar">
    <ul>
        <li><a href="index.php">🏠 الصفحة الرئيسية</a></li>
        <li><a href="prepare_race.php">🏁 إعداد سباق</a></li>
        <li><a href="all_races.php">📋 جميع السباقات</a></li>
    </ul>
</nav>
<h2>🏆 تصنيفات أفضل السباحين</h2>

<div class="filter-bar">
    <label>الفئة:</label>
    <select id="age_filter" onchange="applyFilters()">
        <option value="">الكل</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat ?>" <?= $cat === $selected_age ? 'selected' : '' ?>><?= $cat ?></option>
        <?php endforeach; ?>
    </select>

    <label>النادي:</label>
    <select id="club_filter" onchange="applyFilters()">
        <option value="">الكل</option>
        <?php foreach ($clubs as $club): ?>
            <option value="<?= $club['id'] ?>" <?= $club['id'] == $selected_club ? 'selected' : '' ?>>
                <?= htmlspecialchars($club['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<?php if (empty($leaderboard)): ?>
    <p style="text-align:center;">⚠️ لا توجد نتائج لهذه التصفية.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>الترتيب</th>
                <th>الاسم</th>
                <th>النادي</th>
                <th>الفئة</th>
                <th>عدد السباقات</th>
                <th>النقاط</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaderboard as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['club']) ?></td>
                    <td><?= htmlspecialchars($s['age_category']) ?></td>
                    <td><?= $s['races'] ?></td>
                    <td><?= $s['points'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="export_leaderboard_pdf.php?age=<?= urlencode($selected_age) ?>&club=<?= urlencode($selected_club) ?>" 
   target="_blank" 
   class="btn" 
   style="background:#6f42c1; color:#fff; padding:8px 14px; border-radius:6px; display:inline-block; margin-bottom:15px;">
   📄 تصدير PDF
</a>

<?php endif; ?>

</body>
</html>
