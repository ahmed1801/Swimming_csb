<?php
require 'db.php';

// جلب عدد السباحين
$total_swimmers = $pdo->query("SELECT COUNT(*) FROM swimmers")->fetchColumn();

// جلب عدد النوادي
$total_clubs = $pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn();

$total_races = $pdo->query("SELECT COUNT(DISTINCT race_group_id) FROM races")->fetchColumn();


// حذف سباح محدد
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM swimmers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: view_swimmers.php?deleted=1");
    exit;
}

// حذف الكل
if (isset($_POST['delete_all'])) {
    $pdo->exec("DELETE FROM swimmers");
    header("Location: view_swimmers.php?deleted_all=1");
    exit;
}

// جلب السباحين مع النادي
$stmt = $pdo->query("
    SELECT s.*, c.name AS club_name, c.logo 
    FROM swimmers s
    LEFT JOIN clubs c ON s.club_id = c.id
    ORDER BY s.name ASC
");
$swimmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>قائمة السباحين</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        body { font-family: 'Segoe UI', sans-serif; direction: rtl; padding: 20px; background-color: #f4f4f4; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        .button { padding: 6px 12px; color: white; border-radius: 5px; text-decoration: none; margin: 2px; display: inline-block; font-size: 14px; }
        .edit { background-color: #007bff; }
        .delete { background-color: #dc3545; }
        .view { background-color: #28a745; }
        .add-new { background-color: #17a2b8; padding: 10px 15px; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin: 10px 0; }
        .alert { padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; width: fit-content; margin-bottom: 15px; }
        .navbar ul { list-style: none; padding: 0; text-align: center; margin-bottom: 20px; }
        .navbar ul li { display: inline-block; margin: 0 10px; }
        .navbar ul li a { color: #007bff; text-decoration: none; font-weight: bold; }
        img.club-logo { height: 30px; vertical-align: middle; margin-left: 8px; border-radius: 4px; }
        .stats {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
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

<!-- ✅ شريط التنقل -->
<nav class="navbar">
    <ul>
        <li><a href="index.php">🏠 الرئيسية</a></li>
        <li><a href="prepare_race.php">🏁 إعداد سباق</a></li>
        <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>
        <li><a href="manage_clubs.php">🏢 إدارة النوادي</a></li>
    </ul>
</nav>

<h2>📋 قائمة السباحين</h2>
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

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert">✅ تم حذف السباح بنجاح.</div>
<?php elseif (isset($_GET['deleted_all'])): ?>
    <div class="alert">✅ تم حذف جميع السباحين بنجاح.</div>
<?php endif; ?>

<a href="add_swimmer.php" class="add-new">+ إضافة سباح</a>
<form method="post" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف جميع السباحين؟');">
    <button type="submit" name="delete_all" class="add-new" style="background-color: #dc3545;">🗑️ حذف الكل</button>
</form>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>الجنس</th>
            <th>تاريخ الميلاد</th>
            <th>النادي</th>
            <th>الفئة العمرية</th>
            <th>العمليات</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($swimmers): ?>
            <?php foreach ($swimmers as $i => $swimmer): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($swimmer['name']) ?></td>
                    <td><?= htmlspecialchars($swimmer['gender']) ?></td>
                    <td><?= htmlspecialchars($swimmer['birth_date']) ?></td>
                    <td>
                        <?php if (!empty($swimmer['logo'])): ?>
                            <img src="<?= htmlspecialchars($swimmer['logo']) ?>" class="club-logo" alt="شعار النادي">
                        <?php endif; ?>
                        <?= htmlspecialchars($swimmer['club_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($swimmer['age_category']) ?></td>
                    <td>
                        <a href="edit_swimmer.php?id=<?= $swimmer['id'] ?>" class="button edit">✏️ تعديل</a>
                        <a href="swimmer_profile.php?id=<?= $swimmer['id'] ?>" class="button view">👁️ عرض</a>
                        <a href="?delete_id=<?= $swimmer['id'] ?>" onclick="return confirm('هل أنت متأكد من الحذف؟');" class="button delete">🗑️ حذف</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">لا يوجد سباحين مسجلين.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
