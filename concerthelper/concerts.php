<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

$pageTitle = "Concerts";
$activePage = "concerts";

$error = false;

try {
    $upcomingStmt = getConcertsByStatus("upcoming");
    $pastStmt = getConcertsByStatus("past");
} catch (PDOException $e) {
    error_log("ConcertHelper concerts page error: " . $e->getMessage());
    $error = true;
}

require __DIR__ . "/includes/header.php";
?>

<section class="page-heading">
    <h1>Concerts</h1>
</section>

<?php if ($error): ?>
    <section class="alert" role="alert">
        <p>Something went wrong loading concerts.</p>
    </section>
<?php else: ?>

    <section class="concert-section" aria-labelledby="upcoming-concerts">
        <h2 id="upcoming-concerts">Upcoming Concerts</h2>

        <div class="concert-list">
            <?php
            $hasUpcoming = false;

            while ($row = $upcomingStmt->fetch()) {
                $hasUpcoming = true;

                $title = (string) ($row["title"] ?? "");
                $desc = (string) ($row["description"] ?? "");
                $date = (string) ($row["concert_date"] ?? "");
                $time = (string) ($row["start_time"] ?? "");
                $location = (string) ($row["location"] ?? "");
            ?>

                <article class="concert-card">
                    <h3><?= e($title); ?></h3>

                    <?php if (trim($desc) !== ""): ?>
                        <p><?= e($desc); ?></p>
                    <?php endif; ?>

                    <dl class="concert-details">
                        <div>
                            <dt>Date</dt>
                            <dd><?= e($date); ?></dd>
                        </div>
                        <div>
                            <dt>Time</dt>
                            <dd><?= e($time !== "" ? $time : "TBA"); ?></dd>
                        </div>
                        <div>
                            <dt>Location</dt>
                            <dd><?= e($location !== "" ? $location : "TBA"); ?></dd>
                        </div>
                    </dl>
                </article>

            <?php } ?>

            <?php if (!$hasUpcoming): ?>
                <p class="empty-parts">No upcoming concerts.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="concert-section" aria-labelledby="past-performances">
        <h2 id="past-performances">Past Performances</h2>

        <div class="concert-list">
            <?php
            $hasPast = false;

            while ($row = $pastStmt->fetch()) {
                $hasPast = true;

                $title = (string) ($row["title"] ?? "");
                $desc = (string) ($row["description"] ?? "");
                $date = (string) ($row["concert_date"] ?? "");
                $location = (string) ($row["location"] ?? "");
                $url = trim((string) ($row["performance_url"] ?? ""));
            ?>

                <article class="concert-card">
                    <h3><?= e($title); ?></h3>

                    <?php if (trim($desc) !== ""): ?>
                        <p><?= e($desc); ?></p>
                    <?php endif; ?>

                    <dl class="concert-details">
                        <div>
                            <dt>Date</dt>
                            <dd><?= e($date); ?></dd>
                        </div>
                        <div>
                            <dt>Location</dt>
                            <dd><?= e($location !== "" ? $location : "TBA"); ?></dd>
                        </div>
                    </dl>

                    <?php if ($url !== ""): ?>
                        <p class="concert-actions">
                            <a class="button" href="<?= e($url); ?>" target="_blank" rel="noopener noreferrer">Watch Performance</a>
                        </p>
                    <?php endif; ?>
                </article>

            <?php } ?>

            <?php if (!$hasPast): ?>
                <p class="empty-parts">No past performances.</p>
            <?php endif; ?>
        </div>
    </section>

<?php endif; ?>
<?php require __DIR__ . "/includes/footer.php"; ?>
