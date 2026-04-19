<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$referenceName = trim((string) ($_POST["reference_name"] ?? ""));
$concertId = trim((string) ($_POST["concert_id"] ?? $_POST["concert_name"] ?? ""));
$partId = trim((string) ($_POST["part_id"] ?? ""));
$recordingUrl = trim((string) ($_POST["recording_url"] ?? ""));

if ($referenceName === "" || $concertId === "") {
    adminJsonResponse(false, "Enter a recording name and choose a concert.", 422);
}

if ($recordingUrl !== "" && normalizeExternalMediaUrl($recordingUrl) === null) {
    adminJsonResponse(false, "Enter a valid reference URL.", 422);
}

$fileName = null;

try {
    $db = getDb();
    $concertStatement = $db->prepare("SELECT concert_id FROM concerts WHERE concert_id = :concert_id LIMIT 1");
    $concertStatement->execute([":concert_id" => $concertId]);
    $concert = $concertStatement->fetch();

    if ($concert === false) {
        adminJsonResponse(false, "Selected concert could not be found.", 404);
    }

    if ($partId !== "") {
        $partStatement = $db->prepare(
            "SELECT part_id
             FROM parts
             WHERE part_id = :part_id
               AND concert_id = :concert_id
             LIMIT 1"
        );
        $partStatement->execute([
            ":part_id" => $partId,
            ":concert_id" => $concertId,
        ]);
        $part = $partStatement->fetch();

        if ($part === false) {
            adminJsonResponse(false, "Selected part does not belong to the chosen concert.", 422);
        }
    }

    $recordingUrl = normalizeExternalMediaUrl($recordingUrl);
    $fileName = saveUploadedFile("reference_video", PERFORMANCES_UPLOAD_DIR, ["mp3", "mp4", "m4a", "mov", "wav", "webm"]);
    if ($fileName === null && $recordingUrl === null) {
        adminJsonResponse(false, "Add either a reference URL or a reference file.", 422);
    }

    $recordingId = adminSlugId($concertId . "_" . $referenceName);
    $recordingType = recordingTypeForUrl($recordingUrl);

    $statement = $db->prepare(
        "INSERT INTO recordings
            (recording_id, concert_id, part_id, part_name, file_name, recording_url, recording_type)
         VALUES
            (:recording_id, :concert_id, :part_id, :part_name, :file_name, :recording_url, :recording_type)
         ON DUPLICATE KEY UPDATE
            concert_id = VALUES(concert_id),
            part_id = VALUES(part_id),
            part_name = VALUES(part_name),
            file_name = COALESCE(VALUES(file_name), file_name),
            recording_url = VALUES(recording_url),
            recording_type = VALUES(recording_type)"
    );
    $statement->execute([
        ":recording_id" => $recordingId,
        ":concert_id" => $concertId,
        ":part_id" => $partId !== "" ? $partId : null,
        ":part_name" => $referenceName,
        ":file_name" => $fileName,
        ":recording_url" => $recordingUrl,
        ":recording_type" => $recordingType,
    ]);
} catch (Throwable $exception) {
    if ($fileName !== null) {
        $uploadedFile = PERFORMANCES_UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($uploadedFile)) {
            unlink($uploadedFile);
        }
    }
    error_log("ConcertHelper reference create: " . $exception->getMessage());
    adminJsonResponse(false, "Reference recording could not be saved.", 500);
}

adminJsonResponse(true, "Reference recording saved.");
