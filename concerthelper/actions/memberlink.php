<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Assigns a member to a concert part and optionally links a reference recording.
*/
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$memberId = trim((string) ($_POST["member_id"] ?? $_POST["member_name"] ?? ""));
$partId = trim((string) ($_POST["part_id"] ?? ""));
$recordingId = trim((string) ($_POST["recording_id"] ?? $_POST["reference_name"] ?? ""));

if ($memberId === "" || $partId === "") {
    adminJsonResponse(false, "Choose a member and a part.", 422);
}

try {
    $db = getDb();
    $memberStatement = $db->prepare("SELECT member_id FROM members WHERE member_id = :member_id LIMIT 1");
    $memberStatement->execute([":member_id" => $memberId]);
    $member = $memberStatement->fetch();

    if ($member === false) {
        adminJsonResponse(false, "Selected member could not be found.", 404);
    }

    $partStatement = $db->prepare("SELECT concert_id FROM parts WHERE part_id = :part_id LIMIT 1");
    $partStatement->execute([":part_id" => $partId]);
    $part = $partStatement->fetch();

    if ($part === false) {
        adminJsonResponse(false, "Selected part could not be found.", 404);
    }

    $concertId = (string) $part["concert_id"];

    if ($recordingId !== "") {
        $recordingStatement = $db->prepare(
            "SELECT concert_id, part_id
             FROM recordings
             WHERE recording_id = :recording_id
             LIMIT 1"
        );
        $recordingStatement->execute([":recording_id" => $recordingId]);
        $recording = $recordingStatement->fetch();

        if ($recording === false) {
            adminJsonResponse(false, "Selected recording could not be found.", 404);
        }

        $recordingConcertId = (string) ($recording["concert_id"] ?? "");
        $recordingPartId = $recording["part_id"] !== null ? (string) $recording["part_id"] : null;

        if ($recordingConcertId !== $concertId || ($recordingPartId !== null && $recordingPartId !== $partId)) {
            adminJsonResponse(false, "Selected recording does not match the chosen part.", 422);
        }
    }

    $db->beginTransaction();

    $memberConcertStatement = $db->prepare(
        "INSERT INTO member_concerts (member_concert_id, member_id, concert_id)
         VALUES (:member_concert_id, :member_id, :concert_id)
         ON DUPLICATE KEY UPDATE
            member_id = VALUES(member_id),
            concert_id = VALUES(concert_id)"
    );
    $memberConcertStatement->execute([
        ":member_concert_id" => adminSlugId($memberId . "_" . $concertId),
        ":member_id" => $memberId,
        ":concert_id" => $concertId,
    ]);

    $memberPartStatement = $db->prepare(
        "INSERT INTO member_parts (member_part_id, member_id, part_id, concert_id)
         VALUES (:member_part_id, :member_id, :part_id, :concert_id)
         ON DUPLICATE KEY UPDATE
            member_id = VALUES(member_id),
            part_id = VALUES(part_id),
            concert_id = VALUES(concert_id)"
    );
    $memberPartStatement->execute([
        ":member_part_id" => adminSlugId($memberId . "_" . $partId),
        ":member_id" => $memberId,
        ":part_id" => $partId,
        ":concert_id" => $concertId,
    ]);

    if ($recordingId !== "") {
        $memberRecordingStatement = $db->prepare(
            "INSERT INTO member_recordings (member_recording_id, member_id, recording_id, concert_id)
             VALUES (:member_recording_id, :member_id, :recording_id, :concert_id)
             ON DUPLICATE KEY UPDATE
                member_id = VALUES(member_id),
                recording_id = VALUES(recording_id),
                concert_id = VALUES(concert_id)"
        );
        $memberRecordingStatement->execute([
            ":member_recording_id" => adminSlugId($memberId . "_" . $recordingId),
            ":member_id" => $memberId,
            ":recording_id" => $recordingId,
            ":concert_id" => $concertId,
        ]);
    }

    $db->commit();
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("ConcertHelper member link: " . $exception->getMessage());
    adminJsonResponse(false, "Member assignment could not be saved.", 500);
}

adminJsonResponse(true, "Member assignment saved.");
