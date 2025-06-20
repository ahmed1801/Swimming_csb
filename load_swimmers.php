<?php
require 'db.php';

$age = $_POST['age_category'] ?? '';
$gender = $_POST['gender'] ?? '';

if (!$age || !$gender) {
    echo "<div style='color: red;'>⚠️ الرجاء اختيار الفئة العمرية والجنس أولاً.</div>";
    exit;
}

$stmt = $pdo->prepare("SELECT s.id, s.name, s.club_name, c.logo
                       FROM swimmers s
                       LEFT JOIN clubs c ON s.club_id = c.id
                       WHERE s.age_category = ? AND s.gender = ?
                       ORDER BY s.name ASC");
$stmt->execute([$age, $gender]);
$swimmers = $stmt->fetchAll();

$count = count($swimmers);

if ($count === 0) {
    echo "<div style='color: red;'>🚫 لا يوجد سباحين مطابقين للمعايير المحددة.</div>";
    exit;
}
?>

<h3>عدد السباحين المطابقين: <?= $count ?></h3>

<table>
    <thead>
        <tr>
            <th>اختيار</th>
            <th>الاسم</th>
            <th>النادي</th>
            <th>الشعار</th>
            <th>عرض</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($swimmers as $swimmer): ?>
            <tr>
                <td>
                    <input type="checkbox" name="selected_swimmers[]" value="<?= $swimmer['id'] ?>">
                </td>
                <td><?= htmlspecialchars($swimmer['name']) ?></td>
                <td><?= htmlspecialchars($swimmer['club_name'] ?? 'غير معروف') ?></td>
                <td>
                    <?php if (!empty($swimmer['logo']) && file_exists($swimmer['logo'])): ?>
                        <img src="<?= $swimmer['logo'] ?>" alt="شعار" style="height:40px;">
                    <?php else: ?>
                        لا يوجد
                    <?php endif; ?>
                </td>
                <td>
                    <a href="view_swimmer_details.php?id=<?= $swimmer['id'] ?>" class="button view" style="background:#28a745;">عرض</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
