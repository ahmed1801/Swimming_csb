<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']);
    $gender       = $_POST['gender'];
    $birth_date   = $_POST['birth_date'];
    $club_id      = intval($_POST['club_id']);
    $age_category = $_POST['age_category'];

    try {
        // جلب اسم النادي من clubs
        $stmt = $pdo->prepare("SELECT name FROM clubs WHERE id = ?");
        $stmt->execute([$club_id]);
        $club_name = $stmt->fetchColumn();

        if ($club_name) {
            $stmt = $pdo->prepare("
                INSERT INTO swimmers (name, gender, birth_date, club_name, age_category, club_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $gender, $birth_date, $club_name, $age_category, $club_id]);

            $success = "✅ تم حفظ السباح بنجاح.";
            header("Location: view_swimmers.php?added=1");
            exit;
        } else {
            $error = "❌ النادي غير موجود.";
        }

    } catch (PDOException $e) {
        $error = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
    }
}

// جلب النوادي
$clubs = $pdo->query("SELECT id, name FROM clubs ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>➕ إضافة سباح جديد</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            padding: 20px;
            background: #f8f9fa;
        }
        form {
            background: white;
            padding: 20px;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 12px;
            margin-bottom: 15px;
            text-align: center;
            border-radius: 6px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
  <ul>
    <li><a href="index.php">🏠 الصفحة الرئيسية</a></li>
    <li><a href="view_swimmers.php">🏊 قائمة السباحين</a></li>
    <li><a href="manage_clubs.php">🏢 إدارة النوادي</a></li>
  </ul>
</nav>
<h2 style="text-align:center;">➕ إضافة سباح جديد</h2>

<?php if ($success): ?>
    <div class="message success"><?= $success ?></div>
<?php elseif ($error): ?>
    <div class="message error"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <label>اسم السباح:</label>
    <input type="text" name="name" required>

    <label>الجنس:</label>
    <select name="gender" required>
        <option value="">اختر</option>
        <option value="ذكر">ذكر</option>
        <option value="أنثى">أنثى</option>
    </select>

    <label>تاريخ الميلاد:</label>
    <input type="date" name="birth_date" required>

    <label>الفئة العمرية:</label>
    <select name="age_category" required>
        <option value="">اختر</option>
        <option value="Ecole 1">Ecole 1</option>
        <option value="Ecole 2">Ecole 2</option>
        <option value="Poussins">Poussins</option>
        <option value="Benjamin1">Benjamin1</option>
        <option value="Benjamin2">Benjamin2</option>
        <option value="Minimes">Minimes</option>
        <option value="Juny">Juny</option>
    </select>

    <label>اسم النادي:</label>
    <select name="club_id" required>
        <option value="">اختر النادي</option>
        <?php foreach ($clubs as $club): ?>
            <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">💾 حفظ</button>
</form>

</body>
</html>
