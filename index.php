<?php
session_start();
require_once 'db.php';
// جلب عدد السباحين
$total_swimmers = $pdo->query("SELECT COUNT(*) FROM swimmers")->fetchColumn();

// جلب عدد النوادي
$total_clubs = $pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn();

$total_races = $pdo->query("SELECT COUNT(DISTINCT race_group_id) FROM races")->fetchColumn();

$message = "";

// حذف سباح
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM swimmers WHERE id = ?");
    $stmt->execute([$delete_id]);
    $_SESSION['message'] = "✅ تم حذف السباح بنجاح.";
    header("Location: index.php");
    exit;
}

// حذف الكل
if (isset($_POST['delete_all'])) {
    $pdo->exec("DELETE FROM swimmers");
    $_SESSION['message'] = "✅ تم حذف جميع السباحين بنجاح.";
    header("Location: index.php");
    exit;
}

// جلب السباحين مع بيانات النادي
$stmt = $pdo->prepare("
    SELECT s.*, c.name AS club_name, c.logo 
    FROM swimmers s
    LEFT JOIN clubs c ON s.club_id = c.id
    ORDER BY s.name ASC
");
$stmt->execute();
$swimmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة السباحين</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #2b2929; color: white; }
        a.button { padding: 5px 12px; margin: 0 4px; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; }
        a.edit { background-color: #007bff; }
        a.delete { background-color: #dc3545; }
        a.view { background-color: #17a2b8; }
        .alert { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 15px; border-radius: 5px; width: fit-content; }
        .club-logo { height: 32px; vertical-align: middle; margin-left: 8px; border-radius: 50%; }
        .delete-all { background-color: #dc3545; color: white; border: none; padding: 10px 18px; border-radius: 5px; cursor: pointer; }
        .add-new { background-color: #28a745; color: white; padding: 10px 18px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; margin-bottom: 10px; }
        .stats {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-box {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-box h2 {
            font-size: 40px;
            color: #007bff;
            margin: 0;
        }

        .stat-box p {
            margin: 5px 0 0;
            font-weight: bold;
            color: #444;
        }
    </style>
</head>
<body>

<!-- شريط التنقل -->
<nav class="navbar">
  <ul>
    
    <li><a href="view_swimmers.php">🏊 قائمة السباحين</a></li>
    <li><a href="import_swimmers.php">📥 استيراد السباحين</a></li>
    <li><a href="manage_clubs.php">🏢 إدارة النوادي</a></li>
    <li><a href="all_races.php">📋 جميع السباقات</a></li>
  </ul>
  </nav>
  <nav class="navbar">
  <ul>
    <li><a href="results/view_results.php">📊 عرض النتائج</a></li>
    <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>
    <li><a href="prepare_race.php">🏁 إعداد سباق</a></li>
    <li><a href="prepare_relay_race.php">🏁 إعداد سباق تتابع </a></li>
    <li><a href="final_results.php"> النتائج النهائية المجمعة</a></li>
  </ul>
</nav>


<h2>قائمة السباحين</h2>
<div class="stats">
    <div class="stat-box">
        <p>عدد السباحين المسجلين</p><hr>
        <h2><?= $total_swimmers ?></h2>
    </div>
    <div class="stat-box">
        <p>عدد النوادي المسجلة</p><hr>
        <h2><?= $total_clubs ?></h2>
    </div>
    <div class="stat-box">
        <p>السباقات المُسجّلة</p><hr>
        <h2><?= $total_races ?></h2>   
    </div>
</div>
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<a href="add_swimmer.php" class="add-new">+ إضافة سباح جديد</a>

<form method="post" onsubmit="return confirm('هل أنت متأكد من حذف جميع السباحين؟');" style="display:inline;">
    <button type="submit" name="delete_all" class="delete-all">🗑️ حذف الكل</button>
</form>

<table>
    <thead>
        <tr>
            <th>الرقم</th>
            <th>الاسم</th>
            <th>الجنس</th>
            <th>تاريخ الميلاد</th>
            <th>النادي</th>
            <th>الفئة العمرية</th>
            <th>العمليات</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($swimmers): foreach ($swimmers as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['gender']) ?></td>
                <td><?= htmlspecialchars($s['birth_date']) ?></td>
                <td>
                    <?php if ($s['logo']): ?>
                        <img src="<?= htmlspecialchars($s['logo']) ?>" class="club-logo" alt="شعار النادي">
                    <?php endif; ?>
                    <?= htmlspecialchars($s['club_name']) ?>
                </td>
                <td><?= htmlspecialchars($s['age_category']) ?></td>
                <td>
                    <a href="edit_swimmer.php?id=<?= $s['id'] ?>" class="button edit">تعديل</a>
                    <a href="?delete_id=<?= $s['id'] ?>" class="button delete" onclick="return confirm('هل أنت متأكد من الحذف؟');">حذف</a>
                    <a href="swimmer_profile.php?id=<?= $s['id'] ?>" class="button view">عرض</a>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="7">لا يوجد سباحين.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
