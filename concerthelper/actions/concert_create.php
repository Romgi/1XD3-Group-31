<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$title = trim((string) ($_POST["title"] ?? $_POST["concert_name"] ?? ""));
$concertId = trim((string) ($_POST["concert_id"] ?? ""));
$description = trim((string) ($_POST["description"] ?? ""));
$date = trim((string) ($_POST["date"] ?? ""));
$startTime = trim((string) ($_POST["start_time"] ?? ""));
$location = trim((string) ($_POST["location"] ?? ""));
$status = trim((string) ($_POST["status"] ?? "upcoming"));
$performanceUrl = trim((string) ($_POST["performance_url"] ?? ""));

if ($title === "" || $description === "" || $date === "") {
    adminJsonResponse(false, "Enter a concert title, description, and date.", 422);
}

if (!isValidDateInput($date)) {
    adminJsonResponse(false, "Enter a valid concert date.", 422);
}

if ($startTime !== "" && !isValidTimeInput($startTime)) {
    adminJsonResponse(false, "Enter a valid start time.", 422);
}

if (!in_array($status, ["upcoming", "past"], true)) {
    adminJsonResponse(false, "Choose a valid concert status.", 422);
}

if ($performanceUrl !== "" && normalizeExternalMediaUrl($performanceUrl) === null) {
    adminJsonResponse(false, "Enter a valid performance URL.", 422);
}

if ($concertId === "") {
    $concertId = adminSlugId($title);
}

$performanceFileName = null;

try {
    $performanceUrl = normalizeExternalMediaUrl($performanceUrl);
    $performanceFileName = saveUploadedFile("recording", PERFORMANCES_UPLOAD_DIR, ["mp3", "mp4", "m4a", "mov", "wav", "webm"]);

    $db = getDb();
    $statement = $db->prepare(
        "INSERT INTO concerts
            (concert_id, title, description, concert_date, start_time, location, status, performance_file_name, performance_url)
         VALUES
            (:concert_id, :title, :description, :concert_date, :start_time, :location, :status, :performance_file_name, :performance_url)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            concert_date = VALUES(concert_date),
            start_time = VALUES(start_time),
            location = VALUES(location),
            status = VALUES(status),
            performance_file_name = COALESCE(VALUES(performance_file_name), performance_file_name),
            performance_url = VALUES(performance_url)"
    );
    $statement->execute([
        ":concert_id" => $concertId,
        ":title" => $title,
        ":description" => $description,
        ":concert_date" => $date,
        ":start_time" => $startTime !== "" ? $startTime : null,
        ":location" => $location !== "" ? $location : null,
        ":status" => $status,
        ":performance_file_name" => $performanceFileName,
        ":performance_url" => $performanceUrl,
    ]);
} catch (Throwable $exception) {
    if ($performanceFileName !== null) {
        $uploadedFile = PERFORMANCES_UPLOAD_DIR . DIRECTORY_SEPARATOR . $performanceFileName;
        if (is_file($uploadedFile)) {
            unlink($uploadedFile);
        }
    }
    error_log("ConcertHelper concert create: " . $exception->getMessage());
    adminJsonResponse(false, "Concert could not be saved.", 500);
}

adminJsonResponse(true, "Concert saved.");
