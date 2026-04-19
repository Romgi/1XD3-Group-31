<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$concertId = trim((string) ($_POST["concert_id"] ?? $_POST["concert_name"] ?? ""));
$instrumentPart = trim((string) ($_POST["instrument_part"] ?? ""));

if ($concertId === "" || $instrumentPart === "") {
    adminJsonResponse(false, "Choose a concert and enter an instrument part.", 422);
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

    $fileName = saveUploadedFile("part", PARTS_UPLOAD_DIR, ["pdf"]);
    if ($fileName === null) {
        adminJsonResponse(false, "Upload a PDF part.", 422);
    }

    $partId = adminSlugId($concertId . "_" . $instrumentPart);
    $statement = $db->prepare(
        "INSERT INTO parts (part_id, concert_id, instrument_part, file_name)
         VALUES (:part_id, :concert_id, :instrument_part, :file_name)
         ON DUPLICATE KEY UPDATE
            concert_id = VALUES(concert_id),
            instrument_part = VALUES(instrument_part),
            file_name = VALUES(file_name)"
    );
    $statement->execute([
        ":part_id" => $partId,
        ":concert_id" => $concertId,
        ":instrument_part" => $instrumentPart,
        ":file_name" => $fileName,
    ]);
} catch (Throwable $exception) {
    if ($fileName !== null) {
        $uploadedFile = PARTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($uploadedFile)) {
            unlink($uploadedFile);
        }
    }
    error_log("ConcertHelper part create: " . $exception->getMessage());
    adminJsonResponse(false, "Part could not be saved.", 500);
}

adminJsonResponse(true, "Part saved.");
