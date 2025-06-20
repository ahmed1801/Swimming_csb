<?php
session_start(); // أضف هذا في أعلى الملف
require 'db.php';
?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="success-message"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
    <div class="error-message"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<?php
require 'db.php';

$filter_swim_type = $_GET['swim_type'] ?? '';
$filter_distance = $_GET['distance'] ?? '';

$conditions = [];
$params = [];

if (!empty($filter_swim_type)) {
    $conditions[] = "rg.swim_type = ?";
    $params[] = $filter_swim_type;
}
if (!empty($filter_distance)) {
    $conditions[] = "rg.distance = ?";
    $params[] = $filter_distance;
}

$where = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
$sql = "SELECT rg.id FROM race_groups rg $where ORDER BY rg.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$group_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>📋 جميع السباقات</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial; direction: rtl; padding: 20px; background: #f5f5f5; }
        h2 { margin-bottom: 20px; }
        .race-container { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 0 5px #ccc; }
        .race-header { background: #eee; padding: 10px; border-radius: 5px; margin-bottom: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #343a40; color: white; }
        .club-logo { width: 30px; height: 30px; border-radius: 50%; margin-left: 5px; vertical-align: middle; }

        .race-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s ease-in-out;
        }

        .btn.view   { background-color: #17a2b8; color: white; }
        .btn.edit   { background-color: #28a745; color: white; }
        .btn.delete { background-color: #dc3545; color: white; border: none; }
        .btn-print  { background-color: #ffc107; color: black; }

        .btn:hover { opacity: 0.9; }
        .success-message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
}

    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul>
    
    <li><a href="view_swimmers.php">🏊 قائمة السباحين</a></li>
    <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>
    <li><a href="prepare_race.php">🏁 إعداد سباق</a></li>
    <li><a href="prepare_relay_race.php">🏁 إعداد سباق تتابع </a></li>
    <li><a href="final_results.php"> النتائج النهائية المجمعة</a></li>
  </ul>
  </nav>

<h2>📋 جميع السباقات</h2>

<form method="get">
    <label>نوع السباحة:</label>
    <select name="swim_type" onchange="this.form.submit()">
        <option value="">الكل</option>
        <?php
        $types = ["سباحة حرة", "سباحة على الصدر", "سباحة على الظهر", "سباحة الفراشة", "الفردي المتنوع", "التتابع المتنوع"];
        foreach ($types as $type) {
            $selected = $filter_swim_type === $type ? 'selected' : '';
            echo "<option value=\"$type\" $selected>$type</option>";
        }
        ?>
    </select>

    <label>المسافة:</label>
    <select name="distance" onchange="this.form.submit()">
        <option value="">الكل</option>
        <?php
        $distances = ["25m", "50m", "100m", "200m", "400m"];
        foreach ($distances as $d) {
            $selected = $filter_distance === $d ? 'selected' : '';
            echo "<option value=\"$d\" $selected>$d</option>";
        }
        ?>
    </select>
</form>

<?php foreach ($group_ids as $group_id): ?>
<?php
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        rg.swim_type,
        rg.distance,
        rg.age_category,
        rg.gender,
        rg.is_relay,
        rg.created_at,
        s.name AS swimmer_name,
        s.club_name,
        s.club_id,
        c.logo AS club_logo,
        res.time_record,
        res.rank,
        res.note
    FROM races r
    JOIN race_groups rg ON r.race_group_id = rg.id
    JOIN swimmers s ON r.swimmer_id = s.id
    LEFT JOIN results res ON res.swimmer_id = s.id AND res.race_group_id = r.race_group_id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE r.race_group_id = ?
    ORDER BY res.rank IS NULL, res.rank ASC
");
$stmt->execute([$group_id]);
$race_data = $stmt->fetchAll();

if (!$race_data) continue;
$meta = $race_data[0];
?>

<div class="race-container">
    <table>
        <tr>
            <td>📅 <?= date('Y-m-d', strtotime($meta['created_at'])) ?></td>
            <td>⏰ <?= date('H:i:s', strtotime($meta['created_at'])) ?></td>
            <td>🏊‍♂️ <?= htmlspecialchars($meta['swim_type']) ?></td>
            <td>📏 <?= htmlspecialchars($meta['distance']) ?></td>
            <td>👥 <?= htmlspecialchars($meta['age_category']) ?></td>
            <td>⚧ <?= htmlspecialchars($meta['gender']) ?></td>
            <td>🔁 <?= $meta['is_relay'] ? 'تتابع' : 'فردي' ?></td>
        </tr>
    </table>

    <?php if ($meta['is_relay']): ?>
        <?php
        // تجميع السباحين حسب النادي
        $teams = [];
        foreach ($race_data as $row) {
            $club_id = $row['club_id'];
            $teams[$club_id]['club_name'] = $row['club_name'];
            $teams[$club_id]['logo'] = $row['club_logo'];
            $teams[$club_id]['swimmers'][] = $row['swimmer_name'];
            $teams[$club_id]['time'] = $row['time_record'];
        }

        // ترتيب حسب الوقت
        uasort($teams, function ($a, $b) {
            return strtotime("1970-01-01 {$a['time']}") <=> strtotime("1970-01-01 {$b['time']}");
        });

        $rank = 1;
        ?>

        <table>
            <thead>
                <tr>
                    <th>🏅 الترتيب</th>
                    <th>النادي</th>
                    <th>السباحون</th>
                    <th>⏱️ الوقت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <td>
                            <?= $rank ?>
                            <?= $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : '')) ?>
                        </td>
                        <td>
                            <?php if (!empty($team['logo'])): ?>
                                <img src="<?= htmlspecialchars($team['logo']) ?>" class="club-logo"><br>
                            <?php endif; ?>
                            <?= htmlspecialchars($team['club_name']) ?>
                        </td>
                        <td>
                            <?= implode('<br>', array_map('htmlspecialchars', $team['swimmers'])) ?>
                        </td>
                        <td><?= htmlspecialchars($team['time'] ?? '-') ?></td>
                    </tr>
                    <?php $rank++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- عرض فردي -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>السباح</th>
                    <th>النادي</th>
                    <th>الوقت</th>
                    <th>المرتبة</th>
                    <th>ملاحظة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($race_data as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['swimmer_name']) ?></td>
                        <td>
                            <?php if (!empty($row['club_logo'])): ?>
                                <img src="<?= htmlspecialchars($row['club_logo']) ?>" class="club-logo">
                            <?php endif; ?>
                            <?= htmlspecialchars($row['club_name']) ?>
                        </td>
                        <td><?= htmlspecialchars($row['time_record'] ?? '-') ?></td>
                        <td>
                            <?= htmlspecialchars($row['rank']) ?>
                            <?php if ($row['rank'] == 1): ?> 🥇
                            <?php elseif ($row['rank'] == 2): ?> 🥈
                            <?php elseif ($row['rank'] == 3): ?> 🥉
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- أزرار التحكم -->
   
<?php
// التحقق من وجود نتائج
    $stmtRes1 = $pdo->prepare("SELECT COUNT(*) FROM results WHERE race_group_id = ?");
    $stmtRes2 = $pdo->prepare("SELECT COUNT(*) FROM relay_results WHERE race_group_id = ?");
    $stmtRes1->execute([$group_id]);
    $stmtRes2->execute([$group_id]);
    $has_results = ($stmtRes1->fetchColumn() > 0 || $stmtRes2->fetchColumn() > 0);
?>
<div class="race-actions">
    <a href="results/view_results.php?highlight=<?= $group_id ?>" class="btn view">👁️ عرض النتائج</a>

    <?php if (!$has_results): ?>
        <a href="results/add_result.php?group=<?= $group_id ?>" class="btn edit">📥 إدخال النتائج</a>
    <?php else: ?>
        <span style="color:green; font-weight:bold;">✅ النتائج مدخلة</span>
    <?php endif; ?>

    <form method="post" action="delete_race_group.php" style="display:inline;">
        <input type="hidden" name="race_group_id" value="<?= $group_id ?>">
        <button type="submit" onclick="return confirm('هل أنت متأكد من حذف هذا السباق؟')" class="btn delete">🗑️ حذف</button>
    </form>
    <button onclick="printRace(this)" class="btn btn-print">🖨️ طباعة</button>
</div>

</div>
<?php endforeach; ?>

<script>
function printRace(button) {
    const section = button.closest('.race-container').cloneNode(true);
    const actions = section.querySelector('.race-actions');
    if (actions) actions.remove();
    const win = window.open('', '', 'width=900,height=650');
    win.document.write(`
        <html dir="rtl">
        <head>
            <title>طباعة السباق</title>
            <style>
                body { font-family: Arial; padding: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
                th { background: #343a40; color: white; }
                img.club-logo { width: 25px; height: 25px; object-fit: contain; }
            </style>
        </head>
        <body>${section.outerHTML}</body>
        </html>
    `);
    win.document.close();
    win.print();
}
</script>
</body>
</html>
