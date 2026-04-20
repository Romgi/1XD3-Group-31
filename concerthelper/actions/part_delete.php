<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Deletes a part record and removes its uploaded PDF file from the server.
*/
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$partId = trim((string) ($_POST["part_id"] ?? ""));

if ($partId === "") {
    adminJsonResponse(false, "Choose a part to delete.", 422);
}

try {
    $db = getDb();
    $statement = $db->prepare(
        "SELECT part_id, instrument_part, file_name
         FROM parts
         WHERE part_id = :part_id
         LIMIT 1"
    );
    $statement->execute([":part_id" => $partId]);
    $part = $statement->fetch();

    if ($part === false) {
        adminJsonResponse(false, "Selected part could not be found.", 404);
    }

    $fileName = basename(str_replace("\\", "/", trim((string) ($part["file_name"] ?? ""))));

    $deleteStatement = $db->prepare(
        "DELETE FROM parts
         WHERE part_id = :part_id"
    );
    $deleteStatement->execute([":part_id" => $partId]);

    if ($fileName !== "") {
        $filePath = PARTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
} catch (Throwable $exception) {
    error_log("ConcertHelper part delete: " . $exception->getMessage());
    adminJsonResponse(false, "Part could not be deleted.", 500);
}

$partLabel = trim((string) ($part["instrument_part"] ?? ""));
$label = $partLabel !== "" ? $partLabel : $partId;

adminJsonResponse(true, "Deleted part {$label}.");
