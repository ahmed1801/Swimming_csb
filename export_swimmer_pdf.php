<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

$swimmer_id = $_GET['id'] ?? null;

if (!$swimmer_id) {
    die("معرف غير صالح");
}

// جلب بيانات السباح
$stmt = $pdo->prepare("SELECT s.*, c.name AS club_name, c.logo FROM swimmers s LEFT JOIN clubs c ON s.club_id = c.id WHERE s.id = ?");
$stmt->execute([$swimmer_id]);
$swimmer = $stmt->fetch();

if (!$swimmer) die("السباح غير موجود");

// جلب النتائج
$stmt = $pdo->prepare("
    SELECT res.*, rg.swim_type, rg.distance, rg.age_category, rg.gender
    FROM results res
    JOIN race_groups rg ON res.race_group_id = rg.id
    WHERE res.swimmer_id = ?
    ORDER BY res.rank ASC
");
$stmt->execute([$swimmer_id]);
$results = $stmt->fetchAll();

$mpdf = new Mpdf(['default_font' => 'dejavusans']);
$mpdf->WriteHTML('<style>body { direction: rtl; font-family: dejavusans; }</style>');

// شعارات وترويسة
$federation_logo = 'logos/22.png';
$coach_sign = 'assets/coach_sign.png';
$club_stamp = 'assets/club_stamp.png';

// الشعار الخاص بالنادي
$clubLogoHtml = '';
if (!empty($swimmer['logo']) && file_exists($swimmer['logo'])) {
    $clubLogoHtml = "<img src='{$swimmer['logo']}' style='width:70px; height:70px; border-radius:50%;'>";
}

// الترويسة
$header = "<div style='text-align:center; margin-bottom:20px; border-bottom:2px solid #000; padding-bottom:10px;'>
    <img src='$federation_logo' style='height:80px;'><br>
    <strong style='font-size:18px;'> الرابطة الولائية للسباحة بشار </strong>
</div>";

$html = $header;

$html .= "<div style='text-align:center; margin-bottom:10px;'>
            $clubLogoHtml
            <h2>الملف الشخصي للسباح</h2>
         </div>";

$html .= "<table border='1' cellpadding='8' style='width:100%; border-collapse: collapse; margin-bottom:20px;'>";
$html .= "<tr><th>الاسم</th><td>{$swimmer['name']}</td></tr>";
$html .= "<tr><th>الجنس</th><td>{$swimmer['gender']}</td></tr>";
$html .= "<tr><th>تاريخ الميلاد</th><td>{$swimmer['birth_date']}</td></tr>";
$html .= "<tr><th>النادي</th><td>{$swimmer['club_name']}</td></tr>";
$html .= "<tr><th>الفئة العمرية</th><td>{$swimmer['age_category']}</td></tr>";
$html .= "</table>";

if ($results) {
    $html .= "<h3 style='text-align:center;'>نتائج السباقات</h3>";
    $html .= "<table border='1' cellpadding='6' style='width:100%; border-collapse: collapse;'>
        <thead><tr>
            <th>نوع السباحة</th><th>المسافة</th><th>الفئة</th><th>الجنس</th><th>الوقت</th><th>المرتبة</th><th>ملاحظة</th>
        </tr></thead><tbody>";

    foreach ($results as $res) {
        $medal = '';
        if ($res['rank'] == 1) $medal = '🥇';
        elseif ($res['rank'] == 2) $medal = '🥈';
        elseif ($res['rank'] == 3) $medal = '🥉';

        $html .= "<tr>
            <td>{$res['swim_type']}</td>
            <td>{$res['distance']}</td>
            <td>{$res['age_category']}</td>
            <td>{$res['gender']}</td>
            <td>{$res['time_record']}</td>
            <td>{$res['rank']} $medal</td>
            <td>{$res['note']}</td>
        </tr>";
    }

    $html .= "</tbody></table>";
} else {
    $html .= "<div style='margin-top:20px;'>❌ لا توجد نتائج مسجلة لهذا السباح.</div>";
}

// توقيع وختم
$html .= ' <div class="footer-signature" style= "margin-top: 30px; display: flex; justify-content: space-between; font-weight: bold;" >
        <div style= "width: 45%; text-align: center; padding: 15px; border-top: 1px dashed #999;">ختم النادي</div>
    </div>
</div>';

$mpdf->WriteHTML($html);
$mpdf->Output("نتائج_{$swimmer['name']}.pdf", "I");


