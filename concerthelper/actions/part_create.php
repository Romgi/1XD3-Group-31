<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

function partSaveErrorMessage(PDOException $exception): string
{
    $sqlState = strtoupper((string) $exception->getCode());
    $message = strtolower($exception->getMessage());

    if ($sqlState === "22001") {
        if (str_contains($message, "part_id")) {
            return "That part name is too long. Use a shorter instrument part label.";
        }

        return "One of the part fields is too long for the database.";
    }

    if ($sqlState === "23000") {
        return "That part conflicts with an existing database record.";
    }

    if ($sqlState === "42S02" || $sqlState === "42S22") {
        return "The parts table is out of date. Re-import database/concerthelper_schema.sql.";
    }

    return "Part could not be saved.";
}

function partSaveErrorStatus(PDOException $exception): int
{
    return match (strtoupper((string) $exception->getCode())) {
        "22001", "23000" => 422,
        default => 500,
    };
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$concertId = trim((string) ($_POST["concert_id"] ?? ""));
$instrumentPart = trim((string) ($_POST["instrument_part"] ?? ""));

if ($concertId === "" || $instrumentPart === "") {
    adminJsonResponse(false, "Choose a concert and enter an instrument part.", 422);
}

if (strlen($concertId) > 191) {
    adminJsonResponse(false, "That concert ID is too long.", 422);
}

if (strlen($instrumentPart) > 255) {
    adminJsonResponse(false, "Instrument part must be 255 characters or fewer.", 422);
}

$fileName = null;
$oldFileName = null;

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
    if (strlen($partId) > 191) {
        adminJsonResponse(false, "That part name is too long. Use a shorter instrument part label.", 422);
    }

    $existingPartStatement = $db->prepare(
        "SELECT file_name
         FROM parts
         WHERE part_id = :part_id
         LIMIT 1"
    );
    $existingPartStatement->execute([":part_id" => $partId]);
    $existingPart = $existingPartStatement->fetch();
    if ($existingPart !== false) {
        $oldFileName = basename(str_replace("\\", "/", trim((string) ($existingPart["file_name"] ?? ""))));
    }

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

    if ($oldFileName !== null && $oldFileName !== "" && $oldFileName !== $fileName) {
        $oldFilePath = PARTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $oldFileName;
        if (is_file($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
} catch (RuntimeException $exception) {
    if ($fileName !== null) {
        $uploadedFile = PARTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($uploadedFile)) {
            unlink($uploadedFile);
        }
    }
    $message = $exception->getMessage();
    if ($message === "Unsupported file type.") {
        $message = "Part upload must be a PDF file.";
    }
    error_log("ConcertHelper part create upload: " . $exception->getMessage());
    adminJsonResponse(false, $message, 422);
} catch (PDOException $exception) {
    if ($fileName !== null) {
        $uploadedFile = PARTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($uploadedFile)) {
            unlink($uploadedFile);
        }
    }
    error_log("ConcertHelper part create SQL [" . $exception->getCode() . "]: " . $exception->getMessage());
    adminJsonResponse(false, partSaveErrorMessage($exception), partSaveErrorStatus($exception));
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
