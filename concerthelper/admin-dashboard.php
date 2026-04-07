<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

$pageTitle = "Admin Dashboard";
$activePage = "dashboard";

requireRole([ROLE_ADMIN]);

$loadError = null;
$concerts = [];
$parts = [];
$members = [];
$recordings = [];

try {
    $concerts = getAdminConcertOptions();
    $parts = getAdminPartOptions();
    $members = getAdminMemberOptions();
    $recordings = getAdminRecordingOptions();
} catch (PDOException $exception) {
    error_log("ConcertHelper admin dashboard: " . $exception->getMessage());
    $loadError = "Admin data could not be loaded. Confirm the database is set up with final_project.sql.";
}

require __DIR__ . "/includes/header.php";
?>
<section class="page-heading">
    <h1>Admin Dashboard</h1>
</section>

<?php if ($loadError !== null): ?>
    <section class="alert" role="alert">
        <p><?= e($loadError); ?></p>
    </section>
<?php else: ?>
    <section class="admin-status" id="admin-status" aria-live="polite"></section>

    <section class="admin-grid" aria-label="Admin management forms">
        <article class="admin-card">
            <h2>Create Concert</h2>
            <form class="admin-form" id="concert" method="post" action="concert_create.php" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="concert-title">Concert Title</label>
                    <input id="concert-title" name="title" type="text" required>
                </div>
                <div class="form-field">
                    <label for="concert-id">Concert ID</label>
                    <input id="concert-id" name="concert_id" type="text" placeholder="spring_2026">
                </div>
                <div class="form-field">
                    <label for="concert-description">Description</label>
                    <textarea id="concert-description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="concert-date">Date</label>
                        <input id="concert-date" name="date" type="date" required>
                    </div>
                    <div class="form-field">
                        <label for="concert-time">Start Time</label>
                        <input id="concert-time" name="start_time" type="time">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="concert-location">Location</label>
                        <input id="concert-location" name="location" type="text">
                    </div>
                    <div class="form-field">
                        <label for="concert-status">Status</label>
                        <select id="concert-status" name="status">
                            <option value="upcoming">Upcoming</option>
                            <option value="past">Past</option>
                        </select>
                    </div>
                </div>
                <div class="form-field">
                    <label for="concert-url">Performance URL</label>
                    <input id="concert-url" name="performance_url" type="url">
                </div>
                <div class="form-field">
                    <label for="concert-recording">Performance File</label>
                    <input id="concert-recording" name="recording" type="file" accept="audio/*,video/*">
                </div>
                <button class="button" type="submit">Save Concert</button>
            </form>
        </article>

        <article class="admin-card">
            <h2>Create Member</h2>
            <form class="admin-form" id="create_member" method="post" action="member_create.php" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="member-name">Member Name</label>
                    <input id="member-name" name="member_name" type="text" required>
                </div>
                <div class="form-field">
                    <label for="member-id">Member ID</label>
                    <input id="member-id" name="member_id" type="text" placeholder="macid">
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="member-email">Email</label>
                        <input id="member-email" name="email" type="email">
                    </div>
                    <div class="form-field">
                        <label for="member-instrument">Instrument</label>
                        <input id="member-instrument" name="instrument" type="text">
                    </div>
                </div>
                <div class="form-field">
                    <label for="member-section">Section</label>
                    <input id="member-section" name="section" type="text">
                </div>
                <div class="form-field">
                    <label for="member-description">Description</label>
                    <textarea id="member-description" name="member_description" rows="4"></textarea>
                </div>
                <div class="form-field">
                    <label for="member-photo">Member Photo</label>
                    <input id="member-photo" name="member_photo" type="file" accept="image/*">
                </div>
                <button class="button" type="submit">Save Member</button>
            </form>
        </article>

        <article class="admin-card">
            <h2>Upload Part</h2>
            <form class="admin-form" id="part" method="post" action="part_create.php" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="part-concert">Concert</label>
                    <select id="part-concert" name="concert_id" required>
                        <option value="">Choose a concert</option>
                        <?php foreach ($concerts as $concert): ?>
                            <option value="<?= e($concert["concert_id"]); ?>"><?= e($concert["title"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="part-name">Instrument Part</label>
                    <input id="part-name" name="instrument_part" type="text" required>
                </div>
                <div class="form-field">
                    <label for="part-file">PDF Part</label>
                    <input id="part-file" name="part" type="file" accept="application/pdf" required>
                </div>
                <button class="button" type="submit">Save Part</button>
            </form>
        </article>

        <article class="admin-card">
            <h2>Add Reference Recording</h2>
            <form class="admin-form" id="recording_form" method="post" action="reference_create.php" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="reference-name">Recording Name</label>
                    <input id="reference-name" name="reference_name" type="text" required>
                </div>
                <div class="form-field">
                    <label for="reference-concert">Concert</label>
                    <select id="reference-concert" name="concert_id" required>
                        <option value="">Choose a concert</option>
                        <?php foreach ($concerts as $concert): ?>
                            <option value="<?= e($concert["concert_id"]); ?>"><?= e($concert["title"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="reference-part">Part</label>
                    <select id="reference-part" name="part_id">
                        <option value="">No specific part</option>
                        <?php foreach ($parts as $part): ?>
                            <option value="<?= e($part["part_id"]); ?>"><?= e($part["concert_title"] . " - " . $part["instrument_part"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="reference-url">Reference URL</label>
                    <input id="reference-url" name="recording_url" type="url">
                </div>
                <div class="form-field">
                    <label for="reference-video">Reference File</label>
                    <input id="reference-video" name="reference_video" type="file" accept="audio/*,video/*">
                </div>
                <button class="button" type="submit">Save Recording</button>
            </form>
        </article>

        <article class="admin-card">
            <h2>Assign Member Part</h2>
            <form class="admin-form" id="link_member" method="post" action="memberlink.php">
                <div class="form-field">
                    <label for="link-member">Member</label>
                    <select id="link-member" name="member_id" required>
                        <option value="">Choose a member</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= e($member["member_id"]); ?>"><?= e(($member["name"] ?? "") !== "" ? $member["name"] : $member["member_id"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="link-part">Part</label>
                    <select id="link-part" name="part_id" required>
                        <option value="">Choose a part</option>
                        <?php foreach ($parts as $part): ?>
                            <option value="<?= e($part["part_id"]); ?>"><?= e($part["concert_title"] . " - " . $part["instrument_part"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="link-recording">Recording</label>
                    <select id="link-recording" name="recording_id">
                        <option value="">No specific recording</option>
                        <?php foreach ($recordings as $recording): ?>
                            <option value="<?= e($recording["recording_id"]); ?>"><?= e($recording["part_name"]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="button" type="submit">Assign Part</button>
            </form>
        </article>
    </section>

    <script src="adminmain.js" defer></script>
<?php endif; ?>
<?php require __DIR__ . "/includes/footer.php"; ?>
