<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$recordingId = trim((string) ($_POST["recording_id"] ?? ""));

if ($recordingId === "") {
    adminJsonResponse(false, "Choose a performance to delete.", 422);
}

try {
    $db = getDb();
    $statement = $db->prepare(
        "SELECT recording_id, part_name, file_name
         FROM recordings
         WHERE recording_id = :recording_id
         LIMIT 1"
    );
    $statement->execute([":recording_id" => $recordingId]);
    $recording = $statement->fetch();

    if ($recording === false) {
        adminJsonResponse(false, "Selected performance could not be found.", 404);
    }

    $fileName = basename(str_replace("\\", "/", trim((string) ($recording["file_name"] ?? ""))));

    $deleteStatement = $db->prepare(
        "DELETE FROM recordings
         WHERE recording_id = :recording_id"
    );
    $deleteStatement->execute([":recording_id" => $recordingId]);

    if ($fileName !== "") {
        $filePath = PERFORMANCES_UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
} catch (Throwable $exception) {
    error_log("ConcertHelper recording delete: " . $exception->getMessage());
    adminJsonResponse(false, "Performance could not be deleted.", 500);
}

$recordingName = trim((string) ($recording["part_name"] ?? ""));
$label = $recordingName !== "" ? $recordingName : $recordingId;

adminJsonResponse(true, "Deleted performance {$label}.");
