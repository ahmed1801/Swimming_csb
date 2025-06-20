<?php
require_once '../db.php';
require_once '../vendor/autoload.php';

use Mpdf\Mpdf;

// المعرف الصحيح من الرابط
$group_id = $_GET['group'] ?? null;

if (!$group_id) {
    die("رقم المجموعة غير موجود.");
}

// جلب بيانات المجموعة
$stmt = $pdo->prepare("SELECT * FROM race_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("المجموعة غير موجودة.");
}

// جلب النتائج
$stmt = $pdo->prepare("
    SELECT s.name AS swimmer_name, c.name AS club_name, c.logo, r.rank, r.time_record
    FROM results r
    JOIN swimmers s ON r.swimmer_id = s.id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE r.race_group_id = ?
    ORDER BY r.rank ASC
");
$stmt->execute([$group_id]);
$results = $stmt->fetchAll();

// إنشاء ملف PDF
$mpdf = new Mpdf([
    'default_font' => 'dejavusans',
    'mode' => 'utf-8',
    'orientation' => 'P',
    'format' => 'A4'
]);

$mpdf->SetDirectionality('rtl');

// شعار الاتحاد
$union_logo = file_exists('../logos/union.png') ? '<img src="../logos/union.png" style="width:80px; float:right;">' : '';
$club_stamp = file_exists('../logos/stamp.png') ? '<img src="../logos/stamp.png" style="width:100px;">' : '';

$html = '
<div style="text-align:center; margin-bottom:20px;">
    ' . $union_logo . '
    <h2 style="margin: 0;">نتائج سباق السباحة</h2>
    <p>
         <strong>' . htmlspecialchars($group['swim_type']) . '</strong> |
         <strong>' . htmlspecialchars($group['distance']) . '</strong> |
         <strong>' . htmlspecialchars($group['age_category']) . '</strong> |
         <strong>' . htmlspecialchars($group['gender']) . '</strong><br>
        📅 <strong>' . date('Y-m-d', strtotime($group['created_at'])) . '</strong>
    </p>
</div>';

$html .= '
<table border="1" cellpadding="8" style="width:100%; border-collapse:collapse; font-size: 14px;">
<thead style="background-color:#f0f0f0;">
    <tr>
        <th>الترتيب</th>
        <th>اسم السباح</th>
        <th>النادي</th>
        <th>الوقت</th>
    </tr>
</thead>
<tbody>';

if ($results) {
    foreach ($results as $res) {
        $medal = match ($res['rank']) {
            1 => '🥇',
            2 => '🥈',
            3 => '🥉',
            default => ''
        };

        $club_logo = $res['logo'] && file_exists('../' . $res['logo']) 
            ? '<img src="../' . $res['logo'] . '" style="height:25px; vertical-align:middle; margin-left:5px;">' 
            : '';

        $html .= '<tr>
            <td>' . htmlspecialchars($res['rank']) . ' ' . $medal . '</td>
            <td>' . htmlspecialchars($res['swimmer_name']) . '</td>
            <td>' . $club_logo . htmlspecialchars($res['club_name']) . '</td>
            <td>' . htmlspecialchars($res['time_record']) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="4" style="text-align:center;">❌ لا توجد نتائج.</td></tr>';
}

$html .= '</tbody></table>';

// توقيع وختم
$html .= '
<br><br>
<table style="width:100%; margin-top:30px;">
<tr>
    <td style="text-align:right;">
        <strong>توقيع المدرب</strong><br><br>
        _______________
    </td>
    <td style="text-align:left;">
        <strong>ختم النادي</strong><br><br>' . $club_stamp . '
    </td>
</tr>
</table>';

// إخراج PDF
$mpdf->WriteHTML($html);
$mpdf->Output("نتائج_سباق_رقم_$group_id.pdf", "I");
