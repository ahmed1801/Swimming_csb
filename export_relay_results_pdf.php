<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

$group_id = $_GET['group'] ?? null;
if (!$group_id) die("رقم السباق غير صالح.");

$stmt = $pdo->prepare("SELECT * FROM race_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();
if (!$group || !$group['is_relay']) die("❌ هذا السباق ليس تتابع.");

// جلب الفرق والسباحين
$stmt = $pdo->prepare("
    SELECT c.id AS club_id, c.name AS club_name, c.logo,
           s.name AS swimmer_name, s.id AS swimmer_id
    FROM races r
    JOIN swimmers s ON r.swimmer_id = s.id
    JOIN clubs c ON s.club_id = c.id
    WHERE r.race_group_id = ?
    ORDER BY c.name, s.name
");
$stmt->execute([$group_id]);
$rows = $stmt->fetchAll();

$teams = [];
foreach ($rows as $row) {
    $teams[$row['club_id']]['club_name'] = $row['club_name'];
    $teams[$row['club_id']]['logo'] = $row['logo'];
    $teams[$row['club_id']]['swimmers'][] = $row['swimmer_name'];
}

// جلب النتائج
$stmt = $pdo->prepare("
    SELECT s.club_id, r.time_record
    FROM results r
    JOIN swimmers s ON r.swimmer_id = s.id
    WHERE r.race_group_id = ?
    GROUP BY s.club_id
");
$stmt->execute([$group_id]);
$results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// ترتيب الفرق حسب الوقت
$sorted = $results;
uasort($sorted, fn($a, $b) => strtotime($a) <=> strtotime($b));
$ranking = array_keys($sorted);

// إعداد PDF
$mpdf = new Mpdf(['default_font' => 'dejavusans']);
$mpdf->SetDirectionality('rtl');

$html = "<h2 style='text-align:center;'>نتائج سباق التتابع - {$group['swim_type']} {$group['distance']}</h2>";
$html .= "<table border='1' cellpadding='8' style='width:100%; border-collapse: collapse;'>";
$html .= "<thead><tr>
    <th>🏅 المرتبة</th>
    <th>النادي</th>
    <th>السباحون</th>
    <th>⏱️ الوقت</th>
</tr></thead><tbody>";

$rank_index = 1;
foreach ($ranking as $club_id) {
    $medal = $rank_index == 1 ? '🥇' : ($rank_index == 2 ? '🥈' : ($rank_index == 3 ? '🥉' : ''));
    $club = $teams[$club_id];
    $html .= "<tr>
        <td>{$rank_index} {$medal}</td>
        <td>{$club['club_name']}</td>
        <td>" . implode('<br>', array_map('htmlspecialchars', $club['swimmers'])) . "</td>
        <td>{$results[$club_id]}</td>
    </tr>";
    $rank_index++;
}

$html .= "</tbody></table>";
$mpdf->WriteHTML($html);
$mpdf->Output("Relay_Results_Group_$group_id.pdf", 'I');
