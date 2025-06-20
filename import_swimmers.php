<?php
require 'db.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === 0) {
        $file = $_FILES['excel_file']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $pdo->beginTransaction();

            for ($i = 1; $i < count($rows); $i++) {
                $name         = trim($rows[$i][0]);
                $gender       = trim($rows[$i][1]);
                $birth_date   = trim($rows[$i][2]);
                $club_name    = trim($rows[$i][3]);
                $age_category = trim($rows[$i][4]);

                if ($name && $gender && $birth_date && $club_name && $age_category) {
                    // تحقق من وجود النادي
                    $club_stmt = $pdo->prepare("SELECT id FROM clubs WHERE name = ?");
                    $club_stmt->execute([$club_name]);
                    $club_id = $club_stmt->fetchColumn();

                    // إنشاء النادي إذا لم يكن موجودًا
                    if (!$club_id) {
                        $insert_club = $pdo->prepare("INSERT INTO clubs (name) VALUES (?)");
                        $insert_club->execute([$club_name]);
                        $club_id = $pdo->lastInsertId();
                    }

                    // التحقق من وجود السباح مسبقًا
                    $check_stmt = $pdo->prepare("SELECT id FROM swimmers WHERE name = ? AND birth_date = ?");
                    $check_stmt->execute([$name, $birth_date]);

                    if ($check_stmt->rowCount() === 0) {
                        $insert_stmt = $pdo->prepare("
                            INSERT INTO swimmers (name, gender, birth_date, age_category, club_id)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $insert_stmt->execute([$name, $gender, $birth_date, $age_category, $club_id]);
                    }
                }
            }

            $pdo->commit();
            $message = "✅ تم استيراد السباحين بنجاح.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "❌ خطأ أثناء الاستيراد: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ يرجى اختيار ملف Excel صحيح.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>استيراد السباحين</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        body { font-family: Arial; padding: 20px; background: #f8f8f8; direction: rtl; }
        .message { margin-bottom: 15px; font-weight: bold; }
        .success { color: green; }
        .error { color: red; }
        form { background: white; padding: 20px; border-radius: 10px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type="file"] { margin: 10px 0; display: block; }
        button { padding: 10px 20px; background-color: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>

<nav class="navbar">
    <ul>
        <li><a href="index.php">🏠 الرئيسية</a></li>
        <li><a href="view_swimmers.php">👥 قائمة السباحين</a></li>
        <li><a href="prepare_race.php">🏁 إعداد سباق</a></li>
    </ul>
</nav>

<h2>🧾 استيراد السباحين من Excel</h2>

<?php if ($message): ?>
    <div class="message <?= str_starts_with($message, '✅') ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>اختر ملف Excel:</label>
    <input type="file" name="excel_file" accept=".xls,.xlsx" required>
    <button type="submit">استيراد</button>
    <br><hr><br>
    <p style="background-color:rgb(66, 223, 158);" >تنبيه : يجب على الاعمدة ان تكون عل هذا الشكل </p>
    <hr><br>
    <p>اسم السباح	\ الجنس	\ تاريخ الميلاد	\اسم النادي	\ الفئة العمرية\ </p>
    <hr><br>
    <a href="index.php" class="btn btn-primary stretched-link">🏠 الصفحة الرئيسية</a>
    
</form>

</body>
</html>
