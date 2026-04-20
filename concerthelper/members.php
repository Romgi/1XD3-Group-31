<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Displays the conductor profile and current member roster for the ensemble.
*/
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
<section class="page-heading page-heading-feature">
    <p class="section-tag">Members</p>
    <h1>McMaster Concert Band Members</h1>
    <p class="page-heading-intro">Meet the conductor and the current ensemble roster.</p>
</section>

<section class="member-profile" aria-labelledby="conductor-profile">
    <div class="member-profile-copy">
        <p class="section-tag">Conductor Profile</p>
        <h2 id="conductor-profile">Joseph Resendes</h2>
        <p class="member-profile-role">Conductor and instructor at McMaster University</p>
        <div class="member-profile-body">
            <p>
            Joseph Resendes is a Sessional Lecturer and Instructional Assistant at
            the School of the Arts at McMaster University in Hamilton, Ontario,
            Canada, where he conducts the McMaster University Concert Band
            and teaches courses in conducting. Previously, he worked as a
            Lecturer at the University of Saskatchewan Department of Music.
            </p>
            <p>
            Joseph has extensive credits as an active conductor, composer,
            adjudicator, and clinician. He has worked with many professionals
            and recording artists such as The Tenors, producers like Steve
            Thompson, the Musica em Viagem Wind Ensemble, the University of
            Saskatchewan Wind Orchestra, Northdale Concert Band, and other
            acclaimed ensembles including the University of North Texas Wind
            Symphony.
            </p>
            <p>
            Joseph holds degrees from York University in woodwind performance,
            conducting, and composition, and continues advanced research in wind
            studies. His work focuses on Canadian wind band literature, music
            history, conducting pedagogy, and gesture as communication.
            </p>
        </div>
    </div>
    <figure class="member-profile-media">
        <img
            class="conductor-photo"
            src="<?= e(appUrl("assets/images/conductor.webp")); ?>"
            alt="Joseph Resendes conducting the McMaster Concert Band">
        <figcaption>Joseph Resendes leading the McMaster Concert Band.</figcaption>
    </figure>
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
            <div class="member-section-header">
                <h2>Ensemble Members</h2>
                <p><?= e((string) count($members)); ?> active member<?= count($members) === 1 ? "" : "s"; ?> listed in the current roster.</p>
            </div>
            <ul class="member-list-grid">
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
                        <strong class="member-card-name"><?= e($memberName !== "" ? $memberName : $memberId); ?></strong>
                        <?php if ($summary !== ""): ?>
                            <p class="member-card-meta"><?= e($summary); ?></p>
                        <?php endif; ?>
                        <?php if ($description !== ""): ?>
                            <p class="member-card-note"><?= e($description); ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>
<?php endif; ?>
<?php require __DIR__ . "/includes/footer.php"; ?>
