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
            Joseph Resendes is a Sessional Lecturer and Instructional Assistant at
the School of the Arts at McMaster University in Hamilton, Ontario,
Canada, where he conducts the McMaster University Concert Band,
and teaches courses in conducting. Previously, he worked as a
Lecturer at the University of Saskatchewan Department of Music.
Joseph has extensive credits as an active conductor, composer,
adjudicator, and clinician that has allowed him the privilege of working
with many professionals and recording artists such as 'The Tenors'
(formerly the Canadian Tenors), multiGrammy award winning
producers like Steve Thompson, international tours with the 'Musica
em Viagem' (Azores Musical Journey) Wind Ensemble, conducting the
University of Saskatchewan Wind Orchestra, Northdale Concert Band,
in addition to many highly acclaimed ensembles such as the University
of North Texas Wind Symphony.
Joseph currently holds degrees from York University (BFA -woodwind
performance and conducting, MA - Composition) and is working
towards the completion of a PhD in musicology focusing in the area of
wind studies. As a conductor, Joseph receives regular invitations to
conduct or guest conduct orchestras, chamber ensembles, and wind
ensembles locally and abroad. Joseph's primary research interests
include the dissemination of Canadian wind bands, literature, and
Canadian music history. Research interests also include conducting,
conducting pedagogy, and gesture as communication.

        </p>
    </div>
    <img
        class="conductor-photo"
        src="<?= e(appUrl("assets/images/conductor.webp")); ?>"
        alt="Joseph Resendes conducting the McMaster Concert Band">
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
