<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $logo = '';

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo = 'logos/' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], $logo);
    }

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO clubs (name, logo) VALUES (?, ?)");
        $stmt->execute([$name, $logo]);
        $_SESSION['success'] = "✅ تم إضافة النادي بنجاح.";
    } else {
        $_SESSION['error'] = "⚠️ الرجاء إدخال اسم النادي.";
    }

    header("Location: add_club.php");
    exit;
}

$clubs = $pdo->query("SELECT * FROM clubs ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>➕ إضافة نادي</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; direction: rtl; }
        form { background: #fff; padding: 20px; border-radius: 8px; max-width: 400px; margin: auto; }
        input, button { width: 100%; padding: 10px; margin-top: 10px; }
        table { width: 100%; margin-top: 30px; background: #fff; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        img.logo { width: 40px; height: 40px; border-radius: 50%; }
        .message { text-align: center; margin: 10px auto; max-width: 400px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul> 
    <li><a href="view_swimmers.php">🏊 قائمة السباحين</a></li>
    <li><a href="manage_clubs.php">🏢 إدارة النوادي</a></li>
    
  </ul>
</nav>
<h2 style="text-align:center;">➕ إضافة نادي جديد</h2>

<?php if (isset($_SESSION['success'])): ?>
    <div class="message success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
    <div class="message error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>اسم النادي:</label>
    <input type="text" name="name" required>
    
    <label>شعار النادي:</label>
    <input type="file" name="logo" accept="image/*">

    <button type="submit">💾 إضافة</button>
</form>

<h3>📋 قائمة النوادي</h3>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>الشعار</th>
            <th>الاسم</th>
            <th>إجراء</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($clubs as $club): ?>
            <tr>
                <td><?= $club['id'] ?></td>
                <td>
                    <?php if (!empty($club['logo'])): ?>
                        <img src="<?= $club['logo'] ?>" class="logo">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($club['name']) ?></td>
                <td>
                    <form method="POST" action="delete_club.php" onsubmit="return confirm('هل أنت متأكد من حذف النادي؟');">
                        <input type="hidden" name="id" value="<?= $club['id'] ?>">
                        <button type="submit" style="background:#dc3545;color:white;padding:6px 12px;border:none;border-radius:5px;">🗑️ حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
