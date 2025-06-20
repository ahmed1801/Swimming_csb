<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("معرف السباح غير موجود.");
}

// جلب بيانات السباح
$stmt = $pdo->prepare("SELECT * FROM swimmers WHERE id = ?");
$stmt->execute([$id]);
$swimmer = $stmt->fetch();

if (!$swimmer) {
    die("السباح غير موجود.");
}

// جلب النوادي من جدول clubs
$clubs_stmt = $pdo->query("SELECT id, name FROM clubs ORDER BY name");
$clubs = $clubs_stmt->fetchAll(PDO::FETCH_ASSOC);

// عند حفظ التعديلات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $age_category = $_POST['age_category'];
    $club_id = $_POST['club_id'];

    // جلب اسم النادي من id
    $stmt = $pdo->prepare("SELECT name FROM clubs WHERE id = ?");
    $stmt->execute([$club_id]);
    $club_name = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE swimmers SET name = ?, gender = ?, birth_date = ?, age_category = ?, club_id = ?, club_name = ? WHERE id = ?");
    $stmt->execute([$name, $gender, $birth_date, $age_category, $club_id, $club_name, $id]);

    header("Location: view_swimmers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل السباح</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial; direction: rtl; padding: 20px; background: #f9f9f9; }
        form { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 5px #ccc; }
        label { display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ccc; }
        button { margin-top: 15px; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; width: 100%; font-size: 16px; }
        a { display: inline-block; margin-top: 10px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul> 
    <li><a href="view_swimmers.php">🏊 قائمة السباحين</a></li>
    <li><a href="manage_clubs.php">🏢 إدارة النوادي</a></li>
    <li><a href="all_races.php">📋 جميع السباقات</a></li>
    <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>
  </ul>
</nav>

<h2 style="text-align:center;">✏️ تعديل بيانات السباح</h2>

<form method="POST">
    <label>الاسم:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($swimmer['name']) ?>" required>

    <label>الجنس:</label>
    <select name="gender" required>
        <option value="ذكر" <?= $swimmer['gender'] == 'ذكر' ? 'selected' : '' ?>>ذكر</option>
        <option value="أنثى" <?= $swimmer['gender'] == 'أنثى' ? 'selected' : '' ?>>أنثى</option>
    </select>

    <label>تاريخ الميلاد:</label>
    <input type="date" name="birth_date" value="<?= $swimmer['birth_date'] ?>" required>

    <label>الفئة العمرية:</label>
    <select name="age_category" required>
        <option <?= $swimmer['age_category'] == 'Ecole 1' ? 'selected' : '' ?>>Ecole 1</option>
        <option <?= $swimmer['age_category'] == 'Ecole 2' ? 'selected' : '' ?>>Ecole 2</option>
        <option <?= $swimmer['age_category'] == 'Poussins' ? 'selected' : '' ?>>Poussins</option>
        <option <?= $swimmer['age_category'] == 'Benjamin1' ? 'selected' : '' ?>>Benjamin1</option>
        <option <?= $swimmer['age_category'] == 'Benjamin2' ? 'selected' : '' ?>>Benjamin2</option>
        <option <?= $swimmer['age_category'] == 'Minimes' ? 'selected' : '' ?>>Minimes</option>
        <option <?= $swimmer['age_category'] == 'Juny' ? 'selected' : '' ?>>Juny</option>
    </select>

    <label>النادي:</label>
    <select name="club_id" required>
        <?php foreach ($clubs as $club): ?>
            <option value="<?= $club['id'] ?>" <?= $club['id'] == $swimmer['club_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($club['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">💾 حفظ التعديلات</button>
</form>

<a href="view_swimmers.php">⬅️ العودة للقائمة</a>

</body>
</html>
