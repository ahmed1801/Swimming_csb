<?php
require 'db.php';

$pdo->exec("DELETE FROM swimmers");

header('Location: view_swimmers.php?msg=all_deleted');
exit;
