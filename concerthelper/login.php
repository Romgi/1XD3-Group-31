<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

startAppSession();

$pageTitle = "Member Login";
$activePage = "login";
$errors = [];
$email = "";
$currentRole = currentRole();

if ($currentRole !== null && ($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    redirectTo(currentDashboardUrl());
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $errors[] = "Enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    } elseif (strlen($email) > 254) {
        $errors[] = "Email must be 254 characters or fewer.";
    } else {
        $role = loginRole($email, $password);

        if ($role === null) {
            $errors[] = "Email or password is incorrect.";
        } elseif ($role === ROLE_MEMBER) {
            try {
                $memberId = getMemberIdByEmail($email);
            } catch (PDOException $exception) {
                error_log("ConcertHelper login member lookup: " . $exception->getMessage());
                $memberId = null;
                $errors[] = "Member lookup failed. Confirm the database is set up (see assets/memberDatabases.sql).";
            }
            if ($errors === [] && ($memberId ?? null) === null) {
                $errors[] = "No member profile matches this email.";
            }
            if ($errors === []) {
                signIn($role, $memberId);
                redirectTo(dashboardUrlForRole($role));
            }
        } else {
            signIn($role, null);
            redirectTo(dashboardUrlForRole($role));
        }
    }
}

require __DIR__ . "/includes/header.php";
?>
<section class="page-heading">
    <h1>Member Login</h1>
</section>

<?php if ($errors !== []): ?>
    <section class="alert" role="alert" aria-labelledby="login-errors">
        <h2 id="login-errors">Login could not be completed</h2>
        <?php foreach ($errors as $error): ?>
            <p><?= e($error); ?></p>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<section class="auth-card" aria-label="Member login form">
    <form class="auth-form" method="post" action="login.php" novalidate>
        <div class="form-field">
            <label for="email">Email:</label>
            <input
                id="email"
                name="email"
                type="email"
                value="<?= e($email); ?>"
                maxlength="254"
                autocomplete="username"
                required>
        </div>

        <div class="form-field">
            <label for="password">Password:</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required>
        </div>

        <div class="auth-actions">
            <button class="button" type="submit">Sign In</button>
            <a href="mailto:concertband@mcmaster.ca">Trouble Signing In?</a>
        </div>
    </form>
</section>
<?php require __DIR__ . "/includes/footer.php"; ?>
