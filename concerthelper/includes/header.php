<?php
declare(strict_types=1);

require_once __DIR__ . "/app.php";

startAppSession();

$pageTitle = $pageTitle ?? APP_NAME;
$activePage = $activePage ?? "";
$currentRole = currentRole();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> | <?= e(APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>

<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="wireframe-shell">
        <h1 class="wireframe-title"><?= e($pageTitle); ?></h1>

        <header class="site-header">
            <div class="header-shell">
                <a class="brand" href="index.php" aria-label="ConcertHelper home">
                    <img class="brand-logo" src="images/logo.jpg" alt="ConcertHelper logo" width="48" height="48">
                    <span class="brand-name">McMaster Concert Band</span>
                </a>

                <nav class="app-nav wireframe-nav" aria-label="ConcertHelper navigation">
                    <a class="<?= e(appNavClass("home", $activePage)); ?>" href="index.php">Home</a>
                    <a class="<?= e(appNavClass("members", $activePage)); ?>" href="members.php">Members</a>
                    <a class="<?= e(appNavClass("concerts", $activePage)); ?>" href="concerts.php">Concerts</a>
                    <?php if ($currentRole !== null): ?>
                        <a class="<?= e(appNavClass("dashboard", $activePage)); ?>" href="<?= e(currentDashboardUrl()); ?>">Dashboard</a>
                    <?php else: ?>
                        <a class="<?= e(appNavClass("login", $activePage)); ?>" href="login.php">Login</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <main id="main" class="page-shell">
