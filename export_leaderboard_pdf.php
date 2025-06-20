<?php
require 'db.php';
require 'vendor/autoload.php';

use Mpdf\Mpdf;

$selected_age = $_GET['age'] ?? '';
$selected_club = $_GET['club'] ?? '';

$query = "
    SELECT 
        r.swimmer_id,
        s.name AS swimmer_name,
        s.age_category,
        c.name AS club_name,
        r.rank
    FROM results r
    JOIN swimmers s ON r.swimmer_id = s.id
    LEFT JOIN clubs c ON s.club_id = c.id
    WHERE 1
";

$params = [];
if ($selected_age !== '') {
    $query .= " AND s.age_category = ?";
    $params[] = $selected_age;
}
if ($selected_club !== '') {
    $query .= " AND c.id = ?";
    $params[] = $selected_club;
}

$query .= " ORDER BY s.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getPoints($rank) {
    return match((int)$rank) {
        1 => 10, 2 => 8, 3 => 6, 4 => 4, 5 => 3, 6 => 2,
        default => 1
    };
}

$leaderboard = [];
foreach ($results as $row) {
    $id = $row['swimmer_id'];
    if (!isset($leaderboard[$id])) {
        $leaderboard[$id] = [
            'name' => $row['swimmer_name'],
            'club' => $row['club_name'],
            'age_category' => $row['age_category'],
            'points' => 0,
            'races' => 0
        ];
    }
    $leaderboard[$id]['points'] += getPoints($row['rank']);
    $leaderboard[$id]['races'] += 1;
}

usort($leaderboard, fn($a, $b) => $b['points'] <=> $a['points']);

$mpdf = new Mpdf(['default_font' => 'dejavusans']);
$mpdf->SetDirectionality('rtl');

$html = '<h2 style="text-align:center;">🏆 تصنيفات أفضل السباحين</h2>';
if ($selected_age) $html .= "<p style='text-align:center;'>الفئة: <strong>$selected_age</strong></p>";
if ($selected_club) {
    $clubName = $pdo->query("SELECT name FROM clubs WHERE id = $selected_club")->fetchColumn();
    $html .= "<p style='text-align:center;'>النادي: <strong>$clubName</strong></p>";
}

$html .= "<table border='1' cellpadding='8' style='width:100%; border-collapse:collapse;'>
    <thead>
        <tr style='background:#f0f0f0;'>
            <th>الترتيب</th>
            <th>الاسم</th>
            <th>النادي</th>
            <th>الفئة</th>
            <th>عدد السباقات</th>
            <th>النقاط</th>
        </tr>
    </thead><tbody>";

foreach ($leaderboard as $i => $row) {
    $html .= "<tr>
        <td>" . ($i + 1) . "</td>
        <td>" . htmlspecialchars($row['name']) . "</td>
        <td>" . htmlspecialchars($row['club']) . "</td>
        <td>" . htmlspecialchars($row['age_category']) . "</td>
        <td>" . $row['races'] . "</td>
        <td>" . $row['points'] . "</td>
    </tr>";
}
$html .= "</tbody></table>";

$mpdf->WriteHTML($html);
$mpdf->Output("leaderboard.pdf", "I");
