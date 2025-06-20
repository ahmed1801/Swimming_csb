<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

$swim_type = $_GET['swim_type'] ?? '';
$distance = $_GET['distance'] ?? '';
$age_category = $_GET['age_category'] ?? '';

$conditions = [];
$params = [];

if (!empty($swim_type)) {
    $conditions[] = "rg.swim_type = ?";
    $params[] = $swim_type;
}
if (!empty($distance)) {
    $conditions[] = "rg.distance = ?";
    $params[] = $distance;
}
if (!empty($age_category)) {
    $conditions[] = "rg.age_category = ?";
    $params[] = $age_category;
}

$where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare("
    SELECT s.name AS swimmer_name, c.name AS club_name, res.time_record, res.note
    FROM results res
    JOIN swimmers s ON res.swimmer_id = s.id
    JOIN race_groups rg ON res.race_group_id = rg.id
    LEFT JOIN clubs c ON s.club_id = c.id
    $where
");
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

usort($results, function ($a, $b) {
    return floatval(str_replace([':', '.'], '', $a['time_record'])) <=> floatval(str_replace([':', '.'], '', $b['time_record']));
});

$mpdf = new Mpdf(['default_font' => 'dejavusans']);
$mpdf->SetDirectionality('rtl');
$html = '<h2 style="text-align:center;">📊 النتائج النهائية</h2>';
$html .= '<table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%;">
<thead><tr style="background:#333; color:white;">
    <th>الترتيب</th><th>السباح</th><th>النادي</th><th>الوقت</th><th>ملاحظة</th>
</tr></thead><tbody>';

foreach ($results as $i => $row) {
    $medal = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ''));
    $html .= "<tr>
        <td>" . ($i + 1) . " $medal</td>
        <td>" . htmlspecialchars($row['swimmer_name']) . "</td>
        <td>" . htmlspecialchars($row['club_name']) . "</td>
        <td>" . htmlspecialchars($row['time_record']) . "</td>
        <td>" . htmlspecialchars($row['note']) . "</td>
    </tr>";
}
$html .= '</tbody></table>';

$mpdf->WriteHTML($html);
$mpdf->Output('final_results.pdf', 'I');
