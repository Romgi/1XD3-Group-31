<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$userId = trim((string) ($_POST["user_id"] ?? ""));
$password = (string) ($_POST["password"] ?? "");
$confirmPassword = (string) ($_POST["confirm_password"] ?? "");

if ($userId === "") {
    adminJsonResponse(false, "Choose a user account.", 422);
}

$passwordError = validatePasswordInput($password);
if ($passwordError !== null) {
    adminJsonResponse(false, $passwordError, 422);
}

if ($password !== $confirmPassword) {
    adminJsonResponse(false, "Passwords do not match.", 422);
}

try {
    $db = getDb();
    $statement = $db->prepare(
        "SELECT user_id, email
         FROM users
         WHERE user_id = :user_id
         LIMIT 1"
    );
    $statement->execute([":user_id" => $userId]);
    $user = $statement->fetch();

    if ($user === false) {
        adminJsonResponse(false, "Selected user could not be found.", 404);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($hash) || $hash === "") {
        throw new RuntimeException("Password hashing failed.");
    }

    $updateStatement = $db->prepare(
        "UPDATE users
         SET password_hash = :password_hash
         WHERE user_id = :user_id"
    );
    $updateStatement->execute([
        ":password_hash" => $hash,
        ":user_id" => $userId,
    ]);
} catch (Throwable $exception) {
    error_log("ConcertHelper password update: " . $exception->getMessage());
    adminJsonResponse(false, "Password could not be updated.", 500);
}

$email = trim((string) ($user["email"] ?? ""));
$label = $email !== "" ? $email : $userId;

adminJsonResponse(true, "Password updated for {$label}.");
