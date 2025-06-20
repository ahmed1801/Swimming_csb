<?php
require 'db.php';
session_start();

$group_id = $_GET['group'] ?? null;
if (!$group_id) die("❌ رقم السباق غير صالح.");

// حفظ النتائج عند الإرسال
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['times'])) {
    foreach ($_POST['times'] as $club_id => $time) {
        $time = trim($time);
        if ($time !== '') {
            // حذف النتائج القديمة لهذا الفريق
            $pdo->prepare("
                DELETE FROM results 
                WHERE race_group_id = ? 
                AND swimmer_id IN (
                    SELECT swimmer_id FROM races r 
                    JOIN swimmers s ON r.swimmer_id = s.id 
                    WHERE r.race_group_id = ? AND s.club_id = ?
                )
            ")->execute([$group_id, $group_id, $club_id]);

            // إدخال النتائج الجديدة لجميع السباحين من نفس النادي
            $stmt = $pdo->prepare("
                SELECT swimmer_id FROM races r 
                JOIN swimmers s ON r.swimmer_id = s.id 
                WHERE r.race_group_id = ? AND s.club_id = ?
                LIMIT 4
            ");
            $stmt->execute([$group_id, $club_id]);
            $swimmer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($swimmer_ids as $sid) {
                $pdo->prepare("INSERT INTO results (swimmer_id, race_group_id, time_record) VALUES (?, ?, ?)")
                    ->execute([$sid, $group_id, $time]);
            }
        }
    }

    $_SESSION['success'] = "✅ تم حفظ النتائج بنجاح.";
    header("Location: relay_results.php?group=$group_id");
    exit;
}

// جلب بيانات السباق
$stmt = $pdo->prepare("SELECT * FROM race_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();
if (!$group || !$group['is_relay']) die("❌ هذا ليس سباق تتابع.");

// جلب الفرق المشاركة
$stmt = $pdo->prepare("
    SELECT c.id AS club_id, c.name AS club_name, c.logo,
           s.name AS swimmer_name
    FROM races r
    JOIN swimmers s ON r.swimmer_id = s.id
    JOIN clubs c ON s.club_id = c.id
    WHERE r.race_group_id = ?
    ORDER BY c.name, s.name
");
$stmt->execute([$group_id]);
$rows = $stmt->fetchAll();

$teams = [];
foreach ($rows as $row) {
    $teams[$row['club_id']]['club_name'] = $row['club_name'];
    $teams[$row['club_id']]['logo'] = $row['logo'];
    $teams[$row['club_id']]['swimmers'][] = $row['swimmer_name'];
}

// جلب النتائج القديمة
$stmt = $pdo->prepare("
    SELECT s.club_id, r.time_record
    FROM results r
    JOIN swimmers s ON r.swimmer_id = s.id
    WHERE r.race_group_id = ?
    GROUP BY s.club_id
");
$stmt->execute([$group_id]);
$old_results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// الترتيب حسب الوقت
$sorted = $old_results;
uasort($sorted, fn($a, $b) => strtotime($a) <=> strtotime($b));
$ranking = array_keys($sorted);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نتائج سباق التتابع</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial; padding: 20px; direction: rtl; background: #f5f5f5; }
        h2 { text-align: center; }
        table { width: 100%; background: #fff; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #343a40; color: white; }
        img.logo { width: 40px; height: 40px; border-radius: 50%; }
        input[type="text"] { width: 100px; padding: 6px; text-align: center; }
        .save-btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; }
        .pdf-btn { background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 5px; }
        .success { background: #d4edda; padding: 10px; color: #155724; border: 1px solid #c3e6cb; margin: 15px auto; border-radius: 6px; width: 80%; text-align: center; }
    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul>
    <li><a href="index.php">🏠 الرئيسية</a></li>
    <li><a href="prepare_race.php">🏁 إعداد سباق فردي</a></li>
    <li><a href="prepare_relay_race.php">🏁 إعداد سباق تتابع </a></li>
    <li><a href="all_races.php">📋 جميع السباقات</a></li>
    <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>
    
  </ul>
</nav>
<h2>📊 نتائج سباق التتابع - <?= htmlspecialchars($group['swim_type']) ?> <?= $group['distance'] ?></h2>
<p style="text-align:center; font-weight:bold; color:#555;">
    📅 التاريخ: <?= date('Y-m-d') ?>
</p>

<?php if (isset($_SESSION['success'])): ?>
    <div class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<form method="POST">
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
            <?php
            foreach ($teams as $club_id => $team):
                $time = $old_results[$club_id] ?? '';
                $rank = array_search($club_id, $ranking) + 1;
                $medal = $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : ''));
            ?>
                <tr>
                    <td><?= $time ? "$rank $medal" : '-' ?></td>
                    <td>
                        <?php if (!empty($team['logo'])): ?>
                            <img src="<?= htmlspecialchars($team['logo']) ?>" class="logo"><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($team['club_name']) ?>
                    </td>
                    <td><?= implode('<br>', array_map('htmlspecialchars', $team['swimmers'])) ?></td>
                    <td>
                        <input type="text" name="times[<?= $club_id ?>]" value="<?= $time ?>" class="time-input" placeholder="HHMMSS">
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align:center;">
        <button type="submit" class="save-btn">💾 حفظ النتائج</button>
        <a href="export_relay_results_pdf.php?group=<?= $group_id ?>" class="pdf-btn">📄 حفظ PDF</a>
        <div style="text-align: center; margin-top: 20px;">
        <a href="prepare_relay_race.php" class="btn" style="background-color: #007bff; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
        ⬅️ العودة لإعداد سباق التتابع
    </a>
</div>

    </div>
</form>

<script>
// 🕒 تحويل الإدخال إلى تنسيق HH:MM:SS
function convertToTimeFormat(input) {
    const val = input.value.replace(/\D/g, '');
    if (val.length === 6) {
        input.value = val.slice(0,2) + ":" + val.slice(2,4) + ":" + val.slice(4);
    }
}

document.querySelectorAll(".time-input").forEach(input => {
    input.addEventListener("blur", function () {
        convertToTimeFormat(this);
    });
});
</script>

</body>
</html>
