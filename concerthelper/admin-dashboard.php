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
$users = [];

try {
    $concerts = getAdminConcertOptions();
    $parts = getAdminPartOptions();
    $members = getAdminMemberOptions();
    $recordings = getAdminRecordingOptions();
    $users = getAdminUserOptions();
} catch (PDOException $exception) {
    error_log("ConcertHelper admin dashboard: " . $exception->getMessage());
    $loadError = "Admin data could not be loaded. Confirm the database is set up with database/concerthelper_schema.sql.";
}

$summaryCounts = [
    ["label" => "Concerts", "value" => count($concerts)],
    ["label" => "Parts", "value" => count($parts)],
    ["label" => "Members", "value" => count($members)],
    ["label" => "Accounts", "value" => count($users)],
];

require __DIR__ . "/includes/header.php";
?>
<?php if ($loadError !== null): ?>
    <section class="page-heading admin-hero">
        <div class="admin-hero-copy">
            <h1>Admin Dashboard</h1>
            <p class="admin-hero-text">Manage concerts, member data, parts, recordings, and account access from one place.</p>
        </div>
    </section>
    <section class="alert" role="alert">
        <p><?= e($loadError); ?></p>
    </section>
<?php else: ?>
    <section class="page-heading admin-hero">
        <div class="admin-hero-copy">
            <h1>Admin Dashboard</h1>
            <p class="admin-hero-text">Manage concerts, member data, parts, recordings, and account access from one place.</p>
        </div>
        <div class="admin-summary" aria-label="Admin summary">
            <?php foreach ($summaryCounts as $item): ?>
                <article class="admin-summary-card">
                    <span class="admin-summary-value"><?= e((string) $item["value"]); ?></span>
                    <span class="admin-summary-label"><?= e((string) $item["label"]); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-status" id="admin-status" aria-live="polite"></section>

    <section class="admin-layout" aria-label="Admin management forms">
        <section class="admin-column" aria-labelledby="admin-programming-heading">
            <header class="admin-column-heading">
                <p class="admin-section-kicker">Programming</p>
                <h2 id="admin-programming-heading">Concerts and Library</h2>
                <p>Build each performance from the top down: concert details, assigned parts, and reference material.</p>
            </header>

            <article class="admin-card">
                <h3>Create Concert</h3>
                <p class="admin-card-copy">Add a concert to the public schedule and member workspace.</p>
                <form class="admin-form" id="concert" method="post" action="actions/concert_create.php" enctype="multipart/form-data">
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
                <h3>Upload Part</h3>
                <p class="admin-card-copy">Attach a PDF part to a concert so players can open it from their dashboard.</p>
                <form class="admin-form" id="part" method="post" action="actions/part_create.php" enctype="multipart/form-data">
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
                <h3>Add Reference Recording</h3>
                <p class="admin-card-copy">Give members a guide track or rehearsal recording for a concert or specific part.</p>
                <form class="admin-form" id="recording_form" method="post" action="actions/reference_create.php" enctype="multipart/form-data">
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
        </section>

        <section class="admin-column" aria-labelledby="admin-people-heading">
            <header class="admin-column-heading">
                <p class="admin-section-kicker">Operations</p>
                <h2 id="admin-people-heading">Members and Access</h2>
                <p>Set up the people side of the system: create member records, assign music, and manage sign-in credentials.</p>
            </header>

            <article class="admin-card">
                <h3>Create Member</h3>
                <p class="admin-card-copy">Add or update a member profile for the public members page and band roster.</p>
                <form class="admin-form" id="create_member" method="post" action="actions/member_create.php">
                    <div class="form-field">
                        <label for="member-name">Member Name</label>
                        <input id="member-name" name="member_name" type="text" maxlength="255" required>
                    </div>
                    <div class="form-field">
                        <label for="member-id">Member ID</label>
                        <input id="member-id" name="member_id" type="text" maxlength="191" placeholder="macid">
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="member-email">Email</label>
                            <input id="member-email" name="email" type="email" maxlength="254">
                        </div>
                        <div class="form-field">
                            <label for="member-instrument">Instrument</label>
                            <input id="member-instrument" name="instrument" type="text" maxlength="191">
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="member-section">Section</label>
                        <input id="member-section" name="section" type="text" maxlength="191">
                    </div>
                    <button class="button" type="submit">Save Member</button>
                </form>
            </article>

            <article class="admin-card">
                <h3>Assign Member Part</h3>
                <p class="admin-card-copy">Link a member to the correct part and optional reference recording for the selected concert.</p>
                <form class="admin-form" id="link_member" method="post" action="actions/memberlink.php">
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

            <article class="admin-card">
                <h3>Manage Passwords</h3>
                <p class="admin-card-copy">Reset sign-in access for any existing account in the system.</p>
                <?php if ($users === []): ?>
                    <p class="empty-parts">No user accounts are available.</p>
                <?php else: ?>
                    <form class="admin-form" id="password_form" method="post" action="actions/password_update.php">
                        <div class="form-field">
                            <label for="password-user">User Account</label>
                            <select id="password-user" name="user_id" required>
                                <option value="">Choose a user</option>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $displayName = trim((string) ($user["display_name"] ?? ""));
                                    $email = trim((string) ($user["email"] ?? ""));
                                    $role = strtoupper((string) ($user["role"] ?? ""));
                                    $labelParts = array_filter([$displayName, $email], static fn ($value): bool => $value !== "");
                                    $label = implode(" - ", $labelParts);
                                    if ($label === "") {
                                        $label = (string) ($user["user_id"] ?? "");
                                    }
                                    ?>
                                    <option value="<?= e($user["user_id"]); ?>"><?= e($label . " (" . $role . ")"); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="password-new">New Password</label>
                            <input id="password-new" name="password" type="password" minlength="8" maxlength="72" required>
                        </div>
                        <div class="form-field">
                            <label for="password-confirm">Confirm Password</label>
                            <input id="password-confirm" name="confirm_password" type="password" minlength="8" maxlength="72" required>
                        </div>
                        <button class="button" type="submit">Update Password</button>
                    </form>
                <?php endif; ?>
            </article>
        </section>
    </section>

    <script src="assets/js/adminmain.js" defer></script>
<?php endif; ?>
<?php require __DIR__ . "/includes/footer.php"; ?>
