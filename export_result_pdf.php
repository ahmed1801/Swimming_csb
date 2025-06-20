<?php
require_once '../vendor/autoload.php';
require_once '../db.php';

use Mpdf\Mpdf;

$race_group_id = $_GET['group'] ?? 0;

// جلب بيانات السباق
$stmt = $pdo->prepare("
    SELECT 
        s.name AS swimmer_name,
        c.name AS club_name,
        c.logo AS club_logo,
        r.time_record,
        r.rank,
        r.note,
        g.swim_type,
        g.distance,
        g.age_category,
        g.gender,
        g.created_at
    FROM results r
    JOIN swimmers s ON r.swimmer_id = s.id
    JOIN race_groups g ON r.race_group_id = g.id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE r.race_group_id = ?
    ORDER BY r.rank ASC
");
$stmt->execute([$race_group_id]);
$results = $stmt->fetchAll();

if (!$results) {
    die("لا توجد نتائج لهذا السباق.");
}

$meta = $results[0];

// HTML البداية
$html = '
<html lang="ar" dir="rtl">
<head>
<style>
    body { font-family: "dejavusans"; direction: rtl; }
    h2 { text-align: center; color: #333; }
    .meta { margin-bottom: 15px; font-weight: bold; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px; }
    th { background-color: #f2f2f2; }
    img.logo { max-height: 25px; }
    img.medal { width: 18px; vertical-align: middle; }
</style>
</head>
<body>
<h2>نتائج السباق</h2>

<div class="meta">
    🏊‍♂️ نوع السباحة: ' . htmlspecialchars($meta['swim_type']) . ' | 
    📏 المسافة: ' . htmlspecialchars($meta['distance']) . ' | 
    👥 الفئة: ' . htmlspecialchars($meta['age_category']) . ' | 
    ⚧ الجنس: ' . htmlspecialchars($meta['gender']) . ' | 
    🗓️ التاريخ: ' . date('Y-m-d', strtotime($meta['created_at'])) . '
</div>

<table>
    <thead>
        <tr>
            <th>الترتيب</th>
            <th>السباح</th>
            <th>النادي</th>
            <th>الوقت</th>
            <th>ملاحظة</th>
        </tr>
    </thead>
    <tbody>
';

foreach ($results as $res) {
    $medal = '';
    switch ($res['rank']) {
        case 1:
            $medal = '<img src="../assets/medals/gold.png" class="medal">';
            break;
        case 2:
            $medal = '<img src="../logos/3.png" class="medal">';
            break;
        case 3:
            $medal = '<img src="../logos/3.png" class="medal">';
            break;
    }

    $clubLogo = $res['club_logo'] && file_exists('../' . $res['club_logo']) 
        ? '<img src="../' . $res['club_logo'] . '" class="logo"> ' 
        : '';

    $html .= '<tr>
        <td>' . $res['rank'] . ' ' . $medal . '</td>
        <td>' . htmlspecialchars($res['swimmer_name']) . '</td>
        <td>' . $clubLogo . htmlspecialchars($res['club_name']) . '</td>
        <td>' . htmlspecialchars($res['time_record']) . '</td>
        <td>' . htmlspecialchars($res['note']) . '</td>
    </tr>';
}

$html .= '
    </tbody>
</table>
</body>
</html>
';

// توليد PDF
$mpdf = new Mpdf(['default_font' => 'dejavusans']);
$mpdf->WriteHTML($html);
$mpdf->Output("race_results_group_$race_group_id.pdf", "I");
exit;
