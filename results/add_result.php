<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $race_group_id = $_POST['race_group_id'];

    foreach ($_POST['results'] as $swimmer_id => $data) {
        $time = trim($data['time']);
        $rank = trim($data['rank']);

        if ($time !== '' && $rank !== '') {
            // التحويل إلى TIME بصيغة HH:MM:SS
            $formatted = convertToTimeFormat($time);
            $stmt = $pdo->prepare("INSERT INTO results (time_record, rank, swimmer_id, race_group_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$formatted, $rank, $swimmer_id, $race_group_id]);
        }
    }

    $_SESSION['message'] = "✅ تم حفظ النتائج بنجاح.";
       header("Location: view_results.php?highlight=$race_group_id");
       exit;

}

// جلب السباق
$race_group_id = $_GET['group'] ?? 0;

$stmt = $pdo->prepare("
    SELECT r.id, s.id AS swimmer_id, s.name AS swimmer_name, s.club_name
    FROM races r
    JOIN swimmers s ON r.swimmer_id = s.id
    WHERE r.race_group_id = ?
    ORDER BY s.name ASC
");
$stmt->execute([$race_group_id]);
$swimmers = $stmt->fetchAll();

function convertToTimeFormat($val) {
    $val = preg_replace('/[^0-9]/', '', $val);
    $val = str_pad($val, 6, '0', STR_PAD_LEFT);
    $min = intval(substr($val, 0, strlen($val) - 4));
    if ($min > 7) return '07:59:99'; // حماية ضد أخطاء كبيرة
    $sec = substr($val, -4, 2);
    $part = substr($val, -2);
    return sprintf("%01d:%02d:%02d", $min, $sec, $part); // 1:23:57
}

if (!isset($_GET['group']) || !is_numeric($_GET['group'])) {
    header("Location: ../all_races.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدخال نتائج السباق</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
    .gold   { background-color: #fff8dc !important; font-weight: bold; }
    .silver { background-color: #f0f0f0 !important; font-weight: bold; }
    .bronze { background-color: #fbe5d6 !important; font-weight: bold; }
    .medal-icon {
    margin-right: 5px;
    font-size: 1.2em;
}

</style>

</head>
<body>
<!-- شريط التنقل -->
<nav class="navbar">
    <ul>
        <li><a href="index.php">🏠 الصفحة الرئيسية</a></li>
       
        <li><a href="prepare_race.php">🏁 إعداد سباق</a></li>
        <li><a href="view_results.php">📊 عرض النتائج</a></li>
    </ul>
</nav>
<h2>➕ إدخال نتائج سباق</h2>

<?php if (isset($_SESSION['message'])): ?>
    <div class="message success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<a href="../all_races.php" class="add-new">⬅️ العودة لكل السباقات</a>

<form method="post">
    <input type="hidden" name="race_group_id" value="<?= $race_group_id ?>">

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم السباح</th>
                <th>النادي</th>
                <th>الوقت</th>
                <th>المرتبة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($swimmers as $i => $swimmer): ?>
<tr data-swimmer-id="<?= $swimmer['swimmer_id'] ?>">
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($swimmer['swimmer_name']) ?></td>
    <td><?= htmlspecialchars($swimmer['club_name']) ?></td>
    <td>
        <input type="text"
               name="results[<?= $swimmer['swimmer_id'] ?>][time]"
               class="time-input"
               placeholder="مثال: 12357"
               maxlength="6"
               oninput="limitInputLength(this); updateLiveRanks();"
               onblur="formatTime(this); updateLiveRanks();">
    </td>
    <td>
        <input type="number" readonly
               name="results[<?= $swimmer['swimmer_id'] ?>][rank]"
               class="rank-field" placeholder="ترتيب تلقائي">
    </td>
</tr>
<?php endforeach; ?>

        </tbody>
    </table>

    <button type="submit">💾 حفظ النتائج</button>
</form>

<script>
function limitInputLength(input) {
    input.value = input.value.replace(/[^0-9]/g, '').substring(0, 6);
}

function formatTime(input) {
    let val = input.value.replace(/[^0-9]/g, '');
    if (val.length >= 3 && val.length <= 6) {
        val = val.padStart(6, '0');
        let min = parseInt(val.slice(0, val.length - 4), 10);
        let sec = val.slice(val.length - 4, val.length - 2);
        let part = val.slice(-2);
        input.value = `${min}:${sec}:${part}`;
    }
}
</script>
<script>
function limitInputLength(input) {
    input.value = input.value.replace(/[^0-9]/g, '').substring(0, 6);
}

function formatTime(input) {
    let val = input.value.replace(/[^0-9]/g, '');
    if (val.length >= 3 && val.length <= 6) {
        val = val.padStart(6, '0');
        let min = parseInt(val.slice(0, val.length - 4), 10);
        let sec = val.slice(val.length - 4, val.length - 2);
        let part = val.slice(-2);
        input.value = `${min}:${sec}:${part}`;
    }
}

function convertToSeconds(timeStr) {
    if (!timeStr.includes(':')) return null;
    let parts = timeStr.split(':');
    if (parts.length !== 3) return null;
    let min = parseInt(parts[0]) || 0;
    let sec = parseInt(parts[1]) || 0;
    let hundredths = parseInt(parts[2]) || 0;
    return min * 60 + sec + hundredths / 100;
}


function updateLiveRanks() {
    const rows = document.querySelectorAll("tbody tr");
    let data = [];

    // جمع الأوقات الصالحة
    rows.forEach(row => {
        row.classList.remove("gold", "silver", "bronze"); // إزالة الألوان السابقة
        const timeInput = row.querySelector(".time-input");
        const time = timeInput.value.trim();
        const seconds = convertToSeconds(time);
        if (seconds !== null && !isNaN(seconds)) {
            data.push({ row, seconds });
        }
    });

    // الترتيب حسب الوقت
    data.sort((a, b) => a.seconds - b.seconds);

    // تحديث الرتبة والتلوين
    data.forEach((item, index) => {
        const rankField = item.row.querySelector(".rank-field");
        rankField.value = index + 1;

        // تلوين الصفوف حسب المراتب
        if (index === 0) {
            item.row.classList.add("gold");
        } else if (index === 1) {
            item.row.classList.add("silver");
        } else if (index === 2) {
            item.row.classList.add("bronze");
        }
    });
}
</script>

</body>
</html>
