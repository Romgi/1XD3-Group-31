<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$memberId = trim((string) ($_POST["member_id"] ?? ""));

if ($memberId === "") {
    adminJsonResponse(false, "Choose a member to delete.", 422);
}

try {
    $db = getDb();
    $statement = $db->prepare(
        "SELECT member_id, name
         FROM members
         WHERE member_id = :member_id
         LIMIT 1"
    );
    $statement->execute([":member_id" => $memberId]);
    $member = $statement->fetch();

    if ($member === false) {
        adminJsonResponse(false, "Selected member could not be found.", 404);
    }

    $db->beginTransaction();

    $deleteUsers = $db->prepare(
        "DELETE FROM users
         WHERE member_id = :member_id
           AND role = :role"
    );
    $deleteUsers->execute([
        ":member_id" => $memberId,
        ":role" => ROLE_MEMBER,
    ]);

    $deleteMember = $db->prepare(
        "DELETE FROM members
         WHERE member_id = :member_id"
    );
    $deleteMember->execute([":member_id" => $memberId]);

    $db->commit();
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("ConcertHelper member delete: " . $exception->getMessage());
    adminJsonResponse(false, "Member could not be deleted.", 500);
}

$memberName = trim((string) ($member["name"] ?? ""));
$label = $memberName !== "" ? $memberName : $memberId;

adminJsonResponse(true, "Deleted member {$label}.");
