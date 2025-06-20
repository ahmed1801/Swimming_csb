<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=swimming_club;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// حفظ بيانات السباق
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_race"])) {
    $swim_type = $_POST["swim_type"];
    $distance = $_POST["distance"];
    $age_category = $_POST["age_category"];
    $gender = $_POST["gender"];
    $selected_swimmers = $_POST["selected_swimmers"] ?? [];

    $is_relay = ($swim_type === "التتابع المتنوع");

    // التحقق من عدد السباحين
    if (count($selected_swimmers) < 1) {
        $_SESSION['error'] = "⚠️ يجب اختيار سباح واحد على الأقل.";
    } else {
        try {
            // تحقق من التكرار: لا يمكن مشاركة السباح في نفس النوع والمسافة أكثر من مرة
            $placeholders = implode(',', array_fill(0, count($selected_swimmers), '?'));
            $checkStmt = $pdo->prepare("
                SELECT swimmer_id FROM races r
                JOIN race_groups rg ON r.race_group_id = rg.id
                WHERE rg.swim_type = ? AND rg.distance = ? AND r.swimmer_id IN ($placeholders)
            ");
            $checkStmt->execute(array_merge([$swim_type, $distance], $selected_swimmers));
            $duplicates = $checkStmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($duplicates) > 0) {
                $_SESSION['error'] = "❌ بعض السباحين مشاركون مسبقًا في نفس نوع السباحة والمسافة.";
                header("Location: prepare_race.php");
                exit;
            }

            // إذا كان تتابع: تحقق من الفرق المكونة من 4 سباحين من نفس النادي
            if ($is_relay) {
                $club_counts = [];
                foreach ($selected_swimmers as $swimmer_id) {
                    $stmt = $pdo->prepare("SELECT club_id FROM swimmers WHERE id = ?");
                    $stmt->execute([$swimmer_id]);
                    $club_id = $stmt->fetchColumn();
                    if ($club_id) {
                        $club_counts[$club_id][] = $swimmer_id;
                    }
                }

                foreach ($club_counts as $club_id => $swimmer_ids) {
                    if (count($swimmer_ids) % 4 !== 0) {
                        $_SESSION['error'] = "❌ يجب اختيار فرق مكونة من 4 سباحين من نفس النادي (نادي رقم $club_id).";
                        header("Location: prepare_race.php");
                        exit;
                    }
                }
            }

            // حفظ السباق
            $stmt = $pdo->prepare("INSERT INTO race_groups (swim_type, distance, age_category, gender, is_relay) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$swim_type, $distance, $age_category, $gender, $is_relay ? 1 : 0]);
            $race_group_id = $pdo->lastInsertId();

            // ربط السباحين بالسباق
            $stmt2 = $pdo->prepare("INSERT INTO races (race_group_id, swimmer_id) VALUES (?, ?)");
            foreach ($selected_swimmers as $swimmer_id) {
                $stmt2->execute([$race_group_id, $swimmer_id]);
            }

            $_SESSION['success'] = "✅ تم حفظ السباق بنجاح.";
        } catch (Exception $e) {
            $_SESSION['error'] = "❌ خطأ أثناء الحفظ: " . $e->getMessage();
        }
    }

    header("Location: prepare_race.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعداد سباق</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 20px; direction: rtl; text-align: center; }
        form, table { background: #fff; padding: 20px; border-radius: 10px; margin: 10px auto; width: 90%; }
        select, input[type=submit] { padding: 8px; margin: 8px; font-size: 16px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin: 10px auto; width: 80%; border-radius: 5px; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin: 10px auto; width: 80%; border-radius: 5px; }
    </style>
</head>
<body>

<nav class="navbar">
    <ul>
        <li><a href="index.php">🏠 الصفحة الرئيسية</a></li>
        <li><a href="all_races.php">📋 عرض جميع السباقات</a></li>
        <li><a href="view_results.php">📊 عرض النتائج</a></li>
        <li><a href="leaderboard.php">🥇 تصنيفات السباحين</a></li>
    </ul>
</nav>

<h2>📝 إعداد سباق جديد</h2>

<?php if (isset($_SESSION['success'])): ?>
    <div class="message success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php elseif (isset($_SESSION['error'])): ?>
    <div class="message error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<form method="POST" id="race_form">
    <label>نوع السباحة:</label>
    <select name="swim_type" id="swim_type" required>
        <option value="">اختر</option>
        <option>سباحة حرة</option>
        <option>سباحة على الصدر</option>
        <option>سباحة على الظهر</option>
        <option>سباحة الفراشة</option>
        <option>الفردي المتنوع</option>
        <option>التتابع المتنوع</option>
    </select>

    <label>المسافة:</label>
    <select name="distance" required>
        <option value="">اختر</option>
        <option>25m</option>
        <option>50m</option>
        <option>100m</option>
        <option>200m</option>
        <option>400m</option>
        <option>800m</option>
    </select>

    <label>الفئة العمرية:</label>
    <select name="age_category" id="age_category" required>
        <option value="">اختر</option>
        <option>Ecole 1</option>
        <option>Ecole 2</option>
        <option>Poussins</option>
        <option>Benjamin1</option>
        <option>Benjamin2</option>
        <option>Minimes</option>
        <option>Juny</option>
    </select>

    <label>الجنس:</label>
    <select name="gender" id="gender" required>
        <option value="">اختر</option>
        <option value="ذكر">ذكر</option>
        <option value="أنثى">أنثى</option>
    </select>

    <div id="swimmer_table">
        <!-- تحميل السباحين حسب الفئة والجنس -->
    </div>

    <input type="submit" name="submit_race" value="💾 حفظ السباق">
</form>

<script>
function loadSwimmers() {
    const age = document.getElementById('age_category').value;
    const gender = document.getElementById('gender').value;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "load_swimmers.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function() {
        document.getElementById("swimmer_table").innerHTML = this.responseText;
    };

    xhr.send("age_category=" + encodeURIComponent(age) + "&gender=" + encodeURIComponent(gender));
}

document.getElementById('age_category').addEventListener('change', loadSwimmers);
document.getElementById('gender').addEventListener('change', loadSwimmers);
window.onload = loadSwimmers;
</script>

</body>
</html>
