<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

$pageTitle = "Members";
$activePage = "members";
$members = [];
$loadError = null;

try {
    $members = getMembers();
} catch (PDOException $exception) {
    error_log("ConcertHelper members page error: " . $exception->getMessage());
    $loadError = "Members could not be loaded. Please confirm the database connection and members table.";
}

require __DIR__ . "/includes/header.php";
?>
<section class="page-heading">
    <h1>McMaster Concert Band Members</h1>
</section>

<section class="member-profile" aria-labelledby="conductor-profile">
    <div>
        <h2 id="conductor-profile">Conductor Profile</h2>
        <p>
            Joseph Resendes conducts the McMaster Concert Band and the McMaster Symphony Orchestra
            and has taught music at McMaster University.
        </p>
    </div>
    <div class="image-placeholder" aria-label="Conductor visual placeholder">Conductor</div>
</section>

<?php if ($loadError !== null): ?>
    <section class="alert" role="alert">
        <p><?= e($loadError); ?></p>
    </section>
<?php elseif ($members === []): ?>
    <section class="empty-state">
        <h2>No members yet</h2>
        <p>Member names will appear here after rows are added to the members table.</p>
    </section>
<?php else: ?>
    <section class="member-section-grid" aria-label="Ensemble members">
        <article class="member-section">
            <h2>Ensemble Members</h2>
            <ul>
                <?php foreach ($members as $member): ?>
                    <?php
                    $memberId = (string) ($member["member_id"] ?? "");
                    $memberName = trim((string) ($member["name"] ?? ""));
                    $instrument = trim((string) ($member["instrument"] ?? ""));
                    $section = trim((string) ($member["section"] ?? ""));
                    $description = trim((string) ($member["description"] ?? ""));
                    $summary = trim(implode(" | ", array_filter([$instrument, $section], static fn ($value): bool => $value !== "")));
                    ?>
                    <li class="member-card">
                        <span>
                            <strong><?= e($memberName !== "" ? $memberName : $memberId); ?></strong>
                            <?php if ($summary !== ""): ?>
                                <br><span><?= e($summary); ?></span>
                            <?php endif; ?>
                            <?php if ($description !== ""): ?>
                                <br><span><?= e($description); ?></span>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>
<?php endif; ?>
<?php require __DIR__ . "/includes/footer.php"; ?>
