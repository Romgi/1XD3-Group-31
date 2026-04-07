<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

$pageTitle = "Member Dashboard";
$activePage = "dashboard";

requireRole([ROLE_MEMBER]);

require __DIR__ . "/includes/header.php";
?>
<section class="todo-card">
    <h1>TODO</h1>
    <p>This page still needs to be completed.</p>
</section>
<?php require __DIR__ . "/includes/footer.php"; ?>
