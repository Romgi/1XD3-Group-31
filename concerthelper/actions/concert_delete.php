<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$concertId = trim((string) ($_POST["concert_id"] ?? ""));

if ($concertId === "") {
    adminJsonResponse(false, "Choose a concert to delete.", 422);
}

try {
    $db = getDb();

    $concertStatement = $db->prepare(
        "SELECT concert_id, title, performance_file_name
         FROM concerts
         WHERE concert_id = :concert_id
         LIMIT 1"
    );
    $concertStatement->execute([":concert_id" => $concertId]);
    $concert = $concertStatement->fetch();

    if ($concert === false) {
        adminJsonResponse(false, "Selected concert could not be found.", 404);
    }

    $partFilesStatement = $db->prepare(
        "SELECT file_name
         FROM parts
         WHERE concert_id = :concert_id"
    );
    $partFilesStatement->execute([":concert_id" => $concertId]);
    $partFiles = $partFilesStatement->fetchAll(PDO::FETCH_COLUMN);

    $recordingFilesStatement = $db->prepare(
        "SELECT file_name
         FROM recordings
         WHERE concert_id = :concert_id"
    );
    $recordingFilesStatement->execute([":concert_id" => $concertId]);
    $recordingFiles = $recordingFilesStatement->fetchAll(PDO::FETCH_COLUMN);

    $db->beginTransaction();

    $deleteStatement = $db->prepare(
        "DELETE FROM concerts
         WHERE concert_id = :concert_id"
    );
    $deleteStatement->execute([":concert_id" => $concertId]);

    $db->commit();

    $deleteUploadedFile = static function (string $directory, mixed $fileName): void {
        $safeFileName = basename(str_replace("\\", "/", trim((string) $fileName)));
        if ($safeFileName === "") {
            return;
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $safeFileName;
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    };

    $deleteUploadedFile(PERFORMANCES_UPLOAD_DIR, $concert["performance_file_name"] ?? "");

    foreach ($partFiles as $fileName) {
        $deleteUploadedFile(PARTS_UPLOAD_DIR, $fileName);
    }

    foreach ($recordingFiles as $fileName) {
        $deleteUploadedFile(PERFORMANCES_UPLOAD_DIR, $fileName);
    }
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("ConcertHelper concert delete: " . $exception->getMessage());
    adminJsonResponse(false, "Concert could not be deleted.", 500);
}

$concertTitle = trim((string) ($concert["title"] ?? ""));
$label = $concertTitle !== "" ? $concertTitle : $concertId;

adminJsonResponse(true, "Deleted concert {$label}.");
