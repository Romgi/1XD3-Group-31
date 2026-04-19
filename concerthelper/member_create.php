<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$memberName = trim((string) ($_POST["member_name"] ?? ""));
$memberId = trim((string) ($_POST["member_id"] ?? ""));
$description = trim((string) ($_POST["member_description"] ?? ""));
$email = trim((string) ($_POST["email"] ?? ""));
$instrument = trim((string) ($_POST["instrument"] ?? ""));
$section = trim((string) ($_POST["section"] ?? ""));

if ($memberName === "") {
    adminJsonResponse(false, "Enter a member name.", 422);
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    adminJsonResponse(false, "Enter a valid email address.", 422);
}

if ($memberId === "") {
    $memberId = adminSlugId($memberName);
}

$photoFileName = null;

try {
    $photoFileName = saveUploadedFile("member_photo", MEMBER_PHOTO_UPLOAD_DIR, ["jpg", "jpeg", "png", "gif", "webp"]);

    $db = getDb();
    $statement = $db->prepare(
        "INSERT INTO members
            (member_id, name, instrument, section, email, file_name, description, is_active)
         VALUES
            (:member_id, :name, :instrument, :section, :email, :file_name, :description, 1)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            instrument = VALUES(instrument),
            section = VALUES(section),
            email = VALUES(email),
            file_name = CASE
                WHEN VALUES(file_name) <> '' THEN VALUES(file_name)
                ELSE file_name
            END,
            description = VALUES(description),
            is_active = 1"
    );
    $statement->execute([
        ":member_id" => $memberId,
        ":name" => $memberName,
        ":instrument" => $instrument !== "" ? $instrument : null,
        ":section" => $section !== "" ? $section : null,
        ":email" => $email !== "" ? $email : null,
        ":file_name" => $photoFileName ?? "",
        ":description" => $description,
    ]);
} catch (Throwable $exception) {
    if ($photoFileName !== null) {
        $uploadedFile = MEMBER_PHOTO_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFileName;
        if (is_file($uploadedFile)) {
            unlink($uploadedFile);
        }
    }
    error_log("ConcertHelper member create: " . $exception->getMessage());
    adminJsonResponse(false, "Member could not be saved.", 500);
}

adminJsonResponse(true, "Member saved.");
