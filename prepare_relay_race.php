<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=swimming_club;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// عند الضغط على زر الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_relay'])) {
    $distance = $_POST['distance'];
    $swim_type = $_POST['swim_type'];
    $groups = $_POST['groups'] ?? [];

    if (empty($groups)) {
        $_SESSION['error'] = "❌ يجب اختيار سباحين من نادي واحد على الأقل.";
        header("Location: prepare_relay_race.php");
        exit;
    }

    try {
        // إنشاء سباق تتابع جديد
        $stmt = $pdo->prepare("INSERT INTO race_groups (swim_type, distance, age_category, gender, is_relay) VALUES (?, ?, 'تتابع', 'مختلط', 1)");
        $stmt->execute([$swim_type, $distance]);
        $group_id = $pdo->lastInsertId();

        // ربط السباحين بالسباق
        $stmt2 = $pdo->prepare("INSERT INTO races (race_group_id, swimmer_id) VALUES (?, ?)");
        foreach ($groups as $club_id => $swimmer_ids) {
            foreach ($swimmer_ids as $swimmer_id) {
                $stmt2->execute([$group_id, $swimmer_id]);
            }
        }

        header("Location: relay_results.php?group=$group_id");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = "❌ حدث خطأ أثناء الحفظ: " . $e->getMessage();
        header("Location: prepare_relay_race.php");
        exit;
    }
}

// جلب النوادي والسباحين
$clubs = $pdo->query("SELECT id, name FROM clubs ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$swimmers_by_club = [];

foreach ($clubs as $club) {
    $stmt = $pdo->prepare("SELECT id, name FROM swimmers WHERE club_id = ? ORDER BY name");
    $stmt->execute([$club['id']]);
    $swimmers_by_club[$club['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعداد سباق تتابع</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; direction: rtl; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 960px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }

        h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin: 10px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select { width: 100%; padding: 8px; border-radius: 5px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #007bff; color: white; }
        .club-header { background: #f0f0f0; font-weight: bold; padding: 8px; }

        .btn {
            background: #28a745;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn:hover { background: #218838; }

        .error, .success {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }

        .note { font-size: 0.9em; color: #555; margin-bottom: 10px; }
    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul>
    <li><a href="index.php">🏠 الرئيسية</a></li>
    <li><a href="prepare_race.php">🏁 إعداد سباق فردي</a></li>
    <li><a href="all_races.php">📋 جميع السباقات</a></li>
    <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>
    
  </ul>
</nav>

<div class="container">
    <h2>🏊‍♂️ إعداد سباق تتابع</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>نوع السباحة:</label>
            <select name="swim_type" required>
                <option value="">-- اختر نوع السباحة --</option>
                <option>سباحة حرة</option>
                <option>سباحة على الصدر</option>
                <option>سباحة الفراشة</option>
                <option>التتابع المتنوع</option>
            </select>
        </div>

        <div class="form-group">
            <label>المسافة:</label>
            <select name="distance" required>
                <option value="">-- اختر المسافة --</option>
                <option>100m</option>
                <option>200m</option>
                <option>400m</option>
                <option>800m</option>
            </select>
        </div>

        <p class="note">اختر 4 سباحين فقط من كل نادي للمشاركة في هذا السباق.</p>

        <?php foreach ($clubs as $club): ?>
            <?php if (count($swimmers_by_club[$club['id']]) >= 4): ?>
                <div class="club-header"><?= htmlspecialchars($club['name']) ?></div>
                <table>
                    <thead>
                        <tr>
                            <th>اختر</th>
                            <th>اسم السباح</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($swimmers_by_club[$club['id']] as $swimmer): ?>
                            <tr>
                                <td><input type="checkbox" name="groups[<?= $club['id'] ?>][]" value="<?= $swimmer['id'] ?>" class="club-<?= $club['id'] ?>"></td>
                                <td><?= htmlspecialchars($swimmer['name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit" name="submit_relay" class="btn">💾 حفظ وعرض النتائج</button>
    </form>
</div>

<script>
// السماح باختيار فقط 4 سباحين من كل نادي
document.querySelectorAll("input[type='checkbox']").forEach(checkbox => {
    checkbox.addEventListener("change", function() {
        const clubId = this.className.split("-")[1];
        const selected = document.querySelectorAll(".club-" + clubId + ":checked");
        if (selected.length > 4) {
            alert("❌ يمكنك اختيار 4 سباحين فقط من هذا النادي.");
            this.checked = false;
        }
    });
});
</script>

</body>
</html>
