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
    <link rel="stylesheet" href="<?= e(appUrl("assets/css/app.css")); ?>">
</head>

<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="wireframe-shell">
        <header class="site-header">
            <div class="header-shell">
                <a class="brand" href="<?= e(appUrl("index.php")); ?>" aria-label="ConcertHelper home">
                    <img class="brand-logo" src="<?= e(appUrl("assets/images/logo.jpg")); ?>" alt="ConcertHelper logo" width="48" height="48">
                    <span class="brand-name">McMaster Concert Band</span>
                </a>

                <nav class="app-nav wireframe-nav" aria-label="ConcertHelper navigation">
                    <a class="<?= e(appNavClass("home", $activePage)); ?>" href="<?= e(appUrl("index.php")); ?>">Home</a>
                    <a class="<?= e(appNavClass("members", $activePage)); ?>" href="<?= e(appUrl("members.php")); ?>">Members</a>
                    <a class="<?= e(appNavClass("concerts", $activePage)); ?>" href="<?= e(appUrl("concerts.php")); ?>">Concerts</a>
                    <?php if ($currentRole !== null): ?>
                        <a class="<?= e(appNavClass("dashboard", $activePage)); ?>" href="<?= e(currentDashboardUrl()); ?>">Dashboard</a>
                        <a class="nav-link" href="<?= e(appUrl("logout.php")); ?>">Logout</a>
                    <?php else: ?>
                        <a class="<?= e(appNavClass("login", $activePage)); ?>" href="<?= e(appUrl("login.php")); ?>">Login</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <main id="main" class="page-shell">
