<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

$pageTitle = "Member Dashboard";
$activePage = "dashboard";

requireRole([ROLE_MEMBER]);

require __DIR__ . "/includes/header.php";

$memberId = currentMemberId();
$parts = [];
$loadError = null;
$greeting = "Member";

if ($memberId === null) {
    $loadError = "Your session is missing member information. Please sign out and sign in again.";
} else {
    try {
        $parts = getMemberParts($memberId);
        $greeting = getMemberDisplayName($memberId);
    } catch (PDOException $exception) {
        error_log("ConcertHelper member dashboard: " . $exception->getMessage());
        $loadError = "Parts could not be loaded. In phpMyAdmin, select your app database and import database/concerthelper_schema.sql so the member_parts table exists.";
    }
}
?>
<?php if ($loadError !== null): ?>
    <section class="alert" role="alert">
        <p><?= e($loadError); ?></p>
    </section>
<?php else: ?>
    <section class="page-heading">
        <h1>Member Dashboard</h1>
        <p class="dashboard-lead">Welcome, <?= e($greeting); ?>!</p>
    </section>

    <section class="dashboard-stack" aria-label="Member dashboard sections">
        <section class="wireframe-card dashboard-panel" aria-labelledby="my-parts-heading">
            <h2 id="my-parts-heading">My Parts</h2>

            <?php if ($parts === []): ?>
                <p class="empty-parts">No parts assigned yet.</p>
            <?php else: ?>
                <div class="member-part-list">
                    <?php foreach ($parts as $part): ?>
                        <?php
                        $label = partDisplayLabel($part);
                        $pdfUrl = partPdfUrl(isset($part["pdf_file_name"]) ? (string) $part["pdf_file_name"] : null);
                        $playUrl = partPlayUrl($part);
                        ?>
                        <article class="member-part-card">
                            <h3><?= e($label); ?></h3>
                            <div class="member-part-actions">
                                <?php if ($pdfUrl !== null): ?>
                                    <a
                                        class="part-icon part-icon-pdf"
                                        href="<?= e($pdfUrl); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Open PDF for <?= e($label); ?>">PDF</a>
                                <?php else: ?>
                                    <span class="part-icon-missing" title="PDF not on server">-</span>
                                <?php endif; ?>
                                <?php if ($playUrl !== null): ?>
                                    <a
                                        class="part-icon part-icon-play"
                                        href="<?= e($playUrl); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Open recording for <?= e($label); ?> (new tab)">Play</a>
                                <?php else: ?>
                                    <span class="part-icon-missing" title="No external link or performance file">-</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="wireframe-card dashboard-panel" aria-labelledby="request-part-heading">
            <h2 id="request-part-heading">Request a Part</h2>
            <p class="dashboard-request-text">Need a missing part or an updated file? Contact the professor directly.</p>
            <a class="button button-pill" href="mailto:professor@mcmaster.ca">Request a Part</a>
        </section>
    </section>
<?php endif; ?>
<?php require __DIR__ . "/includes/footer.php"; ?>
