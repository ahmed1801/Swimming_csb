<?php
require 'db.php';
session_start();

$clubs = $pdo->query("SELECT * FROM clubs ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>🏊 إدارة النوادي</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial; direction: rtl; padding: 20px; background: #f9f9f9; }
        h2 { text-align: center; margin-bottom: 20px; }

        .actions {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            margin: 5px;
            cursor: pointer;
        }

        .btn-add { background-color: #28a745; }
        .btn-delete { background-color: #dc3545; }
        .btn-back { background-color: #007bff; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #343a40;
            color: white;
        }

        img.logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul> 
    <li><a href="view_swimmers.php">🏊 قائمة السباحين</a></li>
    <li><a href="all_races.php">📋 جميع السباقات</a></li>
    <li><a href="leaderboard.php">🥇 لوحة التصنيفات</a></li>
  </ul>
</nav>
<h2>🏊 إدارة النوادي الرياضية</h2>

<?php if (isset($_SESSION['success'])): ?>
    <div class="message success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
    <div class="message error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="actions">
    <a href="add_club.php" class="btn btn-add">➕ إضافة نادي جديد</a>
    <a href="index.php" class="btn btn-back">⬅️ العودة للرئيسية</a>
</div>

<table>
    <thead>
        <tr>
            <th>رقم</th>
            <th>الشعار</th>
            <th>اسم النادي</th>
            <th>الإجراء</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($clubs) > 0): ?>
            <?php foreach ($clubs as $club): ?>
                <tr>
                    <td><?= $club['id'] ?></td>
                    <td>
                        <?php if (!empty($club['logo'])): ?>
                            <img src="<?= htmlspecialchars($club['logo']) ?>" class="logo">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($club['name']) ?></td>
                    <td>
                        <form method="POST" action="delete_club.php" onsubmit="return confirm('هل أنت متأكد من حذف هذا النادي؟');" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $club['id'] ?>">
                            <button type="submit" class="btn btn-delete">🗑️ حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">🚫 لا توجد نوادي مسجلة حاليًا.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
