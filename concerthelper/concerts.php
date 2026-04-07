<?php
require_once "includes/app.php";

$pageTitle = "Concerts";
$activePage = "concerts";

$error = false;

try {
    $upcomingStmt = getConcertsByStatus("upcoming");
    $pastStmt = getConcertsByStatus("past");
} catch (Exception $e) {
    $error = true;
}

require "includes/header.php";
?>

<h1>Concerts</h1>

<?php
if ($error) {
    echo "<p>Something went wrong loading concerts.</p>";
} else {
?>

<h2>Upcoming Concerts</h2>

<?php
$hasUpcoming = false;

while ($row = $upcomingStmt->fetch()) {
    $hasUpcoming = true;

    $title = $row["title"];
    $desc = $row["description"];
    $date = $row["concert_date"];
    $time = $row["start_time"];
    $location = $row["location"];
?>

<div>
    <h3><?= $title ?></h3>

    <p><?= $desc ?></p>

    <p>
        Date: <?= $date ?><br>
        Time: <?= $time ? $time : "TBA" ?><br>
        Location: <?= $location ? $location : "TBA" ?>
    </p>
</div>

<hr>

<?php
}

if (!$hasUpcoming) {
    echo "<p>No upcoming concerts.</p>";
}
?>

<h2>Past Performances</h2>

<?php
$hasPast = false;

while ($row = $pastStmt->fetch()) {
    $hasPast = true;

    $title = $row["title"];
    $desc = $row["description"];
    $date = $row["concert_date"];
    $location = $row["location"];
    $url = $row["performance_url"];
?>

<div>
    <h3><?= $title ?></h3>

    <p><?= $desc ?></p>

    <p>
        Date: <?= $date ?><br>
        Location: <?= $location ? $location : "TBA" ?>
    </p>

    <?php if ($url) { ?>
        <a href="<?= $url ?>" target="_blank">Watch Performance</a>
    <?php } ?>
</div>

<hr>

<?php
}

if (!$hasPast) {
    echo "<p>No past performances.</p>";
}
?>

<?php
}
require "includes/footer.php";
?>